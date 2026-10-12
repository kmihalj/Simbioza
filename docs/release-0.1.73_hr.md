# Simbioza 0.1.73

> Povijesna bilješka izdanja — nije aktualna uputa za instalaciju ni nadogradnju. Za čistu osnovu 0.3.0 koristite [instalaciju](installation_hr.md) i [aktualnu konfiguraciju](configuration_hr.md). Upute za nadogradnju starijih verzija u nastavku ostaju samo kao povijest.

Ovo izdanje dotjeruje upravljanje privitcima, nadogradnju administratorskog
izbornika i provjeru dostupnih izdanja komponenti.

## Uređivanje privitaka

Polja metapodataka i akcijski gumbi sada su prvi i najvažniji red kartice
privitka. Izvorni naziv datoteke, MIME tip, veličina, verzija, učitavač i
lokalizirano vrijeme nalaze se ispod njih u poravnatoj responzivnoj mreži.
Povijest datoteke ostaje zaseban sklop ispod podataka. Prikaz se preslaguje na
mobilnom zaslonu bez vodoravnog prelijevanja. Informacijski red koristi boje
aktivne teme pa ostaje jasno čitljiv i u svijetlom i u tamnom načinu.

## Postavke i novi moduli

Updater čuva sve zatečene administratorske postavke izbornika. Nova stavka koju
donese modul ili značajka dodaje se samo ako još ne postoji, i uvijek na kraj.
Time se u postojeće instalacije dodaje i **Setup**, bez preslagivanja ili
prepisivanja njihovih sadašnjih postavki.

Instalacija koja još koristi updater iz starijeg izdanja treba prije prve
nadogradnje na 0.1.73 preuzeti novi `update.php`. Time se nova logika spajanja
postavki izvršava već u istom prolazu nadogradnje.

## Provjera izdanja

Razvojne instalacije bez `composer.lock` sada pronalaze javne repozitorije iz
Composerova instaliranog inventara. Prolazno neuspjeli mrežni zahtjev ponavlja se
jednom, dok se stvarni trajni kvar i dalje jasno prikazuje samo uz zahvaćenu
komponentu.
