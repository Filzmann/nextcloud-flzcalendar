#!/usr/bin/env bash
set -euo pipefail

: "${FLZC_BASE_URL:?FLZC_BASE_URL fehlt}"
: "${FLZC_USER:?FLZC_USER fehlt}"
: "${FLZC_PASSWORD:?FLZC_PASSWORD fehlt}"

app_page="$(mktemp)"
api_response="$(mktemp)"
trap 'rm -f "$app_page" "$api_response"' EXIT

curl --fail --silent --show-error --insecure --user "$FLZC_USER:$FLZC_PASSWORD" \
    "$FLZC_BASE_URL/index.php/apps/flzcalendar/" --output "$app_page"

for contract in 'id="flzcalendar-app"' 'id="flz-calendar-week-number"' 'id="flz-calendar-person-search"' 'id="flz-calendar-toggle-view"'; do
    if ! grep -q "$contract" "$app_page"; then
        echo "App-DOM-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

week_start="$(date -d 'monday this week' +%F)"
curl --fail --silent --show-error --insecure --user "$FLZC_USER:$FLZC_PASSWORD" \
    "$FLZC_BASE_URL/index.php/apps/flzcalendar/api/week?start=$week_start" --output "$api_response"
for contract in '"employees"' '"entries"' '"organization"' '"currentUserProfile"'; do
    if ! grep -q "$contract" "$api_response"; then
        echo "API-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

echo "Filzmann Calendar HTTP smoke: OK ($FLZC_USER)"
