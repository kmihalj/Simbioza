# Simbioza documentation (EN)

- [Accessibility and Survey performance in 0.3.23](release-0.3.23_en.md)
- [Full application navigation in 0.3.21](release-0.3.21_en.md)
- [Modular installations in 0.3.20](release-0.3.20_en.md)
- [Survey settings and application URLs in 0.3.19](release-0.3.19_en.md)

- [Surveys and the shared HTML Editor in 0.3.18](release-0.3.18_en.md)

This is the documentation for the Simbioza collaborative knowledge application and its
enabled HeartPhrame modules. The Framework is consumed from a tagged upstream release; it
is not developed in this repository.

Brand slogan: **Knowledge that lives together.**

- [Choose a website, Calendar, Wiki, Survey system or full Simbioza](modularity_en.md)
- [User guides with and without a Wiki](bundled-manual_en.md)

- [Secured-FPM language/update ownership fixes in 0.3.17](release-0.3.17_en.md)
- [Log pagination, worker logs and rotation in 0.3.16](release-0.3.16_en.md)
- [Log ownership, rotation and retention](logging_en.md)
- [Automatic and custom button text colors in 0.3.15](release-0.3.15_en.md)
- [Clear Setup update summaries and Email worker limits in 0.3.14](release-0.3.14_en.md)
- [Refreshed public installation guides in 0.3.13](release-0.3.13_en.md)
- [Theme/configuration preservation and selected colors in 0.3.10](release-0.3.10_en.md)
- [Portable guide links and same-site restore in 0.3.12](release-0.3.12_en.md)
- [Bundled language revisions in 0.3.7](release-0.3.7_en.md)
- [Verified secured-FPM installation in 0.3.6](release-0.3.6_en.md)
- [Private configuration directory protection in 0.3.5](release-0.3.5_en.md)
- [Docker security patch release 0.3.3](release-0.3.3_en.md)
- [Secured FPM runtime permissions in 0.3.4](release-0.3.4_en.md)
- [Secured-FPM fix release 0.3.2](release-0.3.2_en.md)
- [Documentation release 0.3.1](release-0.3.1_en.md)
- [Initial release 0.3.0 and verification checklist](initial-release-0.3.0_en.md)
- [Expanded pre-release audit and continuation](pre-release-audit-0.3.0_en.md)
- [Current configuration and module-owned files](configuration_en.md)
- [Documentation, examples and comment-language checks](documentation-quality_en.md)
- [Docker deployment, volumes, image updates and optional SAML](docker_en.md)
- [Container readiness and verification boundaries](container-readiness_en.md)
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

- [Installation overview](installation_en.md)
- [Ordinary PHP-FPM installation](installation_fpm_en.md)
- [Secured PHP-FPM installation](installation_fpm_secured_en.md)
- [Maintaining installation guides](maintaining_installation_guides_en.md)
- [Apache mod_php installation](installation_mod_php_en.md)
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
- [Unified configuration plan and continuation point](configuration-migration-plan_en.md)
- [Workspace and page data storage](workspace-data_en.md)
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

### Historical release notes

These notes describe older releases, not the fresh 0.3.0 installation or upgrade procedure.

- [0.2.11 release record](release-0.2.11_en.md)
