<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'settings' => [
        'enabled' => true,
        'route' => 'theme.settings',
    ],
    'storage' => [
        'themes_json' => dirname(__DIR__) . '/resources/config/theme/themes.json',
        'settings_json' => dirname(__DIR__) . '/resources/config/theme/settings.json',
    ],
    'menu_integration' => [
        'enabled' => true,
        'auto_register_settings_item' => true,
        'group_id' => 'theme',
        'item_id' => 'theme.settings',
        'order' => 80,
    ],
];
