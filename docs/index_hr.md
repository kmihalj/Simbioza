# Dokumentacija Simbioze (HR)

Ovo je dokumentacija aplikacije Simbioza za zajedničko znanje i njezinih uključenih
HeartPhrame modula. Framework se koristi s uzvodne grane `main`; ne razvija se u ovom
repozitoriju.

Slogan brenda: **Znanje koje živi zajedno.**

- [Vizualni identitet i tema Simbioza](branding_hr.md)

Dokumentacija je strukturirana za početnike i napredne korisnike. Svaka tema
ima zasebnu hrvatsku i englesku datoteku.

Predloženi put za početnike:

1. [Instalacija](installation_hr.md)
2. [Ovisnosti modula](module-dependencies_hr.md)
3. [Konfiguracija baze](database_hr.md)
4. [Konfiguracija](configuration_hr.md)
5. [Uobičajeni tijekovi rada](common-workflows_hr.md)
6. [End-to-end testiranje](end-to-end-testing_hr.md)

Predloženi put za napredne korisnike:

1. [Struktura projekta](project-structure_hr.md)
2. [Moduli](modules_hr.md)
3. [Zajednički servisi](common-services_hr.md)
4. [API v1 ugovor](api-v1-contract_hr.md)
5. [Plan implementacije API-ja](api-implementation-plan_hr.md)

## Početak

- [Instalacija](installation_hr.md)
- [Ovisnosti modula](module-dependencies_hr.md)
- [Konfiguracija baze](database_hr.md)
- [Konfiguracija](configuration_hr.md)
- [Struktura projekta](project-structure_hr.md)

Podržane minimalne kombinacije, dvojezičnu dokumentaciju i potpuni skup provjera
provjerite naredbama:

```bash
php scripts/verify_clean_install_matrix.php
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
composer on-commit
composer e2e
```

## Osnove

- [Životni ciklus zahtjeva](request-lifecycle_hr.md)
- [Rute](routes_hr.md)
- [Middleware](middleware_hr.md)
- [Prikazi](views_hr.md)
- [Sesije](sessions_hr.md)
- [Lokalizacija](localization_hr.md)

## Napredne teme

- [Zajednički servisi i ubrizgavanje ovisnosti](common-services_hr.md)
- [Moduli](modules_hr.md)
- [Ovisnosti modula](module-dependencies_hr.md)
- [API v1 ugovor](api-v1-contract_hr.md)
- [Plan implementacije API-ja](api-implementation-plan_hr.md)
- [Šifriranje](encryption_hr.md)
- [Dispečer događaja](event-dispatcher_hr.md)
- [End-to-end testiranje](end-to-end-testing_hr.md)

Aplikacija uključuje `aaieduhr/heartphrame-module-api` odmah nakon Autha. Rute
pod `/api/v1` koriste Bearer ključeve i namjerno preskaču pokretanje PHP sesije
te pregledničku CSRF provjeru. Auth posjeduje korisnike, grupe, hashove ključeva
i administratorska pravila; API modul posjeduje verzionirane JSON ugovore i
uvjetni ekran API ključeva.

## Vodiči

- [Uobičajeni tijekovi rada](common-workflows_hr.md)
- [Rješavanje problema](troubleshooting_hr.md)

## English version

- [English documentation](index_en.md)
