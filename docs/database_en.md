# Database configuration

Database-backed modules use `heartphrame-module-orm`; Theme and Menu do not
need ORM or a database. Simbioza is verified on SQLite, PostgreSQL, MySQL, and
MariaDB-compatible connections.

## Local file

Copy the ignored template before editing it:

```bash
cp config/database.php.dist config/database.php
```

Never commit `config/database.php` or print production credentials in CI logs.
Prefer deployment secrets or environment expansion in production.

## SQLite

SQLite is the easiest clean installation and requires no server:

```php
<?php

declare(strict_types=1);

return [
    'connections' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/data/app.sqlite',
        ],
    ],
];
```

Create `data/` and make it writable by PHP. SQLite is suitable for development,
tests, and smaller single-server installations; benchmark real concurrency
before choosing it for a write-heavy deployment.

## PostgreSQL

Use `UTF8`, not MySQL's `utf8mb4` name:

```php
return [
    'connections' => [
        'default' => [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'heartphrame',
            'username' => 'heartphrame_app',
            'password' => getenv('HPH_DB_PASSWORD') ?: '',
            'charset' => 'UTF8',
            'options' => [],
        ],
    ],
];
```

MySQL-specific `utf8*` collation hints in portable module migrations are mapped
to PostgreSQL's `default` collation. An explicit PostgreSQL column collation is
still supported by ORM.

## MySQL or MariaDB

```php
return [
    'connections' => [
        'default' => [
            'driver' => 'mysql', // `mariadb` is accepted as an alias.
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'heartphrame',
            'username' => 'heartphrame_app',
            'password' => getenv('HPH_DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'options' => [],
        ],
    ],
];
```

Create the database with `utf8mb4` and grant the application user rights only
on that database. Do not run the web application as MySQL `root`.

## Migration workflow

```bash
vendor/bin/hph orm-migrate:status --connection=default
vendor/bin/hph orm-migrate:up --connection=default
vendor/bin/hph orm-migrate:status --connection=default
```

`status` must show zero pending migrations after a successful run. A failed
migration must be diagnosed before retrying; do not manually mark it complete.
Use `orm-migrate:rollback --step=1` only when the migration implements a safe
down operation and rollback is the intended recovery.

## Cross-database verification

The clean-room tool accepts an already-created, empty test database:

```bash
HPH_MATRIX_DB_HOST=127.0.0.1 \
HPH_MATRIX_DB_PORT=5432 \
HPH_MATRIX_DB_NAME=heartphrame_matrix \
HPH_MATRIX_DB_USER=heartphrame_matrix \
HPH_MATRIX_DB_PASSWORD='local-test-secret' \
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

Supported values are `sqlite`, `pgsql`, `mysql`, and `mariadb`. Use a dedicated
empty database because the test installs the complete schema. The JSON report
contains the driver and results but never the credential environment variables.

CI provisions disposable PostgreSQL and MySQL service databases and runs the
complete-module candidate against both. It also executes the complete browser,
API, and performance E2E suite on a separate empty database for each network
driver. SQLite CI additionally runs every minimal module case, including
Framework-only, Theme-only, Menu-only, and Theme plus Menu.
