# Instalacija Simbioze sa secured PHP-FPM-om

[Engleska verzija](installation_fpm_secured_en.md)

> Lokalna uputa kandidata **0.3.3**. Tag za preuzimanje nije javan prije odobrenja izdanja.

Ova cjelovita uputa kreće od praznog direktorija aplikacije i ne pretpostavlja
postojeće Simbioza FPM/deploy račune. Ne treba druga instalacijska uputa.
Setup alat izrađuje zasebne račune, grupe, pool/servis i ograničeni pomoćni
program: web-proces ne piše kod, ali može zatražiti paketne radnje od odvojenog
deploy radnika. Ovo je preporučeni način. Pri reinstalaciji sistemac prvo
provjerava postojeće identitete, oznake instanci i servise; drugi site ne smije
koristiti identitet prve instalacije.

## 1. Pripremite poslužitelj

Krenite iz običnog administratorskog terminala na Linuxu ili macOS-u.
Za HTTPS primjere trebate domenu koja pokazuje na poslužitelj i valjan TLS
certifikat. Izvršite samo naredbe za svoj sustav. Nakon greške nemojte nastaviti.

Na Debianu/Ubuntuu instalirajte alate i PHP ekstenzije:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
```

Na macOS-u najprije instalirajte Homebrew ako ga nemate, zatim:

```bash
brew install git rsync composer httpd php
```

Provjerite instalirane alate:

```bash
php -v
php -m
composer --version
git --version
```

PHP mora biti **8.2 ili noviji**, Composer verzije 2, a potrebne PHP ekstenzije
su `ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip`.
Za odabranu bazu treba `pdo_sqlite`, `pdo_mysql` ili `pdo_pgsql`.
CLI i web PHP moraju imati odgovarajuće verzije i ekstenzije.

## 2. Preuzmite čisto izdanje Simbioze 0.3.3

Odaberite **jedan** direktorij aplikacije. Na Linuxu:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

Na macOS-u koristite interni disk, ne razvojni Git direktorij ni volumen
montiran s `noowners`:

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Zadržite ovaj terminal otvorenim. Ako trebate drugu putanju, promijenite je
sada. Direktorij još **ne smije postojati**; provjera namjerno odbija pisanje
preko postojeće instalacije. Pri reinstalaciji sistemac prethodno zasebno
zbrinjava stari servis i podatke. Ove naredbe ih ne brišu.

Cijeli blok preuzimanja pokrenite kao obični račun održavatelja:

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.3 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Očekuje se `0.3.3` i `success` uz svaki platformni preduvjet.
Gitova poruka **detached HEAD** očekivana je pri preuzimanju taga.
Composer ne pokrećite sa `sudo`, ne kopirajte tuđu konfiguraciju ili bazu,
ne prenosite `.git/` u aplikaciju i ovdje ne koristite `--no-dev`.
Verzija 0.3.0 polazna je nova instalacija, ne nadogradnja s 0.2.x.

Zadani korijen konfiguracije je `config/` (`HPH_CONFIG_PATH`), s vlastitim
poddirektorijem za svaki modul. Trajni podatkovni sadržaj pripada `data/`.
Web-poslužitelj izlaže samo `public/`. Za prvu instalaciju zadržite zadane
putanje; vanjski konfiguracijski slojevi napredna su mogućnost, ne preduvjet.

## 3. Odaberite i pripremite bazu

Odaberite **jednu** bazu. SQLite je najkraći postupak: ne treba bazni servis
ni lozinku baze. U čarobnjaku odaberite SQLite i nastat će
`data/simbioza.sqlite`. Nastavite na 4. korak.

Za **MySQL** pokrenite MySQL/MariaDB servis i otvorite njegov administratorski
klijent. Izradite praznu bazu i ograničeni aplikacijski račun:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<jedinstvena-lozinka-baze>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

U čarobnjaku koristite host `127.0.0.1`, port svojeg servisa (obično `3306`),
bazu `simbioza`, korisnika `simbioza` i tu lozinku.
Aplikacija ne smije koristiti administratorski račun baze.

Za **PostgreSQL** pokrenite njegov servis i izvršite ove naredbe kao
PostgreSQL administrator (na Debianu/Ubuntuu ispred svake dodajte
`sudo -u postgres`):

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

U čarobnjaku koristite host `127.0.0.1`, port svojeg servisa (obično `5432`),
bazu i korisnika `simbioza` te lozinku unesenu u skrivenom upitu.
Provjerite dopušta li lokalna TCP autentikacija baze taj račun.
Stvarne lozinke ne stavljajte u upute, povijest terminala ni Git.

## 4. Stvorite namjenski pool

Na Debianu instalirajte PHP-FPM za istu verziju PHP-a koju koristi CLI i paket
`acl`. Iz direktorija izdanja jednokratno pokrenite:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

Na macOS-u s Homebrew PHP-om navedite stvarnu putanju aplikacije i FPM-a:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Za drugu instalaciju ponovite obje naredbe s istim dodatnim opcijama, npr.
`--instance=druga --listen=127.0.0.1:9076`. Iste opcije zatim navedite uz
`--finalize` u 7. koraku. Njihovo izostavljanje odabire drugi, zadani servis.

Provjera ništa ne mijenja. Zadani pool sluša na `127.0.0.1:9075`; ta vrata
smiju biti dostupna samo lokalno. Alat daje FPM računu pravo pisanja po
radnim podacima, ali ne i po cijelom izdanju, te postavlja strogo ograničeni
pomoćni program za paketne radnje. Stvara i grupe za održavanje i rad.
Prije nastavka pregledajte sve rezultate provjere. Ne koristite `chmod 777`.

Uz zadani aplikacijski konfiguracijski direktorij, Auth, Email, HTML Editor,
Workspace, Menu, Theme i Backup imaju runtime-zapisive modulne direktorije.
Backup to treba za atomsku objavu zapisa providera. PHP servisna konfiguracija
kojom upravlja izdanje ostaje u vlasništvu deploy računa i read-only FPM-u.
Alat ne stvara ponovno `data/config` niti daje runtime prava uklonjenim starim
datotekama `editor-html.php`, `email.php` ili `workspace.php`.

## 5. Podesite web-poslužitelj

Odaberite **jednu** konfiguraciju. Primjeri služe zasebnoj domeni na `/`;
zamijenite domenu, putanju izdanja i TLS certifikat. Za instalaciju u
podputanji podesite i URL prefiks na web-poslužitelju te punu adresu predajte
naredbi `install:prepare` u 6. koraku.

### Apache s PHP-FPM-om

Uključite Apache module `rewrite`, `proxy`, `proxy_fcgi` i TLS. PHP zahtjeve
ove instalacije usmjerite u namjenski pool, a ne kroz `mod_php`.

Na Debianu/Ubuntuu uključite Apacheove FPM proxy module i stvorite site:

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Na macOS-u naredbom `sudoedit` uredite `/opt/homebrew/etc/httpd/httpd.conf`.
Uključite `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module`
i `socache_shmcb_module`. Dodajte `Listen 443` ako ondje već nema listenera.
Za ovaj site nemojte dodati mod_php handler.

Za novi Homebrew Apache u `httpd.conf` izričito postavite zajednički
identitet radnika (web-radnik ne smije koristiti vaš račun vlasnika koda):

```apache
User _www
Group _www
```

U konfiguraciji ispod zamijenite domenu, putanje TLS certifikata i
**obje** putanje `/srv/simbioza/public` stvarnom aplikacijskom putanjom
(na macOS-u obično `/Users/Shared/Simbioza/simbioza/public`).
Apache ne proširuje `$SIMBIOZA_APP_ROOT` iz vašeg terminala.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public
    ProxyTimeout 900

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler "proxy:fcgi://127.0.0.1:9075"
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /putanja/do/fullchain.pem
    SSLCertificateKeyFile /putanja/do/privkey.pem
</VirtualHost>
```

