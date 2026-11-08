# Simbioza 0.3.32 — native controls for content tables

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.46](release-0.3.46_en.md).

[Croatian version](release-0.3.32_hr.md)

HTML Editor **0.3.13** adds optional column sorting, pagination and a choice of
**10, 25, 50, 100 or All** rows per page. Click a cell, open **Table → Table
settings**, enable **Sorting and pagination**, then save and publish. Each table
is configured separately; application lists and settings tables are unaffected.

Header arrows indicate sorting direction. Controls support keyboard operation,
localized accessible labels and the active light or dark theme. Editing preserves
all rows and their original order. Complex tables with merged cells keep the
static view. HTML exports include the native code locally; printing and pages
without JavaScript show every row.

The former bundled DataTables example is retired. New installs no longer offer
it. Upgrades remove only its exact original definition from writable editor
configuration; custom scripts, approvals and translated instructions remain.
Private Demo support **0.3.14** also stops recreating or approving that example.
Existing page content is preserved; authors can enable the native controls for
individual tables.

Framework remains **0.0.25** and Theme **0.3.8**. There is no new database migration
or configuration reset. DEMO and apps-test remain operator-managed updates.
See [installation](installation_en.md) and [module dependencies](module-dependencies_en.md).
