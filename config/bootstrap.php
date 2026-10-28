<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\View\View;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

// Bootstrap code. Executed after the container is built.
return [
    function (ContainerInterface $container): void {
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        if (is_string($timezone = $config->get('app.timezone'))) {
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

        // Add global data to views.
        /** @var View $view */
        $view = $container->get(View::class);
        $view->addGlobal('key', 'value');

        // EN: Focused products have the same bundled public manual without adding Wiki or Editor.
        // Native Wiki installations keep their dedicated guide Workspace instead.
        //
        // HR: Samostalni alati imaju isti javni priručnik bez dodavanja Wikija ili Editora.
        // Izvorne Wiki instalacije umjesto toga zadržavaju vlastito područje uputa.
        $enabledModules = $config->get('app.modules.enabled', []);
        $wikiEnabled = is_array($enabledModules)
            && in_array('aaieduhr/simbioza-module-workspace', $enabledModules, true);
        $menu = is_array($enabledModules)
            && in_array('aaieduhr/heartphrame-module-menu', $enabledModules, true)
            && class_exists(MenuConfigRepository::class)
            ? $container->get(MenuConfigRepository::class)
            : null;
        if ($menu instanceof MenuConfigRepository) {
            $menu->upsertItemsForSection('top', $wikiEnabled ? [] : [[
                'id' => 'simbioza.guides', 'label' => ['hr' => 'Upute', 'en' => 'Guides'],
                'translation_key' => 'Upute', 'route' => 'guides.index', 'order' => 80, 'enabled' => true,
            ]], $wikiEnabled ? ['simbioza.guides'] : []);

            // EN: Focused products need a reader entry, not an administrator-only editor link.
            // Saved menu labels, ordering and disabled states remain administrator choices.
            //
            // HR: Samostalni alati trebaju ulaz za čitatelje, ne samo administratorski editor.
            // Spremljeni nazivi, redoslijed i isključene stavke ostaju odabir administratora.
            $contentItems = [];
            $removeContentIds = [];
            foreach (
                [
                ['aaieduhr/heartphrame-module-editor-html', 'simbioza.pages',
                    'HTML stranice', 'HTML pages', 'content.pages', 55],
                ['aaieduhr/heartphrame-module-survey', 'simbioza.surveys', 'Ankete', 'Surveys', 'content.surveys', 60],
                ] as [$package, $id, $hr, $en, $route, $order]
            ) {
                if (is_array($enabledModules) && in_array($package, $enabledModules, true)) {
                    $contentItems[] = ['id' => $id, 'label' => ['hr' => $hr, 'en' => $en],
                        'route' => $route, 'order' => $order, 'enabled' => true];
                } else {
                    $removeContentIds[] = $id;
                }
            }

            $menu->upsertItemsForSection('top', $contentItems, $removeContentIds);
        }

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $logger->info('HeartPhrame: Bootstrap code executed.');
    },
];
