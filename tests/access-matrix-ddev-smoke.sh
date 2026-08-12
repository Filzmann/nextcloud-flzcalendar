#!/usr/bin/env bash
set -euo pipefail

base_url="${ADC_BASE_URL:-https://nextcloud-dev.ddev.site}"
ddev_project="${ADC_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"
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
    ADC_BASE_URL="$base_url" ADC_USER="$uid" ADC_PASSWORD="$password" ADC_EXPECTED="$expected" ADC_DENIED_TARGET="$denied_target" \
        "$(dirname "$0")/access-http-smoke.sh"
}

prefix="adc-smoke-${suffix}"
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

create_user "$pdl" ad-PDL
create_user "$bl_now" ad-BL ad-Bereich-Nordost ad-Bereich-West
create_user "$bo_actor" ad-Buero ad-Bereich-Nordost
create_user "$pfk_actor" ad-PFK
create_user "$pfk_target" ad-PFK
create_user "$eb_west" ad-EB ad-Bereich-West
create_user "$bo_no" ad-Buero ad-Bereich-Nordost
create_user "$bo_west" ad-Buero ad-Bereich-West
create_user "$bo_south" ad-Buero ad-Bereich-Sued
create_user "$pdl_target" ad-PDL
create_user "$bl_target" ad-BL ad-Bereich-Nordost ad-Bereich-West
create_user "$eb_south" ad-EB ad-Bereich-Sued
create_user "$deputy_bl_eb_south" ad-StvBL ad-EB ad-Bereich-Sued

assert_access "$pdl" "$pdl=true,$pfk_target=true,$eb_west=false"
assert_access "$bl_now" "$bl_now=true,$bo_no=true,$bo_west=true,$bo_south=false,$pfk_target=false"
assert_access "$bo_actor" "$bo_actor=true,$bl_target=false,$pdl_target=false"
assert_access "$pfk_actor" "$pfk_actor=true,$pdl_target=false,$bl_target=false"
assert_access "$eb_south" "$eb_south=true,$deputy_bl_eb_south=false" "$deputy_bl_eb_south"

echo "AD Calendar DDEV access matrix smoke: OK"
