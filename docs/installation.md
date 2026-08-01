# HFClean installation / Instalacija HFCleana

[English](#english) | [Hrvatski](#hrvatski)

## English

HFClean is the integration application for the moving `dev-main` versions of
HeartPhrame Framework and its modules. It is not the upstream Framework and it
does not include demo accounts or sample domain data.

### 1. Requirements

- PHP 8.2 or newer;
- Composer 2 and Git access to every private module repository;
- PDO SQLite, PostgreSQL, or MySQL/MariaDB driver;
- PHP extensions required by enabled modules (`dom`, `fileinfo`, `mbstring`,
  `zip`, and others reported by `composer check-platform-reqs`).

### 2. Install current module heads

```bash
git clone <your-hfclean-repository> HFClean
cd HFClean
composer update --with-all-dependencies
composer check-platform-reqs
```

Internal packages intentionally use `dev-main`. The root application therefore
has `"minimum-stability": "dev"` and `"prefer-stable": true`. HFClean does not
commit `composer.lock`; every CI/deployment resolves and tests the current
heads. Use `composer install` only when your deployment process intentionally
provides a generated lock file.

### 3. Create local configuration

```bash
cp config/database.php.dist config/database.php
cp config/env.php.dist config/env.php
vendor/bin/hph encryption:generate-key
```

Paste the generated key into `config/env.php`, select the database in
`config/database.php`, and never commit either local file. Database examples
are in [database configuration](database.md).

Theme and Menu JSON files live below `resources/config/theme/` and
`resources/config/menu/`. Their PHP configuration files remain directly below
`config/`; this avoids a PHP-config-file/directory name collision.

### 4. Migrate an empty database

HFClean already commits the nine official initial migrations. Inspect and run
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

Replace/add scaffold commands according to `vendor/bin/hph`. Do not copy a
module migration when the module is not installed.

### 5. Verify before serving

```bash
composer on-commit
php scripts/audit_bilingual_phpdoc.php
php scripts/verify_clean_install_matrix.php
```

For a full local candidate over PostgreSQL or MySQL, provide an empty test
database through `HPH_MATRIX_DB_*` and run:

```bash
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

The matrix tool never writes database credentials to its JSON report.

### 6. Web server

Point the document root to `HFClean/public`, make `data/` writable by the PHP
process, and route unknown paths to `public/index.php`. For local development:

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Open `http://127.0.0.1:8080/`. Production deployments should use PHP-FPM with
Apache/Nginx, HTTPS, secure cookies, a non-development environment, and a
process manager for configured outbox/webhook workers.

## Hrvatski

HFClean je integracijska aplikacija za pomične `dev-main` verzije HeartPhrame
Frameworka i modula. Nije uzvodni Framework i ne sadrži demo račune ni probne
domenske podatke.

### 1. Preduvjeti

- PHP 8.2 ili noviji;
- Composer 2 i Git pristup svim privatnim repozitorijima modula;
- PDO driver za SQLite, PostgreSQL ili MySQL/MariaDB;
- PHP ekstenzije uključenih modula (`dom`, `fileinfo`, `mbstring`, `zip` i
  ostale koje prijavi `composer check-platform-reqs`).

### 2. Instaliranje aktualnih stanja modula

```bash
git clone <tvoj-hfclean-repozitorij> HFClean
cd HFClean
composer update --with-all-dependencies
composer check-platform-reqs
```

Interni paketi namjerno koriste `dev-main`, zato aplikacija ima
`"minimum-stability": "dev"` i `"prefer-stable": true`. HFClean ne sprema
`composer.lock`; svaki CI/deployment razrješava i testira aktualna stanja.
`composer install` koristi se samo kada deployment namjerno dobiva generirani
lock file.

### 3. Lokalna konfiguracija

```bash
cp config/database.php.dist config/database.php
cp config/env.php.dist config/env.php
vendor/bin/hph encryption:generate-key
```

Generirani ključ unesi u `config/env.php`, bazu odaberi u
`config/database.php`, a lokalne datoteke nikada ne spremaj u Git. Primjeri su
u [konfiguraciji baze](database.md).

Theme i Menu JSON datoteke nalaze se u `resources/config/theme/` i
`resources/config/menu/`. Njihove PHP konfiguracije ostaju izravno u `config/`
kako naziv PHP datoteke ne bi kolidirao s istoimenim direktorijem.

### 4. Migriranje prazne baze

HFClean već sprema devet službenih početnih migracija. Pregledaj ih i pokreni:

```bash
vendor/bin/hph orm-migrate:status
vendor/bin/hph orm-migrate:up
vendor/bin/hph orm-migrate:status
```

Očekivani rezultat drugog statusa: sve migracije su `[RAN]`, a broj pending
migracija je `0`. Migracije kreiraju sheme i obavezne sistemske zapise; prvog
administratora kreiraj kroz `/settings/auth`.

U vlastitoj minimalnoj aplikaciji kopiraj samo migracije instaliranih modula:

```bash
vendor/bin/hph auth:install-migration
vendor/bin/hph api:install-migration
vendor/bin/hph orm-migrate:up
```

Druge scaffold naredbe pronađi s `vendor/bin/hph`. Ne kopiraj migraciju modula
koji nije instaliran.

### 5. Provjera prije posluživanja

```bash
composer on-commit
php scripts/audit_bilingual_phpdoc.php
php scripts/verify_clean_install_matrix.php
```

Za puni lokalni kandidat na PostgreSQL-u ili MySQL-u pripremi praznu testnu
bazu kroz `HPH_MATRIX_DB_*` i pokreni:

```bash
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

Alat nikada ne sprema pristupne podatke baze u JSON izvještaj.

### 6. Web poslužitelj

Document root usmjeri na `HFClean/public`, omogući PHP procesu pisanje u
`data/` i nepoznate putanje usmjeri na `public/index.php`. Za lokalni razvoj:

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Otvori `http://127.0.0.1:8080/`. Produkcija treba PHP-FPM uz Apache/Nginx,
HTTPS, sigurne kolačiće, ne-razvojni environment i process manager za uključene
outbox/webhook workere.
