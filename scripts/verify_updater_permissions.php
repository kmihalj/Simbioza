<?php

declare(strict_types=1);

// HR: Root je dopušten samo za ovaj test na privremenoj Linux instalaciji.
// EN: Root is allowed only for this test against a temporary Linux installation.
if (PHP_OS_FAMILY !== 'Linux' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fwrite(STDERR, "Required: root inside an isolated Linux test environment.\n");
    exit(1);
}

$root = dirname(__DIR__);
$process = proc_open([
    PHP_BINARY, $root . '/vendor/bin/phpunit', '--no-configuration',
    '--bootstrap', $root . '/vendor/autoload.php', '--no-coverage', '--do-not-cache-result',
    '--fail-on-skipped', '--fail-on-empty-test-suite',
    '--filter', 'testSeparateRuntimeOwnerSurvivesGuiSelfUpdate|testMaintainerCliDelegatesToDeploymentWorker',
    $root . '/tests/src/Update/UpdaterSelfUpdateTest.php',
], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);
if (!is_resource($process)) {
    fwrite(STDERR, "Unable to start the isolated updater permissions test.\n");
    exit(1);
}
exit(proc_close($process));
