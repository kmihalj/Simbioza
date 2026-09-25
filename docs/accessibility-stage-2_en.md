# Accessibility — second local implementation checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-2_hr.md).

This checkpoint continues stages 1–2. It is not a completed accessibility module or a whole-application conformance claim.

## Repairs

### Dynamic configuration after backup restoration

Structured configuration restoration replaced dynamic `config/app.php` with evaluated values. CLI subsequently updated module state, but application bootstrap no longer read that file.

The Backup provider now supports locally defined destinations and key mappings. Simbioza restores installation settings into `config/installation.php` and enabled modules into `data/config/modules.php`, leaving `config/app.php` unchanged. Portable session options are read through an allowlist. Languages are still validated against installed packages, and the fallback locale follows the application's default locale.

Archives cannot select paths or supply executable code. Nonselected local values and group rollback are preserved. Record format remains compatible with older backups. A regression also checks that PHP-looking text remains inert data.

This prevents future configuration flattening. It does not automatically repair an installation whose `app.php` was already overwritten by an earlier restore; that installation needs separate inspection and recovery from a trusted release. Nothing was changed on apps-test.

A repeated full test exposed a separate intermittent issue: CLI correctly saved the disabled Theme state, but the web process still read the old OPcache entry. A local HTTP experiment and a warmed-cache regression test reproduced the cause. Bootstrap now invalidates only mutable installation, language, and module data files before reading them; the module-state store does the same for its own reads. Application code caching remains enabled. The regression passes even with automatic timestamp validation disabled; no artificial delay or repeated request hides the problem.

### Narrow authenticated layout

The hidden notification-count description extended beyond its control. At a 320 CSS-pixel viewport it increased document width to 353 pixels. The control now contains that description and truncates only the username, keeping the notification badge visible.

In tested Chromium, document width after repair is 305 pixels, equal to available width with a classic scrollbar. No blanket horizontal-overflow hiding was added. Assistive technology still receives the complete username.

A separate check with Theme unloaded exposed 2.28:1 contrast on adjacent-month dates. Additional opacity on the entire button caused the failure. Both mobile CSS rules were corrected; rerunning the automated calendar check at 320 pixels no longer reports the violation.

### Forms and dynamic controls

- Calendar: associated 17 additional visible labels in main and administrative forms; named read/write permissions, group selection, row removal and administration search.
- Calendar and Workspace: remote pickers announce loading, empty results and errors; workspace/page search has an explicit accessible name.
- Menu: named movement/indentation arrows and enabled, label, destination, URL, query and removal controls in regular and context menus. Existing Croatian keys already translated in all six language packs are reused and added to the module's standalone catalogue where needed.
- Workspace: associated slug, node type, order, document, internal route and target URL labels. Identifiers retain the node ID; automatically created document information uses a named output element.

Submitted data shapes, ACL logic, document contents and Confluence conversion were not changed. No database migrations or remotely hosted end-user assets were introduced.

## Verification

| Check | Result |
| --- | --- |
| Backup, full local `composer on-commit` | Passed; 45 tests, 146 assertions |
| Menu, full local `composer on-commit` | Passed; 53 tests, 344 assertions |
| Calendar, full local `composer on-commit` | Passed; 69 tests, 633 assertions |
| Auth, full local `composer on-commit` | Passed; 79 tests, 503 assertions |
| Theme, full local `composer on-commit` | Passed; 35 tests, 754 assertions |
| Workspace, full local `composer on-commit` | Passed; 109 tests, 876 assertions; existing PHP 8.5 deprecation in unchanged ORM code |
| HFClean, full local `composer on-commit` | Passed; 95 tests, 4241 assertions; 1 existing ownership test skipped without root and 1 existing incomplete application-construction test |
| Comments, documentation and translation catalogues | No reported issues |
| Full local E2E | Final run after all fixes: 76/76, exit code 0, approximately 7.5 minutes. An earlier repeat finished 75/76 and exposed the OPcache issue above. |
| CLI → HTTP with OPcache timestamp validation disabled | Passed 20 consecutive changes (10 enable/disable cycles); the first subsequent HTTP request always showed the correct state |

The extended E2E test verifies that `app.php` source is unchanged after a real full-site restore. It disables/enables Theme through the GUI, checks removal/restoration of its header and settings entry, then repeats the state change through CLI. With Theme unloaded, it checks Tab/Enter skip navigation and main-content focus. The mobile test checks document width, notification badge bounds, and keyboard opening/closing of the account menu in light and dark modes.

Playwright and axe-core 4.10.3 checked calendar administration, the open calendar dialog, added user/group permission rows in dark mode, and the regular menu editor. Those states have no automatic violations of the selected WCAG A/AA rules. This does not dismiss manual-review findings or certify every view or language.

Another isolated post-restore installation was checked with Theme genuinely disabled: its administrator route returns 404, its header is absent, and calendar/calendar administration pass the same automated checks after the date-contrast repair. Test module state is restored afterwards.

CLI disabling of Croatian after restore was also tested: the next request falls back to English, and after re-enabling Croatian it can be selected again with document `lang="hr"`. Both languages and Theme were enabled again at the end.

The final E2E run used the retained isolated installation `e2e-all-0e29929c`. A separate local HTTP server on that fixture used `opcache.validate_timestamps=0` and `opcache.file_update_protection=0`: ten CLI Theme cycles were checked immediately on the real login page, without delays or retry refreshes. Theme was re-enabled afterwards and the temporary server stopped. This verifies the cache problem, but does not replace the still-pending matrix of actual FPM/non-FPM upgrades.

Local narrow-layout screenshot: `output/playwright/accessibility-stage-2-narrow-dark.png`.

## Remaining work and next step

1. Complete semantic and keyboard checks of remaining Auth forms, context menus and every remote-picker state. Check that a selected control retains its purpose in its accessible name rather than only exposing the selected value.
2. Editor: extend the existing content checker in `heartphrame-module-editor-html/views/editor/index.php`, not a second parallel implementation. `checkContentHeadings`, `checkContentImages`, `checkContentTables` and link checks already exist. Currently, empty `alt` is always reported as an error, heading checks expect an H1 inside content without layout context, and tables only need any `th` to pass. Distinguish intentionally decorative images, account for a title already rendered by the application, and check table-header associations. Keep generators and messages aligned with the sanitizer, without invented descriptions or blocking valid content. Add focused checks of known Confluence conversions without bulk-rewriting imported documents.
3. Then build the standalone personal-adjustment module with local fonts and licenses, a multilingual panel and one administrator switch.
4. Before release: separate FPM/non-FPM verification, actual screen-reader testing, zoom, forced colors, languages and all routes. Backup and Simbioza require coordinated versions; changing the application provider definition alone while retaining an old module is insufficient.

The framework and protected repositories were not changed. No commits, pushes, releases or existing-installation upgrades were performed. Development and integration tests use isolated local data.

The installed Backup copy in `HFClean/vendor` does not yet support the new mapping and was not manually patched. Coordinated development sources were verified through the `--local` integration installation. Existing HFC or FPMSimbioza deployments need coordinated package installation first, not an application-configuration-only replacement.

Reference criteria: [reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) and [labeling controls](https://www.w3.org/WAI/tutorials/forms/labels/).
