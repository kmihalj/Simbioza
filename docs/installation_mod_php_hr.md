# Instalacija Simbioze s Apache mod_php modulom

[Engleska verzija](installation_mod_php_en.md)

Ovo je cjelovit postupak nove instalacije, od običnog terminala do prve
administratorske prijave. Ne treba druga instalacijska uputa. Apache izvršava
PHP u zajedničkom web-procesu; radnje s paketima i nadogradnje aplikacije
ostaju u CLI-ju. Nginx ne može koristiti mod_php. Nemojte web-računu omogućiti
pisanje po aplikacijskom kodu radi GUI instaliranja paketa.

## 1. Pripremite poslužitelj

Krenite iz običnog administratorskog terminala na Linuxu ili macOS-u.
Za HTTPS primjere trebate domenu koja pokazuje na poslužitelj i valjan TLS
certifikat. Izvršite samo naredbe za svoj sustav. Nakon greške nemojte nastaviti.

Na Debianu/Ubuntuu instalirajte alate i PHP ekstenzije:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql libapache2-mod-php
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

## 2. Preuzmite čisto izdanje Simbioze 0.3.6

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
git clone --depth 1 --branch 0.3.6 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Očekuje se `0.3.6` i `success` uz svaki platformni preduvjet.
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

## 4. Pripremite uska radna prava

Sistemski paketi osiguravaju zajednički web-račun: `www-data` na
Debianu/Ubuntuu, `_www` za Homebrew Apache primjer. Prije ovih naredbi
potvrdite stvarnog korisnika/grupu Apachea. Kod ostaje u vlasništvu
održavatelja; web-račun piše samo radne podatke i dinamičke postavke.

Na Debianu/Ubuntuu:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp www-data config
sudo chmod 3770 config
sudo chgrp -R www-data data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo setfacl -R -m u:"$SIMBIOZA_MAINTAINER":rwX,u:www-data:rwX data
sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp www-data "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec setfacl -m d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx {} +
```

Na macOS-u:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp _www config
sudo chmod 3770 config
sudo chgrp -R _www data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp _www "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

Web-računu ne dopustite pisanje u korijen izdanja, `src/`, `scripts/`,
`public/`, `vendor/`, `composer.json` ni statičku PHP servisnu konfiguraciju.
Sticky konfiguracijski direktoriji dopuštaju atomske zamjene web-postavki,
ali ne i zamjenu servisnog koda čiji je vlasnik održavatelj.
Nikada ne koristite `chmod 777`.

## 5. Potvrdite da Apache doista izvršava mod_php

Verzija CLI PHP-a sama po sebi ne dokazuje koji PHP koristi Apache. Provjerite
aktivne Apache module i handler te da PHP u pregledniku ima podržanu verziju
i potrebne ekstenzije. Uobičajeni `mod_php` bez podrške za niti traži Apache
MPM `prefork`. Na istoj instalaciji nemojte istodobno uključiti FPM proxy
handler i `mod_php` handler.

Na Debianu/Ubuntuu uključite PHP handler bez niti i potrebne module:

```bash
sudo a2dismod mpm_event
sudo a2enmod mpm_prefork rewrite ssl
sudo a2enmod "php$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Na macOS-u naredbom `sudoedit` uredite `/opt/homebrew/etc/httpd/httpd.conf`.
Uključite `mpm_prefork_module`, `rewrite_module`, `ssl_module` i
`socache_shmcb_module`; isključite `mpm_event_module`. Dodajte `Listen 443` ako listener ondje
već ne postoji, zatim dodajte:

```apache
LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so
```

Za novi Homebrew Apache u `httpd.conf` izričito postavite zajednički
identitet radnika (web-radnik ne smije koristiti vaš račun vlasnika koda):

```apache
User _www
Group _www
```

Sljedeći virtualni host upišite u odabranu konfiguracijsku datoteku.
Zamijenite domenu i putanje certifikata, a **obje** aplikacijske putanje
odabranim `SIMBIOZA_APP_ROOT/public`. Apache konfiguracija ne proširuje
varijable iz vašeg terminala.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /putanja/do/fullchain.pem
    SSLCertificateKeyFile /putanja/do/privkey.pem
