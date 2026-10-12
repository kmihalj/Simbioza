# Simbioza 0.1.81

> Historical release note — not current installation or upgrade instructions. For the clean 0.3.0 baseline, use [installation](installation_en.md) and [current configuration](configuration_en.md). Older-version upgrade instructions below are retained only as history.

This corrective release introduces a persistent GUI update progress screen.

After an update starts, Setup immediately displays a dedicated screen with the
current stage, percentage, and elapsed time. The screen continues working
during the maintenance phase while application code and Composer modules are
replaced, so a short `503` response no longer looks like a failed update.

The background updater atomically publishes a restricted status for every
important stage: release download, backup, code synchronization, Composer
modules, checks, migrations, cache, and any rollback. Technical output remains
in the private administrator log.

CLI operation remains unchanged and does not depend on the GUI reporter.

This release contains no migrations or business-data changes.
