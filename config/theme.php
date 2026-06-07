<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'settings' => [
        'enabled' => true,
        'route' => 'theme.settings',
    ],
    'storage' => [
        'themes_json' => __DIR__ . '/theme/themes.json',
        'settings_json' => __DIR__ . '/theme/settings.json',
    ],
    'menu_integration' => [
        'enabled' => true,
        'auto_register_settings_item' => true,
        'group_id' => 'theme',
        'item_id' => 'theme.settings',
        'order' => 80,
    ],
];
