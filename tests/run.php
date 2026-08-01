<?php

declare(strict_types=1);

require_once __DIR__ . '/../../localbase/tests/Support/PhpTestRunner.php';

use OCA\LocalBase\Tests\Support\PhpTestRunner;

PhpTestRunner::run(
    root: dirname(__DIR__),
    lintDirectories: ['appinfo', 'lib', 'templates', 'tests'],
    testDirectories: ['tests/Model', 'tests/Repository', 'tests/Controller', 'tests/Service', 'tests/Command', 'tests/Ui', 'tests/Migration'],
    testSuffixes: ['Test.php'],
    successMessage: 'AD Kalender PHP tests passed',
);
