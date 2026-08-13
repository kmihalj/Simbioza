<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use App\Performance\QueryLogWriter;
use HeartPhrame\Authn\ArrayAuthnHandler;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Cache\Cache;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Encryption\Encryption;
use HeartPhrame\Encryption\EncryptionInterface;
use HeartPhrame\Event\EventDispatcher;
use HeartPhrame\Event\ListenerProvider;
use HeartPhrame\Factory\CallableFactory;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\Logger\FileLogHandler;
use HeartPhrame\Logger\Logger;
use HeartPhrame\Module\ModuleBootstrapper;
use HeartPhrame\Module\ModuleBootstrapperInterface;
use HeartPhrame\Session\PhpSessionFactory;
use HeartPhrame\Session\SessionFactoryInterface;
use HeartPhrame\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

$services = [
    // HR: ORM profiler ostaje potpuno isključen dok alat za performanse ne
    //     postavi ciljnu JSONL datoteku. Normalni zahtjevi nemaju diskovni log.
    // EN: ORM profiling remains fully disabled until the performance tool sets
    //     a JSONL target. Normal requests perform no query-log disk writes.
    Database::class => function (ContainerInterface $container): Database {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        /** @var Helper $helper */
        $helper = $container->get(Helper::class);
        $database = new Database($config, $helper);
        $queryLogPath = trim((string)getenv('HPH_QUERY_LOG'));
        if ($queryLogPath !== '') {
            $database->listen(new QueryLogWriter($queryLogPath));
        }

        return $database;
    },

    // PSR-7 Server Request
    ServerRequestInterface::class => fn(): \HeartPhrame\Http\Request => Request::fromGlobals(),

    // PSR-14 Event Dispatcher
    EventDispatcherInterface::class => function (ContainerInterface $container): EventDispatcherInterface {
        /** @var ListenerProvider $provider */
        $provider = $container->get(ListenerProvider::class);
        /** @var CallableFactory $callableFactory */
        $callableFactory = $container->get(CallableFactory::class);
        return new EventDispatcher($provider, $callableFactory);
    },

    // PSR-16 Cache
    CacheInterface::class => function (ContainerInterface $container): CacheInterface {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        return new Cache($config->getAsStringOrFail('app.cache_dir'));
    },

    // PSR-17 Stream Factory
    StreamFactoryInterface::class => fn(): \HeartPhrame\Http\StreamFactory => new StreamFactory(),

    // Session handler
    SessionFactoryInterface::class => function (ContainerInterface $container): SessionFactoryInterface {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        /** @var Helper $helper */
        $helper = $container->get(Helper::class);
        return new PhpSessionFactory($config, $helper);
    },

    SessionInterface::class => function (ContainerInterface $container): SessionInterface {
        /** @var SessionFactoryInterface $sessionFactory */
        $sessionFactory = $container->get(SessionFactoryInterface::class);
        return $sessionFactory->getSession();
    },

    // PSR-3 Logger
    LoggerInterface::class => function (ContainerInterface $container): LoggerInterface {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        /** @var SessionInterface $session */
        $session = $container->get(SessionInterface::class);
        $logger = new Logger('app', $session);
        $logsDir = rtrim($config->getAsNonEmptyStringOrFail('app.logs.dir'), '/') . '/' ;
        $appLogName = $config->getAsNonEmptyStringOrFail('app.logs.filename');
        $logLevel = $config->getAsNonEmptyStringOrFail('env.log_level');
        $handler = new FileLogHandler($logsDir . '/' .  $appLogName, $logLevel);
        $logger->addHandler($handler);
        return $logger;
    },

    // HR: Frameworkov pomoćni autentikacijski handler čita korisnike iz aplikacijske konfiguracije.
    // EN: The framework helper authentication handler reads users from application configuration.
    AuthnHandlerInterface::class => function (ContainerInterface $container): AuthnHandlerInterface {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        return new ArrayAuthnHandler($config);
    },

    // Encryption
    EncryptionInterface::class => function (ContainerInterface $container): EncryptionInterface {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $encryptionKey = $config->getAsNonEmptyStringOrFail('env.encryption_key');

        $encryption = new Encryption();
        $encryption->setKey($encryptionKey);
        return $encryption;
    },

    // Module Bootstrapper
    ModuleBootstrapperInterface::class => fn(
        ContainerInterface $container,
    ): ModuleBootstrapperInterface => new ModuleBootstrapper($container),
];

if (class_exists(\AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider::class)) {
    $services['simbioza.backup.provider.application-config'] = static function (
        ContainerInterface $container,
    ): \AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        $files = $config->get('backup.application_configuration');
        $filesystem = $container->get(\AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem::class);
        if (!$filesystem instanceof \AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem) {
            throw new RuntimeException('Backup filesystem service is unavailable.');
        }

        /**
         * HR: Config granica se validira prije predaje generičkom provideru.
         * EN: The config boundary is validated before passing it to the generic provider.
         *
         * @var list<array{key:string,path:string,include_keys?:list<string>,sensitive?:bool}> $providerFiles
         */
        $providerFiles = [];
        if (is_array($files)) {
            foreach ($files as $definition) {
                if (
                    !is_array($definition)
                    || !is_string($definition['key'] ?? null)
                    || !is_string($definition['path'] ?? null)
                ) {
                    throw new RuntimeException('Invalid application backup configuration definition.');
                }

                $includeKeys = $definition['include_keys'] ?? [];
                if (!is_array($includeKeys)) {
                    throw new RuntimeException('Invalid application backup configuration key list.');
                }

                $normalizedKeys = [];
                foreach ($includeKeys as $includeKey) {
                    if (!is_string($includeKey)) {
                        throw new RuntimeException('Application backup configuration keys must be strings.');
                    }

                    $normalizedKeys[] = $includeKey;
                }

                $providerFiles[] = [
                    'key' => $definition['key'],
                    'path' => $definition['path'],
                    'include_keys' => $normalizedKeys,
                    'sensitive' => (bool)($definition['sensitive'] ?? false),
                ];
            }
        }

        return new \AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider(
            new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata(
                'application-config',
                'heartphrame/simbioza',
                1,
                ['hr' => 'Postavke aplikacije', 'en' => 'Application settings'],
                [],
                [
                    \AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope::SITE,
                    \AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope::COMPONENT,
                ],
                true,
                false,
            ),
            $filesystem,
            $providerFiles,
        );
    };
}

return $services;
