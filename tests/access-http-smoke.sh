#!/usr/bin/env bash
set -euo pipefail

: "${ADC_BASE_URL:?ADC_BASE_URL fehlt}"
: "${ADC_USER:?ADC_USER fehlt}"
: "${ADC_PASSWORD:?ADC_PASSWORD fehlt}"
: "${ADC_EXPECTED:?ADC_EXPECTED fehlt}"

workdir="$(mktemp -d)"
response="$workdir/response.json"
page="$workdir/page.html"
cookies="$workdir/cookies.txt"
denied="$workdir/denied.json"
trap 'rm -rf "$workdir"' EXIT
week_start="$(date -d 'monday this week' +%F)"
curl --fail --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
    "$ADC_BASE_URL/index.php/apps/adcalendar/api/week?start=$week_start" --output "$response"

ADC_RESPONSE="$response" php -r '
$state = json_decode(file_get_contents(getenv("ADC_RESPONSE")), true, flags: JSON_THROW_ON_ERROR);
$actual = [];
foreach ($state["employees"] ?? [] as $employee) $actual[$employee["uid"]] = (bool)$employee["canManage"];
foreach (explode(",", getenv("ADC_EXPECTED")) as $expectation) {
    [$uid, $raw] = explode("=", $expectation, 2);
    $expected = $raw === "true";
    if (!array_key_exists($uid, $actual) || $actual[$uid] !== $expected) {
        fwrite(STDERR, "Rechtevertrag verletzt fuer {$uid}: erwartet " . ($expected ? "true" : "false") . ", erhalten " . json_encode($actual[$uid] ?? null) . PHP_EOL);
        exit(1);
    }
}
'

if [[ -n "${ADC_DENIED_TARGET:-}" ]]; then
    curl --fail --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
        --cookie-jar "$cookies" "$ADC_BASE_URL/index.php/apps/adcalendar/" --output "$page"
    token="$(sed -n 's/.*data-requesttoken="\([^"]*\)".*/\1/p' "$page" | head -n 1)"
    if [[ -z "$token" ]]; then
        echo 'Request-Token für direkten Rechteversuch fehlt.' >&2
        exit 1
    fi
    start="${week_start}T10:00:00+02:00"
    end="${week_start}T11:00:00+02:00"
    status="$(curl --silent --show-error --insecure --user "$ADC_USER:$ADC_PASSWORD" \
        --cookie "$cookies" --cookie-jar "$cookies" -H "requesttoken: $token" -H 'Content-Type: application/json' \
        -X POST --data "{\"employeeUid\":\"$ADC_DENIED_TARGET\",\"type\":\"appointment\",\"start\":\"$start\",\"end\":\"$end\",\"title\":\"Unzulässiger Rechteversuch\"}" \
        --write-out '%{http_code}' --output "$denied" "$ADC_BASE_URL/index.php/apps/adcalendar/api/entries")"
    if [[ "$status" != 403 ]] || ! php -r '
$data = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
exit(($data["code"] ?? "") === "forbidden" ? 0 : 1);
' "$denied"; then
        echo "Direkte Fremdänderung ergab HTTP $status statt eines stabilen 403-Fehlers." >&2
        exit 1
    fi
fi

echo "AD Calendar access HTTP smoke: OK ($ADC_USER)"
