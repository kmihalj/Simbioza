# Contributing / Doprinos projektu

Thank you for helping improve Simbioza. Bug reports, documentation fixes,
translations, tests, and focused code changes are welcome. Contributions are
reviewed when maintainer time permits; opening an issue or pull request does
not imply acceptance or a response deadline.

Hvala što pomažete poboljšati Simbiozu. Dobrodošle su prijave grešaka, ispravci
dokumentacije, prijevodi, testovi i usmjerene izmjene koda. Doprinosi se
pregledavaju kada to dopušta vrijeme održavatelja; prijava ili pull request ne
jamče prihvaćanje ni rok odgovora.

## Before you start / Prije početka

### English

- Search [existing issues](https://github.com/kmihalj/Simbioza/issues). For
  larger changes, open an issue first to agree on scope.
- Report vulnerabilities privately as described in [SECURITY.md](SECURITY.md),
  never in a public issue or pull request.
- This repository assembles the application. Modules have separate repositories;
  see the [dependency matrix](docs/module-dependencies_en.md). Send module
  changes to their owning repositories. HeartPhrame Framework is an upstream
  tagged dependency, not part of this repository's development.

### Hrvatski

- Pretražite [postojeće prijave](https://github.com/kmihalj/Simbioza/issues).
  Za veće promjene prvo otvorite prijavu radi dogovora o opsegu.
- Ranjivosti prijavite privatno prema [SECURITY.md](SECURITY.md), nikada javnim
  issueom ili pull requestom.
- Ovaj repozitorij sastavlja aplikaciju. Moduli imaju zasebne repozitorije;
  pogledajte [matricu ovisnosti](docs/module-dependencies_hr.md). Izmjene modula
  šaljite u njihove matične repozitorije. HeartPhrame Framework je označena
  vanjska ovisnost, a ne dio razvoja ovog repozitorija.

## Prepare a change / Priprema izmjene

### English

1. Fork the repository and create a focused branch from `main`.
2. Keep changes small, preserve compatibility with supported PHP versions and
   database drivers, and add regression tests for behavior changes.
3. Update both English and Croatian documentation or translations affected by
   the change. New or changed PHP methods must keep the project's bilingual
   HR/EN PHPDoc convention.
4. Do not commit credentials, instance configuration, user data, generated
   backups, local Composer path-repository settings, or test artifacts.
5. Run the checks below and explain what you verified in the pull request.

### Hrvatski

1. Napravite fork i usmjerenu granu iz `main`.
2. Izmjene neka budu male, kompatibilne s podržanim verzijama PHP-a i bazama;
   za promjene ponašanja dodajte regresijske testove.
3. Dopunite zahvaćenu englesku i hrvatsku dokumentaciju ili prijevode. Nove i
   izmijenjene PHP metode trebaju slijediti dvojezični HR/EN PHPDoc projekta.
4. Ne pohranjujte pristupne podatke, konfiguraciju instalacije, korisničke
   podatke, sigurnosne kopije, lokalne Composer path-postavke ni testne artefakte.
5. Pokrenite donje provjere i opišite rezultate u pull requestu.

## Checks / Provjere

From the repository root / Iz korijena repozitorija:

```bash
composer update --with-all-dependencies
composer check-platform-reqs
composer on-commit
```

For changes across application and module boundaries, or browser-visible
behavior, also run the isolated end-to-end suite. It creates disposable test
data; never point it at a production database. See the
[E2E guide](docs/end-to-end-testing_en.md) / [E2E uputu](docs/end-to-end-testing_hr.md).

Za izmjene koje prelaze granicu aplikacije i modula ili mijenjaju ponašanje u
pregledniku pokrenite i izolirani E2E skup. Stvara privremene podatke; nikada
ga ne usmjeravajte na produkcijsku bazu.

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Please include a concise problem statement, implementation summary, test
results, and screenshots for visible UI changes in your pull request. Submit
only work you have the right to contribute. Accepted contributions are
distributed under the repository's [EUPL 1.2 license](LICENSE).

U pull request uključite kratak opis problema, sažetak rješenja, rezultate
provjera i slike za vidljive promjene sučelja. Šaljite samo rad za koji imate
pravo dati doprinos. Prihvaćeni doprinosi distribuiraju se pod
[licencom EUPL 1.2](LICENSE).
