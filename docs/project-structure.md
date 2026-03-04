
# Project structure

This document explains how the project is organized. It focuses on the
high‑level structure, not implementation details.

---

## Top‑level layout

At the repository root you’ll find:

### Application code (`src/`)

Application source code (e.g., controllers and other app logic).

- Common substructure:
  - Controllers/ — Request handlers (actions) that interact with services
  and return responses.

As your project grows, you can add additional layers such as `Domain/`,
`Services/`, `Repositories/`, etc., to keep responsibilities clear.


### Configuration (`config/`)

Centralized application configuration (app settings, environment, routes,
middleware, DI services, bootstrap code). For more information,
see [Configuration](configuration.md).


### Runtime data (`data/`)

Directory for runtime data. By default, it contains:

- logs/ — Application logs. Ensure this directory is writable by the web
server user.
- cache/ — Cache storage. Also, it must be writable.

You are free to choose a different location for these directories
(see [Configuration](configuration.md) on how to override the default
locations).

Ensure this directory is writable by the web server user. Do not commit
runtime artifacts to version control.


### Build artifacts (`build/`)

Used by various development tools (e.g., Composer, static analyzers, testing
tools) to cache temporary data, store reports, or manage other
build-related artifacts. This directory is not typically
committed to version control.


### Documentation (`docs/`)

Project documentation.


### Web root (`public/`)

Web root containing the front controller (the entry PHP script). Your web
server must point its document root here.

- This directory is the only one exposed to the web.
- It contains any static assets you choose to serve.

Requests are routed to the front controller, which bootstraps the app,
runs middleware, resolves routes, and emits responses.


###  Tests (`tests/`)

- Contains automated tests.
- Use the provided test runner configuration at the root.
- Recommended workflow:
  - run tests locally before committing changes.
  - run tests in CI to validate changes.


### Composer vendor directory (`vendor/`)

Composer packages and autoloading classes. Not included in VCS.


### Views (`views/`)

Contains templates and layouts which are used to render HTML responses.

Typical structure:
- layouts/ — Base layouts shared across multiple pages.
- feature‑specific folders — Grouped by feature or controller for clarity.

Note that you can change the default path for views in the `config/app.php`
configuration file. See [Views](views.md) for more information.


###  Other files

- Tooling and meta files — Dependency, testing, static analysis, coding
standards, and refactoring configs (for example, `composer.json`,
`phpunit.xml`, `phpstan.neon`, `rector.php`, `phpcs.xml`, `README.md`).