Provjerite Apache konfiguraciju prije kontroliranog ponovnog učitavanja.
Roditeljski direktorij izdanja nikada ne izlažite kao korijenski web direktorij.

Nakon spremanja provjerite i pokrenite Apache. Na Debianu/Ubuntuu:

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

Na macOS-u:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Očekuje se `Syntax OK`; nakon neuspjele provjere ne pokrećite/učitavajte servis.
Za već aktivni Apache nakon provjere napravite jedno kontrolirano ponovno
učitavanje, ne pokrećite drugi servis. Nastavite na installer; alternativni
Nginx ispod nije dodatni korak.

### Nginx s PHP-FPM-om

Nginx ne može izvršavati `mod_php`; PHP zahtjeve šalje istom namjenskom FPM
poolu. Pravila `SCRIPT_FILENAME` i `try_files` moraju ostati zajedno:

Ovu alternativu odaberite **umjesto Apachea**: u popisu paketa 1. koraka
koristite `nginx` umjesto `apache2` (`brew install nginx` na macOS-u)
i ne izvršavajte naredbe konfiguracije/pokretanja Apachea. Uredite
`/etc/nginx/sites-available/simbioza` na Debianu/Ubuntuu ili
`/opt/homebrew/etc/nginx/nginx.conf` na macOS-u. U cijelom server bloku
ispod zamijenite domenu, putanje certifikata i korijen aplikacije.

