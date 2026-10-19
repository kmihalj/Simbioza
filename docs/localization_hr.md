# Lokalizacija

Simbioza koristi hrvatski kao izvorni jezik sučelja. Tekst vidljiv korisniku
piše se hrvatski u kodu i isti je ključ u svim jezičnim datotekama. Jezični
paket smije biti preveden s engleskog kao radnog jezika prevoditelja, ali to
ne dodaje drugi korak prevođenja pri prikazu.
Tehnički identifikatori, primjerice `can_view` i oznake formata datuma,
ostaju stabilni kodovi jer nisu tekstovi sučelja.

## Konfiguracija

Zadane postavke lokalizacije određuje aplikacijski `config/app.php`.
Izbor instalacije sprema se u `installation.php`, a potpuni registar jezika u
`languages.php`, uz `HPH_CONFIG_PATH` i uređene override slojeve. GUI i CLI
čitaju iste efektivne postavke i spremaju u zadnji deklarirani sloj; promjene
zaključava `.languages.lock`. Najviši postojeći `languages.php` je potpuni
registar, ne djelomično spajanje ključeva. Prava, ručne izmjene i putanje resursa
opisani su u [konfiguracijskoj uputi](configuration_hr.md#instalacija-moduli-i-jezici).

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

Kad je `detect_browser_locale` uključen (zadano ako instalacija to ne
promijeni), bira se prvi uključeni jezik koji odgovara preferencijama
preglednika iz zaglavlja `Accept-Language`. Regionalna oznaka poput `fr-CH`
može koristiti instalirani paket `fr`. Ako odgovarajući paket ne postoji,
ostaje primarni jezik aplikacije. Ručni izbor u izborniku ima prednost i u
ovom se pregledniku pamti godinu dana. Kolačić vrijedi samo za putanju
pojedine instalacije, pa `/demo` i `/hfc` ne mijenjaju jezik jedan drugome.
Brisanje kolačića stranice ponovno uključuje automatski odabir. Instalacija
može izričito postaviti `detect_browser_locale` na `false` ako uvijek želi
početi primarnim jezikom dok ga korisnik ne promijeni.

## Paketne jezične datoteke

Izvorni katalozi aplikacije/modula su PHP datoteke koje vraćaju polje prijevoda.
To su kodovni resursi u `translations_dir`/modulskim `lang` direktorijima,
nazvani prema jeziku (npr. `hr.php`, `en.php`). Developeri ih uređuju uz izmjene
koda. Administratori dodatne ili zamjenske pakete instaliraju kao JSON pod
`data/languages/catalogs/`, nikada kao PHP u aplikacijski `lang/`.
Vidi [runtime pohranu i backup](#runtime-spremanje-prioritet-i-backup) niže.

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

`add` instalira jedan objedinjeni JSON katalog u
`data/languages/catalogs/<jezik>.json` i dodaje jezik u privatnu instalacijsku
konfiguraciju. Instalirane vrijednosti imaju prednost pred paketnim prijevodima
aplikacije i modula. Svaki paket mora imati sigurnu SVG zastavicu.
Instalirani paket u aplikacijski registar lokalizacije dodaje i izvorni naziv
jezika te zastavicu. Zato je izbornik jezika potpun i kada instalacija zadrži
privatni `HPH_CONFIG_PATH/menu/menu.php`; izričito zadani nazivi i putanje iz te
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

## Provjera nadogradnji samo prijevoda

U Setup → Jezici gumb **Provjeri nadogradnje jezika** osvježava javni katalog,
bez instaliranja koda ili jezičnih datoteka. Tablica razlikuje tri izvora:

- **Ugrađeni prijevodi** prikazuju verziju instalirane aplikacije, primjerice
  `Simbioza 0.3.10`. Prepoznaju se odmah nakon nove instalacije i nadogradnje
  aplikacije; dodatna CLI instalacija jezika nije potrebna.
- **Jezični paket** prikazuje stvarnu reviziju instaliranu iz repozitorija.
- **Ručni prijevod** nema potvrđenu reviziju repozitorija. Nikada se ne zamjenjuje
  automatski niti se prikazuje kao nepromijenjeni objavljeni paket.

Dostupna revizija paketa ima vlastitu numeraciju, neovisnu o Simbiozi.
Read-only datoteka `resources/localization/bundled-languages.json` bilježi
izvorno polazište repozitorija za ugrađene jezike. To su metapodaci izdanja,
ne korisnička postavka niti potvrda da je paket repozitorija instaliran.
Polazište ostaje isto pri nadogradnjama aplikacije kako bi se otkrila kasnija
zasebna objava prijevoda, uključujući promjenu digesta bez promjene broja revizije.
Instalacija ponuđene nadogradnje gumbom **Ažuriraj** bilježi stvarnu reviziju paketa.
Ako mrežna provjera zakaže, GUI ne prikazuje stari cache kao uspješnu provjeru.

Instaliranje, zamjena, uključivanje i uklanjanje jezika ne mijenjaju kod i ne
trebaju privilegirani paketni helper. GUI radnje rade na mod_php, FPM-u i
sigurnom FPM-u ako su podatkovni direktorij i završni konfiguracijski sloj
zapisivi. Postojeće admin i CSRF provjere ostaju. Ako prava onemoguće GUI radnje,
prikazuju se ove naredbe; pokrenite ih iz korijena Simbioze kao ovlašteni
runtime/deploy korisnik, s istim `HPH_CONFIG_PATH` kao taj site:

```bash
vendor/bin/hph languages available --refresh
vendor/bin/hph languages update
```

Za ručni prijevod gumb **Preuzmi jezični paket** nudi provjerenu zamjenu kroz
GUI i traži potvrdu. Ista radnja kroz CLI je:

```bash
vendor/bin/hph languages install hr --replace
```

Promjena samo vrijednosti prijevoda ne zahtijeva aplikacijski/modulski tag ni
Composer release. Objavite jezični JSON i njegov novi SHA-256 u manifestu
jezičnog repozitorija; nova jezična revizija je preporučena. Otkrivanje koristi
digest i kada naziv revizije ostane isti. I dalje vrijede provjera checksum-a,
potpunosti/zamjenskih oznaka i očuvanje prethodne uključenosti jezika.
Ručno instalirani JSON paket uklanja prethodnu evidenciju revizije repozitorija:
vlastiti prijevod ne prikazuje se kao nepromijenjeni objavljeni paket.

## Runtime spremanje, prioritet i backup

| Putanja | Namjena |
| --- | --- |
| Aplikacijski/modulski `lang/<jezik>.php` | Read-only paketni prijevodi; zamjenjuju se s nadogradnjom koda |
| `data/languages/catalogs/<jezik>.json` | Instalirani objedinjeni prijevodi, nikada PHP izvor |
| `data/languages/flags/<jezik>.svg` | Provjerena instalirana SVG zastavica |
| `data/languages/repository-versions.json` | Objavljena revizija i digest izvornog paketa, ne konfiguracija |
| `HPH_CONFIG_PATH/languages.php` | Nazivi jezika i registar u završnom deklariranom sloju |
| `HPH_CONFIG_PATH/installation.php` | Uključeni jezici i primarni jezik u završnom deklariranom sloju |

`App\Localization\RuntimeTranslator` u host aplikaciji proširuje postojeći
Framework prevoditelj. Framework nije promijenjen. Paketni direktorij i dalje
se zove `lang`, pa se modulsko učitavanje ne mijenja. Instalirani ključevi imaju
prednost; nedostajući koriste aktualni katalog aplikacije/modula i postojeći
pričuvni jezik. Novi zahtjev odmah čita zamijenjeni JSON, bez poništavanja PHP
OPcachea. Nevaljani instalirani katalog prijavljuje grešku, umjesto prešutnog
korištenja starog prijevoda.

Instalirajte JSON paket kroz GUI/CLI, ne kopirajte PHP u podatkovni direktorij.
Generirani runtime dokument ima ovaj mali podatkovni oblik:

```json
{
  "format": "simbioza-runtime-translations",
  "version": 1,
  "locale": "de",
  "translations": {"Jezici": "Sprachen"}
}
```

Ovo je primjer internog kataloga, ne potpunog distribucijskog jezičnog paketa.
Uređivanje već instaliranog kataloga mijenja vrijednosti pri sljedećem zahtjevu;
novi jezik zahtijeva i registar te politiku uključivanja. Koristite `languages
template`, `validate` i `add` kako bi se zajedno provjerili nazivi, zamjenske
oznake i zastavica. Runtime datoteke ograničene su na 2 MiB. Podređeni ciljevi
preko symlinka i nevaljane oznake jezika/putanje odbijaju se.

GUI i CLI uzimaju `.languages.lock` u završnom konfiguracijskom sloju, zatim
`data/languages/.packs.lock`. Neuspjela objava pri instalaciji/uklanjanju vraća
sve promijenjene jezične resurse i privatne jezične postavke. Uklanjanje nikada
ne briše paketne PHP datoteke aplikacije/modula. Nadogradnja čuva prethodnu
uključenost; zadnji uključeni jezik ostaje zaštićen.

Backup postavki aplikacije, shema 3, uključuje registar jezika, instalirani JSON,
provjerene zastavice i revizije. Uključenost/primarni jezik ostaju u aplikacijskim
postavkama. Vraćanje sitea/komponente koristi lokalne putanje odredišta, ne
putanje izvora, i ne uvozi izvršive PHP kataloge. Izričiti replace uklanja
izostavljene runtime pakete; merge čuva lokalne jezike. Za dolazni jezik isključivo
paketni katalog ili zastavica zamjenjuje odredišni runtime override i pri mergeu.
Greška kasnijeg
providera ili DB commita vraća konfiguraciju, JSON, zastavice i revizije.
Cache, preuzimanja, predlošci i lockovi ne ulaze u backup. Backup samo stranice/
područja ne prenosi instalacijske jezike.

Pri zamjeni imagea/koda zadržite podatkovni i konfiguracijski mount; aplikacijski
`lang/` ne treba pravo pisanja za administraciju jezika. Simbioza 0.3.0 koristi
novu instalaciju: runtime pokretanje ne sadrži migraciju starih PHP paketa.
