# Dobro došli u HFClean / Welcome to HFClean

Ovo je dokumentacija integracijske HFClean aplikacije i uključenih HeartPhrame
modula. Framework se koristi s uzvodne grane `main`; ne razvija se u ovom
repozitoriju.

This is the documentation for the HFClean integration application and its
enabled HeartPhrame modules. The Framework is consumed from upstream `main`; it
is not developed in this repository.

---

## Getting Started

New to HeartPhrame? Start here to get your first application up and running.

* [Installation](installation.md)
* [Project Structure](project-structure.md)
* [Configuration](configuration.md)

## The Basics

Learn the fundamental concepts that power a HeartPhrame application.

* [Request Lifecycle](request-lifecycle.md)
* [Routing](routes.md)
* [Middleware](middleware.md)
* [Views](views.md)
* [Sessions](sessions.md)
* [Localization](localization.md)

## Digging Deeper

Explore more advanced topics and the framework's core components.

* [Common Services (Dependency Injection)](common-services.md)
* [Modules](modules.md)
* [Module dependencies / Ovisnosti modula](module-dependencies.md)
* [API v1 contract / API v1 ugovor](api-v1-contract.md)
* [API implementation plan / Plan implementacije API-ja](api-implementation-plan.md)
* [Database](database.md)
* [Encryption](encryption.md)
* [Event Dispatcher](event-dispatcher.md)

The application enables `aaieduhr/heartphrame-module-api` immediately after
Auth. Routes under `/api/v1` use Bearer keys and intentionally skip PHP session
startup and browser CSRF validation. Auth owns users, groups, key hashes, and
administrator rules; the API module owns versioned JSON contracts and the
conditional API-key screen.

Aplikacija uključuje `aaieduhr/heartphrame-module-api` odmah nakon Autha. Rute
pod `/api/v1` koriste Bearer ključeve i namjerno preskaču pokretanje PHP sesije
te pregledničku CSRF provjeru. Auth posjeduje korisnike, grupe, hashove ključeva
i administratorska pravila; API modul posjeduje verzionirane JSON ugovore i
uvjetni ekran API ključeva.

## Guides

Follow these step-by-step guides for common development tasks.

* [Common Workflows](common-workflows.md)
* [Troubleshooting](troubleshooting.md)
