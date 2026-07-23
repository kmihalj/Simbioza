<?php

declare(strict_types=1);

// HR: Aplikacijske postavke koje HTML editor može mijenjati kroz administratorsko sučelje.
// EN: Application settings that the HTML editor may update through its administration UI.
return [
    'storage' => [
        'driver' => 'filesystem',
        'filesystem_path' => 'editor-html',
    ],
    'view' => [
        'table_of_contents' => true,
        'slug' => [
            'enabled' => false,
            'path' => 'view',
        ],
    ],
    'localization' => [
        'default_language' => 'hr',
    ],
    'uploads' => [
        'path' => 'editor-html/uploads',
        'max_size_mb' => 1024,
        'chunk_size_mb' => 1,
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'video/mp4',
            'video/webm',
        ],
    ],
];
