# Accessibility — first local implementation checkpoint

Date: 24 September 2026. Baseline: Simbioza `0.1.89`.

[Croatian version](accessibility-stage-1_hr.md) · [Full implementation plan](accessibility-implementation-plan_en.md)

## Scope and status

This is the first batch of baseline-component repairs, not a completed module or a conformance statement. Stages 0–2 remain open. Only the HFClean, Auth, Calendar, HTML Editor, and Theme source repositories changed. Protected repositories, existing business data, saved user themes, and apps-test were not modified. There are no new migrations, publications, or upgrades to existing installations.

Checks use an isolated local installation with test data. No new application dependencies, fonts, or remote executable resources were added. Future fonts must be bundled in the new module with their licenses. The axe-core tool was used locally for testing only, not embedded in the application.

## Changes and rationale

1. **Base layout:** a skip link is available even when Theme is unavailable or does not render its own link. Its target accepts programmatic focus. Fallback CSS is emitted only when needed; duplicate skip links are avoided.
2. **Theme:** six button families receive readable text colors calculated from their backgrounds. Outline buttons and supporting text use derived readable colors. Saved palettes are not rewritten. Contrast-calculation changes also invalidate cached CSS. The hero becomes a named landmark, tab focus remains visible without shadows, and selected custom transitions respect reduced motion.
3. **Messages:** routine Auth confirmations use a polite status and errors an urgent alert. Auth messages no longer disappear after seven seconds. Editor messages also remain until dismissed by default; callers can still explicitly request automatic hiding. Long sessions with stacked messages and content obstruction remain part of the next review.
4. **Calendar:** event, import, scheduling-search, and resource-profile fields have associated labels. Event, subscription, import, and settings dialogs have names. View selection maintains `aria-pressed`, and the displayed period is announced politely. Event chips calculate contrast independently of Theme, both in the browser and in server-rendered document embeds. Undersized calendar-list controls and the selected day's contrast in the dark mobile view were fixed.
5. **Saved editor HTML:** preserves `h6`, valid `lang` and `dir`, constrained descriptive ARIA attributes, and table-header references. Dangerous URLs, scripts, arbitrary roles, and content hiding remain blocked. This enables accessible authoring; it is not yet a content-completeness check or a guarantee of meaningful image descriptions.
6. **Translations:** the skip-link key was added to application HR/EN catalogues so it does not depend on Theme. The key already exists in all six published language packs; no private local replacement pack was added.

## Editor and Confluence boundaries

Existing imported documents were not bulk-rewritten. The importer already converts supported Expand macros into native `details`/`summary`, and some table macros into tables with headers. Such elements should not receive a static `aria-expanded` attribute that would become stale.

Next assess every editor component generator and its save/reopen path. Add author warnings for missing image descriptions, headings, table headers, unclear links, and charts without a textual alternative. Authors decide whether an image is decorative and provide its description; a filename is not an alternative. Warnings must not prevent legitimate saving through unsupported assumptions.

For Confluence, improve only well-understood macro conversions covered by regression tests. Unsupported macros, external embeds, and attachments need documented limitations or review guidance, not invented accessibility labels. Semantic rules must remain consistent across editing, published pages, and exports.

## View inventory and remaining assessment

There are 80 PHP templates across the maintained application and modules, excluding `vendor` copies. This is not a count of completed assessments; dynamic interfaces can also be generated outside templates.

| Repository or module | Templates | Next coverage |
| --- | ---: | --- |
| HFClean | 4 | Base layout without Theme, installation and updates |
| API | 3 | Forms, validation, tables |
| Audit | 2 | Filters and results |
| Auth | 10 | Repeated settings rows, errors and dynamic fields |
| Backup | 1 | Component selection, progress and confirmations |
| Calendar | 9 | Remaining administration/profile forms, ACL pickers, other event views |
| Comment | 0 | Controls rendered by other modules |
| HTML Editor | 7 | Authoring, dialogs, attachments, dynamic components and generated HTML |
| E-mail | 2 | SMTP settings and messages |
| Menu | 7 | Keyboard operation, dropdowns and repeated forms |
| Notification | 2 | Reading, state labels and focus |
| ORM | 0 | Messages in consuming interfaces |
| Task | 0 | Generated controls and state changes |
| Theme | 2 | Palette editing, arbitrary combinations and layout |
| Confluence Import | 2 | Mapping, reports and supported conversion semantics |
| Simbioza User | 4 | Profile and user fields |
| Workspace | 23 | Tree, pickers, forms, navigation and content |
| Workspace Search | 2 | Filters, pickers and result announcements |

