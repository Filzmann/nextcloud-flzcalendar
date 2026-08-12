#!/usr/bin/env bash
set -euo pipefail

base_url="${ADC_BASE_URL:-https://nextcloud-dev.ddev.site}"
ddev_project="${ADC_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"
uid="adc-admin-smoke-$(date +%s)-$$"
password="$(php -r 'echo bin2hex(random_bytes(24));')"
workdir="$(mktemp -d)"
cookies="$workdir/cookies.txt"
page="$workdir/page.html"
saved="$workdir/saved.json"
invalid="$workdir/invalid.json"

occ() {
    (cd "$ddev_project" && ddev exec -d /var/www/html/html php occ "$@")
}

old_url=''
old_name=''
had_url=false
had_name=false
if old_url="$(occ config:app:get adcalendar calendar_default_kopano_url 2>/dev/null)"; then had_url=true; fi
if old_name="$(occ config:app:get adcalendar calendar_default_name 2>/dev/null)"; then had_name=true; fi

restore_config() {
    local key="$1" had_value="$2" value="$3"
    if [[ "$had_value" == true ]]; then
        occ config:app:set adcalendar "$key" --value="$value" --type=string --sensitive >/dev/null 2>&1 || true
    else
        occ config:app:delete adcalendar "$key" >/dev/null 2>&1 || true
    fi
}

cleanup() {
    restore_config calendar_default_kopano_url "$had_url" "$old_url"
    restore_config calendar_default_name "$had_name" "$old_name"
    occ user:delete "$uid" >/dev/null 2>&1 || true
    rm -rf "$workdir"
}
trap cleanup EXIT

(cd "$ddev_project" && ddev exec -d /var/www/html/html env OC_PASS="$password" php occ user:add --password-from-env "$uid") >/dev/null
occ group:adduser admin "$uid" >/dev/null

curl --fail --silent --show-error --insecure --user "$uid:$password" \
    --cookie-jar "$cookies" "$base_url/index.php/apps/adcalendar/" --output "$page"
token="$(sed -n 's/.*data-requesttoken="\([^"]*\)".*/\1/p' "$page" | head -n 1)"
if [[ -z "$token" ]]; then
    echo 'Request-Token fehlt.' >&2
    exit 1
fi

endpoint="$base_url/index.php/apps/adcalendar/api/admin/calendar-defaults"
status="$(curl --silent --show-error --insecure --user "$uid:$password" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X PUT --data '{"kopanoUrl":"https://calendar.example.test","calendarName":"RC Testkalender"}' \
    --write-out '%{http_code}' --output "$saved" "$endpoint")"
if [[ "$status" != 200 ]]; then
    echo "Admin-Speichern ergab HTTP $status statt 200." >&2
    exit 1
fi
php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
if (($data["calendarDefaults"] ?? null) !== ["kopanoUrl" => "https://calendar.example.test/", "calendarName" => "RC Testkalender"]) {
    throw new RuntimeException("Gespeicherte Kalenderdefaults fehlen in der Antwort.");
}
' "$saved"

status="$(curl --silent --show-error --insecure --user "$uid:$password" \
    --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X PUT --data '{"kopanoUrl":"http://unsafe.example.test","calendarName":""}' \
    --write-out '%{http_code}' --output "$invalid" "$endpoint")"
if [[ "$status" != 400 ]] || ! php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
exit(($data["code"] ?? "") === "invalid_calendar_defaults" && trim((string)($data["error"] ?? "")) !== "" ? 0 : 1);
' "$invalid"; then
    echo "Ungültige Defaults ergaben keinen verständlichen stabilen HTTP-400-Fehler." >&2
    exit 1
fi

echo 'AD Calendar admin defaults DDEV smoke: OK'
