# Održavanje dokumentacije

Projekt čuva razvojne reference u Gitu, a korisničke upute unutar Simbioze.
Oboje mora opisivati ponašanje stvarnog koda. Framework je vanjska ovisnost;
ovi alati za održavanje ne mijenjaju njegov izvorni kod.

## Izvor korisničkih uputa

Registar poglavlja jest `resources/installation/manual-sources.json`. Većina
izvora nalazi se u `docs/user-guide/*_en.md` i `*_hr.md`; zajednički pregled,
instalacija i galerija ostaju u `docs/`. Javni privitci s provjerama integriteta
zapisani su u `manual-assets.json`, a slike su u `docs/user-guide/images/`.
Engleski i hrvatski ostaju odvojeni. Na slici
koristite odgovarajući jezik sučelja i provjerite prikazuje li slika opisanu
radnju i rezultat. Primjeri moraju biti prenosivi: bez poveznica na razvojne
ankete, privatna područja ili lokalne datoteke.

Ista se poglavlja pakiraju kao područje **Korisničke upute** za Wiki
instalacije. Bez Wikija stavka **Upute** prikazuje isti ugrađeni sadržaj kao HTML
s navigacijom po poglavljima. Za manje instalacije ne održava se drugi priručnik
niti se ručno uređuju generirani zapisi paketa.

Za novo poglavlje registrirajte oba jezika i njegov smještaj; nove javne slike
dodajte u registar privitaka s njihovim sažecima. Generiranje traži instalirane
razvojne ovisnosti i Pandoc. Nakon izmjene izvora obnovite i provjerite priručnik:

```bash
php scripts/sync_user_guides.php --apply
php scripts/sync_user_guides.php --check
```

`--apply` ažurira generirane datoteke izdanja iz izvora u Gitu; `--check` otkriva
razlike. Održavanje izvora ne objavljuje izdanje niti daje odobrenje za zamjenu
živog administratorskog sadržaja. Pregledajte razlike pa koristite redovni,
kontrolirani postupak instalacije/nadogradnje. [Upute za HTML Editor u polju
obrasca](html-editor-forms_hr.md) održavaju se u repozitoriju modula za razvojne
programere; nisu dio korisničkog priručnika u aplikaciji.

## Jezik i primjeri

- `README.md` i `docs/*_en.md` pišu se engleski; `README_hr.md` i `*_hr.md` hrvatski.
- Komentari koda i PHPDoc metoda imaju cjelovit blok `EN:`, prazan redak
  komentara pa cjelovit blok `HR:`. Anotacije tipova ostaju izvan tog teksta.
- Cjeloviti PHP primjeri počinju s `<?php` i moraju proći provjeru sintakse.
  Jasno označite isječke konfiguracijskih polja i točke umetanja u raspored.
- JSON primjeri moraju biti valjani objekti/liste. Opisni `_documentation.en`
  i `_documentation.hr` u distribucijskom primjeru nisu postavke.
- Ne kopirajte `.dist` preko živih privatnih postavki. Pri dodavanju očuvajte
  ostale module, servise aplikacije i postojeće vrijednosti.
- Ne uključujte lozinke, tokene, privatne identitete ili stvarne odgovore anketa.

## Automatizirane provjere

Pokrećite ih u izdvojenom izvornom checkoutu s njegovim razvojnim ovisnostima:

```bash
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
php scripts/audit_module_translations.php
php scripts/sync_user_guides.php --check
```

Provjeravaju označene PHP komentare/PHPDoc, opis obaveznih ovisnosti, parove
EN/HR dokumenata, lokalne poveznice i sidra naslova, cjelovite PHP/JSON primjere,
jezične ključeve i usklađenost generiranog priručnika. Ne dokazuju postoji li
udaljena web-stranica, odgovara li slika novom sučelju ili zadovoljava li primjer
pravila ovlasti prilagođene aplikacije. To provjerite prema kodu i stvarnom toku.

`php scripts/normalize_bilingual_comments.php` radi probnu provjeru bez upisa.
Izričita opcija `--write` služi održavanju izvora: sažeci izvršivih tokena i
potpisi PHPDoc anotacija moraju ostati isti. Pregledajte razlike; alat izuzima
Framework, vendor pakete, privatne postavke i runtime podatke.

[Instalacija](installation_hr.md), [konfiguracija](configuration_hr.md),
[Docker](docker_hr.md) i [end-to-end provjere](end-to-end-testing_hr.md) aktualne
su operativne reference. Bilješke izdanja opisuju svoje povijesno izdanje,
a ne današnji instalacijski postupak. Razvojni planovi i jednokratni zapisnici
prihvata ne pripadaju objavljenom priručniku.
