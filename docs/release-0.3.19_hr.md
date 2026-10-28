# Simbioza 0.3.19 — jasnije postavke anketa i nasljeđivanje adrese aplikacije

[Engleska inačica](release-0.3.19_en.md)

> Povijesna bilješka izdanja. Za aktualne upute pogledajte
> [instalacijske upute](installation_hr.md) i [Simbiozu 0.3.20](release-0.3.20_hr.md).

Izdanje uključuje **Survey 0.3.1**. Framework, Auth i verzije ostalih javnih
modula nisu mijenjani.

## Promjene

- Vanjska prijava na ankete preuzima pouzdani javni URL aplikacije ili URL
  poveznica iz postavki e-pošte. Zaseban Survey URL nije potreban kada ta adresa
  postoji. Prefiks aplikacije vrijedi u HTTP i CLI integracijama. Ranije izričito
  spremljene Survey adrese ostaju neobavezne napredne iznimke. Host zaglavlje
  zahtjeva ne određuje povratnu adresu.
- Opće postavke grupirane su prema namjeni. Vremena su u minutama/satima,
  veličine u MB, uz objašnjenja i točnu pretvorbu u postojeću pohranu. Tehničke
  mogućnosti početno su sklopljene; vrijednosti se ne zaokružuju prešutno.
- Neobavezni dokaz SAML izvora vidljiv je samo za proxy instance i njihove
  kartice. Testiranim podprofilima obično ne treba dodatni atribut izvora.
- Napredna pravila primjerima razlikuju filtre dopuštene autorima od obveznih
  administratorskih ograničenja. Primjeri ne uključuju pravila.
- Dopunjene su ugrađene administratorske upute EN/HR i svih šest prijevoda
  Survey sučelja. Biraju se samo instalirani, uključeni jezici.

## Nadogradnja

Na instalacijama sa zaštićenim FPM-om koristite uobičajenu GUI nadogradnju.
Instalirani Survey nadograđuje se na 0.3.1; instalacije bez Surveya zadržavaju
izbor modula. Nema novih migracija ni obveznih promjena providera. Postojeća
mapiranja, testovi, pravila, odgovori i namjerne URL iznimke ostaju sačuvani.
OIDC/OAuth2 provideri i dalje trebaju registriran prikazani callback.

Već pripremljeni Demo koristi instalirani privatni updater iz Demo direktorija.
Jednokratna priprema privatnog dodatka korištena za 0.3.18 više nije potrebna.
Updater prije nadogradnje vraća čistu početnu kopiju, a zatim snima novu čistu
kopiju za reset; posjetiteljske ankete se ne zadržavaju.

Docker instalacije koriste `ghcr.io/kmihalj/simbioza:0.3.19`. Pogledajte
[Docker uputu](docker_hr.md) i [ovisnosti modula](module-dependencies_hr.md).
