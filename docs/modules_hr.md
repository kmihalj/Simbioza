# Moduli

## Pregled

HeartPhrame sadrži sustav modula za organiziranje aplikacije u samostalne i
ponovno upotrebljive komponente. Modul je Composer paket koji može sadržavati
vlastite rute, servise, kontrolere, prikaze i druge funkcionalnosti. To je
preporučeni način izgradnje velikih aplikacija i pakiranja funkcionalnosti koje
se dijele među projektima.

---

## Izrada modula

Izrada modula ima dva glavna koraka: postavljanje Composer paketa i izrada
manifest datoteke.

### 1. Postavljanje Composer paketa

Modul je Composer paket posebne vrste `heartphrame-module`. U pravilu se razvija
kao zaseban projekt s vlastitom datotekom `composer.json`.

Primjer za novi modul:

```json
{
    "name": "my-vendor/my-module",
    "description": "Primjer HeartPhrame modula.",
    "type": "heartphrame-module",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "MyVendor\\MyModule\\": "src/"
        }
    },
    "require": {
        "php": ">=8.2"
    }
}
```

### 2. Manifest modula

Svaki modul u korijenu mora sadržavati `heartphrame-manifest.php`. To je ulazna
točka koja aplikaciji opisuje servise, rute i ostale mogućnosti modula.

Manifest mora vratiti instancu klase koja implementira
`HeartPhrame\Module\ModuleManifestInterface`. Najjednostavnije je proširiti
osnovnu klasu `HeartPhrame\Module\AbstractModuleManifest`.

Primjer manifesta s jednom rutom:

```php
<?php

declare(strict_types=1);

use HeartPhrame\Module\AbstractModuleManifest;
use MyVendor\MyModule\Controllers\DemoController;
use MyVendor\MyModule\Services\DemoService;

return new class extends AbstractModuleManifest
{
    /**
     * @inheritDoc
     */
    public function getModuleRoutes(): array
    {
        return [
            ['GET', '/demo', DemoController::class . '@index', 'demo.index'],
        ];
    }
};
```

> **Napomena:** potpuni popis dostupnih metoda nalazi se u
> `ModuleManifestInterface`.

---

## Instaliranje i uključivanje modula

1. **Instalirajte Composerom:** iz korijena glavne aplikacije pokrenite:

    ```shell
    composer require my-vendor/my-module
    ```

2. **Uključite u konfiguraciji:** dodajte modul u `config/app.php` aplikacije:

    ```php
    // config/app.php
    'modules' => [
        // Popis vrsta modula koje se mogu učitati; obično zadržite zadano.
        'loadable_types' => [
            'heartphrame-module',
        ],
        // Dodajte naziv paketa na popis uključenih modula.
        'enabled' => [
            'my-vendor/my-module', // Vaš novi modul.
        ],
    ],
    ```

## Kako se moduli učitavaju

Tijekom pokretanja aplikacije servis `ModuleBootstrapper` prolazi kroz sve
uključene module. Za svaki modul pronalazi `heartphrame-manifest.php`, učitava
ga i poziva njegove metode kako bi registrirao rute i ostalu konfiguraciju.

Manifest sadrži i metode za uvjetno učitavanje modula, primjerice prema
okruženju ili konfiguraciji:

- `canLoad()` se poziva pri inicijalizaciji aplikacije. Kada vrati `false`,
  modul se preskače.
- `requiresDeferredLoading()` označava da modul treba kontekst zahtjeva nakon
  pokretanja sesije. Zadano vraća `false`.
- `canLoadForRequest()` poziva `DeferredModuleLoaderMiddleware` samo kada
  `requiresDeferredLoading()` vraća `true`. Metoda odlučuje treba li modul
  učitati za trenutačni zahtjev.
- `canLoadForCli()` poziva `App::loadCommands()` u CLI kontekstu kada
  `requiresDeferredLoading()` vraća `true`. Budući da nema HTTP zahtjeva ni
  sesije, modul se može izričito uključiti ili isključiti za CLI. Zadano vraća
  `true`.

`AbstractModuleManifest` prema zadanim postavkama vraća vrijednosti koje
omogućuju učitavanje. Te metode možete nadjačati vlastitom logikom.
