# Provjere dokumentacije i komentara

Pravila vrijede za aplikaciju 0.3.0 i njezinih 18 javnih modula. Framework je
uzvodni kod i ovi ga alati ne prepisuju.

## Pravila jezika i primjera

`README.md` i `docs/*_en.md` sadrže engleski tekst; `README_hr.md` i
`docs/*_hr.md` hrvatski. Komentari koda i PHPDoc metoda imaju jedan cjelovit
blok `EN:`, prazan redak komentara, pa jedan cjelovit blok `HR:`. Jezici se ne
izmjenjuju po retcima niti se stavljaju u isti redak. Anotacije tipova i
direktive alata ostaju izvan prevedenog teksta. Rječnici prijevoda i višejezične
JSON oznake menija podatci su aplikacije, ne tekst dokumentacije.

Cjeloviti PHP primjeri počinju s `<?php`; sintaksa se provjerava bez izvršavanja.
Stavke nizova i točke umetanja rasporeda isječci su, ne zamjenske datoteke
aplikacije. JSON primjeri cjeloviti su valjani objekti/liste. Pri dodavanju opcija
ili uključenih modula očuvajte postojeće postavke aplikacije.

JSON `.dist` primjeri opisuju opcije u odvojenim nepraznim odjeljcima
`_documentation.en` pa `_documentation.hr`. Primjer registra skripti koristi
jednako vrijedne odjeljke `_description_en` pa `_description_hr`. To su neaktivni
opisi, ne postavke. Kopirajte samo potrebne primjere na dokumentirano odredište
`HPH_CONFIG_PATH/<modul>/`, nikada preko privatnih postavki.
`.dist` se ne učitava automatski. Vidi [konfiguraciju](configuration_hr.md).

## Pokretanje provjera

Iz izvornog repozitorija aplikacije:

```bash
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
php scripts/audit_module_translations.php
php scripts/normalize_bilingual_comments.php
```

Prvi provjerava stvarne označene PHP komentare u izvoru vidljivom Gitu,
uključujući predloške/testove, te dvojezičnu dokumentaciju metoda produkcijskog
koda. Dug neposredni PHPDoc je valjan. Stringovi s primjerima nisu komentari i
ne prepisuju se. Drugi provjerava rano navedene obavezne Composer ovisnosti u
README-ju, parove dokumenata, relativne poveznice na datoteke, sidra naslova,
cjelovite PHP/JSON primjere i opise početnih primjera. Ne dohvaća udaljene stranice
niti dokazuje primjenjivost svakog primjera u prilagođenoj aplikaciji. Treći
provjerava pokrivenost jezičnih ključeva aplikacije/modula.

Zadnja je naredba **probna provjera bez upisa**. Samo za održavanje izvornog koda
dodajte `--write` za uređivanje odgovarajućih PHP komentara. Svaka izmjena traži
jednak sažetak izvršivih tokena i očuvane potpise PHPDoc anotacija; izvještaji idu
u `build/a5/`. Framework, vendor paketi, ignorirane privatne postavke i podatci
isključeni su. Pregledajte razlike i potom ponovite provjere. JavaScript i CI
komentari slijede isto jezično pravilo, ali ovaj PHP formatter ne parsira te jezike.

## Aktualne upute i povijest

[Instalacija](installation_hr.md), [konfiguracija](configuration_hr.md) i
[Docker](docker_hr.md) opisuju aktualni raspored čiste instalacije. Starije bilješke
izdanja čuvaju izvorne pojedinosti uz izričitu povijesnu napomenu; nisu upute za
instalaciju/nadogradnju 0.3.0. [Audit prije releasea](pre-release-audit-0.3.0_hr.md)
aktualni je nastavak i popis provjera prije objave. Prolaz ovih provjera nije
odobrenje izdanja.
