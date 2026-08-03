# Routes

Routing in HeartPhrame is the process of mapping an incoming HTTP request
to a specific controller action. The routes for your application are
defined in the `config/routes.php` file.

---

## Defining Routes

HeartPhrame provides two ways to define your routes: a simple array
format for quick definitions and an object-oriented
approach using `Route` and `RouteGroup` classes for more complex configurations.

### Basic Route Definition

You can define a simple route using a plain PHP array with the following
structure:

`[HTTP_METHOD, PATH, HANDLER, NAME, [MIDDLEWARE]]`

- **HTTP_METHOD**: The HTTP verb (e.g., 'GET', 'POST').
- **PATH**: The URL path for the route.
- **HANDLER**: The controller and method to execute.
- **NAME**: A unique name for the route, useful for URL generation.
- **MIDDLEWARE**: An optional array of middleware classes to apply to
this route.

Here is an example from `config/routes.php`:

```php
['GET', '/', HomeController::class . '@index', 'home', [SampleMiddleware::class]],
```

Simbioza's root controller also checks the neutral
`heartphrame.application_homepage_resolver` service. Workspace registers that
service only while the module is enabled. A resolved published page or
ACL-visible Summaries target produces a temporary private redirect to its
canonical Workspace URL; otherwise the built-in sample homepage is rendered.
Summaries targets carry structured `tree` and `options` visibility values, not
an administrator-supplied free-form query string. This keeps Auth and the host
application fully operational without Workspace.

Alternatively, you can use the `Route` class for a more explicit definition:

```php
use HeartPhrame\Routing\Route;
use HeartPhrame\CodeBook\HttpMethodsEnum;

new Route(
    HttpMethodsEnum::GET, // Using an enum for the method
    '/about',
    [HomeController::class, 'about'], // Handler as an array
    'about'
),
```

### Route Handlers

The handler specifies which code to execute when a route is matched.
It can be defined in two ways:

1.  **String syntax**: `'ControllerName::class . '@methodName'`
2.  **Array syntax**: `[ControllerName::class, 'methodName']`

Both are equivalent and resolve to the same controller action.

## Route Groups

Route groups are useful for applying common attributes, such as a path prefix
or middleware, to multiple routes.
You can create a group using the `RouteGroup` class.

```php
use HeartPhrame\Routing\Route;
use HeartPhrame\Routing\RouteGroup;
use HeartPhrame\Routing\RouteGroupProperties;

new RouteGroup(
    new RouteGroupProperties(
        '/admin', // Path prefix
        'admin.', // Name prefix
        [SampleMiddleware::class] // Common middleware
    ),
    new Route(
        HttpMethodsEnum::GET,
        '/dashboard',
        [AdminController::class, 'dashboard'],
        'dashboard' // Resulting name: 'admin.dashboard'
    ),
    new Route(
        HttpMethodsEnum::GET,
        '/users',
        [AdminController::class, 'users'],
        'users' // Resulting name: 'admin.users'
    ),
);
```

In this example, all routes within the group will have their paths prefixed
with `/admin`, their names prefixed with `admin.`, and the
`SampleMiddleware` applied.

## Route Parameters

You can define routes that capture dynamic segments from the URL, such as a
user ID. These are known as route parameters.

### Defining a Parameter

Parameters are defined by wrapping a segment of the path in curly braces `{}`.

```php
// config/routes.php
['GET', '/users/{id}', 'UserController@show', 'users.show']
```

This route would match URLs like `/users/1`, `/users/42`, etc.

### Accessing Parameters in the Controller

The value of the captured segment is passed as an argument to your controller
method. The name of the method argument **must** match the name of the
parameter in the route definition.

```php
// src/Controllers/UserController.php
namespace App\Controllers;

class UserController
{
    public function show(string $id): ResponseInterface
    {
        // The $id variable will contain the value from the URL.
        // For a request to /users/123, $id will be '123'.

        // ... find user by ID and return a response ...
    }
}
```

You can also use type-hinting (e.g., `int $id`) in your controller method, and
the framework will attempt to automatically convert the parameter to the
specified type.
