# Doprinos projektu

[Engleska verzija](CONTRIBUTING.md)

Hvala što pomažete poboljšati Simbiozu. Dobrodošle su prijave grešaka,
ispravci dokumentacije, prijevodi, testovi i usmjerene izmjene koda. Doprinosi
se pregledavaju kada to dopušta vrijeme održavatelja; prijava ili zahtjev za
izmjenu ne jamče prihvaćanje ni rok odgovora.

## Prije početka

- Pretražite [postojeće prijave](https://github.com/kmihalj/Simbioza/issues).
  Za veće promjene prvo otvorite prijavu radi dogovora o opsegu.
- Ranjivosti prijavite privatno prema
  [sigurnosnoj politici](SECURITY_hr.md). Nemojte ih uključiti u javnu prijavu
  ili zahtjev za izmjenu.
- Ovaj repozitorij sastavlja aplikaciju. Moduli imaju zasebne repozitorije;
  pogledajte [matricu ovisnosti](docs/module-dependencies_hr.md). Izmjene
  modula šaljite u njihove matične repozitorije. HeartPhrame Framework je
  označena vanjska ovisnost, a ne dio razvoja ovog repozitorija.

## Priprema izmjene

1. Napravite vlastitu kopiju repozitorija i usmjerenu granu iz `main`.
2. Izmjene neka budu male, kompatibilne s podržanim verzijama PHP-a i bazama;
   za promjene ponašanja dodajte regresijske testove.
3. Dopunite zahvaćenu englesku i hrvatsku dokumentaciju ili prijevode. Nove i
   izmijenjene PHP metode trebaju slijediti dvojezični HR/EN PHPDoc projekta.
4. Ne pohranjujte pristupne podatke, konfiguraciju instalacije, korisničke
   podatke, sigurnosne kopije, lokalne Composer postavke ni testne artefakte.
5. Pokrenite donje provjere i opišite rezultate u zahtjevu za izmjenu.

## Provjere

Iz korijena repozitorija:

```bash
composer update --with-all-dependencies
composer check-platform-reqs
composer on-commit
```

Za izmjene koje prelaze granicu aplikacije i modula ili mijenjaju ponašanje u
pregledniku pokrenite i izolirani E2E skup. Stvara privremene podatke; nikada
ga ne usmjeravajte na produkcijsku bazu. Pogledajte
[uputu za E2E testiranje](docs/end-to-end-testing_hr.md).

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

U zahtjev za izmjenu uključite kratak opis problema, sažetak rješenja,
rezultate provjera i slike za vidljive promjene sučelja. Šaljite samo rad za
koji imate pravo dati doprinos. Prihvaćeni doprinosi distribuiraju se pod
[licencijom EUPL 1.2](LICENSE).
