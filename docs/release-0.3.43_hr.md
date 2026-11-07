# Simbioza 0.3.43 — slike ostaju unutar HTML sadržaja

Slike sada stanu u okvir HTML sadržaja, uključujući velike slike uvezene iz Markdowna i slike s fiksnom širinom u pikselima. Širina **100%** koristi raspoloživi prostor unutar tog okvira. Popravak čuva omjer slike, manje odabrane širine, poveznice i potpise; ne reže sliku niti povećava male slike.

## Jednako ponašanje u svim prikazima

HTML Editor **0.3.19** dijeli ista ograničenja medija između uređivanja dokumenta, objavljenih stranica, HTML polja u obrascima, HTML sadržaja anketa i novih samostalnih HTML izvoza. Istu zaštitu imaju upute prikazane bez Wikija. Slike u karticama, tabovima, harmonikama, tablicama i poveznicama također poštuju širinu pripadajućeg sadržaja i razmake.

Regresijski test preglednika obuhvaća velike izvorne slike, postotne i fiksne širine, potpise, ugniježđeni sadržaj i razmake u editoru, objavljenom prikazu i HTML izvozu bez poslužitelja, na desktopu i mobitelu. Izvorni HTML ostaje moguće uređivati; popravak mijenja pravila prikaza, a ne prepisuje spremljeni sadržaj.

## Nadogradnja

Koristite uobičajenu nadogradnju kroz GUI na pravilno podešenoj secured PHP-FPM instalaciji, odnosno dokumentirani CLI postupak na ostalim instalacijama. Demo koristi svoj instalirani pomoćni alat kojim upravlja administrator. Aktivne Demo i produkcijske instalacije ne nadograđuju se automatski.

Simbioza pri nadogradnji automatski osvježava zajednički CSS za medije koji koriste postojeće HTML stranice spremljene na datotečnom sustavu. Mijenja se samo taj stil: spremljeni HTML, revizije dokumenata i `theme.css` ostaju netaknuti. Projekt koji koristi generički modul HTML Editor može isto osvježavanje pokrenuti iz direktorija svoje aplikacije:

```sh
vendor/bin/hph editor-html:refresh-media-css
```

Nema novih migracija baze. HeartPhrame Framework ostaje **0.0.25**, neizmijenjen. Prije preuzete arhive HTML izvoza samostalne su datoteke: nakon nadogradnje napravite novi izvoz kako bi sadržavao ispravljeni stil.

Vidi [instalaciju](installation_hr.md), [Docker instalaciju](docker_hr.md) i [povijest izdanja](releases_hr.md).
