# Osobna područja

Simbioza svakom aktivnom korisniku može dati osobno područje. To nije poseban
sustav dokumenata, nego obično ograničeno područje sa stabilnim mapiranjem
korisnika na Workspace. Zato stranice, povijest, privitci, komentari, zadaci,
kalendari, tema, meniji, pretraga i ACL pravila rade jednako kao u svakom drugom
području.

Početni naziv je **Područje od: Ime Prezime** (ili login oznaka kada prikazano
ime nije dostupno). Generirani naziv i opis prate trenutačni jezik sučelja pa
se u hrvatskom i engleskom prikazu ne miješaju jezici. Vlasnik ga poslije može
preimenovati kao svako drugo područje; prilagođeni naziv i opis ostaju
sačuvani, a stabilno mapiranje u bazi ne ovisi o nazivu ni slugu.

## Administratorsko postavljanje

1. Primijenite migracije aplikacije:

   ```bash
   php vendor/bin/hph orm-migrate:up
   ```

2. Otvorite **Postavke → Područja → Osobna područja**.
3. Ostavite uključeno **Automatski izradi osobno područje pri prvoj prijavi**
   ako ga novi korisnici trebaju dobiti automatski.
4. Pri uvođenju mogućnosti u postojeću instalaciju jednom pokrenite
   **Izradi osobna područja postojećim korisnicima**.

Tablica na istom ekranu sadrži iznimku za pojedinog korisnika i ručnu radnju
**Izradi sada**. Isključivanje automatske izrade ne briše postojeće područje.
Soft-obrisano osobno područje ostaje povezano s vlasnikom i može se vratiti kroz
uobičajenu administraciju obrisanih područja; sljedeća prijava ne stvara potajno
novu zamjenu.

## Pravila pristupa

- Vlasnik dobiva uobičajena vlasnička prava Workspace modula.
- Područje se izrađuje s ograničenom (`restricted`) vidljivošću.
- Drugi korisnici i grupe ne dobivaju implicitne ACL retke. Vlasnik ili
  administrator mogu im naknadno dodijeliti uobičajena prava područja.
- Gost ne može otvoriti područje niti ga pronaći u uobičajenom popisu područja.

Auth objavljuje neutralni događaj uspješne prijave, a Simbioza User ga sluša.
Auth zato ostaje neovisan i radi kada Workspace ili Simbioza User nisu
instalirani. Izrada je idempotentna: ponovljene ili istodobne prijave zadržavaju
jedno mapiranje.

## Profil korisnika i API

Nakon izrade, **Moj profil → Praćenje i obavijesti** sadrži izravnu poveznicu
**Moje osobno područje**. Autenticirani API ključ sa scopeom `workspaces:read`
može pročitati samo vlastito mapiranje:

```http
GET /api/v1/me/personal-workspace
```

Endpoint služi samo za čitanje i nikada ne izrađuje područje.

## Backup i vraćanje

Backup područja uključuje mapiranje kada je odabrano područje osobno. Poslovna
cjelina **Korisnici** čuva korisničke iznimke i mapiranja, a **Postavke** čuvaju
globalno pravilo automatske izrade. Backup cijelog sitea sadrži sva tri dijela.

Pri copy importu postojeće mapiranje osobnog područja ima prednost jer jedan
korisnik može imati samo jedno osobno područje. Uvezena kopija ostaje obično
ograničeno područje istog vlasnika. Indeksi pretrage ostaju izvedeni podatci i
automatski se obnavljaju nakon vraćanja.
