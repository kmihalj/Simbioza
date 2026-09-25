# Accessibility — seventh local development checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-7_hr.md).

This follows the [sixth checkpoint](accessibility-stage-6_en.md). It is still a local prototype, not a release or a claim of WCAG conformance. The protected HeartPhrame framework and demo repositories were not changed.

## Independent application check

The accessibility package now has a `StandaloneHostTest` that boots a real, minimal HeartPhrame application with **only** the accessibility module enabled. It verifies that the module loads, both local asset routes register, and the global renderer reaches the layout. This goes beyond a mocked renderer test and shows the package does not require Simbioza, Workspace, Editor, Theme, Menu, Auth, or a database. The test passes with 9 assertions. The separate Simbioza integration remains in place for visual testing.

## Local HFC Setup recovery

The HFC `settings/setup` page failed before sending a response because the expired language catalogue triggered a PHP HTTPS stream in macOS Apache's forked mod_php process. The macOS crash report shows the fault during DNS resolution inside `file_get_contents`; Apache recorded child segmentation faults. This was not an authorization or accessibility-module failure. Refreshing the catalogue in CLI restored the page temporarily. `LanguageRepository` now uses the system curl executable in a separate process **only** for HTTPS downloads from Darwin `apache2handler`; other platforms and CLI keep their previous transport. The TLS, five-second, protocol, and size limits remain enforced. An authenticated visit with a deliberately stale catalogue completed and refreshed it without another Apache crash.

The isolated local E2E run passed all 77 browser, API, and performance scenarios. This includes the authenticated Setup page. The focused language tests passed (6 tests, 23 assertions); the accessibility package passed 4 tests with 221 assertions. The full HFClean `composer on-commit` passed 96 tests with 4,261 assertions and one existing Mac-only root-permission skip. No user content or imported documents were changed. No commit, push, release, or apps-test update was made.

## Remaining work

The module still needs local fonts with verified licenses and glyph coverage, high-contrast and reading accommodations, full visual/manual assistive-technology review, language-stress tests, and the complete package lifecycle checks. It remains disabled in the HFC runtime after the previous prototype test; only its local Composer link and allowlist remain for development.
