# Simbioza

[English version](README.md)

> Znanje koje živi zajedno.

Simbioza je aplikacija za zajedničko znanje izgrađena na HeartPhrame Frameworku i njegovim
samostalno održavanim modulima. Aplikacijski razvoj pripada ovdje i u repozitorije
modula; Framework se koristi s uzvodne grane `main` i ne razvija se u ovom
repozitoriju.

## Ovisnosti

Svaki HeartPhrame modul zahtijeva
`aaieduhr/heartphrame-framework:dev-main`. Simbioza trenutačno povezuje ORM,
Menu, Theme, Auth, E-mail, Notification, HTML Editor, Task, Comment, Workspace,
Workspace Search, Calendar, API, Backup i Audit. Obavezni redoslijed modula i opcionalne mogućnosti navedeni su
u [matrici ovisnosti](docs/module-dependencies_hr.md).

Najmanje provjerene instalacije su samo Framework, Framework + Theme,
Framework + Menu te Framework + Theme + Menu. Moduli s bazom dodaju ORM i svoje
dokumentirane domenske ovisnosti. Composer automatski razrješava sve tranzitivne
ovisnosti.

## Preduvjeti

- PHP 8.2 ili noviji
- Composer 2
- PDO SQLite za zadanu lokalnu instalaciju
- Git pristup navedenim repozitorijima modula

## Politika ovisnosti

Framework i svi interni HeartPhrame moduli namjerno se zahtijevaju s pomične
grane `dev-main`. Ne koriste se fiksni aliasi ni rasponi internih verzija. Ova
aplikacija također ne sprema `composer.lock`; svaki CI i deployment dohvaća
najnovija razvojna stanja te pokreće cijeli skup provjera kvalitete.

Spremljeni Composer metapodaci koriste VCS repozitorije kako bi čista CI kopija
radila bez susjednih direktorija. Za lokalni rad sa simbolički povezanim
modulima koristi se nespremljeni `composer.local.json` preko varijable
`COMPOSER`; lokalni `path` repozitoriji ne spremaju se u zajednički manifest.

## Instalacija i provjera

```bash
composer update --with-all-dependencies
composer check-platform-reqs
composer on-commit
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Konfiguracija aplikacije, migracije, redoslijed modula i API integracija opisani
su u [hrvatskoj dokumentaciji](docs/index_hr.md). Engleska dokumentacija ima
zaseban [engleski indeks](docs/index_en.md).

## Dokumentacija

- Glavni indeks (HR): [docs/index_hr.md](docs/index_hr.md)
- Glavni indeks (EN): [docs/index_en.md](docs/index_en.md)
- [Instalacija](docs/installation_hr.md)
- [Ovisnosti modula](docs/module-dependencies_hr.md)
- [Konfiguracija baze](docs/database_hr.md)
- [API v1 ugovor](docs/api-v1-contract_hr.md)
- [End-to-end testiranje](docs/end-to-end-testing_hr.md)
- [Vizualni identitet i tema](docs/branding_hr.md)

E2E skup uključuje neosjetljiva ORM i HTTP mjerenja te trajne budžete za broj
SQL upita, trajanje zahtjeva, vršnu memoriju i veličinu odgovora. Isti potpuni
skup u CI-ju se pokreće na SQLiteu, PostgreSQL-u i MySQL-u.

## Uključeni moduli

Simbioza povezuje API, Audit, Auth, Backup, Calendar, Comment, HTML Editor,
E-mail, Menu, Notification, ORM, Task, Theme, Workspace i Workspace Search. Moduli zadržavaju vlasništvo nad
svojim domenskim pravilima, a aplikacija ih povezuje i daje postavke deploymenta.

## Licencija

Ovaj rad objavljen je pod
[Javnom licencijom Europske unije (EUPL) v1.2](LICENSE).
