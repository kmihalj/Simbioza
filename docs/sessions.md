# Session Management

## Overview

The HeartPhrame framework includes a flexible session management system that
provides an abstraction layer over different session storage implementations.
By default, the framework uses PHP's native session management,
but the architecture allows for easily switching to alternative storage
backends.

## Components

- **SessionInterface**: Defines the core session operations such as
`get()`, `set()`, `remove()`, etc.
- **SessionFactoryInterface**: Manages session creation and configuration.
- **PhpSession**: The default implementation using PHP’s native
`$_SESSION` superglobal.

---

## Configuration

Session behavior is configured in the `config/app.php` file under
the `session` key.

```php
// config/app.php
'session' => [
    'options' => [
        'use_cookies' => 1,
        'cookie_secure' => 1, // Recommended for production
        'cookie_httponly' => 1, // Mitigates XSS
        'cookie_samesite' => 'Lax',
        'name' => 'HEARTPHRAME_SESSION',
        'gc_maxlifetime' => 1440, // 24 minutes
    ],
    'excluded_routes' => [
        // Routes starting with these prefixes will not have sessions started.
        // '/api/public',
    ],
],
```

### Session Options

The `options` array contains settings that are passed directly to PHP's
`session_start()` function. You can configure standard PHP session
settings here.

### Excluding Routes

The `excluded_routes` array allows you to specify URL prefixes for which
the session should not be started. This is useful for public API
endpoints or other routes that don't require a session state.

---

## Usage in Controllers

To work with the session in your controllers, you can inject the
`HeartPhrame\Session\SessionInterface` into your
controller's constructor or action methods. The DI container will
automatically provide the session instance.

Here is an example of how to use the session to manage a shopping cart:

```php
<?php

namespace App\Controllers;

use HeartPhrame\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use HeartPhrame\Http\ResponseFactory;

class CartController
{
    public function __construct(
        protected readonly SessionInterface $session,
        protected readonly ResponseFactory $responseFactory,
    )
    {
    }

    public function addItem(): ResponseInterface
    {
        // Get an existing cart or initialize a new one
        $cart = $this->session->get('cart', []);

        // Add a new item
        $newItem = ['id' => 123, 'name' => 'Sample Product'];
        $cart[] = $newItem;

        // Save the updated cart back to the session
        $this->session->set('cart', $cart);
        
        return $this->responseFactory->redirect('/cart/view');
    }

    public function view(): ResponseInterface
    {
        $cart = $this->session->get('cart', []);

        return $this->responseFactory->view('cart/view', ['items' => $cart]);
    }

    public function clear(): ResponseInterface
    {
        // Remove the cart from the session
        $this->session->remove('cart');

        return $this->responseFactory->redirect('/cart/view');
    }
}
```

## Session Middleware

The `HeartPhrame\Middleware\StartSessionMiddleware` is registered
globally in `config/middleware.php` and is responsible for starting
the session at the beginning of a request and persisting its data at the end.
It runs for every request unless the route is excluded in the configuration.
