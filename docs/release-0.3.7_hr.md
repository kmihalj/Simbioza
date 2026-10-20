# Simbioza 0.3.7 — revizije ugrađenih jezika

> Povijesna bilješka izdanja. Za aktualne instalacijske upute koristite [instalacijsku uputu](installation_hr.md).

[English version](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/release-0.3.7_en.md)

Ova aplikacijska zakrpa prepoznaje ugrađene prijevode na novim i nadograđenim
instalacijama. Framework ostaje **v0.0.25**, API **0.3.1**, ostali javni moduli
**0.3.0**. Updater **1.0.8** i migracije baze
ostaju nepromijenjeni. Postojeći tagovi ne pomiču se.

## Promjene

- Setup prikazuje **Ugrađeni prijevodi** i verziju instalirane Simbioze za
  ugrađene engleske/hrvatske kataloge. Ne traži jednokratnu CLI instalaciju
  jezika samo radi prepoznavanja nove instalacije.
- Verzija instalirane aplikacije i dostupna revizija paketa repozitorija
  izričito su odvojene. Ugrađeni prijevodi ne evidentiraju se lažno kao
  instalirani vanjski paket.
- Kasnija promjena digesta repozitorija može se instalirati gumbom **Ažuriraj**,
  čak i ako izdavač zadrži isti broj jezične revizije. Tada se zapisuje stvarna
  revizija paketa uz očuvanje prethodne uključenosti/isključenosti jezika.
- Ručni prijevodi ostaju prepoznatljivi i ne zamjenjuju se automatski.
  Provjerenu zamjenu moguće je izričito potvrditi u GUI-u.
- Nove i nadograđene instalacije ne trebaju migraciju podataka/postavki ni
  dodatne jezične naredbe. Postojeći runtime prijevodi, zastavice i evidencija
  čuvaju se. Runtime stanje ostaje u `data/languages/`; korisničke postavke
  i dalje poštuju `HPH_CONFIG_PATH`.
- Upute sigurnog FPM-a razlikuju postojeći HTTPS vhost s Aliasom od novog
  zasebnog `simbioza.conf` **prije** Apache naredbi. Naredbe uključivanja
  zasebnog sitea prekidaju se nakon greške.
- Instalacijske naredbe, primjeri i ugrađene EN/HR upute koriste **0.3.7**.
  Nativni Docker AMD64/ARM64 imagei čuvaju volumene postavki/podataka i baze.

## Nadogradnja

Na podešenoj FPM-secured instalaciji koristite **Postavke → Setup i moduli →
Provjeri novo izdanje**, zatim pokrenite nadogradnju aplikacije na **0.3.7**.
Nakon dovršetka osvježite Setup → Jezici: ugrađeni jezici pokazuju
`Simbioza 0.3.7`, osim ako ste namjerno instalirali vanjski ili ručni paket.

Pogledajte [upute lokalizacije](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/localization_hr.md),
[cjelovitu uputu sigurnog FPM-a](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/installation_fpm_secured_hr.md)
i [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/docker_hr.md).

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.7)
