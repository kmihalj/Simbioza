<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'brand' => [
        'route' => 'home',
    ],
    'top' => [
        'enabled' => true,
        'json' => dirname(__DIR__) . '/resources/config/menu/top.json',
    ],
    'settings' => [
        'enabled' => true,
        'route' => 'menu.settings',
        'json' => dirname(__DIR__) . '/resources/config/menu/settings.json',
    ],
    'updates' => [
        'application_repository' => 'https://github.com/kmihalj/Simbioza',
        'application_version_file' => 'VERSION',
        'minimum_refresh_interval_seconds' => 60,
        'request_timeout_seconds' => 5,
    ],
    'contexts' => [
        'enabled' => true,
        'json' => dirname(__DIR__) . '/resources/config/menu/contexts.json',
    ],
    'language_selector' => [
        'enabled' => true,
        'route' => 'menu.locale.switch',
        'session_key' => 'hfc_locale',
        'labels' => [
            'hr' => 'Hrvatski',
            'en' => 'English',
        ],
    ],
];
