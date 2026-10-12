# Simbioza 0.3.0

[Engleska verzija](release-0.3.0_en.md)

Ovo je čisto inicijalno izdanje. Instalirati u novu bazu i nove direktorije runtime
konfiguracije/podataka. Nadogradnje s 0.2.x i stari Simbioza backup formati nisu
podržani; vanjski Confluence exporti ostaju podržani.

## Što je uključeno

- Svih 18 javnih modula koristi usklađene ovisnosti 0.3.0. HeartPhrame Framework
  ostaje nepromijenjen, kao objavljena ovisnost 0.0.25.
- Centralna konfiguracija prati HPH_CONFIG_PATH, uređene override slojeve i
  direktorije modula. Auth profili/atributi i postavke generičkih modula mogu se
  uređivati u datotekama; postojeći GUI ostaje dostupan gdje ima smisla.
- Područja/stranice organiziraju HTML datoteke, privitke, menije i teme. Ostaje
  podržano i spremanje HTML-a u bazu.
- Odobrene vanjske skripte učitavaju se samo na odabranim stranicama. Import
  prijavljuje nepoznate skripte i može automatski povezati postojeća odobrenja.
  Pregled upotrebe i backup/restore stranica/područja uključuju njihove reference
  i definicije.
- Prijenosni backup/restore prilagođava interne linkove između siteova. Confluence
  import čuva podržane datume i prijavljuje nerazriješene reference.
- Auth/OIDC validacija, granice arhiva/putanja, skupne ACL provjere i ograničenja
  SQL upita pokriveni su auditom i automatiziranim testovima.
- Dokumentacija i primjeri odvojeni su po jezicima; dvojezični komentari u kodu
  imaju prvo engleski pa hrvatski blok.

## Docker

Linux AMD64 i ARM64 imagei koriste PHP/Apache i PostgreSQL 18. Runtime
konfiguracija/podaci dijele jedan imenovani volumen; baza koristi drugi. Kod
aplikacije je nepromjenjiv u imageu. Zadani port hosta je 127.0.0.1:1511 i može
se promijeniti. SimpleSAMLphp je opcionalni dokumentirani read-only mount koda,
ne obavezna ovisnost standardnog imagea.

Release datoteke sadrže Compose paket, kontrolne sume, zaključane ovisnosti,
SBOM-ove, izvještaje ranjivosti i identitet imagea. Popravljivi visoki/kritični
nalazi blokiraju objavu. Izvještaji zadržavaju nepopravljene upstream nalaze za
pregled i ne tvrde da operacijski sustav nema ranjivosti. Baza koristi Debian
Trixie bez kompilacijskih alata/zaglavlja. Debian trenutačno preostali
[libxml2 CVE-2026-6653](https://security-tracker.debian.org/tracker/CVE-2026-6653)
klasificira kao manji problem bez stabilne sigurnosne nadogradnje. Migracijske
arhive trebaju dostavljati samo pouzdani administratori; ne uvoziti nepouzdane exporte.

## Provjere

Lokalni integrirani E2E: svih 80 scenarija prošlo je provjeru. Aplikacijski unit
testovi i tri dodatne root/Linux provjere također se izvode u CI-ju izdanja.
CI izdanja dodatno provjerava PHP 8.2-8.5, čiste kombinacije modula, PostgreSQL,
MySQL, browser/API tokove i obje nativne arhitekture imagea. Točni CI rezultati i
digesti imagea dio su objave; pripremljeni checkout nije dokaz objavljenog artefakta.

[Docker upute](docker_hr.md) · [Popis za objavu](release-publication-0.3.0_hr.md)
