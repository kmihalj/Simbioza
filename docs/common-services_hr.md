# Zajednički servisi

HeartPhrame pruža zajedničke servise kojima upravlja spremnik za ubrizgavanje
ovisnosti (DI).

## Uporaba servisa

Za servis koji želite koristiti navedite njegovo sučelje ili klasu kao tip u
konstruktoru ili metodi. Spremnik će automatski ubrizgati odgovarajuću
implementaciju.

Sljedeći primjer ubrizgava `LoggerInterface` u konstruktor kontrolera i
`ServerRequestInterface` u akciju:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->debug('Podaci zahtjeva: ', $request->getParsedBody() ?? []);
        // ... vrati odgovor
    }
}
```

---

## Zadani servisi

U nastavku su zadani servisi dostupni u spremniku, grupirani prema namjeni.

### Servisi usklađeni s PSR standardima

Ovi servisi implementiraju uobičajene
[PHP-FIG PSR standarde](https://www.php-fig.org/psr/).

#### PSR-3 zapisnik

Ubrizgajte `Psr\Log\LoggerInterface` za zapisivanje poruka. Zapisi se prema
zadanim postavkama spremaju u datoteku definiranu u `config/app.php`.

```php
/** @var \Psr\Log\LoggerInterface $logger */
$logger->warning('Ponovni pokušaj Calendar workera je zakazan.', [
    'module' => 'calendar',
    'event_uuid' => $eventUuid,
    'attempt' => $attempt,
    'exception' => $exception,
]);
```

PSR-3 koristite za dijagnostiku, neočekivane kvarove, ponovne pokušaje workera
i operativni kontekst. Uvijek navedite stabilni `module` kanal i strukturirane,
neosjetljive identifikatore. Nikad ne zapisujte zaporke, tokene, kolačiće,
tijelo zahtjeva ili odgovora, sadržaj dokumenta ili e-pošte ni sadržaj učitane
datoteke. Rotirajući handler dodatno redigira uobičajene oblike vjerodajnica.

Poslovne radnje pripadaju u `AuditLogService` ili neutralni domenski događaj
koji Audit sluša. Taj append-only zapis u bazi je pretraživ i po izboru prenosiv
kroz Backup. Datoteke tehničkog loga namjerno su isključene iz svih backupa.

#### PSR-7 poslužiteljski zahtjev

Ubrizgajte `Psr\Http\Message\ServerRequestInterface` za pristup informacijama o
trenutačnom HTTP zahtjevu.

```php
/** @var \Psr\Http\Message\ServerRequestInterface $request */
$queryParams = $request->getQueryParams();
```

#### PSR-14 dispečer događaja

Ubrizgajte `Psr\EventDispatcher\EventDispatcherInterface` za objavu događaja i
neovisnu komunikaciju među dijelovima aplikacije.

```php
// Događaj je jednostavan objekt koji sadrži podatke.
class UserRegisteredEvent { ... }
$event = new UserRegisteredEvent($userId);

/** @var Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher */
$eventDispatcher->dispatch($event);
```

#### PSR-16 predmemorija

Ubrizgajte `Psr\SimpleCache\CacheInterface` za rad s predmemorijom. Zadana
implementacija koristi datoteke.

```php
/** @var \Psr\SimpleCache\CacheInterface $cache */
if ($cache->has('products-list')) {
    return $cache->get('products-list');
}
```

### Servisi Frameworka

Ovi servisi pružaju temeljne mogućnosti Frameworka.

#### Konfiguracija

Ubrizgajte `HeartPhrame\Config\ConfigInterface` za čitanje vrijednosti iz
konfiguracijskih datoteka.

```php
/** @var \HeartPhrame\Config\ConfigInterface $config */
// Dohvati vrijednost iz config/app.php ili zadanu vrijednost.
$appName = $config->get('app.name', 'My App');

