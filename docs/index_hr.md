# Dokumentacija Simbioze (HR)

- [Pristupačnost i performanse anketa u 0.3.22](release-0.3.22_hr.md)
- [Navigacija pune aplikacije u 0.3.21](release-0.3.21_hr.md)
- [Modularne instalacije u 0.3.20](release-0.3.20_hr.md)
- [Postavke anketa i adrese aplikacije u 0.3.19](release-0.3.19_hr.md)

- [Ankete i zajednički HTML Editor u 0.3.18](release-0.3.18_hr.md)

Ovo je dokumentacija aplikacije Simbioza za zajedničko znanje i njezinih uključenih
HeartPhrame modula. Framework se koristi iz označenog uzvodnog izdanja; ne razvija se u ovom
repozitoriju.

Slogan brenda: **Znanje koje živi zajedno.**

- [Odaberite web, kalendar, Wiki, ankete ili cjelovitu Simbiozu](modularity_hr.md)
- [Korisničke upute s Wikijem i bez njega](bundled-manual_hr.md)

- [Ispravak vlasništva pri FPM nadogradnji jezika i aplikacije u 0.3.17](release-0.3.17_hr.md)
- [Paginacija, log workera i rotacija u 0.3.16](release-0.3.16_hr.md)
- [Vlasništvo, rotacija i čuvanje logova](logging_hr.md)
- [Automatska i ručna boja teksta gumba u 0.3.15](release-0.3.15_hr.md)
- [Jasan pregled nadogradnji u Setupu i Email limit workera u 0.3.14](release-0.3.14_hr.md)
- [Osvježene javne instalacijske upute u 0.3.13](release-0.3.13_hr.md)
- [Očuvanje tema/postavki i odabranih boja u 0.3.10](release-0.3.10_hr.md)
- [Prenosive poveznice uputa i povrat na isti site u 0.3.12](release-0.3.12_hr.md)
- [Revizije ugrađenih jezika u 0.3.7](release-0.3.7_hr.md)
- [Provjerena instalacija sigurnog FPM-a u 0.3.6](release-0.3.6_hr.md)
- [Zaštita privatnih konfiguracijskih direktorija u 0.3.5](release-0.3.5_hr.md)
- [Sigurnosno Docker izdanje 0.3.3](release-0.3.3_hr.md)
- [Runtime prava sigurnog FPM-a u 0.3.4](release-0.3.4_hr.md)
- [Izdanje 0.3.2 s ispravkom secured-FPM setupa](release-0.3.2_hr.md)
- [Dokumentacijsko izdanje 0.3.1](release-0.3.1_hr.md)
- [Početno izdanje 0.3.0 i popis provjera](initial-release-0.3.0_hr.md)
- [Prošireni audit prije releasea i nastavak](pre-release-audit-0.3.0_hr.md)
- [Aktualna konfiguracija i datoteke u vlasništvu modula](configuration_hr.md)
- [Provjere dokumentacije, primjera i jezika komentara](documentation-quality_hr.md)
- [Docker instalacija, volumeni, nadogradnja imagea i opcionalni SAML](docker_hr.md)
- [Spremnost za containere i granice provjere](container-readiness_hr.md)
- [Vizualni identitet i tema Simbioza](branding_hr.md)


Dokumentacija je strukturirana za početnike i napredne korisnike. Svaka tema
ima zasebnu hrvatsku i englesku datoteku.

Predloženi put za početnike:

1. [Instalacija](installation_hr.md)
2. [Zapis šest čistih instalacija](installation-lab_hr.md)
3. [Ovisnosti modula](module-dependencies_hr.md)
4. [Konfiguracija baze](database_hr.md)
5. [Konfiguracija](configuration_hr.md)
6. [Uobičajeni tijekovi rada](common-workflows_hr.md)
7. [End-to-end testiranje](end-to-end-testing_hr.md)

Predloženi put za napredne korisnike:

1. [Struktura projekta](project-structure_hr.md)
2. [Moduli](modules_hr.md)
3. [Zajednički servisi](common-services_hr.md)
4. [API v1 ugovor](api-v1-contract_hr.md)
5. [Plan implementacije API-ja](api-implementation-plan_hr.md)

## Početak

- [Pregled instalacije](installation_hr.md)
- [Instalacija s običnim PHP-FPM-om](installation_fpm_hr.md)
- [Instalacija sa secured PHP-FPM-om](installation_fpm_secured_hr.md)
- [Održavanje instalacijskih uputa](maintaining_installation_guides_hr.md)
- [Instalacija s Apache mod_php modulom](installation_mod_php_hr.md)
- [Zapis šest čistih instalacija i screenshotovi](installation-lab_hr.md)
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
- [Plan jedinstvene konfiguracije i točka nastavka](configuration-migration-plan_hr.md)
- [Organizacija podataka područja i stranica](workspace-data_hr.md)
- [Šifriranje](encryption_hr.md)
- [Dispečer događaja](event-dispatcher_hr.md)
- [End-to-end testiranje](end-to-end-testing_hr.md)
- [Dnevnik aktivnosti i tehničko zapisivanje](audit-logging_hr.md)
- [Osobna područja](personal-workspaces_hr.md)
- [Osobni izgled](personal-appearance_hr.md)

Aplikacija uključuje `aaieduhr/heartphrame-module-api` odmah nakon Autha. Rute
pod `/api/v1` koriste Bearer ključeve i namjerno preskaču pokretanje PHP sesije
te pregledničku CSRF provjeru. Auth posjeduje korisnike, grupe, hashove ključeva
i administratorska pravila; API modul posjeduje verzionirane JSON ugovore i
uvjetni ekran API ključeva.

## Vodiči

- [Uobičajeni tijekovi rada](common-workflows_hr.md)
- [Rješavanje problema](troubleshooting_hr.md)
- [Dnevnik aktivnosti i tehničko zapisivanje](audit-logging_hr.md)
- [Osobna područja](personal-workspaces_hr.md)
- [Osobni izgled](personal-appearance_hr.md)

## English version

- [English documentation](index_en.md)

### Povijesne bilješke izdanja

Ove bilješke opisuju starija izdanja, ne instalaciju ni nadogradnju čiste osnove 0.3.0.

- [Zapis izdanja 0.2.11](release-0.2.11_hr.md)
