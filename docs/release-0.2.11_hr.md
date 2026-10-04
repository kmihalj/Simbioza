# Simbioza 0.2.11

## Video prijelaza iz Confluencea

Engleski i hrvatski README sada pri vrhu predstavljaju opcionalni modul
Confluence Import i sadrže video koji se može reproducirati. Odobreni video
prikazuje izvoz Confluence prostora, uvoz XML ZIP arhive i pregled rezultata.
Ima naraciju na britanskom engleskom, engleske titlove i zamagljene privatne
identitete. Objašnjava i da ugrađeno kazalo Simbioze zamjenjuje Confluenceov
makro za kazalo.

Dvojezična korisnička uputa za Confluence import sadrži isti video i naslovnu
sliku kao lokalne privitke stranice. Nove instalacije dobivaju ih u početnom
području korisničkih uputa, a postojeće kroz redovitu nadogradnju. Reprodukcija
iz instalirane upute ne ovisi o vanjskom video-poslužitelju.

## Sigurno ažuriranje upute

Updater sada pojedinačno upravlja i uputom za Confluence import, uz upute za
instalaciju i sastanke. Čuva identitet stranice, položaj i ovlasti, ne mijenja
ostale stranice te prije zamjene sprema privatnu povratnu arhivu. Nepromijenjeni
paket ne uvozi se dvaput.

Nema novih migracija baze niti novih verzija aplikacijskih modula. Izdanje ne
instalira Confluence Import tamo gdje ga nema, ne ponavlja uvoz i ne mijenja
uvezeni poslovni sadržaj. Kao i inače, prije nadogradnje izradite sigurnosnu
kopiju instalacije.

Zasebno održavani privatni demo modul također nosi video i lokalizirane opise
za zaključani članak o prijelazu s Confluencea. Urednički sadržaj osvježava se
kroz postupak upravljanja početnom kopijom dema, nikada automatskim
prepisivanjem sadržaja tijekom Composer nadogradnje.
