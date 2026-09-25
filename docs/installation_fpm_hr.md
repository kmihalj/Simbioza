# Instalacija Simbioze s namjenskim PHP-FPM poolom

[Engleska verzija](installation_fpm_en.md) · [Pregled instalacije](installation_hr.md)

Ovo je preporučeni postupak za novu instalaciju. Jednokratno sistemsko
podešavanje stvara zaseban PHP proces i ograničeni pomoćni program za
održavanje. Početna instalacija, upravljanje opcionalnim paketima i jezicima
te nadogradnja aplikacije tada se mogu pokrenuti iz preglednika. Održavatelj
može koristiti i CLI.

**Trenutačno jedna instalacija po poslužitelju s ovim alatom:**
`configure_fpm_setup.php` koristi fiksna imena sistemskih računa, pomoćnog
programa, servisa i konfiguracije. Nemojte pokrenuti `--install` za drugu
Simbiozu na računalu na kojem je alat već podesio prvu: time biste prepisali
FPM konfiguraciju prve instalacije. Ograničenje ne smeta jednoj instalaciji.

## 1. Pripremite izdanje i bazu

Provedite [zajedničke preduvjete, dohvat izdanja i pripremu baze](installation_hr.md#prije-odabira-načina-izvršavanja-php-a).
Koristite datotečni sustav koji doista primjenjuje vlasništvo. Vanjski macOS
volumen montiran s `noowners` nije prikladan za izoliranu FPM instalaciju.

U primjerima zamijenite `/srv/simbioza`, `KORISNIK`, domenu i putanju do
PHP-FPM-a stvarnim vrijednostima. Na macOS-u prikladna je putanja
`/Users/Shared/Simbioza/simbioza`, odvojena od razvojnog Git direktorija.
Korijenski web direktorij smije biti samo `public/` iz tog izdanja.

## 2. Stvorite namjenski pool

Na Debianu instalirajte PHP-FPM za istu verziju PHP-a koju koristi CLI i paket
`acl`. Iz direktorija izdanja jednokratno pokrenite:

```bash
cd /srv/simbioza
sudo php scripts/configure_fpm_setup.php --install \
  --app-root=/srv/simbioza --maintainer=KORISNIK
php scripts/configure_fpm_setup.php --check \
  --app-root=/srv/simbioza --maintainer=KORISNIK
```

Na macOS-u s Homebrew PHP-om navedite stvarnu putanju aplikacije i FPM-a:

```bash
cd /Users/Shared/Simbioza/simbioza
sudo php scripts/configure_fpm_setup.php --install \
  --app-root=/Users/Shared/Simbioza/simbioza \
  --maintainer="$USER" --php-fpm="$(brew --prefix)/sbin/php-fpm"
php scripts/configure_fpm_setup.php --check \
  --app-root=/Users/Shared/Simbioza/simbioza \
  --maintainer="$USER" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Provjera ništa ne mijenja. Zadani pool sluša na `127.0.0.1:9075`; ta vrata
smiju biti dostupna samo lokalno. Alat daje FPM računu pravo pisanja po
radnim podacima, ali ne i po cijelom izdanju, te postavlja strogo ograničeni
pomoćni program za paketne radnje. Stvara i grupe za održavanje i rad.
Prije nastavka pregledajte sve rezultate provjere. Ne koristite `chmod 777`.

## 3. Podesite web-poslužitelj

Odaberite **jednu** konfiguraciju. Primjeri služe zasebnoj domeni na `/`;
zamijenite domenu, putanju izdanja i TLS certifikat. Za instalaciju u
podputanji podesite i URL prefiks na web-poslužitelju te punu adresu predajte
naredbi `install:prepare` u 4. koraku.

### Apache s PHP-FPM-om

Uključite Apache module `rewrite`, `proxy`, `proxy_fcgi` i TLS. PHP zahtjeve
ove instalacije usmjerite u namjenski pool, a ne kroz `mod_php`.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

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

### Nginx s PHP-FPM-om

Nginx ne može izvršavati `mod_php`; PHP zahtjeve šalje istom namjenskom FPM
poolu. Pravila `SCRIPT_FILENAME` i `try_files` moraju ostati zajedno:

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
    }

    location ~ /\. {
        deny all;
    }
}
```

Provjerite Nginx konfiguraciju prije ponovnog učitavanja. FPM port ne smije
biti dostupan nepouzdanoj mreži.

## 4. Provedite grafičku instalaciju

Iz direktorija izdanja stvorite jednokratnu adresu instalera:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Za instalaciju na `/simbioza` koristite
`--base-url=https://simbioza.example.org/simbioza`. Adresu otvorite u
privatnom prozoru preglednika; token nemojte dijeliti ni prikazivati na
screenshotu. Čarobnjak provjerava preduvjete i bazu, traži naziv sitea,
jezike, vremensku zonu, prvog administratora i opcionalne module te prije
instalacije prikazuje pregled. Ako namjenski pomoćni program radi, odabrani
opcionalni paketi instaliraju se kroz GUI. Tema je preporučena, ali nije
obvezna.

Nakon instalacije prijavite se kao administrator. U **Postavke → Setup i
moduli** provjerite da su namjenski pool, pomoćni program i radna prava u
redu. Čarobnjak uvozi korisničke upute na odabranim jezicima.

## 5. Učvrstite prava i provjerite instalaciju

Nakon što je čarobnjak izradio radne datoteke, jednokratno pokrenite:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root=/srv/simbioza --maintainer=KORISNIK
php scripts/configure_fpm_setup.php --check \
  --app-root=/srv/simbioza --maintainer=KORISNIK
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Na macOS-u ponovite putanju aplikacije i argument `--php-fpm` iz 2. koraka.
Odjavite se i ponovno prijavite kako bi održavatelj dobio novo članstvo u
grupama. Očekujte nula migracija na čekanju, ispravnu prijavu i početnu
stranicu te zaključan `/install`. Sigurnosno kopirajte bazu, `config/`,
korisničke datoteke i podatke teme.

Jednokratni `sudo` za sistemsko podešavanje **ne znači** da redovne nadogradnje
traže root. Naredbe za GUI i CLI module, jezike i nadogradnju nalaze se u
[održavanju](installation_hr.md#nakon-instalacije).

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
