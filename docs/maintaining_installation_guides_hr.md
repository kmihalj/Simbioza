# Održavanje instalacijskih uputa i cijelog priručnika

[Engleska verzija](maintaining_installation_guides_en.md)

Potpuni javni priručnik ima **90 poglavlja na hrvatskom, engleskom, njemačkom, španjolskom, francuskom i talijanskom**. Mjerodavan popis izvora je `resources/installation/manual-sources.json`; većina prevedenih poglavlja nalazi se pod `docs/user-guide/`. `resources/installation/manual-assets.json` povezuje korištene snimke i ilustracije s održavanim datotekama i kontrolnim sažecima. Jezike održavajte odvojeno, uz očuvanje korisnih slika, prenosivih poveznica i postojećih identiteta stranica.

## Odaberite pravi izvor

Četiri uvodne/instalacijske stranice zadržavaju postojeće izvore izravno pod `docs/`:

| Objavljena stranica | Izvori pod `docs/` |
| --- | --- |
| Simbioza | `simbioza_overview_en.md` / `simbioza_overview_hr.md` |
| Instalacija | `installation_*` i `modularity_*` |
| Apache mod_php instalacija | `installation_mod_php_*` |
| PHP-FPM instalacija | `installation_fpm_*` i `installation_fpm_secured_*` |

Ovdje `*` označava odvojene datoteke `hr.md`, `en.md`, `de.md`, `es.md`, `fr.md` i `it.md`. Svaki instalacijski postupak je cjelovit; čitatelj ne mora tražiti preduvjete u drugom postupku. Stranica PHP-FPM sadrži obični i secured FPM uz sidra `#ordinary-fpm` i `#secured-fpm`.

Ostala poglavlja dolaze iz `docs/user-guide/`: stranice i područja, kalendari i sastanci, ankete, suradnja, profil, pristupačnost, teme i izbornici. Odvojeni su postupci za autore i čitatelje, 15 administratorskih poglavlja te 12 API poglavlja za 123 konkretne operacije. Ugradnja HTML Editora u drugu aplikaciju dokumentirana je zasebno u repozitoriju tog modula i nije poglavlje instaliranih korisničkih uputa. Prije uređivanja pogledajte manifest: održavana kopija na drugoj putanji nije automatski izvor paketa.

## Jedan manifest određuje cijelu hijerarhiju

`manual-sources.json` određuje stabilni `slug`, roditeljski `parent`, naslov i
popis izvora za svaki jezik. Novo poglavlje dodajte ovdje, uz oba prijevoda.
`BundledGuideIndex` provjerava jedinstvene oznake, dopuštene putanje izvora,
postojanje roditelja i odsutnost ciklusa te raspoređuje roditelje prije djece.
Nevaljani postojeći manifest zaustavlja postupak; ne prešućuje poglavlja.

Generator i `BundledAssetsUpdater` koriste isti potpuni indeks. Povijesni
`GUIDE_PAGES` nije novi paralelni popis za ručno dopunjavanje: zadržava stare
ključeve potvrda i referenci oporavka te služi starijim paketima bez manifesta.
Za roditelje se koriste stvarni UUID-ovi iz arhive, uključujući postojeće
povijesne identitete; ne izvodite im nove UUID-ove iz naziva.

Pri nadogradnji se usklađuje `parent_id` upravljanih poglavlja, uz očuvanje
njihova UUID-a, postojećeg redoslijeda i ACL-a. Nepovezane korisničke stranice
ne reorganiziraju se. Ponovljena primjena istog manifesta ne smije stvarati
duplikate ni ponavljati već dovršeno premještanje. Promjenu roditelja provjerite
i u početnom arhivu i u pojedinačnim paketima.

## Obnovite i provjerite sva poglavlja

Iz direktorija aplikacije s instaliranim Composer ovisnostima:

```sh
php scripts/sync_user_guides.php --apply
php scripts/sync_user_guides.php --check
php scripts/sync_installation_guides.php --check
```

Potpuni generator koristi popise izvora i privitaka, obnavlja javni početni paket cijelog područja i pojedinačne pakete upravljanih stranica te uključuje postojeći postupak instalacijskih/preglednih uputa. To je redovna naredba nakon revizije priručnika. Čuvaju se identiteti stranica/dokumenata, prava, potrebne aktualne verzije privitaka i njihove datoteke; roditeljske veze prate potpuni manifest. Paketi sadrže javne upute, nikada privatnu konfiguraciju, žive ankete ni odgovore ispitanika.

`--apply` treba Pandoc (`brew install pandoc` ili `sudo apt-get install pandoc`) i priprema arhive u izdvojenom radnom direktoriju. Ne ažurira aktivnu bazu. Provjere rade offline, otkrivaju nesklad izvora i paketa i ne trebaju Pandoc. Pokrenite i testove uputa/arhiva i instalacije; podudaranje sažetka izvora ne dokazuje čisti uvoz, filtriranje jezika i ispravne slike. Pregledajte oba jezika u Wikiju i kroz `/guides` bez Wikija, uz provjeru javnih slika i poveznica kao gost.

## Održavanje samo instalacijskih uputa

Za promjenu ograničenu na četiri početne stranice ostaje uži alat:

```sh
php scripts/sync_installation_guides.php --apply
php scripts/sync_installation_guides.php --check
```

Ažurira **pet arhiva**: cijeli početni `korisnicke-upute.zip` i četiri pojedinačna paketa stranica. Ne obnavlja sva ostala poglavlja priručnika. Prije završetka revizije koja uključuje `docs/user-guide/` ili njegove privitke koristite potpuni generator.

Šifrirani HTML ne uređujte neovisno o izvorima. Ako isporučenu uputu promijenite izravno u razvojnom Wikiju, prenesite izmjenu u pripadajući Markdown prije pakiranja; inače je sljedeća upravljana nadogradnja može zamijeniti. Javna fraza početnog paketa nije tajna i ne smije štititi privatne operativne sigurnosne kopije.

## Instalirani siteovi i izdanja

Razvojni direktorij može sadržavati noviju dokumentaciju od instaliranog izdanja. Promjena Markdowna ili obnova paketa sama ne mijenja pohranjene stranice. Nova instalacija uvozi svoje isporučene pakete; updater kroz `BundledAssetsUpdater` osvježava sva upravljana poglavlja i njihovu roditeljsku hijerarhiju iz manifesta, uz očuvanje identiteta, prava, postojećeg redoslijeda i nepovezanih stranica. Bez Wikija `/guides` izravno čita isporučeni početni paket cijelog područja.

Poveznice u arhivama relativne su aplikaciji. Instalacija/nadogradnja dodaje prefiks cilja točno jednom, uključujući ugniježđene putanje. Usklađivanje popravlja poznate poveznice ugrađenih uputa s ponovljenim prefiksom ili izvornom adresom samo kada isporučeni cilj lokalno postoji. Ne preusmjerava nepovezane vanjske poveznice ili primjere koda niti zamjenjuje postavke teme, identitete stranica, prava ili tijek objave.

Lokalno osvježavanje dokumentacije vrijedi samo za odabranu razvojnu instalaciju. Ostale dobivaju obnovljene javne pakete redovnom nadogradnjom. Objavljene arhive izdanja ostaju povijesne snimke; nemojte premještati postojeći tag radi zamjene njihove dokumentacije.
