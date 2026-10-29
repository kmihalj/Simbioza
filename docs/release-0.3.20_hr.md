# Simbioza 0.3.20 — cjelovita aplikacija uz modularnu prilagodljivost

[English version](release-0.3.20_en.md)

> Povijesna bilješka izdanja. Za aktualne upute pogledajte
> [instalacijske upute](installation_hr.md) i [Simbiozu 0.3.22](release-0.3.22_hr.md).

Simbioza povezuje stranice, Wiki, kalendare, ankete i suradnju u jednoj cjelovitoj aplikaciji. Modularnost HeartPhrame frameworka omogućuje i uže instalacije HTML weba, Kalendara, Wikija ili Anketa za one kojima ne trebaju sve funkcionalnosti. Auth, ORM, Menu, Theme, Backup i Audit čine obaveznu osnovu.

## Promjene

- Dodavanje Područja postojećem HTML webu zadržava samostalne stranice i njihove
  adrese. Administrator ih može izričito premjestiti s prijevodima, poviješću i
  privitcima, uz popravljene poveznice i prava odredišnog područja.
- Početna i O aplikaciji koriste sadržajne kartice čiji akcent i sjenu određuje tema.
- Početna i O aplikaciji objašnjavaju dostupne kombinacije. Instalirani sadržaj
  ima jasne ulazne poveznice, a postavke prikazuju funkcionalnosti tog sitea.
- Bez Wikija, **Upute** nude cjelovit ugrađeni HTML priručnik s navigacijom po
  poglavljima. Uz Wiki koristi se postojeće područje korisničkih uputa.
- HTML Editor 0.3.9 dodaje prava korisnika i grupa za izradu i uređivanje te
  javno, prijavljeno ili ograničeno čitanje stranica. API, datoteke stranice
  i privitci provjeravaju ta prava. Postavke stranice ne objavljuju nacrt.
- Task 0.3.3 i Comment 0.3.3 poštuju dodijeljene ovlasti HTML urednika.
- Kalendar 0.3.1 prikazuje detalje događaja javnim posjetiteljima i korisnicima
  s pravom čitanja.
- Nove instalacije dobivaju zaseban naziv sesijskog kolačića, uključujući
  instalacije na istom poslužitelju. Povrat backupa čuva identitet odredišta.
- Ažurirane su hrvatske i engleske upute, ugrađene arhive uputa i svih šest
  jezičnih paketa sučelja. Opcionalne integracije ostaju opcionalne.

## Nadogradnja

Koristite GUI nadogradnju za secured FPM ili dokumentirani CLI updater.
Postojeće instalacije zadržavaju odabrane opcionalne module i podatke.
Šest osnovnih modula obavezno je. Instalirani HTML Editor, Kalendar, Task ili
Comment dobiva odgovarajuće izdanje. Framework 0.0.25 i Auth 0.3.1 nisu
mijenjani; ovo izdanje ne dodaje migracije baze.

Pripremljeni Demo koristi postojeći privatni updater, koji prije nadogradnje
vraća čistu kopiju i nakon nje snima novu osnovu za reset. Ankete koje izrade
posjetitelji brišu se tim resetom.

Docker koristi `ghcr.io/kmihalj/simbioza:0.3.20`. Trajni odabir modula ostaje
sačuvan pri zamjeni imagea. Vidi [Docker upute](docker_hr.md).

Vidi [primjere modularnih instalacija](modularity_hr.md),
[ugrađene upute](bundled-manual_hr.md) i
[ovisnosti modula](module-dependencies_hr.md).
