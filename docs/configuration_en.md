# Configuration

This document explains how configuration files are arranged.

## Default location and overriding

By default, the application expects to find its configuration files in the
following location: `config/`. However, you can override this location
by setting the `HPH_CONFIG_PATH` environment variable.

## File structure

Configuration is split into focused files which serve as a namespace for
related settings. By default, the files noted below are present.
Each file is a PHP file that returns an array and that contains default
settings for the corresponding section. Note that you are free to
add additional entries to these files, as needed.


### Global application settings (`app.php`)

- Global settings: application name, timezone, cache folder, logs
(directory/filename), views defaults, session options.
- CSRF configuration (exclusions if needed).
- Module system settings (loadable types and enabled modules).


### Bootstrap functions (`bootstrap.php`)

- Code that runs after the container is built and before handling requests.
- Typical tasks include runtime configuration (error reporting based on
environment), module initialization, global view data, startup logging...
and anything else that needs to run before the request lifecycle begins.


### Commands (`commands.php`)

- App level commands (e.g., to generate a new encryption key that you can
use in `config/env.php`).
- Commands are registered in the container and can be invoked via the
`vendor/bin/hph` CLI.


### Environments (`env.php`, created by copying `env.php.dist`)

By default, environment settings include:
- Environment‑specific values: environment (development/production), log level,
debug flags..
- Security‑sensitive values like encryption keys.
- Trusted proxy addresses (used when the app is behind reverse proxies).

Typical settings:
- Development: more verbose output and developer‑friendly settings.
- Production: strict error handling, no error display, logging enabled.

Keep secrets and environment‑specific values in `env.php` (ignored by VCS),
and not hard‑coded elsewhere.


### Middleware (`middleware.php`)

Global middleware applied to every request (e.g., trusted proxy handling,
session, CSRF checks).


### Routes (`routes.php`)

- Route definitions mapping HTTP methods and paths to handlers and names.
- Supports both concise route arrays and grouped/explicit route objects.
- Route groups allow path/name prefixes and shared middleware.

Check the `config/routes.php` file for different examples of how to define
routes.

### Services (`services.php`)

- Dependency Injection (DI) container definitions.
- Wires core services such as logging, HTTP request factory, event dispatcher,
caching, session handling, authentication, encryption, and module
bootstrapper... and anything else that needs to be injected into your code.

The format of the `services.php` configuration file is an array of
service definitions. The array key is the service name, and the
array value is a callback that returns a service definition. Each
callback receives the container as its only argument.

For example, the following configuration file defines a service named
`MyService` that returns a new instance of `MyService` class:

```php
<?php

declare(strict_types=1);

return [
    MyService::class => fn(ContainerInterface $container): MyService => new MyService(),
];
```

Feel free to add additional services as needed or to override existing
services. The only requirement is that they are defined as callbacks
that return a service definition.

However, keep in mind that you should only define services in `services.php`
if:
* special setup is needed before they can be used
* you want a specific/custom/overridden interface implementation instead
of the default one

For example, if you want to override the default
`Psr\Http\Message\ServerRequestInterface` service, you should
define a service in `services.php` that returns a new instance
of the `Psr\Http\Message\ServerRequestInterface`
class, like from a package that you are using, or similar.

If you have a simple class which you want to use as a service, you
can simply type-hint it and the container will inject it for you.
You don't need to define a service in `config/services.php` for it.
"Simple class" in this context means that the class does not need
any special setup, and all of its dependencies can be automatically
injected by the container (its state is resolvable from the container
out-of-the-box).

### Custom configuration files

You are free to add additional files for your custom configuration as needed.
The only requirement is that they:
- are PHP files located in the `config/` directory (or the directory set by
`HPH_CONFIG_PATH` environment variable),
- return an array.

Name your config files after the configuration section they contain.
The name of the file is used as a namespace for the returned array.

For example, you could have a custom `config/my_config.php` file with the
following content:

```php
<?php

declare(strict_types=1);

return [
    'foo' => 'bar',
];
```

## Using system environment variables as configuration values

Since config files are PHP files, you can use environment variables as
configuration values by utilizing `getenv()` function, in any of
your configuration files.

For example, let's assume that you will have a `HPH_FOO` environment
variable that you want to use as a configuration
value. You can use it in your `config/app.php` file as follows:

```php
<?php

declare(strict_types=1);

return [
    'foo' => is_string($fooVal = getenv('HPH_FOO')) ? $fooVal : 'default-foo-value',
];
```
