# Instalacija i održavanje Simbioze

[English version](installation_en.md)

Simbioza se instalira iz označenog izdanja. Početni paket sadrži samo obveznu
jezgru; opcionalni se moduli dodaju tijekom instalacije ili poslije nje. Modul
**Tema** je preporučen i unaprijed označen, ali ga je moguće isključiti.

Postoje dva podržana načina rada:

- s namjenskim PHP-FPM poolom administrator iz GUI-ja može instalirati,
  uklanjati, uključivati i isključivati module, dodavati jezike i nadograđivati
  aplikaciju;
- bez namjenskog FPM-a iste paketne radnje izvode se CLI-jem, dok GUI i dalje
  može uključiti ili isključiti već instalirane module.

Za svakodnevni rad i održavanje nije potreban `sudo`. Koristi se samo jednom za
izradu izoliranih sistemskih računa, FPM servisa i strogo ograničenog helpera.

## 1. Preduvjeti

- Linux ili macOS;
- PHP 8.2 ili noviji;
- Composer 2 i Git;
- Apache 2.4 ili Nginx;
- prazna SQLite, MySQL ili PostgreSQL baza;
- HTTPS za svaku javno dostupnu instalaciju.

Obvezne PHP ekstenzije:

```text
ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip
```

Potrebna je i odgovarajuća PDO ekstenzija: `pdo_sqlite`, `pdo_mysql` ili
`pdo_pgsql`. Provjera:

```bash
php -v
php -m
composer check-platform-reqs
```

## 2. Dohvat označenog izdanja

Na poslužitelj se kopiraju samo datoteke odabranog taga, bez trajnog `.git`
direktorija. Zamijenite `0.1.75` stvarnim izdanjem koje instalirate:

```bash
git clone --quiet --depth 1 --branch 0.1.75 --single-branch \
https://github.com/kmihalj/Simbioza.git /tmp/simbioza-release
mkdir -p /srv/simbioza
rsync --archive --exclude=.git/ /tmp/simbioza-release/ /srv/simbioza/
cd /srv/simbioza
composer update --with-all-dependencies --optimize-autoloader
composer check-platform-reqs
```

Simbioza se objavljuje s tagiranim paketima. Ne koristite `--no-dev`: razvojne
ovisnosti nisu dio produkcijskog manifesta, a razvojne kopije povezujemo samo u
lokalnom razvojnom okruženju.

Obvezna jezgra sadrži Framework, ORM, Menu, Auth, Notification, HTML Editor,
Workspace, Workspace Search i Simbioza User. API, Task, Theme, Audit, E-mail,
Comment, Calendar, Confluence Import i Backup instaliraju se samo kada ih
odaberete ili naknadno dodate.

## 3. Document root i osnovna prava

Document root smije biti isključivo `public/`. `config/`, `data/`, migracije i
paketi uputa ne smiju biti dostupni webom.

```bash
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
chmod 750 config data resources/config/menu resources/config/theme
```

Nemojte koristiti `chmod 777`. Namjenski alat iz 6. poglavlja postavlja točne
vlasnike i prava. Ako ne koristite taj alat, proces koji izvršava PHP mora moći
čitati aplikaciju i pisati u `data/`, dinamičke datoteke u `config/` te
`resources/config/menu/` i `resources/config/theme/`.

## 4. Apache

Minimalni VirtualHost:

```apache
<VirtualHost *:443>
ServerName simbioza.example.org
DocumentRoot /srv/simbioza/public

<Directory /srv/simbioza/public>
Options -Indexes +FollowSymLinks
AllowOverride FileInfo Options
Require all granted
</Directory>

<FilesMatch ".+\.php$">
SetHandler "proxy:fcgi://127.0.0.1:9075"
</FilesMatch>

SSLEngine on
# Ovdje postavite certifikat i privatni ključ organizacije.
</VirtualHost>
```

Uključite `rewrite`, `proxy`, `proxy_fcgi`, TLS i odgovarajuću FastCGI
konfiguraciju. Port `9075` sluša samo na `127.0.0.1` i ne smije biti dostupan
iz mreže.

## 5. Nginx

