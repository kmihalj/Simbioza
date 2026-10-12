<?php

/**
 * EN: Developer-only integration check against an explicitly retained SQLite
 *     E2E fixture. Never accepts HFC, demo, production, or arbitrary paths.
 *     Exercises real ZIP import, page/all approval, reimport and usage reporting.
 *     Leaves its harmless fixture pages for an optional manual browser check.
 *
 * HR: Razvojna integracijska provjera izričito zadržane SQLite E2E instalacije.
 *     Ne prihvaća HFC, demo, produkciju ni proizvoljne putanje. Provjerava pravi
 *     ZIP import, odobrenje jedne/svih stranica, reimport i pregled upotrebe.
 *     Bezopasne testne stranice ostavlja za opcionalnu provjeru preglednikom.
 */

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorApiActorContext;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorExternalScripts;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorExternalScriptUsage;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorService;
use AaiEduHr\SimbiozaModuleConfluenceImport\Service\ConfluenceImportRepository;
use AaiEduHr\SimbiozaModuleConfluenceImport\Service\ConfluenceImportService;
use AaiEduHr\SimbiozaModuleConfluenceImport\Service\ConfluenceImportUploadService;
use HeartPhrame\App;

$root = realpath($argv[1] ?? '');
$allowed = realpath(sys_get_temp_dir() . '/heartphrame-clean-matrix');
if (
    !is_string($root) || !is_string($allowed) || dirname($root) !== $allowed
    || preg_match('/^e2e-all-[a-f0-9]+$/D', basename($root)) !== 1
    || !is_file($root . '/data/matrix.sqlite')
) {
    throw new RuntimeException('Specify a retained isolated SQLite E2E fixture, not a development/live site.');
}
putenv('HPH_APP_PATH=' . $root);
putenv('HPH_CONFIG_PATH=' . $root . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
// EN: Model the fixture's web entry point for URLs generated during this CLI check.
//
// HR: Za URL-ove ovog CLI testa koristi ulaznu točku izolirane web instalacije.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
require $root . '/vendor/autoload.php';
$application = new App($root . '/config');
$application->loadCommands();
$container = $application->getContainer();
$users = $container->get(AuthUserService::class);
$actor = $users->findByLoginIdentifier('e2e-admin');
if (!is_array($actor) || ($actor['is_admin'] ?? false) !== true) {
    throw new RuntimeException('The isolated E2E administrator is required.');
}
$editor = $container->get(EditorService::class);
$registry = $editor->externalScripts();
$actors = $container->get(EditorApiActorContext::class);
$uploads = $container->get(ConfluenceImportUploadService::class);
$imports = $container->get(ConfluenceImportService::class);
$repository = $container->get(ConfluenceImportRepository::class);
$usage = $container->get(EditorExternalScriptUsage::class);
$suffix = bin2hex(random_bytes(4));
$scripts = [];
foreach (['Approved', 'Pending', 'Rejected'] as $name) {
    $scripts[$name] = EditorExternalScripts::definition([
        'name' => 'Registry fixture ' . $name . ' ' . $suffix,
        'type' => 'inline',
        'value' => 'window.registry' . $name . ' = (window.registry' . $name . ' || 0) + 1; // ' . $suffix,
    ]);
}
$registry->importPending([$scripts['Approved'], $scripts['Rejected']]);
$registry->decide($scripts['Approved']['id'], 'approved');
$registry->decide($scripts['Rejected']['id'], 'rejected');
$space = 'REGISTRY' . strtoupper($suffix);
$spaceId = (string)hexdec($suffix);
$archive = $root . '/data/registry-fixture-' . $suffix . '.zip';
$xml = '<?xml version="1.0" encoding="UTF-8"?><hibernate-generic>'
. '<object class="Space" package="com.atlassian.confluence.spaces"><id name="id">' . $spaceId . '</id>'
. '<property name="key">' . $space . '</property><property name="name">Registry fixture</property>'
. '<property name="spaceType">global</property><property name="homePage"><id>100</id></property>'
. '<property name="creator"><id>u1</id></property></object>'
. '<object class="ConfluenceUserImpl" package="com.atlassian.confluence.user"><id name="id">u1</id>'
. '<property name="username">e2e-admin</property><property name="displayName">E2E Administrator</property>'
. '<property name="emailAddress">e2e-admin@example.invalid</property></object>';
foreach ([100 => 'First', 101 => 'Second', 102 => 'Plain'] as $sourceId => $title) {
    $body = '<p>Registry fixture ' . $title . '</p>';
    if ($title !== 'Plain') {
        foreach ($scripts as $definition) {
            $body .= '<script>' . $definition['value'] . '</script>';
        }
    }
    $body = '<ac:structured-macro ac:name="html"><ac:plain-text-body><![CDATA[' . $body
    . ']]></ac:plain-text-body></ac:structured-macro>';
    $xml .= '<object class="Page" package="com.atlassian.confluence.pages"><id name="id">' . $sourceId
    . '</id><property name="space"><id>' . $spaceId . '</id></property><property name="title">' . $title
    . '</property><property name="version">1</property><property name="contentStatus">current</property>'
    . '<property name="creator"><id>u1</id></property><property name="lastModifier"><id>u1</id></property>'
    . '</object><object class="BodyContent" package="com.atlassian.confluence.core"><id name="id">b'
    . $sourceId . '</id><property name="content"><id>' . $sourceId . '</id></property><property name="body">'
    . htmlspecialchars($body, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</property></object>';
}
$xml .= '</hibernate-generic>';
$zip = new ZipArchive();
$zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL);
$zip->addFromString('entities.xml', $xml);
$zip->addFromString('exportDescriptor.properties', "exportType=space\nspaceKey=" . $space . "\nspaceKeys=" . $space
    . "\ncreatedByVersionNumber=10.2.11\nbackupAttachments=false\n");
if (!$zip->close()) {
    throw new RuntimeException('Unable to create the isolated Confluence archive.');
}
// EN: Use actual upload/preflight services; no database job fixtures bypass validation.
//
// HR: Koristi stvarni upload/preflight; ručni zapisi poslova ne zaobilaze provjeru.
$upload = static function () use ($archive, $uploads, $actor): array {
    $job = $uploads->start(basename($archive), filesize($archive), (int)$actor['id']);
    $stream = fopen($archive, 'rb');
    try {
        $uploads->append($job['uuid'], 0, $stream, (int)$actor['id']);
    } finally {
        fclose($stream);
    }
    return $uploads->finish($job['uuid'], (int)$actor['id']);
};
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$load = static function (array $job, string $sourceId) use ($repository, $editor, $actors, $actor): string {
    $content = $repository->contentForJobSource((int)$job['id'], $sourceId);
    return $actors->runAs($actor, fn(): string => $editor->load($content['target_document_key'], 'en')->html);
};
$options = [
    'workspace_name' => 'Registry fixture ' . $suffix, 'workspace_slug' => 'registry-' . $suffix,
    'language' => 'en', 'source_base_url' => 'https://source.example.invalid',
    'include_attachments' => false, 'include_comments' => false, 'identity_map' => ['u1' => (int)$actor['id']],
];
try {
    $job = $upload();
    $summary = $imports->import($job['uuid'], $options, $actor);
    $check($summary['pages_imported'] === 3, 'Expected all three pages.');
    foreach (['100', '101'] as $page) {
        $html = $load($job, $page);
        $check(EditorExternalScripts::references($html) === [$scripts['Approved']['id']], 'Auto-selection mismatch.');
        $check(count(EditorExternalScripts::references($html, true)) === 3, 'Candidates must remain available.');
    }
    $check(EditorExternalScripts::references($load($job, '102'), true) === [], 'Plain page gained a script.');
    $check($registry->all()[$scripts['Pending']['id']]['status'] === 'pending', 'Import granted approval.');
    $imports->decideExternalScript($job['uuid'], $scripts['Pending']['id'], 'approve', 'page', '100', $actor);
    $check(count(EditorExternalScripts::references($load($job, '100'))) === 2, 'Single-page approval failed.');
    $check(
        count(EditorExternalScripts::references($load($job, '101'))) === 1,
        'Single-page action affected another page.',
    );
    $imports->decideExternalScript($job['uuid'], $scripts['Pending']['id'], 'approve', 'all', '', $actor);
    $check(count(EditorExternalScripts::references($load($job, '101'))) === 2, 'Bulk approval failed.');
    $check($usage->find($scripts['Pending']['id'])['total'] === 2, 'Usage must find both pages.');
    $secondJob = $upload();
    $secondOptions = array_replace($options, [
        'reimport_strategy' => 'copy', 'workspace_slug' => 'registry-copy-' . $suffix,
        'workspace_name' => 'Registry reimport ' . $suffix,
    ]);
    $imports->import($secondJob['uuid'], $secondOptions, $actor);
    foreach (['100', '101'] as $page) {
        $check(count(EditorExternalScripts::references($load($secondJob, $page))) === 2, 'Reimport lost approval.');
    }
    $check(
        EditorExternalScripts::references($load($secondJob, '102'), true) === [],
        'Plain reimport page gained a script.',
    );
    $check($registry->all()[$scripts['Rejected']['id']]['status'] === 'rejected', 'Reimport changed rejection.');
    $check($usage->find($scripts['Pending']['id'])['total'] === 4, 'Usage must include both imports.');
    // EN: Verify real page, Workspace and full-site archives, not only a helper.
    //     Execution permissions must never leak into portable script records.
    //
    // HR: Provjeri stvarne arhive stranice, područja i cijelog sitea, ne samo helper.
    //     Dopuštenja izvršavanja ne smiju ući u prenosive zapise skripti.
    $backup = $container->get(BackupManager::class);
    $page = $repository->contentForJobSource((int)$job['id'], '100');
    $backupScopes = [
        new BackupScope(BackupScope::PAGE, (string)$page['target_node_id']),
        new BackupScope(BackupScope::WORKSPACE, $options['workspace_slug']),
        new BackupScope(BackupScope::SITE),
    ];
    foreach ($backupScopes as $scope) {
        $archivePath = $actors->runAs($actor, fn(): string => $backup->create(
            new BackupExportContext(
                $scope,
                [],
                options: ['site_base_url' => 'http://127.0.0.1:58084'],
                actorUserId: (int)$actor['id'],
                passphrase: 'RegistryFixture2026!',
            ),
            'Registry ' . $scope->type . ' ' . $suffix,
        ));
        $reader = new BackupArchiveReader(
            $archivePath,
            $container->get(BackupConfig::class),
            $container->get(BackupFilesystem::class),
            'RegistryFixture2026!',
        );
        try {
            $provider = $scope->type === BackupScope::SITE ? 'editor-html-files' : 'editor-html-workspace';
            $definitions = iterator_to_array($reader->records($provider, 'scripts'), false);
            $ids = array_column($definitions, 'id');
            foreach ($scripts as $script) {
                $check(in_array($script['id'], $ids, true), 'Backup lost a selected script or candidate.');
            }
            foreach ($definitions as $definition) {
                $check(!array_key_exists('status', $definition), 'Backup transferred execution permission.');
            }
        } finally {
            $reader->close();
        }
        $context = new BackupImportContext(
            $scope->type === BackupScope::WORKSPACE
                ? new BackupScope(BackupScope::WORKSPACE, $options['workspace_slug'] . '-backup')
                : $scope,
            $scope->type === BackupScope::SITE
                ? BackupImportContext::CONFLICT_MERGE
                : BackupImportContext::CONFLICT_COPY,
            [],
            options: [
                'site_base_url' => 'http://127.0.0.1:58084',
                'editor-html-workspace' => ['target_workspace' => $options['workspace_slug']],
                'page-transfer' => ['target_workspace' => $options['workspace_slug']],
            ],
            actorUserId: (int)$actor['id'],
            passphrase: 'RegistryFixture2026!',
        );
        $preflight = $backup->preflight($archivePath, $context);
        $check($preflight->isAllowed(), 'Archive preflight: ' . implode('; ', $preflight->errors));
        $actors->runAs($actor, fn() => $backup->restore($archivePath, $context));
        $check($registry->all()[$scripts['Approved']['id']]['status'] === 'approved', 'Restore lost local approval.');
        $check($registry->all()[$scripts['Rejected']['id']]['status'] === 'rejected', 'Restore lost local rejection.');
    }
    echo json_encode([
        'status' => 'success', 'job' => $job['uuid'], 'copy_job' => $secondJob['uuid'],
        'workspace' => $options['workspace_slug'], 'copy_workspace' => $secondOptions['workspace_slug'],
        'scripts' => array_map(static fn(array $entry): string => $entry['id'], $scripts),
        'backup_scopes' => array_map(static fn(BackupScope $scope): string => $scope->type, $backupScopes),
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    unlink($archive);
}
