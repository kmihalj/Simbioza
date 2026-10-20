# Simbioza 0.3.5 — private configuration directory protection

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/release-0.3.5_hr.md)

> Historical release note — use the [current installation index](installation_en.md) for a new installation. The original 0.3.5 tag and images remain unchanged.

This application patch ships updater **1.0.8**. Framework stays **v0.0.25**,
API **0.3.1**, other public modules **0.3.0**. No existing tag is moved and no new database migration is added.

## Changes

- The updater preserves parent directories of private configuration files and
  declared configuration layers when those directories do not exist in the
  synchronization source. This applies to additional modules and nested paths,
  not only built-in module names.
- Both update and pre-migration rollback use the same directory protection.
  A private directory such as `config/custom-module/` no longer causes the misleading
  rsync warning `not empty, cannot delete` while its files remain protected.
- Directories supplied by the release still follow normal per-file rules:
  release files and `.dist` examples can be updated or removed. Real rsync
  failures and diagnostics are not suppressed or ignored.
- Regression coverage includes CLI and GUI updates, restrictive secured-FPM and
  private runtime masks, rollback, nested external configuration layers, private
  file ownership/permissions, and a deliberately failing rsync command.
- Installation commands, Docker examples and bundled EN/HR guides use **0.3.5**.
  Single-space Confluence import, Auth, module code and Framework are unchanged.

## Installation and existing sites

Use the [complete installation index](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/installation_en.md),
[secured FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/installation_fpm_secured_en.md)
or [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/docker_en.md).

Classic installations use the existing CLI or GUI update procedure. The updater
checks its own latest version first, so directory protection is active before
application files are synchronized. There is no need to delete configuration,
locks or backups, reinstall the application, or widen FPM write access.

An update that already completed with the directory warning does not need to be
repeated solely because of that warning. This release prevents it on subsequent
updates; it does not imply that the protected configuration was lost.

For immutable Docker deployments, replace the application image with
`ghcr.io/kmihalj/simbioza:0.3.5`; keep the existing runtime/configuration and
PostgreSQL volumes. Linux AMD64/ARM64 images retain the existing tests and
security gates. Publishing this release does not upgrade demo or apps-test.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.5)