```nginx
server {
listen 443 ssl http2;
server_name simbioza.example.org;
root /srv/simbioza/public;
index index.php;

location / {
try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
try_files $uri =404;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
fastcgi_pass 127.0.0.1:9075;
}

location ~ /\. {
deny all;
}
}
```

## 6. Preporučeni namjenski FPM i siguran GUI Setup

Na Debianu najprije instalirajte PHP-FPM verziju koja odgovara CLI PHP-u i paket
`acl`, potreban za odvojeno pravo čitanja privatnog SAML configa. Zatim
jednom, iz release direktorija, pokrenite:

```bash
cd /srv/simbioza
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/srv/simbioza \
--maintainer=KORISNIK
```

Alat izrađuje zaključane račune `fpm-simbioza` i `simbioza-deploy` te odvojene
grupe `app-simbioza`, `deploy-simbioza` i `run-simbioza`. Održavatelja dodaje
u deploy i runtime grupu. Web proces nema pravo mijenjati cijeli kod.
Na Linuxu se pool izvršava u zasebnom systemd servisu s `ProtectSystem=strict`,
read-only prikazom aplikacijskog koda i upisom samo u dokumentirane runtime
putanje. Ograničeni root-owned helper prihvaća samo nasumični ID unaprijed
provjerenog zahtjeva. Na Linuxu zatim preko systemd-a pokreće zaseban,
neprivilegirani `simbioza-deploy` worker s ponovno uključenim
`NoNewPrivileges`; web proces ne dobiva opću root ljusku ni izravan pristup
Composeru.

Provjera ne mijenja sustav:

```bash
php scripts/configure_fpm_setup.php \
--check \
--app-root=/srv/simbioza \
--maintainer=KORISNIK
```

Nakon dovršetka web instalera jednom učvrstite nova runtime prava:

```bash
sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/srv/simbioza \
--maintainer=KORISNIK
```

Odjavite se i ponovno prijavite kako bi novo članstvo u grupama vrijedilo u
shellu. Nakon toga CLI, GUI Setup i nadogradnje rade bez `sudo`.

### 6.1. SAML autentikacija i FPM postavke

Ako instalacija koristi SimpleSAMLphp, aplikacija i njezin SimpleSAMLphp
endpoint moraju prolaziti kroz **isti** namjenski FPM pool. Nije dovoljno samo
prebaciti Simbiozu na FPM, a postojeći zajednički `/simplesaml` ostaviti na
drugom PHP handleru: kod `store.type = phpsession` tada se prijava i povratni
poziv ne služe istom pohranom sesija.

Koristi se zajednički instalirani SimpleSAMLphp kod, ali svaka Simbioza ima
vlastite postavke i runtime. Pripremite primjerice:

```text
/srv/simbioza/data/saml/config/config.php
/srv/simbioza/data/saml/config/authsources.php
/srv/simbioza/data/saml/cert/
/srv/simbioza/data/saml-runtime/cache/
/srv/simbioza/data/saml-runtime/data/
/srv/simbioza/data/sessions/
```

Privatni `config.php` mora imati vlastite `secretsalt`, `assets.salt`,
administratorsku lozinku, cookie nazive i putanju. Ne kopirajte zajedničke
saltove, administratorsku lozinku ni SP privatne ključeve. Za instalaciju na
`/simbioza/` bitne postavke izgledaju ovako:

```php
'baseurlpath' => 'https://simbioza.example.org/simbioza/simplesaml/',
'cachedir' => '/srv/simbioza/data/saml-runtime/cache',
'datadir' => '/srv/simbioza/data/saml-runtime/data',
'certdir' => '/srv/simbioza/data/saml/cert',
'metadatadir' => '/usr/share/simplesamlphp-aai/metadata',
'store.type' => 'phpsession',
'session.phpsession.savepath' => '/srv/simbioza/data/sessions',
'session.phpsession.cookiename' => 'SimpleSAMLSimbioza',
'session.cookie.name' => 'SimpleSAMLSimbiozaStore',
'session.cookie.path' => '/simbioza/',
```

