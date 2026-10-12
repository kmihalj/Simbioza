<?php

declare(strict_types=1);

// EN: Runs only in the named synthetic Colima installation, with fictional values and no GUI.
//
// HR: Radi samo u imenovanoj sintetičkoj Colima instalaciji, s lažnim vrijednostima i bez GUI-ja.
$root = dirname(__DIR__);
if (!str_starts_with($root, '/srv/simbioza-config-test/')) {
    throw new RuntimeException('Use only the isolated Colima installation.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
header('Content-Type: application/json');
try {
    $config = new \HeartPhrame\Config\Config(new \HeartPhrame\Helper\Helper(), [], $root);
    $layers = \HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root);
    $config->loadLayeredDirectories($layers);
    $secure = count($layers) > 1;
    $composer = new class ($root) extends \HeartPhrame\Bridge\ComposerBridge {
        /**
         * EN: Uses the isolated installation as the Composer application root.
         *
         * HR: Koristi izoliranu instalaciju kao Composerov korijen aplikacije.
         */
        public function __construct(private readonly string $root)
        {
        }

        /**
         * EN: Keeps complete metadata, replacing only the fictional application path.
         *
         * HR: Čuva potpune metapodatke, mijenjajući samo izmišljenu putanju aplikacije.
         */
        public function getRootPackage(): array
        {
            return array_replace(parent::getRootPackage(), ['install_path' => $this->root]);
        }
    };
    $backup = new \AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig(
        $config,
        $composer,
        $root . '/vendor/aaieduhr/heartphrame-module-backup',
    );
    $api = \AaiEduHr\HeartPhrameModuleApi\Service\ApiConfiguration::resolve($config);
    $webhooks = new \AaiEduHr\HeartPhrameModuleApi\Service\WebhookConfig($config);
    $database = new \AaiEduHr\HeartPhrameModuleOrm\Database\Database($config, new \HeartPhrame\Helper\Helper());
    $logs = new \AaiEduHr\HeartPhrameModuleAudit\Service\TechnicalLogService($config);
    $checks = [
        'real_fpm' => PHP_SAPI === 'fpm-fcgi',
        'backup_data_path' => $backup->archiveDirectory() === $root . '/data/custom-archives',
        'backup_layer_priority' => $backup->chunkSize() === ($secure ? 1048576 : 524288),
        'descriptor_in_highest_layer' => $backup->providerDescriptorPath()
            === $layers[count($layers) - 1] . '/backup/backup-providers.php',
        'api_policy' => $api->getAsInt('api.rate_limit_per_minute') === 37,
        'webhook_policy' => $webhooks->enabled() && $webhooks->maxAttempts() === ($secure ? 5 : 3),
        'list_replacement' => $webhooks->allowedHosts() === ($secure ? ['override.test'] : ['base.test', 'other.test']),
        'orm_shared_database' => $database->getDriverName() === 'sqlite',
        'audit_log_is_data' => $logs->path() === $root . '/data/logs/fixture.log',
        'secure_config_read_only' => !$secure || !is_writable($layers[0] . '/api/api.php'),
    ];
    if (in_array(false, $checks, true)) {
        http_response_code(500);
    }

    echo json_encode(['checks' => $checks], JSON_THROW_ON_ERROR);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => $throwable->getMessage()], JSON_THROW_ON_ERROR);
}
