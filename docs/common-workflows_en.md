# Common Workflows

This document outlines the typical steps for common development tasks in
a HeartPhrame application.

---

## The Request-to-Response Workflow

A frequent task is adding a new page or API endpoint. This typically involves
creating a route, a controller action to handle the request, and a view
to render the response.

### 1. Add a Route

First, define a new route in your route configuration file (usually
`config/routes.php`). A route maps an HTTP method and URL path to a
specific controller action. You can also assign a name and attach
middleware here.

Check the existing examples in the `config/routes.php` file for reference.
For more details, see [Routes](routes_en.md).

### 2. Create a Controller Action

Next, create the controller method that the route points to. Controllers
are located in `src/Controllers/`. The action method will contain the
logic for handling the request and must return a
`Psr\Http\Message\ResponseInterface` instance.

Check the existing examples in the `src/Controllers` folder for reference.

### 3. Create and Render a View

For HTML responses, you'll typically render a view. Create a new template
file under the `views/` directory. You can use layouts for a shared page
structure (e.g., headers and footers).

From your controller action, render the view and return it as a response.

For more information on views, see [Views](views_en.md).

---

## Other Common Tasks

### Registering a Service

To add a new service to the Dependency Injection (DI) container, you need
to register it in the `config/services.php` configuration file. Bind the
implementation to an interface or class name, and the container will
be able to inject it automatically wherever it's necessary.

For more information, see the `services.php` section in the
[Configuration](configuration_en.md) documentation.

### Adjusting Middleware

Middleware can be applied globally to all requests or attached to specific
routes or route groups. You can add or remove global middleware in
`config/middleware.php` or attach it per-route in your `config/routes.php`
file.
