# Instalacija i održavanje Simbioze

[Engleska verzija](installation_en.md)

Način izvršavanja PHP-a odaberite **prije** podešavanja web-poslužitelja.
Slijedite jedan cjelovit postupak; nemojte miješati direktive ili prava iz
različitih načina:

| Način rada PHP-a | Web-poslužitelj | Početni grafički instaler | Kasnije GUI radnje s paketima i nadogradnjom |
|---|---|---|---|
| [Namjenski PHP-FPM](installation_fpm_hr.md) | Apache ili Nginx | Da; odabrani opcionalni paketi mogu se instalirati u čarobnjaku | Da, ako namjenski setup i provjera prava prolaze |
| [Apache mod_php](installation_mod_php_hr.md) | Samo Apache | Da; opcionalne pakete prvo treba pripremiti u CLI-ju | Ne; za paketne radnje i nadogradnje koristite CLI |

Početni čarobnjak u pregledniku **nije ograničen na FPM**. U oba načina
podešava site, bazu, jezike, administratora i odabrane module. Razlika je u
tome smije li web-proces zatražiti instalaciju nedostajućih Composer paketa i
kasnije nadogradnje. Za novu instalaciju preporučuje se namjenski FPM.
Nginx ne podržava Apacheov `mod_php`.

## Prije odabira načina izvršavanja PHP-a

### 1. Provjerite poslužitelj

Potrebni su Linux ili macOS, PHP 8.2 ili noviji, Composer 2, Git, Apache 2.4
ili Nginx te HTTPS za javni site. PHP proces web-poslužitelja mora imati iste
potrebne ekstenzije kao CLI PHP:

```text
ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip
```

Instalirajte PDO ekstenziju za odabranu bazu: `pdo_sqlite`, `pdo_mysql` ili
`pdo_pgsql`. CLI provjerite naredbama `php -v` i `php -m`, a zatim potvrdite
i verziju i ekstenzije **web** PHP handlera. Ispravan CLI ne dokazuje da Apache
ili FPM izvršavaju istu PHP verziju.

### 2. Dohvatite označeno izdanje

