# Simbioza documentation (EN)

This is the documentation for the Simbioza collaborative knowledge application and its
enabled HeartPhrame modules. The Framework is consumed from upstream `main`; it
is not developed in this repository.

Brand slogan: **Knowledge that lives together.**

- [Brand identity and the Simbioza theme](branding_en.md)

The documentation is structured for beginners and advanced users. Each topic
has a separate English and Croatian file.

Suggested path for beginners:

1. [Installation](installation_en.md)
2. [Six clean installations lab record](installation-lab_en.md)
3. [Module dependencies](module-dependencies_en.md)
4. [Database configuration](database_en.md)
5. [Configuration](configuration_en.md)
6. [Common workflows](common-workflows_en.md)
7. [End-to-end testing](end-to-end-testing_en.md)

Suggested path for advanced users:

1. [Project structure](project-structure_en.md)
2. [Modules](modules_en.md)
3. [Common services](common-services_en.md)
4. [API v1 contract](api-v1-contract_en.md)
5. [API implementation plan](api-implementation-plan_en.md)

## Getting started

- [Installation](installation_en.md)
- [Six clean installations and screenshots](installation-lab_en.md)
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
composer e2e
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
- [End-to-end testing](end-to-end-testing_en.md)
- [Activity audit and technical logging](audit-logging_en.md)
- [Personal spaces](personal-workspaces_en.md)
- [Personal appearance](personal-appearance_en.md)

The application enables `aaieduhr/heartphrame-module-api` immediately after
Auth. Routes under `/api/v1` use Bearer keys and intentionally skip PHP session
startup and browser CSRF validation. Auth owns users, groups, key hashes, and
administrator rules; the API module owns versioned JSON contracts and the
conditional API-key screen.

## Guides

- [Common workflows](common-workflows_en.md)
- [Troubleshooting](troubleshooting_en.md)
- [Activity audit and technical logging](audit-logging_en.md)
- [Personal spaces](personal-workspaces_en.md)
- [Personal appearance](personal-appearance_en.md)

## Croatian version

- [Hrvatska dokumentacija](index_hr.md)
