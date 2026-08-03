# End-to-end testiranje

HFClean ima end-to-end skup testova preglednika i HTTP API-ja za sastavljenu
aplikaciju. Za razliku od jediničnog testa modula, ovaj test instalira najnovije
`dev-main` stanje Frameworka i modula u novi privremeni projekt, pokreće sve
službene migracije zadano nad novom SQLite bazom, podiže stvarni HTTP
poslužitelj i upravlja Chromiumom kroz Playwright. Isti runner prihvaća izričito
pripremljenu praznu PostgreSQL, MySQL ili MariaDB bazu za potpuni cross-driver
test.

Skup nikada ne koristi `config/database.php` radne aplikacije. Kod SQLitea
testni korisnici, Bearer ključ, Workspace zapisi, sesije, logovi i cache postoje
ispod privremenog sistemskog korijena `heartphrame-clean-matrix`. Kod mrežnog
drivera pristupni podaci čitaju se samo iz `HPH_MATRIX_DB_*`, a operator mora
pripremiti zasebnu praznu jednokratnu bazu. Runner uklanja privremeni projekt,
ali nikada ne izrađuje niti briše mrežnu bazu; njezino izričito čišćenje ostaje
operatoru.

Workspace, stranica, nacrt i objavljene verzije koje stvara browser test
sintetički su testni podaci, a ne početni ili demonstracijski sadržaj. Nikada se
ne kopiraju u HFClean, paket modula ni administratorsku instalaciju.

## Što se provjerava

Svih 40 scenarija pokriva svaki modul koji HFClean isporučuje. Provjerava se
javno ponašanje, a ne privatni detalji implementacije.

| Područje | End-to-end pokrivenost |
|---|---|
| Čisti host i ORM | Nova aplikacija, SQLite/PostgreSQL/MySQL baza, sve službene migracije, stvarni front controller, sesije, logovi, cache direktoriji i sigurno uklanjanje. |
| Theme i Menu | Desktop/mobilna navigacija, desni mobilni panel, pamćenje jezika, spremanje menija, responzivni hero vizual pune visine, prilagodljivo mobilno preklapanje sadržaja bez sudara, jednake Home/Inner veličine, prikaz od ruba do ruba, uski pregled uživo bez sudara teksta, kopiranje teme, izvoz paketa, prijenosni backup, brisanje i uvoz backupa. |
| Auth | Preusmjeravanje gosta, ovlasti administratora i običnog korisnika, lokalna prijava/odjava, profil i postavka obavijesti, povratna promjena lozinke, CRUD grupa/korisnika, članstva, ETagovi, siguran izlaz, audit i čišćenje. |
| API | Bearer autentikacija, dinamički scopeovi, discovery, izvorni OpenAPI 3.1, CORS preflight, paginacija, RFC 9457 problemi, rate-limit zaglavlja, idempotentni replay, `If-Match`, zahtjev za osobni ključ, odobrenje administratora, jednokratni prikaz i odvajanje scopea od domenskih prava. |
| Workspace | Kreiranje, skriveni nedopušteni dohvat, pretraga subjekata, ACL područja i čvorova, poveznice stabla, potpuni poredak, izmjene, brisanje čvora, soft-delete područja, popis obrisanih, oporavak, Sažetci od dvanaest redaka, jezični fallback, sklopivo stablo/opcije i strukturirani ciljevi Sažetaka na naslovnici. |
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

## Performance budžeti

E2E poslužitelj registrira ORM query observer samo tijekom izoliranog testa.
Zapisuje `build/e2e-query-log.jsonl` za SQLite, odnosno datoteku sa sufiksom
drivera poput `build/e2e-query-log-mysql.jsonl`, ali nikada SQL bind vrijednosti,
API tokene, query string zahtjeva ni tijela odgovora. Observer nije uključen u
normalnim zahtjevima aplikacije.

