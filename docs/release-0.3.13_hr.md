# Simbioza 0.3.13 — osvježene javne instalacijske upute

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/release-0.3.13_en.md)

Ovo dokumentacijsko izdanje odvaja javne upute Simbioze od privatnih postupaka
postavljanja. Ponašanje aplikacije, shema baze i verzije modula ostaju isti kao
u 0.3.12. HTML Editor ostaje **0.3.2**, Backup, Auth, API, Email i Theme **0.3.1**,
Menu **0.3.2**, ostali javni moduli **0.3.0**, Framework **v0.0.25**, a updater
**1.0.8**. Postojeći tagovi ne pomiču se.

## Promjene

- Javne instalacijske upute i izvori bilježaka izdanja više ne upućuju čitatelje
  na privatne postupke ili repozitorije kojima nemaju pristup.
  Javne poveznice za isprobavanje Simbioze ostaju dostupne.
- Engleske i hrvatske arhive upravljanih uputa ponovno su izrađene iz istih
  održavanih Markdown izvora. Redovna nadogradnja osvježava te upute bez promjene
  nepovezanih stranica, prava, identiteta stranica ili privatnih postavki.
- Instalacijske naredbe i Docker primjeri dosljedno koriste **0.3.13**.
  Linux AMD64 i ARM64 imagei sadrže iste osvježene pakete uputa.
- Regresijski testovi provjeravaju javnu dokumentaciju i sadržaj svake ugrađene
  arhive radi slučajno dodanih privatnih postupaka ili nedostupnih poveznica.
- Opisi postojećih GitHub izdanja čiste se odvojeno. Povijesni tagovi, izvorne
  arhive i objavljene datoteke ostaju nepromijenjeni.

## Nadogradnja

Na podešenoj secured-FPM instalaciji otvorite **Postavke → Setup i moduli**
i pokrenite nadogradnju aplikacije na **0.3.13**. Nisu potrebni reinstalacija,
promjena konfiguracije ni nova migracija. Popravci prenosivih poveznica iz
0.3.12 ostaju uključeni. Nakon nadogradnje pregledajte osvježene Korisničke upute.

Za Docker zamijenite aplikacijski image prema Docker uputi, uz očuvanje volumena
konfiguracije/podataka i baze. Ne uklanjajte volumene pri nadogradnji.

Vidi [instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/installation_hr.md),
[konfiguraciju i backup](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/configuration_hr.md#backup-i-prava)
i [Docker instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/docker_hr.md).
