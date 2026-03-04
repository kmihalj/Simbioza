<?php

declare(strict_types=1);

use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Module\ModuleBootstrapperInterface;
use HeartPhrame\View\View;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

// Bootstrap code. Executed after the container is built.
return [
    function (ContainerInterface $container): void {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        if (is_string($timezone = $config->get('timezone'))) {
            ini_set('date.timezone', $timezone);
        }

        if ($config->get('env.environment') !== 'development') {
            // Set a custom error handler
            set_error_handler(function ($severity, $message, $file, $line): false {
                // Check if the severity corresponds to a warning or any other error level we want to handle
                if ((error_reporting() & $severity) === 0) {
                    // If error_reporting() excludes this error level, ignore it
                    return false;
                }

                throw new ErrorException($message, 0, $severity, $file, $line);
            });

            // Configure error reporting to include warnings
            error_reporting(E_ALL);

            // Disable runtime error output
            ini_set('display_errors', '0');
            // Log errors instead of outputting
            ini_set('log_errors', '1');
            //ini_set('error_log', '/path/to/your/php-error.log');
        } else {
            ini_set('xdebug.var_display_max_depth', 10); // -1 for no limits
            ini_set('debug.var_display_max_children', 256);
            ini_set('xdebug.var_display_max_data', 1024);
        }

        // Load the module bootstrapper and execute it.
        /** @var ModuleBootstrapperInterface $moduleBootstrapper */
        $moduleBootstrapper = $container->get(ModuleBootstrapperInterface::class);
        $moduleBootstrapper->do();

        // Add global data to views.
        /** @var View $view */
        $view = $container->get(View::class);
        $view->addGlobal('key', 'value');

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $logger->info('HeartPhrame: Bootstrap code executed.');
    },
];
