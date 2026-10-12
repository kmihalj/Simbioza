<?php

declare(strict_types=1);

// EN: Theme options and global JSON use HPH_CONFIG_PATH/theme and its override layers.
// Shared theme assets remain application data; private workspace themes are unchanged.
// Relative asset paths belong to the application root, not the configuration directory.
//
// HR: Postavke Tema i globalni JSON koriste HPH_CONFIG_PATH/theme i override slojeve.
// Zajednički asseti tema ostaju aplikacijski podaci; privatne teme područja su nepromijenjene.
// Relativne putanje asseta pripadaju korijenu aplikacije, ne konfiguracijskom direktoriju.
return [
    'enabled' => true,
    'settings' => ['enabled' => true, 'route' => 'theme.settings'],
    'storage' => ['themes_dir' => 'data/themes'],
    'menu_integration' => [
        'enabled' => true,
        'auto_register_settings_item' => true,
        'group_id' => 'theme',
        'item_id' => 'theme.settings',
        'order' => 80,
    ],
];
