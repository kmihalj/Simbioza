<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleMenu\Middleware\RequireSettingsAdminWhenAuthEnabledMiddleware;

// Global middleware for all routes.
$middleware = [
    \HeartPhrame\Middleware\TrustedProxyMiddleware::class,
    \HeartPhrame\Middleware\SecurityHeadersMiddleware::class,
    \HeartPhrame\Middleware\ErrorHandlerMiddleware::class,
    \App\Security\ScriptPolicyMiddleware::class,
    \HeartPhrame\Middleware\StartSessionMiddleware::class,
    \HeartPhrame\Middleware\CheckCsrfMiddleware::class,
    \HeartPhrame\Middleware\DeferredModuleLoaderMiddleware::class,
];

// EN: Installation-private middleware runs before the admin gate; invalid
//     configuration fails closed instead of silently dropping protection.
//
// HR: Privatni middleware jedne instalacije ide prije administratorske provjere;
//     pogrešna konfiguracija ruši zatvoren sustav umjesto da ukine zaštitu.
$localMiddlewareFile = __DIR__ . '/middleware.local.php';
if (is_file($localMiddlewareFile)) {
    $localMiddleware = require $localMiddlewareFile;
    if (!is_array($localMiddleware)) {
        throw new RuntimeException('Local middleware configuration must return an array.');
    }

    foreach ($localMiddleware as $middlewareClass) {
        if (
            !is_string($middlewareClass)
            || !is_subclass_of($middlewareClass, \Psr\Http\Server\MiddlewareInterface::class)
        ) {
            throw new RuntimeException('Invalid local middleware class.');
        }

        $middleware[] = $middlewareClass;
    }
}

// EN: Rechecking settings after deferred modules applies the Simbioza admin
//     elevation policy, while minimal installations may omit the Menu module.
//
// HR: Ponovna settings provjera nakon odgođenih modula primjenjuje Simbioza
//     admin elevaciju, ali minimalne instalacije smiju raditi bez Menu modula.
$settingsAdminMiddleware = RequireSettingsAdminWhenAuthEnabledMiddleware::class;
if (class_exists($settingsAdminMiddleware)) {
    $middleware[] = $settingsAdminMiddleware;
}

// EN: Performance middleware is not even registered in production; the E2E
//     runner enables it only with a safe target file.
//
// HR: Performance middleware u produkciji nije ni registriran; E2E runner ga
//     uključuje samo sigurnom ciljnom datotekom.
if (trim((string)getenv('HPH_REQUEST_LOG')) !== '') {
    // EN: Keep the framework's security/error pair adjacent and outermost;
    // metrics must not bypass headers on an error response.
    //
    // HR: Frameworkov security/error par ostaje susjedan i izvana;
    // metrike ne smiju zaobići zaglavlja odgovora pogreške.
    array_splice($middleware, 3, 0, [\App\Performance\RequestMetricsMiddleware::class]);
}

return $middleware;
