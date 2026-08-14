# Common Services

HeartPhrame provides a number of common services that are managed by
its Dependency Injection (DI) container.

## Using Services

For any service you wish to use, you can type-hint its interface or class in
your class constructor or method, and the container will automatically
inject the correct implementation.

The following example shows how to inject the `LoggerInterface` into
a controller's constructor and the `ServerRequestInterface` into
an action method.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }
    
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->debug('Request data: ', $request->getParsedBody() ?? []);
        // ... return a response
    }
}
```

---

## Default Services

Below is a list of the default services available in the container,
grouped by category.

### PSR-Compliant Services

These services are implementations of common
[PHP-FIG PSR standards](https://www.php-fig.org/psr/).

#### PSR-3 Logger

Inject `Psr\Log\LoggerInterface` to log messages. By default, logs are
written to the file defined in your `config/app.php` file.

```php
/** @var \Psr\Log\LoggerInterface $logger */
$logger->warning('Calendar worker retry scheduled.', [
    'module' => 'calendar',
    'event_uuid' => $eventUuid,
    'attempt' => $attempt,
    'exception' => $exception,
]);
```

Use PSR-3 for diagnostic information, unexpected failures, worker retries, and
operational context. Always supply a stable `module` channel and structured,
non-sensitive identifiers. Never log passwords, tokens, cookies, request or
response bodies, document contents, e-mail bodies, or uploaded file contents.
The rotating handler redacts common credential forms as a final safety net.

Business actions belong in `AuditLogService` or a neutral domain event handled
by Audit. That append-only database record is searchable and optionally
portable through Backup. Technical log files are deliberately excluded from
all backups.

#### PSR-7 Server Request

Inject `Psr\Http\Message\ServerRequestInterface` to get information
about the current HTTP request.

```php
/** @var \Psr\Http\Message\ServerRequestInterface $request */
$queryParams = $request->getQueryParams();
```

#### PSR-14 Event Dispatcher

Inject `Psr\EventDispatcher\EventDispatcherInterface` to dispatch events,
allowing for decoupled communication between different parts of
your application.

```php
// An event is a simple object that holds data
class UserRegisteredEvent { ... }
$event = new UserRegisteredEvent($userId);

/** @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher */
$eventDispatcher->dispatch($event);
```

#### PSR-16 Cache

Inject `Psr\SimpleCache\CacheInterface` for caching operations. The default
implementation uses a file-based cache.

```php
/** @var \Psr\SimpleCache\CacheInterface $cache */
if ($cache->has('products-list')) {
    return $cache->get('products-list');
}
```

### Framework Services

These services provide core framework functionalities.

#### Configuration

Inject `HeartPhrame\Config\ConfigInterface` to access configuration
values from your `config/` files.

```php
/** @var \HeartPhrame\Config\ConfigInterface $config */
// Get a value from config/app.php, with a default
$appName = $config->get('app.name', 'My App');

// Get a required, non-empty string from config/env.php
$logLevel = $config->getAsNonEmptyStringOrFail('env.log_level');
```

#### Response Factory

Inject `HeartPhrame\Http\ResponseFactory` to create `ResponseInterface`
instances. This is the standard way to create responses in your controllers.

```php
/** @var \HeartPhrame\Http\ResponseFactory $responseFactory */

// Create an HTML response from a view template
$response = $responseFactory->view('home/index', ['foo' => 'bar']);

// Create a JSON response
$response = $responseFactory->json(['data' => 'hello']);

// Create a redirect response
$response = $responseFactory->redirect('/login');
```

#### URL Generator

Inject `HeartPhrame\Routing\UrlGenerator` to generate paths and
full URLs for your named routes.

```php
/** @var \HeartPhrame\Routing\UrlGenerator $urlGenerator */

// Get the path for a route named 'home'
$path = $urlGenerator->getPathFor('home'); // -> "/"

// Get the full URL for a route with parameters
$url = $urlGenerator->getUrlFor('users.profile', ['id' => 123]); // -> "http://.../users/123"
```

#### Session

Inject `HeartPhrame\Session\SessionInterface` to read and write
data to the user's session.

```php
/** @var \HeartPhrame\Session\SessionInterface $session */
$session->set('user_id', 123);
$userId = $session->get('user_id');
```

#### Authentication Handler

Inject `HeartPhrame\Authn\AuthnHandlerInterface` to manage user authentication.

```php
/** @var \HeartPhrame\Authn\AuthnHandlerInterface $authn */
if ($authn->isAuthenticated()) {
    $user = $authn->getUser();
}
```

#### Encryption

Inject `HeartPhrame\Encryption\EncryptionInterface` to encrypt and decrypt
data using the key from your environment configuration.

```php
/** @var \HeartPhrame\Encryption\EncryptionInterface $encryption */
$encrypted = $encryption->encrypt('my-secret-data');
$decrypted = $encryption->decrypt($encrypted);
```

#### View Renderer

Inject `HeartPhrame\View\View` to render a view template to a string.
This is a lower-level service.

```php
/** @var \HeartPhrame\View\View $view */
$htmlContent = $view->for('emails/welcome', ['name' => 'Alex']);
```
> **Note**: For returning HTML from a controller, it's usually easier to use
> `ResponseFactory::view()`, which handles creating the
> `Response` object for you.

#### Alert Handler

Inject `HeartPhrame\Alert\AlertHandler` to manage flash messages that will
be displayed to the user on the next request.

```php
/** @var \HeartPhrame\Alert\AlertHandler $alertHandler */
$alertHandler->add(new Alert('Profile updated successfully!', AlertLevelEnum::Success));
```

#### Old Request Data Handler

Inject `HeartPhrame\Validator\OldRequestDataHandler` to preserve user input
across requests, typically after a form submission fails validation.

```php
// After validation fails in a controller action:
/** @var \HeartPhrame\Validator\OldRequestDataHandler $oldRequestDataHandler */
$oldRequestDataHandler->addOldData($request->getParsedBody());

// In the view, you can retrieve the old data to repopulate the form.
```

### Custom Services

Any class that the container can instantiate can be used as a service.
If your class has simple dependencies that are also known to the container,
you can type-hint it in a constructor or method, and the container will
build it for you automatically without any extra configuration.
