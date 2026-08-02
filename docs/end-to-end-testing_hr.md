# End-to-end testiranje

HFClean ima end-to-end skup testova preglednika i HTTP API-ja za sastavljenu
aplikaciju. Za razliku od jediničnog testa modula, ovaj test instalira najnovije
`dev-main` stanje Frameworka i modula u novi privremeni projekt, pokreće sve
službene migracije nad novom SQLite bazom, podiže stvarni HTTP poslužitelj i
upravlja Chromiumom kroz Playwright.

Skup nikada ne koristi `config/database.php` radne aplikacije. Testni korisnici,
Bearer ključ, Workspace zapisi, sesije, logovi i cache postoje samo u
direktoriju ispod privremenog sistemskog korijena
`heartphrame-clean-matrix`. Runner odbija raditi izvan tog korijena i nakon
završetka uklanja projekt.

Workspace, stranica, nacrt i objavljene verzije koje stvara browser test
sintetički su testni podaci, a ne početni ili demonstracijski sadržaj. Nikada se
ne kopiraju u HFClean, paket modula ni administratorsku instalaciju.

## Što se provjerava

Svih 35 scenarija pokriva svaki modul koji HFClean isporučuje. Provjerava se
javno ponašanje, a ne privatni detalji implementacije.

| Područje | End-to-end pokrivenost |
|---|---|
| Čisti host i ORM | Nova aplikacija, SQLite baza, sve službene migracije, stvarni front controller, sesije, logovi, cache direktoriji i sigurno uklanjanje. |
| Theme i Menu | Desktop/mobilna navigacija, desni mobilni panel, pamćenje jezika, spremanje menija, responzivni hero vizual, jednake Home/Inner veličine, prikaz od ruba do ruba, kopiranje teme, izvoz paketa, prijenosni backup, brisanje i uvoz backupa. |
| Auth | Preusmjeravanje gosta, ovlasti administratora i običnog korisnika, lokalna prijava/odjava, profil i postavka obavijesti, povratna promjena lozinke, CRUD grupa/korisnika, članstva, ETagovi, siguran izlaz, audit i čišćenje. |
| API | Bearer autentikacija, dinamički scopeovi, discovery, izvorni OpenAPI 3.1, CORS preflight, paginacija, RFC 9457 problemi, rate-limit zaglavlja, idempotentni replay, `If-Match`, zahtjev za osobni ključ, odobrenje administratora, jednokratni prikaz i odvajanje scopea od domenskih prava. |
| Workspace | Kreiranje, skriveni nedopušteni dohvat, pretraga subjekata, ACL područja i čvorova, poveznice stabla, potpuni poredak, izmjene, brisanje čvora, soft-delete područja, popis obrisanih i oporavak. |
| HTML Editor | Strukturirani nacrt, odbijanje zastarjele revizije, slanje na pregled, granica prava objavljivača, objava, nepromjenjive verzije, renderirani izlaz, prijevodi, povrat verzije, odbacivanje nacrta, brisanje stranice i uklanjanje javne Workspace rute. |
| Privitci | Obični multipart upload, odbijanje nepodržane idempotentnosti uploada, prijenos i prekid chunkova, vidljivost, popis, izmjena metapodataka, byte-for-byte preuzimanje i brisanje. |
| Task | Otkrivanje iz verzioniranog sadržaja dokumenta, ETagom zaštićena promjena stanja, idempotentni replay i povijest s jednim stvarnim prijelazom. |
| Notification | Domenske obavijesti nakon reviewa i prijave komentara, API inbox/read/read-all te ekran obavijesti prijavljenog korisnika. |
| Comment | Stvaranje komentara na stvarnoj objavljenoj stranici, reakcija, prijava neprimjerenog sadržaja, obavijest administratora i moderatorsko brisanje. |
| Calendar i CalDAV | ACL kalendara, CRUD događaja, obavezni vremenski rasponi, ICS izvoz, ETagovi, well-known discovery, `HEAD`, `OPTIONS`, principal/collection `PROPFIND`, `REPORT` te `PUT`/`GET`/`DELETE` kalendarskog objekta. |
| Webhookovi | Vlasništvo pretplate, jednokratna tajna, ETag izmjena, rotacija tajne, dostava nastala stvarnom domenskom promjenom, pregled/retry dostave i zaštićeno brisanje. |
| Email | Spremanje postavki, red kroz stvarni outbox, trenutačni pokušaj prema namjerno nedostupnom lokalnom SMTP-u i vidljiv konačni neuspjeh bez vanjske isporuke. |

Negativni tokovi namjerna su pokrivenost: skriveni odgovori `404`, zabrane `403`,
neispravni ključevi, nedostajući ili zastarjeli preduvjeti, neispravni rasponi i
nepodržana idempotentnost uploada moraju ostati stabilni ugovori.

## Prvo pokretanje

Instalirajte najnoviji Playwright testni paket bez lock datoteke, zatim
instalirajte Chromium:

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Aplikacija i interni paketi namjerno prate pomična `dev-main` stanja. Zato se
`package-lock.json` i `composer.lock` ne spremaju u Git.

## Testiranje lokalnih izmjena modula

Zadana naredba dohvaća udaljena `dev-main` stanja paketa, jednako kao CI. Prije
pusha dopuštene susjedne module provjerite ovako:

```bash
composer e2e -- --local
```

Lokalni način koristi path repozitorije samo za module koji pripadaju ovom
projektu. Nikada ne zamjenjuje uzvodni Framework ni Demo modul.

Lokalni prolaz obavezan je kada se zajedno mijenjaju dva ili više susjednih
modula. Zadani prolaz zatim mora proći i nakon što su commitovi tih modula
dostupni na udaljenim granama `dev-main`.

## Istraživanje greške

Pokrenite vidljivi preglednik i sačuvajte izoliranu aplikaciju:

```bash
composer e2e -- --local --headed --keep
```

Runner ispisuje putanju sačuvanog projekta. Playwright traceovi, slike zaslona,
videozapisi i HTML izvještaj zapisuju se ispod `build/`, a izlaz PHP poslužitelja
u `build/e2e-server.log`. Git ignorira sve te putanje. Sačuvani projekt uklonite
nakon pregleda ili ponovno pokrenite test bez `--keep`.

## CI

GitHub Actions instalira najnoviji Node.js, razrješava najnoviji npm paket,
instalira Chromium i njegove Linux ovisnosti te pokreće `composer e2e`. Kod
greške Playwright izvještaj, traceovi, slike, videozapisi i log poslužitelja
ostaju dostupni kao CI artefakt.

Browser skup namjerno je zaseban job od PHP jediničnih i statičkih provjera.
Tako je odmah jasno pripada li greška izoliranom modulu, čistoj instalaciji,
mrežnoj bazi ili sastavljenom browser/API tijeku.
