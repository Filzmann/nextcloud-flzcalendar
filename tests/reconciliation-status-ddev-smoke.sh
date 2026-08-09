#!/usr/bin/env bash
set -euo pipefail

base_url="${ADC_BASE_URL:-https://nextcloud-dev.ddev.site}"
ddev_project="${ADC_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"
status_key="shift_calendar_reconciliation_status"
suffix="$(date +%s)-$$"
password="$(php -r 'echo bin2hex(random_bytes(24));')"
admin_uid="adc-status-${suffix}-admin"
user_uid="adc-status-${suffix}-user"
admin_page="$(mktemp)"
user_page="$(mktemp)"
sync_panel="$(mktemp)"
previous_status=""
created_users=()

occ() {
    (cd "$ddev_project" && ddev exec -d /var/www/html/html php occ "$@")
}

cleanup() {
    local uid
    if [[ -n "$previous_status" ]]; then
        occ config:app:set adcalendar "$status_key" --value="$previous_status" --type=string --lazy >/dev/null 2>&1 || true
    else
        occ config:app:delete adcalendar "$status_key" >/dev/null 2>&1 || true
    fi
    for uid in "${created_users[@]}"; do
        occ user:delete "$uid" >/dev/null 2>&1 || true
    done
    rm -f "$admin_page" "$user_page" "$sync_panel"
}
trap cleanup EXIT

create_user() {
    local uid="$1"
    (cd "$ddev_project" && ddev exec -d /var/www/html/html env OC_PASS="$password" php occ user:add --password-from-env "$uid") >/dev/null
    created_users+=("$uid")
}

set_status() {
    local attempted="$1"
    local succeeded="$2"
    local failed="$3"
    local payload
    payload="{\"schemaVersion\":1,\"direction\":\"outbound\",\"lastRunAt\":1753000000,\"attempted\":${attempted},\"succeeded\":${succeeded},\"failed\":${failed}}"
    occ config:app:set adcalendar "$status_key" --value="$payload" --type=string --lazy >/dev/null
}

fetch_admin_page() {
    curl --fail --silent --show-error --insecure --user "$admin_uid:$password" \
        "$base_url/index.php/settings/admin/adcalendar" --output "$admin_page"
}

assert_summary() {
    local state="$1"
    local attempted="$2"
    local succeeded="$3"
    local failed="$4"
    grep -q "adc-sync-state--${state}" "$admin_page"
    grep -q "<dd>${attempted}</dd>" "$admin_page"
    grep -q "<dd>${succeeded}</dd>" "$admin_page"
    grep -q "<dd>${failed}</dd>" "$admin_page"
    sed -n '/id="adc-calendar-sync-heading"/,/<\/section>/p' "$admin_page" > "$sync_panel"
    if rg -q 'adc-status-|calendarUri|providerId|errorDetail' "$sync_panel"; then
        echo "Aggregierter Adminstatus enthält technische oder personenbezogene Details." >&2
        exit 1
    fi
}

previous_status="$(occ config:app:get adcalendar "$status_key")"
create_user "$admin_uid"
create_user "$user_uid"
occ group:adduser admin "$admin_uid" >/dev/null

set_status 4 4 0
fetch_admin_page
assert_summary success 4 4 0

set_status 5 4 1
fetch_admin_page
assert_summary warning 5 4 1

user_status="$(curl --silent --show-error --insecure --user "$user_uid:$password" \
    --output "$user_page" --write-out '%{http_code}' \
    "$base_url/index.php/settings/admin/adcalendar")"
if [[ "$user_status" == "200" ]] || rg -q 'id="adcalendar-admin"' "$user_page"; then
    echo "Nichtadmin kann den AD-Kalender-Adminstatus aufrufen." >&2
    exit 1
fi

echo "AD Calendar reconciliation status DDEV smoke: OK"
