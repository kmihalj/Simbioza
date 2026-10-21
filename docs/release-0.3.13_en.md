# Simbioza 0.3.13 — refreshed public installation guides

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/release-0.3.13_hr.md)

> Historical release note. Use the [current installation guide](installation_en.md)
> and [Simbioza 0.3.14](release-0.3.14_en.md) for current instructions.

This documentation release separates public Simbioza instructions from private
deployment procedures. Application behavior, database schema and module versions
are unchanged from 0.3.12. HTML Editor remains **0.3.2**, Backup, Auth, API, Email
and Theme **0.3.1**, Menu **0.3.2**, other public modules **0.3.0**, Framework
**v0.0.25** and updater **1.0.8**. Existing tags are not moved.

## Changes

- Public installation guides and release-note sources no longer refer readers
  to private deployment instructions or repositories that they cannot access.
  Public links for trying Simbioza remain available.
- The English and Croatian managed guide archives are rebuilt from the same
  maintained Markdown sources. Normal updates refresh these guides without
  changing unrelated pages, permissions, page identities or private settings.
- Installation commands and Docker examples consistently use **0.3.13**.
  Linux AMD64 and ARM64 images contain the same refreshed guide packages.
- Regression tests check public documentation and every bundled guide payload
  for accidental private operational instructions or inaccessible private links.
- Existing GitHub release descriptions are cleaned separately. Historical tags,
  source archives and published assets remain immutable.

## Updating

On a configured secured-FPM installation, open **Settings → Setup and modules**
and run the **0.3.13** application update. No reinstallation, configuration change
or new migration is required. The portable-link corrections from 0.3.12 remain
included. Review the refreshed User guides after the update.

Docker operators replace the application image according to the Docker guide,
preserving both runtime and database volumes. Do not remove volumes for an update.

See [installation](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/installation_en.md),
[configuration and backup](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/configuration_en.md#backup-and-permissions)
and [Docker deployment](https://github.com/kmihalj/Simbioza/blob/0.3.13/docs/docker_en.md).
