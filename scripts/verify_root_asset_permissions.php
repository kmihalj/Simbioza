<?php

/**
 * HR: Pokreće samo test promjene vlasnika u izoliranom Linux CI okruženju.
 *     Ne pokreće Composer, aplikaciju, migracije niti mijenja žive datoteke.
 * EN: Runs only the ownership-change test in an isolated Linux CI environment.
 *     Never runs Composer, the application, migrations, or touches live files.
 */

declare(strict_types=1);

if (PHP_OS_FAMILY !== 'Linux' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fwrite(STDERR, "Required: root inside an isolated Linux test environment.\n");
    exit(1);
}

$root = dirname(__DIR__);
$process = proc_open([
    PHP_BINARY, $root . '/vendor/bin/phpunit', '--no-configuration',
    '--bootstrap', $root . '/vendor/autoload.php', '--no-coverage', '--do-not-cache-result',
    '--fail-on-skipped', '--fail-on-empty-test-suite',
    '--filter', 'testRootImportsInheritAnotherRuntimeIdentity',
    $root . '/tests/src/Update/BundledAssetPermissionsTest.php',
], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $root);

if (!is_resource($process)) {
    fwrite(STDERR, "Unable to start the isolated ownership test.\n");
    exit(1);
}

exit(proc_close($process));
