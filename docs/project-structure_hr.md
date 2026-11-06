# Struktura projekta

Simbioza povezuje neovisne HeartPhrame module u jednu aplikaciju. Aplikacijski
kod u `src/` koristi prostor imena `App`; domenski moduli imaju vlastite
repozitorije, servise, rute i testove. Framework se instalira kao vanjski
Composer paket i ne uređuje se u ovom projektu.

| Putanja | Namjena |
| --- | --- |
| `src/Controllers/`, `views/` | Početna/O aplikaciji, postavke, ulazi u sadržaj i HTML upute; rasporedi povezuju opcionalne renderere modula. |
| `src/Module/`, `src/Installation/`, `src/Setup/` | Katalog i životni ciklus modula, nova instalacija i administrativne radnje prema načinu postavljanja. |
| `src/Configuration/`, `config/`, `framework.php` | Razrješavanje konfiguracije te pouzdani bootstrap/rute/servisi. Privatne vrijednosti koriste konfigurirane slojeve. |
| `src/Localization/`, `lang/` | Instalirani jezici, objedinjeni rječnici i održavanje jezika. |
| `src/Update/`, `resources/` | Podrška nadogradnji i verzionirani instalacijski sadržaj: upute i kolekcije tema. |
| `src/Guides/`, `docs/user-guide/` | Čitač ugrađenog priručnika i izvori dvojezičnih korisničkih uputa/slika. |
| `src/Security/`, `src/Performance/` | Pravila skripti domaćina i opcionalna mjerenja zahtjeva/upita. |
| `database/migrations/` | Verzionirane promjene sheme; stanje primjene migracija ostaje u bazi. |
| `public/` | Front controller i namjerno javne datoteke. Web document root pokazuje ovdje. |
| `scripts/`, `tests/` | Alati održavanja/izgradnje, jedinični i integracijski testovi te izolirani testovi preglednika/API-ja. |
| `docs/` | Aktualne razvojne/administratorske reference i povijesne bilješke izdanja. |
| `vendor/` | Composer ovisnosti; ne uređuju se ručno niti spremaju u Git. |
| `build/` | Ignorirani izvještaji testova, privremeni manifesti provjera i generirane razvojne datoteke. |
| `data/` | Privatni runtime: logovi, cache, privitci, privremeni arhivi i administratorske datoteke tema. |

## Granica konfiguracije i podataka

`HPH_CONFIG_PATH` određuje korijen konfiguracije; `HPH_CONFIG_OVERRIDE_PATHS`
dodaje jače slojeve. HTTP i CLI moraju razrješavati iste direktorije. Korijen
koda nije nužno korijen konfiguracije. Vidi [konfiguraciju](configuration_hr.md).

Na postojećoj instalaciji ne zamjenjujte privatne JSON/PHP dokumente `.dist`
primjerima. Pristupni podaci baze, ključevi šifriranja, SMTP/provider tajne,
logovi, uploadi, sesije i backup arhivi ne pripadaju Gitu ni javnom web korijenu.
Ne dajte web računu pravo pisanja po kodu radi rješavanja runtime pogreške.

Dio održavanih datoteka u `data/themes/` jesu **datoteke izdanja**, a ne
administratorski uploadi. U `resources/installation/theme/` nalaze se paketi za
uvoz tema; evidencija kolekcije sprječava da nadogradnja stalno vraća temu koju
je administrator obrisao. [Dokumentacija Teme](theme-collection_hr.md) opisuje uvoz/izvoz
kolekcije i galeriju.

## Upute i vlasništvo modula

Uređujte `docs/user-guide/*_en.md` / `*_hr.md` i njihove slike, zatim primijenite
[postupak sinkronizacije priručnika](documentation-quality_hr.md). Generirani
paket područja koristi se i za HTML upute bez Wikija.

Za polje obrasca druge aplikacije koristite [primjer ugrađenog HTML
Editora](html-editor-forms_hr.md). Spremanje polja odvojeno je od
stvaranja objavljenog dokumenta ili stranice područja; aplikacija je vlasnik
validacije, ovlasti i pohrane tog obrasca.

Ponašanje modula mijenja se u njegovu repozitoriju, a ne u `vendor/` ni kopiranjem
privatne implementacije u Simbiozu. Opcionalne integracije ostaju opcionalne;
vidi [ovisnosti modula](module-dependencies_hr.md) i
[uobičajene postupke](common-workflows_hr.md).
