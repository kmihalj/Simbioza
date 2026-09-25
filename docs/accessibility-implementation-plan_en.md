# Accessibility and heartphrame-module-accessibility implementation plan

Plan date: 23 September 2026. Updated: 25 September 2026. Status: the local module and integration are implemented; the final accessibility assessment remains open.

[Croatian version](accessibility-implementation-plan_hr.md)

This document is the continuation point for future development sessions. It is based on a quick review of HFC and selected source code with local Simbioza tag `0.1.89`. Confirmed findings are recorded below; the additional local report is `output/playwright/audit_pristupacnosti_hr.md`. The initial review did not cover every authenticated workflow and is not a conformance assessment.

## 1. Objective and fixed rules

- Deliver an accessible baseline application and an independent, reusable personal-adjustment module for HeartPhrame applications. Use WCAG 2.2 AA as the development target, supplemented by real usability checks; do not claim full conformance before completing assessment.
- Baseline semantics, keyboard operation, focus, readability, and contrast must work without the new module and without the theme module. The module does not replace repairs to existing code.
- Repositories `heartphrame-framework`, `heartphrame`, and `heartphrame-module-demo` remain read-only. The demo template may be studied and used to create the new module, but its source repository must not change. Do not patch the framework through `vendor` either.
- Change only application code and modules we maintain. If a defect requires a framework change, document the limitation and prepare a proposal for its maintainer; do not bypass this boundary.
- Administrators have only a module enable/disable control. Visitors choose personal adjustments in the panel. Do not introduce two independent switches with contradictory states.
- Translate every visible string, accessible label, and dynamic announcement. Application source strings remain Croatian.
- Display adjustments must not rewrite documents, custom themes, existing menu order, private configuration, or business data.
- HFC remains the non-FPM test installation; FPMSimbioza remains the FPM installation. Do not update apps-test or disable its modules without fresh explicit user authorization.
- On 24 September 2026 the user authorized staged local development; on 25 September they explicitly requested a release. Updating apps-test remains a separate user action. Introduce migrations only when needed, with a backup and separate verification.
- New editor-authored content must carry accessible semantics in its saved HTML, not only after script-based intervention. Authors supply meaningful descriptions; the application provides structure and warnings.
- Preserve the existing Confluence importer. Improve only known macro conversions into native Simbioza components, with regression tests. Do not rewrite arbitrary imported content, invent descriptions, or add redundant ARIA to native elements.

## 2. Sequence and completion gates

The stage 0–2 foundation repairs, standalone module, personal panel, and Simbioza integration have local implementations and tests. Manual assessment of every workflow and authored document, parts of lifecycle coverage, and publication remain open. The first checkpoint is recorded in the [first-stage report](accessibility-stage-1_en.md). An inventory of 80 templates does not mean that every template has been fully assessed or repaired.

The [second implementation checkpoint](accessibility-stage-2_en.md) closes overflow and configuration-restoration findings, improves Calendar/Menu/Workspace forms, and fixes stale web state after CLI changes. Its final E2E passes 76/76; an additional 20 consecutive CLI → HTTP changes pass with OPcache timestamp validation disabled. The [third checkpoint](accessibility-stage-3_en.md) adds image authoring actions and semantic checks, aligns all module dependencies, completes the construction test and executes the root ownership test in isolated Linux. Stages 1–2 remain open: remaining forms, generator coverage, and manual checks of complete workflows are still required.

The [fourth checkpoint](accessibility-stage-4_en.md) repairs the API key owner picker, Audit/Search filters, main landmarks and menu headings. It extends keyboard checks of published content and preserves local header associations during safe Confluence table conversion. Checks cover light/dark rendering and operation without Theme. The personal panel has not been started.

The [fifth checkpoint](accessibility-stage-5_en.md) repairs Setup progress, Backup messages/focus, dynamic Confluence mappings, comments, tasks and notification controls. Full isolated E2E passes 77/77; final Setup changes also pass a focused test. Expanded states, operation without Theme and narrow screens were checked. This does not replace screen-reader and actual zoom assessment.

The [sixth checkpoint](accessibility-stage-6_en.md) introduces an independent development package for personal preferences, local assets, central activation, and an explicit Simbioza layout hook. Keyboard behavior, corrupt-storage recovery, and reflow at 320 CSS pixels with 200% text were checked on a small set of local pages; actual 400% browser zoom remains unverified. Fonts and complete integration are unfinished.

The [seventh checkpoint](accessibility-stage-7_en.md) confirms the module boots in a minimal HeartPhrame application. Since then, locally licensed fonts, four tabs, three palettes, high-contrast views, self-contained widget styles, and optional Theme integration have been added. The module is being prepared for an initial release, not the completion of an audit or a claim of WCAG conformance. Current limitations are in the module documentation and [release notes](release-0.1.90_en.md).