Static review found candidate label-association gaps in repeated Auth/Menu settings rows, parts of calendar administration, workspace-node fields, and dynamic pickers. Each needs rendered confirmation: hidden fields, wrapping labels, and custom pickers require different handling. Do not duplicate identifiers in loops or mask defects with a global script that guesses labels.

## Completed checks

| Check | Result |
| --- | --- |
| Auth, `composer on-commit` | Pass; 79 tests, 503 assertions |
| Calendar, `composer on-commit` | Pass; 68 tests, 599 assertions |
| Theme, `composer on-commit` | Pass; 35 tests, 754 assertions |
| HTML Editor, `composer on-commit` | Pass; 188 tests, 1262 assertions; 3 PHP 8.5 deprecation warnings in unchanged image/ORM code |
| HFClean, `composer on-commit` | Pass; 94 tests, 4228 assertions; 1 existing incomplete application-construction test and 1 skipped ownership test requiring root |
| Bilingual comments, documentation and translation catalogues | No issues reported by application checks |
| Existing Confluence conversion tests | Pass; 43 tests, 275 assertions; importer unchanged |
| Full local E2E, `php scripts/run_e2e.php --local --keep` | Pass; all 76 scenarios, exit code 0, approximately 7.5 minutes; browser, API and performance budgets |

The first E2E run passed 74/76 tests: the search test expected the previous `alert` instead of the new polite `status`, and a public page exceeded its response-size budget by 60 bytes. The semantic assertion and unnecessary fallback-CSS delivery were corrected; test budgets were not increased.

Chromium checks on the isolated installation covered Tab/Enter skip navigation, the named event dialog, focus containment, Escape, and return to its trigger. After target-size and contrast fixes, axe-core 4.10.3 reported no automatic violations of the selected WCAG A/AA rules on the tested authenticated calendar in light/dark modes or its event form. Contrast and ARIA findings requiring manual review remain. This is neither whole-site conformance nor a real screen-reader test.

### Additional open findings

Follow-up on 24 September: overflow and dynamic-configuration loss were fixed in the [second implementation checkpoint](accessibility-stage-2_en.md). The test with Theme unloaded also passed. The following list remains a historical record of the first checkpoint, not a current list of unresolved defects.

- At a 320 CSS-pixel viewport, the tested authenticated page has a document width of 353 pixels. Calendar controls have 24 × 24-pixel targets; the hidden notification-count description in the header also extends beyond the viewport. Confirm and repair the overflow cause separately; the narrow-layout check is not marked as passing.
- After the existing full-backup restore E2E test, the isolated `config/app.php` contains an evaluated static `modules.enabled` list, while CLI updates `data/config/modules.php`. In that test state, disabling Theme did not remove its CSS. This is evidence for further dynamic-configuration restore investigation, not a claim about apps-test. Module lifecycle after restore needs a separate regression test before release. The test module's state was restored.
- The fallback skip link without theme CSS was checked separately by disabling theme presentation in its own settings inside the isolated installation. This does not replace testing with the module entirely absent. The test setting was then restored.

## Exact next step

Continue stages 1–2 with whole Calendar/Auth/Menu/Workspace forms and dynamic pickers, including operation without Theme. Then cover editor generators and author warnings, with known import-conversion checks. Only afterwards build `heartphrame-module-accessibility`, its bundled fonts and personal panel according to the plan. Screen-reader, zoom, all-language, and separate FPM/non-FPM checks are required before release.

Reference criteria: [labels and instructions](https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html), [minimum contrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html), and the [disclosure pattern](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/). Automated checks do not replace manual assessment.