```nginx
server {
    listen 443 ssl;
    server_name simbioza.example.org;
    root /srv/simbioza/public;
    index index.php;

    ssl_certificate /putanja/do/fullchain.pem;
    ssl_certificate_key /putanja/do/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9075;
        fastcgi_read_timeout 900s;
    }

    location ~ /\. {
        deny all;
    }
}
```

Na Debianu/Ubuntuu uključite novi server blok, provjerite i pokrenite:

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

Na macOS-u: `sudo /opt/homebrew/bin/nginx -t`, zatim
`sudo /opt/homebrew/bin/brew services start nginx`.
Ako poveznica ili servis već postoji, prvo ga provjerite, ne duplicirajte ga.

Provjerite Nginx konfiguraciju prije ponovnog učitavanja. FPM port ne smije
biti dostupan nepouzdanoj mreži. Priprema paketa kroz grafički instaler može
trajati nekoliko minuta; čekanje od 900 sekundi sprječava zadanu pogrešku 504
nakon 60 sekundi dok ograničeni radnik još radi. Uskladite i čekanje svakog
dodatnog posredničkog proxyja, ali ne ukidajte ograničenja drugim siteovima.
Namjenski pool koji izrađuje `configure_fpm_setup.php` postavlja i PHP-ovo
`max_execution_time` na 900 sekundi. To je važno: na provjerenom macOS/PHP
8.5 okruženju vrijednost `0` ipak je prekidala dugačak zahtjev nakon jedne
minute, premda bi trebala značiti bez ograničenja. Nakon generiranja poola
provjerite oba vremenska ograničenja i testirajte dulju pripremu paketa.

### Neobvezno: instalacija u URL podputanji, npr. /simbioza

Za zajedničku domenu ovaj blok stavite **unutar njezina postojećeg HTTPS
virtualnog hosta**, ne izrađujte još jedan virtualni host istog imena.
Zamijenite putanju izdanja, URL prefiks i (za FPM) port poola:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9075"
    </FilesMatch>
</Directory>
```

Installeru predajte `--base-url=https://vas-host.example/simbioza`, ne samo
domenu bez prefiksa. Za demo na `/demo` promijenite **oba** primjera prefiksa
i koristite `--base-url=https://vas-host.example/demo`.
Direktorij je datotečna putanja, a prefiks URL putanja, ne naziv direktorija
aplikacije. Privatni `config/` i `data/` ne izlažite aliasom.
Prije kontroliranog ponovnog učitavanja provjerite Apache konfiguraciju.

## 6. Provedite grafičku instalaciju

