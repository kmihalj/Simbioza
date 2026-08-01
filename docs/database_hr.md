# Konfiguracija baze

Moduli koji spremaju podatke koriste `heartphrame-module-orm`; Theme i Menu ne
trebaju ORM ni bazu. HFClean je provjeren na SQLite, PostgreSQL, MySQL i
MariaDB-kompatibilnim vezama.

## Lokalna datoteka

Prije uređivanja kopirajte ignorirani predložak:

```bash
cp config/database.php.dist config/database.php
```

Nikada ne spremajte `config/database.php` u Git i ne ispisujte produkcijske
pristupne podatke u CI zapis. U produkciji koristite deployment tajne ili
varijable okruženja.

## SQLite

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

Izradite `data/` i dopustite PHP procesu pisanje. SQLite je prikladan za razvoj,
testove i manje instalacije na jednom poslužitelju; prije produkcije s mnogo
pisanja izmjerite stvarnu konkurentnost.

## PostgreSQL

Koristite `UTF8`, a ne MySQL naziv `utf8mb4`:

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

ORM u prijenosnim migracijama mapira MySQL `utf8*` collation naputak na
PostgreSQL `default`. Eksplicitni PostgreSQL collation pojedinog stupca i dalje
je podržan.

## MySQL ili MariaDB

```php
return [
    'connections' => [
        'default' => [
            'driver' => 'mysql', // Prihvaća se i alias `mariadb`.
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

Bazu izradite s `utf8mb4`, a aplikacijskom korisniku dodijelite prava samo nad
tom bazom. Web-aplikaciju nemojte pokretati kao MySQL `root`.

## Tijek migracija

```bash
vendor/bin/hph orm-migrate:status --connection=default
vendor/bin/hph orm-migrate:up --connection=default
vendor/bin/hph orm-migrate:status --connection=default
```

Nakon uspjeha `status` mora pokazati nula migracija na čekanju. Neuspjelu
migraciju prvo dijagnosticirajte; nemojte je ručno označiti završenom. Naredbu
`orm-migrate:rollback --step=1` koristite samo kada migracija ima siguran `down`
i rollback je planirani oporavak.

## Provjera više baza

Clean-room alat prima već izrađenu praznu testnu bazu:

```bash
HPH_MATRIX_DB_HOST=127.0.0.1 \
HPH_MATRIX_DB_PORT=5432 \
HPH_MATRIX_DB_NAME=heartphrame_matrix \
HPH_MATRIX_DB_USER=heartphrame_matrix \
HPH_MATRIX_DB_PASSWORD='lokalna-testna-tajna' \
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

Podržane vrijednosti su `sqlite`, `pgsql`, `mysql` i `mariadb`. Koristite
zasebnu praznu bazu jer test instalira cijelu shemu. JSON izvještaj sadrži
driver i rezultate, ali nikada pristupne varijable okruženja.
