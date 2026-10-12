<?php

declare(strict_types=1);

// EN: Installation options only; global JSON is resolved in this module's config folder.
// Special page/workspace menus are content in data/menu, not installation settings.
// Language labels follow the same framework configuration layers as the application.
//
// HR: Samo instalacijske postavke; globalni JSON određuje se u config folderu ovog modula.
// Posebni meniji stranica/područja su sadržaj u data/menu, ne instalacijske postavke.
// Nazivi jezika prate iste konfiguracijske slojeve frameworka kao aplikacija.
$applicationRoot = getenv('HPH_APP_PATH') ?: dirname(__DIR__, 2);
$languageRegistry = [];
foreach (\HeartPhrame\Config\ConfigPathResolver::fromEnvironment($applicationRoot) as $directory) {
    $file = $directory . '/languages.php';
    if (is_file($file)) {
        $values = require $file;
        if (is_array($values)) {
            $languageRegistry = $values;
        }
    }
}

$languageLabels = [];
foreach ($languageRegistry as $locale => $definition) {
    $nativeName = is_array($definition) ? ($definition['native_name'] ?? null) : null;
    if (is_string($locale) && is_string($nativeName) && trim($nativeName) !== '') {
        $languageLabels[strtolower($locale)] = trim($nativeName);
    }
}

return [
    'enabled' => true,
    'brand' => ['route' => 'home'],
    'top' => ['enabled' => true],
    'settings' => ['enabled' => true, 'route' => 'menu.settings'],
    'contexts' => ['enabled' => true],
    'updates' => [
        'application_repository' => 'https://github.com/kmihalj/Simbioza',
        'application_version_file' => 'VERSION',
        'minimum_refresh_interval_seconds' => 60,
        'request_timeout_seconds' => 5,
    ],
    'language_selector' => [
        'enabled' => true,
        'route' => 'menu.locale.switch',
        'session_key' => 'hfc_locale',
        'labels' => $languageLabels !== [] ? $languageLabels : ['en' => 'English', 'hr' => 'Hrvatski'],
        'flags_dir' => 'data/languages/flags',
    ],
];