Iz direktorija izdanja stvorite jednokratnu adresu instalera:

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Ako ste odabrali `--instance=druga`, koristite `-u fpm-simbioza-druga`.
PHP se izvršava kao FPM identitet, ne kao root. Za instalaciju na `/simbioza` koristite
`--base-url=https://simbioza.example.org/simbioza`. Adresu otvorite u
privatnom prozoru preglednika; token nemojte dijeliti ni prikazivati na
screenshotu. Čarobnjak provjerava preduvjete i bazu, traži naziv sitea,
jezike, vremensku zonu, prvog administratora i opcionalne module te prije
instalacije prikazuje pregled. Svi opcionalni moduli unaprijed su označeni;
odznačite one koje ne želite. Ako namjenski pomoćni program radi, odabrani
opcionalni paketi instaliraju se kroz GUI.

Ako svjesno ne koristite namjenski pomoćni program, prije nastavka obrasca
kao deploy korisnik pripremite odabrane opcionalne pakete naredbom
`php scripts/installation_packages.php prepare` (svi moduli) ili navedite
`--modules=theme,calendar` za uži izbor. Popis prilagodite stvarnom odabiru i
osvježite instalacijski obrazac. Bez pomoćnog programa kasnije GUI izmjene
paketa nisu dostupne. Instalacija/ažuriranje/uklanjanje jezika piše samo
runtime podatke i konfiguraciju te ne treba taj helper. Osigurajte da PHP
može pisati u te direktorije; vidi [lokalizaciju](localization_hr.md).

Nakon instalacije prijavite se kao administrator. U **Postavke → Setup i
moduli** provjerite da su namjenski pool, pomoćni program i radna prava u
redu. Čarobnjak uvozi korisničke upute na odabranim jezicima.

## 7. Učvrstite prava i provjerite instalaciju

Nakon čarobnjaka odjavite se i ponovno prijavite u SSH/terminal kako bi se
aktivirala nova članstva u deploy/runtime grupama. Ponovno postavite
`SIMBIOZA_APP_ROOT` na putanju iz 2. koraka, zatim pokrenite iste opcije:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Na macOS-u ponovite putanju aplikacije i argument `--php-fpm` iz 4. koraka.
Odjavite se i ponovno prijavite kako bi održavatelj dobio novo članstvo u
grupama. Očekujte nula migracija na čekanju, ispravnu prijavu i početnu
stranicu te zaključan `/install`. Sigurnosno kopirajte bazu, `config/`,
korisničke datoteke i podatke teme.

Jednokratni `sudo` za sistemsko podešavanje **ne znači** da redovne nadogradnje
traže root. Naredbe za održavanje nalaze se na kraju ove upute.

## Neobvezna integracija sa SimpleSAMLphp-om

Ako koristite SAML, njegov endpoint i Simbioza moraju prolaziti kroz **isti**
namjenski FPM pool kada se sesije čuvaju s `store.type = phpsession`.
Privatne SAML postavke i radne podatke držite pod `data/` te instalacije.
Privatna konfiguracija mora imati zasebne saltove, administratorsku lozinku,
nazive kolačića i putanju; nikad ne kopirajte tajne ni SP ključeve druge
instalacije.

Prije stvaranja poola pripremite `data/saml/config/config.php`,
`authsources.php` te privatne direktorije za certifikate i radne podatke.
Dodajte `--simplesaml-config-dir=/srv/simbioza/data/saml/config` i pri
`--install` i pri `--finalize`. Aplikacijski `/simplesaml` PHP endpoint
usmjerite u isti pool, a odgovarajuće EntityID, ACS i SLO adrese registrirajte
kod pružatelja identiteta. Stari endpoint ne gasite dok nova registracija nije
propagirana i dok cijeli postupak prijave i odjave u pregledniku ne prođe.

## Održavanje nakon instalacije

Redovne CLI naredbe izvršava vlasnik koda, ne web-račun i ne root.
Prije nadogradnje kopirajte bazu, aktivne direktorije `HPH_CONFIG_PATH`
i trajne podatke iz `data/`.

