# Simbioza 0.3.1 — cjelovite instalacijske upute

[Engleska verzija](release-0.3.1_en.md)

> Povijesna bilješka izdanja — koristite aktualne [instalacijske upute](installation_hr.md) i [izdanje 0.3.2](release-0.3.2_hr.md), koje popravlja novu secured-FPM instalaciju. Izvorni tag 0.3.1 ostaje nepromijenjen.

Ovo je dokumentacijsko izdanje na osnovi 0.3.0. Samo aplikacija dobiva tag
**0.3.1**. Svi javni moduli i privatni demo modul ostaju na **0.3.0**, a
HeartPhrame Framework ostaje nepromijenjen na **0.0.25**. Nema novih migracija
baze niti promjena autentikacije/konfiguracije. Objavljeni tagovi 0.3.0 ne pomiču se.

## Što je promijenjeno

- Odvojene, cjelovite upute za Apache mod_php, obični PHP-FPM i sigurni PHP-FPM.
  Svaka počinje na administratorskom terminalu i uključuje alate, preuzimanje
  izdanja, pripremu baze, prava, konfiguraciju web-poslužitelja, instalacijski
  čarobnjak, prvu prijavu i održavanje. Druga instalacijska uputa nije obavezan
  preduvjet.
- Nova sigurna instalacija stvara FPM/deploy identitete. Postojeći identiteti
  razmatraju se samo za reinstalaciju.
- Najprije se instalira standardna Simbioza; opcionalni privatni demo modul
  aktivira se tek nakon provjere obične instalacije.
- Engleske/hrvatske ugrađene instalacijske stranice i početno područje generiraju
  se iz istih Markdown izvora. FPM stranica sadrži odvojene cjelovite postupke
  za običnu i sigurnu instalaciju.
- Offline CI provjere otkrivaju razlike izvora i ugrađenih stranica, parsiraju
  primjere ljuske bez izvršavanja te provjeravaju Apache izraze za obradu PHP-a.
  Primjeri preuzimanja prate aplikacijski VERSION.
- Docker upute i primjer Compose postavki koriste verzionirani image
  `ghcr.io/kmihalj/simbioza:0.3.1`.

## Instalacija i postojeći siteovi 0.3.0

Za novu instalaciju odaberite [jednu cjelovitu uputu](installation_hr.md).
Postojeći site 0.3.0 nadogradite njegovim uobičajenim GUI/CLI updaterom. On
osvježava upravljane ugrađene upute bez zamjene drugih stranica ili njihovih prava.
GitHub `main` ne osvježava automatski stranice već spremljene na siteu.
Vidi [održavanje uputa](maintaining_installation_guides_hr.md).

Docker instalacije zamjenjuju image aplikacije i izvode dokumentirani izričiti
postupak migracije/provjere; runtime konfiguracija/podaci i PostgreSQL volumeni
ostaju sačuvani. Ne uklanjajte volumene radi nadogradnje imagea.
Vidi [Docker instalaciju](docker_hr.md).

Granica nove osnove ostaje ista: nadogradnje s 0.2.x i stari Simbioza backup
formati nisu podržani. Vanjski Confluence izvozi ostaju podržani. Instalaciju
demo/apps-test siteova obavlja administrator, ne postupak objave izdanja.

## Provjere i datoteke izdanja

CI izdanja provjerava PHP 8.2–8.5, čiste kombinacije modula,
SQLite/MySQL/PostgreSQL browser/API tokove i nativne Linux AMD64/ARM64 imagee.
Docker provjere pokrivaju novu PostgreSQL instalaciju, backup, jezike,
trajnost podataka i nepromjenjiv kod. Stranica izdanja bilježi točan uspješan
CI postupak i digest imagea.

Datoteke uključuju Compose/dokumentacijski paket, SHA-256 kontrolne sume te
zaključane ovisnosti, SBOM-ove i potpune izvještaje ranjivosti za obje arhitekture.
Popravljivi HIGH/CRITICAL nalazi blokiraju objavu. Nepopravljeni upstream nalazi
ostaju vidljivi u izvještajima; uspješna provjera ne znači da nema ranjivosti.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.1)
