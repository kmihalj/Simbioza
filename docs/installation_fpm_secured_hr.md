# Instalacija Simbioze sa secured PHP-FPM-om

[Engleska verzija](installation_fpm_secured_en.md)

Ova cjelovita uputa kreće od praznog direktorija aplikacije i ne pretpostavlja
postojeće Simbioza FPM/deploy račune. Ne treba druga instalacijska uputa.
Setup alat izrađuje zasebne račune, grupe, pool/servis i ograničeni pomoćni
program: web-proces ne piše kod, ali može zatražiti paketne radnje od odvojenog
deploy radnika. Ovo je preporučeni način. Pri reinstalaciji sistemac prvo
provjerava postojeće identitete, oznake instanci i servise; drugi site ne smije
koristiti identitet prve instalacije.

**SimpleSAMLphp nije obvezan.** Ugrađena Simbiozina prijava, OIDC, CAS i OAuth2
ne trebaju ga. Najprije prođite korake 1–7, a neobvezni SAML postupak samo ako
upravo ta instalacija treba SAML autentikaciju.

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

## 2. Preuzmite čisto izdanje Simbioze 0.3.11

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
git clone --depth 1 --branch 0.3.11 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Očekuje se `0.3.11` i `success` uz svaki platformni preduvjet.
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
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

Na macOS-u s Homebrew PHP-om navedite stvarnu putanju aplikacije i FPM-a:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Za drugu instalaciju ponovite obje naredbe s istim dodatnim opcijama, npr.
`--instance=druga --listen=127.0.0.1:9076`. Iste opcije zatim navedite uz
`--finalize` u 7. koraku. Njihovo izostavljanje odabire drugi, zadani servis.

### Što 4. korak generira i pokreće

**Nemojte ručno stvarati ni popunjavati `php-fpm.conf`.** Gornja naredba
`--install` zapisuje cijelu namjensku FPM konfiguraciju, provjerava njezinu
sintaksu, registrira servis i pokreće ga odnosno ponovno pokreće. Naredba
`--check` nakon nje ne mijenja postavke: stvara i uklanja kratkotrajne probne
zahtjeve kroz stvarni helper. Provjerava i prolaz helpera i CLI podršku za
pozadinsku nadogradnju. Neuspješna provjera vraća izlazni status različit od
nule. Očekuju se `[OK] Real Setup helper round trip` i
`[OK] Background updater CLI runtime`; ne nastavljajte na Apache ni čarobnjak
ako neka od njih izostaje ili ne prođe.

Izričita provjera sa `sudo -H -u fpm-simbioza` odmah koristi novi radni račun,
bez ovisnosti o osvježenom članstvu grupa u vašoj trenutačnoj SSH prijavi.
Za imenovanu instancu koristite i njezin radni račun, npr. `fpm-simbioza-druga`.
Helper ostaje u vlasništvu root računa i ograničen na dopuštene radnje; web-procesu
nemojte dati pravo pisanja po kodu niti isključivati izolaciju datotečnog sustava.

Na Linuxu zadana instanca koristi
`/etc/php/<major.minor>/php-fpm-simbioza/php-fpm.conf`, pri čemu verzija odgovara
CLI PHP-u korištenom u 4. koraku. Uz PHP 8.4 to je točno
`/etc/php/8.4/php-fpm-simbioza/php-fpm.conf`. Datoteka sadrži i `[global]` i
namjenski pool `[simbioza]`: njegove račune, lokalni listener, putanje aplikacije
i `HPH_CONFIG_PATH`, privatne direktorije sesija i privremenih datoteka te PHP
ograničenja. To **nije** zajednički `/etc/php/8.4/fpm/php-fpm.conf` ni
`pool.d/www.conf`. Generator zapisuje i `php-fpm-simbioza.service`, uključujući
izolaciju datotečnog sustava i masku prava. Te systemd postavke nisu FPM INI
zapisi. Bez izmjena možete provjeriti koju konfiguraciju namjenski servis koristi:

```bash
sudo systemctl show php-fpm-simbioza.service -p ExecStart &&
sudo systemctl is-active php-fpm-simbioza.service
```

