# Simbioza 0.1.98

Ovo korektivno izdanje prilagođava sinkronizaciju aplikacijskih datoteka i
povratak nadogradnje direktorijima u vlasništvu namjenskoga PHP-FPM korisnika.
Nadograđivač više ne pokušava kao zaseban deploy korisnik mijenjati vrijeme
izmjene tih direktorija. Vlasništvo, prava, privatne postavke i podaci aplikacije
i dalje ostaju sačuvani.

Izdanje uključuje i zaštitu istodobnog uređivanja te odabir jezika preglednika
iz izdanja 0.1.97. Nema migracija baze. Redovna nadogradnja kroz sučelje ili
naredbeni redak dovoljna je gdje već instalirani nadograđivač može pisati
datoteke izdanja. Popravak se primjenjuje tek kada se instalira novi `update.php`:
stariji CLI nadograđivač koji je već stao na pogrešci `utimensat` može prije
ponovnog pokušaja zahtijevati jednokratno usklađivanje vlasništva pogođenog
direktorija od administratora poslužitelja. Najprije provjerite da aplikacija
radi i da je izašla iz načina održavanja. Ne pretpostavljajte da je neuspjeli
povratak potpuno obnovio sve datoteke bez provjere instalacije.
