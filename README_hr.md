# Simbioza

[English version](README.md)

> Znanje koje živi zajedno.

Simbioza je aplikacija za zajedničko znanje izgrađena na HeartPhrame Frameworku i njegovim
samostalno održavanim modulima. Aplikacijski razvoj pripada ovdje i u repozitorije
modula; Framework se koristi iz označenog izdanja `v0.0.24` i ne razvija se u ovom
repozitoriju.

## Ovisnosti

Svaki HeartPhrame modul zahtijeva
`aaieduhr/heartphrame-framework:^0.0.24`. Simbioza trenutačno povezuje ORM,
Menu, Theme, Auth, E-mail, Notification, HTML Editor, Task, Comment, Workspace,
Workspace Search, Calendar, API, Backup, Audit i Simbioza User. Obavezni redoslijed modula i opcionalne mogućnosti navedeni su
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

Framework je ograničen na `^0.0.24`, a Simbioza moduli koriste kompatibilnu
liniju izdanja `^0.1.0`. Aplikacija ne sprema `composer.lock`; svaki CI dohvaća
najnovija kompatibilna označena izdanja i pokreće cijeli skup provjera kvalitete.
Produkcijski deployment može izvan izvornog repozitorija čuvati vlastiti
provjereni lock.

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

Poslužiteljska release instalacija smije namjerno biti bez `.git` direktorija i
čuvati vlastiti provjereni `composer.lock`. Od izdanja `0.1.9` nadalje iz
korijena instalacije provjerite i instalirajte najnovije stabilne tagove
aplikacije i kompatibilnih modula ovako:

```bash
sudo php update.php --check
sudo php update.php
```

Cijeli postupak release instalacije i nadogradnje opisan je u
[uputama za instalaciju](docs/installation_hr.md#11-nadogradnja-release-instalacije).

Konfiguracija aplikacije, migracije, redoslijed modula i API integracija opisani
su u [hrvatskoj dokumentaciji](docs/index_hr.md). Engleska dokumentacija ima
zaseban [engleski indeks](docs/index_en.md).

## Dokumentacija

- Glavni indeks (HR): [docs/index_hr.md](docs/index_hr.md)
- Glavni indeks (EN): [docs/index_en.md](docs/index_en.md)
- [Instalacija](docs/installation_hr.md)
- [Zapis šest čistih instalacija i screenshotovi](docs/installation-lab_hr.md)
- [Ovisnosti modula](docs/module-dependencies_hr.md)
- [Konfiguracija baze](docs/database_hr.md)
- [API v1 ugovor](docs/api-v1-contract_hr.md)
- [End-to-end testiranje](docs/end-to-end-testing_hr.md)
- [Vizualni identitet i tema](docs/branding_hr.md)

E2E skup uključuje neosjetljiva ORM i HTTP mjerenja te trajne budžete za broj
SQL upita, trajanje zahtjeva, vršnu memoriju i veličinu odgovora. Isti potpuni
skup u CI-ju se pokreće na SQLiteu, PostgreSQL-u i MySQL-u.

Održavanje područja optimizira postojeće slike kao trajni i nastavivi posao s
vidljivim progress barom. Slike se obrađuju u ograničenim serijama, pa veliki
site više ne drži jedan HTTP zahtjev otvorenim tijekom cijele obrade; izvorne
datoteke ostaju nepromijenjene.

## Uključeni moduli

Simbioza povezuje API, Audit, Auth, Backup, Calendar, Comment, HTML Editor,
E-mail, Menu, Notification, ORM, Simbioza User, Task, Theme, Workspace i Workspace Search. Simbioza User dodaje
praćenja, pravila dostave obavijesti i ograničena osobna područja koja se mogu
izraditi pri prvoj prijavi prema administratorskom pravilu. Moduli zadržavaju vlasništvo nad
svojim domenskim pravilima, a aplikacija ih povezuje i daje postavke deploymenta.

## Licencija

Ovaj rad objavljen je pod
[Javnom licencijom Europske unije (EUPL) v1.2](LICENSE).