</VirtualHost>
```

Direktiva `LoadModule` za PHP ovisi o distribuciji i mora upućivati na
stvarno instalirani modul. Provjerite Apache konfiguraciju prije kontroliranog
ponovnog učitavanja. Za instalaciju u podputanji podesite i alias te punu
osnovnu adresu navedite u 7. koraku.

Provjerite i pokrenite samo ovako podešen Apache servis. Na Debianu/Ubuntuu:

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

Očekuje se `Syntax OK`. Ako servis već postoji, nakon provjere konfiguraciju
učitajte kontrolirano, bez restarta nepovezanih siteova.

### Neobvezno: instalacija u URL podputanji, npr. /simbioza

Za zajedničku domenu ovaj blok stavite **unutar njezina postojećeg HTTPS
virtualnog hosta**, ne izrađujte još jedan virtualni host istog imena.
Zamijenite putanju izdanja, URL prefiks i (za FPM) port poola:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "application/x-httpd-php"
    </FilesMatch>
</Directory>
```

Installeru predajte `--base-url=https://vas-host.example/simbioza`, ne samo
domenu bez prefiksa. Za demo na `/demo` promijenite **oba** primjera prefiksa
i koristite `--base-url=https://vas-host.example/demo`.
Direktorij je datotečna putanja, a prefiks URL putanja, ne naziv direktorija
aplikacije. Privatni `config/` i `data/` ne izlažite aliasom.
Prije kontroliranog ponovnog učitavanja provjerite Apache konfiguraciju.

## 6. Pripremite opcionalne pakete u CLI-ju

Grafički instaler ne može pokrenuti Composer kao Apache web-račun. Vlasnik
koda iz direktorija izdanja unaprijed priprema opcionalne module. Naredba bez
popisa priprema **sve** opcionalne module, jer su svi unaprijed označeni u
čarobnjaku:

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

Ako želite samo dio modula, primjerice Temu, Kalendar i E-poštu, pripremite
upravo njih i ostale odznačite u čarobnjaku:

```bash
php scripts/installation_packages.php prepare --modules=theme,calendar,email
php scripts/installation_packages.php status
```

Ako ne želite nijedan opcionalni modul, upotrijebite
`php scripts/installation_packages.php prepare --modules=`. Tada se privremeno
priprema samo Backup potreban za početne upute, a u čarobnjaku odznačite sve
opcionalne module.

Naredba `status` pokazuje koji su paketi stvarno instalirani. Čarobnjak će
nedostupan paket označiti kao paket koji prvo treba pripremiti u CLI-ju;
nakon pripreme osvježite stranicu. Web-računu nemojte dati
pravo pisanja u `vendor/` ili `composer.json` radi zaobilaženja te granice.

## 7. Provedite grafičku instalaciju

Stvorite jednokratnu adresu instalera:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Ako instalirate u podputanji, uključite je u `--base-url`. Adresu s tokenom
otvorite u privatnom prozoru; nemojte je dijeliti ni snimati. Čarobnjak
provjerava preduvjete i bazu, traži naziv sitea, barem jedan jezik, vremensku
zonu, prvog administratora i opcionalne module (sve unaprijed odabrane) te prije instalacije prikazuje
pregled. Uvozi korisničke upute na odabranim jezicima.

Čarobnjak stvara privatne datoteke pod web-računom. Prije prve CLI naredbe
održavatelja učvrstite dijeljeni pristup samo tim radnim datotekama;
time se aktiviraju i naslijeđene Linux ACL maske datoteka početno stvorenih
s pravima `0600`. Statički PHP servisni kod se ne mijenja:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo find data -type f -exec chmod 0660 {} +
sudo find config -type f \( -name '*.json' -o -name '*.lock' \) -exec chmod 0660 {} +
for SIMBIOZA_FILE in database.php env.php installation.php modules.php languages.php; do
  if [ -f "config/$SIMBIOZA_FILE" ]; then
    sudo chmod 0660 "config/$SIMBIOZA_FILE"
  fi
done
```

Nakon uspješne instalacije pokrenite čišćenje privremenog stanja pripreme.
Backup ostaje instaliran ako je ostao odabran u čarobnjaku; u suprotnom se
uklanja samo ako ga je priprema dodala:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Očekujte nula migracija na čekanju, ispravnu prijavu i početnu stranicu te
zaključan `/install`. Prije javne objave sigurnosno kopirajte bazu, `config/`,
korisničke datoteke i podatke teme.


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
