# Simbioza 0.3.0

[Croatian version](release-0.3.0_hr.md)

This is the clean initial release. Install into a new database and runtime
configuration/data directories. Upgrades from 0.2.x and old Simbioza backup
formats are not supported; external Confluence exports remain supported.

## Included

- All 18 public modules use coordinated 0.3.0 dependencies. HeartPhrame Framework
  remains unchanged at its published 0.0.25 dependency.
- Central configuration follows HPH_CONFIG_PATH, ordered overrides and module
  subdirectories. Auth profiles/attributes and generic module settings are
  editable in files; the existing GUI remains available where appropriate.
- Workspace/page ownership organizes filesystem documents, attachments, menus
  and themes. Database-backed HTML remains supported.
- Approved external scripts are loaded only on selected pages. Imports report
  unknown scripts; existing approvals can be matched automatically. Usage and
  page/workspace backup/restore include the script references and definitions.
- Portable backup/restore relocates internal links between sites. Confluence
  import preserves supported dates and reports unresolved references.
- Auth/OIDC validation, archive/path boundaries, batched ACL checks and query
  budgets are covered by the pre-release audit and automated tests.
- Documentation and examples are separate English/Croatian files; bilingual
  code comments use English first, then Croatian.

## Docker

Linux AMD64 and ARM64 images use PHP/Apache and PostgreSQL 18. Runtime
configuration/data share one named volume; the database uses another. Application
code is immutable in the image. Host port defaults to 127.0.0.1:1511 and is
configurable. SimpleSAMLphp is an optional documented read-only code mount,
not an implicit dependency of the standard image.

Release assets include the Compose bundle, checksums, dependency locks, SBOMs,
vulnerability reports and image identity. Fixable high/critical findings block
publication. Reports retain unfixed upstream findings for review; they do not
claim a vulnerability-free operating system. The base uses Debian Trixie and
removes compilation tools/headers. Debian currently classifies the remaining
[libxml2 CVE-2026-6653](https://security-tracker.debian.org/tracker/CVE-2026-6653)
as a minor issue without a stable security update. Only trusted administrators
should supply migration archives; do not import untrusted exports.

## Verification

Local integrated E2E: 80/80 scenarios passed. Application unit checks and
three additional root/Linux-specific checks are also verified in release CI.
Release CI additionally checks PHP 8.2-8.5, fresh module combinations, PostgreSQL,
MySQL, browser/API flows and both native image architectures. Exact CI results and
image digests accompany publication; a prepared source checkout is not proof of
a published artifact.

[Docker instructions](docker_en.md) · [Publication checklist](release-publication-0.3.0_en.md)
