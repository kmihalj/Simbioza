# Simbioza 0.3.30 — brisanje anketa i sadržaj Igraonice

[Engleska verzija](release-0.3.30_en.md)

Stranice anketa sada imaju koš za brisanje u zaglavlju, kao obične stranice. Brisanje premješta anketu u oporavljivo smeće i zaustavlja prikupljanje; prihvaćeni odgovori, revizije i postavke ostaju dostupni za administratorski povrat. Vraćena prethodno objavljena anketa ostaje zatvorena dok je objavljivač izričito ne otvori. Rasporedi izvještaja nakon brisanja ostaju isključeni.

Radni nacrt može se zasebno odbaciti, uz očuvanje objavljene revizije i prihvaćenih odgovora. Odbacivanje potpuno neobjavljene ankete premješta je u smeće. Na radnje u pregledniku i API-ju primjenjuju se aktualne ovlasti, potvrda i provjera revizije; neovlašteni korisnici ne mogu brisati ni vraćati sadržaj. Administrator pronalazi obrisane ankete pod Postavke → Ankete → Opće postavke → Ankete · Smeće.

Urednici mogu zajednički uređivati nacrte anketa. Oznake Nacrt i Na pregledu razlikuju radne verzije u stablu područja. Čitatelji i ispitanici vide samo objavljenu reviziju i ne saznaju postoji li noviji nacrt. Obavijesti područja o objavi izvršavaju se nakon završetka izvorne transakcije, čime se izbjegavaju ugniježđene transakcije indeksa pri promjenama ankete.

Obrazac nove stranice početno prikazuje obvezno polje naslova na primarnom jeziku. Naslov upisan u to polje odmah omogućuje kreiranje i uređivanje stranice. Privatna DEMO pravila dopuštaju redovne radnje nad stranicama, anketama i komentarima u Igraonici, uz zaštitu isporučenih uputa i administrativnih postavki.

Kreiranje HTML stranice, spremanje nacrta, uređivanje i objava provjereni su administratorskim i uredničkim računom. DataTables uključen po izboru stranice provjeren je na objavljenom prikazu: sortiranje svih stupaca, brojeva i datuma, pretraga, straničenje, tipkovnica, engleske i hrvatske oznake te javni pristup. HTML izvoz čuva odabrane odobrene resurse. Obične tablice ostaju iste, a odobrene skripte sadržaja ne izvršavaju se u editoru ni pregledu nacrta.

Izdanje koristi Survey 0.3.5 i Workspace 0.3.4. Privatni DEMO dodatak nosi verziju 0.3.13. Nema nove migracije baze. Javna nadogradnja čuva instalirane opcionalne module i lokalne postavke. Vlasnik Dema najprije nadograđuje privatni dodatak dokumentiranom operaterskom naredbom; obične instalacije taj dodatak ne instaliraju. Vidi [instalaciju](installation_hr.md) i [ovisnosti modula](module-dependencies_hr.md).

Stilovi pretrage sada se poslužuju s ispravnom CSS vrstom sadržaja. Dijalog poveznice pamti zahtjev zatvaranja tijekom animacije otvaranja, pa brz unos ne ostavlja editor blokiranim. Provjere u pregledniku čekaju završetak navigacije prije rada na idućoj stranici. Popravke uključuju HTML Editor 0.3.11 i Workspace Search 0.3.2.
