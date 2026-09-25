<?php

declare(strict_types=1);

return [
    // HR: Brendirani naziv ne mijenja prenosivi HeartPhrame format arhiva.
    // EN: The branded filename does not change the portable HeartPhrame archive format.
    'archive_suffix' => 'simbioza-backup.zip',
    'archive_dir' => 'data/backups/archives',
    'staging_dir' => 'data/backups/staging',
    'upload_dir' => 'data/backups/uploads',
    'chunk_size' => 8 * 1024 * 1024,
    'require_maintenance_for_full_replace' => true,

    // HR: U backup ulaze samo prenosive poslovne postavke. Lokalna veza baze,
    // ključevi šifriranja, apsolutne putanje, logovi i cache namjerno ne ulaze.
    // EN: Only portable business settings enter the backup. The local database
    // connection, encryption keys, absolute paths, logs, and cache do not.
    'application_configuration' => [
        [
            'key' => 'application',
            'path' => __DIR__ . '/app.php',
            'include_keys' => [
                'name',
                'localization.locale',
                'localization.fallback_locale',
                'localization.supported_locales',
                'localization.detect_browser_locale',
                'timezone',
                'session.options.gc_maxlifetime',
                'session.options.cookie_lifetime',
                'session.options.cookie_secure',
                'session.options.cookie_httponly',
                'session.options.cookie_samesite',
                'modules.enabled',
            ],
            // HR: app.php ostaje dinamičan; i stare arhive vraćaju samo podatke
            //     u autoritativna spremišta instalacije i stanja modula.
            // EN: Keep app.php dynamic; even old archives restore data only
            //     into the authoritative installation and module-state stores.
            'restore_targets' => [
                [
                    'path' => __DIR__ . '/installation.php',
                    'key_map' => [
                        'name' => 'name',
                        'localization.locale' => 'primary_locale',
                        'localization.supported_locales' => 'supported_locales',
                        'localization.detect_browser_locale' => 'detect_browser_locale',
                        'timezone' => 'timezone',
                        'session.options.gc_maxlifetime' => 'session_options.gc_maxlifetime',
                        'session.options.cookie_lifetime' => 'session_options.cookie_lifetime',
                        'session.options.cookie_secure' => 'session_options.cookie_secure',
                        'session.options.cookie_httponly' => 'session_options.cookie_httponly',
                        'session.options.cookie_samesite' => 'session_options.cookie_samesite',
                    ],
                ],
                [
                    'path' => __DIR__ . '/../data/config/modules.php',
                    'key_map' => ['modules.enabled' => 'enabled'],
                ],
            ],
        ],
        [
            'key' => 'api-policy',
            'path' => __DIR__ . '/api.php',
            'include_keys' => [
                'rate_limit_per_minute', 'max_json_body_bytes', 'idempotency_ttl_seconds',
                'require_if_match', 'cors', 'webhooks',
            ],
        ],
        [
            'key' => 'editor-policy',
            'path' => __DIR__ . '/editor-html.php',
            'include_keys' => [
                'view',
                'localization.default_language',
                'uploads.max_size_mb',
                'uploads.chunk_size_mb',
                'uploads.allowed_mime_types',
            ],
        ],
        [
            'key' => 'workspace-policy',
            'path' => __DIR__ . '/workspace.php',
            'include_keys' => ['defaults', 'creation', 'shorts', 'menu'],
        ],
        [
            'key' => 'workspace-search-policy',
            'path' => __DIR__ . '/workspace-search.php',
            'include_keys' => ['search', 'index', 'menu'],
        ],
    ],
];
