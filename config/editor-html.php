<?php

declare(strict_types=1);

return [
    'storage' => [
        'driver' => 'filesystem',
        'filesystem_path' => 'editor-html',
    ],
    'view' => [
        'table_of_contents' => true,
        'slug' => [
            'enabled' => true,
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
