<?php

declare(strict_types=1);

// EN: Isolated test installation only; never deploy to the public HFC.
//
// HR: Samo izolirana testna instalacija; nikada ne postavljati u javni HFC.
$root = dirname(__DIR__);
require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
header('Content-Type: application/json');
try {
    $paths = \App\Configuration\ConfigurationPaths::fromEnvironment($root);
    $reader = new \App\Configuration\PhpConfigurationReader($paths);
    $modules = new \App\Module\ModuleStateStore($root, new \App\Module\ModuleCatalog());
    $modules->enable('email');
    $language = new \App\Localization\LanguagePackManager($root, new \App\Module\ModuleCatalog());
    $language->setActive('en', true);
    $layers = $paths->directories();
    $secure = count($layers) > 1;
    $codeWritable = @file_put_contents($root . '/src/fpm-forbidden.txt', 'fixture') !== false;
    if ($codeWritable) {
        unlink($root . '/src/fpm-forbidden.txt');
    }

    $lowerWritable = $secure && @file_put_contents($layers[0] . '/forbidden.txt', 'fixture') !== false;
    if ($lowerWritable) {
        unlink($layers[0] . '/forbidden.txt');
    }

    $checks = [
        'real_fpm' => PHP_SAPI === 'fpm-fcgi',
        'modules_highest_layer' => is_file(end($layers) . '/modules.php') && $modules->isEnabled('email'),
        'languages_highest_layer' => is_file(end($layers) . '/installation.php')
            && in_array('en', $reader->read('installation.php')['supported_locales'] ?? [], true),
        'data_writable' => @file_put_contents($root . '/data/fpm-probe.txt', 'fixture') !== false,
        'secure_code_read_only' => !$secure || !$codeWritable,
        'secure_lower_read_only' => !$secure || !$lowerWritable,
    ];
    if (in_array(false, $checks, true)) {
        http_response_code(500);
    }

    echo json_encode(['checks' => $checks, 'config_layers' => $layers], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
