# Upravljanje sesijama

## Pregled

HeartPhrame Framework sadrži prilagodljiv sustav sesija s apstraktnim slojem nad
različitim načinima pohrane. Zadano koristi PHP-ovo izvorno upravljanje
sesijama, ali arhitektura omogućuje zamjenu drugim spremištima.

## Komponente

- **SessionInterface:** definira osnovne operacije poput `get()`, `set()` i
  `remove()`.
- **SessionFactoryInterface:** upravlja stvaranjem i podešavanjem sesije.
- **PhpSession:** zadana implementacija koja koristi PHP-ovo globalno polje
  `$_SESSION`.

---

## Konfiguracija

Ponašanje sesije podešava se pod ključem `session` u `config/app.php`.

```php
// config/app.php
'session' => [
    'options' => [
        'use_cookies' => 1,
        'cookie_secure' => 1, // Preporučeno u produkciji.
        'cookie_httponly' => 1, // Ublažava XSS.
        'cookie_samesite' => 'Lax',
        'name' => 'HEARTPHRAME_SESSION',
        'gc_maxlifetime' => 1440, // 24 minute.
    ],
    'excluded_routes' => [
        // Sesija se ne pokreće za rute koje počinju ovim prefiksima.
        // '/api/public',
    ],
],
```

### Mogućnosti sesije

Polje `options` sadrži postavke koje se izravno prosljeđuju PHP funkciji
`session_start()`. Ovdje možete postaviti standardne mogućnosti PHP sesije.

### Izuzimanje ruta

Polje `excluded_routes` sadrži URL prefikse za koje se sesija ne pokreće. To je
korisno za javne API krajnje točke i druge rute kojima stanje sesije nije
potrebno.

---

## Uporaba u kontrolerima

U konstruktor ili metodu kontrolera ubrizgajte
`HeartPhrame\Session\SessionInterface`. DI spremnik automatski pruža instancu
sesije.

Primjer košarice:

```php
<?php

namespace App\Controllers;

use HeartPhrame\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use HeartPhrame\Http\ResponseFactory;

class CartController
{
    public function __construct(
        protected readonly SessionInterface $session,
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    public function addItem(): ResponseInterface
    {
        // Dohvati postojeću košaricu ili stvori novu.
        $cart = $this->session->get('cart', []);

        // Dodaj stavku.
        $newItem = ['id' => 123, 'name' => 'Primjer proizvoda'];
        $cart[] = $newItem;

        // Spremi promijenjenu košaricu u sesiju.
        $this->session->set('cart', $cart);

        return $this->responseFactory->redirect('/cart/view');
    }

    public function view(): ResponseInterface
    {
        $cart = $this->session->get('cart', []);

        return $this->responseFactory->view('cart/view', ['items' => $cart]);
    }

    public function clear(): ResponseInterface
    {
        // Ukloni košaricu iz sesije.
        $this->session->remove('cart');

        return $this->responseFactory->redirect('/cart/view');
    }
}
```

## Middleware sesije

`HeartPhrame\Middleware\StartSessionMiddleware` globalno je registriran u
`config/middleware.php`. Pokreće sesiju na početku zahtjeva i sprema podatke na
kraju. Izvršava se za svaki zahtjev osim za rute izuzete konfiguracijom.
