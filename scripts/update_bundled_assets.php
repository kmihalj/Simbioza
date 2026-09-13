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
use App\Update\BundledAssetsUpdater;
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

    $themes = $container->get(ThemeConfigRepository::class);
    $updater = new BundledAssetsUpdater(
        $database,
        $container->get(BackupManager::class),
        $repository,
        $themes,
        new ThemeArchiveService($themes, new ThemeAssetLibrary($themes)),
        $container->get(AuthUserService::class),
        $container->get(EditorHtmlConfig::class),
        $container->get(EditorHtmlDocumentFormatter::class),
    );
    foreach ($updater->run($root, $basePath) as $action) {
        echo $action . PHP_EOL;
    }
} finally {
    foreach (['bootstrap.php', 'backup.php'] as $name) {
        if (is_file($runtime . '/' . $name)) {
            unlink($runtime . '/' . $name);
        }
    }

    rmdir($runtime);
}