Za imenovanu instancu konfiguracijski direktorij i naziv servisa dobivaju isti
nastavak, npr. `php-fpm-simbioza-druga`. Na macOS-u alat umjesto toga stvara
`$(brew --prefix)/etc/simbioza/php-fpm.conf`, koji uključuje `pool.conf` iz istog
direktorija, i registrira namjenski launchd servis. Ispravnu generiranu datoteku
nemojte zamijeniti konfiguracijom drugog sitea. SimpleSAMLphp konfiguracija dodaje
se samo ako provedete neobvezne SAML korake. Apache/Nginx zasebno podešavate u
5. koraku; generiranje FPM-a ne podešava web-poslužitelj. Nakon uspješnog
4. koraka bez naknadnih izmjena nije potreban još jedan ručni restart FPM-a.

Generator iz 0.3.4 postavlja launchd `Umask` na cijeli broj **7** (oktalno
`0007`), jednako Linux postavci `UMask=0007`. Masku koriste web-proces i
ograničeni deploy worker; worker je postavlja **nakon** promjene računa.
Njihov `TMPDIR` jest `data/tmp` ove aplikacije, pa su zajednički privremeni
lockovi u istom privatnom direktoriju runtime grupe. Novi obični logovi imaju
`0660`, a ne `0644` uz koje updater ne može pisati u FPM-ov log.

Na macOS-u provjerite generirani servis prije otvaranja čarobnjaka:

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

Za imenovanu instancu odaberite odgovarajuću servisnu datoteku, npr.
`hr.simbioza-druga.php-fpm.plist`. Izostala maska neuspješan je preduvjet,
a ne razlog da kod postane zapisiv ili da se updater pokreće kao root.
Ne ponavljajte opću finalizaciju nad postojećom DEMO reset kopijom radi
popravka prava. Sesije ostaju privatne (`0600`); ne proširujte rekurzivno
prava cijelog direktorija podataka.

Provjera ne mijenja postavke aplikacije. Zadani pool sluša na `127.0.0.1:9075`; ta vrata
smiju biti dostupna samo lokalno. Alat daje FPM računu pravo pisanja po
radnim podacima, ali ne i po cijelom izdanju, te postavlja strogo ograničeni
pomoćni program za paketne radnje. Stvara i grupe za održavanje i rad.
Prije nastavka pregledajte sve rezultate provjere. Ne koristite `chmod 777`.

### Korisnici, grupe i prava koje postavlja alat

Gornja naredba `--install` uključuje kreiranje korisnika i grupa; nemojte ih
zasebno stvarati naredbama `useradd` i `groupadd`. Nepostojeći identiteti se
stvaraju, a postojeći s tim imenima koriste se bez zamjene UID/GID vrijednosti.
Alat usklađuje potrebne primarne grupe i dodatna članstva. Za imenovanu instancu
sva imena ispod dobivaju isti nastavak `-instanca`.

Pri reinstalaciji na **istoj aplikacijskoj putanji** ipak pokrenite `--install`
iz 4. koraka: on osvježava i root-owned helper izvan aplikacijskog direktorija.
Brisanje aplikacijskih datoteka ne uklanja taj helper. Postojeći računi/grupe
koriste se bez ručnog ponovnog stvaranja. Postojeća Apache konfiguracija može
ostati ako odgovara 5. koraku, uključujući stvarnu javnu putanju, ispravan
handler i kategorije `AllowOverride`.

| Identitet | Svrha |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Zaključani web-servisni račun i primarna grupa; ne mogu pisati po kodu aplikacije. |
| `simbioza-deploy` / `deploy-simbioza` | Zaključani račun za isporuku i grupa za održavanje koda; i vaš održavateljski račun član je te grupe. |
| `run-simbioza` | Zajednička privatna radna grupa za FPM, isporuku i održavatelja. |

Na Linuxu nakon ponovne SSH prijave pregledajte rezultat:

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

Kod koristi vlasništvo deploy računa i FPM izolaciju samo za čitanje. Privatni
radni direktoriji koriste grupu `run-simbioza` (`2770`), a konfiguracijski
korijen sticky direktorij (`3770`) za kontrolirane atomske zamjene. Uobičajeno
dijeljenje radnih podataka koristi ta grupna prava, ne široke ACL dozvole
pisanja. Uz neobveznu privatnu SimpleSAMLphp konfiguraciju alat dodatno postavlja
Linux pristupni i nasljedni ACL koji grupi `app-simbioza` daju **samo čitanje i
prolaz** kroz konfiguraciju. SAML postupak ispod sadrži postavljanje i provjeru
ACL-a. Na secured FPM nemojte primijeniti recept običnog/zajedničkog FPM-a koji
računu `www-data` daje pravo pisanja.

