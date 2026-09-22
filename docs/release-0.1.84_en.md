# Simbioza 0.1.84

This corrective release fixes the GUI update progress flow on dedicated FPM
installations.

The real update could finish successfully while the browser remained on the
progress overlay. Two independent FPM details caused that behavior:

- the maintenance front controller resolved `data/` from the public web path
  instead of the configured `HPH_APP_PATH` application root;
- files newly introduced by a release inherited the deploy helper's restrictive
  `umask 0007` and could therefore remain unreadable by the isolated FPM user.

The maintenance status endpoint now resolves the real application root before
loading Composer. The updater also makes release files readable and directories
traversable by FPM without widening write permissions or changing any private
configuration, data, or administrator-managed resource settings.

For an update started by version 0.1.83, the new release finalization performs
the same permission repair before maintenance mode ends. This makes the direct
GUI upgrade path from 0.1.83 self-healing even though the older updater starts
the process.

Regression coverage reproduces the dedicated helper's restrictive umask and
verifies that private settings remain private. This release contains no
migrations or business-data changes.
