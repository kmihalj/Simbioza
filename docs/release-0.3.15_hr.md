# Simbioza 0.3.15 — automatska i ručna boja teksta gumba

[English version](https://github.com/kmihalj/Simbioza/blob/0.3.15/docs/release-0.3.15_en.md)

> Povijesna bilješka izdanja. Koristite [aktualnu uputu za instalaciju](installation_hr.md)
> i [Simbiozu 0.3.16](release-0.3.16_hr.md) za aktualne upute.

Ovo izdanje uključuje **Theme 0.3.2**. HTML Editor, Menu i Email ostaju
**0.3.2**, Backup/Auth/API **0.3.1**, ostali javni moduli **0.3.0**, Framework
**v0.0.25** i updater **1.0.8**. Postojeći tagovi ne pomiču se.

## Promjene

- Svaka vrsta punog gumba (primarni, sekundarni, uspjeh, upozorenje, opasnost i
  info) ima zasebnu boju teksta za svijetli i tamni prikaz. U **Postavke teme
  → Boje gumbiju → Boje teksta punih gumba** odaberite **Automatski** ili
  ručno postavite HEX boju. Automatski odabire crnu ili bijelu radi boljeg
  kontrasta. Ručni odabir primjenjuje se doslovno, pa provjerite čitljivost na
  odabranoj pozadini.
- Pregled uživo koristi iste postavke kao stvarni gumbi, uključujući alatnu
  traku kalendara. Prazni obrubni gumbi zadržavaju čitljivu akcentnu boju teksta;
  popunjeno stanje pri prelasku mišem ili pritisku koristi odabranu boju teksta.
- Klasična nadogradnja aplikacije i izričiti Docker `migrate` dodaju samo
  nedostajućih dvanaest opcija u `HPH_CONFIG_PATH/theme/themes.json`, uz `auto`.
  Postojeće vrijednosti, dodatna polja i odabrana tema ostaju sačuvani. Ponavljanje
  nadogradnje ne zapisuje već dopunjenu datoteku. To vrijedi i bez opcionalnog
  Backup paketa ili kada je GUI instaliranog Theme modula isključen.
- Priloženi ZIP Simbioza teme sadrži nove opcije za **nove instalacije**.
  Nadogradnja postojeće instalacije **ne** uvozi taj paket niti zamjenjuje
  prilagođene teme. Privatne teme područja koriste ista pravila prikaza;
  nedostajuće opcije ponašaju se kao Automatski, bez resetiranja podataka.
- Instalacijske upute, priložene arhive uputa i Docker AMD64/ARM64 primjeri
  koriste **0.3.15**. Nema promjene sheme baze podataka.

## Nadogradnja

Na podešenoj secured-FPM instalaciji u Setupu odaberite **Pokreni nadogradnju**
i **0.3.15**. Nije potreban zaseban uvoz paketa teme ni ponovna instalacija.
Zatim, ako želite bijeli tekst alatne trake kalendara, postavite boju teksta
sekundarnog gumba na bijelu u obje varijante. Postojeće instalacije zadržavaju
Automatski do izričite promjene; automatski kontrast opravdano može odabrati
crnu na narančastoj pozadini.

Za Docker slijedite [uputu za deployment](https://github.com/kmihalj/Simbioza/blob/0.3.15/docs/docker_hr.md),
zaustavite web i workere, izričito pokrenite migraciju te ih ponovno pokrenite
s istim novim imageom. Zadržite postojeći runtime i PostgreSQL volumen.

[Uputa za konfiguraciju Theme modula](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-theme/-/blob/0.3.2/docs/configuration_hr.md)
opisuje svih šest ključeva, rad iz datoteka i očuvanje postavki. Email ostaje
0.3.2, uključujući `--max-messages`; njegove upute za raspored ne mijenjaju se.
