# Lokalizacija

Simbioza koristi hrvatski kao izvorni jezik sučelja. Tekst vidljiv korisniku
piše se hrvatski u kodu i isti je ključ u svim jezičnim datotekama. Jezični
paket smije biti preveden s engleskog kao radnog jezika prevoditelja, ali to
ne dodaje drugi korak prevođenja pri prikazu.
Tehnički identifikatori, primjerice `can_view` i oznake formata datuma,
ostaju stabilni kodovi jer nisu tekstovi sučelja.

## Konfiguracija

Lokalizacija se podešava u `config/app.php`:

Hrvatski je zadani jezik standardne nove instalacije, ali installer može
odabrati drugi primarni jezik i izostaviti hrvatski. Odabrani primarni jezik
postaje jezik povratnog prikaza, a korisnici se mogu prebacivati među uključenim
jezicima. Sljedeći primjer prikazuje uobičajenu hrvatsku konfiguraciju:

```php
return [
    'localization' => [
        'locale' => 'hr',
        'fallback_locale' => 'hr',
        'detect_browser_locale' => true,
        'translations_dir' => __DIR__ . '/../lang',
    ],
];
```

## Jezične datoteke

Jezične datoteke su PHP datoteke koje vraćaju polje prijevoda. Nalaze se u
direktoriju zadanom postavkom `translations_dir` i nazivaju prema oznaci jezika,
primjerice `hr.php` ili `en.php`.

### Primjer `lang/hr.php`

```php
<?php

return [
    'Dobro došli!' => 'Dobro došli!',
    'Pozdrav, :name!' => 'Pozdrav, :name!',
];
```

## Uporaba

### Pomoćne funkcije

Globalne pomoćne funkcije `__()` i `__e()` — druga izbjegava HTML — dostupne su
u prikazima, kontrolerima i drugim dijelovima aplikacije.

```php
echo __('Dobro došli!');
echo __e('Pozdrav, :name!', ['name' => 'Ivan']);
```

### Zamjenske vrijednosti

U prijevodu definirajte zamjenske vrijednosti sintaksom `:name`. Drugi argument
funkcije `__()` prima asocijativno polje vrijednosti.

Prevoditelj podržava i varijante velikih i malih slova:

- `:name` -> `Ivan`
- `:NAME` -> `IVAN`
- `:Name` -> `Ivan` — prvo slovo veliko

### U kontrolerima

`TranslatorInterface` automatski se ubrizgava u `AbstractController`.

```php
namespace App\Controller;

use HeartPhrame\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;

class HomeController extends AbstractController
{
    public function index(): ResponseInterface
    {
        $message = $this->translator->trans('Dobro došli!');
        // ...
    }
}
```

### U prikazima

Servis `translator` dostupan je kao globalna varijabla u predlošcima prikaza.
Zbog sažetosti se ipak preporučuju funkcije `__()` i `__e()`.

```php
<h1><?= __('Dobro došli!') ?></h1>
```

## Promjena jezika tijekom izvođenja

Trenutačni jezik promijenite preko `TranslatorInterface`:

```php
$translator->setLocale('hr');
```

## Moduli

Prijevodi modula učitavaju se automatski kada modul u svojem korijenu sadrži
direktorij istog naziva kao konfigurirani direktorij prijevoda. Primjerice, uz
postavku `'translations_dir' => __DIR__ . '/../lang'`, automatski se učitavaju
prijevodi iz direktorija `lang` svakog modula.

## Objedinjeni jezični paketi

Za dodavanje novog jezika nije potrebno uređivati svaki modul. Simbioza može
skupiti ključeve glavne aplikacije i svih instaliranih modula u jedan JSON
paket. Objavljeni su paketi navedeni u
[javnom repozitoriju jezika](https://github.com/kmihalj/simbioza-languages):

```bash
vendor/bin/hph languages list
vendor/bin/hph languages available
vendor/bin/hph languages install de
vendor/bin/hph languages disable de
vendor/bin/hph languages enable de
vendor/bin/hph languages update
vendor/bin/hph languages remove de
```

Za izradu novog prijevoda:

```bash
vendor/bin/hph languages template de --source=en --output=/tmp/de.json
vendor/bin/hph languages validate /tmp/de.json
vendor/bin/hph languages add /tmp/de.json
```

U JSON-u su ključevi hrvatski tekstovi iz koda, ne engleski prijevodi. Prevodite
samo vrijednosti u objektu `translations`. `source_locale` označava jezik
teksta iz kojeg prevoditelj radi (`en` ili `hr`), ali se pri prikazu traži
izravno po hrvatskom ključu: nema slijeda HR → EN → drugi jezik. Ne mijenjajte
ključeve ni zamjenske oznake poput `:name`, `%s` ili `{{value}}`. `validate`
uspoređuje sve ključeve i zamjenske oznake s izvornim jezikom. Nepotpun paket se
odbija, osim ako je svjesno zadan `--allow-missing`; postojeći jezik se ne
pregazuje bez `--replace`.

`add` instalira jedan objedinjeni `lang/<jezik>.php` i dodaje jezik u privatnu
instalacijsku konfiguraciju. Aplikacijska datoteka ima prednost pred
pojedinačnim prijevodima modula. Svaki paket mora imati sigurnu SVG zastavicu.
Instalirani paket u aplikacijski registar lokalizacije dodaje i izvorni naziv
jezika te zastavicu. Zato je izbornik jezika potpun i kada instalacija zadrži
stariji privatni `config/menu.php`; izričito zadani nazivi i putanje iz te
datoteke i dalje imaju prednost kao prilagodbe.
Zadnji uključeni jezik nije moguće isključiti ili ukloniti. U GUI Setupu
instalirani jezici ostaju u tablici, a objavljeni neinstalirani jezici nalaze se
u pretraživom višestrukom odabiru. Više označenih jezika instalira se jednim
ograničenim Setup zahtjevom.

Kada se promijene stringovi aplikacije ili modula, u repozitoriju jezika
pokrenite `php scripts/sync.php /putanja/do/Simbioze --version=GGGG.MM.DD.N`.
Alat u `pending/<jezik>.json` izdvaja samo nove i promijenjene izvorne ključeve;
postojeći prijevod nikada ne prepisuje. Pregledajte promjene, dopunite paket,
nakon pregleda uklonite njegovu pending datoteku, provjerite `languages
validate` i objavite novu reviziju. Sljedeća nadogradnja aplikacije osvježava
instalirani paket kada je objavljen novi digest; `languages update` to radi i
na zahtjev. Njemački je objavljen s prijevodom pripremljenim uz AI i još je
poželjan pregled izvornog govornika. Objavljeni francuski, španjolski i
talijanski prijevodi također su pripremljeni uz AI agenta ChatGPT/Codex.

### Datumi i vremena

Datumi i vremena vidljivi korisniku oblikuju se prema ICU pravilima jezika
sučelja: mijenjaju se redoslijed, nazivi mjeseci i 12/24-satni prikaz. Zapisi
u bazi, API polja, strojno čitljivi HTML datumi i tehnički logovi zadržavaju
stabilan format. Vremenska zona instalacije određuje lokalno vrijeme prikaza;
promjena jezika ne mijenja pohranjeni trenutak. Mogući su i UTF-8 jezici na
ćirilici, primjerice `sr-cyrl`.
