<?php

declare(strict_types=1);

$languageRegistry = is_file(__DIR__ . '/languages.php') ? require __DIR__ . '/languages.php' : [];
$languageLabels = [];
foreach (is_array($languageRegistry) ? $languageRegistry : [] as $locale => $definition) {
    if (!is_string($locale) || !is_array($definition)) {
        continue;
    }

    $nativeName = $definition['native_name'] ?? null;
    if (is_string($nativeName) && trim($nativeName) !== '') {
        $languageLabels[strtolower($locale)] = trim($nativeName);
    }
}

return [
    'enabled' => true,
    'brand' => [
        'route' => 'home',
    ],
    'top' => [
        'enabled' => true,
        'json' => __DIR__ . '/../resources/config/menu/top.json',
    ],
    'settings' => [
        'enabled' => true,
        'route' => 'menu.settings',
        'json' => __DIR__ . '/../resources/config/menu/settings.json',
    ],
    'updates' => [
        'application_repository' => 'https://github.com/kmihalj/Simbioza',
        'application_version_file' => 'VERSION',
        'minimum_refresh_interval_seconds' => 60,
        'request_timeout_seconds' => 5,
    ],
    'contexts' => [
        'enabled' => true,
        'json' => __DIR__ . '/../resources/config/menu/contexts.json',
    ],
    'language_selector' => [
        'enabled' => true,
        'route' => 'menu.locale.switch',
        'session_key' => 'hfc_locale',
        'labels' => $languageLabels !== [] ? $languageLabels : ['hr' => 'Hrvatski', 'en' => 'English'],
        'flags_dir' => __DIR__ . '/../data/languages/flags',
    ],
];