| Stage | Work | Completion gate |
| --- | --- | --- |
| 0 | Verify state, inventory all views, reproduce findings | Versions, authority boundaries, test matrix, and reproducing tests recorded |
| 1 | Base layout, theme, menus, and messages | Baseline navigation and shared components work without the new module |
| 2 | Calendars, editor, workspaces, and remaining views | Confirmed issues fixed in their owning modules; remaining findings recorded |
| 3 | Integration contract and new module foundation | Module works in a minimal second application without Simbioza modules or framework changes |
| 4 | Personal adjustments and accessible panel | Every first-version feature works by keyboard, with reset and content preservation |
| 5 | Simbioza, module lifecycle, translations, and documentation | Consistent GUI/CLI and languages; documentation and comments complete |
| 6 | Integration and manual verification | Local tests and CI passed; manual results and limitations recorded |
| 7 | Coordinated release and upgrade verification | Verified versions published and local installations upgraded from the release |

Write documentation and regression tests throughout every stage, not only at the end. Each session updates status, evidence, and the next step in both language versions of this plan.

## 3. Stage 0 — preparation without business-data changes

1. Read this plan, repository instructions, and the current module dependency contract. Verify actual paths, branches, tags, local changes, and CI. Preserve unrelated changes.
2. Locate the available `heartphrame-module-demo` template and read it without changes. Also use `ModuleTheme`, `ThemeMenuIntegration`, `ThemeLayoutRenderer`, and their HR/EN comments as examples. Do not assume new framework APIs exist.
3. Inventory routes, shared components, and states: anonymous/authenticated users, administrators, empty/populated results, errors, open dialogs, longer translations, and dynamic content.
4. Cover all existing views in Simbioza and installed modules, including authentication, API settings, audit, backup, calendars, comments, editor, email, menus, notifications, ORM settings, users, tasks, themes, workspaces, search, and import. Mark modules without a GUI accordingly, but check their messages shown through other modules.
5. Reproduce findings using isolated data and an authorized test account for authenticated workflows. Mark unavailable checks as not performed rather than passed.
6. Establish an automated baseline report and targeted regression tests. Do not disable rules to make results appear successful.

## 4. Stages 1 and 2 — repairs to existing code

| Change owner | Required work and verification |
| --- | --- |
| Simbioza, `views/layouts/main.php` | Make the skip link and basic landmarks independent of Theme; avoid duplicate skip links when Theme is active. Align page titles, main content, and named regions. Check focus with sticky headers, mobile navigation, and zoom. |
| `heartphrame-module-theme` | Fix shipped color combinations and all control states. Initial dark-mode measurements were 2.46:1 for calendar controls, 2.64:1 for the local sign-in button, and 3.21:1 for helper text. Add contrast checks to theme editing and clear warnings for custom palettes without silently replacing user themes. Check gradients, forced-colors focus, and reduced motion. |
| `heartphrame-module-menu` | Check keyboard navigation, active items, expanded/collapsed state, dropdowns, and focus restoration. Append new entries without reordering existing ones; hiding/restoring module entries must preserve customization. |
| `heartphrame-module-auth` and transient-message producers | Associate form labels and errors; announce routine confirmations politely and urgent errors appropriately. Replace identical seven-second automatic dismissal with behavior that provides enough reading time and later access to important information. Check duplicated components, including Editor messages. |
| `heartphrame-module-calendar` | Associate labels with fields; name event/subscription dialogs; maintain `aria-pressed` for the selected view. Check keyboard operation, focus after date changes, loading announcements, complete event names, and use without color discrimination. Calendar color selection must yield readable event text instead of always using white text. |
| `heartphrame-module-editor-html` | Check toolbars, dialogs, attachments, tables, rendered documents, tabs, collapsible blocks, charts, and other dynamic elements. Add authoring checks for meaningful alternative text or decorative-image status, headings, table headers, and understandable links. Do not invent image descriptions or rewrite published documents automatically. |
| `simbioza-module-workspace` and `simbioza-module-workspace-search` | Check tree navigation, selection, search results/announcements, forms, pagination, and rendered documents. Personal adjustments must not affect ACLs, publication, or content translations. |
| `heartphrame-module-notification`, `heartphrame-module-task`, `heartphrame-module-comment` | Check readability, states conveyed beyond color, keyboard operation, status announcements, and focus after adding/removing items. |
| Remaining modules, installation, Setup, and upgrades | Review every remaining view in the inventory. Pay particular attention to settings forms, file pickers, deletion confirmations, upgrade status, progress, and errors. Do not declare a defect before verifying it. |

