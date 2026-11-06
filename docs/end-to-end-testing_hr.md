# End-to-end testiranje

Simbioza ima end-to-end skup testova preglednika i HTTP API-ja za sastavljenu
aplikaciju. Za razliku od jediničnog testa modula, ovaj test instalira označeno
izdanje Frameworka `^0.0.25` i kompatibilna izdanja modula iz `^0.3.0` u novi
privremeni projekt, pokreće sve
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
ne kopiraju u Simbiozu, paket modula ni administratorsku instalaciju.

## Što se provjerava

Skup učitava sve module i provjerava odabrane javne tokove u nastavku.
Dopunjuje testove domenskih modula; ne tvrdi da svaka postavka, provider ili
kombinacija prava ima zaseban preglednički scenarij. [Primjer HTML Editora
u obrascu](user-guide/html-editor-u-obrascu_hr.md) ima vlastite upute
integracije i izvršivi primjer.

| Područje | End-to-end pokrivenost |
|---|---|
| Čisti host i ORM | Nova aplikacija, SQLite/PostgreSQL/MySQL baza, sve službene migracije, stvarni front controller, sesije, logovi, cache direktoriji i sigurno uklanjanje. |
| Theme i Menu | Desktop/mobilna navigacija, desni mobilni panel, pamćenje jezika, spremanje menija, responzivni hero vizual pune visine, prilagodljivo mobilno preklapanje sadržaja bez sudara, jednake Home/Inner veličine, prikaz od ruba do ruba, prateći tekst automatskih hero naslova u bojama Hero postavki, uski pregled uživo bez sudara teksta, kopiranje teme, izvoz paketa samo s korištenim assetima, potpuni izvoz teme, brisanje, uvoz cijele teme i request-scoped privatna kopija područja koja ne mijenja sistemske teme. |
| Auth | Preusmjeravanje gosta, ovlasti administratora i običnog korisnika, lokalna prijava/odjava, profil i postavka obavijesti, povratna promjena lozinke, jednokratna izrada osobnog područja, poveznica u profilu, skrivanje od gosta, administratorski ekran pravila, CRUD grupa/korisnika, članstva, ETagovi, siguran izlaz, audit i čišćenje. |
| API | Bearer autentikacija, dinamički scopeovi, discovery, izvorni OpenAPI 3.1, CORS preflight, paginacija, RFC 9457 problemi, rate-limit zaglavlja, idempotentni replay, `If-Match`, zahtjev za osobni ključ, odobrenje administratora, jednokratni prikaz i odvajanje scopea od domenskih prava. |
| Workspace | Kreiranje, skriveni nedopušteni dohvat, pretraga subjekata, ACL područja i čvorova, poveznice stabla, potpuni poredak, izmjene, brisanje čvora, soft-delete područja, popis obrisanih, oporavak, Sažetci od dvanaest redaka, jezični fallback, sklopivo stablo/opcije, strukturirani ciljevi Sažetaka na naslovnici i izolacija tema između područja. |
| HTML Editor | Strukturirani nacrt, odbijanje zastarjele revizije, slanje na pregled, granica prava objavljivača, objava, nepromjenjive verzije, renderirani izlaz, prijevodi, povrat verzije, odbacivanje nacrta, brisanje stranice i uklanjanje javne Workspace rute. |
| Privitci | Obični multipart upload, odbijanje nepodržane idempotentnosti uploada, prijenos i prekid chunkova, vidljivost, popis, izmjena metapodataka, byte-for-byte preuzimanje i brisanje. |
| Task | Otkrivanje iz verzioniranog sadržaja dokumenta, ETagom zaštićena promjena stanja, idempotentni replay i povijest s jednim stvarnim prijelazom. |
| Notification | Domenske obavijesti nakon reviewa i prijave komentara, API inbox/read/read-all te ekran obavijesti prijavljenog korisnika. |
| Comment | Stvaranje komentara na stvarnoj objavljenoj stranici, reakcija, prijava neprimjerenog sadržaja, obavijest administratora i moderatorsko brisanje. |
| Calendar i CalDAV | ACL kalendara, CRUD događaja, obavezni vremenski rasponi, ICS izvoz, ETagovi, well-known discovery, `HEAD`, `OPTIONS`, principal/collection `PROPFIND`, `REPORT` te `PUT`/`GET`/`DELETE` kalendarskog objekta. |
| Webhookovi | Vlasništvo pretplate, jednokratna tajna, ETag izmjena, rotacija tajne, dostava nastala stvarnom domenskom promjenom, pregled/retry dostave i zaštićeno brisanje. |
| Email | Spremanje postavki, red kroz stvarni outbox, trenutačni pokušaj prema namjerno nedostupnom lokalnom SMTP-u i vidljiv konačni neuspjeh bez vanjske isporuke. |
| Survey | API stvaranje/arhiviranje nacrta i vidljivost samostalne navigacije; detaljna pravila pristupa, pitanja, odgovora i oporavka dodatno provjerava Survey modul. |
| Backup | Odabir potpunih poslovnih cjelina, šifrirani arhiv, upload/preflight, ograničeni/puni povrat i ponašanje sesije nakon vraćanja. |
| Pristupačnost i skripte | Osobne prilagodbe preglednika, rad tipkovnice/modala i pomoć odobrene skripte bez izvršavanja upute kao koda. |
| Confluence Import | Kontrolirani sintetički uvoz i dobiveni obični Workspace/Editor sadržaj. |

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

