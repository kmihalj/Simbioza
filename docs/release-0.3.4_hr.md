# Simbioza 0.3.4 — runtime prava sigurnog FPM-a

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/release-0.3.4_en.md)

Ova zakrpa ispravlja generator sigurnog macOS FPM-a i upute za novu instalaciju.
Framework ostaje **v0.0.25**, API **0.3.1**, a ostali javni moduli **0.3.0**.
Privatni DEMO modul je **0.3.6**. Postojeći tagovi ne pomiču se.

## Promjene

- Generirani launchd servisi izričito koriste cijeli broj `Umask=7` (oktalno
  `0007`), jednako postojećoj Linux politici. Novi obični logovi zadržavaju
  privatni grupni upis umjesto FPM-ovih datoteka `0644` bez upisa za deploy.
- macOS deploy helper postavlja masku nakon sudo promjene identiteta.
  Helperi na obje platforme koriste privatni aplikacijski `data/tmp`, jednako
  FPM-u, pa su zajednički privremeni lockovi u istom runtime direktoriju.
- Testovi parsiraju stvarno generirani plist, provjeravaju omotač nakon sudo i
  granice argumenata te zadržavaju provjeru pravim FPM parserom. Framework i Auth
  kod nisu mijenjani.
- Instalacijske naredbe, Docker primjeri i ugrađene EN/HR upute koriste **0.3.4**.
  macOS instalacija provjerava masku servisa prije otvaranja čarobnjaka.
- DEMO 0.3.6 provjerava logove/bazu prije reseta/pauziranja, čuva privatne sesije/
  upravljačke podatke i testira stvarni bootstrap pod odvojenim deploy/FPM računima.
  Cjelovita uputa koristi ispravnu masku za instalacijske CLI naredbe.

## Instalacija i postojeći siteovi

Koristite [pregled instalacije](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/installation_hr.md),
[uputu za sigurni FPM](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/installation_fpm_secured_hr.md)
ili [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/docker_hr.md).
[Privatna DEMO uputa](https://github.com/kmihalj/simbioza-module-demo/blob/0.3.6/docs/demo_installation_hr.md)
počinje praznim direktorijem i zahtijeva pristup repozitoriju.

Nadogradnja aplikacije ne prepisuje postojeće launchd/helper datoteke poslužitelja
niti popravlja stare logove. Za njih je potreban ciljani popravak uz prethodnu
sigurnosnu kopiju. Ne ponavljati opću finalizaciju nad DEMO reset kopijom, brisati
lock, davati FPM-u upis po kodu ili pokretati updater kao root radi zaobilaženja greške.
Nema novih migracija baze; Confluence import jednog područja ostaje nepromijenjen.

Linux AMD64/ARM64 imagei zadržavaju postojeće sigurnosne provjere i raspored
trajnih volumena. Ne uvodi se upstream zakrpa za libxml2 ni iznimka za ranjivosti.
Objava izdanja ne nadograđuje demo, HFC ni apps-test automatski.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.4)