Označeno izdanje instalirajte u direktorij **bez** `.git` direktorija.
Odaberite aktualni stabilni tag na [stranici izdanja](https://github.com/kmihalj/Simbioza/releases)
i zamijenite `0.1.91` ako je dostupno novije izdanje:

```bash
SIMBIOZA_TAG=0.1.91
SIMBIOZA_FETCH_DIR="$(mktemp -d)"
git clone --quiet --depth 1 --branch "$SIMBIOZA_TAG" --single-branch \
  https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/release"
mkdir -p /srv/simbioza
rsync --archive --exclude=.git/ "$SIMBIOZA_FETCH_DIR/release/" /srv/simbioza/
cd /srv/simbioza
composer update --with-all-dependencies --optimize-autoloader
composer check-platform-reqs
```

Naredbe pokrenite kao isti sistemski račun koji će biti vlasnik i održavatelj
izdanja. Na macOS-u zamijenite `/srv/simbioza` putanjom na lokalnom volumenu
koji primjenjuje vlasništvo, npr. `/Users/Shared/Simbioza/simbioza`.
Aplikacijski manifest koristi tagirane pakete; nemojte podmetati lokalne
razvojne module ni koristiti `--no-dev`. Obvezna jezgra uključuje Framework,
ORM, Menu, Auth, Notification, HTML Editor, Workspace, Workspace Search i
Simbioza User. Ostale module možete odabrati tijekom ili nakon instalacije.

Webu smije biti izložen samo `public/`. `config/`, `data/`, Composer datoteke,
migracije i ugrađene upute nikad ne smiju biti korijenski web direktorij.
Pripremite radne putanje:

```bash
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Ne koristite `chmod 777`. Namjenski FPM alat sam postavlja precizno
vlasništvo; uputa za mod_php navodi uske zapisive putanje.

### 3. Pripremite praznu bazu

SQLite ne treba bazni servis. Odaberite ga u instaleru pa Simbioza stvara
`data/simbioza.sqlite`. Za MySQL ili PostgreSQL stvorite **praznu** bazu i
zasebnog aplikacijskog korisnika bez globalnih ovlasti.

Primjer za MySQL:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<jedinstvena-lozinka>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Primjer za PostgreSQL:

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Nastavite **ili** [FPM postupkom](installation_fpm_hr.md) **ili**
[Apache mod_php postupkom](installation_mod_php_hr.md). Svaki sadrži vlastitu
konfiguraciju web-poslužitelja, instaler, završnu provjeru i odgovarajuća
prava. Dok se ne ukloni ograničenje fiksnih imena, nemojte pokretati namjenski
FPM alat za drugu instalaciju na istom računalu.

## Nakon instalacije

### Moduli

Administrator otvara **Postavke → Setup i moduli**. Na namjenskom FPM-u GUI
može instalirati, ukloniti, uključiti ili isključiti opcionalne module ako su
sve provjere okruženja uspješne. Na mod_php-u GUI može uključiti ili
isključiti već instalirane module, dok se paketne radnje rade u CLI-ju.
CLI naredbe iste su u oba načina:

```bash
vendor/bin/hph modules list
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
vendor/bin/hph modules backups calendar
vendor/bin/hph modules remove calendar --yes
vendor/bin/hph modules add calendar --restore
vendor/bin/hph modules migrate-status
```

Isključivanje zadržava paket, tablice i podatke, ali pri sljedećem zahtjevu
prestaje učitavati njegove rute, servise, izbornike i tablice. Uklanjanje
najprije izrađuje NDJSON kopiju u `data/module-backups/`, a zatim uklanja
migracije, tablice i paket. Pri ponovnom dodavanju izričito odaberite čistu
instalaciju ili povrat. Obvezne module nije moguće ukloniti; ovisnosti se
provjeravaju. Već uvezene Confluence stranice rade bez import-modula, ali se
uklanjanje blokira dok postoje nerazriješene privremene import-referencije.

### Jezici

Instaler dohvaća objavljene jezike iz
[repozitorija jezika](https://github.com/kmihalj/simbioza-languages).
Hrvatski i engleski unaprijed su označeni, ali ih možete odznačiti ako
ostane barem jedan jezik. Administrator može upravljati objavljenim paketima
u namjenskom FPM GUI-ju; inače koristite:

```bash
vendor/bin/hph languages list
vendor/bin/hph languages available
vendor/bin/hph languages install de
vendor/bin/hph languages disable de
vendor/bin/hph languages enable de
vendor/bin/hph languages update
vendor/bin/hph languages remove de
```

`languages update` provjerava instalirane pakete neovisno o izdanju
aplikacije. Posljednji aktivni jezik nije moguće isključiti ni ukloniti.
Čarobnjak uvozi engleske i/ili hrvatske korisničke upute odabrane pri
instalaciji; dodatni jezici sučelja mogu se instalirati i prije nego što
postoje prijevodi svih uputa.

### Nadogradnje aplikacije

Na ispravno podešenom namjenskom FPM-u administrator može provjeriti i
pokrenuti nadogradnju u GUI Setupu. Stvarni posao radi ograničeni deploy
račun, a ne web-proces. Održavatelj može koristiti i CLI nakon što dobije
članstvo u deploy i runtime grupama te se ponovno prijavi u shell. Na
mod_php-u CLI koristi Unix vlasnik koda. Redovne radnje ne trebaju root:

```bash
cd /srv/simbioza
php update.php --check
php update.php
```

Za određeni objavljeni tag koristite `php update.php --tag=<TAG>`. Updater
izrađuje kopiju koda, uključuje održavanje, čuva privatnu konfiguraciju i
podatke, zadržava odabrane opcionalne module, instalira kompatibilne tagirane
pakete, provjerava pokretanje, primjenjuje migracije, osvježava upute i temu
te čisti cache. Na postojećoj instalaciji **nemojte** ga zamijeniti
samostalnim `composer update`: time se mogu izgubiti odabrani opcionalni
paketi iz aplikacijskog manifesta.

Greška **prije** migracija automatski vraća kod i Composer pakete. Kada
migracije već počnu, održavanje namjerno ostaje uključeno radi kontroliranog
oporavka. `data/update-maintenance.json` zaštitni je zapis: nikad ga ne
premještajte niti brišite dok ne dokažete da nema aktivnog updatera i ne
pregledate dnevnik i putanju kopije. Nakon svake nadogradnje potvrdite
završni status, nula migracija na čekanju te stvarnu prijavu i prikaz
sadržaja. Bazu, postavke, datoteke i teme kopirajte i zasebno.

Starije namjenske FPM instalacije podešene izdanjem 0.1.77 ili starijim
trebaju jednom ponoviti `--finalize` iz FPM upute nakon nadogradnje, kako bi
CLI održavatelji i pozadinske nadogradnje dobili nova ograničena pravila
helpera. Ne koristite redovno `sudo php update.php` i ne pokrećite updater
kao web/FPM račun.
