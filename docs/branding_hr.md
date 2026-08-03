# Vizualni identitet Simbioze

Simbioza je naziv aplikacije za zajedničko znanje koju pokreće HeartPhrame.

## Poruka

- Naziv: **Simbioza**
- Potpis: **Simbioza by HeartPhrame**
- Slogan: **Znanje koje živi zajedno.**
- Opis: **Zajednički prostor za znanje, suradnju i sadržaj koji raste s vašom
  zajednicom.**

## Znak i hero vizual

Znak spaja raka samca i moruzgvu u trobojnom crtežu nadahnutom kazalištem
sjena. Široka baza moruzgve pričvršćena je uz kućicu, a spirala kućice završava
malim srcem. Aktivna paleta Natural Dark koristi koraljnu `#FF8064` za
moruzgvu, zlatnu `#E9B84A` za kućicu i boju morske pjene `#72D4C8` za raka.

Cijeli branding paket nalazi se u `data/themes/simbioza/`, izvan javnog web
korijena. Direktorij `assets/` sadrži šest cjelovitih prozirnih hero PNG
datoteka veličine 1600 x 1600, šest pripadajućih vektorskih SVG datoteka te svih
šest varijanti kao aplikacijske PNG i SVG ikone veličine 512 x 512. Nijedna
datoteka nije izrezana iz kontaktne slike. Ponovljivi geometrijski master čuva
se u `source/`, a `theme-assets.json` sadrži dvojezične nazive, namjene,
dimenzije, veličine i SHA-256 kontrolne zbrojeve. Cijeli se skup ponovno izrađuje
naredbom:

```bash
php scripts/generate_simbioza_brand_assets.php
```

Aktivna tema `simbioza` odabire `hero-natural-dark.png` i
`icon-natural-light.png` kroz upravljane reference `@theme-assets/...`.
Aplikacija kroz rutu Theme modula isporučuje samo zatražene datoteke biblioteke;
ne duplicira biblioteku teme u `public` ili `vendor`.

`Preuzmi paket teme` uključuje samo vizual i ikonu koje tema koristi. `Izvezi
cijelu temu` uključuje cijeli direktorij `data/themes/simbioza`, zajedno s
nekorištenim varijantama i izvornim materijalom, pa kasniji uvoz vraća cijelu
biblioteku dostupnu za uređivanje.
