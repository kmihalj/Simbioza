# Simbioza 0.2.9

Opcionalni modul Confluence Import nadograđen je na 0.1.42.

Datumi zapisani samo u atributu `datetime` praznog Confluence elementa `time` sada postaju vidljiv tekst prije čišćenja HTML-a. Time se čuvaju datumi u tablicama, odlomcima i podržanim makroima s bogatim sadržajem, uključujući nakon uređivanja i spremanja uvezene stranice. Postojeće vidljive oznake datuma ostaju sačuvane; vrijednosti datuma i vremenske zone ne mijenjaju se.

Starije verzije privitaka sada pronalaze binarne datoteke prema izvornom logičkom identifikatoru privitka. I dalje su podržani exporti koji koriste identifikator povijesnog zapisa, a točna tražena verzija ima prednost pred novijom zamjenskom verzijom. Time se uklanjaju pogrešne prijave da stariji privitak nije pronađen, iako postoji u arhivi.

Regresijski testovi pokrivaju oba popravka u modulu i integriranom postupku importa i ponovnog importa kroz preglednik. Nema novih prijevoda ni migracija baze.

Prije nadogradnje načinite obnovljivu sigurnosnu kopiju. Postojećim instalacijama bez Confluence Importa on se ne dodaje automatski. Instalirane se kopije nadograđuju uobičajenom nadogradnjom aplikacije/modula. Za vraćanje datuma koji nedostaju u ranije uvezenom sadržaju nakon nadogradnje ponovite import izvornog ZIP-a; sama nadogradnja ne prepisuje postojeće stranice. Prije potvrde zamjenskog importa provjerite mapiranje identiteta i ovlasti.