Isti izolirani run zapisuje `build/e2e-request-log.jsonl`, odnosno odgovarajuću
datoteku sa sufiksom drivera, s metodom, putanjom bez query stringa, statusom,
trajanjem, uporabom memorije, brojem bajtova tijela odgovora i content typeom.
Ne zapisuje zaglavlja, kolačiće, tijelo zahtjeva ni tijelo odgovora. Query i
request zapisi spremaju se u međuspremnik i zapisuju jednom po zahtjevu kako
profiler ne bi iskrivio prometnu putanju zasebnim zapisivanjem datoteke za svaki
upit.

Zadnji scenarij označava reprezentativne zahtjeve za naslovnicu, aktualnog
korisnika te popise korisnika, Workspacea, kalendara i obavijesti. Za svaki
zahtjev provjerava budžete broja upita, trajanja, vršne memorije i veličine
odgovora. Dodatno odbija ponovljeno otkrivanje sheme, ponovljeno čitanje Auth
provider postavki, popravne zapise Auth grupa i neočekivane zapise uporabe API
ključa. Tako izmjerene optimizacije postaju trajni regresijski ugovor, a ne
jednokratni benchmark.

Najskuplje zahtjeve nakon testa možeš pregledati ovako:

```bash
jq -s 'group_by(.request_id) | map({path:.[0].path, queries:length}) | sort_by(.queries) | reverse | .[:20]' \
  build/e2e-query-log.jsonl

jq -s 'sort_by(.duration_ms) | reverse | .[:20] | map({path,duration_ms,peak_memory_bytes,response_bytes})' \
  build/e2e-request-log.jsonl
```

## Prvo pokretanje

Instalirajte najnoviji Playwright testni paket bez lock datoteke, zatim
instalirajte Chromium:

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Za stvarni MySQL ili PostgreSQL test izradite zasebnu praznu testnu bazu i
predajte vezu isključivo kroz okoliš procesa:

```bash
HPH_MATRIX_DB_HOST=127.0.0.1 \
HPH_MATRIX_DB_PORT=3306 \
HPH_MATRIX_DB_NAME=heartphrame_e2e \
HPH_MATRIX_DB_USER=heartphrame_e2e \
HPH_MATRIX_DB_PASSWORD='lokalna-testna-tajna' \
php scripts/run_e2e.php --local --database=mysql
```

Za PostgreSQL koristite `--database=pgsql` i port `5432`. Baza mora biti prazna
prije svakog pokretanja. Ne koristite produkcijsku shemu ni produkcijski račun.
Uz mrežni driver izbjegavajte `--keep`, osim kada svjesno želite zadržati
privremenu konfiguraciju veze radi dijagnostike.

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
videozapisi i HTML izvještaj zapisuju se ispod `build/`, izlaz PHP poslužitelja u
`build/e2e-server.log`, a neosjetljiva mjerenja upita u
`build/e2e-query-log.jsonl`. Prolazi s mrežnom bazom dodaju odabrani driver,
primjerice `build/e2e-server-mysql.log`, `build/e2e-query-log-mysql.jsonl` i
`build/e2e-request-log-mysql.jsonl`. Git ignorira sve te putanje. Sačuvani
projekt uklonite nakon pregleda ili ponovno pokrenite test bez `--keep`.

## CI

GitHub Actions instalira najnoviji Node.js, razrješava najnoviji npm paket,
instalira Chromium i njegove Linux ovisnosti te pokreće `composer e2e`. Kod
greške Playwright izvještaj, traceovi, slike, videozapisi te logovi poslužitelja
i performansi ostaju dostupni kao CI artefakt. Zaseban job pokreće isti potpuni
skup nad čistim PostgreSQL i MySQL bazama.

Browser skup namjerno je zaseban job od PHP jediničnih i statičkih provjera.
Tako je odmah jasno pripada li greška izoliranom modulu, čistoj instalaciji,
mrežnoj bazi ili sastavljenom browser/API tijeku.
