# Lokalizacija

HeartPhrame pruža jednostavan i prilagodljiv sustav za prevođenje aplikacije na
više jezika.

## Konfiguracija

Lokalizacija se podešava u `config/app.php`:

```php
return [
    'localization' => [
        'locale' => 'hr',
        'fallback_locale' => 'en',
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
    'welcome' => 'Dobro došli u našu aplikaciju!',
    'greet' => 'Pozdrav, :name!',
    'auth' => [
        'login' => 'Prijavite se',
    ],
];
```

## Uporaba

### Pomoćne funkcije

Globalne pomoćne funkcije `__()` i `__e()` — druga izbjegava HTML — dostupne su
u prikazima, kontrolerima i drugim dijelovima aplikacije.

```php
echo __('welcome');
echo __e('greet', ['name' => 'Ivan']);
echo __('auth.login'); // Podržana je točkasta notacija za ugniježđena polja.
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
        $message = $this->translator->trans('welcome');
        // ...
    }
}
```

### U prikazima

Servis `translator` dostupan je kao globalna varijabla u predlošcima prikaza.
Zbog sažetosti se ipak preporučuju funkcije `__()` i `__e()`.

```php
<h1><?= __('welcome') ?></h1>
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