// Dohvati obavezan neprazan niz znakova iz config/env.php.
$logLevel = $config->getAsNonEmptyStringOrFail('env.log_level');
```

#### Tvornica odgovora

Ubrizgajte `HeartPhrame\Http\ResponseFactory` za stvaranje instanci
`ResponseInterface`. To je standardni način izrade odgovora u kontrolerima.

```php
/** @var \HeartPhrame\Http\ResponseFactory $responseFactory */

// Izradi HTML odgovor iz predloška prikaza.
$response = $responseFactory->view('home/index', ['foo' => 'bar']);

// Izradi JSON odgovor.
$response = $responseFactory->json(['data' => 'hello']);

// Izradi odgovor za preusmjeravanje.
$response = $responseFactory->redirect('/login');
```

#### Generator URL-ova

Ubrizgajte `HeartPhrame\Routing\UrlGenerator` za generiranje putanja i punih
URL-ova imenovanih ruta.

```php
/** @var \HeartPhrame\Routing\UrlGenerator $urlGenerator */

// Dohvati putanju rute naziva home.
$path = $urlGenerator->getPathFor('home'); // -> "/"

// Dohvati puni URL rute s parametrima.
$url = $urlGenerator->getUrlFor('users.profile', ['id' => 123]); // -> "http://.../users/123"
```

#### Sesija

Ubrizgajte `HeartPhrame\Session\SessionInterface` za čitanje i zapisivanje
podataka korisničke sesije.

```php
/** @var \HeartPhrame\Session\SessionInterface $session */
$session->set('user_id', 123);
$userId = $session->get('user_id');
```

#### Obrađivač autentikacije

Ubrizgajte `HeartPhrame\Authn\AuthnHandlerInterface` za upravljanje
autentikacijom korisnika.

```php
/** @var \HeartPhrame\Authn\AuthnHandlerInterface $authn */
if ($authn->isAuthenticated()) {
    $user = $authn->getUser();
}
```

#### Šifriranje

Ubrizgajte `HeartPhrame\Encryption\EncryptionInterface` za šifriranje i
dešifriranje ključem iz konfiguracije okruženja.

```php
/** @var \HeartPhrame\Encryption\EncryptionInterface $encryption */
$encrypted = $encryption->encrypt('my-secret-data');
$decrypted = $encryption->decrypt($encrypted);
```

#### Iscrtavanje prikaza

Ubrizgajte `HeartPhrame\View\View` za iscrtavanje predloška u niz znakova. To je
servis niže razine.

```php
/** @var \HeartPhrame\View\View $view */
$htmlContent = $view->for('emails/welcome', ['name' => 'Alex']);
```

> **Napomena:** za vraćanje HTML-a iz kontrolera obično je jednostavnije
> koristiti `ResponseFactory::view()`, koji stvara i objekt `Response`.

#### Obrađivač upozorenja

Ubrizgajte `HeartPhrame\Alert\AlertHandler` za upravljanje flash porukama koje
će se prikazati pri sljedećem zahtjevu.

```php
/** @var \HeartPhrame\Alert\AlertHandler $alertHandler */
$alertHandler->add(new Alert('Profil je uspješno ažuriran!', AlertLevelEnum::Success));
```

#### Obrađivač prethodnih podataka zahtjeva

Ubrizgajte `HeartPhrame\Validator\OldRequestDataHandler` za čuvanje korisničkog
unosa između zahtjeva, obično nakon neuspjele validacije obrasca.

```php
// Nakon neuspjele validacije u akciji kontrolera:
/** @var \HeartPhrame\Validator\OldRequestDataHandler $oldRequestDataHandler */
$oldRequestDataHandler->addOldData($request->getParsedBody());

// U prikazu dohvatite prethodne podatke i ponovno popunite obrazac.
```

### Vlastiti servisi

Svaka klasa koju spremnik može izgraditi može se koristiti kao servis. Kada
klasa ima jednostavne ovisnosti poznate spremniku, navedite je kao tip u
konstruktoru ili metodi. Spremnik će je automatski izgraditi bez dodatne
konfiguracije.
