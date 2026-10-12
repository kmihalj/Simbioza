<?php

declare(strict_types=1);

return [
    // EN: Shared limits apply per API key and per minute.
    //
    // HR: Zajednička ograničenja vrijede po API ključu i po minuti.
    'rate_limit_per_minute' => 120,
    'max_json_body_bytes' => 2_097_152,
    'idempotency_ttl_seconds' => 86_400,
    'require_if_match' => true,

    // EN: CORS remains safely disabled until an administrator explicitly allows origins.
    //
    // HR: CORS je sigurno isključen dok administrator izričito ne odredi izvore.
    'cors' => [
        'enabled' => false,
        'allowed_origins' => [],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => [
            'Accept',
            'Accept-Language',
            'Authorization',
            'Content-Type',
            'Idempotency-Key',
            'If-Match',
            'X-Request-Id',
        ],
        'allow_credentials' => false,
        'max_age' => 600,
    ],

    // EN: Webhook events are stored in an outbox and sent by a background worker.
    //
    // HR: Webhook događaji se spremaju u outbox i šalju iz pozadinskog workera.
    'webhooks' => [
        'enabled' => true,
        'max_attempts' => 8,
        'base_retry_seconds' => 30,
        'max_retry_seconds' => 3600,
        'timeout_seconds' => 15,
        'allow_insecure_http' => false,
        'allow_private_networks' => false,
        'allowed_hosts' => [],
    ],
];
