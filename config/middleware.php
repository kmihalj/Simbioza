<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleMenu\Middleware\RequireSettingsAdminWhenAuthEnabledMiddleware;

// Global middleware for all routes.
$middleware = [
    \HeartPhrame\Middleware\TrustedProxyMiddleware::class,
    \HeartPhrame\Middleware\StartSessionMiddleware::class,
    \HeartPhrame\Middleware\CheckCsrfMiddleware::class,
    \HeartPhrame\Middleware\DeferredModuleLoaderMiddleware::class,
];

// HR: Ponovna settings provjera nakon odgođenih modula primjenjuje Simbioza
//     admin elevaciju, ali minimalne instalacije smiju raditi bez Menu modula.
// EN: Rechecking settings after deferred modules applies the Simbioza admin
//     elevation policy, while minimal installations may omit the Menu module.
$settingsAdminMiddleware = RequireSettingsAdminWhenAuthEnabledMiddleware::class;
if (class_exists($settingsAdminMiddleware)) {
    $middleware[] = $settingsAdminMiddleware;
}

// HR: Performance middleware u produkciji nije ni registriran; E2E runner ga
//     uključuje samo sigurnom ciljnom datotekom.
// EN: Performance middleware is not even registered in production; the E2E
//     runner enables it only with a safe target file.
if (trim((string)getenv('HPH_REQUEST_LOG')) !== '') {
    array_unshift($middleware, \App\Performance\RequestMetricsMiddleware::class);
}

return $middleware;
