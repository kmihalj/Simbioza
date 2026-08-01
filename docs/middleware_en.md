# Middleware

Middleware is a powerful feature for filtering and processing HTTP requests
that enter your application. They are processed as a stack, with each
layer having the ability to modify the request before passing it to
the next layer, or to modify the response returned from the inner layers.

HeartPhrame middleware follows the
[PSR-15](https://www.php-fig.org/psr/psr-15/) standard.

---

## Creating Middleware

Middleware is a class that implements the
`Psr\Http\Server\MiddlewareInterface`. This interface has a single method,
`process()`, which receives the current request and a request handler.
The middleware can perform its logic and then either pass the request to
the handler to continue the chain or return a response itself to stop the chain.

Here is an example of simple logging middleware:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->info(sprintf('Request: %s %s', $request->getMethod(), $request->getUri()->getPath()));

        // Pass the request to the next middleware in the chain
        $response = $handler->handle($request);

        $this->logger->info(sprintf('Response: %s', $response->getStatusCode()));

        return $response;
    }
}
```

---

## Registering Middleware

### Global Middleware

Global middleware runs on every single request to your application.
You can register global middleware by adding its
class name to the array in the `config/middleware.php` file.

```php
// config/middleware.php
return [
    \HeartPhrame\Middleware\TrustedProxyMiddleware::class,
    \HeartPhrame\Middleware\StartSessionMiddleware::class,
    \HeartPhrame\Middleware\CheckCsrfMiddleware::class,
    \HeartPhrame\Middleware\DeferredModuleLoaderMiddleware::class,
    
    // Add your custom global middleware here
    \App\Middleware\LoggingMiddleware::class,
];
```

The order of middleware in this array is important, as they will
be processed sequentially.

### Route-Specific Middleware

You can also apply middleware to specific routes or route groups. This is
done in your `config/routes.php` file by
passing an array of middleware classes in the route definition.

**On a single route:**

```php
// config/routes.php
['GET', '/profile', ProfileController::class . '@show', 'profile.show', [AuthMiddleware::class]],
```

**On a route group:**

```php
// config/routes.php
new RouteGroup(
    new \HeartPhrame\Routing\RouteGroupProperties(
        '/admin',
        'admin.',
        [AuthMiddleware::class, AdminMiddleware::class] // Applied to all routes in the group
    ),
    // ... routes in this group
);
```
