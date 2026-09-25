<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleMenu\Service\ComponentUpdateService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use App\Controllers\SetupController;
use App\Localization\LanguageCommand;
use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\ModuleCommand;
use App\Module\ModuleDataArchive;
use App\Module\ModuleLifecycleManager;
use App\Module\ModuleStateStore;
use App\Module\NativeProcessRunner;
use App\Module\ProcessRunnerInterface;
use App\Performance\QueryLogWriter;
use App\Setup\ApplicationUpdateStatusStore;
use App\Setup\SetupDiagnostics;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use App\Update\BundledUpgradeCommandManager;
use HeartPhrame\Alert\AlertHandler;
use HeartPhrame\Authn\ArrayAuthnHandler;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Cache\Cache;
use HeartPhrame\Command\CommandManager;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Encryption\Encryption;
use HeartPhrame\Encryption\EncryptionInterface;
use HeartPhrame\Event\EventDispatcher;
use HeartPhrame\Event\ListenerProvider;
use HeartPhrame\Factory\CallableFactory;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Logger\FileLogHandler;
use HeartPhrame\Logger\Logger;
use HeartPhrame\Module\ModuleBootstrapper;
use HeartPhrame\Module\ModuleBootstrapperInterface;
use HeartPhrame\Routing\UrlGenerator;
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
    LanguageRepository::class => static function (ContainerInterface $container): LanguageRepository {
        $config = $container->get(ConfigInterface::class);
        if (!$config instanceof ConfigInterface) {
            throw new RuntimeException('Language repository configuration is unavailable.');
        }

        return new LanguageRepository($config->getAppRootDir());
    },
    RepositoryLanguageManager::class => static function (ContainerInterface $container): RepositoryLanguageManager {
        $config = $container->get(ConfigInterface::class);
        $repository = $container->get(LanguageRepository::class);
        $languages = $container->get(LanguagePackManager::class);
        if (
            !$config instanceof ConfigInterface
            || !$repository instanceof LanguageRepository
            || !$languages instanceof LanguagePackManager
        ) {
            throw new RuntimeException('Repository language services are unavailable.');
        }

        return new RepositoryLanguageManager($repository, $languages, $config->getAppRootDir());
    },
    LanguagePackManager::class => static function (ContainerInterface $container): LanguagePackManager {
        $config = $container->get(ConfigInterface::class);
        $catalog = $container->get(ModuleCatalog::class);
        if (!$config instanceof ConfigInterface || !$catalog instanceof ModuleCatalog) {
            throw new RuntimeException('Language-pack services are unavailable.');
        }

        return new LanguagePackManager($config->getAppRootDir(), $catalog);
    },
    LanguageCommand::class => static function (ContainerInterface $container): LanguageCommand {
        $languages = $container->get(LanguagePackManager::class);
        $repository = $container->get(RepositoryLanguageManager::class);
        if (!$languages instanceof LanguagePackManager || !$repository instanceof RepositoryLanguageManager) {
            throw new RuntimeException('The language-pack manager is unavailable.');
        }

        return new LanguageCommand($languages, $repository);
    },
    ModuleCatalog::class => static fn(): ModuleCatalog => new ModuleCatalog(),
    ProcessRunnerInterface::class => static fn(): ProcessRunnerInterface => new NativeProcessRunner(),
    ComposerPackageManager::class => static function (ContainerInterface $container): ComposerPackageManager {
        $catalog = $container->get(ModuleCatalog::class);
        $runner = $container->get(ProcessRunnerInterface::class);
        $state = $container->get(ModuleStateStore::class);
        $config = $container->get(ConfigInterface::class);
        if (
            !$catalog instanceof ModuleCatalog
            || !$runner instanceof ProcessRunnerInterface
            || !$state instanceof ModuleStateStore
            || !$config instanceof ConfigInterface
        ) {
            throw new RuntimeException('Composer package services are unavailable.');
        }

        return new ComposerPackageManager($catalog, $runner, $config->getAppRootDir(), $state);
    },
    SetupRequestStore::class => static function (ContainerInterface $container): SetupRequestStore {
        $config = $container->get(ConfigInterface::class);
        if (!$config instanceof ConfigInterface) {
            throw new RuntimeException('Setup request configuration is unavailable.');
        }

        return new SetupRequestStore(
            $config->getAsString('setup.request_dir', $config->getAppRootDir() . '/data/setup-requests')
                ?? $config->getAppRootDir() . '/data/setup-requests',
        );
    },
    SetupGateway::class => static function (ContainerInterface $container): SetupGateway {
        $config = $container->get(ConfigInterface::class);
        $requests = $container->get(SetupRequestStore::class);
        $processes = $container->get(ProcessRunnerInterface::class);
        if (
            !$config instanceof ConfigInterface
            || !$requests instanceof SetupRequestStore
            || !$processes instanceof ProcessRunnerInterface
        ) {
            throw new RuntimeException('Setup gateway services are unavailable.');
        }

        return new SetupGateway(
            $requests,
            $processes,
            $config->getAppRootDir(),
            $config->getAsString('setup.helper', '/usr/local/sbin/simbioza-setup')
                ?? '/usr/local/sbin/simbioza-setup',
            getenv('SIMBIOZA_SETUP_DIRECT') === '1'
                || ($config->getAsBoolean('setup.direct_local_testing', false) ?? false),
        );
    },
    SetupDiagnostics::class => static function (ContainerInterface $container): SetupDiagnostics {
        $config = $container->get(ConfigInterface::class);
        $gateway = $container->get(SetupGateway::class);
        if (!$config instanceof ConfigInterface || !$gateway instanceof SetupGateway) {
            throw new RuntimeException('Setup diagnostics services are unavailable.');
        }

        return new SetupDiagnostics($config->getAppRootDir(), $gateway);
    },
    ApplicationUpdateStatusStore::class => static function (
        ContainerInterface $container,
    ): ApplicationUpdateStatusStore {
        $config = $container->get(ConfigInterface::class);
        if (!$config instanceof ConfigInterface) {
            throw new RuntimeException('Application update status service is unavailable.');
        }

        return new ApplicationUpdateStatusStore($config->getAppRootDir());
    },
    ModuleStateStore::class => static function (ContainerInterface $container): ModuleStateStore {
        $config = $container->get(ConfigInterface::class);
        $catalog = $container->get(ModuleCatalog::class);
        if (!$config instanceof ConfigInterface || !$catalog instanceof ModuleCatalog) {
            throw new RuntimeException('Module-state services are unavailable.');
        }

        return new ModuleStateStore($config->getAppRootDir(), $catalog);
    },
    ModuleDataArchive::class => static function (ContainerInterface $container): ModuleDataArchive {
        $database = $container->get(Database::class);
        $config = $container->get(ConfigInterface::class);
        if (!$database instanceof Database || !$config instanceof ConfigInterface) {
            throw new RuntimeException('Module-archive services are unavailable.');
        }

        return new ModuleDataArchive($database, $config->getAppRootDir());
    },
    ModuleLifecycleManager::class => static function (ContainerInterface $container): ModuleLifecycleManager {
        $database = $container->get(Database::class);
        $catalog = $container->get(ModuleCatalog::class);
        $state = $container->get(ModuleStateStore::class);
        $archives = $container->get(ModuleDataArchive::class);
        $config = $container->get(ConfigInterface::class);
        $packages = $container->get(ComposerPackageManager::class);
        if (
            !$database instanceof Database
            || !$catalog instanceof ModuleCatalog
            || !$state instanceof ModuleStateStore
            || !$archives instanceof ModuleDataArchive
            || !$config instanceof ConfigInterface
            || !$packages instanceof ComposerPackageManager
        ) {
            throw new RuntimeException('Module-lifecycle services are unavailable.');
        }

        return new ModuleLifecycleManager(
            $database,
            $catalog,
            $state,
            $archives,
            $config->getAppRootDir(),
            $packages,
        );
    },
    ModuleCommand::class => static function (ContainerInterface $container): ModuleCommand {
        $modules = $container->get(ModuleLifecycleManager::class);
        $gateway = $container->get(SetupGateway::class);
        if (!$modules instanceof ModuleLifecycleManager || !$gateway instanceof SetupGateway) {
            throw new RuntimeException('The module CLI services are unavailable.');
        }

        return new ModuleCommand($modules, $gateway);
    },
    SetupController::class => static function (ContainerInterface $container): SetupController {
        $responses = $container->get(ResponseFactory::class);
        $modules = $container->get(ModuleLifecycleManager::class);
        $catalog = $container->get(ModuleCatalog::class);
        $diagnostics = $container->get(SetupDiagnostics::class);
        $gateway = $container->get(SetupGateway::class);
        $requests = $container->get(SetupRequestStore::class);
        $languages = $container->get(LanguagePackManager::class);
        $repositoryLanguages = $container->get(RepositoryLanguageManager::class);
        $componentUpdates = $container->get(ComponentUpdateService::class);
        $updates = $container->get(ApplicationUpdateStatusStore::class);
        $alerts = $container->get(AlertHandler::class);
        $urls = $container->get(UrlGenerator::class);
        $translator = $container->get(TranslatorInterface::class);
        if (
            !$responses instanceof ResponseFactory
            || !$modules instanceof ModuleLifecycleManager
            || !$catalog instanceof ModuleCatalog
            || !$diagnostics instanceof SetupDiagnostics
            || !$gateway instanceof SetupGateway
            || !$requests instanceof SetupRequestStore
            || !$languages instanceof LanguagePackManager
            || !$repositoryLanguages instanceof RepositoryLanguageManager
            || !$componentUpdates instanceof ComponentUpdateService
            || !$updates instanceof ApplicationUpdateStatusStore
            || !$alerts instanceof AlertHandler
            || !$urls instanceof UrlGenerator
            || !$translator instanceof TranslatorInterface
        ) {
            throw new RuntimeException('Setup controller services are unavailable.');
        }

        return new SetupController(
            $responses,
            $modules,
            $catalog,
            $diagnostics,
            $gateway,
            $requests,
            $languages,
            $repositoryLanguages,
            $componentUpdates,
            $updates,
            $alerts,
            $urls,
            $translator,
        );
    },
    // HR: I stariji updater nakon preuzimanja koda koristi novu CLI integraciju paketa.
    // EN: An older updater uses the new CLI bundle integration after downloading the code.
    CommandManager::class => static function (ContainerInterface $container): CommandManager {
        $logger = $container->get(LoggerInterface::class);
        $config = $container->get(ConfigInterface::class);
        if (!$logger instanceof LoggerInterface || !$config instanceof ConfigInterface) {
            throw new RuntimeException('The CLI command manager requires valid logger and configuration services.');
        }

        return new BundledUpgradeCommandManager($container, $logger, $config->getAppRootDir());
    },
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
        $logPath = $logsDir . '/' . $appLogName;
        // HR: Audit modul donosi sigurnu rotaciju i uklanjanje tajni iz
        //     tehničkog loga. Aplikacija i dalje radi s framework handlerom
        //     kada taj opcionalni modul nije instaliran.
        // EN: The Audit module provides safe rotation and secret redaction for
        //     the technical log. The app still works with the framework handler
        //     when that optional module is not installed.
        $handler = class_exists(\AaiEduHr\HeartPhrameModuleAudit\Log\RotatingFileLogHandler::class)
            ? new \AaiEduHr\HeartPhrameModuleAudit\Log\RotatingFileLogHandler(
                $logPath,
                $logLevel,
                max(1048576, $config->getAsInt('app.logs.max_bytes', 10485760) ?? 10485760),
                max(2, $config->getAsInt('app.logs.max_files', 10) ?? 10),
            )
            : new FileLogHandler($logPath, $logLevel);
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
         * @var list<array{key:string,path:string,include_keys?:list<string>,sensitive?:bool,
         *     restore_targets?:list<array{path:string,key_map:array<string,string>}>}> $providerFiles
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

                $providerFile = [
                    'key' => $definition['key'],
                    'path' => $definition['path'],
                    'include_keys' => $normalizedKeys,
                    'sensitive' => (bool)($definition['sensitive'] ?? false),
                ];
                if (array_key_exists('restore_targets', $definition)) {
                    if (!is_array($definition['restore_targets']) || $definition['restore_targets'] === []) {
                        throw new RuntimeException('Application backup restore targets must be a nonempty list.');
                    }

                    $targets = [];
                    foreach ($definition['restore_targets'] as $target) {
                        if (
                            !is_array($target) || !is_string($target['path'] ?? null)
                            || !is_array($target['key_map'] ?? null)
                        ) {
                            throw new RuntimeException('Invalid application backup restore target.');
                        }

                        $keyMap = [];
                        foreach ($target['key_map'] as $sourceKey => $targetKey) {
                            if (!is_string($sourceKey) || !is_string($targetKey)) {
                                throw new RuntimeException('Application backup mapping keys must be strings.');
                            }

                            $keyMap[$sourceKey] = $targetKey;
                        }

                        $targets[] = ['path' => $target['path'], 'key_map' => $keyMap];
                    }

                    $providerFile['restore_targets'] = $targets;
                }

                $providerFiles[] = $providerFile;
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
                componentGroups: [\AaiEduHr\HeartPhrameModuleBackup\Value\BackupComponentGroup::SETTINGS],
            ),
            $filesystem,
            $providerFiles,
        );
    };
}

return $services;
