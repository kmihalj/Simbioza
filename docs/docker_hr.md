# Docker instalacija — 0.3.29

[Engleska verzija](docker_en.md)

Klasična instalacija ostaje podržana. Docker je dodatni način instalacije, ne
pretvorba postojećeg sitea. Verzionirani image je
`ghcr.io/kmihalj/simbioza:0.3.29` za Linux AMD64 i ARM64, uključujući Apple Silicon
kroz Colimu. Compose paket i kontrolne sume preuzimaju se iz
[izdanja 0.3.29](https://github.com/kmihalj/Simbioza/releases/tag/0.3.29).
Email je 0.3.3; HTML Editor/Menu/Theme su 0.3.2; Audit/Backup/Auth/API su 0.3.1, a ostali javni moduli ostaju 0.3.0; Framework v0.0.25
i raspored instalacije nisu promijenjeni.

## Što se trajno čuva

Svaki Compose servis koristi ograničeni Docker `local` logging (10 MiB, pet
datoteka) za standardni izlaz i greške. To ne rotira datoteke u runtime
volumenu; [uputa za rotaciju](logging_hr.md) opisuje zasebne logove aplikacije i workera.

| Mjesto | Namjena |
| --- | --- |
| Aplikacijski image, `/opt/simbioza` | Aplikacija, PHP biblioteke, vendor paketi, tvorničke postavke i javne početne upute; read-only, bez trajnog volumena preko koda |
| Volumen `runtime`, `/runtime/config` | Privatna konfiguracija aplikacije i modula, uključujući vjerodajnice baze i Autha |
| Isti volumen, `/runtime/data` | Stranice/datoteke, privitci, teme, logovi, cache, backupi, importi, prijevodi i PHP sesije |
| Isti volumen, `/runtime/saml` | Opcionalna privatna SimpleSAMLphp konfiguracija, metapodaci i certifikati |
| Volumen `database` | PostgreSQL podaci, neovisni o zamjeni aplikacijskog imagea |

Postoje dva dugotrajna containera (aplikacija i PostgreSQL) te kratki posao
inicijalizacije. Treća *komponenta* je runtime volumen, ne dodatni aktivni
container. Aplikacijski kod pripada imageu, ne podatkovnom volumenu koji se
zamjenjuje. Na stvarnoj instalaciji ne pokretati `docker compose down -v`: time
se uklanjaju imenovani volumeni runtimea i baze.

Konfiguracija koristi postojeći dogovor frameworka o slojevima:

```text
HPH_CONFIG_PATH=/opt/simbioza/config
HPH_CONFIG_OVERRIDE_PATHS=/runtime/config
```

Prvi sloj sadrži read-only tvorničke PHP postavke. Zadnji sloj je privatni cilj
pisanja za GUI i CLI. Modulima ostaju vlastiti direktoriji, primjerice
`auth/auth.json`, `email/email.json`, `menu/` i `theme/`. Ne kopirati cijeli
tvornički config u privatni sloj: tako bi stari servisi/rute nadjačavali nove
postavke sljedećeg imagea.

Poddirektorij `data` istog volumena mounta se **izravno** na
`/opt/simbioza/data`, koristeći Compose `volume.subpath`; nije symlink. Potrebni
su Docker Engine 27+ i Compose koji podržava `volume.subpath`. Provjereno
okruženje koristi Engine 29.5.2 i Compose 5.6.0. Privatne datoteke nisu u
Apacheovu javnom `public/` direktoriju.

## Gradnja bez izvoza žive instalacije

Iz razvojnog/release checkouta s instaliranim Composer autoloaderom:

```sh
mkdir -p build/docker
php scripts/build_container_context.php --output=build/docker/context --local
docker build -t simbioza:0.3.29-candidate \
  -f build/docker/context/docker/Dockerfile build/docker/context
```

Ciljni direktorij mora biti nov; ne spajati niti ponovno koristiti stari.
`--local` izvozi aktualni Git-visible kod paketa iz susjednih repozitorija i
označava ga kao **neobjavljeni lokalni kandidat**. Oznake candidate verzija
služe zadovoljavanju Composer ovisnosti; nisu potvrda objavljenog izdanja.
Maknuti `--local` tek kada su potrebni release paketi objavljeni. Za deployment
zabilježiti digest imagea i njegov generirani `composer.lock`: ponovna gradnja
ne jamči potpuno jednake razriješene ovisnosti.
Builder zaključava read-only oznaku koda sažetkom razriješenih ovisnosti, pa
promijenjene ovisnosti zahtijevaju izričitu migracijsku generaciju čak i kada
aplikacijski izvor nije promijenjen.

Izvoz koristi allowlist koda i zadanih postavki te izostavlja privatni HFC config,
aktivni Auth JSON, bazu, sadržaj, Composer vjerodajnice i žive vendor symlinkove.
Nikada ne pokretati `docker build .` iz žive HFC instalacije.

Zadano se uključuju svi opcionalni moduli. Za novi manji image može se zadati
`--modules=theme,calendar`, ili `--modules=` za minimum. Obavezne ovisnosti i
Backup biblioteka potrebna za uvoz početnih uputa uključuju se automatski.
Installer smije odabrati samo pakete stvarno ugrađene u image. Za postojeću bazu
zadržati pakete čije su migracije već izvršene; nepotreban modul isključiti,
umjesto uklanjanja njegove sheme/paketa iz imagea.

## Prva instalacija

1. Kopirati `docker/.env.example` u `docker/.env`. Provjeriti digest objavljenog
   imagea (ili `SIMBIOZA_IMAGE` postaviti na vlastiti provjereni image). Stvoriti
   dvije privatne datoteke lozinki: jednu za Simbiozu, drugu za PostgreSQL admina.
   Svaka ima različitu snažnu lozinku u jednom retku. Ne spremati ih u Git.
   Direktorij tajni postaviti na `0700`, a datoteke na `0444`: direktorij sprječava
   druge korisnike hosta da ih čitaju, dok read-only Compose mountovi omogućuju
   drukčijem UID-u PostgreSQL containera čitanje samo njegovih tajni. Compose
   tajne iz datoteka ne mijenjaju vlasništvo. Datoteke ne stavljati u javni ili
   dijeljeni direktorij hosta; aplikacija ne dobiva administratorsku tajnu baze.
2. Zadani listener je `127.0.0.1:1511`. Ispred njega postaviti pouzdan HTTPS
   reverse proxy; port baze ne objavljivati. Sigurnost kolačića ostaviti uključenu.
   Samo za izolirani lokalni HTTP test zadati `SIMBIOZA_COOKIE_SECURE=0`.
   `SIMBIOZA_PORT` u `.env` mijenja TCP port hosta ako druga usluga koristi
   1511. Unutarnji port containera ostaje 8080. Registracija porta za drugu
   uslugu sama po sebi ne zauzima taj port, ali možda treba prilagoditi pravila
   vatrozida. Zadržati slušanje na loopbacku osim ako je udaljeni pristup
   namjerno potreban.
3. Iz `docker/` koristiti isti Compose naziv projekta:

```sh
docker compose -p simbioza run --rm --no-deps init
docker compose -p simbioza up -d database app
docker compose -p simbioza run --rm --no-deps app install:prepare \
  --base-url=https://simbioza.example.org
```

4. Generiranu jednokratnu adresu otvoriti privatno. Odabrati PostgreSQL, host
   `database`, port `5432`, bazu/korisnika `simbioza` i aplikacijsku lozinku
   (ne administratorsku). Dovršiti uobičajeni instalacijski čarobnjak.
5. Prije prvog kontroliranog ponovnog pokretanja zabilježiti generaciju imagea:

```sh
docker compose -p simbioza stop app
docker compose -p simbioza run --rm --no-deps app migrate
docker compose -p simbioza up -d app
```

Aplikacija i CLI rade kao UID/GID `33:33`, bez dodatnih capabilities, uz
`no-new-privileges`, read-only korijenski filesystem i privremeni `/tmp`.
Kratki root init ima samo ovlasti potrebne za vlasništvo datoteka/direktorija i
nema mrežu. Stvara nedostajuću privatnu konfiguraciju bez prepisivanja postojećih
datoteka ili rekurzivne promjene vlasništva podataka. Pogrešno vlasništvo volumena
izričita je greška za održavatelja, ne nešto što se tiho popravlja.

PostgreSQL na novom volumenu stvara zasebnog vlasnika baze `simbioza` s
`NOSUPERUSER`, `NOCREATEDB`, `NOCREATEROLE` i `NOREPLICATION`. Promjena datoteke
tajne kasnije **ne** mijenja lozinku postojeće baze: rotirati je izričito u
PostgreSQLu i privatnoj aplikacijskoj konfiguraciji.

## Provjera release imagea

CI provjerava PostgreSQL instalaciju, backup/restore, spremanje jezika, trajnost
runtime podataka i nepromjenjivi kod na obje arhitekture. Dokazi izdanja sadrže
zaključane ovisnosti, SBOM-ove, izvještaje ranjivosti i digeste imagea.
Popravljivi visoki/kritični nalazi blokiraju objavu; potpuni izvještaji zadržavaju
i nalaze bez dostupnog upstream popravka za pregled održavatelja.

Dockerfile u ovom izvornom kodu zadržava PHP osnovni image zaključan digestom,
a naslijeđene Debian pakete nadograđuje prije instaliranja PHP proširenja.
Sama instalacija build ovisnosti ne nadograđuje pouzdano pakete koji već postoje
u osnovi, primjerice `curl` i `libcurl`. Builder i završna aplikacija nasljeđuju
nadograđenu runtime fazu; sigurnosna provjera ostaje nepromijenjena.
Za novu sigurnosnu gradnju dodati `--pull --no-cache` gornjoj naredbi gradnje
kako se ne bi ponovno koristio stari sloj paketa iz cachea. Zabilježiti novi
digest imagea, zaključane ovisnosti i rezultate skeniranja. Nova gradnja ne
mijenja već pokrenuti image niti zamjenjuje postojeće izdanje/tag.

## Nadogradnja i migracije

Oznaka nepromjenjivog deploymenta dio je koda imagea; privatne postavke je ne
mogu isključiti. `update.php`, Composer/paketno dodavanje i uklanjanje te
odgovarajuće privilegirane worker radnje prekidaju prije promjene koda. Setup
objašnjava postupak zamjene imagea; GUI postavki, sadržaja i jezika te
uključivanje/isključivanje modula ostaju dostupni.

Za svaku nadogradnju:

1. Prije održavanja izgraditi/preuzeti odgovarajući verzionirani image i provjeriti digest.
2. Zaustaviti web aplikaciju **i sve zasebne workere**. Dok su pisci zaustavljeni,
   sačuvati konzistentan PostgreSQL backup i snimku runtime volumena. Aplikacijski
   arhivi stranica/područja ne zamjenjuju kopiju cijelog deploymenta, posebno
   privatnih SAML certifikata i vjerodajnica baze.
3. U `.env` promijeniti `SIMBIOZA_IMAGE`, zadržavajući oba volumena i naziv projekta.
4. Pokrenuti inicijalizaciju i izričite migracije, a zatim web aplikaciju:

```sh
docker compose -p simbioza run --rm --no-deps init
docker compose -p simbioza run --rm --no-deps app migrate
docker compose -p simbioza up -d app
```

5. Prije workera provjeriti zdravlje, prijavu, reprezentativni sadržaj i poslove.

Migracija uzima neblokirajući lock zajedničkog runtimea, učitava iste config
slojeve bez boota servisa koji možda trebaju novu shemu, izvršava ugrađene
migracije po redoslijedu, dodaje nedostajuće Theme opcije teksta gumba, čisti
obnovljivi cache i zapisuje novi ID imagea **tek na kraju**. Postojeće vrijednosti
teme i nepoznata polja ostaju nepromijenjeni. Paket teme za novu instalaciju ne
uvozi se ponovno; nove opcije dobivaju `auto`. Ponovljena migracija ne zapisuje
već dopunjeni JSON teme. Web/worker startup odbija instalaciju čiji zabilježeni ID ne
odgovara imageu. Neuspjela migracija ostavlja prethodni ID; potrebno je izričito
popraviti stanje ili vratiti kopiju. Nema automatskog rollbacka djelomično
primijenjenog DDL-a. Image bez prethodno primijenjenih migracija odbija se:
nakon nadogradnje baze nije sigurno vratiti samo stari image.

Koristiti jedan runtime volumen po deploymentu. Datotečni lock ne koordinira
nepovezane volumene koji koriste istu bazu; multi-replica/high-availability nije
potvrđena ovim kandidatom. Migracijska naredba sama ne zaustavlja već pokrenuti
stari container; zaustavljanje pisaca je obavezno. Ne preskakati buduće
release-specifične upute za migraciju podataka i konfiguracije.

## CLI, workeri i jezici

```sh
docker compose -p simbioza run --rm --no-deps app cli orm-migrate:status
docker compose -p simbioza run --rm --no-deps app cli modules list
docker compose -p simbioza run --rm --no-deps app cli languages install de
docker compose -p simbioza run --rm --no-deps app cli email outbox:worker --max-messages=20
```

Email worker zahtijeva podešen i uključen SMTP; zadano je slanje isključeno.
Jednokratna naredba pokušava obraditi najviše 20 poruka; `--batch-size` određuje
samo veličinu internog kruga. Cjeloviti primjeri zasebnog stalnog workera nalaze
se u [uputama za Email 0.3.3 worker](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/0.3.3/docs/workers_hr.md).
Za trajni worker dodati održavateljev servis s istim imageom, runtime mountovima,
korisnikom, okruženjem i sigurnosnim ograničenjima, uz naredbu
`["cli", "email", "outbox:worker", "--watch", "--sleep=2"]`, bez ukupnog limita. Zaustavljati ga pri nadogradnji.
CLI koristi isti privatni config i organizaciju sesija/podataka kao HTTP.

## Opcionalni SimpleSAMLphp

Auth učitava PHP biblioteku **u istom PHP procesu**. URL neke odvojene instalacije
ne zamjenjuje `autoload_path`. Podržani candidate raspored koristi opcionalni
overlay `docker/compose.saml.yaml`:

```sh
docker compose -p simbioza -f compose.yaml -f compose.saml.yaml up -d app
```

`SIMBIOZA_SAML_LIBRARY` zadati na pripremljeni kompatibilni Linux PHP kod s
`vendor/`, koji se read-only mounta na `/opt/simplesamlphp`. Koristiti očišćeni
code-only direktorij: kroz njega ne izlagati živi host config, privatne ključeve,
metapodatke, cache ni sesije. Privatne datoteke pripadaju runtime volumenu:

```text
/runtime/saml/config/config.php
/runtime/saml/config/authsources.php
/runtime/saml/metadata/
/runtime/saml/cert/
/runtime/data/saml/cache/
/runtime/data/saml/sessions/
```

Image već zadaje `SIMPLESAMLPHP_CONFIG_DIR=/runtime/saml/config`. Ondje podesiti
apsolutne privatne `certdir`, `cachedir`, `datadir`, izvore metapodataka i PHP
session putanju. `baseurlpath` postaviti na javni HTTPS `/saml/` URL, registrirati
SP i njegove metapodatke/ACS kod IdP-a te uključiti potreban SAML modul.
U Auth SAML profilu zadati `autoload_path=/opt/simplesamlphp/vendor/autoload.php`
i naziv `auth_source`. Apache izlaže samo `/opt/simplesamlphp/public` na `/saml`;
nedostajuća biblioteka/config ne uključuje SAML automatski.

Test provjerava učitavanje SimpleSAMLphpa 2.4.4 uz izdvojeni privatni config,
sesije/cache i metapodatke izmišljenog SP-a. **Nije** registriran stvarni container
SP kod AAI@EduHr niti je dokazana stvarna IdP prijava. To provjeriti prije
produkcijskog uključivanja SAML-a. Postojeće HFC SSO postavke nisu premještene,
ponovno korištene niti izmijenjene. Odvojeni SAML servis/posebni image mogući su
budući koraci, ne obavezne ovisnosti normalnog Simbioza imagea.

## Provjere i granice

Dokaze pratiti u A4 zapisu [audita prije izdanja](pre-release-audit-0.3.0_hr.md).
Docker testovi koriste samo privremene izmišljene siteove. HFC ostaje klasična
razvojna instalacija.
Objava i skeniranje imagea dio su CI-ja izdanja. Stvarna HTTPS/proxy provjera i
registracija/prijava SAML SP-a/IdP-a ostaju održavateljeve provjere deploymenta.
Vidi [bilješke izdanja 0.3.29](release-0.3.29_hr.md) i priložene CI/sigurnosne dokaze.
