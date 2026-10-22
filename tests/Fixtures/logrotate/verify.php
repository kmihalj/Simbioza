<?php

declare(strict_types=1);

// EN: Linux-only disposable-container check. All generated files stay under a
//     fresh /tmp directory; no installation files or real mail are accessed.
//
// HR: Linux provjera u jednokratnom kontejneru. Sve stvorene datoteke ostaju
//     u novom /tmp direktoriju; instalacija i stvarna pošta ne koriste se.
$root = '/tmp/simbioza-logrotate-' . bin2hex(random_bytes(8));
$directory = $root . '/data/logs';
mkdir($directory, 0770, true);
chown($directory, 33);
chgrp($directory, 33);
chmod($directory, 0770);
$documentation = (string)file_get_contents(dirname(__DIR__, 3) . '/docs/logging_en.md');
if (preg_match('/```conf\n(.*?)```/s', $documentation, $matches) !== 1) {
    throw new RuntimeException('The documented rotation rule is missing.');
}

$configuration = $root . '/logrotate.conf';
file_put_contents($configuration, str_replace('/srv/simbioza', $root, $matches[1]));
chmod($configuration, 0600);
$metadata = [];
foreach (
    ['email-worker.log' => [33, 33, 0660], 'application-update.log' => [1000, 1000, 0660],
    'installer.log' => [33, 33, 0600], 'php-fpm.log' => [0, 0, 0640]] as $name => [$uid, $gid, $mode]
) {
    $path = $directory . '/' . $name;
    file_put_contents($path, 'rotation fixture ' . $name . "\n");
    chown($path, $uid);
    chgrp($path, $gid);
    chmod($path, $mode);
    $stat = stat($path);
    $metadata[$path] = [$stat['ino'], $stat['uid'], $stat['gid'], $stat['mode'] & 0777];
}

$handle = fopen($directory . '/email-worker.log', 'ab');
for ($iteration = 1; $iteration <= 16; ++$iteration) {
    $process = proc_open(
        ['logrotate', '--state', $root . '/state', '--force', $configuration],
        [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => STDERR],
        $pipes,
    );
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Rotation failed.');
    }

    foreach ($metadata as $path => $expected) {
        clearstatcache(true, $path);
        $stat = stat($path);
        if ([$stat['ino'], $stat['uid'], $stat['gid'], $stat['mode'] & 0777] !== $expected) {
            throw new RuntimeException('Rotation changed inode, ownership or mode.');
        }

        file_put_contents($path, 'next fixture run ' . $iteration . "\n", FILE_APPEND);
    }

    fwrite($handle, "Writing through the original open handle\n");
}

fclose($handle);
foreach ($metadata as $path => $expected) {
    if (count(glob($path . '.*') ?: []) !== 14) {
        throw new RuntimeException('Archive retention is not bounded at fourteen.');
    }
}

if (!str_contains((string)file_get_contents($directory . '/email-worker.log'), 'original open handle')) {
    throw new RuntimeException('An already open writer no longer reaches the active log.');
}

echo "PASS: documented Linux rotation; 14 archives; unchanged inode, owner and mode; live handle preserved.\n";
