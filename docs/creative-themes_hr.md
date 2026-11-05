# Teme za sport, obrazovanje i kreativne zajednice

[English version](creative-themes_en.md)

Ovih osam tema dopunjuje [prvu kolekciju tema](theme-collection_hr.md). Svaka ima
svijetlu i tamnu paletu te pet zasebnih boja hero prijelaza. Raspored, boje,
fontove, ilustracije, kartice i sjene možete naknadno uređivati u postavkama teme.

Zaglavlja koriste srednju visinu na naslovnici i kompaktan prikaz na unutarnjim
stranicama. Manje ilustracije i razmaci ostavljaju više mjesta za sadržaj.

| Tema | Izgled i namjena |
| --- | --- |
| Stadion | Fotografija atletske staze u zalasku sunca; za sportske klubove i natjecanja. |
| Učionica | Kolaž knjiga, geometrije i svemira; za škole, nastavnike i učenje. |
| Mali svijet | Sunce, duga i kućice od papira; za vrtiće i obiteljske zajednice. |
| Kinoteka | Filmska vrpca i svjetlost projektora; za film, kulturu i priče. |
| Manga | Izvorni anime junak i stripovski kadrovi; za strip, igre i kreativne klubove. |
| Atelje | Akvarel na toplom papiru; za likovne radionice i umjetničke portale. |
| Planina | Fotografija planinskog jezera u svitanje; za planinare i aktivnosti na otvorenom. |
| Festival | Šarene vrpce, konfeti i ljubičasto-tirkizni prijelaz; za događanja i glazbu. |

## Odabir i uvoz

U Simbiozi otvorite **Postavke → Tema**, odaberite temu u polju **Aktivna tema** i
spremite. Naziv teme prati jezik sučelja, primjerice *Učionica / Classroom*.
Trenutačno odabrana tema ne mijenja se dodavanjem kolekcije.

Za drugu instalaciju koristite pojedinačni paket
`resources/installation/theme/simbioza-<naziv>.zip` u obrascu **Uvezi cijelu temu**.
Primjer: `simbioza-mali-svijet.zip`. Svaki paket sadrži konfiguraciju, slike i
kontrolne sažetke. Teme su opcionalne; nadogradnja ne zamjenjuje vaše postojeće teme.

## Ilustracije i izvori

Sedam rasterskih ilustracija i fotografija izrađeno je ugrađenim alatom
`image_gen`. Nisu fotografije određenog stvarnog objekta ili događaja. Festival
koristi izvorne statične SVG vrpce. Znak Simbioze ostaje nepromijenjen.
Teme ne učitavaju udaljene slike, fontove ni dodatne skripte.

Izvorne rasterske datoteke i korišteni opisi nalaze se u
`resources/theme-collection/creative/artwork/`. Svaki paket uključuje pripadajući
opis u `source/artwork.json`. Palete i manifeste možete ponovno izraditi naredbom
`python3 scripts/generate_creative_themes.py`; ona piše u zaseban izlazni direktorij
i ne mijenja aktivne postavke. Konačne konfiguracije nalaze se u
`resources/theme-collection/creative/catalog.json`.