Make repairs in the owning source repository, never as permanent manual patches inside an installed `vendor` copy. Do not rewrite existing migrations. If a new migration is genuinely needed, make it additive with a backup and recovery plan.

## 5. Stage 3 — integration contract and module architecture

- New package: `heartphrame-module-accessibility`; confirm the complete Composer name, namespace, and remote repository against the template and existing conventions before creating them. Author: Krešimir Mihalj; future commits use `kmihalj@srce.hr`.
- The core must not require Simbioza, Workspace, Editor, or Theme. Menu and Auth are optional integrations. Without a verified authorization mechanism, no administrator settings mutation may be publicly exposed.
- Define a narrow host-layout contract: resource inclusion, launcher/panel placement, adjustment root, and installation identity. Integrate explicitly into existing layouts rather than relying on fragile arbitrary HTML rewriting.
- Separate module registration, state access, personal-preference validation, panel rendering, CSS/JS assets, and optional adapters. Create a separate second-application test fixture without changing the demo repository.
- Store personal preferences in the browser for the first version; do not introduce a database or cross-device synchronization. Storage keys must include installation identity/base path and schema version so HFC and FPMSimbioza remain separate. Document that users sharing the same browser profile share preferences for that installation.
- Validate allowed values, handle corrupt/unavailable storage, and apply safe defaults. Do not store medical diagnoses, document contents, or tracking data.
- Respect CSP, avoid remote executable scripts and arbitrary user CSS/HTML. Serve assets and fonts locally; verify exact license compatibility and include license files.
- Apply preferences early without weakening CSP or flashing the wrong appearance. The baseline application remains accessible without JavaScript.

## 6. Stage 4 — first-version features

| Area | Planned functionality |
| --- | --- |
| Reading and dyslexia | Default system font, Atkinson Hyperlegible, and optional OpenDyslexic after license/glyph checks; text size, line spacing, letter/word spacing, line width, and suitable alignment. These are personal choices, not treatment claims or a universally superior font. |
| Low vision | Verified contrast palettes, emphasized links and focus; support text adjustments and browser zoom without clipping controls. Do not rely on `transform: scale()` across the entire page. |
| Color-vision differences | Verified-contrast palettes together with text, symbols, or patterns in relevant components. Color-vision simulations support testing rather than acting as a universal user correction filter. |
| Reduced motion and concentration | Respect system `prefers-reduced-motion`, allow additional motion reduction, and provide an optional reading ruler and calmer content presentation. No option hides essential controls, warnings, or content without an accessible exit. |
| Panel itself | Localized labels/states, keyboard operation, visible focus, accessible naming, predictable dismissal/focus restoration, and reset of all personal preferences. The launcher must not cover important controls on mobile or at high zoom. |

Adjustments must work in combination. Scope CSS to components and content without breaking icons, code blocks, tables, or scripts unsuited to character spacing. Check Croatian diacritics, extended Latin, and Cyrillic fallback fonts; text direction must follow the document. Do not promise complete script/language coverage without verification.

A calmer presentation must not create duplicate identifiers, bypass permissions, or modify saved documents. Third-party embeds, PDFs, and other attachments have separate limitations that must be documented.

Outside the first version: a custom screen reader, automatic image-description generation, OCR/attachment rewriting, medical profiles, universal vision filters, cross-device synchronization, and automatic certification claims. This does not exclude checking the underlying accessibility of content.

## 7. Stage 5 — lifecycle, languages, and documentation

### Enabling, installation, and updates

- Use the existing central module-state source. The module settings switch, central module management, and CLI must produce the same result. After full disablement, re-enabling remains available through central management/CLI, not through a disabled route.
- When absent or disabled, the module registers no public assets, panel, or settings entries; the application and baseline fixes remain functional. Re-enabling restores integration without duplicates or loss of personal preferences. First-time menu entries go at the end.
- Uninstallation must not remove other modules' business data. Document configuration retention; old browser preferences remain inactive and can be reset, rather than claiming the server can remove them from every device.
- Test installation, enablement, disablement, removal, and updates on isolated installations. GUI package operations use the existing restricted FPM mechanism; CLI must work for both FPM and non-FPM installations. Do not broaden sudo permissions or web-process write access for this module.

### Localization

- Register Croatian source keys and update every published language in `simbioza-languages`, including screen-reader labels, validation, dynamic messages, and option names.
- Use existing key extraction and language-package versioning. Test fresh installation and installed-package updates; do not rely on private local translations.
- Test every offered language and longer translations. Do not intentionally mix interface languages; document normal fallback behavior only for genuinely unavailable translations.

