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
koda iz direktorija izdanja unaprijed priprema upravo opcionalne module koji
će biti ponuđeni u čarobnjaku. Bez posebnog popisa priprema se preporučena
Tema:

```bash
cd /srv/simbioza
php scripts/installation_packages.php prepare
```

Za drukčiji odabir, primjerice Temu, Kalendar i E-poštu:

```bash
php scripts/installation_packages.php prepare --modules=theme,calendar,email
php scripts/installation_packages.php status
```

Čarobnjak će pokazati koji su paketi spremni. Paket koji nedostaje treba
pripremiti u CLI-ju i zatim osvježiti čarobnjaka. Web-računu nemojte dati
pravo pisanja u `vendor/` ili `composer.json` radi zaobilaženja te granice.

## 4. Provedite grafičku instalaciju

Stvorite jednokratnu adresu instalera:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Ako instalirate u podputanji, uključite je u `--base-url`. Adresu s tokenom
otvorite u privatnom prozoru; nemojte je dijeliti ni snimati. Čarobnjak
provjerava preduvjete i bazu, traži naziv sitea, barem jedan jezik, vremensku
zonu, prvog administratora i opcionalne module te prije instalacije prikazuje
pregled. Uvozi korisničke upute na odabranim jezicima.

Nakon uspješne instalacije uklonite samo privremeni Backup paket potreban za
uvoz uputa; ako ste Backup izričito odabrali, on ostaje instaliran:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Očekujte nula migracija na čekanju, ispravnu prijavu i početnu stranicu te
zaključan `/install`. Prije javne objave sigurnosno kopirajte bazu, `config/`,
korisničke datoteke i podatke teme.

## 5. Održavajte instalaciju

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
