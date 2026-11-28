<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$bundleRoot = $root . '/vendor/flz-shared';
$composerAutoload = $bundleRoot . '/php/vendor/autoload.php';
$lockPath = $bundleRoot . '/flz-shared.lock.json';

foreach ([$composerAutoload, $lockPath, $bundleRoot . '/php/src/Service/DemoAccountProvisioningService.php', $bundleRoot . '/LICENSE'] as $requiredPath) {
    if (!is_file($requiredPath)) {
        throw new RuntimeException('App-lokaler Kategorie-A-Bestandteil fehlt: ' . $requiredPath);
    }
}

require_once $composerAutoload;

$service = 'OCA\\FlzCalendar\\Vendor\\Filzmann\\Shared\\Service\\DemoAccountProvisioningService';
if (!class_exists($service)) {
    throw new RuntimeException('Das app-isolierte DemoAccountProvisioningService ist nicht über Composer auflösbar.');
}

$lock = json_decode((string)file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
if (($lock['app_id'] ?? null) !== 'flzcalendar'
    || ($lock['app_namespace'] ?? null) !== 'FlzCalendar'
    || ($lock['php_namespace'] ?? null) !== 'OCA\\FlzCalendar\\Vendor\\Filzmann\\Shared') {
    throw new RuntimeException('Der Kategorie-A-Lock beschreibt nicht das isolierte flzcalendar-Bundle.');
}

$demoPack = (string)file_get_contents($root . '/lib/Service/CalendarDemoPackService.php');
$application = (string)file_get_contents($root . '/lib/AppInfo/Application.php');
if (!str_contains($demoPack, $service)) {
    throw new RuntimeException('Der Kalender-Demo-Pack konsumiert nicht das gebündelte DemoAccountProvisioningService.');
}
if (!str_contains($application, "require_once dirname(__DIR__, 2) . '/vendor/flz-shared/php/vendor/autoload.php';")) {
    throw new RuntimeException('Das App-Bootstrap lädt den Kategorie-A-Autoloader nicht.');
}

echo "CategoryABundleContractTest: OK\n";
