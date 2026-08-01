# Middleware

Middleware filtrira i obrađuje HTTP zahtjeve koji ulaze u aplikaciju. Izvršava
se kao stog u kojem svaki sloj može promijeniti zahtjev prije prosljeđivanja
sljedećem sloju ili promijeniti odgovor koji vraćaju unutarnji slojevi.

HeartPhrame middleware slijedi standard
[PSR-15](https://www.php-fig.org/psr/psr-15/).

---

## Izrada middlewarea

Middleware je klasa koja implementira `Psr\Http\Server\MiddlewareInterface`.
Sučelje ima jednu metodu `process()`, koja prima trenutačni zahtjev i obrađivač
zahtjeva. Middleware može izvršiti logiku i zatim proslijediti zahtjev
obrađivaču ili sam vratiti odgovor i prekinuti lanac.

Primjer jednostavnog middlewarea za zapisivanje:

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
        $this->logger->info(sprintf('Zahtjev: %s %s', $request->getMethod(), $request->getUri()->getPath()));

        // Proslijedi zahtjev sljedećem middlewareu u lancu.
        $response = $handler->handle($request);

        $this->logger->info(sprintf('Odgovor: %s', $response->getStatusCode()));

        return $response;
    }
}
```

---

## Registriranje middlewarea

### Globalni middleware

Globalni middleware izvršava se za svaki zahtjev. Registrira se dodavanjem
naziva klase u polje datoteke `config/middleware.php`.

```php
// config/middleware.php
return [
    \HeartPhrame\Middleware\TrustedProxyMiddleware::class,
    \HeartPhrame\Middleware\StartSessionMiddleware::class,
    \HeartPhrame\Middleware\CheckCsrfMiddleware::class,
    \HeartPhrame\Middleware\DeferredModuleLoaderMiddleware::class,

    // Ovdje dodajte vlastiti globalni middleware.
    \App\Middleware\LoggingMiddleware::class,
];
```

Redoslijed je važan jer se middleware izvršava slijedno.

### Middleware pojedine rute

Middleware možete primijeniti i na pojedinu rutu ili grupu ruta. U datoteci
`config/routes.php` definiciji rute proslijedite polje klasa middlewarea.

**Pojedinačna ruta:**

```php
// config/routes.php
['GET', '/profile', ProfileController::class . '@show', 'profile.show', [AuthMiddleware::class]],
```

**Grupa ruta:**

```php
// config/routes.php
new RouteGroup(
    new \HeartPhrame\Routing\RouteGroupProperties(
        '/admin',
        'admin.',
        [AuthMiddleware::class, AdminMiddleware::class] // Primjenjuje se na sve rute u grupi.
    ),
    // ... rute u ovoj grupi
);
```
