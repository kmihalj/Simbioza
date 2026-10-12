<?php

declare(strict_types=1);

// EN: Fixed commands only; runtime never installs Composer packages or downloads application code.
//
// HR: Samo fiksne naredbe; runtime ne instalira Composer pakete niti preuzima aplikacijski kod.
umask(0007);
require '/opt/simbioza/vendor/autoload.php';
$runtime = new \App\Deployment\ContainerRuntime('/opt/simbioza', '/runtime');
$mode = $argv[1] ?? 'serve';
try {
    if ($mode === 'init') {
        $runtime->initialize();
        exit(0);
    }
    if (posix_geteuid() !== 33) {
        throw new RuntimeException('Application, migration and worker processes must run as UID 33.');
    }
    if ($mode === 'migrate') {
        printf("Applied migrations: %d\n", $runtime->migrate());
        exit(0);
    }
    $runtime->assertReady();
    if ($mode === 'serve') {
        if (!is_dir('/tmp/apache2') && !mkdir('/tmp/apache2', 0700)) {
            throw new RuntimeException('Unable to prepare ephemeral Apache runtime.');
        }
        pcntl_exec('/usr/local/bin/apache2-foreground', []);
    } elseif ($mode === 'install:prepare') {
        pcntl_exec('/usr/local/bin/php', ['/opt/simbioza/bin/simbioza', 'install:prepare', ...array_slice($argv, 2)]);
    } elseif ($mode === 'cli') {
        pcntl_exec('/usr/local/bin/php', ['/opt/simbioza/vendor/bin/hph', ...array_slice($argv, 2)]);
    } else {
        throw new RuntimeException('Supported container commands: init, serve, migrate, install:prepare, cli.');
    }
    throw new RuntimeException('Unable to start the container process.');
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
