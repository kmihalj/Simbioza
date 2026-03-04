<?php

declare(strict_types=1);

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

return [
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

    // Authn Handler
    AuthnHandlerInterface::class => fn(): AuthnHandlerInterface => new ArrayAuthnHandler(),

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
