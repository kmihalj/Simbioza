# Accessibility — third local implementation checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-3_hr.md).

This checkpoint continues the baseline repairs in stages 1–2. It does not mark architectural stage 3 or the personal-adjustment module as complete. All work remains local; no release, push, remote CI run or apps-test change was performed.

## Dependency consistency

All 17 first-party module manifests now require the latest compatible published internal tags as their minimums, including development integrations. Every module uses `minimum-stability: stable`. Its own local dependencies were resolved again and checked; updating Simbioza alone does not refresh an individual module's development installation.

Workspace now requires and locally uses ORM 0.1.6. The previously observed PHP 8.5 deprecation disappears with the current tagged ORM, without patching an installed dependency. The Framework repository remains unchanged and read-only; Composer consumes the maintainer's official 0.0.25 tag. Historical 1.x module tags are not mistaken for successors of the current 0.1.x release line.

PHPStan, Rector and compatible test/style tools were refreshed. PHPUnit 10/11 and PHP_CodeSniffer 3 remain intentional compatibility boundaries; PHPUnit 13 and PHP_CodeSniffer 4 require separate migration work. The [dependency policy](module-dependencies_en.md) now makes per-module resolution, platform checks and the full quality suite mandatory before release. A caret constraint permits compatible future releases; it does not automatically refresh an existing lock file or installed package.

Strict checks also exposed small development issues: Auth test namespaces and explicit SimpleSAML stub autoloading, Workspace optional-service fixture autoloading, and the repeated side-effecting User delivery operation under newer static analysis. These were corrected without removing assertions or suppressing the checks. Pre-existing development package copies/symlinks were preserved under the affected modules' ignored `build` directories before installing tagged dependencies.

## Application construction and Linux ownership test

The previously incomplete construction test now instantiates the real application with isolated configuration, real application service factories and routes, and no business modules or database. It verifies the relevant services and configuration and checks that construction does not create application files.

The ownership test was actually executed as root inside a temporary Ubuntu 24.04 Linux VM: **1 test, 12 assertions, no skips**. Only the permission class, its test, the dedicated runner and an isolated PHPUnit installation were copied. The VM had no host-directory mount and did not load private application configuration.

The new runner refuses non-Linux/non-root execution and fails on skipped or empty tests. Dedicated GitHub/GitLab CI steps run it separately from ordinary tests. Their configuration was checked locally; remote CI has not run because nothing was pushed.

Colima and its newly installed Lima dependency were uninstalled afterwards. The temporary VM, image, caches, logs and newly created Colima state were removed; the existing Docker configuration was preserved. apps-test was not used for this test.

## Editor authoring and saved HTML

- Added image alternative-text editing and an explicit decorative-image action to the Media toolbar and image context menu. Changes apply to that occurrence, not every attachment use.
- Decorative images save native `alt=""` plus a narrowly validated author-decision marker. Conflicting labels, descriptions and tooltips are removed only when the author explicitly chooses that action. Editing the description clears the marker.
- A blank description alone is still reported. Existing/imported images are not automatically declared decorative, and no descriptions are invented.
- Fragment checks no longer demand a second H1 when the page layout may provide one. Empty/skipped/duplicate headings still have checks; the final page structure still needs review.
- Image links and explicit ARIA names are recognised by the limited link check. Invalid, duplicate, self-referencing and cross-table header references are reported; nested table headers no longer mask missing outer-table headers.
- Removed deprecated PHP 8.5 `imagedestroy()` calls in the Editor's image-variant service; releasing `GdImage` object references preserves the existing cleanup behavior.

The browser regression creates the content, runs the check, uses both image actions, fixes a header association, saves and publishes, then verifies the persisted/rendered semantics. No Confluence conversion or existing-document bulk rewrite was introduced.

Separate English (`docs/accessibility_en.md`) and Croatian instructions are included in the Editor repository and linked from its documentation index. Four new source keys are translated in all six maintained packs. The local language catalog revision is `2026.09.24.1`, with 4,373 keys per pack; hashes and SVG flags validate. These package changes have not yet been published.

## Verification

| Check | Result |
| --- | --- |
| All 17 modules, complete `composer on-commit` | Passed; 901 tests, 6,834 assertions in total |
| Module platform requirements and development-branch audit | Passed in all 17; no installed development versions; 65 internal constraints match the verified installed tags |
| HFClean, complete `composer on-commit` | Passed; 95 tests, 4,253 assertions; no incomplete tests; the Linux-only ownership test is skipped on macOS and separately passed in Linux |
| Isolated Linux root ownership test | Passed; 1 test, 12 assertions |
| Focused Editor browser regression | Passed, including save/publish and rendered HTML |
| Full local E2E | Passed; 76/76, exit code 0, 7.5 minutes; isolated SQLite installation `e2e-all-929594dc` retained |
| Language catalogs | All six valid; 4,373 keys each |

An additional Playwright CLI check selected an image, opened Media with Enter, reached the new decorative-image action with arrow keys and activated it with Enter. The DOM then contained empty `alt` and the explicit marker. This narrow keyboard check is not a complete keyboard audit. Image fixtures use an unavailable local image path; their checks cover semantics, not visual image loading. The temporary browser session and HTTP server were stopped afterwards.

An initial full E2E run finished 75/76 because the new test looked for findings in the retired inline panel instead of the actual check notification. The locator and repeat-check action were corrected to follow the real UI; no application assertion was removed. An initial parallel Task quality run also hit a shared Rector temporary-cache collision; its complete sequential rerun passed.

## Remaining work and exact next step

Continue the existing view inventory, starting with remaining settings/error states and dynamic pickers. For Editor, extend keyboard and rendered-semantic coverage of table, tab, accordion and chart generators; check the complete page heading structure with and without Theme. Safe improvements to converted Confluence macros require their own regression fixtures, not indiscriminate ARIA injection.

Manual screen-reader verification and the broader language/theme/zoom matrix remain open. These changes are authoring assistance and regression fixes, not a WCAG conformance declaration. No migration, external end-user asset, new font or personal-preference panel was added in this checkpoint. The reusable accessibility module follows completion of these foundations.
