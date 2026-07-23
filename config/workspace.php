<?php

declare(strict_types=1);

// HR: Aplikacijske putanje i zadane vrijednosti koje Workspace modul može mijenjati kroz postavke.
// EN: Application routes and defaults that the Workspace module may update through its settings.
return [
    'routing' => [
        'root_path' => 'workspace',
    ],
    'defaults' => [
        'visibility' => 'restricted',
        'tree_visible' => true,
    ],
    'creation' => [
        'authenticated_users' => false,
    ],
    'menu' => [
        'auto_register_top' => true,
        'auto_register_settings' => true,
    ],
];
