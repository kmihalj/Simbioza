# Simbioza

[Hrvatska verzija](README_hr.md)

> Knowledge that lives together.

Simbioza is the collaborative knowledge application built on the HeartPhrame Framework and its
independently maintained modules. Application work belongs here and in module
repositories; the Framework is consumed from its tagged `v0.0.24` release and is
not developed as part of this repository.

## Dependencies

Every HeartPhrame module requires `aaieduhr/heartphrame-framework:^0.0.24`.
Simbioza currently integrates ORM, Menu, Theme, Auth, E-mail, Notification,
HTML Editor, Task, Comment, Workspace, Workspace Search, Calendar, API, Backup,
Audit, and Simbioza User. Required module order
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

The Framework is constrained to `^0.0.24`, while Simbioza modules use the
compatible `^0.1.0` release line. This application does not commit
`composer.lock`; each CI run resolves the latest compatible tagged releases and
executes the complete quality suite. Production deployments may retain their
own verified lock file outside the source repository.

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

A deployed release may intentionally omit `.git` and retain its own verified
`composer.lock`. From release `0.1.9` onward, check and install the newest stable
application and compatible module tags from the installation root with:

```bash
sudo php update.php --check
sudo php update.php
```

The complete release-install and update procedure is documented in
[Installing Simbioza](docs/installation_en.md#11-updating-a-release-installation).

Application configuration, migrations, module order, and API integration are
described in the [English documentation](docs/index_en.md). The Croatian
documentation has a separate [Croatian index](docs/index_hr.md).

## Documentation

- Main index (EN): [docs/index_en.md](docs/index_en.md)
- Main index (HR): [docs/index_hr.md](docs/index_hr.md)
- [Installation](docs/installation_en.md)
- [Six clean installations and screenshots](docs/installation-lab_en.md)
- [Module dependencies](docs/module-dependencies_en.md)
- [Database configuration](docs/database_en.md)
- [API v1 contract](docs/api-v1-contract_en.md)
- [End-to-end testing](docs/end-to-end-testing_en.md)
- [Brand identity and theme](docs/branding_en.md)

The E2E suite includes non-sensitive ORM and HTTP measurements plus durable
budgets for SQL count, request duration, peak memory, and response size. The
same complete suite runs on SQLite, PostgreSQL, and MySQL in CI.

Workspace maintenance optimizes existing images as a persistent, resumable job
with a visible progress bar. Images are processed in bounded batches so a large
site never holds one HTTP request open for the entire collection; source files
remain unchanged.

## Enabled modules

Simbioza integrates API, Audit, Auth, Backup, Calendar, Comment, HTML Editor,
E-mail, Menu, Notification, ORM, Simbioza User, Task, Theme, Workspace, and Workspace Search. Simbioza User adds
following, notification delivery rules, and restricted personal Workspaces created
at first sign-in under an administrator-controlled policy. Modules keep ownership of their
domain rules; the application composes them and supplies deployment settings.

## Licence

This work is published under the
[European Union Public Licence (EUPL) v1.2](LICENSE).
