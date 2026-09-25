<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\SetupController;
use HeartPhrame\CodeBook\HttpMethodsEnum;
use HeartPhrame\Middleware\SampleMiddleware;
use HeartPhrame\Routing\Route;

return [
    // Array format: [method, path, handler, name, [middleware]]
    ['GET', '/', HomeController::class . '@index', 'home', [SampleMiddleware::class]],

    // Or use Route / RouteGroup instances
    new Route(
        HttpMethodsEnum::GET,
        '/about',
        [HomeController::class, 'about'],
        'about',
        [],
    ),
    new Route(HttpMethodsEnum::GET, '/settings/setup', [SetupController::class, 'index'], 'setup.index', []),
    new Route(
        HttpMethodsEnum::GET,
        '/settings/setup/application-update-status',
        [SetupController::class, 'applicationUpdateStatus'],
        'setup.application-update-status',
        [],
    ),
    new Route(HttpMethodsEnum::POST, '/settings/setup', [SetupController::class, 'change'], 'setup.change', []),
    // HR: Administratorska sklopka ostaje u aplikaciji i kada je opcionalni modul isključen.
    // EN: The administrator toggle stays in the application while the optional module is disabled.
    new Route(
        HttpMethodsEnum::GET,
        '/settings/accessibility',
        [SetupController::class, 'accessibility'],
        'accessibility.settings',
        [],
    ),
    new Route(
        HttpMethodsEnum::POST,
        '/settings/accessibility',
        [SetupController::class, 'changeAccessibility'],
        'accessibility.settings.change',
        [],
    ),
];
