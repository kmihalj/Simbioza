# Simbioza installation

Simbioza is the collaborative knowledge application for the moving `dev-main` versions of
HeartPhrame Framework and its modules. It is not the upstream Framework and it
does not include demo accounts or sample domain data.

## 1. Requirements

- PHP 8.2 or newer
- Composer 2 and Git access to every private module repository
- PDO SQLite, PostgreSQL, or MySQL/MariaDB driver
- PHP extensions required by enabled modules (`dom`, `fileinfo`, `mbstring`,
  `zip`, and others reported by `composer check-platform-reqs`)

## 2. Install current module heads

```bash
git clone <your-simbioza-repository> Simbioza
cd Simbioza
composer update --with-all-dependencies
composer check-platform-reqs
```

Internal packages intentionally use `dev-main`. The root application therefore
has `"minimum-stability": "dev"` and `"prefer-stable": true`. Simbioza does not
commit `composer.lock`; every CI/deployment resolves and tests the current
heads. Use `composer install` only when your deployment process intentionally
provides a generated lock file.

## 3. Create local configuration

```bash
cp config/database.php.dist config/database.php
cp config/env.php.dist config/env.php
vendor/bin/hph encryption:generate-key
```

Paste the generated key into `config/env.php`, select the database in
`config/database.php`, and never commit either local file. Database examples
are in [database configuration](database_en.md).

Theme and Menu JSON files live below `resources/config/theme/` and
`resources/config/menu/`. Their PHP configuration files remain directly below
`config/`; this avoids a PHP-config-file/directory name collision.

## 4. Migrate an empty database

Simbioza already commits the nine official initial migrations. Inspect and run
them:

```bash
vendor/bin/hph orm-migrate:status
vendor/bin/hph orm-migrate:up
vendor/bin/hph orm-migrate:status
```

Expected result after the second status command: every migration is `[RAN]`
and pending count is `0`. The migrations create schemas and required system
records only; create the first administrator through `/settings/auth`.

In a custom minimal application, copy only migrations for installed modules:

```bash
vendor/bin/hph auth:install-migration
vendor/bin/hph api:install-migration
vendor/bin/hph orm-migrate:up
```

Replace or add scaffold commands according to `vendor/bin/hph`. Do not copy a
module migration when the module is not installed.

## 5. Verify before serving

```bash
composer on-commit
php scripts/audit_bilingual_phpdoc.php
php scripts/verify_clean_install_matrix.php
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

For a full local candidate over PostgreSQL or MySQL, provide an empty test
database through `HPH_MATRIX_DB_*` and run:

```bash
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

After that clean-install check, use a second empty disposable database to run
the complete browser, API, and performance suite with
`php scripts/run_e2e.php --local --database=pgsql` or `--database=mysql`.
The matrix and E2E tools never write database credentials to their reports or
metrics.
The Simbioza CI workflow runs every minimal module combination on SQLite and the
complete module set plus all 52 E2E scenarios on clean PostgreSQL and MySQL
service databases. This keeps Composer resolution, migrations, CLI/HTTP boot,
functional flows, and performance budgets covered on every supported database
family.

## 6. Web server

Point the document root to `Simbioza/public`, make `data/` writable by the PHP
process, and route unknown paths to `public/index.php`. For local development:

```bash
php -S 127.0.0.1:8080 -t public scripts/dev_router.php
```

Open `http://127.0.0.1:8080/`. Production deployments should use PHP-FPM with
Apache/Nginx, HTTPS, secure cookies, a non-development environment, and a
process manager for configured outbox/webhook workers.

The development router serves existing assets directly and sends only unknown
paths through `public/index.php`. See [end-to-end testing](end-to-end-testing_en.md)
for the isolated browser and API workflow.
