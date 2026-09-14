# Simbioza 0.1.71

Uključuje sve popravke, temu i dvojezične upute iz 0.1.70 te Calendar 0.1.17.

## Zaseban pregled Mojih sastanaka

Klik na **Moji sastanci** u popisu kalendara otvara zaseban kalendarski pregled,
bez događaja iz ostalih kalendara. Prikazuje sastanke prijavljenog korisnika
i kada nije pretplaćen na izvorni kalendar sastanka. Klik na događaj, uključujući
njegovu ikonu, otvara detalje sastanka i dopuštene radnje.

Virtualni kalendar ostaje prikaz, a ne novo mjesto za upis ili kopija događaja.
Uređivanje je i dalje vezano uz izvorni sastanak i njegove ovlasti. CalDAV
kolekcija `my-meetings` ostaje dostupna samo za čitanje.

## Čitljiv termin i status

U detaljima sastanka datum, vrijeme i status izdvojeni su u istaknut okvir.
Datum i vrijeme prate jezik sučelja, primjerice **utorak, 15. rujna 2026.**
i **10:00 – 11:00** na hrvatskom, odnosno engleski oblik datuma i vremena.
Lokaliziran je i popis sastanaka. Višednevni termini prikazuju početni i završni
datum, a cjelodnevni događaji oznaku **Cijeli dan**.

Okvir se prilagođava mobilnom prikazu, koristi boje teme i zadržava kontrast
u svijetloj i tamnoj paleti. Radi i bez modula Tema. Pohranjeni termini i
vremenske zone sastanaka ne mijenjaju se.

## Tiši updater

Prilikom dohvata release taga više se ne ispisuje Gitov savjet o stanju
`detached HEAD`. Postavka vrijedi samo za tu radnju; greške ostaju vidljive,
a globalne Git postavke ne mijenjaju se.

Postojeća isporuka teme i uputa te popravak vlasništva slika u uputama ostaju
uključeni. Popis privitaka u uputama ostaje namjerno skriven.

Apps-test nije automatski nadograđen. Administrator pokreće:

```bash
cd /data/www/simbioza && sudo php update.php
```
