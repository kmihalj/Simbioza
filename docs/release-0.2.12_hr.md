# Simbioza 0.2.12

## Obvezno prijelazno izdanje updatera

Svaku instalaciju stariju od 0.2.12 **prvo izričito nadogradite na 0.2.12**:

```bash
php update.php --tag=0.2.12
```

Stari updater instalira ovo prijelazno izdanje dosadašnjim postupkom. Tek
sljedeće nadogradnje koriste novi postupak u kojem updater ima prednost.
Izravna nadogradnja starije instalacije na kasnije izdanje nije podržana:
ne preskačite ovaj korak. Prije nadogradnje uobičajeno sigurnosno kopirajte
bazu, postavke i korisničke datoteke.

## Updater prije aplikacije

CLI i pozadinski GUI posao prvo dohvaćaju najnovije stabilno izdanje i
provjeravaju verziju samostalnog updatera, protokol predaje i PHP sintaksu.
Noviji updater dobiva privatnu sigurnosnu kopiju i atomski se aktivira.
Zatim ostatak nadogradnje izvršava svježi PHP proces; prepisivanje već učitane
PHP datoteke više se ne smatra nadogradnjom aktivnog updatera.

Roditeljski proces čuva zaključavanje nadogradnje i prenosi napredak postojećem
GUI poslu. Predaja zadržava odabrani aplikacijski tag, jezik, deploy identitet i
stvarni izlazni kod. Aplikacijski kod, Composer paketi, konfiguracija i migracije
mijenjaju se tek nakon ovog koraka. Za osvježavanje updatera nisu potrebni
aplikacijski autoloader niti pristup bazi.

I izričito odabrani aplikacijski tag koristi updater iz najnovijeg stabilnog
izdanja. Sinkronizacija izvora i povrat aplikacijskog koda ne zamjenjuju ga
starijom kopijom. Zahtjevi za aplikacijska izdanja prije ovog prijelaza odbijaju
se. Nevaljan updater zaustavlja nadogradnju prije promjena aplikacije.
`--check` ostaje read-only, a `--updater-info` lokalno prikazuje metapodatke updatera.

Privatne kopije updatera nalaze se u `data/backups/updater/`. Privremeni rad
ostaje unutar instalacijskog `data/` i čisti se po završetku. Čuvaju se vlasnik,
grupa i prava datoteke; web-proces ne dobiva nova prava pisanja u kod. FPM
koristi postojeći ograničeni deploy helper, a mod_php i dalje CLI vlasnika koda.
Demo pod nadzorom hosta i za ovaj prijelaz mora zadržati kontrolirani postupak
početne kopije i automatskog reseta.

Izdanje ne premješta auth ni druge konfiguracije i ne dodaje migracije baze.
Buduće promjene `update.php` moraju povećati njegov zasebni `UPDATER_VERSION`,
a protokol predaje 1 mora ostati kompatibilan sa starijim updaterima.
