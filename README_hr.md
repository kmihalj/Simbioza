# HFClean HeartPhrame aplikacija

[English version](README.md)

HFClean je integracijska aplikacija za HeartPhrame Framework i njegove
samostalno održavane module. Aplikacijski razvoj pripada ovdje i u repozitorije
modula; Framework se koristi s uzvodne grane `main` i ne razvija se u ovom
repozitoriju.

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
```

Konfiguracija aplikacije, migracije, redoslijed modula i API integracija opisani
su u [dokumentaciji](docs/index.md). Dvojezična matrica nalazi se u dokumentu
[ovisnosti modula](docs/module-dependencies.md).

## Uključeni moduli

HFClean povezuje API, Auth, Calendar, Comment, HTML Editor, E-mail, Menu,
Notification, ORM, Task, Theme i Workspace. Moduli zadržavaju vlasništvo nad
svojim domenskim pravilima, a aplikacija ih povezuje i daje postavke deploymenta.

## Licencija

Ovaj rad objavljen je pod
[Javnom licencijom Europske unije (EUPL) v1.2](LICENSE).