### Mandatory documentation and comments

- Provide separate module `README.md` in English and `README_hr.md` in Croatian.
- In `docs/`, create separate language pairs for user instructions, administration/GUI/CLI, installation and integration into another HeartPhrame application, architecture/extensions, accessibility testing, and known limitations. Use `*_en.md` and `*_hr.md` filenames.
- Update Simbioza user/installation guides, module inventory, dependencies, and release notes. English screenshots show the English interface and Croatian screenshots the Croatian interface; examples and screenshots must contain no secrets or personal data.
- **All first-party code in the new module and added/modified code in existing modules must have bilingual HR/EN comments following existing conventions.** This includes documentation of classes, services, methods/functions, meaningful constants, configuration contracts, templates, JavaScript, CSS, and test scenarios. Explain purpose, behavior, limitations, and nontrivial decisions in both languages; there is no need to restate every obvious statement.
- Identifiers and standard annotations such as `@param` follow code conventions. Bundled third-party code/fonts retain their original license and attribution without artificial rewrites of their comments.
- Every documentation file remains monolingual; only comments and documentation inside code intentionally contain both HR and EN. Review and release gates must check both rules and keep the two instruction versions aligned.

## 8. Stage 6 — verification matrix and acceptance

1. **States:** Accessibility absent/disabled/enabled; Theme absent/disabled/enabled; light/dark presentation; default and custom workspace themes. Test critical shared workflows in every relevant combination.
2. **Interaction modes:** mouse, keyboard only, 200% text resizing, actual 400% browser zoom, 320 CSS-pixel width, custom text spacing, reduced motion, and forced system colors. Check two-dimensional tables/calendars using the applicable exceptions and accessible controls.
3. **Screen readers:** VoiceOver/Safari on Mac and NVDA with a supported browser when an appropriate system/tester is available. Record unavailable checks honestly; axe does not replace them.
4. **Automation:** unit tests for validation, storage, and module state; integration tests for optional dependencies; browser tests for panel behavior, focus, localization, font loading, lifecycle, and axe checks on representative pages/states. Resolve reported WCAG A/AA issues in the covered first-party interface before acceptance; manually inspect review-required findings instead of silently ignoring them.
5. **Security/regressions:** CSRF and administrator authorization, unavailable storage, invalid values, CSP, module removal, preserved ACLs/documents, and absence of unnecessary external requests.
6. **Installations:** use local HFClean for integration; if migrations are introduced, first take a restorable backup, migrate to zero pending migrations, and verify the schema. Then run focused tests and the complete existing local E2E process through its final exit result. Use HFC and FPMSimbioza to verify real non-FPM/FPM installation behavior without mixing preferences.
7. **User acceptance:** short tasks with intended users; record difficulties and address them before claiming superiority over alternatives. Do not collect unnecessary health data.

Every stage ends with verifiable output: tests/report, changed repositories, documentation status, and remaining limitations. Publication waits for all required checks, not merely the absence of critical automated warnings. Starting commands are `composer on-commit` in changed repositories that define it and `php scripts/run_e2e.php --local --keep` in HFClean; verify in stage 0 that they still match the current workflow.

## 9. Stage 7 — release and upgrade

1. Confirm release scope and authorization once development is complete. Choose versions at that point.
2. Run and await every required check and CI pipeline in each changed repository. Publish dependencies and the new module before Simbioza; publish language packages before the application release that requires them.
3. Update the application module catalog, compatible versions, translations, and release notes. Follow the then-current repository policy for Composer lock files and keep development separate from installations.
4. Upgrade local HFC from the published release using CLI and verify migrations; separately test CLI and GUI upgrades on FPMSimbioza from the previous supported version. Do not substitute manual source copying for the real upgrade flow.
5. Verify preservation of private configuration, menus, themes, languages, personal preferences, and FPM-helper functionality, and that recovery does not lose data.
6. Hand over release tags, actual CI/test results, known limitations, and the verified apps-test CLI command. apps-test remains the user's GUI/CLI step unless they explicitly request otherwise.

## 10. Remaining work

Do not repeat the completed baseline, standalone module, and local-font work. Complete lifecycle checks from the published package, real 200/400% zoom, text spacing, longer translations and gradient contrast, and review notification read-state announcements and errors in remaining administration dialogs. VoiceOver/Safari, NVDA and user testing remain separate open tasks. An initial screen without automatic findings does not mean all states have been assessed.

At each session boundary record completed/remaining work, changed files/repositories, tests started versus actually finished, open decisions, and the exact next action. Re-estimate time and tokens after stage 0; this plan sets no spending budget and starts no background development.
