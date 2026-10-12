<?php

declare(strict_types=1);

// EN: Read-only verification of payload references and declared Workspace ownership.
// Reports counts only; never prints configuration, titles, credentials or document bytes.
//
// HR: Provjera referenci sadržaja i deklariranog vlasništva područja bez zapisivanja.
// Ispisuje samo brojeve; nikada konfiguraciju, naslove, vjerodajnice ni sadržaj dokumenata.
if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Payload audit is CLI only.');
}

$root = realpath($argv[1] ?? dirname(__DIR__, 2));
if (!is_string($root) || !is_file($root . '/bootstrap.php')) {
    throw new RuntimeException('A valid Simbioza installation is required.');
}

putenv('HPH_APP_PATH=' . $root);
putenv('HPH_CONFIG_PATH');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
$helper = new HeartPhrame\Helper\Helper();
$config = new HeartPhrame\Config\Config($helper, [], $root);
$config->loadLayeredDirectories(HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root));
$database = new AaiEduHr\HeartPhrameModuleOrm\Database\Database($config, $helper);
$editor = new AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig(
    $config,
    $root . '/vendor/aaieduhr/heartphrame-module-editor-html',
);
$owner = new AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspacePayloadOwner($database);
$directories = new AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceDataDirectory($database);
$documents = [];
$assets = [];
$result = ['filesystem_html' => 0, 'owned_html' => 0, 'filesystem_attachments' => 0,
    'owned_attachments' => 0, 'missing' => 0, 'wrong_owner' => 0];
foreach ($database->table('editor_html_documents')->get() as $row) {
    $documents[$row['id']] = $row;
}

foreach ($database->table('editor_html_assets')->get() as $row) {
    $assets[$row['id']] = $row;
}

foreach (['editor_html_document_versions', 'editor_html_assets', 'editor_html_asset_versions'] as $table) {
    foreach ($database->table($table)->get() as $row) {
        if (($row['storage_driver'] ?? null) !== 'filesystem') {
            continue;
        }

        $html = $table === 'editor_html_document_versions';
        $path = $row['content_path'] ?? '';
        $absolute = $html ? $editor->documentPayloadPath($path) : $editor->attachmentPayloadPath($path);
        ++$result[$html ? 'filesystem_html' : 'filesystem_attachments'];
        if (!is_file($absolute) || !is_readable($absolute)) {
            ++$result['missing'];
        }

        $documentId = $table === 'editor_html_asset_versions'
        ? ($assets[$row['asset_id']]['document_id'] ?? null) : ($row['document_id'] ?? null);
        $document = $documents[$documentId ?? 0] ?? null;
        $workspace = is_array($document) ? $owner->storageWorkspaceId($document['document_key']) : null;
        if ($workspace !== null) {
            ++$result[$html ? 'owned_html' : 'owned_attachments'];
            $prefix = '@data/workspaces/' . $directories->workspace($workspace) . '/pages/';
            $suffix = '(?:[a-z0-9-]{1,96}--)?' . preg_quote($document['uuid'], '#')
            . ($html ? '/content/' : '/attachments/');
            if (preg_match('#^' . preg_quote($prefix, '#') . $suffix . '#D', $path) !== 1) {
                ++$result['wrong_owner'];
            }
        }
    }
}

echo json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL;
exit($result['missing'] === 0 && $result['wrong_owner'] === 0 ? 0 : 1);
