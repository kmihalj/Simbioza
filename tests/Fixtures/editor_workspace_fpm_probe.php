<?php

declare(strict_types=1);

// EN: Only the named synthetic FPM installation; never accesses private HFC data.
//
// HR: Samo sintetička, imenovana FPM instalacija; ne pristupa privatnim HFC podacima.
$root = dirname(__DIR__);
if (!str_starts_with($root, '/srv/simbioza-config-test/')) {
    throw new RuntimeException('Use only the isolated Colima installation.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
header('Content-Type: application/json');
try {
    $helper = new \HeartPhrame\Helper\Helper();
    $standalone = new \HeartPhrame\Config\Config($helper, [
        'app' => ['modules' => ['enabled' => []]],
    ], $root);
    $integrated = new \HeartPhrame\Config\Config($helper, [
        'app' => ['modules' => ['enabled' => ['aaieduhr/simbioza-module-workspace']]],
    ], $root);
    $module = $root . '/vendor/aaieduhr/heartphrame-module-editor-html';
    $editor = new \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig($integrated, $module);
    $independent = new \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig($standalone, $module);
    $workspace = new \AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceConfig(
        $integrated,
        $root . '/vendor/aaieduhr/simbioza-module-workspace',
    );
    $checks = [
        'real_fpm' => PHP_SAPI === 'fpm-fcgi',
        'standalone_slug_enabled' => $independent->slugViewEnabled(),
        'workspace_runtime_override' => !$editor->slugViewEnabled() && $editor->configuredSlugViewEnabled(),
        'editor_gui_optional' => !$editor->guiEnabled(),
    ];

    if (($_GET['mode'] ?? '') !== 'read') {
        // EN: A separate attempt verifies Workspace write rejection independently of Editor.
        //
        // HR: Zaseban pokušaj potvrđuje Workspace zabranu zapisa neovisno o Editoru.
        if (($_GET['mode'] ?? '') !== 'workspace') {
            $settings = new \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlSettingsService($editor);
            $form = $settings->settingsForForm();
            $form['slug_enabled'] = false;
            $form['slug_path'] = 'forced-workspace-value';
            $form['table_of_contents'] = true;
            $settings->saveFromForm($form);
        }

        $database = new \AaiEduHr\HeartPhrameModuleOrm\Database\Database($integrated, $helper);
        $homepage = new \AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceHomepageRepository($database, $workspace);
        $homepage->saveSettings(0, 0, false);
        $checks['homepage_json_save'] = !$homepage->settings()['allow_user_selection'];
    }

    $editorValues = $editor->configurationStore()->read();
    $workspaceValues = $workspace->configurationStore()->read();
    $checks += [
        'override_not_persisted' => $editor->configuredSlugViewEnabled()
            && $editor->slugViewPath() === 'fixture-view',
        'extensions_preserved' => $editorValues['fixture_extension'] === 'preserved'
            && $workspaceValues['fixture_extension'] === 'preserved',
        'content_stays_in_data' => str_starts_with($editor->documentsRoot(), $root . '/data/'),
        'no_legacy_php' => !is_file($root . '/config/editor-html.php') && !is_file($root . '/config/workspace.php'),
    ];
    echo json_encode(['checks' => $checks,
        'editor_writable' => $editor->configurationStore()->isWritable(),
        'workspace_writable' => $workspace->configurationStore()->isWritable(),
        'editor_path' => $editor->settingsFilePath(), 'workspace_path' => $workspace->settingsFilePath(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
