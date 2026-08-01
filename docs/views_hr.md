# Prikazi

HeartPhrame za predloške prikaza koristi obične PHP datoteke. U prikazima možete
koristiti mogućnosti PHP-a bez učenja dodatnog predloškovnog jezika.

---

## Izrada prikaza

Prikazi se zadano nalaze u direktoriju `views/`, što se može promijeniti u
`config/app.php`. Prikaz je PHP datoteka s HTML oznakama dijela stranice.

Primjer `views/greeting.php`:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $name
 */
?>

<h1>Pozdrav, <?= $this->escape($name) ?>!</h1>
```

Svaki prikaz iscrtava instanca `HeartPhrame\View\View`, dostupna kao `$this`.
Pruža metode poput `escape()` iz prethodnog primjera.

> **Napomena:** dinamičke podatke koje ispisujete treba izbjegavati kako biste
> spriječili XSS napade.

## Stvaranje odgovora prikaza iz kontrolera

U akciji kontrolera možete iscrtati prikaz i vratiti ga kao
`Psr\Http\Message\ResponseInterface`. Za to koristite
`HeartPhrame\Http\ResponseFactory`.

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view('greeting', ['name' => 'Svijete']);
    }
}
```

## Prosljeđivanje podataka prikazima

Podaci se prosljeđuju kao asocijativno polje u drugom argumentu metode `view`.
Ključevi polja izdvajaju se kao varijable u dosegu prikaza.

U prethodnom primjeru ključ `name` postaje varijabla `$name` u predlošku
`greeting.php`.

## Uporaba rasporeda

Rasporedi definiraju zajedničku strukturu, primjerice zaglavlje, podnožje i
bočne stupce. Zadani raspored `views/layouts/main.php` podešava se u
`config/app.php`.

Raspored sadrži glavnu HTML strukturu i mjesto u koje se umeće sadržaj prikaza.

Primjer `views/layouts/main.php`:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string|null $content
 */
?>

<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Moja aplikacija</title>
</head>
<body>
    <header>
        <h1>Moja aplikacija</h1>
    </header>

    <main>
        <?= $content ?? '' ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Moja aplikacija</p>
    </footer>
</body>
</html>
```

Generirani sadržaj prikaza prosljeđuje se rasporedu u varijabli `$content`.

### Odabir drugog rasporeda

Kada prikaz treba koristiti raspored različit od zadanog, njegov naziv
proslijedite kao treći argument metode `view`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view(
            'greeting',
            ['name' => 'Svijete'],
            'layouts/alternative'
        );
    }
}
```

### Isključivanje rasporeda

Za prikaz bez rasporeda, primjerice AJAX odgovor s HTML fragmentom, postavite
naziv rasporeda na `null`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    public function hello(): ResponseInterface
    {
        return $this->responseFactory->view(
            'greeting',
            ['name' => 'Svijete'],
            null,
        );
    }
}
```

## Iscrtavanje prikaza u niz znakova

Metoda `for` instance `View` vraća iscrtani sadržaj kao niz znakova:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use HeartPhrame\View\View;

class HomeController
{
    public function __construct(
        protected readonly View $view,
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    public function hello(): ResponseInterface
    {
        // Sadržaj sa zadanim rasporedom.
        $viewContent = $this->view->for('greeting', ['name' => 'Svijete']);

        // Sadržaj s drugim rasporedom.
        $viewContentAlternative = $this->view->for('greeting', ['name' => 'Svijete'], 'layouts/alternative');

        // Sadržaj bez rasporeda.
        $viewContentWithoutLayout = $this->view->for('greeting', ['name' => 'Svijete'], null);

        return $this->responseFactory->html($viewContent);
    }
}
```

## Iscrtavanje parcijalnih prikaza

Prikaz se može iscrtati kao parcijal radi ponovne uporabe. Primjerice, izradite
`views/partials/flash.php`:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $message
 */
?>

<p><?= $this->escape($message) ?></p>
```

Zatim ga iscrtajte iz drugog prikaza:

```php
<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $message
 */
?>

<?= $this->forPartial('partials/flash', ['message' => $message]) ?>
```

## Iscrtavanje prikaza modula

`HeartPhrame\Http\ResponseFactory` i `HeartPhrame\View\View` mogu iscrtavati
prikaze iz modula.

```php
<?php

declare(strict_types=1);

/** @var \HeartPhrame\Http\ResponseFactory $responseFactory */
$responseInstance = $responseFactory->viewForModule(
    'moduleName',
    'viewName',
    ['name' => 'Svijete'],
    layout: 'layouts/alternative', // null isključuje, a true koristi zadani raspored.
    useModuleLayout: true, // false koristi zadani raspored aplikacije.
);

/** @var \HeartPhrame\View\View $view */
$viewString = $view->forModule(
    'moduleName',
    'viewName',
    ['name' => 'Svijete'],
    layout: 'layouts/alternative', // null isključuje, a true koristi zadani raspored.
    useModuleLayout: true, // false koristi zadani raspored aplikacije.
);
```
