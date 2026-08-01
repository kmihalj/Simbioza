# Event Dispatcher (PSR-14)

HeartPhrame provides a lightweight implementation of PSR-14 (Event Dispatcher).
This component allows you to decouple your application logic by dispatching
events and having listeners react to them.

## Overview

- **Event Dispatcher**: `HeartPhrame\Event\EventDispatcher`
implements `Psr\EventDispatcher\EventDispatcherInterface`.
- **Listener Provider**: `HeartPhrame\Event\ListenerProvider`
implements `Psr\EventDispatcher\ListenerProviderInterface`.

The implementation supports:
- Registering listeners for specific event classes.
- Registering listeners for interfaces (listeners will be called for any
event implementing that interface).
- Stoppable events (via `Psr\EventDispatcher\StoppableEventInterface`).

## Configuration

In the HeartPhrame application, event listeners are configured in
`config/listeners.php`. This file returns an array of
`HeartPhrame\Event\EventListener` objects.

### Registering a Listener

To register a new listener, add a new `EventListener` entry to the array
in `config/listeners.php`.

```php
// config/listeners.php

use HeartPhrame\Event\EventListener;
use App\Event\UserRegisteredEvent;
use App\Listener\SendWelcomeEmailListener;

return [
    new EventListener(
        UserRegisteredEvent::class,      // The event class
        SendWelcomeEmailListener::class, // The listener class
    ),
    // Add more listeners here...
];
```

The `EventListener` constructor takes two arguments:
1.  **Event Class**: The fully qualified class name of the event you want
to listen to.
2.  **Listener Class**: The fully qualified class name of the listener that
handles the event.

## Creating Events

An event is simply a PHP object that holds data related to the event. It does
not need to extend any specific class or implement any interface, unless you
want it to be stoppable.

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

## Creating Listeners

A listener is a callable that receives the event instance. In HeartPhrame,
listeners are typically invokable classes.

```php
namespace App\Listener;

use App\Event\UserRegisteredEvent;

class SendWelcomeEmailListener
{
    public function __invoke(UserRegisteredEvent $event): void
    {
        // Logic to send email to $event->email
        echo "Sending welcome email to " . $event->email;
    }
}
```

## Dispatching Events

To dispatch an event, you need to inject the
`Psr\EventDispatcher\EventDispatcherInterface` into your service or controller.

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
        // ... create user logic ...
        $userId = 123;

        // Dispatch the event
        $event = new UserRegisteredEvent($userId, $email);
        $this->dispatcher->dispatch($event);
    }
}
```

## Stoppable Events

If an event implements `Psr\EventDispatcher\StoppableEventInterface`, a
listener can stop further propagation of the event to other listeners.

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

In a listener:

```php
public function __invoke(BannedUserLoginAttemptEvent $event): void
{
    // Log the attempt
    // ...

    // Stop other listeners from handling this
    $event->stopPropagation();
}
```

## Internal Wiring

The `EventDispatcher` is configured in `config/services.php`. It uses the
`ListenerProvider` to find listeners and a `CallableFactory` to instantiate
listener classes lazily. This means your listener classes are resolved through
the dependency injection container, allowing you to inject dependencies
into your listeners.