Provjere koje ne mijenjaju podatke:

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

Za kasnije dodavanje modula koristite
`vendor/bin/hph modules add calendar --fresh`;
`modules disable calendar` čuva podatke, a `modules enable calendar`
ponovno ga uključuje. Uklanjanje koristi `modules remove calendar --yes`
i izrađuje kopiju modula; pregledajte je naredbom `modules backups calendar`
prije namjernog `modules add calendar --restore`. To su alternative, ne blok
koji treba izvršiti od početka do kraja.

Jezični paketi ne ovise o izdanju aplikacije. Koristite
`vendor/bin/hph languages install de` ili `vendor/bin/hph languages update`;
njihov GUI radi kada su radni direktoriji zapisivi.

Kada želite nadograditi aplikaciju, kao vlasnik koda/deploy održavatelj
pokrenite `php update.php`. Updater prvo osvježava sebe, čuva odabrane module
i privatnu konfiguraciju, izvršava migracije i osvježava ugrađene upute.
Na instaliranom siteu nemojte ga zamijeniti samostalnim `composer update`.
Prije migracija neuspjeh može vratiti kod; nakon početka migracija održavanje
ostaje uključeno radi kontroliranog oporavka. Nemojte brisati
`data/update-maintenance.json` kako biste prikrili nedovršenu nadogradnju.

Na postojećoj **Linux instalaciji 0.3.2**, helper generiran bez read-only
override slojeva može pasti s porukom `Source path is not absolute`. S dodatnim
slojem može stati na 60% jer deploy worker ne može zamijeniti isporučene datoteke
u read-only `config/`. Ne isključujte izolaciju web procesa niti uređujte bazu.
Prvu nadogradnju pokrenite iz CLI-ja kao
stvarni deploy/vlasnik koda; zatim konfiguratorom nove verzije ponovno generirajte
helper uz istu instancu, listener i konfiguracijske putanje. Nove instalacije 0.3.3
izostavljaju prazna bind svojstva i razlikuju upis isporučenih datoteka pouzdanog
deploy workera od nepromijenjenih read-only nižih slojeva FPM-a. Privatni vanjski
niži slojevi ostaju read-only za oba procesa, a updater zasebno čuva privatnu
konfiguraciju.

Nakon toga potvrdite nula migracija na čekanju, stvarnu prijavu, sadržaj i
privitke. Primjeri koriste zadane putanje `config/` i `data/`; ako kasnije
odaberete vanjske konfiguracijske slojeve, izričito pripremite njihova prava
i obuhvat sigurnosnih kopija.

## Vanjski konfiguracijski slojevi

Prije odabira u privatnom `framework.php` pripremite cijelu konfiguraciju.
Generirani pool i fiksni helper prenose isti bazni i override parametar; ne ovise
o očuvanju naslijeđenih varijabli kroz `clear_env` ili `sudo`. Linux izolacija
veže niže slojeve read-only, a završni sloj i podešeni direktorij Setup zahtjeva
čini dostupnima. Pisanje je dopušteno samo gdje ga Unix prava već dopuštaju;
alat ne mijenja vlasništvo ni prava vanjskih slojeva.

FPM-u i deploy računu dajte čitanje/prolaz. PHP servisni kod neka pripada deploy
računu i bude read-only FPM-u; runtime podatkovne datoteke/lockovi trebaju
dijeljenu runtime grupu i pravo atomske zamjene. Privatnu konfiguraciju ne
stavljajte u zaštićeni home niti javni web-korijen. Nestandardni direktorij
zahtjeva unaprijed stvorite s potrebnim dijeljenim pravima. Nakon promjene putanja
ponovite `--install`, zatim `--finalize`, uz istu instancu, port i SAML opcije.
Sam `--finalize` osvježava helper/vlasništvo, ne FPM pool. Pogledajte
[konfiguracijsku uputu](configuration_hr.md).

