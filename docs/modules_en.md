# Modules

## Overview

HeartPhrame features a powerful module system that allows you to organize your
application into self-contained, reusable components. A module is essentially
a Composer package that can bundle its own routes, services, controllers,
views, and other functionalities. This is the recommended way to build
large applications or to package features that can be shared across
different projects.

---

## Creating a Module

Creating a module involves two main steps: setting up a Composer package
and creating a manifest file.

### 1. Composer Package Setup

A module is a Composer package with the special `type` of `heartphrame-module`.
You would typically develop a module as a separate project with its
own `composer.json` file.

Here is an example `composer.json` for a new module:

```json
{
    "name": "my-vendor/my-module",
    "description": "A sample module for HeartPhrame.",
    "type": "heartphrame-module",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "MyVendor\\MyModule\\": "src/"
        }
    },
    "require": {
        "php": ">=8.2"
    }
}
```

### 2. The Module Manifest

To expose module functionalities to the framework, each module must contain a
`heartphrame-manifest.php` file in its root directory. This file is the
entry point for the module and tells the application what services,
routes, and other functionalities the module provides.

The manifest file must return an instance of a class that implements
the `HeartPhrame\Module\ModuleManifestInterface`. The easiest way to
do this is to extend the `HeartPhrame\Module\AbstractModuleManifest`
base class.

Here is an example `heartphrame-manifest.php` in which we define a
single module route:

```php
<?php

declare(strict_types=1);

use HeartPhrame\Module\AbstractModuleManifest;
use MyVendor\MyModule\Controllers\DemoController;
use MyVendor\MyModule\Services\DemoService;

return new class extends AbstractModuleManifest
{
    /**
     * @inheritDoc
     */
    public function getModuleRoutes(): array
    {
        return [
            ['GET', '/demo', DemoController::class . '@index', 'demo.index'],
        ];
    }
}
```

> **Note:** Check the ModuleManifestInterface for a full list of
available methods.

---

## Installing and Enabling a Module

1.  **Install with Composer**: From your main application root, install
the module package:
    ```shell
    composer require my-vendor/my-module
    ```

2.  **Enable in Configuration**: Enable the module in your application's
`config/app.php` file.

    ```php
    // config/app.php
    'modules' => [
        // List of loadable module types (usually keep the default)
        'loadable_types' => [
            'heartphrame-module',
        ],
        // Add your module's package name to the list of enabled modules
        'enabled' => [
            'my-vendor/my-module', // Your new module
        ],
    ],
    ```

## How Modules are Loaded

During the application's bootstrap process, the `ModuleBootstrapper`
service iterates through all enabled modules. For each module, it
locates the `heartphrame-manifest.php` file, loads it, and
calls its methods to register the provided routes, and other
configurations with the main application.

This allows modules to seamlessly extend the application's functionality.

The module manifest also has the following methods that can be used to
conditionally load the module based on certain criteria
(e.g., environment, configuration settings, etc.).
  - `canLoad()` - called at app initialization time. If it returns `false`,
  the module will be skipped during the loading process.
  - `requiresDeferredLoading()` - can be used to indicate that the module
  requires request-time (post-session) context to determine if it should load.
  Returns `false` by default.
  - `canLoadForRequest()` - Called by `DeferredModuleLoaderMiddleware` only
  when `requiresDeferredLoading()` returns true. This method can contain
  logic to determine if the module should be loaded for the current request.
  - `canLoadForCli()` - Called by `App::loadCommands()` (CLI context) when
  `requiresDeferredLoading()` is true. There is no HTTP request or session,
  so this method allows the module to explicitly opt in or out of CLI loading.
  Defaults to true.

By default, in `AbstractModuleManifest` those methods return default values
which will enable the module to be loaded. You can override those methods
in your manifest to implement custom logic for determining whether the module
should be loaded or not.

## Managing Simbioza bundled modules

A Simbioza release contains only the required core. An optional module's code
is installed only when needed. An administrator does not edit `config/app.php`
manually, but uses the CLI or **Settings → Setup and modules**; both preserve
module order, check dependencies, and maintain the private
`config/modules.php` file:

```bash
vendor/bin/hph modules list
vendor/bin/hph modules enable calendar
vendor/bin/hph modules disable calendar
vendor/bin/hph modules remove calendar --yes
vendor/bin/hph modules backups calendar
vendor/bin/hph modules add calendar --restore
vendor/bin/hph modules add calendar --fresh
```

ORM, Menu, Auth, Notification, HTML Editor, Workspace, Workspace Search, and
Simbioza User are required. API, Task, Theme, Audit, E-mail, Comment, Calendar,
Confluence Import, and Backup are optional. Theme is the only recommended
module and is preselected for a new installation, but the user may deselect it.

`disable` only stops loading a module: its schema and data remain intact, so it
can be enabled again immediately. `remove` is available only for optional
modules. Before removing their migrations and tables, the command writes a
private NDJSON backup under `data/module-backups/<module>/`, then removes the
Composer package. Its routes, services, and schema are no longer used.

GUI package installation and removal are available only when diagnostics
confirm the dedicated PHP-FPM pool, restricted helper, and correct
permissions. Without them, the GUI can still enable or disable an installed
module and shows the exact CLI command for installing or removing a package.

On a later `add`, the command detects the newest backup. An interactive terminal
asks whether to restore it or start fresh; automation must choose explicitly
with `--restore` or `--fresh`. Restoration is transactional and refuses a
non-empty target table. A module cannot be disabled while another enabled
module depends on it, and required modules cannot be disabled or removed.

Theme owns no business tables. Disabling it retains theme files and the
application continues with its base views. Confluence Import is not required to
render completed imports: Workspace and HTML Editor retain standalone content
and durable internal links.
Before `remove confluence-import`, the command additionally scans every HTML
version in the database and on the filesystem. Removal stops if a legacy
temporary import proxy or dynamic source-workspace reference is found, so an
operator cannot accidentally remove a module that any imported page version
still needs.
