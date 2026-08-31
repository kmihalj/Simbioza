<?php

declare(strict_types=1);

$installationFile = __DIR__ . '/installation.php';
$installation = is_file($installationFile) ? require $installationFile : [];
if (!is_array($installation)) {
    $installation = [];
}

$applicationName = is_string($installation['name'] ?? null) && trim($installation['name']) !== ''
? trim($installation['name'])
: 'Simbioza';
$primaryLocale = is_string($installation['primary_locale'] ?? null)
? strtolower(trim($installation['primary_locale']))
: 'hr';
$supportedLocales = is_array($installation['supported_locales'] ?? null)
? array_values(array_filter(
    $installation['supported_locales'],
    static fn(mixed $locale): bool => is_string($locale) && in_array($locale, ['hr', 'en'], true),
))
: ['hr', 'en'];
if ($supportedLocales === [] || !in_array($primaryLocale, $supportedLocales, true)) {
    $primaryLocale = 'hr';
    $supportedLocales = ['hr', 'en'];
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
        'enabled' => [
            'aaieduhr/heartphrame-module-orm',
            'aaieduhr/heartphrame-module-menu',
            'aaieduhr/heartphrame-module-theme',
            'aaieduhr/heartphrame-module-auth',
            'aaieduhr/heartphrame-module-audit',
            'aaieduhr/heartphrame-module-api',
            'aaieduhr/heartphrame-module-email',
            'aaieduhr/heartphrame-module-notification',
            'aaieduhr/heartphrame-module-editor-html',
            'aaieduhr/heartphrame-module-task',
            'aaieduhr/heartphrame-module-comment',
            'aaieduhr/simbioza-module-workspace',
            'aaieduhr/simbioza-module-workspace-search',
            'aaieduhr/heartphrame-module-calendar',
            'aaieduhr/simbioza-module-user',
            'aaieduhr/simbioza-module-confluence-import',
            'aaieduhr/heartphrame-module-backup',
        ],
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
