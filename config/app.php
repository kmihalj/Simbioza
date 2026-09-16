<?php

declare(strict_types=1);

$installationFile = __DIR__ . '/installation.php';
$installation = is_file($installationFile) ? require $installationFile : [];
if (!is_array($installation)) {
    $installation = [];
}

$moduleStateFile = __DIR__ . '/modules.php';
$moduleState = is_file($moduleStateFile) ? require $moduleStateFile : [];
if (!is_array($moduleState)) {
    $moduleState = [];
}

$defaultEnabledModules = [
    'aaieduhr/heartphrame-module-orm',
    'aaieduhr/heartphrame-module-menu',
    'aaieduhr/heartphrame-module-auth',
    'aaieduhr/heartphrame-module-notification',
    'aaieduhr/heartphrame-module-editor-html',
    'aaieduhr/simbioza-module-workspace',
    'aaieduhr/simbioza-module-workspace-search',
    'aaieduhr/simbioza-module-user',
];
$allowedModules = [
    ...$defaultEnabledModules,
    'aaieduhr/heartphrame-module-theme',
    'aaieduhr/heartphrame-module-audit',
    'aaieduhr/heartphrame-module-api',
    'aaieduhr/heartphrame-module-email',
    'aaieduhr/heartphrame-module-task',
    'aaieduhr/heartphrame-module-comment',
    'aaieduhr/heartphrame-module-calendar',
    'aaieduhr/simbioza-module-confluence-import',
    'aaieduhr/heartphrame-module-backup',
];
$legacyInstalledModules = [];
if (!array_key_exists('enabled', $moduleState) && is_file(__DIR__ . '/../data/installation.lock')) {
    $legacyInstalledModules = array_values(array_filter(
        $allowedModules,
        static fn(string $package): bool => \Composer\InstalledVersions::isInstalled($package),
    ));
}

$enabledModules = is_array($moduleState['enabled'] ?? null)
? array_values(array_unique([
    ...$defaultEnabledModules,
    ...array_filter(
        $moduleState['enabled'],
        static fn(mixed $package): bool => is_string($package) && in_array($package, $allowedModules, true),
    ),
]))
: ($legacyInstalledModules !== [] ? $legacyInstalledModules : $defaultEnabledModules);

$applicationName = is_string($installation['name'] ?? null) && trim($installation['name']) !== ''
? trim($installation['name'])
: 'Simbioza';
$primaryLocale = is_string($installation['primary_locale'] ?? null)
? strtolower(trim($installation['primary_locale']))
: 'hr';
$languageRegistryFile = __DIR__ . '/languages.php';
$languageRegistry = is_file($languageRegistryFile) ? require $languageRegistryFile : [];
$availableLocales = [];
foreach (is_array($languageRegistry) ? array_keys($languageRegistry) : [] as $locale) {
    if (
        is_string($locale)
        && preg_match('/\A[A-Za-z0-9]+(?:[-_][A-Za-z0-9]+)*\z/D', $locale) === 1
        && is_file(__DIR__ . '/../lang/' . $locale . '.php')
    ) {
        $availableLocales[] = strtolower($locale);
    }
}

$availableLocales = array_values(array_unique($availableLocales));
if ($availableLocales === []) {
    $availableLocales = ['hr', 'en'];
}

$supportedLocales = is_array($installation['supported_locales'] ?? null)
? array_values(array_filter(
    $installation['supported_locales'],
    static fn(mixed $locale): bool => is_string($locale) && in_array($locale, $availableLocales, true),
))
: array_values(array_intersect(['hr', 'en'], $availableLocales));
if ($supportedLocales === [] || !in_array($primaryLocale, $supportedLocales, true)) {
    $primaryLocale = in_array('hr', $availableLocales, true) ? 'hr' : $availableLocales[0];
    $supportedLocales = $availableLocales;
}

$timezone = is_string($installation['timezone'] ?? null)
&& in_array($installation['timezone'], timezone_identifiers_list(), true)
? $installation['timezone']
: 'Europe/Zagreb';

return [
    // Application name
    'name' => $applicationName,

    // Localization
    'localization' => [
        'locale' => $primaryLocale,
        'fallback_locale' => $primaryLocale,
        'supported_locales' => $supportedLocales,
        // HR: Čista instalacija poštuje odabrani primarni jezik; korisnik ga
        //     i dalje može ručno promijeniti među dostupnim jezicima.
        // EN: A fresh installation honors its selected primary locale; users
        //     can still switch manually among the enabled locales.
        'detect_browser_locale' => $installation === [],
        'translations_dir' => __DIR__ . '/../lang',
    ],

    // Cache directory
    'cache_dir' => __DIR__ . '/../data/cache',

    // Logs configuration
    'logs' => [
        // Logs directory
        'dir' => __DIR__ . '/../data/logs',
        'filename' => 'app.log',
        // HR: Rotacija ograničava rast tehničkog loga na približno 100 MB.
        // EN: Rotation limits technical-log growth to approximately 100 MB.
        'max_bytes' => 10485760,
        'max_files' => 10,
    ],

    // Views configuration
    'views' => [
        'path' => __DIR__ . '/../views',
        'default_layout' => 'main',
    ],

    'timezone' => $timezone,

    // Session configuration
    'session' => [
        'options' => [
            'use_cookies' => 1,
            'cookie_secure' => 1,
            'cookie_httponly' => 1,
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => 1,
            'name' => 'HEARTPHRAME_SESSION',
            // HR: Auth modul primjenjuje kraće, administratorski podesivo trajanje prijave.
            // EN: The Auth module enforces the shorter administrator-configured login duration.
            'gc_maxlifetime' => 31536000,
            'cookie_lifetime' => 0,
        ],
        // List of route prefixes for which the session will not be started by the StartSessionMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
            '/api/v1',
        ],
    ],

    // Modules configuration
    'modules' => [
        // List of loadable module types
        'loadable_types' => [
            'heartphrame-module',
        ],
        // List of enabled modules (package names)
        'enabled' => $enabledModules,
    ],

    'csrf' => [
        // List of route prefixes for which the CSRF token check will not be performed by the CheckCsrfMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
            '/caldav',
            '/api/v1',
        ],
    ],
];
