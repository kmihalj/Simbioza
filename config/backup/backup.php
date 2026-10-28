<?php

declare(strict_types=1);

return [
    // EN: The branded filename does not change the portable HeartPhrame archive format.
    //
    // HR: Brendirani naziv ne mijenja prenosivi HeartPhrame format arhiva.
    'archive_suffix' => 'simbioza-backup.zip',
    'archive_dir' => 'data/backups/archives',
    'staging_dir' => 'data/backups/staging',
    'upload_dir' => 'data/backups/uploads',
    'chunk_size' => 8 * 1024 * 1024,
    'require_maintenance_for_full_replace' => true,

    // EN: Only portable business settings enter the backup. The local database
    // connection, encryption keys, absolute paths, logs, and cache do not.
    //
    // HR: U backup ulaze samo prenosive poslovne postavke. Lokalna veza baze,
    // ključevi šifriranja, apsolutne putanje, logovi i cache namjerno ne ulaze.
    'application_configuration' => [
        [
            'key' => 'application',
            'file' => 'app.php',
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
            // EN: Keep app.php dynamic; current archives restore data only
            // into the authoritative installation and module-state stores.
            //
            // HR: app.php ostaje dinamičan; aktualne arhive vraćaju samo podatke
            // u autoritativna spremišta instalacije i stanja modula.
            'restore_targets' => [
                [
                    'file' => 'installation.php',
                    'lock' => '.languages.lock',
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
                    'file' => 'modules.php',
                    'document' => true,
                    'lock' => '.modules.lock',
                    'key_map' => ['modules.enabled' => 'enabled'],
                ],
            ],
        ],
        [
            'key' => 'api-policy',
            'required_package' => 'aaieduhr/heartphrame-module-api',
            'file' => 'api/api.php',
            'include_keys' => [
                'rate_limit_per_minute', 'max_json_body_bytes', 'idempotency_ttl_seconds',
                'require_if_match', 'cors', 'webhooks',
            ],
        ],
        [
            'key' => 'workspace-search-policy',
            'required_package' => 'aaieduhr/simbioza-module-workspace-search',
            'file' => 'workspace-search/workspace-search.php',
            'include_keys' => [
                'results_per_page', 'maximum_results_per_page', 'minimum_query_length',
                'snippet_length', 'automatic_index_refresh_seconds',
            ],
        ],
    ],
];
