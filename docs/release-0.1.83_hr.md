# Simbioza 0.1.83

Ovo izdanje uvodi javni katalog jezika Simbioze. Instalacijski program nudi objavljene
jezike i zahtijeva da barem jedan bude odabran. Hrvatski i engleski ostaju
ugrađeni jezici, a u postavkama se dodatni jezični paketi mogu instalirati,
uključiti, isključiti, ukloniti i kasnije osvježiti. Nadogradnja postojeće
instalacije zadržava njezine odabrane jezike. Preuzimanja paketa ograničena
su veličinom, provjeravaju se prema SHA-256 sažetku iz kataloga i validiraju
prije aktivacije.

Hrvatski tekst namijenjen korisniku u aplikaciji i održavanim modulima sada
je kanonski ključ prijevoda. Prikaz svakog instaliranog jezika polazi izravno
od tog ključa, bez posrednog prevođenja preko engleskog. Datumi i vremena
prikazani korisniku slijede odabrani jezik, dok pohranjene vrijednosti i
strojno čitljivi vremenski zapisi ostaju nepromijenjeni.

Javni katalog sadrži hrvatski, engleski, njemački, francuski, španjolski i
talijanski paket, svaki s višejezičnim nazivom i SVG zastavicom. Francuski,
španjolski i talijanski prijevod pripremljeni su uz pomoć AI alata; način
izrade i ograničenja jezične provjere opisani su u repozitoriju jezika.
Upute za instalaciju dopunjene su odabirom jezika, osvježavanjem kataloga i
postupkom izdvajanja samo novih nizova za prijevod.

Izdanje nema novih migracija ni promjena poslovnih podataka. Ograničenja
modula ažurirana su na označena izdanja koja prolaze CI. Nakon nadogradnje
putem sučelja otvorite postavke kako biste vidjeli dostupne jezike i instalirali
one koje želite koristiti.
