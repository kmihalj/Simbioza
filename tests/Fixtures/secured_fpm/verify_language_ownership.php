<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- Disposable cross-identity regression entry point.

// EN: Exercise both real Unix identities against fictional configuration only.
// This reproduces the updater's final metadata check and FPM-owned lock reuse.
//
// HR: Provjerava oba stvarna Unix identiteta isključivo na izmišljenim postavkama.
// Ponavlja završnu provjeru prava updatera i ponovnu uporabu FPM-ova locka.
if (!is_file('/.dockerenv') || !is_file('/usr/local/share/simbioza-fixture/secured-fpm')) {
    throw new RuntimeException('Disposable fixture required.');
}

$appRoot = dirname(__DIR__, 3);
require $appRoot . '/vendor/autoload.php';
require_once $appRoot . '/update.php';
$fixture = $argv[2] ?? '';
if (($argv[1] ?? '') === 'actor') {
    if (!str_starts_with($fixture, '/tmp/simbioza-language-ownership-') || !is_dir($fixture)) {
        throw new RuntimeException('Invalid fictional fixture root.');
    }

    putenv('HPH_CONFIG_PATH=' . $fixture . '/config');
    putenv('HPH_CONFIG_OVERRIDE_PATHS');
    class_exists(HeartPhrame\Config\ConfigPathResolver::class);
    class_exists(HeartPhrame\Config\ConfigArrayMerger::class);
    enum_exists(HeartPhrame\CodeBook\EnvKeyEnum::class);
    $command = new Simbioza\Update\ApplicationUpdateCommand($fixture, ['--lang=en']);
    (new ReflectionMethod($command, 'capturePreservedPathMetadata'))->invoke($command);
    $before = [];
    foreach (['installation.php', 'languages.php', '.languages.lock'] as $file) {
        $before[$file] = stat($fixture . '/config/' . $file);
    }

    $lockPath = $fixture . '/data/languages/.packs.lock';
    $lockBefore = stat($lockPath);
    $manager = new App\Localization\LanguagePackManager($fixture, new App\Module\ModuleCatalog());
    $manager->install($fixture . '/data/de.json', true);
    $manager->setActive('de', false);
    $manager->setActive('de', true);
    (new ReflectionMethod($command, 'restorePreservedPathMetadata'))->invoke($command);
    clearstatcache();
    foreach ($before as $file => $metadata) {
        $after = stat($fixture . '/config/' . $file);
        foreach (['ino', 'uid', 'gid', 'mode'] as $key) {
            if ($after[$key] !== $metadata[$key]) {
                throw new RuntimeException('Language config metadata changed: ' . $file . '/' . $key);
            }
        }
    }

    foreach (['ino', 'uid', 'gid', 'mode'] as $key) {
        if (stat($lockPath)[$key] !== $lockBefore[$key]) {
            throw new RuntimeException('Runtime language lock metadata changed.');
        }
    }

    $configuration = require $fixture . '/config/installation.php';
    if ($configuration['custom'] !== ['keep' => true] || $configuration['supported_locales'] !== ['en', 'de']) {
        throw new RuntimeException('Unrelated settings or enabled locales changed.');
    }

    echo "PASS language install/toggle and updater metadata restoration for UID " . posix_geteuid() . ".\n";
    exit(0);
}

if (posix_geteuid() !== 0) {
    throw new RuntimeException('Root inside the disposable fixture required.');
}

$fixture = '/tmp/simbioza-language-ownership-' . bin2hex(random_bytes(8));
foreach (['config', 'lang', 'data/languages/catalogs', 'data/languages/flags'] as $directory) {
    mkdir($fixture . '/' . $directory, 0770, true);
}

file_put_contents($fixture . '/lang/en.php', '<?php return ["hello"=>"Hello"];');
file_put_contents($fixture . '/config/languages.php', '<?php return ["en"=>['
. '"names"=>["en"=>"English"],"native_name"=>"English","flag"=>"en.svg"]];');
file_put_contents($fixture . '/config/installation.php', '<?php return ['
. '"supported_locales"=>["en"],"primary_locale"=>"en","custom"=>["keep"=>true]];');
file_put_contents($fixture . '/config/.languages.lock', '');
file_put_contents($fixture . '/data/languages/.packs.lock', '');
putenv('HPH_CONFIG_PATH=' . $fixture . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
$manager = new App\Localization\LanguagePackManager($fixture, new App\Module\ModuleCatalog());
$pack = $manager->createTemplate('de', 'en', $fixture . '/data/de.json');
$payload = json_decode((string)file_get_contents($pack), true, 512, JSON_THROW_ON_ERROR);
$payload['names'] = ['en' => 'German', 'de' => 'Deutsch'];
$payload['native_name'] = 'Deutsch';
file_put_contents($pack, json_encode($payload, JSON_THROW_ON_ERROR));
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($fixture, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ([$fixture, ...array_keys(iterator_to_array($iterator))] as $path) {
    chown($path, 'simbioza-deploy');
    chgrp($path, 'run-simbioza');
    chmod($path, is_dir($path) ? 02770 : 0660);
}

chmod($fixture . '/config', 03770);
$runtimeOwned = ['config/installation.php', 'config/languages.php',
    'config/.languages.lock', 'data/languages/.packs.lock'];
foreach ($runtimeOwned as $file) {
    chown($fixture . '/' . $file, 'fpm-simbioza');
}

foreach (['simbioza-deploy', 'fpm-simbioza', 'simbioza-deploy'] as $identity) {
    $process = proc_open(
        ['runuser', '-u', $identity, '--', PHP_BINARY, __FILE__, 'actor', $fixture],
        [0 => ['file', '/dev/null', 'r'], 1 => STDOUT, 2 => STDERR],
        $pipes,
    );
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Cross-identity language regression failed: ' . $identity);
    }
}

echo "PASS FPM/deploy/FPM round trips without chown, changed metadata or weakened permissions.\n";
