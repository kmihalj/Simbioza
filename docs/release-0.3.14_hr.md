# Simbioza 0.3.14 — jasan pregled nadogradnji u Setupu

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.14/docs/release-0.3.14_en.md)

Ovo izdanje poboljšava ekran nadogradnje i uključuje objavljeni **Email 0.3.2**
worker. HTML Editor i Menu ostaju **0.3.2**; Backup, Auth, API i Theme ostaju
**0.3.1**, ostali javni moduli **0.3.0**, Framework **v0.0.25** i updater
**1.0.8**. Postojeći tagovi se ne pomiču. Nema nove migracije baze niti vraćanja
konfiguracije na zadane vrijednosti.

## Promjene

- **Postavke → Setup i moduli** jasno ističe popis komponenti za nadogradnju,
  uključujući instalirane i dostupne verzije, čak i kada je noviji samo modul.
- Retci modula imaju izričitu oznaku nadogradnje i istaknuti rub. Stanje
  uključenosti/isključenosti ostaje zasebno. Predmemorirani rezultati renderiraju
  se bez JavaScripta, a automatska provjera osvježava popis bez tumačenja
  metapodataka kao HTML-a.
- Bitni podaci nadogradnje koriste obični tekst kartice umjesto dekorativnih
  prigušenih boja. Spremljene postavke teme ne mijenjaju se niti prepisuju.
- Email 0.3.2 dodaje `--max-messages=20` za najviše 20 pokušaja dostave po
  pokretanju workera, neovisno o internom `--batch-size`. Njegove EN/HR upute
  pokrivaju secured FPM, mod_php i zasebni Docker worker.
- Instalacijske upute, ugrađeni upravljani paketi uputa i Docker primjeri za
  Linux AMD64/ARM64 koriste **0.3.14**. Image uključuje Email 0.3.2; konfiguracija,
  podaci i PostgreSQL ostaju na postojećim trajnim volumenima.

## Nadogradnja

Na podešenom secured-FPM siteu u Setupu odaberite **Pokreni nadogradnju** i izdanje
**0.3.14**. Poboljšani ekran pojavljuje se nakon nadogradnje. Postojeći updater
zajedno nadograđuje aplikaciju i kompatibilne module, uz očuvanje privatnih
postavki i podataka. Ne treba reinstalacija niti zasebna nadogradnja Theme modula.

Nakon nadogradnje provjerite navodi li `vendor/bin/hph email help` opciju
`--max-messages` prije postavljanja ograničenog crona. Pogledajte
[upute za Email worker](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/0.3.2/docs/workers_hr.md).
Zakazani worker ne pokrećite tijekom održavanja aplikacije/baze.

Za Docker slijedite [upute za instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.14/docs/docker_hr.md),
zaustavite web/worker servise radi migracije i zajedno zamijenite njihov image.
Brisanje trajnih volumena nije korak nadogradnje.
