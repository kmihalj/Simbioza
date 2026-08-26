<?php

declare(strict_types=1);

return [
    'routing' => [
        'root_path' => 'workspace',
    ],
    'defaults' => [
        'visibility' => 'restricted',
        'tree_visible' => true,
        'contents_visible' => true,
    ],
    'creation' => [
        'users' => [],
        'groups' => [],
    ],
    'shorts' => [
        'depth' => 2,
        'limit' => 10,
        'order' => 'newest',
        'display_options_visible' => false,
    ],
    'menu' => [
        'auto_register_top' => true,
        'auto_register_settings' => true,
    ],
];
