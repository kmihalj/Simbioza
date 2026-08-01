# Instalacija HFCleana

HFClean je integracijska aplikacija za pomične `dev-main` verzije HeartPhrame
Frameworka i modula. Nije uzvodni Framework i ne sadrži demo račune ni probne
domenske podatke.

## 1. Preduvjeti

- PHP 8.2 ili noviji
- Composer 2 i Git pristup svim privatnim repozitorijima modula
- PDO driver za SQLite, PostgreSQL ili MySQL/MariaDB
- PHP ekstenzije uključenih modula (`dom`, `fileinfo`, `mbstring`, `zip` i
  ostale koje prijavi `composer check-platform-reqs`)

## 2. Instaliranje aktualnih stanja modula

```bash
git clone <tvoj-hfclean-repozitorij> HFClean
cd HFClean
composer update --with-all-dependencies
composer check-platform-reqs
```

Interni paketi namjerno koriste `dev-main`, zato aplikacija ima
`"minimum-stability": "dev"` i `"prefer-stable": true`. HFClean ne sprema
`composer.lock`; svaki CI ili deployment razrješava i testira aktualna stanja.
`composer install` koristi se samo kada deployment namjerno dobiva generirani
lock file.

## 3. Lokalna konfiguracija

```bash
cp config/database.php.dist config/database.php
cp config/env.php.dist config/env.php
vendor/bin/hph encryption:generate-key
```

Generirani ključ unesite u `config/env.php`, bazu odaberite u
`config/database.php`, a lokalne datoteke nikada ne spremajte u Git. Primjeri
su u [konfiguraciji baze](database_hr.md).

Theme i Menu JSON datoteke nalaze se u `resources/config/theme/` i
`resources/config/menu/`. Njihove PHP konfiguracije ostaju izravno u `config/`
kako naziv PHP datoteke ne bi kolidirao s istoimenim direktorijem.

## 4. Migriranje prazne baze

HFClean već sprema devet službenih početnih migracija. Pregledajte ih i
pokrenite:

```bash
vendor/bin/hph orm-migrate:status
vendor/bin/hph orm-migrate:up
vendor/bin/hph orm-migrate:status
```

Očekivani rezultat drugog statusa: sve migracije su `[RAN]`, a broj migracija
na čekanju je `0`. Migracije stvaraju sheme i obavezne sistemske zapise; prvog
administratora izradite kroz `/settings/auth`.

U vlastitoj minimalnoj aplikaciji kopirajte samo migracije instaliranih modula:

```bash
vendor/bin/hph auth:install-migration
vendor/bin/hph api:install-migration
vendor/bin/hph orm-migrate:up
```

Druge scaffold naredbe pronađite s `vendor/bin/hph`. Ne kopirajte migraciju
modula koji nije instaliran.

## 5. Provjera prije posluživanja

```bash
composer on-commit
php scripts/audit_bilingual_phpdoc.php
php scripts/verify_clean_install_matrix.php
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Za puni lokalni kandidat na PostgreSQL-u ili MySQL-u pripremite praznu testnu
bazu kroz `HPH_MATRIX_DB_*` i pokrenite:

```bash
php scripts/verify_clean_install_matrix.php \
  --case=all --database=pgsql --local
```

Alat nikada ne sprema pristupne podatke baze u JSON izvještaj.
HFClean CI tijek rada pokreće svaku minimalnu kombinaciju modula na SQLiteu te
potpuni skup modula na čistim PostgreSQL i MySQL servisnim bazama. Tako se pri
svakom pokretanju provjeravaju Composer razrješavanje, instalacija migracija,
pokretanje CLI-ja i HTTP naslovnica na svakoj podržanoj obitelji baza.

## 6. Web-poslužitelj

Document root usmjerite na `HFClean/public`, omogućite PHP procesu pisanje u
`data/` i nepoznate putanje usmjerite na `public/index.php`. Za lokalni razvoj:

```bash
php -S 127.0.0.1:8080 -t public scripts/dev_router.php
```

Otvorite `http://127.0.0.1:8080/`. Produkcija treba PHP-FPM uz Apache/Nginx,
HTTPS, sigurne kolačiće, ne-razvojno okruženje i upravitelj procesa za uključene
outbox/webhook workere.

Razvojni router postojeće assete poslužuje izravno, a samo nepoznate putanje
šalje kroz `public/index.php`. Izolirani browser i API tijek opisan je u
[end-to-end testiranju](end-to-end-testing_hr.md).
