<?php

declare(strict_types=1);

// Global middleware for all routes.
$middleware = [
    \HeartPhrame\Middleware\TrustedProxyMiddleware::class,
    \HeartPhrame\Middleware\StartSessionMiddleware::class,
    \HeartPhrame\Middleware\CheckCsrfMiddleware::class,
    \HeartPhrame\Middleware\DeferredModuleLoaderMiddleware::class,
    // HR: Ponovna settings provjera nakon odgođenih modula primjenjuje Simbioza admin elevaciju.
    // EN: Rechecking settings after deferred modules applies the Simbioza administrator elevation policy.
    \AaiEduHr\HeartPhrameModuleMenu\Middleware\RequireSettingsAdminWhenAuthEnabledMiddleware::class,
];

// HR: Performance middleware u produkciji nije ni registriran; E2E runner ga
//     uključuje samo sigurnom ciljnom datotekom.
// EN: Performance middleware is not even registered in production; the E2E
//     runner enables it only with a safe target file.
if (trim((string)getenv('HPH_REQUEST_LOG')) !== '') {
    array_unshift($middleware, \App\Performance\RequestMetricsMiddleware::class);
}

return $middleware;
