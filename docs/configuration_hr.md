# Konfiguracija

Ovaj dokument objašnjava raspored konfiguracijskih datoteka.

## Zadana lokacija i nadjačavanje

Aplikacija prema zadanim postavkama traži konfiguracijske datoteke u
direktoriju `config/`. Lokaciju možete promijeniti varijablom okruženja
`HPH_CONFIG_PATH`.

## Struktura datoteka

Konfiguracija je podijeljena u namjenske datoteke koje služe kao prostor naziva
za povezane postavke. Svaka PHP datoteka vraća polje sa zadanim vrijednostima
odgovarajućeg odjeljka. Prema potrebi možete dodati vlastite stavke.

### Globalne postavke aplikacije (`app.php`)

- opće postavke: naziv aplikacije, vremenska zona, direktorij predmemorije,
  direktorij i naziv tehničkog loga, razina, veličina rotacije i broj sačuvanih
  datoteka, zadane vrijednosti prikaza i mogućnosti sesije. Te postavke vrijede
  samo za PSR-3 tehnički log; dnevnik aktivnosti odvojeno je u `audit_events`.
- CSRF postavke, uključujući opravdane iznimke
- postavke sustava modula: vrste paketa koje je moguće učitati i uključeni
  moduli

### Bootstrap funkcije (`bootstrap.php`)

- kod koji se izvršava nakon izgradnje spremnika, a prije obrade zahtjeva
- tipični zadaci uključuju podešavanje runtimea, inicijalizaciju modula,
  globalne podatke prikaza, početno zapisivanje i sve ostalo što mora završiti
  prije životnog ciklusa zahtjeva

### Naredbe (`commands.php`)

- naredbe aplikacije, primjerice generiranje ključa za `config/env.php`
- naredbe se registriraju u spremniku i pozivaju preko CLI alata
  `vendor/bin/hph`

### Okruženja (`env.php`, nastaje kopiranjem `env.php.dist`)

Zadane postavke okruženja obuhvaćaju:

- vrijednosti vezane uz okruženje, primjerice razvoj/produkciju, razinu
  zapisivanja i debug zastavice
- sigurnosno osjetljive vrijednosti poput ključeva za šifriranje
- adrese pouzdanih proxy poslužitelja

Tipične postavke:

- razvoj: opširniji ispis i postavke prilagođene programeru
- produkcija: stroga obrada pogrešaka, bez prikaza pogrešaka i uz uključeno
  zapisivanje

Tajne i vrijednosti specifične za okruženje čuvajte u datoteci `env.php`, koju
VCS ignorira. Nemojte ih tvrdo upisivati drugdje.

### Middleware (`middleware.php`)

Globalni middleware primjenjuje se na svaki zahtjev, primjerice za pouzdane
proxyje, sesije i CSRF provjeru.

### Rute (`routes.php`)

- definicije ruta povezuju HTTP metode i putanje s obrađivačima i nazivima
- podržani su sažeti zapisi u poljima te eksplicitni objekti ruta
- grupe ruta podržavaju zajedničke prefikse putanje i naziva te middleware

Primjere različitih definicija ruta potražite u `config/routes.php`.

### Servisi (`services.php`)

- definicije spremnika za ubrizgavanje ovisnosti (DI)
- povezuju temeljne servise poput zapisivanja, tvornice HTTP zahtjeva,
  dispečera događaja, predmemorije, sesije, autentikacije, šifriranja i
  pokretača modula

Datoteka `services.php` vraća polje definicija. Ključ polja je naziv servisa, a
vrijednost callback koji vraća definiciju servisa. Svaki callback kao jedini
argument prima spremnik.

Primjer definicije servisa `MyService`:

```php
<?php

declare(strict_types=1);

return [
    MyService::class => fn(ContainerInterface $container): MyService => new MyService(),
];
```

Možete dodavati servise ili nadjačati postojeće. Definicije moraju biti callback
funkcije koje vraćaju servis.

Servis u `services.php` definirajte samo kada:

- treba posebno podešavanje prije uporabe
- želite određenu, prilagođenu ili zamjensku implementaciju sučelja

Primjerice, za zamjenu zadane implementacije
`Psr\Http\Message\ServerRequestInterface` definirajte servis koji vraća željenu
implementaciju iz paketa koji koristite.

Jednostavnu klasu možete samo navesti kao tip, a spremnik će je automatski
izgraditi. U ovom kontekstu jednostavna klasa ne treba posebno podešavanje i
sve se njezine ovisnosti mogu razriješiti iz spremnika.

### Vlastite konfiguracijske datoteke

Prema potrebi dodajte vlastite konfiguracijske datoteke. One moraju:

- biti PHP datoteke u `config/` ili direktoriju zadanom varijablom
  `HPH_CONFIG_PATH`
- vraćati polje

Nazovite datoteku prema odjeljku konfiguracije koji sadrži. Naziv datoteke
postaje prostor naziva vraćenog polja.

Primjer `config/my_config.php`:

```php
<?php

declare(strict_types=1);

return [
    'foo' => 'bar',
];
```

## Varijable sustava kao konfiguracijske vrijednosti

Budući da su konfiguracijske datoteke PHP datoteke, varijable okruženja možete
čitati funkcijom `getenv()`.

Primjer uporabe varijable `HPH_FOO` u `config/app.php`:

```php
<?php

declare(strict_types=1);

return [
    'foo' => is_string($fooVal = getenv('HPH_FOO')) ? $fooVal : 'default-foo-value',
];
```
