# Instalacija Simbioze s Apache mod_php modulom

[Engleska verzija](installation_mod_php_en.md) · [Pregled instalacije](installation_hr.md)

Ovaj je postupak za Apache koji već koristi kompatibilni `mod_php`. To **nije**
konfiguracija za Nginx: Nginx mora PHP zahtjeve slati FastCGI procesu, npr.
PHP-FPM-u. Za novu instalaciju preporučuje se
[namjenski FPM](installation_fpm_hr.md). Početni grafički čarobnjak radi i ovdje,
ali web-proces ne smije instalirati Composer pakete ni nadograđivati
aplikaciju. Opcionalne pakete prvo pripremite u CLI-ju; kasnije paketne radnje
i nadogradnje izvršavajte u CLI-ju kao vlasnik instalacije.

## 1. Pripremite izdanje i bazu

Provedite [zajedničke preduvjete, dohvat izdanja i pripremu baze](installation_hr.md#prije-odabira-načina-izvršavanja-php-a).
Izdanje neka bude u vlasništvu zasebnog Unix računa koji pokreće Composer i
CLI nadogradnje. Apache/PHP procesu dopustite čitanje koda i pisanje samo po
potrebnim radnim putanjama: `data/`, dinamičke postavke u `config/`,
`resources/config/menu/` i `resources/config/theme/`. Na svojem sustavu
podesite vlasništvo ili ACL samo za te putanje; cijelo izdanje ne smije biti
zapisivo web-procesu i ne koristite `chmod 777`. Korijenski web direktorij je
`public/`, nikada direktorij izdanja.

Na macOS-u ovo podesite **prije** otvaranja čarobnjaka. Ako Apache radi pod
drugom grupom, zamijenite `_www`. Nasljedni ACL za održavatelja važan je jer
čarobnjak stvara privatne datoteke s pravima `0600` koje kasnije CLI
nadogradnje moraju moći čitati.

```bash
cd /srv/simbioza
MAINTAINER="$(id -un)"
sudo chgrp -R _www config data resources/config/menu resources/config/theme
chmod 3770 config
chmod 2770 resources/config/menu resources/config/theme
chmod -R g+rwX data resources/config/menu resources/config/theme
sudo chmod +a "user:${MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
sudo find data resources/config/menu resources/config/theme -type d -exec chmod +a "user:${MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

Na macOS-u umjesto `/srv/simbioza` koristite stvarnu putanju izdanja. Na
Linuxu podesite odgovarajući uski ACL i zadani ACL pomoću `setfacl` te nakon
instalacije provjerite može li održavatelj čitati svaku nastalu privatnu
datoteku; maska POSIX ACL-a može ograničiti pristup datoteci izričito stvorenoj
s pravima `0600`. Nemojte to rješavati javnim otvaranjem prava datoteke.
Instaler i CLI moraju imati pristup radnim putanjama, a `vendor/` ostaje
nedostupan za pisanje web-procesu.

## 2. Potvrdite da Apache doista izvršava mod_php

Verzija CLI PHP-a sama po sebi ne dokazuje koji PHP koristi Apache. Provjerite
aktivne Apache module i handler te da PHP u pregledniku ima podržanu verziju
i potrebne ekstenzije. Uobičajeni `mod_php` bez podrške za niti traži Apache
MPM `prefork`. Na istoj instalaciji nemojte istodobno uključiti FPM proxy
handler i `mod_php` handler.

Uključite Apache `rewrite` i TLS pa podesite virtualni host:

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
osnovnu adresu navedite u 4. koraku.

## 3. Pripremite opcionalne pakete u CLI-ju

Grafički instaler ne može pokrenuti Composer kao Apache web-račun. Vlasnik
koda iz direktorija izdanja unaprijed priprema opcionalne module. Naredba bez
popisa priprema **sve** opcionalne module, jer su svi unaprijed označeni u
čarobnjaku:

```bash
cd /srv/simbioza
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

## 4. Provedite grafičku instalaciju

Stvorite jednokratnu adresu instalera:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Ako instalirate u podputanji, uključite je u `--base-url`. Adresu s tokenom
otvorite u privatnom prozoru; nemojte je dijeliti ni snimati. Čarobnjak
provjerava preduvjete i bazu, traži naziv sitea, barem jedan jezik, vremensku
zonu, prvog administratora i opcionalne module (sve unaprijed odabrane) te prije instalacije prikazuje
pregled. Uvozi korisničke upute na odabranim jezicima.

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

## 5. Održavajte instalaciju

Verzije starije od 0.2.12 prvo nadogradite naredbom `php update.php --tag=0.2.12`
kao vlasnik koda. Ne preskačite ovo obvezno prijelazno izdanje updatera.

U **Postavke → Setup i moduli** administrator može uključivati ili
isključivati već instalirane module. Dodavanje/uklanjanje paketa i nadogradnje
mora pokrenuti Unix vlasnik koda, a ne Apache:

```bash
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
php update.php --check
php update.php
```

U [zajedničkim uputama za održavanje](installation_hr.md#nakon-instalacije)
nalaze se naredbe za uklanjanje i povrat modula, jezike, zaštite updatera i
provjera rezultata. Redovne CLI radnje nemojte izvršavati kao `root`.
