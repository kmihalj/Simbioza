# Simbioza 0.2.12

## Required updater bridge

Every installation older than 0.2.12 **must first upgrade explicitly to 0.2.12**:

```bash
php update.php --tag=0.2.12
```

The old updater installs this bridge in the normal way. Only subsequent
updates use the new updater-first protocol. Direct upgrades from an older
installation to a later release are unsupported: do not skip this step.
Keep the usual database, settings, and uploaded-file backup before upgrading.

## Updater before application

Both the CLI and the GUI background job first fetch the newest stable release
and validate its standalone updater version, handoff protocol, and PHP syntax.
A newer updater is backed up with private permissions and activated atomically.
A fresh PHP process then runs the remaining update; merely overwriting an
already-loaded PHP file is no longer considered a self-update.

The parent keeps the update lock and relays progress to the existing GUI job.
The selected application tag, language, deployment identity, and actual exit
code survive the handoff. Application code, Composer packages, configuration,
and migrations are changed only after this step. No application autoloader
or database access is needed to refresh the updater.

Even an explicitly selected application tag uses the updater from the latest
stable release. Source synchronization and application rollback never replace
it with an older copy. Requests targeting pre-bridge application versions are
rejected. An invalid updater stops the update before application changes.
`--check` remains read-only; `--updater-info` reports updater metadata locally.

Private updater backups live in `data/backups/updater/`. Temporary work stays
inside the installation's `data/` and is cleaned after completion. File owner,
group, and access mode are preserved; the web process gains no new code-write
privileges. FPM uses the existing restricted deployment helper, while mod_php
continues to use the code owner's CLI. Host-managed demos must retain their
controlled baseline/reset procedure when updating to this bridge.

No authentication/configuration relocation or new database migration is part
of this release. Future changes to `update.php` must increment its independent
`UPDATER_VERSION`; handoff protocol 1 must remain backward compatible.
