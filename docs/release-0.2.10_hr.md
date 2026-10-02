# Simbioza 0.2.10

Ovo izdanje usklađuje HTML Editor 0.1.39, opcionalni modul Confluence Import
0.1.43 i dopunjene prijevode u svih šest podržanih jezika.

## Izričit odabir povijesti privitaka

Confluence import zadano prenosi samo aktualne verzije privitaka. Ranije verzije
prenose se samo kada je odabrana **Povijest stranica i privitaka**. Isključena
opcija više ne uvozi povijest privitaka.

Završni sažetak i trajni izvještaj razdvajaju aktualne privitke od povijesnih
verzija. Neuspješan prijenos stare verzije jasno se razlikuje od nedostajuće
aktualne datoteke. Jasniji izvještaj dobivaju i ranije završeni importi, bez
izmjene njihova uvezenog sadržaja ili pohranjenih zapisa posla.

## Izvorni prikaz slika

Uvezene slike zadržavaju širinu ili visinu koju je zadao Confluence, proporcije
te središnje ili desno poravnanje. Editor čuva te postavke pri otvaranju i
spremanju stranice. Na uskom ekranu slike se i dalje proporcionalno smanjuju,
a autor može izričito odabrati postojeće postotke širine u editoru.

## Nadogradnja i provjera

Regresijski testovi pokrivaju import bez povijesti i s njom, zamjenski import,
preuzimanje privitaka, izvještaje i sigurno zadavanje dimenzija slika. Stvarna
NR arhiva dodatno je uvezena u pregledniku u zasebnoj testnoj instalaciji:
246 stranica i 378 aktualnih privitaka, bez grešaka prijenosa i bez pokušaja
uvoza 61 stare verzije.

Prije nadogradnje izradite sigurnosnu kopiju iz koje se podaci mogu vratiti.
Nema novih migracija baze podataka. Postojećim instalacijama bez modula
Confluence Import on se ne dodaje automatski. Uobičajena nadogradnja aplikacije
nadograđuje instalirane primjerke zajedno s potrebnim izdanjem editora i
osvježava instalirane jezične pakete.

Nadogradnja ne prepisuje postojeće uvezene stranice niti ponavlja import. Za
primjenu poboljšanog prikaza slika na postojeći sadržaj ponovite import iz
izvorne ZIP arhive nakon nadogradnje. Prije potvrde provjerite odabir zamjene,
mapiranje identiteta i prava. Povijest uključite samo ako trebate stare verzije.
