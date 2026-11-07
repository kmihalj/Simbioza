# Simbioza 0.3.36 — sve teme, galerije i trajni odabir tema

> Povijesna bilješka izdanja. Pogledajte [aktualne upute za instalaciju](installation_hr.md) i [Simbiozu 0.3.43](release-0.3.43_hr.md).

Izdanje uključuje svih **26 tema**: Orbit, prvu kolekciju od dvanaest tema i osam kreativnih tema za sport, školu, vrtić, film, umjetnost i zajednice. Svaka tema ima svijetli i tamni izgled. Modul Tema: **0.3.12**. Framework ostaje **0.0.25** i nije mijenjan.

## Instalacija i nadogradnje

Nedostajuće teme kolekcije uvoze se jednom. Postojeće teme, administratorske izmjene, aktivni odabir i način prikaza ostaju sačuvani. Trajna evidencija u `HPH_CONFIG_PATH/theme/collection.json` sprječava da sljedeće nadogradnje vrate uklonjene dodatne teme. Backup i restore sitea uključuju tu evidenciju. Jednako ponašanje vrijedi za nove instalacije, klasične CLI/GUI nadogradnje i trajne Docker instalacije.

[Preuzmite svih 26 tema, galeriju i pojedinačne ZIP-ove za uvoz](https://github.com/kmihalj/Simbioza/releases/download/0.3.36/Simbioza-0.3.36-themes.zip). Raspakirajte kolekciju pa u **Postavke → Tema → Uvezi cijelu temu** odaberite jedan `themes/<id>.zip`.

## Galerije i prikaz

- [Svih 26 svijetlih i tamnih pregleda](theme-gallery_hr.md), također u dokumentaciji modula Tema.
- Stranica **Simbioza** u korisničkim uputama sadrži preglede svih tema; slike su lokalni resursi aplikacije i dostupne su kroz postojeće Upute i bez Wikija.
- Hero postavke osam najnovijih tema koriste manju visinu na unutarnjim stranicama.
- Desktop breadcrumb ostaje ispod navigacije, tekst na tabletima ne prekriva slike, a mobilne kontrole zaglavlja po potrebi prelaze u novi red.

Javni Demo dobiva galeriju tema i posjetiteljski odabir teme kroz zasebno održavani Demo dodatak. Odabir se pamti samo u tom pregledniku do 30 dana. Ne mijenja globalne postavke i ne dodaje se običnim produkcijskim instalacijama.

## Provjere i nadogradnja

Uz integriranu provjeru i CI izdanja provjeravaju se uvoz, brisanje i ponovni uvoz, povrat nakon greške, izolacija zahtjeva i galerija. Privatne postavke i izmjene Frameworka nisu dio izdanja. Pravilno podešene secured FPM instalacije mogu koristiti **Postavke → Nadogradnja aplikacije**, a ostale klasične instalacije `php update.php` iz direktorija aplikacije. Prije nadogradnje sačuvajte sigurnosnu kopiju aplikacije.
