# Rute

Usmjeravanje u HeartPhrameu povezuje dolazni HTTP zahtjev s određenom akcijom
kontrolera. Rute aplikacije definiraju se u `config/routes.php`.

---

## Definiranje ruta

Rute se mogu definirati jednostavnim PHP poljem ili objektno orijentiranim
pristupom s klasama `Route` i `RouteGroup` za složenije konfiguracije.

### Osnovna definicija rute

Jednostavna ruta koristi polje sljedećeg oblika:

`[HTTP_METHOD, PATH, HANDLER, NAME, [MIDDLEWARE]]`

- **HTTP_METHOD:** HTTP metoda, primjerice `GET` ili `POST`
- **PATH:** URL putanja rute
- **HANDLER:** kontroler i metoda koja se izvršava
- **NAME:** jedinstven naziv rute za generiranje URL-a
- **MIDDLEWARE:** opcionalno polje klasa middlewarea

Primjer iz `config/routes.php`:

```php
['GET', '/', HomeController::class . '@index', 'home', [SampleMiddleware::class]],
```

Početni HFClean kontroler provjerava i neutralni servis
`heartphrame.application_homepage_resolver`. Workspace taj servis registrira
samo dok je modul uključen. Pronađena objavljena stranica ili ACL-vidljiv cilj
Sažetaka daje privremeni privatni redirect na svoj kanonski Workspace URL;
inače se prikazuje ugrađena probna naslovnica. Cilj Sažetaka nosi strukturirane
vrijednosti vidljivosti `tree` i `options`, a ne administratorski slobodno
sastavljen query string. Auth i host aplikacija zato potpuno rade bez Workspacea.

Za eksplicitniju definiciju upotrijebite klasu `Route`:

```php
use HeartPhrame\Routing\Route;
use HeartPhrame\CodeBook\HttpMethodsEnum;

new Route(
    HttpMethodsEnum::GET, // Enum HTTP metode.
    '/about',
    [HomeController::class, 'about'], // Obrađivač kao polje.
    'about'
),
```

### Obrađivači ruta

Obrađivač određuje kod koji se izvršava nakon povezivanja rute. Može se navesti
na dva načina:

1. **Sintaksa niza znakova:** `ControllerName::class . '@methodName'`
2. **Sintaksa polja:** `[ControllerName::class, 'methodName']`

Oba zapisa razrješavaju se u istu akciju kontrolera.

## Grupe ruta

Grupe primjenjuju zajednička svojstva, primjerice prefiks putanje ili
middleware, na više ruta.

```php
use HeartPhrame\Routing\Route;
use HeartPhrame\Routing\RouteGroup;
use HeartPhrame\Routing\RouteGroupProperties;

new RouteGroup(
    new RouteGroupProperties(
        '/admin', // Prefiks putanje.
        'admin.', // Prefiks naziva.
        [SampleMiddleware::class] // Zajednički middleware.
    ),
    new Route(
        HttpMethodsEnum::GET,
        '/dashboard',
        [AdminController::class, 'dashboard'],
        'dashboard' // Konačni naziv: admin.dashboard.
    ),
    new Route(
        HttpMethodsEnum::GET,
        '/users',
        [AdminController::class, 'users'],
        'users' // Konačni naziv: admin.users.
    ),
);
```

Sve rute u grupi dobivaju prefiks putanje `/admin`, prefiks naziva `admin.` i
`SampleMiddleware`.

## Parametri ruta

Ruta može iz URL-a dohvatiti dinamičke segmente poput identifikatora korisnika.
Takvi segmenti nazivaju se parametrima rute.

### Definiranje parametra

Parametar se definira vitičastim zagradama `{}`.

```php
// config/routes.php
['GET', '/users/{id}', 'UserController@show', 'users.show']
```

Ruta odgovara URL-ovima poput `/users/1` i `/users/42`.

### Dohvat parametra u kontroleru

Vrijednost segmenta prosljeđuje se kao argument metode kontrolera. Naziv
argumenta **mora** odgovarati nazivu parametra u definiciji rute.

```php
// src/Controllers/UserController.php
namespace App\Controllers;

class UserController
{
    public function show(string $id): ResponseInterface
    {
        // $id sadrži vrijednost iz URL-a.
        // Za zahtjev /users/123 vrijednost je '123'.

        // ... pronađi korisnika i vrati odgovor ...
    }
}
```

U kontroleru možete navesti i tip poput `int $id`; Framework će pokušati
automatski pretvoriti parametar u navedeni tip.
