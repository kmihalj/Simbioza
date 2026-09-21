<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use App\Module\ModuleCatalog;
use App\Update\BundledAssetsUpdater;
use Composer\InstalledVersions;
use HeartPhrame\App;

// HR: Isključivo CLI korak updatera nakon migracija, nikada web endpoint.
// EN: CLI-only updater step after migrations, never a web endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$runtime = $root . '/data/.bundled-assets-runtime-' . bin2hex(random_bytes(8));
if (!mkdir($runtime, 0700)) {
    throw new RuntimeException('The bundled-asset runtime cannot be created.');
}

try {
    file_put_contents($runtime . '/bootstrap.php', "<?php return [];\n");
    $backup = [
        'archive_dir' => $root . '/data/backups/archives',
        'staging_dir' => $root . '/data/backups/staging',
        'upload_dir' => $root . '/data/backups/uploads',
    ];
    file_put_contents($runtime . '/backup.php', "<?php return " . var_export($backup, true) . ";\n");
    // HR: Backup se privremeno uključuje samo za upravljane pakete uputa; trajni odabir modula ostaje netaknut.
    // EN: Backup is enabled temporarily only for managed guide packages; the persistent module selection stays intact.
    $runtimeApp = require $root . '/config/app.php';
    if (!is_array($runtimeApp)) {
        throw new RuntimeException('The application configuration is invalid.');
    }
    $catalog = new ModuleCatalog();
    $backupPackage = $catalog->definitionFor('backup')['package'];
    $enabledModules = $runtimeApp['modules']['enabled'] ?? [];
    if (!is_array($enabledModules)) {
        throw new RuntimeException('The enabled-module configuration is invalid.');
    }
    $persistentlyEnabledModules = $enabledModules;
    $backupInstalled = InstalledVersions::isInstalled($backupPackage);
    if ($backupInstalled && !in_array($backupPackage, $enabledModules, true)) {
        $enabledModules[] = $backupPackage;
    }
    $runtimeApp['modules']['enabled'] = array_values(array_unique($enabledModules));
    file_put_contents($runtime . '/app.php', "<?php return " . var_export($runtimeApp, true) . ";\n");
    $app = new App([$root . '/config', $runtime], $root);
    $app->loadCommands();
    $container = $app->getContainer();
    $database = $container->get(Database::class);
    $repository = $container->get(WorkspaceRepository::class);
    $basePath = null;
    foreach (array_slice($argv, 1) as $argument) {
        if (str_starts_with($argument, '--base-path=')) {
            $basePath = substr($argument, strlen('--base-path='));
        }
    }

    $installation = is_file($root . '/config/installation.php') ? require $root . '/config/installation.php' : [];
    if ($basePath === null && is_array($installation) && is_string($installation['base_path'] ?? null)) {
        $basePath = $installation['base_path'];
    }

    // HR: Starije instalacije nemaju `base_path`, ali već imaju kanonski javni URL e-pošte.
    // EN: Older installations lack `base_path` but already have the canonical public e-mail URL.
    $email = is_file($root . '/config/email.php') ? require $root . '/config/email.php' : [];
    if ($basePath === null && is_array($email) && is_string($email['application_base_url'] ?? null)) {
        $basePath = BundledAssetsUpdater::basePathFromApplicationUrl($email['application_base_url']);
    }

    if ($basePath === null) {
        $html = [];
        $editorConfig = $container->get(EditorHtmlConfig::class);
        $formatter = $container->get(EditorHtmlDocumentFormatter::class);
        $workspace = $repository->findWorkspaceBySlug('korisnicke-upute');
        if (is_array($workspace)) {
            foreach ($repository->nodesForWorkspace((int)$workspace['id']) as $node) {
                $document = $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
                    ->where('document_key', '=', $node['document_key'] ?? '')->first();
                if (!is_array($document)) {
                    continue;
                }

                $version = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                    ->where('document_id', '=', $document['id'])->orderBy('version_number', 'DESC')->first();
                if (is_array($version)) {
                    $html[] = BundledAssetsUpdater::guideVersionHtml($version, $editorConfig->documentsRoot(), $formatter);
                }
            }
        }

        $basePath = BundledAssetsUpdater::basePathFromGuideHtml($html);
    }

    $themePackage = $catalog->definitionFor('theme')['package'];
    $themeEnabled = InstalledVersions::isInstalled($themePackage)
    && in_array($themePackage, $persistentlyEnabledModules, true);
    $guideProviders = [
        'calendar' => 'calendar-workspace',
        'comment' => 'comment-workspace',
        'confluence-import' => 'simbioza-confluence-import-workspace',
        'task' => 'task-workspace',
    ];
    $skippedGuideProviders = [];
    foreach ($guideProviders as $module => $provider) {
        $package = $catalog->definitionFor($module)['package'];
        if (!InstalledVersions::isInstalled($package) || !in_array($package, $persistentlyEnabledModules, true)) {
            $skippedGuideProviders[] = $provider;
        }
    }

    $themes = $themeEnabled ? $container->get(ThemeConfigRepository::class) : null;
    $updater = new BundledAssetsUpdater(
        $database,
        $backupInstalled ? $container->get(BackupManager::class) : null,
        $repository,
        $themes,
        $themes instanceof ThemeConfigRepository
            ? new ThemeArchiveService($themes, new ThemeAssetLibrary($themes))
            : null,
        $container->get(AuthUserService::class),
        $container->get(EditorHtmlConfig::class),
        $container->get(EditorHtmlDocumentFormatter::class),
    );
    foreach ($updater->run($root, $basePath, $skippedGuideProviders) as $action) {
        echo $action . PHP_EOL;
    }
} finally {
    foreach (['bootstrap.php', 'backup.php', 'app.php'] as $name) {
        if (is_file($runtime . '/' . $name)) {
            unlink($runtime . '/' . $name);
        }
    }

    rmdir($runtime);
}
