# Accessibility — fifth local development checkpoint

Date: 24 September 2026. [Croatian version](accessibility-stage-5_hr.md).

Continuation of the [fourth checkpoint](accessibility-stage-4_en.md). These are baseline repairs, not the completed personal-adjustment module or a claim of full WCAG conformance. Work is local: no release, commit, push, remote CI or apps-test changes. Existing HFC and FPMSimbioza installations have not been upgraded. Isolated installations use synthetic data and local module sources; installed packages were not hand-patched.

## Changes and rationale

- **Setup:** progress uses a native dialog. Opening focuses its heading, the background is inert and Tab/Shift+Tab stay among available buttons. Escape or Close dismisses the view only; the updater continues. A button beside update information reopens progress. Polling does not reopen a dismissed view. Failure has an appropriate heading and a refresh button. The nested main landmark was removed, and module rows no longer combine `article` with an unsupported `role="row"`. Server-side updating, permissions and migrations are unchanged.
- **Backup:** all five pickers associate visible labels and current selections with controls. The file picker has one label and visible focus. Upload percentage is exposed to assistive technology; preflight and restore remain indeterminate when no real percentage exists. Messages no longer expire. Focus recovery after an operation or dismissal does not interrupt someone who has already moved on. Background job refresh retains unchanged rows and focus; removal moves focus to a remaining action or the list heading. Icon buttons have explicit names. The component grid fits narrow screens.
- **Confluence mappings:** each picker identifies its source identity, controls a named panel and exposes accurate expanded state. Native search and result buttons replace incomplete combobox semantics. Enter selects, Escape dismisses and restores focus, and leaving the picker closes it. Opening a second picker closes the first. Cancellation and request sequencing reject late responses. Loading, result counts and errors have a status region. Group selects and progress bars have names. Workflow messages remain until dismissed. Without Theme, small identifier text measured 4.03:1 contrast; its fallback now uses the readable body text color.
- **Comments:** reactions expose and update `aria-pressed`. Deletion focuses the comments heading when focus belonged to the removed comment. Errors are alerts and confirmations are polite status messages; both remain available until dismissed, returning focus to the related control or heading.
- **Tasks:** successful and failed saves restore checkbox focus only if temporary disabling lost it. Failed saves restore the previous state. Dismissing an error returns focus to the task or follow control. Duplicate assertive live-region declarations were removed.
- **Notifications:** deletion controls reference their notification titles. Unavailable pagination links leave the tab sequence and expose disabled state. Existing full-navigation forms were not replaced with a new asynchronous system.

Changed sources: HFClean and five modules—Backup, Comment, Task, Notification and Confluence Import. Protected repositories were not changed; `heartphrame-framework` remains clean. No migrations, fonts or external runtime resources were added.

## Verification evidence

| Completed local check | Result |
| --- | --- |
| Backup, full `composer on-commit` | 45 tests, 146 assertions |
| Comment, full `composer on-commit` | 7 tests, 35 assertions |
| Task, full `composer on-commit` | 11 tests, 73 assertions |
| Notification, full `composer on-commit` | 8 tests, 54 assertions |
| Confluence Import, full `composer on-commit` | 100 tests, 629 assertions |
| HFClean, full `composer on-commit` | 96 tests, 4,261 assertions; one Linux root test skipped on macOS |
| Full isolated E2E | **77/77, exit 0, 8.0 minutes** |
| Final focused Setup regression | 1/1, exit 0, 3.7 seconds |
| Six-language catalog | 4,373 keys each; valid SHA-256 and SVG |
| Documentation pairs and links | 0 issues |

The five modules total 171 tests and 937 assertions. Their configured style and static-analysis checks also pass. The single skipped HFC test needs Linux/root; its earlier real execution is documented in checkpoint three. The temporary Linux environment was not reinstalled.

The initial full run was 75/77. A test showed Chromium could offer its address bar at the end of a native dialog, so explicit Tab/Shift+Tab handling was added without removing the assertion. The other failure expected `Select` instead of the actual `Choose a backup archive`; the test expectation was corrected. The final full run uses fresh isolated installation `e2e-all-957cdcfe`. Subsequent final Setup semantics were additionally verified by the focused test and the latest rendered template staged in an earlier isolated fixture.

Extended E2E covers dismissing/reopening progress, terminal failure, persistent Backup messages and focus, real full-site backup/restore, keyboard-based Confluence selection, reaction state and comment deletion, and successful plus deliberately failed task saves. Task failure and update status are synthetic; **no real application update was launched**. Existing API, ACL, import and performance checks remain enabled.

Local axe-core 4.10.3 reports no confirmed violations after repairs in the inspected initial and expanded Setup, Backup and Confluence mapping states. Checks include themed light/dark rendering and operation without Theme; narrow themed Backup and expanded mappings at 320 CSS pixels have no page-level horizontal overflow. A separate two-identity exercise verifies closing the other picker and rejecting a late response after Escape. Gradient contrast still requires manual review. A transient contrast finding during a theme transition disappeared after the transition settled. Disabling Theme produced one finishing request from the old page for a Theme asset; subsequent navigation contains neither that asset nor broken images.

Logs: `build/a11y-stage5-quality/`. Screenshots: `output/playwright/`. The temporary audit tool is not an application dependency. Synthetic Confluence preparation was cancelled through its UI without importing a business workspace.

After inspection, Theme was re-enabled in the helper fixture, the browser and helper HTTP server were stopped, and the temporary axe-core copy was removed (2.7 MB; it can be downloaded again). Isolated E2E installations and logs remain available for review.

## Documentation and next step

Backup instructions, Comment/Task module descriptions and Confluence mapping instructions were updated in separate English and Croatian files. New comments are bilingual HR/EN. All six packs already contain the reused keys; Confluence now also has its own HR/EN loading and result-count fallbacks. This checkpoint requires no new language-pack revision.

Next finish actual 200/400% zoom, text spacing, longer translations and gradient contrast checks. VoiceOver/Safari, NVDA and user evaluation were not performed by this automated run. Specifically review announcement of notification read-state changes and errors in remaining administration dialogs. Then finalize the narrow integration contract and start the `heartphrame-module-accessibility` personal panel with local licensed fonts. The initial design remains one administrator switch and browser-local personal preferences, without framework changes.

References: [W3C modal dialog pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) and [status messages](https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html).
