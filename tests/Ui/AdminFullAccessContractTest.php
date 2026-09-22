<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$technicalAdminTemplate = (string)file_get_contents($root . '/templates/admin.php');
$template = (string)file_get_contents($root . '/templates/index.php');
$script = is_file($root . '/js/admin-access.js') ? (string)file_get_contents($root . '/js/admin-access.js') : '';
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$pageController = (string)file_get_contents($root . '/lib/Controller/PageController.php');
$accessController = (string)file_get_contents($root . '/lib/Controller/TemporaryAdminAccessController.php');

foreach (['adc-full-access-form','adc-full-access-enabled','adc-full-access-history','Maximal 24 Stunden'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("App-lokale DPO-Freigabesteuerung fehlt: {$contract}");
}
foreach (['Datenschutzbeauftragte','Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff','href="#adc-full-access"'] as $contract) if(!str_contains($template,$contract))throw new RuntimeException("Sichere rollenabhängige Eintrittsmeldung fehlt: {$contract}");
foreach (['/api/admin/full-access','durationMinutes','targetUid','Widerrufen'] as $contract) {
    if (!str_contains($script . $routes, $contract)) throw new RuntimeException("Vollzugriffs-UI/API-Vertrag fehlt: {$contract}");
}
if (!str_contains($routes, "'verb' => 'DELETE'")) throw new RuntimeException('Widerrufroute fehlt.');
if(str_contains($technicalAdminTemplate,'adc-full-access-form'))throw new RuntimeException('Freigabesteuerung ist noch an den technischen Adminbereich gebunden.');
foreach(['canManageAdminAccess','showMissingAdminGrant','showAdminAccessLink'] as $contract)if(!str_contains($template,$contract)||!str_contains($pageController,"'{$contract}'"))throw new RuntimeException("Rollenabhängige Eintrittsgrenze fehlt: {$contract}");
if(!str_contains($pageController,"'showAdminAccessLink' => \$canManageAdminAccess && \$showMissingAdminGrant"))throw new RuntimeException('Direktlink ist nicht auf gleichzeitige Datenschutz- und Adminrolle begrenzt.');
if(str_contains($accessController,'PublicPage'))throw new RuntimeException('Freigaberouten dürfen nicht öffentlich erreichbar sein.');

echo "AD Kalender admin full access UI contract tests passed\n";
