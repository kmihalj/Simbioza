# End-to-end testiranje

HFClean ima end-to-end skup testova preglednika i HTTP API-ja za sastavljenu
aplikaciju. Za razliku od jediničnog testa modula, ovaj test instalira najnovije
`dev-main` stanje Frameworka i modula u novi privremeni projekt, pokreće sve
službene migracije nad novom SQLite bazom, podiže stvarni HTTP poslužitelj i
upravlja Chromiumom kroz Playwright.

Skup nikada ne koristi `config/database.php` radne aplikacije. Testni korisnici,
Bearer ključ, Workspace zapisi, sesije, logovi i cache postoje samo u
direktoriju ispod privremenog sistemskog korijena
`heartphrame-clean-matrix`. Runner odbija raditi izvan tog korijena i nakon
završetka uklanja projekt.

## Što se provjerava

- naslovnica i statičke datoteke učitavaju se kroz stvarni front controller;
- mobilni izbornik otvara se kao desni bočni panel i ponovno se zatvara;
- vizual podešen u hero postavkama učitava se na mobilnom viewportu;
- iste Home i Inner postavke veličine daju jednaku renderiranu visinu heroa;
- hero dolazi do ruba viewporta bez horizontalnog overflowa;
- gost se s Auth administracije preusmjerava na prijavu;
- lokalni administrator može se prijaviti, otvoriti Auth postavke i odjaviti;
- prijavljeni korisnik koji nije administrator dobiva HTTP `403` za Auth postavke;
- nedostajući i neispravan Bearer ključ vraćaju isti RFC problem odgovor;
- valjani ključ čita API discovery i `/api/v1/me` bez podataka o lozinci;
- administratorski ključ izvršava stvarni Workspace create/read API tijek.

## Prvo pokretanje

Instalirajte najnoviji Playwright testni paket bez lock datoteke, zatim
instalirajte Chromium:

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Aplikacija i interni paketi namjerno prate pomična `dev-main` stanja. Zato se
`package-lock.json` i `composer.lock` ne spremaju u Git.

## Testiranje lokalnih izmjena modula

Zadana naredba dohvaća udaljena `dev-main` stanja paketa, jednako kao CI. Prije
pusha dopuštene susjedne module provjerite ovako:

```bash
composer e2e -- --local
```

Lokalni način koristi path repozitorije samo za module koji pripadaju ovom
projektu. Nikada ne zamjenjuje uzvodni Framework ni Demo modul.

## Istraživanje greške

Pokrenite vidljivi preglednik i sačuvajte izoliranu aplikaciju:

```bash
composer e2e -- --local --headed --keep
```

Runner ispisuje putanju sačuvanog projekta. Playwright traceovi, slike zaslona,
videozapisi i HTML izvještaj zapisuju se ispod `build/`, a izlaz PHP poslužitelja
u `build/e2e-server.log`. Git ignorira sve te putanje. Sačuvani projekt uklonite
nakon pregleda ili ponovno pokrenite test bez `--keep`.

## CI

GitHub Actions instalira najnoviji Node.js, razrješava najnoviji npm paket,
instalira Chromium i njegove Linux ovisnosti te pokreće `composer e2e`. Kod
greške Playwright izvještaj, traceovi, slike, videozapisi i log poslužitelja
ostaju dostupni kao CI artefakt.

Browser skup namjerno je zaseban job od PHP jediničnih i statičkih provjera.
Tako je odmah jasno pripada li greška izoliranom modulu, čistoj instalaciji,
mrežnoj bazi ili sastavljenom browser/API tijeku.
