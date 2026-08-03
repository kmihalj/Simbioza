# Simbioza

[Hrvatska verzija](README_hr.md)

> Knowledge that lives together.

Simbioza is the collaborative knowledge application built on the HeartPhrame Framework and its
independently maintained modules. Application work belongs here and in module
repositories; the Framework is consumed from its upstream `main` branch and is
not developed as part of this repository.

## Dependencies

Every HeartPhrame module requires `aaieduhr/heartphrame-framework:dev-main`.
Simbioza currently integrates ORM, Menu, Theme, Auth, E-mail, Notification,
HTML Editor, Task, Comment, Workspace, Calendar, and API. Required module order
and optional capabilities are listed in
[the dependency matrix](docs/module-dependencies_en.md).

The smallest verified installations are Framework only, Framework + Theme,
Framework + Menu, and Framework + Theme + Menu. Database-backed modules add ORM
and their documented domain dependencies. Composer resolves all transitive
dependencies automatically.

## Requirements

- PHP 8.2 or newer
- Composer 2
- PDO SQLite for the default local setup
- Git access to the listed module repositories

## Dependency policy

The Framework and every internal HeartPhrame module are intentionally required
from the moving `dev-main` branch. Fixed aliases and internal version ranges are
not used. This application also does not commit `composer.lock`; each CI run and
deployment resolves the latest development heads and executes the complete
quality suite.

Committed Composer metadata uses VCS repositories so a clean CI checkout works
without sibling directories. For local work with symlinked module checkouts,
use an untracked `composer.local.json` through the `COMPOSER` environment
variable; do not commit local `path` repositories into the shared manifest.

## Installation and verification

```bash
composer update --with-all-dependencies
composer check-platform-reqs
composer on-commit
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Application configuration, migrations, module order, and API integration are
described in the [English documentation](docs/index_en.md). The Croatian
documentation has a separate [Croatian index](docs/index_hr.md).

## Documentation

- Main index (EN): [docs/index_en.md](docs/index_en.md)
- Main index (HR): [docs/index_hr.md](docs/index_hr.md)
- [Installation](docs/installation_en.md)
- [Module dependencies](docs/module-dependencies_en.md)
- [Database configuration](docs/database_en.md)
- [API v1 contract](docs/api-v1-contract_en.md)
- [End-to-end testing](docs/end-to-end-testing_en.md)
- [Brand identity and theme](docs/branding_en.md)

The E2E suite includes non-sensitive ORM and HTTP measurements plus durable
budgets for SQL count, request duration, peak memory, and response size. The
same complete suite runs on SQLite, PostgreSQL, and MySQL in CI.

## Enabled modules

Simbioza integrates API, Auth, Calendar, Comment, HTML Editor, E-mail, Menu,
Notification, ORM, Task, Theme, and Workspace. Modules keep ownership of their
domain rules; the application composes them and supplies deployment settings.

## Licence

This work is published under the
[European Union Public Licence (EUPL) v1.2](LICENSE).
