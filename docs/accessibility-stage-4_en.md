# Accessibility — fourth local implementation checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-4_hr.md).

This continues the foundation repairs from the [third checkpoint](accessibility-stage-3_en.md); it does not complete architectural stage 4. The personal-preference module has not been built. There is no release, commit, push, remote CI run or change to apps-test or existing installations. Checks use an isolated local installation with synthetic data.

## Repairs and rationale

- **API key owner picker:** the active result is associated with the input through `aria-activedescendant`; options have unique IDs and selection state while DOM focus remains in the input. Arrow keys select a result, Enter commits it, and Escape closes and cancels the request. Late responses no longer reopen a dismissed list. Pagination also works by keyboard; focus returns to the input before the pagination button is hidden. Loading, result counts and errors use a separate polite status region outside the option list. Required-selection validation marks the invalid input.
- **Audit and Workspace Search:** labels are associated with custom pickers and their accessible names include the current selection. Loading, empty results and errors have appropriate announcements. Selection returns focus to the trigger. Audit pagination has a name and text labels for previous/next links. A separate check without Theme found a 4.35:1 contrast ratio on the success badge; only its fallback text color was darkened, and the repeated check passes.
- **Complete-page structure:** 13 partial views no longer add a nested `main`; the host layout owns the main landmark. This covers API, Audit, User, Confluence Import, Calendar meeting views, Editor and Workspace. Sidebar titles are H2 in both templates and fallback rendering rather than another main page heading. Visual classes are unchanged. Another application embedding these views must provide its own main landmark.
- **Confluence static-table HTML macros:** captions, header scopes and valid `headers` associations are preserved. IDs are remapped separately for each macro, with independent association scopes for nested tables. Invalid, ambiguous, self-referencing and cross-table references are not copied, but visible content is retained. No descriptions are invented or arbitrary ARIA labels added. The original element and URL allowlists remain in force. Existing imported documents are not bulk-rewritten.
- **Confluence archive picker:** one label instead of two, associated selected-file/help descriptions, and visible focus on the localized native picker's label. No external widget is used.
- **Editor saved and published content:** regression coverage now exercises tab/panel associations, selected state, arrow keys, Home/End, Tab into the active panel and Enter on native summaries. Existing image, table and chart checks remain. Test images now use an available local asset instead of the previous missing path. These keyboard interactions did not require changes to the existing generator.

Changed source repositories: HFClean and nine modules—API, Audit, Calendar, HTML Editor, Menu, Simbioza User, Workspace, Workspace Search and Confluence Import. Installed package copies were not hand-patched. `heartphrame-framework` remains clean and read-only; other prohibited repositories were not changed.

## Verification

| Local check | Result |
| --- | --- |
| API, full `composer on-commit` | 39 tests, 200 assertions |
| Audit, full `composer on-commit` | 18 tests, 99 assertions |
| Calendar, full `composer on-commit` | 69 tests, 633 assertions |
| HTML Editor, full `composer on-commit` | 190 tests, 1,281 assertions |
| Menu, full `composer on-commit` | 54 tests, 349 assertions |
| Simbioza User, full `composer on-commit` | 20 tests, 195 assertions |
| Workspace, full `composer on-commit` | 109 tests, 876 assertions |
| Workspace Search, full `composer on-commit` | 29 tests, 186 assertions |
| Confluence Import, full `composer on-commit` | 99 tests, 622 assertions |
| HFClean, full `composer on-commit` | 95 tests, 4,253 assertions; one Linux root test skipped on macOS |
| Six language packs | 4,373 keys each; valid SHA-256 digests and SVG flags |

The nine changed modules total **627 tests and 4,441 assertions**, with their configured style and static-analysis checks passing. The Linux root test was not repeated; its separate actual execution and temporary-environment removal are documented in checkpoint three. There is no new migration.

**Final full E2E: 77/77, exit code 0, 7.6 minutes.** `php scripts/run_e2e.php --local --keep` checked browser, API, authorization, backup, import, publication and performance budgets on isolated SQLite. Installation `e2e-all-7e1797f6` was retained. The expanded Confluence scenario confirms that four unique header IDs survive actual import, persistence and rendering; existing private-attachment and ACL checks still pass.

The first full E2E finished at 76/77: the expanded Confluence scenario exposed how removing an earlier macro changes the remaining siblings' XPath, allowing two tables to receive identical IDs. Original positions are now captured before conversion; collapsible-block identities also use this stable position. Unit fixtures now include an additional sibling macro and separated collapsible blocks. The unique-ID assertion was not removed. An attempted focused rerun on a reused installation stopped at the initial workspace-name expectation because that Confluence source had already been imported; the final rerun uses a new isolated installation.

After repairs, automated checks of the rendered pages using local axe-core 4.10.3 report no confirmed violations on the checked API, Audit, Workspace Search and Confluence initial screens in light/dark themes and without Theme. Initial technical-log, personal-workspace, e-mail and notification views also passed a narrow check. This does not cover every possible state or authored content.

Outstanding manual-review results are not ignored: the tool cannot reliably calculate contrast over gradients. The naming warning on the closed navigation container was checked in its open mobile state: Bootstrap supplies `role="dialog"` and `aria-modal="true"`, focus enters, and Escape closes it and returns focus; that warning is absent when open. The first check sent Escape before the transition and focus activation completed; the repeated check waits for an actually active dialog. This is not a screen-reader test.

## Documentation and languages

Editor documentation now describes keyboard use and the main-landmark contract; Confluence Import documentation describes table-header association preservation. English and Croatian remain in separate files, and new code comments are bilingual HR/EN. API and Audit reuse existing translated keys, so the six language packs need no new revision for this checkpoint.

API documentation explains keyboard owner selection. Automated documentation-pair/link and UI-key extraction checks pass. The temporary browser, auxiliary HTTP server and downloaded axe-core copy were removed after inspection; the checker was not added as an application dependency. Isolated E2E installations are retained for reviewing results.

References: [W3C combobox pattern](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/), [form labels](https://www.w3.org/WAI/tutorials/forms/labels/) and [tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/).

## Remaining work and next step

1. Complete remaining inventory states: Setup/Backup errors and confirmations, administrative dialogs and dynamic Confluence user/group mappings, and focus after notification/comment/task changes. Passing the initial screen is insufficient.
2. Finish checks at 320 CSS pixels, real 200/400% browser zoom, text spacing and gradient contrast, including longer translations. Manual VoiceOver/Safari and NVDA checks and user testing remain open.
3. Then finalize the integration contract and build the standalone `heartphrame-module-accessibility`, local licensed fonts, multilingual personal panel and unified enable/disable lifecycle according to the plan. No claim of full WCAG conformance is made.

This work does not upgrade existing HFC or FPMSimbioza installations. Combined application/module source is checked through isolated `--local` integration; existing installations need a later coordinated installation of released versions. apps-test remains within the user's manual-upgrade boundary.
