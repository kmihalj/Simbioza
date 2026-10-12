<?php

declare(strict_types=1);

// EN: Named synthetic Colima installation only; never access private HFC settings.
//
// HR: Samo imenovana sintetička Colima instalacija; ne pristupati privatnim HFC postavkama.
$root = dirname(__DIR__);
if (!str_starts_with($root, '/srv/simbioza-config-test/')) {
    throw new RuntimeException('Use only the isolated Colima installation.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
header('Content-Type: application/json');
try {
    $config = new \HeartPhrame\Config\Config(new \HeartPhrame\Helper\Helper(), [], $root);
    $layers = \HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root);
    $config->loadLayeredDirectories($layers);
    $menu = new \AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository(
        $config,
        $root . '/vendor/aaieduhr/heartphrame-module-menu',
    );
    $theme = new \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository(
        $config,
        $root . '/vendor/aaieduhr/heartphrame-module-theme',
    );
    $highest = $layers[count($layers) - 1];
    $checks = ['real_fpm' => PHP_SAPI === 'fpm-fcgi'];
    foreach (['menu', 'theme'] as $module) {
        $target = $highest . '/' . $module . '/settings.json';
        $before = file_get_contents($target);
        $inode = fileinode($target);
        $owner = fileowner($target);
        if (($_GET['mode'] ?? '') === 'readonly') {
            try {
                $module === 'menu' ? $menu->writeJsonContentsToSection('settings', '[]')
                : $theme->writeJsonContents('settings', '{}');
                $checks[$module . '_read_only_rejected'] = false;
            } catch (RuntimeException) {
                $checks[$module . '_read_only_rejected'] = file_get_contents($target) === $before;
            }
        } elseif (($_GET['mode'] ?? '') !== 'read') {
            $module === 'menu' ? $menu->writeJsonContentsToSection('settings', '[{"id":"fpm-write"}]')
            : $theme->writeJsonContents('settings', '{"enabled":false}');
            clearstatcache();
            $checks[$module . '_other_owner_inode_preserved'] = fileinode($target) === $inode
            && fileowner($target) === $owner;
        }
    }

    $checks += [
        'menu_target' => $menu->jsonPathForSection('settings') === $highest . '/menu/settings.json',
        'theme_target' => $theme->settingsJsonPath() === $highest . '/theme/settings.json',
        'context_is_data' => $menu->jsonPathForSection('contexts') === $root . '/data/menu/contexts.json',
        'theme_library_is_data' => $theme->themesDirectoryPath(false) === $root . '/data/themes',
        'manual_php_layer' => $menu->configurationFiles()->options()['fixture'] === 'manual'
            && $theme->configurationFiles()->options()['fixture'] === 'manual',
    ];
    if (($_GET['mode'] ?? '') === 'read') {
        $checks['manual_json_read'] = !$theme->isEnabled()
        && str_contains((string)file_get_contents($menu->jsonPathForSection('settings')), 'manual-edit');
    }

    if (count($layers) > 1) {
        $checks['lower_unchanged'] = file_get_contents($layers[0] . '/menu/settings.json') === '[]'
        && file_get_contents($layers[0] . '/theme/settings.json') === '{}';
        $checks['lower_not_writable'] = !is_writable($layers[0] . '/menu/settings.json');
        $checks['code_not_writable'] = !is_writable($root . '/src');
    }

    if (in_array(false, $checks, true)) {
        http_response_code(500);
    }

    echo json_encode(['checks' => $checks], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
