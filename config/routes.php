<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use HeartPhrame\CodeBook\HttpMethodsEnum;
use HeartPhrame\Middleware\SampleMiddleware;
use HeartPhrame\Routing\Route;
use HeartPhrame\Routing\RouteGroup;

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

    // RouteGroup allows defining path / name prefixes and common middleware for a group of routes.
    new RouteGroup(
        new \HeartPhrame\Routing\RouteGroupProperties(
            '/contact',
            'contact.',
            [SampleMiddleware::class],
        ),
        new Route(
            HttpMethodsEnum::GET,
            '/index',
            [HomeController::class, 'contact'],
            'index',
        ),
        new Route(
            HttpMethodsEnum::POST,
            '/index',
            [HomeController::class, 'submitContact'],
            'submitContact',
        ),
    ),
];
