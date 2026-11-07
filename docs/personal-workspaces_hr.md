# Osobna područja

Simbioza svakom aktivnom korisniku može dati osobno područje. To nije poseban
sustav dokumenata, nego obično ograničeno područje sa stabilnim mapiranjem
korisnika na Workspace. Zato stranice, povijest, privitci, komentari, zadaci,
kalendari, tema, meniji, pretraga i ACL pravila rade jednako kao u svakom drugom
području.

Početni naziv je **Područje od: Ime Prezime** (ili login oznaka kada prikazano
ime nije dostupno). Generirani naziv i opis prate trenutačni jezik sučelja pa
se u hrvatskom i engleskom prikazu ne miješaju jezici. Korisnik kojemu je
područje mapirano poslije ga može
preimenovati kao svako drugo područje; prilagođeni naziv i opis ostaju
sačuvani, a stabilno mapiranje u bazi ne ovisi o nazivu ni slugu.

## Administratorsko postavljanje

Nakon instalacije modulâ i primjene migracija otvorite **Postavke → Osobna
područja** (`/settings/personal-workspaces`). Ekran ima dvije globalne mogućnosti:

- **Automatski izradi osobno područje pri prvoj prijavi** zadano je uključeno.
  Nedostajuće osobno područje nastaje pri sljedećoj uspješnoj prijavi aktivnog
  korisnika; uključivanje ne pokreće masovnu izradu za sve postojeće račune.
- **Omogući korisnicima izradu osobnog područja** zadano je isključeno. Dostupno
  je samo kada je automatska izrada isključena. Korisniku bez osobnog područja
  tada se u **Moj profil → Praćenje i obavijesti** pojavljuje **Izradi moje osobno
  područje**.

Odaberite **Spremi**. Ako su obje mogućnosti isključene, tim se putovima ne
izrađuju nova osobna područja. Postojeća područja ostaju sačuvana.

Tablica **Izrađena osobna područja** služi samo pregledu izrađenih područja i
poveznica na njih. Nema masovne izrade, korisničkih iznimaka ni radnje **Izradi
sada**. Zastarjela tablica `simbioza_user_personal_workspace_policies` ostaje u
migracijskoj shemi radi kompatibilnosti; aktualni servis i obrazac ne primjenjuju
njezina pravila. Meko obrisano osobno područje ostaje povezano s korisnikom i
vraća se kroz **Postavke područja → Obrisana područja**; sljedeća prijava ne
izrađuje zamjenu.

## Pravila pristupa

- Korisnik kojemu je osobno područje izrađeno automatski dobiva jedan izravni
  ACL redak sa svim pravima: **Pregled**, **Dodavanje**, **Uređivanje**,
  **Objavljivanje**, **Brisanje** i **Upravljanje**.
- Područje se izrađuje s ograničenom (`restricted`) vidljivošću.
- Drugi korisnici i grupe ne dobivaju implicitne ACL retke. Taj korisnik ili
  administrator mogu im naknadno dodijeliti uobičajena prava područja.
- Početno gost nema pristup području ni njegov prikaz na uobičajenom popisu;
  eventualne kasnije javne iznimke postavljaju se kroz uobičajena prava područja
  ili stranice.

Auth objavljuje neutralni događaj uspješne prijave, a Simbioza User ga sluša.
Auth zato ostaje neovisan i radi kada Workspace ili Simbioza User nisu
instalirani. Izrada je idempotentna: ponovljene ili istodobne prijave zadržavaju
jedno mapiranje. Pri prvoj sljedećoj prijavi provjerava se i ACL postojećeg
osobnog područja te se korisniku ponovno osiguravaju sva prava ako je područje
nastalo prije uvođenja tog pravila.

Opća područja i dalje nemaju poseban koncept vlasnika: njima upravlja svaki
korisnik s pravom **Upravljanje**. Osobno područje samo ima dodatno stabilno
mapiranje na korisnika kojemu se taj puni ACL automatski dodjeljuje.

## Profil korisnika i API

Ako je samostalna izrada dopuštena, korisnik bez područja može ga izraditi u
svojem profilu. Nakon izrade, **Moj profil → Praćenje i obavijesti** sadrži izravnu poveznicu
**Moje osobno područje**. Autenticirani API ključ sa scopeom `workspace:read`
može pročitati samo vlastito mapiranje:

```http
GET /api/v1/me/personal-workspace
```

Endpoint služi samo za čitanje i nikada ne izrađuje područje.

## Backup i vraćanje

Backup područja uključuje mapiranje kada je odabrano područje osobno. Poslovna
cjelina **Korisnici** čuva podržane korisničke podatke i mapiranja, a **Postavke**
čuvaju globalne postavke izrade. Zastarjela pohranjena korisnička pravila nisu
aktivna mogućnost obrasca ni servisa. Backup cijelog sitea sadrži sva tri dijela.

Pri copy importu postojeće mapiranje osobnog područja ima prednost jer jedan
korisnik može imati samo jedno osobno područje. Uvezena kopija ostaje obično
ograničeno područje istog mapiranog korisnika. Indeksi pretrage ostaju izvedeni
podatci i automatski se obnavljaju nakon vraćanja.
