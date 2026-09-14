# Simbioza 0.1.70

Uključuje sve popravke i isporučene upute i temu iz 0.1.69 te Calendar 0.1.16.

## Višestruki odabir sudionika

Pri planiranju sastanka i uređivanju zadanih sudionika kalendara sada se može
označiti više osoba u pretraživom dropdownu i dodati ih jednom akcijom
**Dodaj odabrane**. Odabrane osobe ostaju sačuvane tijekom pretraživanja i
učitavanja sljedeće stranice rezultata. Odabir se može ukloniti checkboxom ili
oznakom uz ime; već dodane osobe označene su s **dodano** i ne dodaju se ponovno.
Korisnici se i dalje dohvaćaju postupno, najviše 25 po zahtjevu.

Birač podržava tipkovnicu i dodir, uski i vodoravni mobilni prikaz te stilove
teme i samostalni Bootstrap prikaz. Pretraga ne otvara automatski mobilnu
tipkovnicu, a Enter u pretrazi ne šalje obrazac sastanka. U modernim preglednicima
birač koristi gornji sloj prikaza kako ga fiksirano zaglavlje ne bi prekrilo.
Odabir vlasnika i korisnika u ACL retcima ostaje pojedinačan.

## Slike u isporučenim uputama

Popravljen je updater za nadogradnje pokrenute s `sudo`: datoteke i direktoriji
uvezenih privitaka sada nasljeđuju vlasnika i grupu svojega runtime spremišta.
Ne proširuju se prava pristupa niti se mijenjaju privatni backup artefakti.
Popravak se primjenjuje i na već uvezenu uputu za sastanke kada je paket upute
nepromijenjen, bez ponovnog uvoza, promjene sadržaja ili dodavanja povijesti.
Popis privitaka u uputi ostaje namjerno skriven.

Apps-test nije automatski nadograđen. Administrator pokreće:

```bash
cd /data/www/simbioza && sudo php update.php
```
