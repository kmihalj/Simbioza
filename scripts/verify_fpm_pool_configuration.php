<?php

declare(strict_types=1);

// EN: Tests generated pools with the real FPM parser, without starting a worker or changing services.
// Only private temporary files are used; default and explicit configuration layers are both covered.
//
// HR: Provjerava generirane poolove stvarnim FPM parserom, bez pokretanja workera ili mijenjanja servisa.
// Koristi samo privatne privremene datoteke; pokriva zadane i izričite konfiguracijske slojeve.
if (PHP_SAPI !== 'cli' || !function_exists('posix_geteuid')) {
    fwrite(STDERR, "This isolated FPM parser check requires macOS or Linux CLI.\n");
    exit(2);
}

$options = getopt('', ['php-fpm:']);
$phpFpm = (string)($options['php-fpm'] ?? '');
if ($phpFpm === '' || !is_file($phpFpm) || !is_executable($phpFpm)) {
    fwrite(STDERR, "Pass --php-fpm=/absolute/path/to/php-fpm. This check never skips.\n");
    exit(2);
}

$user = posix_getpwuid(posix_geteuid());
$group = posix_getgrgid(posix_getegid());
if (!is_array($user) || !is_array($group)) {
    throw new RuntimeException('Unable to resolve the current test identity.');
}

define('SIMBIOZA_FPM_SETUP_LIBRARY', true);
define('FPM_USER', $user['name']);
define('FPM_APP_GROUP', $group['name']);
define('FPM_CONFIG_NAME', 'simbioza-parser-fixture');
define('FPM_HELPER', '/fixture-only/simbioza-setup');
require __DIR__ . '/configure_fpm_setup.php';

$originalEnvironment = [];
foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
    $originalEnvironment[$key] = getenv($key);
}

$root = sys_get_temp_dir() . '/simbioza-fpm-parser-' . bin2hex(random_bytes(8));
mkdir($root . '/data/tmp', 0700, true);
mkdir($root . '/data/sessions', 0700);
mkdir($root . '/config', 0700);
mkdir($root . '/override-one', 0700);
mkdir($root . '/override-two', 0700);
$failed = [];
try {
    putenv('HPH_CONFIG_PATH');
    $cases = [
        'absent overrides' => false,
        'empty overrides' => '',
        'whitespace overrides' => " \t ",
        'one override' => $root . '/override-one',
        'ordered overrides' => $root . '/override-one' . PATH_SEPARATOR . $root . '/override-two',
    ];
    $variants = [];
    foreach ([false, true] as $includeIdentity) {
        foreach ($cases as $name => $overrides) {
            $variants[] = [$name . ($includeIdentity ? ' / explicit identity' : ' / launchd identity'), $overrides, $includeIdentity];
        }
    }

    foreach ($variants as [$name, $overrides, $includeIdentity]) {
        putenv($overrides === false ? 'HPH_CONFIG_OVERRIDE_PATHS' : 'HPH_CONFIG_OVERRIDE_PATHS=' . $overrides);
        $pool = fpmPool($root, $root . '/fpm.sock', $includeIdentity, null);
        $configuration = "[global]\ndaemonize = no\nerror_log = " . $root . '/fpm.log'
            . "\npid = " . $root . "/fpm.pid\n" . $pool;
        $path = $root . '/fpm.conf';
        file_put_contents($path, $configuration);
        $process = proc_open([$phpFpm, '--test', '--allow-to-run-as-root', '--fpm-config', $path],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to run the FPM configuration parser.');
        }

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0) {
            $failed[] = $name;
            fwrite(STDERR, 'FAIL ' . $name . ' (exit ' . $status . '): ' . $output . PHP_EOL);
        } else {
            fwrite(STDOUT, 'PASS real FPM parser: ' . $name . PHP_EOL);
        }

        if (file_exists($root . '/fpm.sock') || file_exists($root . '/fpm.pid')) {
            throw new RuntimeException('The syntax-only check unexpectedly started a pool.');
        }
    }
} finally {
    foreach ($originalEnvironment as $key => $value) {
        putenv($value === false ? $key : $key . '=' . $value);
    }

    // EN: The random test directory is owned by this invocation; never follow links while cleaning it.
    //
    // HR: Slučajni testni direktorij pripada ovom pozivu; pri čišćenju se ne prate poveznice.
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($root);
}

exit($failed === [] ? 0 : 1);
