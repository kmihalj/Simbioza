<?php

declare(strict_types=1);

return [
    // Application name
    'name' => 'HeartPhrame',

    // Localization
    'localization' => [
        'locale' => 'en',
        'fallback_locale' => 'en',
        'detect_browser_locale' => true,
        'translations_dir' => __DIR__ . '/../lang',
    ],

    // Cache directory
    'cache_dir' => __DIR__ . '/../data/cache',

    // Logs configuration
    'logs' => [
        // Logs directory
        'dir' => __DIR__ . '/../data/logs',
        'filename' => 'app.log',
    ],

    // Views configuration
    'views' => [
        'path' => __DIR__ . '/../views',
        'default_layout' => 'main',
    ],

    'timezone' => 'Europe/Zagreb',

    // Session configuration
    'session' => [
        'options' => [
            'use_cookies' => 1,
            'cookie_secure' => 1,
            'cookie_httponly' => 1,
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => 1,
            'name' => 'HEARTPHRAME_SESSION',
            'gc_maxlifetime' => 1440,
            'cookie_lifetime' => 0,
        ],
        // List of route prefixes for which the session will not be started by the StartSessionMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
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
            // Example: 'vendor/module-name'
            'aaieduhr/heartphrame-module-demo',
        ],
    ],

    'csrf' => [
        // List of route prefixes for which the CSRF token check will not be performed by the CheckCsrfMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
        ],
    ],
];
