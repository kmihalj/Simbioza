# Simbioza 0.1.77

> Historical release note — not current installation or upgrade instructions. For the clean 0.3.0 baseline, use [installation](installation_en.md) and [current configuration](configuration_en.md). Older-version upgrade instructions below are retained only as history.

This corrective release makes application updates safe for the actual modular
composition of an existing installation.

The updater now recognises optional modules from explicit Composer
requirements, locked packages, and persistent administrator state. It
therefore preserves enabled modules as well as installed modules that are only
disabled. An older installation whose manifest was already reduced to the
required core is repaired without deleting data or resetting its module
selection.

Bundled-guide refreshes on legacy installations can safely derive the base
path from the public application URL. When the optional Backup module is not
installed, the application update no longer fails: the theme can still be
updated, while guide-package replacement is cleanly deferred until the Backup
service is available.

The procedure was verified by the full static and unit checks and the clean
installation matrix for every individual module and the complete selection.
HFC was restored to all 17 enabled modules, 34 executed migrations, and zero
pending migrations.