Zatim pri početnom podešavanju i završnom učvršćivanju navedite privatni
direktorij. Alat ga provjerava, postavlja samo za Simbiozin pool i ostavlja
config zapisivim deploy korisniku, a FPM-u samo čitljivim:

```bash
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/srv/simbioza \
--maintainer=KORISNIK \
--simplesaml-config-dir=/srv/simbioza/data/saml/config

sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/srv/simbioza \
--maintainer=KORISNIK \
--simplesaml-config-dir=/srv/simbioza/data/saml/config
```

Na Apacheu aplikacijski SAML PHP endpoint usmjerite na isti `127.0.0.1:9075`
pool, prije općenitih PHP pravila:

```apache
ProxyPassMatch "^/simbioza/simplesaml/(.+?[.]php)(/.*)?$" \
"fcgi://127.0.0.1:9075/usr/share/simplesamlphp-aai/public/$1$2"
Alias /simbioza/simplesaml "/usr/share/simplesamlphp-aai/public/"

<Directory "/usr/share/simplesamlphp-aai/public">
Options -Indexes
AllowOverride None
Require all granted
</Directory>
```

U AAI/proxy registru moraju biti registrirani novi aplikacijski EntityID, ACS
i SLO URL-ovi ispod `/simbioza/simplesaml/`. Na postojećoj instalaciji stari
endpoint ostavite aktivnim dok novi retci nisu uneseni i propagirani; tek tada
prebacite prijavu i napravite cijeli preglednički test prijave i odjave. Sam
`auth_source` može ostati `default-sp`, a aplikacija i dalje učitava autoloader
zajedničkog SimpleSAMLphp paketa.

Za AAI izvor `default-sp` registrirajte sljedeće nove vrijednosti (domenu i
osnovnu putanju zamijenite stvarnom instalacijom):

