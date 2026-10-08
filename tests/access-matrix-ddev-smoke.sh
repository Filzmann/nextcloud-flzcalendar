#!/usr/bin/env bash
set -euo pipefail

base_url="${FLZC_BASE_URL:-https://nextcloud-dev.ddev.site}"
ddev_project="${FLZC_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"
suffix="$(date +%s)-$$"
# Nur im Arbeitsspeicher vorhandenes Einmalpasswort für alle temporären Matrix-Konten.
password="$(php -r 'echo bin2hex(random_bytes(24));')"
created_users=()

occ() {
    (cd "$ddev_project" && ddev exec -d /var/www/html/html php occ "$@")
}

cleanup() {
    local uid
    for uid in "${created_users[@]}"; do
        occ user:delete "$uid" >/dev/null 2>&1 || true
    done
}
trap cleanup EXIT

create_user() {
    local uid="$1"
    shift
    (cd "$ddev_project" && ddev exec -d /var/www/html/html env OC_PASS="$password" php occ user:add --password-from-env "$uid") >/dev/null
    created_users+=("$uid")
    local group
    for group in "$@"; do
        occ group:adduser "$group" "$uid" >/dev/null
    done
}

assert_access() {
    local uid="$1"
    local expected="$2"
    local denied_target="${3:-}"
    FLZC_BASE_URL="$base_url" FLZC_USER="$uid" FLZC_PASSWORD="$password" FLZC_EXPECTED="$expected" FLZC_DENIED_TARGET="$denied_target" \
        "$(dirname "$0")/access-http-smoke.sh"
}

prefix="flz-calendar-smoke-${suffix}"
pdl="${prefix}-pdl"
bl_now="${prefix}-bl-now"
bo_actor="${prefix}-bo-actor"
pfk_actor="${prefix}-pfk-actor"
pfk_target="${prefix}-pfk-target"
eb_west="${prefix}-eb-west"
bo_no="${prefix}-bo-no"
bo_west="${prefix}-bo-west"
bo_south="${prefix}-bo-south"
pdl_target="${prefix}-pdl-target"
bl_target="${prefix}-bl-target"
eb_south="${prefix}-eb-south"
deputy_bl_eb_south="${prefix}-stvbl-eb-south"

create_user "$pdl" flz-PDL
create_user "$bl_now" flz-BL flz-Bereich-Nordost flz-Bereich-West
create_user "$bo_actor" flz-Buero flz-Bereich-Nordost
create_user "$pfk_actor" flz-PFK
create_user "$pfk_target" flz-PFK
create_user "$eb_west" flz-EB flz-Bereich-West
create_user "$bo_no" flz-Buero flz-Bereich-Nordost
create_user "$bo_west" flz-Buero flz-Bereich-West
create_user "$bo_south" flz-Buero flz-Bereich-Sued
create_user "$pdl_target" flz-PDL
create_user "$bl_target" flz-BL flz-Bereich-Nordost flz-Bereich-West
create_user "$eb_south" flz-EB flz-Bereich-Sued
create_user "$deputy_bl_eb_south" flz-StvBL flz-EB flz-Bereich-Sued

assert_access "$pdl" "$pdl=true,$pfk_target=true,$eb_west=false"
assert_access "$bl_now" "$bl_now=true,$bo_no=true,$bo_west=true,$bo_south=false,$pfk_target=false"
assert_access "$bo_actor" "$bo_actor=true,$bl_target=false,$pdl_target=false"
assert_access "$pfk_actor" "$pfk_actor=true,$pdl_target=false,$bl_target=false"
assert_access "$eb_south" "$eb_south=true,$deputy_bl_eb_south=false" "$deputy_bl_eb_south"

echo "Filzmann Calendar DDEV access matrix smoke: OK"
