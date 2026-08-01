# Database configuration / Konfiguracija baze

[English](#english) | [Hrvatski](#hrvatski)

## English

Database-backed modules use `heartphrame-module-orm`; Theme and Menu do not
need ORM or a database. HFClean is verified on SQLite, PostgreSQL, MySQL, and
MariaDB-compatible connections.

### Local file

Copy the ignored template before editing it:

```bash
cp config/database.php.dist config/database.php
```

Never commit `config/database.php` or print production credentials in CI logs.
Prefer deployment secrets/environment expansion in production.

### SQLite

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

### PostgreSQL

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

### MySQL or MariaDB

```php
return [
    'connections' => [
        'default' => [
            'driver' => 'mysql', // `mariadb` is accepted as an alias
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

### Migration workflow

```bash
vendor/bin/hph orm-migrate:status --connection=default
vendor/bin/hph orm-migrate:up --connection=default
vendor/bin/hph orm-migrate:status --connection=default
```

`status` must show zero pending migrations after a successful run. A failed
migration must be diagnosed before retrying; do not manually mark it complete.
Use `orm-migrate:rollback --step=1` only when the migration implements a safe
down operation and rollback is the intended recovery.

### Cross-database verification

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
contains driver and results but never the credential environment variables.

## Hrvatski

Moduli koji spremaju podatke koriste `heartphrame-module-orm`; Theme i Menu ne
trebaju ORM ni bazu. HFClean je provjeren na SQLite, PostgreSQL, MySQL i
MariaDB-kompatibilnim konekcijama.

### Lokalna datoteka

Prije uređivanja kopiraj ignorirani predložak:

```bash
cp config/database.php.dist config/database.php
```

Nikada ne spremaj `config/database.php` u Git i ne ispisuj produkcijske
pristupne podatke u CI log. U produkciji koristi deployment tajne/varijable
okoliša.

### SQLite

SQLite je najjednostavnija čista instalacija i ne treba poslužitelj:

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

Kreiraj `data/` i dopusti PHP procesu pisanje. SQLite je prikladan za razvoj,
testove i manje instalacije na jednom poslužitelju; prije write-heavy produkcije
izmjeri stvarnu konkurentnost.

### PostgreSQL

Koristi `UTF8`, a ne MySQL naziv `utf8mb4`:

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

ORM u prijenosnim migracijama mapira MySQL `utf8*` collation hint na
PostgreSQL `default`. Eksplicitni PostgreSQL collation pojedinog stupca i dalje
je podržan.

### MySQL ili MariaDB

```php
return [
    'connections' => [
        'default' => [
            'driver' => 'mysql', // prihvaća se i alias `mariadb`
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

Bazu kreiraj s `utf8mb4`, a aplikacijskom korisniku dodijeli prava samo nad tom
bazom. Web aplikaciju ne pokreći kao MySQL `root`.

### Tijek migracija

```bash
vendor/bin/hph orm-migrate:status --connection=default
vendor/bin/hph orm-migrate:up --connection=default
vendor/bin/hph orm-migrate:status --connection=default
```

Nakon uspjeha `status` mora pokazati nula pending migracija. Neuspjelu migraciju
prvo dijagnosticiraj; nemoj je ručno označiti završenom. Naredbu
`orm-migrate:rollback --step=1` koristi samo kada migracija ima siguran `down` i
rollback je planirani oporavak.

### Provjera više baza

Clean-room alat prima već kreiranu praznu testnu bazu:

```bash
HPH_MATRIX_DB_HOST=127.0.0.1 \
HPH_MATRIX_DB_PORT=5432 \
HPH_MATRIX_DB_NAME=heartphrame_matrix \
HPH_MATRIX_DB_USER=heartphrame_matrix \
HPH_MATRIX_DB_PASSWORD='lokalna-testna-tajna' \
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

Podržane vrijednosti su `sqlite`, `pgsql`, `mysql` i `mariadb`. Koristi zasebnu
praznu bazu jer test instalira cijelu shemu. JSON izvještaj sadrži driver i
rezultate, ali nikada pristupne varijable okoliša.
