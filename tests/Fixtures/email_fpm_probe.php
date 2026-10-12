<?php

declare(strict_types=1);

// EN: Synthetic Email check, only in the isolated Colima installation.
//
// HR: Sintetička Email provjera, samo u izoliranoj Colima instalaciji.
$root = dirname(__DIR__);
if (!str_starts_with($root, '/srv/simbioza-config-test/')) {
    throw new RuntimeException('This fixture may run only in its isolated Colima installation.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
header('Content-Type: application/json');
try {
    $config = new \AaiEduHr\HeartPhrameModuleEmail\Service\EmailConfig(
        new \HeartPhrame\Config\Config(new \HeartPhrame\Helper\Helper(), [], $root),
        $root . '/vendor/aaieduhr/heartphrame-module-email',
    );
    $settings = new \AaiEduHr\HeartPhrameModuleEmail\Service\EmailSettingsService($config);
    if (($_GET['mode'] ?? '') !== 'read') {
        $settings->saveFromForm(['host' => 'smtp.example.invalid', 'username' => 'fixture',
            'password' => 'fixture-secret', 'sender_email' => 'fixture@example.invalid']);
        $settings->saveFromForm(['host' => 'smtp.example.invalid', 'username' => 'fixture',
            'password' => '', 'sender_email' => 'fixture@example.invalid']);
    }

    $checks = [
        'real_fpm' => PHP_SAPI === 'fpm-fcgi',
        'highest_layer_json' => is_file($config->settingsFilePath()),
        'secret_preserved' => $config->password() === 'fixture-secret',
        'secret_not_returned' => !array_key_exists('password', $settings->settingsForForm()),
        'legacy_not_used' => $config->host() === 'smtp.example.invalid',
    ];
    echo json_encode(['checks' => $checks, 'writable' => $config->store()->isWritable(),
        'path' => $config->settingsFilePath()], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
