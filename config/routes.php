<?php

declare(strict_types=1);

use App\Controllers\HomeController;
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
];
