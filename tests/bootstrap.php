<?php

declare(strict_types=1);

require_once __DIR__ . '/../../localbase/tests/bootstrap.php';

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'OCA\\AdCalendar\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $root . '/lib/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
