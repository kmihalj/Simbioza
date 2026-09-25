<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\SetupController;
use App\Module\OptionalModuleRoutes;
use HeartPhrame\CodeBook\HttpMethodsEnum;
use HeartPhrame\Middleware\SampleMiddleware;
use HeartPhrame\Routing\Route;

$routes = [
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
];

// HR: Postavke dodatka ostaju dostupne kada je instaliran, čak i ako je isključen.
//     Uklonjen paket ne smije ostaviti rutu ni stavku izbornika.
// EN: Extension settings remain available while installed, even if disabled.
//     A removed package must leave neither a route nor a visible menu entry.
return [
    ...$routes,
    ...OptionalModuleRoutes::whenInstalled('aaieduhr/heartphrame-module-accessibility', [
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
    ]),
];