Uz zadani aplikacijski konfiguracijski direktorij, Auth, Email, HTML Editor,
Workspace, Menu, Theme i Backup imaju runtime-zapisive modulne direktorije.
Backup to treba za atomsku objavu zapisa providera. PHP servisna konfiguracija
kojom upravlja izdanje ostaje u vlasništvu deploy računa i read-only FPM-u.
Alat ne stvara ponovno `data/config` niti daje runtime prava uklonjenim starim
datotekama `editor-html.php`, `email.php` ili `workspace.php`.

## 5. Podesite web-poslužitelj

**Odaberite postupak prije pokretanja naredbi:**

- **Postojeći HTTPS virtualni host i URL prefiks poput `/simbioza`:** prijeđite izravno na
  [upute za URL podputanju](#neobvezno-instalacija-u-url-podputanji-npr-simbioza).
  Uredite postojeći vhost. Ne stvarajte `simbioza.conf` i ne pokrećite
  `a2ensite simbioza`; ta naredba uključuje samo novi, zasebni virtualni host.
- **Nova zasebna domena:** koristite Apache ili Nginx konfiguraciju ispod.

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
        AllowOverride FileInfo Indexes Options
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

Isporučeni `public/.htaccess` koristi `DirectoryIndex index.php`, za što je
potrebna kategorija dopuštenja `Indexes`; rewrite pravila trebaju `FileInfo`,
a `Options -Indexes` treba `Options`. Zadržite sve tri kategorije u
`AllowOverride`. Time se **ne uključuje** ispis direktorija: `Options -Indexes`
i dalje ga isključuje. Izostala kategorija može izazvati HTTP 500
(`DirectoryIndex not allowed here`) čak i kada `apache2ctl configtest` javlja
`Syntax OK`, jer se `.htaccess` čita tek pri zahtjevu za taj direktorij.
Nakon ponovnog učitavanja provjerite stvarni javni URL prije nastavka na
čarobnjak. Vidi
[Apacheovu uputu za DirectoryIndex](https://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryindex).
Roditeljski direktorij izdanja nikada ne izlažite kao korijenski web direktorij.

**Samo za novi zasebni `simbioza.conf` izrađen iznad**, provjerite i pokrenite
Apache. Preskočite ovaj blok kada koristite postojeći zajednički vhost.
Na Debianu/Ubuntuu:

```bash
test -f /etc/apache2/sites-available/simbioza.conf &&
sudo a2ensite simbioza &&
sudo apache2ctl configtest &&
sudo systemctl enable --now apache2 &&
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

Tri putanje imaju različite namjene:

| Postavka | Primjer |
| --- | --- |
| Korijen aplikacije korišten u 2. i 4. koraku | `/srv/simbioza` |
| Javni direktorij koji koriste i `Alias` i `<Directory>` | `/srv/simbioza/public` |
| Javni URL prefiks | `/simbioza` |

Korijen aplikacije ne mora biti unutar `DocumentRoot` direktorija zajedničke
domene. Držite ga izvan tog javnog stabla. Ovaj postupak ne traži simboličku
poveznicu na direktorij aplikacije. Nginx poveznica iznad nije povezana s time:
ona uključuje konfiguraciju poslužitelja, ne aplikacijske datoteke.

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9075"
    </FilesMatch>
</Directory>
```

U postojećem vhostu postavite i `ProxyTimeout 900`. Ako stara konfiguracija
sadrži `<Directory "/var/www/html/simbioza">` za nekadašnju poveznicu na javni
direktorij, **zamijenite taj blok** gornjim blokom stvarnog javnog direktorija;
nije dovoljno dodati samo `Alias`. PHP handler mora vrijediti za stvarni
datotečni cilj aliasa. Ne izlažite roditeljski aplikacijski direktorij,
`config/`, `data/` ni `vendor/` kako biste osposobili zastarjelu putanju.

Za korijen aplikacije `/data/www/simbioza` obje putanje u bloku postaju
`/data/www/simbioza/public`; javni URL ostaje `/simbioza`. Ne mijenjajte
`SIMBIOZA_APP_ROOT` i ne generirajte ponovno pool samo radi URL usmjeravanja.
Ako se mijenja sam korijen aplikacije, zasebno treba uskladiti FPM/helper
putanje; Apache alias ne premješta aplikaciju.

Uz Puppet/Ansible odgovarajuću promjenu napravite u izvornoj upravljanoj
vhost konfiguraciji. Nemojte trajno popravljati generiranu datoteku, stvarati
drugi HTTPS vhost istog imena ni uklanjati aliase drugih aplikacija. Ako
neobvezni SAML već ima dulji alias poput `/simbioza/simplesaml`, zadržite ga
**prije** općeg aliasa `/simbioza`. SAML i dalje nije obvezan.

Nakon promjene **samo Apache aliasa/direktorijske konfiguracije**, provjerite je
i kontrolirano učitajte Apache. Namjenski FPM servis već je pokrenut u 4. koraku:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Ovaj Linux blok koristite umjesto naredbi `a2ensite simbioza` za novi site
ako zajednički HTTPS vhost već postoji. Homebrew naredbe provjere/učitavanja
navedene su u prethodnom Apache poglavlju. Aktivan FPM servis i ispravna Apache
sintaksa ne dokazuju da zahtjev koristi ispravnu aplikacijsku putanju:
provjerite i javnu adresu `/simbioza/`. Stanite na HTTP 500 ili nedostajući
`bootstrap.php`; provjerite alias, direktorijski handler i FPM putanje umjesto
nastavka čarobnjaka ili općeg širenja prava.

#### Samo pri reinstalaciji: zastarjeli vlastiti FPM bind mount

Čista instalacija ne stvara dodatak za staru poveznicu na javni direktorij.
Ako ga je postojeća instalacija imala, prije restarta pregledajte ga:

```bash
sudo systemctl cat php-fpm-simbioza.service
sudo systemctl show php-fpm-simbioza.service -p DropInPaths
```

Za imenovanu instancu koristite servis s nastavkom. Nakon prelaska na izravni
alias uklonite **samo potvrđeno zastarjeli vlastiti dodatak** čija je jedina
namjena bila vezanje uklonjene putanje poveznice. Nikada ne brišite cijeli
`.service.d` direktorij niti uklanjajte generirana vezanja stvarne aplikacije.
Zastarjelo vezanje na nepostojeću putanju može spriječiti sljedeće pokretanje
FPM-a. Nakon tog konkretnog uklanjanja koristite ovaj cjelovit Linux slijed
umjesto gornjeg učitavanja samo Apachea. Vrijedi i nakon namjernog ručnog
uređivanja namjenske FPM konfiguracije; `daemon-reload` potreban je kada se
mijenja systemd servisna datoteka ili njezin dodatak, ne samo `php-fpm.conf`:

```bash
SIMBIOZA_PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')" &&
sudo systemctl daemon-reload &&
sudo "/usr/sbin/php-fpm$SIMBIOZA_PHP_VERSION" --test \
  --fpm-config="/etc/php/$SIMBIOZA_PHP_VERSION/php-fpm-simbioza/php-fpm.conf" &&
sudo apache2ctl configtest &&
sudo systemctl restart php-fpm-simbioza.service &&
sudo systemctl is-active php-fpm-simbioza.service &&
sudo systemctl reload apache2
```

Koristite odgovarajući FPM izvršni program, konfiguracijsku putanju i nastavak
servisa svoje instalacije. Očekuju se uspješna provjera FPM konfiguracije,
`Syntax OK` i `active`. Lanac `&&` zaustavlja se nakon neuspjele naredbe;
nakon greške nemojte nastaviti na čarobnjak. Restart FPM-a prekida njegove
trenutačne zahtjeve, pa ga ne pokrećite tijekom instalacije/nadogradnje.
Ovo je jednokratno čišćenje poslužitelja ili primjena namjerne izmjene
konfiguracije, **ne obvezan dodatni korak čiste instalacije**. Ako FPM
konfiguracija ostaje ista, sama promjena Apache aliasa ne traži restart FPM-a.

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
redu. Mora proći i CLI provjera pozadinske nadogradnje, a GUI upravljanje
paketima i nadogradnja aplikacije moraju biti dostupni. Sivi rezultat **Nije
provjereno** nije prolaz: prvo riješite prethodnu neuspješnu provjeru helpera;
ne znači da CLI ekstenzije nedostaju. Čarobnjak uvozi korisničke upute na
odabranim jezicima.

## 7. Učvrstite prava i provjerite instalaciju

Nakon čarobnjaka odjavite se i ponovno prijavite u SSH/terminal kako bi se
aktivirala nova članstva u deploy/runtime grupama. Ponovno postavite
`SIMBIOZA_APP_ROOT` na putanju iz 2. koraka, zatim pokrenite iste opcije:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
vendor/bin/hph modules migrate-status &&
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

**Preskočite cijelo poglavlje ako ne trebate SAML.** Ne odnosi se na OIDC, CAS,
OAuth2 ni ugrađenu prijavu. Lokalnog administratora i break-glass prijavu
zadržite dok SAML ne proradi. Javni demo ne treba SAML samo zato što njegove
Korisničke upute sadrže ovo poglavlje.

### S1. Odaberite biblioteku i javne adrese

Najprije dovršite osnovnu instalaciju. U održavateljskoj ljusci postavite
stvarne putanje i adrese. Primjer za zasebnu domenu:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

Za site `https://vas-host.example/simbioza` umjesto toga koristite
`SIMBIOZA_SAML_URL=https://vas-host.example/simbioza/simplesaml/`.
Zadržite završnu kosu crtu. Korijen biblioteke **nije** direktorij Simbioze.
Možete koristiti već instaliranu sistemsku biblioteku, npr.
`/usr/share/simplesamlphp-aai`; ne mijenjajte njezinu zajedničku konfiguraciju,
certifikate ni SP druge aplikacije.

Ako već postoji kompatibilna biblioteka, preskočite blok preuzimanja. Inače
primjer instalira upstream **2.5.3.1 full**, uz objavljenu kontrolnu sumu.
Traži PHP **8.3 ili noviji** i proširenja navedena u
[SimpleSAMLphp preduvjetima](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html).
Na [upstream stranici](https://simplesamlphp.org/download/) provjerite sigurnosne
nadogradnje; za drugu verziju zajedno promijenite URL i kontrolnu sumu.
Sljedeće izvršite samo za **novi**, još nepostojeći direktorij biblioteke:

Na Debianu/Ubuntuu najprije instalirajte alate za preuzimanje naredbom
`sudo apt-get install curl openssl`. macOS već ima `curl` i `openssl`.

```bash
test ! -e "$SIMBIOZA_SAML_ROOT" && test ! -L "$SIMBIOZA_SAML_ROOT" &&
SIMBIOZA_SAML_FETCH="$(mktemp -d)" &&
curl --fail --location --proto '=https' --tlsv1.2 \
  --output "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  https://github.com/simplesamlphp/simplesamlphp/releases/download/v2.5.3.1/simplesamlphp-2.5.3.1-full.tar.gz &&
test "$(openssl dgst -sha256 "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" | awk '{print $NF}')" = \
  b199ebcba6e30220e7b5df2783ca42f8947dd9bdf046e9e6302fef3d3a438d01 &&
sudo install -d -m 0755 "$SIMBIOZA_SAML_ROOT" &&
sudo tar -xzf "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  --strip-components=1 -C "$SIMBIOZA_SAML_ROOT"
```

Nakon bilo koje greške stanite. Provjerite odabranu biblioteku:

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

### S2. Pripremite privatnu konfiguraciju i pouzdane metapodatke

Iz `$SIMBIOZA_APP_ROOT` stvorite privatne direktorije i kopirajte **predloške**.
Blok odbija prepisati konfiguraciju. Ako vraćate sačuvane postavke **istog SP-a**,
umjesto toga vratite njegov `config`, certifikate i potrebne metapodatke;
ne kopirajte tajne drugog sitea i ne vraćajte sesije, cache ni staru
aplikacijsku bazu.

```bash
mkdir -p "$SIMBIOZA_APP_ROOT/data/saml/config" \
  "$SIMBIOZA_APP_ROOT/data/saml/metadata" "$SIMBIOZA_APP_ROOT/data/saml/cert" \
  "$SIMBIOZA_APP_ROOT/data/saml/data" "$SIMBIOZA_APP_ROOT/data/saml/cache" \
  "$SIMBIOZA_APP_ROOT/data/saml/logs" "$SIMBIOZA_APP_ROOT/data/saml/tmp" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/config.php" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/config.php.dist" "$SIMBIOZA_SAML_CONFIG/config.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/authsources.php.dist" "$SIMBIOZA_SAML_CONFIG/authsources.php"
```

Uredite `config.php` naredbom `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`.
U njegovu postojećem polju `$config` postavite ključeve ispod. Ogledne putanje
zamijenite stvarnom putanjom aplikacije; PHP ne proširuje varijable terminala.

| Ključ | Vrijednost ili radnja |
| --- | --- |
| `baseurlpath` | Puna HTTPS vrijednost `SIMBIOZA_SAML_URL`, sa završnom kosom crtom. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` i `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | Odgovarajući privatni direktoriji `data`, `cache`, `logs`, `tmp` pod `/srv/simbioza/data/saml`. |
| `metadata.sources` | Pouzdani izvor, npr. `[['type' => 'flatfile']]` uz privatni `metadatadir`; po potrebi koristite provjereni mehanizam svoje federacije. |
| `secretsalt`, `assets['salt']` | Dvije različite slučajne vrijednosti, svaku generirajte s `openssl rand -hex 32`; `salt` uredite unutar postojećeg polja `assets`, ne novim glavnim ključem `assets.salt`. Ne koristite predloške i ne objavljujte ih. |
| `auth.adminpassword` | Hash interaktivno generiran s `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"`; odvojen od Simbiozine lozinke. |
| `store.type` | `phpsession` za ovaj postupak. |
| `session.phpsession.cookiename` | Naziv specifičan za site, npr. `SimbiozaSAML`, različit od Simbiozine sesije i sesija drugih siteova. |
| `session.phpsession.savepath` | `null`, pa oba endpointa koriste privatni `data/sessions` ovog poola. |
| `session.cookie.path` | `/` za zasebnu domenu ili `/simbioza/` za taj prefiks; mora obuhvatiti aplikaciju i SAML endpoint. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true`; koristite HTTPS. |
| `showerrors` | `false` osim tijekom kontrolirane dijagnostike. |

U `authsources.php` podesite `default-sp` kao `saml:SP`, s vlastitim stabilnim
`entityID` i `idp` entity ID-em pouzdanog IdP-a. Uklonite nekorištene primjere.
Metapodatke IdP-a preuzmite od administratora federacije/IdP-a; pouzdane PHP
metapodatke spremite u `data/saml/metadata/saml20-idp-remote.php` ili podesite
podržani mehanizam svoje federacije. Ne isključujte provjeru potpisa i ne
vjerujte certifikatu iz neprovjerenog izvora.

Ako IdP traži SP certifikat, za **novi** SP generirajte privatni ključ i
certifikat u `data/saml/cert` ovog sitea te njihova imena povežite ključevima
`privatekey` i `certificate` u `default-sp`, prema politici federacije.
Za **isti registrirani SP** zadržite njegov EntityID, postojeće ključeve,
certifikate i potrebne metapodatke; ne generirajte zamjenske.

### S3. Podesite namjenski pool i provjerite ACL prava

Iz `$SIMBIOZA_APP_ROOT` ponovno pokrenite generator za **istu aplikaciju i
instancu**, s argumentom privatne konfiguracije. Time se ažurira i ponovno
pokreće samo taj namjenski pool, uz korištenje postojećih korisnika i grupa:

```bash
cd "$SIMBIOZA_APP_ROOT" &&
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG"
```

Na macOS-u **svim trima** naredbama dodajte
`--php-fpm="$(brew --prefix)/sbin/php-fpm"`. Za imenovanu instancu ponovite i
izvorne opcije `--instance` i `--listen`. Ovo nije postupak popravka DEMO
reset kopije. Generator postavlja `SIMPLESAMLPHP_CONFIG_DIR` unutar FPM radnika
unatoč `clear_env = yes`; samo SSH `export` ili Apacheov `SetEnv` nisu dovoljni.

Na Linuxu provjerite čitanje i ACL privatne konfiguracije:

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

Očekujte `group:app-simbioza:r-x` i odgovarajući nasljedni ACL na direktoriju;
FPM datoteke smije čitati, ne mijenjati. Vlasnik konfiguracije ostaje deploy
račun. Za imenovanu instancu koristite identitete s nastavkom. Na macOS-u
generator koristi grupno vlasništvo umjesto Linux POSIX ACL naredbi.

### S4. Usmjerite SAML endpoint u isti FPM pool

Blok dodajte **postojećem HTTPS vhostu**, ne drugom vhostu ili zajedničkom
globalnom SimpleSAMLphp aliasu. Zamijenite URL prefiks i putanje vrijednostima
iz S1; Apache ne proširuje varijable ljuske. Dulji SAML alias mora biti
**prije** općeg aplikacijskog aliasa `/simbioza`.

```apache
Alias /simplesaml "/opt/simplesamlphp/public"
<Directory "/opt/simplesamlphp/public">
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    AcceptPathInfo On
</Directory>
ProxyPassMatch "^/simplesaml/(.+\.php(?:/.*)?)$" "fcgi://127.0.0.1:9075/opt/simplesamlphp/public/$1"
```

Za `/simbioza` koristite `Alias /simbioza/simplesaml` i regex
`^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. Za sistemski paket **obje** putanje
`/opt/simplesamlphp/public` zamijenite njegovim stvarnim `public/` direktorijem.
Koristite stvarni port svojeg poola. Izričiti proxy čuva PATH_INFO u
`module.php/...` i koristi aplikacijski FPM identitet, okruženje i sesijski
direktorij. Obični aplikacijski PHP handler ne pokriva taj zasebni alias.

Na Debianu/Ubuntuu provjerite i tek zatim kontrolirano učitajte Apache:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Za Homebrew Apache koristite `sudo /opt/homebrew/bin/httpd -t` i samo nakon
uspjeha `sudo /opt/homebrew/bin/httpd -k graceful`. Uz Puppet/Ansible mijenjajte
izvornu upravljanu vhost konfiguraciju, ne generiranu datoteku. Za Nginx koristite
[službeni alias/PATH_INFO primjer](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx)
s ovim istim namjenskim poolom i URL prefiksom, ne zajedničkim zadanim poolom.
Web alias nikada ne smije izložiti `config/`, `cert/` ni privatne metapodatke.

### S5. Registrirajte SP, mapirajte atribute i provjerite prijavu

Otvorite SAML osnovnu adresu i kroz SimpleSAMLphp administratorsko sučelje
preuzmite metapodatke za `default-sp`. Predajte ih administratoru IdP-a ili
federacije. Provjerite da registrirani **EntityID, ACS i SLO** koriste HTTPS
SAML prefiks ovog sitea te pričekajte aktivaciju registracije.

U Simbiozi pod **Postavke → Prijava** uključite SimpleSAMLphp protokol.
Stvorite SAML profil s apsolutnom putanjom biblioteke `vendor/autoload.php`
i `auth_source = default-sp`. Naredbom **Testiraj profil** dohvatite stvarne
IdP atribute; odaberite stabilni subject/login atribut i mapirajte email/ime.
Ne nagađajte nazive prema drugom provideru. Testirajte dok profil nije operativan,
uključite ga i provjerite da mu namijenjeni korisnici smiju pristupiti.
Automatsko kreiranje računa izričit je odabir profila.

GUI Simbiozine postavke zapisuje u `HPH_CONFIG_PATH/auth/auth.json`; ne uređuje
vanjske SimpleSAMLphp datoteke. Za ručni Auth config počnite od predloška
`auth.json.dist` u Auth modulu, nikad prepisivanjem postojeće aktivne datoteke.

U privatnom pregledniku provjerite: SAML prijava → povratak u Simbiozu →
zaštićena stranica → odjava → nova prijava. Učitani metapodaci nisu dokaz
ispravne autentikacije. Uz `phpsession` drugi pool/sesijski direktorij može
izazvati petlju prijave: ispravite usmjeravanje/okruženje, ne širite prava.
Lokalni administratorski pristup zadržite dok cijeli postupak ne prođe.

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

