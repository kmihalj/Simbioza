# HFClean documentation (EN)

This is the documentation for the HFClean integration application and its
enabled HeartPhrame modules. The Framework is consumed from upstream `main`; it
is not developed in this repository.

The documentation is structured for beginners and advanced users. Each topic
has a separate English and Croatian file.

Suggested path for beginners:

1. [Installation](installation_en.md)
2. [Module dependencies](module-dependencies_en.md)
3. [Database configuration](database_en.md)
4. [Configuration](configuration_en.md)
5. [Common workflows](common-workflows_en.md)

Suggested path for advanced users:

1. [Project structure](project-structure_en.md)
2. [Modules](modules_en.md)
3. [Common services](common-services_en.md)
4. [API v1 contract](api-v1-contract_en.md)
5. [API implementation plan](api-implementation-plan_en.md)

## Getting started

- [Installation](installation_en.md)
- [Module dependencies](module-dependencies_en.md)
- [Database configuration](database_en.md)
- [Configuration](configuration_en.md)
- [Project structure](project-structure_en.md)

Verify supported minimal combinations, bilingual documentation, and the
complete quality suite with:

```bash
php scripts/verify_clean_install_matrix.php
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
composer on-commit
```

## Fundamentals

- [Request lifecycle](request-lifecycle_en.md)
- [Routes](routes_en.md)
- [Middleware](middleware_en.md)
- [Views](views_en.md)
- [Sessions](sessions_en.md)
- [Localization](localization_en.md)

## Advanced topics

- [Common services and dependency injection](common-services_en.md)
- [Modules](modules_en.md)
- [Module dependencies](module-dependencies_en.md)
- [API v1 contract](api-v1-contract_en.md)
- [API implementation plan](api-implementation-plan_en.md)
- [Encryption](encryption_en.md)
- [Event dispatcher](event-dispatcher_en.md)

The application enables `aaieduhr/heartphrame-module-api` immediately after
Auth. Routes under `/api/v1` use Bearer keys and intentionally skip PHP session
startup and browser CSRF validation. Auth owns users, groups, key hashes, and
administrator rules; the API module owns versioned JSON contracts and the
conditional API-key screen.

## Guides

- [Common workflows](common-workflows_en.md)
- [Troubleshooting](troubleshooting_en.md)

## Croatian version

- [Hrvatska dokumentacija](index_hr.md)
