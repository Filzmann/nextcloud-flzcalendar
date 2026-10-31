<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$template = (string)file_get_contents($root . '/templates/index.php');
$script = is_file($root . '/js/admin-access.js') ? (string)file_get_contents($root . '/js/admin-access.js') : '';
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController = (string)file_get_contents($root . '/lib/Controller/PageController.php');
foreach (['flz-calendar-full-access', 'Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff', 'canManageAdminAccess', 'showMissingAdminGrant', 'showAdminAccessLink'] as $obsolete) {
    if (str_contains($template . $pageController, $obsolete)) throw new RuntimeException("Entfallene app-lokale Kalenderfreigabe ist noch sichtbar: {$obsolete}");
}
if (str_contains($routes, '/api/admin/full-access') || $script !== '') throw new RuntimeException('Entfallene Kalenderfreigabe besitzt noch Route oder JavaScript.');

echo "Filzmann Kalender native admin exception UI contract tests passed\n";
