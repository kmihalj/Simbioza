# Dispečer događaja (PSR-14)

HeartPhrame pruža laganu implementaciju standarda PSR-14. Događaji i njihovi
slušači omogućuju razdvajanje dijelova aplikacijske logike.

## Pregled

- **Dispečer događaja:** `HeartPhrame\Event\EventDispatcher` implementira
  `Psr\EventDispatcher\EventDispatcherInterface`.
- **Pružatelj slušača:** `HeartPhrame\Event\ListenerProvider` implementira
  `Psr\EventDispatcher\ListenerProviderInterface`.

Implementacija podržava:

- registriranje slušača za određene klase događaja
- registriranje slušača za sučelja, pri čemu se pozivaju za svaki događaj koji
  implementira to sučelje
- događaje čije je širenje moguće zaustaviti preko
  `Psr\EventDispatcher\StoppableEventInterface`

## Konfiguracija

Slušači se podešavaju u `config/listeners.php`. Datoteka vraća polje objekata
`HeartPhrame\Event\EventListener`.

### Registriranje slušača

Dodajte novi zapis `EventListener` u polje datoteke `config/listeners.php`.

```php
// config/listeners.php

use HeartPhrame\Event\EventListener;
use App\Event\UserRegisteredEvent;
use App\Listener\SendWelcomeEmailListener;

return [
    new EventListener(
        UserRegisteredEvent::class,      // Klasa događaja.
        SendWelcomeEmailListener::class, // Klasa slušača.
    ),
    // Ovdje dodajte ostale slušače.
];
```

Konstruktor `EventListener` prima:

1. **klasu događaja:** puni naziv klase događaja koji se sluša
2. **klasu slušača:** puni naziv klase koja obrađuje događaj

## Izrada događaja

Događaj je PHP objekt koji sadrži povezane podatke. Ne mora proširivati posebnu
klasu ni implementirati sučelje, osim kada želite omogućiti zaustavljanje
širenja.

```php
namespace App\Event;

class UserRegisteredEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {}
}
```

## Izrada slušača

Slušač je callable koji prima instancu događaja. U HeartPhrameu su slušači
obično invokable klase.

```php
namespace App\Listener;

use App\Event\UserRegisteredEvent;

class SendWelcomeEmailListener
{
    public function __invoke(UserRegisteredEvent $event): void
    {
        // Logika slanja poruke na $event->email.
        echo "Sending welcome email to " . $event->email;
    }
}
```

## Objavljivanje događaja

U servis ili kontroler ubrizgajte
`Psr\EventDispatcher\EventDispatcherInterface`.

```php
namespace App\Service;

use App\Event\UserRegisteredEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserRegistrationService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}

    public function register(string $email): void
    {
        // ... logika stvaranja korisnika ...
        $userId = 123;

        // Objavi događaj.
        $event = new UserRegisteredEvent($userId, $email);
        $this->dispatcher->dispatch($event);
    }
}
```

## Zaustavljivi događaji

Ako događaj implementira `Psr\EventDispatcher\StoppableEventInterface`, slušač
može spriječiti njegovo daljnje širenje ostalim slušačima.

```php
namespace App\Event;

use Psr\EventDispatcher\StoppableEventInterface;

class BannedUserLoginAttemptEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
```

U slušaču:

```php
public function __invoke(BannedUserLoginAttemptEvent $event): void
{
    // Zapiši pokušaj.
    // ...

    // Spriječi obradu u ostalim slušačima.
    $event->stopPropagation();
}
```

## Unutarnje povezivanje

`EventDispatcher` se podešava u `config/services.php`. Koristi
`ListenerProvider` za pronalazak slušača i `CallableFactory` za njihovo lijeno
stvaranje. Klase slušača razrješavaju se kroz DI spremnik, pa u njih možete
ubrizgati ovisnosti.
