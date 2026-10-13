# Simbioza 0.3.1 — complete installation guides

[Croatian version](release-0.3.1_hr.md)

This is a documentation release on the 0.3.0 baseline. Only the application
receives tag **0.3.1**. All public modules and the private demo module remain
at **0.3.0**, and HeartPhrame Framework remains unchanged at **0.0.25**.
There are no new database migrations or authentication/configuration changes.
Published 0.3.0 tags are not moved.

## What changed

- Separate, complete guides for Apache mod_php, ordinary PHP-FPM and secured
  PHP-FPM. Each starts from an administrator terminal and includes tools,
  release download, database preparation, permissions, web-server configuration,
  the installer, first sign-in and maintenance. No other installation guide is
  a mandatory prerequisite.
- Fresh secured installations create their FPM/deployment identities. Existing
  identities are discussed only as a reinstallation concern.
- Install standard Simbioza first; activate the optional private demo module
  only after the standard installation works.
- English/Croatian bundled installation pages and the starter workspace are
  generated from the same Markdown sources. The FPM page contains separate
  complete ordinary and secured walkthroughs.
- Offline CI checks detect differences between source documentation and bundled
  pages, parse shell examples without executing them, and check Apache handler
  expressions. Download examples follow the application VERSION.
- Docker documentation and the Compose environment example use the versioned
  `ghcr.io/kmihalj/simbioza:0.3.1` image.

## Installation and existing 0.3.0 sites

For a fresh installation, choose [one complete guide](installation_en.md).
For an existing 0.3.0 site, use its normal GUI/CLI updater. It refreshes the
managed bundled guides without replacing unrelated pages or their permissions.
GitHub `main` does not automatically update pages already stored on a site.
See [maintaining the guides](maintaining_installation_guides_en.md).

Docker deployments replace the application image and run the documented
explicit migration/check procedure; the runtime configuration/data and
PostgreSQL volumes remain in place. Do not remove volumes to update an image.
See [Docker deployment](docker_en.md).

The clean-install boundary remains unchanged: upgrades from 0.2.x and old
Simbioza backup formats are not supported. External Confluence exports remain
supported. Demo/apps-test installation is operator-managed, not part of release
publication.

## Verification and release assets

Release CI covers PHP 8.2–8.5, clean module combinations, SQLite/MySQL/PostgreSQL
browser/API flows and native Linux AMD64/ARM64 images. Docker checks cover
fresh PostgreSQL installation, backups, languages, persistence and immutable
code. The release page records the exact successful CI run and image digest.

Assets include the Compose/documentation bundle, SHA-256 checksums and both
architectures' dependency locks, SBOMs and complete vulnerability reports.
Fixable HIGH/CRITICAL findings block publication. Unfixed upstream findings
remain visible in the reports; a passed gate does not mean zero vulnerabilities.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.1)