| Polje | Nova vrijednost |
|---|---|
| **EntityID** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/metadata.php/default-sp` |
| **AssertionConsumerService URL** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp` |
| **SingleLogoutService URL** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp` |

Ako koristite i `proxy-sp`, registrirajte isti skup URL-ova sa završetkom
`/proxy-sp` umjesto `/default-sp`. Odgovarajući `authsources.php` mora sadržavati
isti EntityID koji je registriran za taj izvor. Promjena PHP handlera bez ove
registracije prekida povratak s IdP-a i odjavu, iako se početna stranica
Simbioze može normalno otvoriti.

## 7. Instalacija bez namjenskog FPM-a

Web-installer ne pokreće Composer iz običnog web procesa. Prije otvaranja
čarobnjaka CLI-jem pripremite pakete koje želite odabrati. Bez argumenta se
priprema preporučeni Theme:

```bash
php scripts/installation_packages.php prepare
```

Za vlastiti izbor:

```bash
php scripts/installation_packages.php prepare \
--modules=theme,calendar,email
```

Nakon uspješne web instalacije uklonite samo privremeni Backup paket koji je
bio potreban za uvoz ugrađenih uputa:

```bash
php scripts/installation_packages.php cleanup
```

Ako je Backup bio izričito odabran, ostaje instaliran.

## 8. Prazna baza

SQLite ne traži poslužitelj; installer stvara `data/simbioza.sqlite`. Za MySQL
ili PostgreSQL napravite praznu bazu i zasebnog aplikacijskog korisnika bez
globalnih administratorskih ovlasti.

Primjer MySQL-a:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<sigurna-lozinka>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Primjer PostgreSQL-a:

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

## 9. Web-installer

Izradite jednokratnu adresu:

```bash
bin/simbioza install:prepare \
--base-url=https://simbioza.example.org
```

Za instalaciju u poddirektoriju uključite ga u osnovnu adresu:

```bash
bin/simbioza install:prepare \
--base-url=https://simbioza.example.org/simbioza
```

Adresu s tokenom otvorite u privatnom prozoru i nemojte je slati e-poštom,
chatom ili screenshotom. Token se troši pri prvom valjanom otvaranju.

Čarobnjak vodi kroz:

1. provjeru PHP-a, ekstenzija, putanja i paketa;
2. spajanje na praznu bazu;
3. naziv aplikacije, jezike, vremensku zonu i prvog administratora;
4. odabir opcionalnih modula;
5. pregled bez prikaza lozinki i stvarnu instalaciju.

Theme je preporučen i unaprijed označen. Ako ga ostavite označenim, uvozi se i
aktivira ugrađena tema Simbioza. Uvijek se uvozi dvojezično područje
**Korisničke upute**; stranice opcionalnih modula uvoze se samo kada je njihov
modul dostupan.

## 10. Setup nakon prijave

Administrator otvara **Postavke → Setup i moduli**. Dijagnostika prikazuje
stvarni FPM način, dostupnost helpera, vlasnika, grupu i prava svake bitne
putanje.

Ako su sve FPM provjere zelene, GUI omogućava:

- instalaciju i deinstalaciju opcionalnog modula;
- uključivanje i isključivanje instaliranog modula;
- povrat podataka iz NDJSON kopije ili čistu ponovnu instalaciju;
- dodavanje provjerenog JSON jezičnog paketa;
- provjeru i pokretanje nadogradnje cijele aplikacije.

Bez namjenskog FPM-a GUI nudi samo sigurne promjene stanja postojećih modula i
prikazuje točnu CLI naredbu za svaku nedostupnu paketnu radnju.

## 11. CLI za module

```bash
vendor/bin/hph modules list
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
vendor/bin/hph modules remove calendar --yes
vendor/bin/hph modules backups calendar
vendor/bin/hph modules add calendar --restore
```

Iste naredbe koriste se u oba načina instalacije. Na ispravno podešenoj FPM
instalaciji CLI za Composerovo dodavanje i uklanjanje paketa automatski koristi
isti ograničeni deploy helper kao GUI; `enable`, `disable` i migracije izvršava
izravno prijavljeni održavatelj. Bez namjenskog FPM-a paketne radnje izvršava
vlasnik instalacije. Ni u jednom slučaju za redovni rad nije potreban `sudo`.

`disable` pri sljedećem zahtjevu prestaje učitavati manifest, rute, servise,
stavke izbornika i tablice modula, ali ostavlja paket, tablice i podatke.
`remove` najprije izrađuje NDJSON kopiju u `data/module-backups/`, zatim uklanja
migracije, tablice i Composer paket. Pri ponovnom dodavanju odaberite
`--restore` ili `--fresh`. Obvezni modul nije moguće ukloniti, a ovisnosti se
provjeravaju prije svake promjene.

Confluence Import nije potreban za normalan prikaz već uvezenih stranica.
Uklanjanje se ipak zaustavlja ako neka verzija sadržaja još sadrži privremenu
import-referencu.

## 12. Dodavanje jezika

```bash
vendor/bin/hph languages list
vendor/bin/hph languages template de \
--source=en \
--output=/tmp/de.json
vendor/bin/hph languages validate /tmp/de.json
vendor/bin/hph languages add /tmp/de.json
```

Jedan paket sadrži sve stringove aplikacije i trenutno instaliranih modula,
višejezične nazive jezika i sigurnu SVG zastavicu. Njemački je samo priloženi
primjer; ne uključuje se automatski. Detalji su u
[uputi za lokalizaciju](localization_hr.md).

## 13. Nadogradnja

U namjenskom FPM načinu nadogradnju možete provjeriti i pokrenuti iz GUI
Setupa. Pozadinski posao tada radi kao ograničeni `simbioza-deploy`, a FPM
proces ne dobiva pravo pisanja po aplikacijskom kodu.

Isti CLI radi u oba načina instalacije:

```bash
php update.php --check
php update.php
```

Za određeni tag:

```bash
php update.php --tag=0.1.75
```

Na namjenskoj FPM instalaciji naredbu pokreće prijavljeni održavatelj koji je
nakon početnog podešavanja i ponovne prijave član grupa `deploy-simbioza` i
`run-simbioza`. Ne pokrećite updater kao `fpm-simbioza` i ne treba koristiti
`sudo`:

```bash
cd /srv/simbioza
php update.php --check
php update.php
```

Na instalaciji bez namjenskog FPM-a iste naredbe pokreće Unix korisnik koji je
vlasnik aplikacijskog koda i zapisivih postavki. Ako provjera prava ne prolazi,
ispravite vlasništvo jednom; nemojte rutinski pokretati web aplikaciju ili
updater kao `root`.

Updater izrađuje kopiju koda, uključuje održavanje, čuva privatnu konfiguraciju
i podatke, ažurira tagirane pakete, provjerava bootstrap, primjenjuje migracije,
osvježava ugrađene upute i temu te čisti cache. Neuspjeh prije migracija vraća
prethodno stanje; nakon početka migracija održavanje ostaje uključeno radi
sigurnog ručnog oporavka.

Datoteka `data/update-maintenance.json` dio je zaštite updatera. Ne premještajte
je niti brišite dok ne provjerite da nema aktivnog procesa ažuriranja. Nakon
uspjeha ili sigurnog automatskog povrata updater je sam uklanja; nakon greške
koja se dogodila poslije početka migracija ostavlja je namjerno i ispisuje
putanju sigurnosne kopije za kontrolirani oporavak.

Pri prvoj nadogradnji starije instalacije updater iz statičkog `config/app.php`
ili stare `config/modules.php` izdvaja zatečeno stanje u trajni
`data/config/modules.php`, a zatim postavlja novu dinamičku konfiguraciju. Time
se postojeći odabir ne mijenja, GUI i CLI mogu sigurno koristiti atomsku
zamjenu iste datoteke, a FPM i dalje ne može mijenjati release konfiguraciju.

Postojeći administratorski izbornik postavki ostaje netaknut. Ako novo izdanje
donese postavke novog modula ili značajke, updater dodaje samo nedostajuće
stavke na kraj, bez promjene postojećih oznaka, redoslijeda ili uključenosti.

Kod koordiniranog izdanja prvo se moraju objaviti tagovi izmijenjenih modula, a
tek zatim tag glavne Simbioze koji na njih upućuje.

## 14. Posebnosti macOS-a

Za lokalni ili interni macOS poslužitelj instalirajte Homebrew PHP, Composer,
Git i Apache:

```bash
brew install php composer git httpd
brew_prefix="$(brew --prefix)"
php_fpm="$brew_prefix/sbin/php-fpm"
```

Na Apple Silicon računalima Homebrew je obično u `/opt/homebrew`, a na Intel
računalima u `/usr/local`. Alat prepoznaje prefiks prema stvarnom `php-fpm`
programu i na tom mjestu stvara zasebnu konfiguraciju i runtime direktorij.

Aplikaciju stavite na datotečni sustav na kojem macOS primjenjuje vlasništvo,
primjerice `/Users/Shared/Simbioza/simbioza`. Vanjski volumen montiran s
`noowners` nije prikladan: `chown` može izgledati uspješno, ali izolacija
korisnika nije stvarno provedena. Svi roditeljski direktoriji moraju
namjenskom FPM korisniku dopuštati prolaz.

```bash
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/Users/Shared/Simbioza/simbioza \
--maintainer="$USER" \
--php-fpm="$php_fpm"
```

Alat stvara system `launchd` servis `hr.simbioza.php-fpm`. Homebrew Apache treba
učitati `mod_proxy` i `mod_proxy_fcgi`, koristiti `public/` kao DocumentRoot i
slati PHP na `127.0.0.1:9075`, jednako kao primjer u 4. poglavlju.

Nakon web instalacije:

```bash
sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/Users/Shared/Simbioza/simbioza \
--maintainer="$USER" \
--php-fpm="$php_fpm"
```

Ponovno se prijavite u macOS sesiju. Daljnje upravljanje iz GUI-ja ili CLI-ja
ne traži `sudo`.

## 15. Završna provjera

```bash
php scripts/configure_fpm_setup.php \
--check \
--app-root=/srv/simbioza \
--maintainer=KORISNIK
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Očekujte nula migracija na čekanju, HTTP 200 za prijavu i početnu stranicu te
404 za zaključani `/install`. Provjerite i backup baze, `config/`, korisničkih
datoteka i teme. Povjerljive vrijednosti nikada ne dodajte u prijavu greške.
