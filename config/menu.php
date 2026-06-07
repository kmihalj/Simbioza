<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'brand' => [
        'route' => 'home',
    ],
    'top' => [
        'enabled' => true,
        'json' => __DIR__ . '/menu/top.json',
    ],
    'settings' => [
        'enabled' => true,
        'route' => 'menu.settings',
        'json' => __DIR__ . '/menu/settings.json',
    ],
    'contexts' => [
        'enabled' => true,
        'json' => __DIR__ . '/menu/contexts.json',
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
