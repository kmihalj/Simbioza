<?php

declare(strict_types=1);

// EN: Exercises owned payload paths only inside the synthetic localhost FPM installations.
// Never copy this probe into a real installation or expose it publicly.
//
// HR: Provjerava vlasničke putanje samo u sintetičkim lokalnim FPM instalacijama.
// Nikada ne kopirati u stvarnu instalaciju niti javno izložiti ovu provjeru.
$root = dirname(__DIR__);
if (!in_array($root, ['/srv/simbioza-config-test/normal', '/srv/simbioza-config-test/secure'], true)) {
    throw new RuntimeException('Synthetic FPM fixture only.');
}

$bootstrap = realpath($root . '/bootstrap.php');
$autoload = realpath($root . '/vendor/autoload.php');
if ($bootstrap === false || $autoload === false) {
    throw new RuntimeException('Synthetic installation is incomplete.');
}

require $bootstrap;
require $autoload;
header('Content-Type: application/json');
try {
    $config = new HeartPhrame\Config\Config(new HeartPhrame\Helper\Helper(), [], $root);
    $config->loadLayeredDirectories(HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root));
    $database = new AaiEduHr\HeartPhrameModuleOrm\Database\Database($config, new HeartPhrame\Helper\Helper());
    if (!$database->schema()->hasTable('workspaces')) {
        $database->execute('CREATE TABLE workspaces (id INTEGER PRIMARY KEY, slug TEXT, storage_slug TEXT)');
        $database->table('workspaces')->insert(['id' => 77, 'slug' => 'fixture', 'storage_slug' => 'fixture']);
    }

    $editor = new AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig(
        $config,
        $root . '/vendor/aaieduhr/heartphrame-module-editor-html',
    );
    $workspace = new AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceConfig(
        $config,
        $root . '/vendor/aaieduhr/simbioza-module-workspace',
        $database,
    );
    $prefix = '@data/workspaces/fixture--77/pages/fixture-page--11111111-1111-4111-8111-111111111111/';
    $htmlFile = $editor->documentPayloadPath($prefix . 'content/hr/versions/000001.html');
    $assetFile = $editor->attachmentPayloadPath($prefix . 'attachments/fixture.txt');
    $themeFile = $workspace->workspaceThemePath(77) . '/assets/fixture.txt';
    $formatter = new AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter();
    if (($_GET['mode'] ?? '') !== 'read') {
        foreach ([$htmlFile, $assetFile, $themeFile] as $file) {
            $editor->preparePayloadDirectory(dirname($file));
        }

        file_put_contents($htmlFile, $formatter->standaloneDocument('Fixture', 'hr', '<p>Owned FPM</p>', []));
        file_put_contents($assetFile, 'Private attachment');
        file_put_contents($themeFile, 'Private theme');
        $owners = new class implements AaiEduHr\HeartPhrameModuleMenu\Service\MenuContextOwnerResolverInterface {
            /**
             * EN: Assigns only this synthetic context to its fixture workspace.
             *
             * HR: Dodjeljuje samo sintetički kontekst području ove fixture.
             */
            public function directory(array $context): string
            {
                return 'workspaces/fixture--77';
            }
        };
        $menus = new AaiEduHr\HeartPhrameModuleMenu\Service\MenuContextFiles(
            $editor->dataRoot(),
            $root . '/data/p7r-menu-index.json',
            $owners,
        );
        $menus->write([['id' => 'fixture.p7r', 'owner_target' => 'workspace.77', 'items' => []]]);
    }

    $menus = new AaiEduHr\HeartPhrameModuleMenu\Service\MenuContextFiles(
        $editor->dataRoot(),
        $root . '/data/p7r-menu-index.json',
    );
    $checks = [
        'real_fpm' => PHP_SAPI === 'fpm-fcgi',
        'html_read_write' => is_writable($htmlFile)
            && $formatter->extractFragment((string)file_get_contents($htmlFile)) === '<p>Owned FPM</p>',
        'attachment_read_write' => is_writable($assetFile) && file_get_contents($assetFile) === 'Private attachment',
        'private_theme_read_write' => is_writable($themeFile) && file_get_contents($themeFile) === 'Private theme',
        'owned_menu_read' => ($menus->read()[0]['id'] ?? null) === 'fixture.p7r',
        'owned_menu_write' => is_writable(
            $root . '/data/workspaces/fixture--77/menus/' . hash('sha256', 'fixture.p7r') . '.json',
        ),
        'runtime_owner' => fileowner($htmlFile) === posix_geteuid(),
    ];
    if (in_array(false, $checks, true)) {
        http_response_code(500);
    }

    echo json_encode($checks, JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
