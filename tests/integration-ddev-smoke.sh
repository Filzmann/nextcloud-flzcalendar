#!/usr/bin/env bash
set -euo pipefail

ddev_project="${ADC_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"

run_smoke() {
    local file="$1"
    (cd "$ddev_project" && ddev exec -d /var/www/html/html php -r \
        "define('OC_CONSOLE', true); require '/var/www/html/html/custom_apps/adcalendar/tests/integration/${file}';")
}

run_smoke RecurringAppointmentSmoke.php
run_smoke DefaultShiftVacationSmoke.php
run_smoke ShiftCalendarSyncSmoke.php
