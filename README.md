# HFClean HeartPhrame application

[Hrvatska verzija](README_hr.md)

HFClean is the integration application for the HeartPhrame Framework and its
independently maintained modules. Application work belongs here and in module
repositories; the Framework is consumed from its upstream `main` branch and is
not developed as part of this repository.

## Dependencies

Every HeartPhrame module requires `aaieduhr/heartphrame-framework:dev-main`.
HFClean currently integrates ORM, Menu, Theme, Auth, E-mail, Notification,
HTML Editor, Task, Comment, Workspace, Calendar, and API. Required module order
and optional capabilities are listed in
[the dependency matrix](docs/module-dependencies.md).

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
```

Application configuration, migrations, module order, and API integration are
described in the [documentation](docs/index.md). The bilingual dependency
matrix is in [module dependencies](docs/module-dependencies.md).

## Enabled modules

HFClean integrates API, Auth, Calendar, Comment, HTML Editor, E-mail, Menu,
Notification, ORM, Task, Theme, and Workspace. Modules keep ownership of their
domain rules; the application composes them and supplies deployment settings.

## Licence

This work is published under the
[European Union Public Licence (EUPL) v1.2](LICENSE).
