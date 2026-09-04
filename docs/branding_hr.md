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

## Ugrađene partnerske teme

Uz teme Simbioza, Srce SUP i Standard, aplikacija sadrži i teme `dabar` i
`aai`. Obje koriste mogućnosti Theme modula: dva logotipa u zaglavlju,
dekorativni hero SVG, zasebnu navigaciju te svijetle i tamne palete. Theme modul
podržava zasebnu širinu i najveću visinu ukrasa, njegove pomake te kontrolirano
prelaženje preko donjeg ruba hero područja.

Tema Dabar nalazi se u `data/themes/dabar/`. Koristi službeni Dabar logotip
lijevo, logotip Srce 55 desno i ilustraciju povećala u hero području. Svijetli
hero slijedi izvorni crveni gradijent `#D71635` - `#A01F23`, a tamna varijanta
koristi dublju crvenu paletu uz posebno prilagođene logotipe. Izvorne SVG
datoteke preuzete su s `https://dabar.srce.hr/`.

Tema AAI nalazi se u `data/themes/aai/`. Koristi službeni AAI@EduHr logotip
lijevo, logotip Srca desno i AAI banner u hero području. Svijetli hero slijedi
izvorni gradijent `#003567` - `#1F5EA0` - `#1F8CA0`, a tamna varijanta koristi
dublju plavu paletu uz posebno prilagođene logotipe. Izvorne SVG datoteke
preuzete su s `https://www.aaiedu.hr/` i `https://www.srce.unizg.hr/`.

Obje partnerske teme preuzimaju širine, oblik vala i preklapanje sadržaja iz
teme Simbioza. Njihovi hero ukrasi imaju zasebno zadanu najveću visinu i
vertikalni pomak kako bi veći dio ilustracije ostao vidljiv iznad donjeg dijela
vala, bez promjene širine sadržaja.

Svaki direktorij sadrži `theme-assets.json` s dimenzijama, veličinama i SHA-256
kontrolnim zbrojevima. `Izvezi paket teme` u samostalni paket uključuje spremljene
dimenzije, pomake i pravilo prelaženja ukrasa. `Izvezi cijelu temu` i njegov uvoz
prenose iste postavke i sve hero i logotip assete za obje varijante. Potpuni
backup site-a dodatno sprema cijele direktorije `resources/config/theme` i
`data/themes`, pa se iste vrijednosti i datoteke vraćaju kroz restore.
