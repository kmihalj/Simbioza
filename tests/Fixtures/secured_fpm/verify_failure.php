<?php

declare(strict_types=1);

// EN: Reintroduces the old empty-bind bug only in the disposable fixture and restores its helper afterwards.
// Requires a failed CLI check and honest dependent GUI diagnostics; no application update is started.
//
// HR: Vraća staru grešku praznog binda samo u privremenom testu i zatim vraća njegov ispravni helper.
// Traži neuspješan CLI status i točnu ovisnu GUI dijagnostiku; ne pokreće nadogradnju aplikacije.
if (!is_file('/.dockerenv') || !is_file('/source/app/tests/Fixtures/secured_fpm/prepare.sh')) {
    throw new RuntimeException('Disposable fixture required.');
}

$helper = '/usr/local/sbin/simbioza-setup';
$original = file_get_contents($helper);
$needle = '  --property=PrivateTmp=true --property=PrivateDevices=true';
$broken = str_replace($needle, "  --property=BindReadOnlyPaths='' \\\n" . $needle, $original, $count);
if ($count !== 1) {
    throw new RuntimeException('Unexpected fixture helper structure.');
}

try {
    file_put_contents($helper, $broken);
    $process = proc_open([
        'runuser', '-u', 'fpm-simbioza', '--', 'php', '/srv/simbioza/scripts/configure_fpm_setup.php',
        '--check', '--app-root=/srv/simbioza', '--maintainer=fixture-admin',
    ], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot run the failing diagnostic fixture.');
    }

    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) === 0 || !str_contains($output, 'Source path')) {
        throw new RuntimeException('The broken helper did not stop the CLI check.');
    }

    $response = file_get_contents('http://127.0.0.1/simbioza/__fixture_probe.php');
    $report = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $checks = array_column($report['checks'], null, 'id');
    if (
        $report['helper_ready'] || $report['application_updates_allowed']
        || $checks['application_update_runtime']['checked']
        || !str_contains($checks['helper_ready']['detail'], 'Source path')
    ) {
        throw new RuntimeException('Dependent GUI checks hid or misreported the helper failure.');
    }
} finally {
    file_put_contents($helper, $original);
}

echo "PASS old bug reproduced, CLI stopped, dependent CLI check marked untested, helper restored.\n";
