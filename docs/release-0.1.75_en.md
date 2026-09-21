# Simbioza 0.1.75

This release completes the effective optional-module lifecycle in both the GUI
and CLI, consistently on dedicated FPM and non-FPM installations.

From the next request, a disabled module no longer registers its manifest,
routes, services, or menu entries, and the application no longer queries its
tables. Its package, schema, data, and settings remain available for immediate
re-enabling. Complete removal first creates a private NDJSON backup and then
removes the module's own migrations, tables, and Composer package. Adding the
module again supports either a fresh installation or transactional restore.

The same CLI now works in both installation modes. On a verified FPM setup,
package operations automatically use the restricted deploy helper; without
FPM, the installation owner performs them directly. Routine maintenance needs
no `sudo`. A failed package operation restores the previous state, while a
per-operation Composer cache prevents ownership conflicts between FPM, deploy,
and CLI users.

Persistent module state now lives in `data/config/modules.php`. The first
update of a legacy installation automatically extracts the existing selection
from a static `config/app.php` or old `config/modules.php` without changing
enabled modules. The updater then runs migrations only for packages that are
actually installed. It also recognizes migration wrappers whose timestamp was
assigned by the installer and restores the original development or tagged
Composer constraint when a package is added again.

Regression coverage includes effective disable and re-enable, safe package
remove and restore, legacy configuration, FPM and non-FPM CLI operation,
minimal installations without optional tables, and the complete browser, API,
and performance E2E suite.
