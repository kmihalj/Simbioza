# Simbioza 0.3.42 — potpune višejezične upute i samostalni moduli

> Povijesna bilješka izdanja. Pogledajte [aktualne upute za instalaciju](installation_hr.md) i [Simbiozu 0.3.44](release-0.3.44_hr.md).

Isporučeni priručnik sada ima **90 poglavlja na šest jezika**: hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom. Obuhvaća svakodnevno čitanje i uređivanje, područja, kalendare i sastanke, ankete, suradnju, teme, izbornike, administraciju, instalaciju i 123 API operacije. Poglavlja su organizirana prema radnji i ulozi. Hrvatski i engleski zadržavaju snimke vlastitog sučelja; četiri nova prijevoda koriste engleske snimke uz jasnu napomenu i prevedene opise.

## Upute u svakoj instalaciji

Instalacije s Wikijem zadržavaju upute kao izvorne stranice područja **Korisničke upute**. Instalacije bez Wikija iste objavljene sadržaje prikazuju preko stavke **Upute**, s navigacijom i snimkama. Ponuđeni jezici ovise o instaliranim i aktivnim jezicima aplikacije. Sadržaj dokumenta ima vlastitu oznaku jezika za pomoćnu tehnologiju, neovisno o jeziku sučelja.

Nadogradnja osvježava održavana poglavlja i njihovu hijerarhiju, čuva identitete, redoslijed i ovlasti stranica te nepovezane vlastite stranice, a nove prijevode dodaje bez sudaranja brojeva revizija. Paket uključuje aktualne snimke svijetle teme; provjerene su slike, poveznice među poglavljima i poveznice na naslove. Postojeći odabir jezika sučelja i postavke instalacije ostaju sačuvani.

## Korištenje modula u vlastitom PHP projektu

Svih **15 generičkih HeartPhrame modula** sada u svojim Git repozitorijima ima zasebne engleske i hrvatske **How-to** vodiče. Svaki sadrži potpuni mali HeartPhrame projekt sa samo nužnim ovisnostima, instalaciju i uključivanje modula, postavke, izvršive PHP primjere, očekivane rezultate i poveznice na detaljne reference. Za korištenje modula nije potrebna Simbioza. Pogledajte [popis razvojnih vodiča](module-dependencies_hr.md#korištenje-generičkih-modula-u-vlastitom-projektu).

Vodič HTML Editora zadržava potpuni primjer polja obrasca: urediti HTML u tekstualnom polju, primijeniti promjene, provjeriti i spremiti poslani sadržaj te ga prikazati i ponovno otvoriti bez gubitka podržanih elemenata. Te razvojne upute pripadaju dokumentaciji modula, odvojeno od instaliranog korisničkog priručnika.

## Dodatni popravci

- Nazivi kalendara, izbornika i svojstava područja prate odabrani jezik sučelja.
- Dohvat izbornika područja provjerava stvarne ovlasti upravljanja tim područjem.
- JSON API rute dosljedno prihvaćaju JSON objekt, uključujući prazan objekt, i odbijaju popise i skalarne vrijednosti na vrhu zahtjeva.
- Paginacija API-ja dnevnika poštuje zatraženu veličinu stranice.
- HTML izvoz područja čuva podržane stilove editora.
- Ažuriranje uputa osvježava promijenjene snimke uz tekst poglavlja.

## Nadogradnja

Izdanje aplikacije usklađuje nove verzije generičkih modula, Workspace **0.3.6**, Simbioza User **0.3.6** i jezične pakete **2026.11.07.1**. Framework ostaje **0.0.25**, nepromijenjen. Izdanje ne dodaje migracije baze.

Koristite uobičajenu GUI nadogradnju pravilno podešene secured PHP-FPM instalacije ili dokumentirani CLI postupak na ostalima. Demo ostaje pod upravljanjem održavatelja i koristi vlastitu instaliranu skriptu za nadogradnju. Izdanje ne nadograđuje pokrenuti Demo ni produkciju automatski.

Pogledajte [instalaciju](installation_hr.md), [priručnik](bundled-manual_hr.md) te [ovisnosti modula i razvojne vodiče](module-dependencies_hr.md).
