# Simbioza 0.3.46 — moj rad i preglednije upravljanje sadržajem

## Vratite se svojem radu

Osobni izbornik sada sadrži **Moj rad**, sa zasebnim pregledima nedavnih doprinosa, nacrta i sadržaja koji čeka vaš pregled prije objave. Stranice u područjima, samostalne HTML stranice i ankete imaju vlastite ograničene popise. Svaki zahtjev provjerava trenutačne ovlasti; prethodno autorstvo ne vraća opozvan pristup. Uspješne izmjene i pregledi suradnika na anketama bilježe se s vlastitim vremenom, bez promjene vlasništva i bez povezivanja ispitanika s tom autorskom evidencijom.

U korisničke upute dodano je poglavlje **Moj rad** ispod **Prijava i moj profil**, uz osvježene upute za profil, pretraživanje i upravljanje grupama na hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom. Nove hrvatske i engleske snimke koriste svijetlu temu Simbioza.

## Pronađite i uredite sadržaj

- Pretraživanje područja podržava filtre postojećih oznaka, strukturiranih svojstava i statusa objave. Pretraživi odabiri učitavaju male skupove rezultata s poslužitelja i provjeravaju ovlasti prije ponude vrijednosti.
- Brisanje grupe zasebna je radnja s potvrdom koja prikazuje posljedice. Prazan naziv više ne briše grupu. Potvrda prepoznaje naknadne promjene članstva, isključenja i pravila mapiranja.
- Spremanje profila preusmjerava na novi GET prikaz, čuva unesene vrijednosti kod pogreške te omogućuje uobičajenu promjenu jezika nakon uspješnog spremanja.
- Odabiri jezika HTML dokumenta i editora nude samo instalirane, aktivne jezike, uz očuvanje spremljenih prijevoda na ostalim jezicima.

## Kolekcija tema

Distribuirana kolekcija sadrži **23 teme**. Tri teme pojedinih instalacija, `aai`, `dabar` i `srce-sup`, više nisu uključene u paket ni javne galerije. Nadogradnja jednokratno uklanja neizmijenjene primjerke instalirane iz stare kolekcije; prilagođeni ili samostalno instalirani primjerci ostaju. Ako je povučena neizmijenjena tema bila aktivna, instalacija prelazi na Simbiozu uz očuvanje načina prikaza i uključenosti teme. Naknadni izričiti uvoz poštuje se.

Provjere izdanja provjeravaju tvorničke pakete tema neovisno o privatnoj zbirci pojedine instalacije.

Preuzmite [kolekciju tema](https://github.com/kmihalj/Simbioza/releases/download/0.3.46/Simbioza-0.3.46-themes.zip) ili pregledajte [galeriju](theme-gallery_hr.md).

## Nadogradnja

Koristite uobičajenu GUI nadogradnju na pravilno podešenoj secured PHP-FPM instalaciji, odnosno dokumentirani CLI postupak na ostalim klasičnim instalacijama. Demo koristi zaseban postupak nadogradnje svojeg dodatka kojim upravlja administrator; produkcijske instalacije zadržavaju postojeće ponašanje.

Survey **0.3.8** dodaje migraciju `20261107000100_install_survey_author_activity_schema`. Sigurnosne kopije anketa uključuju novu autorsku evidenciju, pri vraćanju koriste postojeće preslikavanje korisnika te podržavaju stare arhive bez tih zapisa. Odgovori ispitanika i pravila identiteta ostaju nepromijenjeni. Stariji doprinosi suradnika koji nisu bili zabilježeni ne izmišljaju se.

Usklađeni moduli su Auth **0.3.5**, HTML Editor **0.3.20**, Survey **0.3.8**, Theme **0.3.15** i Workspace Search **0.3.4**. HeartPhrame Framework ostaje **0.0.25**, neizmijenjen. Vidi [instalaciju](installation_hr.md), [Docker instalaciju](docker_hr.md) i [povijest izdanja](releases_hr.md).
