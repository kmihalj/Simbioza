<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleTheme\Backup\ThemeBackupProvider;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;

// EN: Synthetic Linux/root fixture only: restore by CLI must retain web-runtime access.
// No private HFC files are loaded; cleanup removes only the new named fixture.
//
// HR: Samo sintetička Linux/root fixture: CLI restore mora sačuvati pristup web izvršavanja.
// Privatne HFC datoteke se ne učitavaju; čišćenje uklanja samo novu imenovanu fixture.
if (PHP_SAPI !== 'cli' || PHP_OS_FAMILY !== 'Linux' || posix_geteuid() !== 0) {
    throw new RuntimeException('Run as root inside the synthetic Colima installation.');
}

$root = '/srv/simbioza-config-test/backup-permissions';
$application = dirname(__DIR__, 2);
require $application . '/vendor/autoload.php';
if (!is_dir(dirname($root)) || file_exists($root)) {
    throw new RuntimeException('A new synthetic fixture is required.');
}

$filesystem = new BackupFilesystem();
try {
    foreach (['source', 'target'] as $name) {
        $filesystem->ensureDirectory($root . '/' . $name . '/config');
    }

    putenv('HPH_CONFIG_OVERRIDE_PATHS');
    putenv('HPH_CONFIG_PATH=' . $root . '/source/config');
    $moduleRoot = $application . '/vendor/aaieduhr/heartphrame-module-theme';
    $source = new ThemeConfigRepository(new Config(new Helper(), [], $root . '/source'), $moduleRoot);
    $source->writeJsonContents('themes', '[{"id":"permission-fixture"}]');
    $source->writeJsonContents('settings', '{}');
    $library = $source->themesDirectoryPath();
    $filesystem->ensureDirectory($library . '/nested');
    file_put_contents($library . '/nested/new.svg', '<new/>');
    chmod($library . '/nested/new.svg', 0600);
    $bridge = new class ($root) extends ComposerBridge {
        /**
         * EN: Pins archive operations to the synthetic test root.
         *
         * HR: Veže arhivske radnje na sintetički korijen testa.
         */
        public function __construct(private readonly string $root)
        {
        }

        /**
         * EN: Uses only this synthetic root for archive paths.
         *
         * HR: Za arhive koristi samo ovaj sintetički korijen.
         *
         * @return array<string,mixed>
         */
        public function getRootPackage(): array
        {
            return ['install_path' => $this->root];
        }
    };
    $backup = new BackupConfig(new Config(new Helper(), ['backup' => [
        'archive_dir' => $root . '/archives', 'staging_dir' => $root . '/staging',
    ]], $root), $bridge, $application . '/vendor/aaieduhr/heartphrame-module-backup');
    $scope = new BackupScope(BackupScope::SITE);
    $provider = new ThemeBackupProvider($source, $filesystem);
    $writer = new BackupArchiveWriter($backup, $filesystem, $scope, [], 'P7 permissions');
    $writer->beginProvider($provider->metadata());
    $provider->export(new BackupExportContext($scope, ['theme']), $writer);
    $reader = new BackupArchiveReader($writer->finish(), $backup, $filesystem);
    putenv('HPH_CONFIG_PATH=' . $root . '/target/config');
    $target = new ThemeConfigRepository(new Config(new Helper(), [], $root . '/target'), $moduleRoot);
    $library = $target->themesDirectoryPath();
    chmod($library, 0770);
    chown($library, 'www-data');
    chgrp($library, 'www-data');
    $targetProvider = new ThemeBackupProvider($target, $filesystem);
    $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_REPLACE, ['theme']);
    if (!$targetProvider->preflight($context, $reader)->isAllowed()) {
        throw new RuntimeException('Permission fixture preflight failed.');
    }

    $targetProvider->import($context, $reader);
    $targetProvider->finalizeImport($context, $reader);
    $targetProvider->completeImport($context);
    $checks = ['library', 'nested directory', 'new asset'];
    foreach ([$library, $library . '/nested', $library . '/nested/new.svg'] as $index => $path) {
        clearstatcache(true, $path);
        if (
            fileowner($path) !== posix_getpwnam('www-data')['uid']
            || filegroup($path) !== posix_getgrnam('www-data')['gid']
        ) {
            throw new RuntimeException('Runtime ownership was lost: ' . $checks[$index]);
        }
    }

    if ((fileperms($library . '/nested/new.svg') & 0777) !== 0660) {
        throw new RuntimeException('New asset is not accessible to the shared runtime group.');
    }

    $reader->close();
    echo "PASS: root CLI restore retains www-data ownership/group for nested Theme assets.\n";
} finally {
    $filesystem->removeDirectory($root);
}
