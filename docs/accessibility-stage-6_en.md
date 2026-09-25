# Accessibility — sixth local development checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-6_hr.md).

This continues the [fifth checkpoint](accessibility-stage-5_en.md). It is a module foundation prototype, not a completed release or a WCAG conformance claim. All work is local: no commit, push, release, or change to apps-test, HFC, or FPMSimbioza. The read-only `heartphrame-framework`, `heartphrame`, and `heartphrame-module-demo` repositories were not modified. The demo template was reviewed from a temporary read-only copy.

## Implemented

- A separate local `heartphrame-module-accessibility` package follows the manifest and Composer conventions. It depends only on the framework, not on Simbioza, Theme, Workspace, or Editor. Its two local CSS/JS asset routes and global layout renderer register only while central module state loads the package.
- The development Simbioza layout explicitly includes resources in its head and the panel near the end of the body, without rewriting other HTML. The package is allowlisted in central module state but not added to the public catalog or production Composer dependencies because no package release exists. Only the ignored local Composer configuration installs it for development. There is no second administrator switch, database, or migration.
- The initial panel offers text sizing and spacing, link emphasis, and reduced motion. It uses a native dialog with Escape, focus return, and reset. Only allowlisted values are stored under an origin/base-path/schema-version key; corrupt or blocked storage does not break the page. Without JavaScript, the launcher remains hidden and baseline content is available.
- Visible labels and dynamic messages have Croatian source keys and local English, German, French, Spanish, and Italian translations. Package documents are separated by language; new code comments are Croatian and English.
- Browser review found an oversized launcher and a long title overflowing at narrow width with 200% text. The launcher is compact on small screens and the title can wrap. This does not establish that every zoom/language combination is acceptable.

## Completed checks

| Check | Result |
| --- | --- |
| New PHP/JS syntax and package PHP style | Pass; five PHP files without warnings |
| New renderer and six-language PHPUnit checks | 3 tests, 212 assertions, exit code 0 |
| HFClean `composer on-commit` after local linking | 96 tests, 4,261 assertions, one existing Mac skip; exit code 0 |
| HTTP with temporarily enabled module | Home page and two local assets return 200; after disabling, asset returns 404 and no panel appears in HTML |
| Browser and keyboard | Open, choose 200%, persist, reload, Escape, and focus return passed |
| Narrow reflow | At 320 CSS pixels and personal text size 200%, reviewed home page and panel do not overflow horizontally; panel scrolls vertically |
| Corrupt storage | Intentionally invalid JSON falls back to defaults without breaking rendering |

Playwright CLI was used to inspect the real local interface. A 320-CSS-pixel width check is not actual 400% browser zoom. The full installation E2E suite, VoiceOver/Safari, NVDA, manual review of every route, and remote CI were not run. After testing, the development module state was restored to disabled and the local browser/server were closed. The ignored local Composer configuration and package link remain for continued development; production `composer.json` was not changed.

## Open work and next action

The module still lacks local fonts, high-contrast and reading options, full long-translation/gradient checks, an independent second HeartPhrame host, catalog/GUI/CLI lifecycle, and the remaining required documentation pairs. Font upstreams were inspected, but exact file licenses and glyph coverage still need checking before bundling. User documents and imported content were not changed.

Next, build a minimal independent HeartPhrame host without Simbioza modules and verify the same layout contract; then bundle local fonts only with licenses and diacritic/Cyrillic checks. Expand the panel and CI, add the package to the catalog, and test installation, disable/re-enable, and removal. Separately resume real 200/400% zoom, gradient contrast, notification read-state announcements, and remaining administrator-dialog errors.