Instalirajte točne JavaScript alate zapisane u `package-lock.json`, zatim
njihov testni Chromium. Koristite izdvojeni izvorni checkout, a ne direktorij
žive aplikacije. Testni preglednik ne treba osobni Chrome profil:

```bash
npm ci
npx --no-install playwright install chromium
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

Aplikacija pri čistoj provjeri dohvaća kompatibilna označena PHP izdanja;
jezin izvor ne sprema `composer.lock`. JavaScript alati namjerno koriste
`package-lock.json` iz Gita; `npm ci` čuva točne verzije. Uz rezultate koje
želite uspoređivati sačuvajte stvarni popis testiranih paketa.

## Testiranje lokalnih izmjena modula

Zadana naredba dohvaća udaljena označena izdanja paketa, jednako kao CI. Prije
pusha dopuštene susjedne module provjerite ovako:

```bash
composer e2e -- --local
```

Lokalni način koristi path repozitorije samo za module koji pripadaju ovom
projektu. Nikada ne zamjenjuje uzvodni Framework ni Demo modul.

Lokalni način dopušta razvojnu stabilnost posrednih aliasa modula, uz prednost
stabilnim ovisnostima. To vrijedi samo za generirani testni manifest, ne za
produkcijski manifest.

Za cijeli skup PHP provjera kvalitete koristite izoliranu kopiju izvornog koda:

```bash
HPH_LOCAL_MODULE_ROOT=/apsolutna/putanja/do/roditelja-modula php scripts/build_ci_composer.php --local
COMPOSER=composer.ci.json composer update --with-all-dependencies
COMPOSER=composer.ci.json composer on-commit
```

Ne zamjenjujte vendor direktorij aktivne aplikacije samo radi instalacije QA alata.
Generirani manifest uključuje sve opcionalne module i alate navedene pod
`extra.simbioza.development-tools`.

Lokalni prolaz obavezan je kada se zajedno mijenjaju dva ili više susjednih
modula. Zadani prolaz zatim mora proći i nakon što su odgovarajući release
tagovi tih modula dostupni na udaljenim repozitorijima.

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

Rezultati pregledničkih provjera također su odvojeni po driveru: SQLite koristi
`build/test-results/` i `build/playwright-report/`, a MySQL, MariaDB i PostgreSQL
dodaju `-mysql`, `-mariadb` ili `-pgsql`. Ne pokrećite istovremeno dva skupa za isti
driver. Privremeni PHP razvojni poslužitelj isključuje opcode cache jer testovi
stvaraju i mijenjaju generirane PHP fixture datoteke. Postavke opcode cachea
redovnih instalacija ostaju nepromijenjene.

Višestranični audit i browser tokovi postavki imaju izričite rokove od 90–300
sekundi kako bi stigli završiti hladni PHP zahtjevi i obvezno čišćenje. Granice
trajanja pojedinačnog zahtjeva, broja upita, memorije i veličine odgovora nisu
promijenjene. Sintetička browser fixture izričito uključuje Simbiozinu temu,
umjesto da naslijedi njezino isključivanje u razvojnoj instalaciji.

Scenarij potpunog restorea odgađa sljedeći odgovor popisa backup poslova i
zahtijeva da završi prije GUI potvrde uspjeha, a zatim provjerava novu prijavu.
Time sprječava kasnu promjenu sesijskog kolačića tijekom restorea. Testovi pravila
naslovnice vraćaju izvorni fixture JSON u `finally`, uključujući grešku ili istek
roka, tako da kasniji testovi zadrže ugrađenu naslovnicu.

## CI

GitHub Actions odabire Node.js prema workflowu, pokreće `npm ci` sa
spremljenim lockom, instalira odgovarajući Chromium s Linux ovisnostima
i pokreće `composer e2e`. Kod
greške Playwright izvještaj, traceovi, slike, videozapisi te logovi poslužitelja
i performansi ostaju dostupni kao CI artefakt. Zaseban job pokreće isti potpuni
skup nad čistim PostgreSQL i MySQL bazama.

Browser skup namjerno je zaseban job od PHP jediničnih i statičkih provjera.
Tako je odmah jasno pripada li greška izoliranom modulu, čistoj instalaciji,
mrežnoj bazi ili sastavljenom browser/API tijeku.
