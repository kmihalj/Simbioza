# Simbioza 0.1.82

This corrective release completes post-update permission hardening for GUI and
CLI updates on dedicated FPM installations.

When optional modules are installed, the updater rebuilds `composer.json`
before resolving packages. Under the dedicated deploy process's restrictive
`umask 0007`, that file could remain unreadable by FPM after a successful
update, causing HTTP 500 when opening Settings.

The updater now explicitly leaves the Composer manifest readable by the web
process without changing its owner, group, or write permissions. New-release
finalization applies the same rule when an older updater started the update,
so a direct GUI update from 0.1.81 is safe without manually replacing the
updater first.

Regression coverage now verifies both the restrictive FPM `umask` and the
upgrade path from an older updater.

This release contains no migrations or business-data changes.
