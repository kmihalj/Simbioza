# Pre-release audit and continuation — 0.3.0

Current status: release publication was explicitly authorized on 12 October 2026.
[Publication tracking](release-publication-0.3.0_en.md) records exact-commit CI,
immutable tags and image artifacts. The dated sections below are local audit evidence.

The earlier [initial-release checks](initial-release-0.3.0_en.md) are baseline
evidence **before** the new script registry. They do not approve publication of
the expanded candidate. No release/tag/push or live-site update is authorized.

## Script registry work

- [x] File-backed registry under `HPH_CONFIG_PATH/editor-html/`, with atomic layered writes.
- [x] Admin approval/revocation, per-page editor selection, no global loading or H5P exception.
- [x] Confluence captures script candidates, groups exact identities by page, and offers activation of one/all matching pages.
- [x] New imports automatically select only exact locally approved matches, never new/rejected definitions.
- [x] Admin usage report distinguishes current published/draft references and optional history in both payload drivers, with batched SQL.
- [x] Referenced definitions/candidates are backed up without approval permission; destination-local decisions win. Restore sanitizes HTML.
- [x] Separate EN/HR instructions and annotated `.json.dist` example.
- [x] Renaming changes only the display label; import preserves local labels and decisions.
- [x] Six public guide archives rebuilt for current Editor provider schemas; payload bytes unchanged.
- [x] Complete integrated GUI validation of approval, selection, single/bulk import activation, reimport and usage reporting.
- [x] Record final full browser suite result after new feature changes.
- [x] Confirm page/workspace/full-site archive round trips with new scripts, including abort and destination review.

## Audit coverage

Review and local corrections A1–A5 are complete, with evidence below. The current
continuation is release approval and published-artifact verification, not the
old configuration upgrade plan. Local checks do not authorize publication.

- [x] Review active configuration consumers; correct Search/Confluence nesting and Search backup policy.
- [x] Review current examples/documentation; improve API/Backup examples and remove obsolete active upgrade instructions. Language-order/example corrections are completed in A5.
- [x] Measure SQL growth and run existing Calendar/Workspace/Editor query budgets; record the Notification defect as A2.
- [x] Review upload/ZIP/path/SSRF, HTML/iframe, headers, cookie/session, CSRF/authorization and API checks. Implement scoped fixes; Auth protocol correction A1 is completed below.
- [x] Verify minimal generic-module dependency combinations without changing Framework.
- [x] Review Docker write paths and SimpleSAMLphp requirements; record A3/A4 rather than claim an image is ready.
- [x] Rerun affected SQLite clean-install and real Linux FPM/secure-FPM checks. No schema changed; other database baseline dates stay explicit.
- [x] Resolve A1–A5 and rerun affected local checks.
- [x] Release publication authorized on 12 October 2026.
- [ ] Complete coordinated publication and tagged-package/image verification. User reinstalls demo/apps-test.

## Comprehensive audit results — 12 October 2026

The review has identified release corrections, not approved the candidate.
Passing existing tests does not close the findings below. HFC is aligned with
the audited runtime fixes; remote demo/apps-test and Framework are untouched.

### Corrections implemented

- Search now consumes the native layered `workspace-search.workspace-search`
  tree at `HPH_CONFIG_PATH/workspace-search/workspace-search.php`. Confluence
  consumes `confluence-import.confluence-import` at its corresponding module
  path. Package defaults stay in read-only package resources. Relative Confluence
  staging paths reject absolute paths and traversal.
- Application backup now reads the same Search policy path and its actual five
  supported keys. Private installation paths are not carried to another host.
- SecurityHeaders and ErrorHandler middleware are enabled in the correct order,
  including request-metrics mode. The policy covers normal, redirect, denial
  and error responses; private error details are hidden. CSP currently adds
  `base-uri 'self'; object-src 'none'; frame-ancestors 'self'` only. It is not a
  complete `script-src` policy or a replacement for HTML sanitization/the registry.
- Editor's browser dialog and server sanitizer use
  `allow-scripts allow-presentation` without `allow-same-origin`. Some external
  embeds requiring origin storage/cookies need compatibility review; HTTPS
  alone does not approve their JavaScript. H5P has no exception.
- Webhook transport connects to the numeric address it validated, retaining
  the original Host and TLS peer name. It rejects mixed public/private DNS,
  non-global IPv4/IPv6, unsafe URLs/headers and redirects. Responses and timeouts
  are bounded. Actual isolated HTTP tests prove pinning and no redirect follow;
  positive production-certificate TLS/SNI interoperability still needs its own
  integration check before claiming every webhook provider was tested.
- API and Backup have one canonical module-root PHP `.dist` each, mirrored by
  Simbioza's examples, with every option explained in complete EN/HR blocks.
  Native file loading was tested without any GUI or private HFC configuration.
  Active module documentation no longer promises a 0.2.x config-export upgrade.
  Backup's beginner commands now use the real `vendor/bin/hph` entry point and
  `orm-migrate:up`, after enabling the module. A host without Auth must protect
  the web console itself or use CLI only.

### Measured query behavior

`scripts/verify_audit_scaling.php` accepts only a new, retained, disposable
all-module SQLite installation and seeds fictional users/pages. Each measurement
boots a new service graph and counts SQL only for the read under examination.

| Read | One record | 100 records | Observation |
| --- | --- | --- | --- |
| Comment list, limit 30 | 4 queries | 4 queries | Bounded for this fixture |
| Notification inbox, limit 30 | 9 queries | 504 queries | Pre-A2 baseline; correction recorded below |
| Auth-user SELECTs inside inbox | 1 | 100 | Repeated same-user loading |

Before A2, `NotificationService::visibleRows()` read the whole inbox before
pagination. Simbioza's visibility provider called `FollowTargetService` per
notification; node/workspace/ACL and user resolution caused the measured growth.
The correction batches visibility/targets and avoids retained positive
authorization decisions. The original requirement was to cache only
request-local, correctly invalidated authorization data. Do not skip ACL checks
or hide the defect behind a warm cache. Add query-budget tests for distinct
targets, read/unread totals and users with different permissions.

Existing Calendar performance tests passed: 30-calendar read uses at most five
relevant SELECTs, and the 122-event ICS fixture imports within 35 queries.
Workspace/Editor browser query budgets and the script-usage test (two queries
for over 200 versions, both drivers) also pass. These bounds are scoped to their
fixtures, not a claim that every application query is optimized.

### Corrections in execution order

- [x] **A1 — Auth/OIDC security (release gate).** Implemented and verified on HFC, with signed-token/nonce/PKCE/subject and bounded TLS transport checks. See the completed A1 record below; no Framework or remote-site change.
- [x] **A2 — Notification scaling.** Batched current ACL checks, bounded memory,
  stable cursor traversal and grouped writes are applied to HFC. Cold-request
  and four-driver checks pass; full browser/API suite is 80/80. See the A2 record below.
- [x] **A3 — Runtime language storage.** Installed JSON catalogues live in
  data; packaged application/module `lang/` remains code. HFC, locale GUI/CLI,
  transactional backup/restore and read-only-code FPM are verified. See A3 below;
  Framework was not modified.
- [x] **A4 — Immutable deployment operations.** Image/Compose and explicit locked
  migrations passed in Colima. Runtime/database volumes, immutable code policy,
  retained configuration/content/language GUI and optional read-only SAML are
  implemented. See the A4 record and Docker guide below.
- [x] **A5 — Documentation/comment consistency.** Marked comments now have
  separate EN then HR blocks, with token-aware order checks. Beginner examples,
  current configuration paths, README/file/heading links and historical release
  boundaries are corrected. See the A5 record below.
- [x] Rerun affected local checks after the corrections.
- [x] Explicit release approval received on 12 October 2026. Publication is tracked separately; remote reinstalls remain user-operated.

For A1, lack of a local signature check alone is not proof of an unauthenticated
JWT-login exploit: OIDC code-flow responses can use validated direct TLS under
the specification's defined conditions. Issuer, audience, lifetime and identity
checks still matter. Follow
[OpenID Connect's ID-token validation rules](https://openid.net/specs/openid-connect-core-1_0.html#IDTokenValidation).

### Verification recorded for this review

- All 18 public module suites passed: 1,063 tests / 9,092 assertions, including
  the final browser-template sandbox regression.
- The application suite passed 242 tests / 5,995 assertions, with three
  Linux/root-only cases skipped on macOS. Those three passed separately in
  Colima (43 assertions), without changing host service configuration.
- All 21 fresh SQLite combinations passed with real Composer/CLI/migrations
  and HTTP startup, including minimal generic-module installations.
- The complete isolated SQLite browser/API/performance suite passed 80/80
  in 11.5 minutes with exit code 0 and the runner's final success summary.
  The subsequent iframe-dialog literal change passed its focused view/sanitizer
  regression tests; that does not constitute a second full 80-case run.
- Real Linux FPM and secure FPM passed home/login plus Auth/Menu/Theme/registry
  writes with read-only code, writable data and final-layer configuration.
- Configuration/examples/security-header integration tests passed 14 tests /
  223 assertions; they use real framework layering without HFC secrets.
- Installed non-development dependency audit reported no known advisories.
  This does not cover a separately installed SimpleSAMLphp or certify source security.
- Configured PHPStan and style checks passed for the application and changed
  runtime modules (including development checks where configured; Confluence
  has a production-only PHPStan configuration). Translation coverage, EN/HR document pairs/links
  and bilingual method-documentation presence checks report zero issues; the
  latter does not validate language order and therefore does not close A5.
- HFC home/login return HTTP 200; 14 migrations applied, zero pending. No new
  schema migration was needed for these fixes.

The PostgreSQL 18/MySQL 8.4/MariaDB 11.4 baseline results dated 11 October remain
historical evidence, not newly rerun database matrices for this review.
Reports are in `build/audit-0.3.0/`, `build/clean-install-matrix-sqlite.json`
and the browser report. See [container readiness](container-readiness_en.md) for
the precise Docker boundaries. Test installations contain synthetic data only;
their cleanup never follows module/vendor symlinks. The retained scaling/E2E
installations and FPM input copy were removed without backup after verification;
their test records are disposable. Reports and tools remain. The Linux FPM
runner stops its own process and removes its own fixture.

## Evidence recorded during implementation

11 October 2026: focused Editor suite 224 tests / 1,628 assertions and Confluence
suite 116 tests / 722 assertions passed. Production PHPStan passed in both modules,
and Editor's development analysis passed. Module style checks passed.
The usage test covers multiple languages, both drivers, deleted pages, history,
missing files, and two batch queries for over 200 versions. These are focused
results, not completion of the comprehensive audit above.

The guarded developer tool `scripts/verify_external_script_import.php` ran on a
retained isolated SQLite E2E installation, never HFC or a live site. It exercised
real Confluence ZIP imports and reimports in English, exact approval reuse,
one/all-page activation, a plain page without scripts, usage across two imports,
and real page/workspace/site archive restore. Archives contained all referenced
definitions but no approval status. Destination/abort unit coverage confirmed
new scripts remain pending and concurrent local administrator decisions survive
rollback. English import also exposed and fixed missing language policy on new
Workspaces/nodes; the existing Confluence browser scenario passed again (1/1).

Manual browser checks confirmed add/approve, editor selection, draft persistence,
publication, revocation, renaming, one/all report buttons, and script usage.
Published pages executed each selected approved script once; unrelated pages,
editor/draft/history/report views did not execute them. Ordinary-user access to
the usage route returned HTTP 403. The usage page was visually checked at 1280
and 390 pixels, with local table scrolling and no page overflow. Screenshots are
in `output/playwright/external-script-usage*.png`. The temporary installation and
its test backups were removed; private HFC configuration was not overwritten.

Six bundled guide archives use the current Editor provider schemas without
changing content records or blobs. The paired bundled-guide tests passed
(2 tests / 65 assertions). HFC has 14 applied migrations and zero pending;
translation, documentation and bilingual PHPDoc audits report zero issues.

The fresh local SQLite `editor-html` installation case passed in 11.285 seconds:
Composer, CLI boot, two migrations and HTTP home response. Its only installed
modules were ORM, Auth and Editor, without Simbioza, Menu, Theme or Workspaces.
The runner removed the temporary installation after the check.

The final local SQLite browser/API/performance suite passed all 80 scenarios
in 10.3 minutes, with exit code 0 and the runner's success summary. This includes
the existing Confluence import, backup/restore, permission and SQL-budget flows.
The separate script-specific integration and manual checks above cover registry
approval, automatic reuse and usage reporting; they are not additional cases
inside that 80-scenario suite. The runner cleaned up its isolated installation.

HFC remains the development installation. Private configuration and Framework
sources are protected; no new database schema migration is needed for scripts.

## A1 completed — 12 October 2026

A1 is complete for the current development candidate. It does not approve a
release, close A2–A5 or authorize a demo/apps-test update.

### Implementation

- Auth now has a native `OidcIdTokenValidator`, `AuthBackchannelClient` and
  injectable `AuthBackchannelClientInterface`. No Framework changes or
  cryptographic Composer dependency were introduced. cURL/OpenSSL requirements
  are documented as protocol-specific optional extensions.
- OIDC pins explicit issuer/JWKS and validates an RSA signature, issuer,
  client audience/azp, subject, expiry/issuance/not-before, nonce and requested
  authentication age. UserInfo must match the signed subject and cannot supply
  or replace reserved security claims. No unsigned JWT identity fallback exists.
  Supported algorithms are RS256/RS384/RS512, default RS256; unsupported
  HMAC/EC/JWE algorithms are explicitly documented.
- OIDC defaults to S256 PKCE; generic OAuth2 has an explicit opt-in and still
  authenticates only from UserInfo. Callback state is single-use, expires after
  600 seconds, and binds profile/protocol, original callback and configuration/
  mappings. Provider-error callbacks also consume state. Existing application
  sessions remain valid; outstanding old-format callbacks must restart.
- Native backchannel verifies TLS chain/hostname, retains private/internal
  HTTPS and custom CA support, never follows redirects, allows only GET/POST,
  and limits connection/total time to 10/30 seconds and response size to 2 MiB.
  Insecure HTTP is an explicit non-production option, not a TLS-verification
  bypass. CAS uses the same transport without losing its CA or mapping.
- GUI and manual JSON expose the same trust/PKCE/CA options.
  `heartphrame-module-auth/docs/oidc_security_en.md` and its Croatian pair
  explain every new option with a beginner JSON example. Module README,
  provider/configuration/installation guides and EN/HR translations are current.
  The annotated `auth.json.dist` and Simbioza's `config/auth.json.dist` match.

### HFC and verification

- HFC runs the new module code. Only the existing OIDC profile received issuer/
  JWKS from trusted public AAI discovery metadata; no client secret, endpoint,
  mapping, profile key or user link was overwritten. All seven existing SSO
  profile IDs and two user-identity links remain intact.
- The user completed the real AAI OIDC profile test in the browser. The success
  screen and database confirm profile ID 3 is verified/operational again.
  SAML/CAS/OAuth2 configuration and verification state are unchanged.
  This is a real OIDC callback check, not a claim that every other external
  provider was manually retested today; CAS/private CA and the protocol matrix
  have regression coverage.
- Auth: **123 tests / 933 assertions**, PHP 8.5.11. New signed-token, complete
  authorization, OAuth2 and native transport tests include malformed/tampered
  tokens, wrong issuer/audience/nonce/subject, age/expiry, PKCE binding, replay,
  profile/config/mapping/callback mismatch, error callbacks, reserved parameters,
  CAS CA preservation, redirect refusal, body limits and real TLS CA/hostname
  acceptance/refusal.
- Colima Linux PHP 8.4.26: **38 security tests / 345 assertions**, with read-only
  source and a disposable writable `/tmp`; no live credentials or network
  provider were used. Native HTTPS used only synthetic localhost certificates.
- Production/development PHPStan and source/test style checks pass.
  Translation/documentation/bilingual-presence audits report zero issues.
  These checks do not close the remaining language-order scope of A5.
- Minimal generic Auth + ORM + Framework clean SQLite install passed Composer,
  CLI/migration and HTTP startup in **11.868 seconds**, without Simbioza/GUI
  configuration or private HFC settings.
- Application unit suite: **242 tests / 5,995 assertions**, three unchanged
  Linux/root-only cases skipped on macOS. They were not newly rerun for A1.
- Full isolated SQLite browser/API/performance suite: **80/80**, **17.9 minutes**,
  exit 0 and final runner success. Its server stopped and temporary installation
  was removed without following module/vendor symlinks; reports remain under
  `build/playwright-report/`, `build/test-results/` and E2E logs.
- HFC home/login works; **14 migrations applied, zero pending**. No new schema
  migration was required. Framework, remote demo/apps-test and unrelated edits
  remain unchanged; no commit, push, tag or release was performed.

### A2 completed — 2026-10-12

- Notification adds optional `NotificationBatchVisibilityProviderInterface`;
  the registry combines applicable policies with AND and rejects exceptions,
  malformed lists and non-boolean approvals. In-memory scalar policies remain
  supported; the generic module has no dependency on Simbioza.
- `NotificationService::visibleBatches()` streams owner/state-filtered windows
  of at most 128 rows, ordered by immutable creation time and ID. Inbox memory
  retains only a window and the requested/last visible page; exact totals still
  visit all candidate windows. Bulk read/delete writes only visible IDs, with
  additional ownership/state conditions and no offset-based skipped rows.
- Simbioza User's new `FollowTargetVisibilityResolverInterface` is implemented
  by `FollowTargetService::canViewTargets()`: one active-user read per window,
  targeted Workspaces/pages/ancestors and fresh batched ACL. Workspace reuses
  its normal restrictions and direct grants; incomplete/cyclic ancestry denies
  access. Calendar's `readableCalendarIds()` keeps its owning read policy and
  remains optional. Embedded Calendar/task-list notices also require page ACL.

| Cold inbox fixture | Before A2 | After A2 | Active-user reads after A2 |
| --- | --- | --- | --- |
| 1 distinct page | 9 queries | 9 queries | 1 |
| 100 distinct pages | 504 queries | 9 queries | 1 |
| 1,000 distinct pages | Not measured | 65 queries | 8 windows / 8 reads |

The same 9/9/65 budget passes on SQLite, PostgreSQL 18, MySQL 8.4 and
MariaDB 11.4, including recursive-ancestor restrictions, direct grants and
same-service revocation. Comments remain four queries in each fixture. Reports
are `build/notification-scaling-{sqlite,pgsql,mysql,mariadb}.json`;
`scripts/verify_audit_scaling.php` accepts only a disposable matrix installation,
with a dedicated loopback `codex_a2` database for network drivers.

Verification:

- Notification **11 tests / 86 assertions**; Simbioza User **22 / 211**;
  Workspace **126 / 967**; Calendar **86 / 690**.
- Regression coverage includes over three cursor windows, tied timestamps,
  hidden rows, exact state totals, last-page clamping, batch mutations, different
  users, effective groups, direct grants, same-service ACL/group revocation,
  deleted/missing targets, inactive users, optional Calendar absence and
  source-plus-page access. Another cold test uses 100 distinct Workspaces.
- Production/development PHPStan and changed-source/test style pass for all
  four modules. Documentation, translation and bilingual-presence audits pass;
  A5 remains open for pre-existing language-order inconsistencies elsewhere.
- Full isolated SQLite browser/API/performance suite **80/80, 18.6 minutes**,
  final exit 0 and runner success. Application unit tests also pass (see the
  additional-fix record for the final count).
- Four clean all-module installations pass CLI/migration/HTTP startup. HFC has
  **14 applied migrations / zero pending**; no A2 migration/config change.
  Private Auth configuration checksum is unchanged. No Framework, remote
  demo/apps-test, commit, push, tag or release change.

### Additional user-reported fixes — 2026-10-12

Final application unit suite: **243 tests / 6,004 assertions**, three unchanged
Linux/root-only cases skipped on macOS. Language regression subset: **11 / 71**.
The isolated administrator browser check confirms the mod_php/CLI instructions,
separate revisions, unknown-revision badges and successful fresh-check message.
Owned matrix/browser fixtures and three disposable database containers were
removed after testing; reports and the user's original archive remain.

- DABAR: the supplied source contains empty `time[datetime]` date widgets.
  Dates are materialized before sanitization and again after macro conversion;
  existing labels and adjacent literal time remain intact. The shown previously
  imported HFC meeting page had already lost its date; only that missing value
  was recovered from the archive, without reimporting the Workspace. The source
  archive is unchanged. Confluence Import tests: **117 / 725**.
- Setup Languages: an explicit fresh catalogue check is available even on
  mod_php; local/remote revisions are separate, and an unrecorded local revision
  is not declared current. An unavailable fresh check cannot silently use stale
  cache. The GUI shows `languages available --refresh`, `languages update` and
  a per-locale `languages install <locale> --replace` bootstrap command where
  needed. At A2 completion, the existing FPM/helper write restrictions remained in place.
  Translation-only digest changes are tested without an application/module
  release. Runtime language storage was unchanged at that stage; A3 below supersedes
  those write-mode limitations.

The A2 handoff was A3; its implemented scope and verification are recorded below.

## A3 — Runtime language storage (12 October 2026)

### Implemented and applied to HFC

- `RuntimeLanguageStorage.php` stores installed catalogues as bounded JSON in
  `data/languages/catalogs/<locale>.json`, flags in `data/languages/flags/`, and
  repository revision/digest state in `data/languages/repository-versions.json`.
  JSON is never evaluated as PHP. Canonical names, fixed relative targets,
  subordinate-symlink rejection and atomic writes constrain data publication.
- `RuntimeTranslator.php`, `config/app.php` and `config/services.php` keep the
  application's/module packages' `lang/` as read-only source resources.
  Installed values override exact keys; missing keys retain package/module
  translations and the unchanged Framework's normal fallback locale.
- `LanguagePackManager.php` and `RepositoryLanguageManager.php` use final-layer
  `.languages.lock`, then data `.packs.lock`. Installation/removal precheck all
  targets and restore bytes/modes after publication failure. GUI, CLI and backup
  share the same locks. Source digests are recorded atomically with repository
  packs; manual replacement clears a previous repository revision. The classic
  updater no longer preserves generated application `lang/<locale>.php` files;
  it preserves `data/`, which owns installed language state.
- `SetupController.php`, `SetupDiagnostics.php`, `views/setup/index.php` and the
  installer permit data-only language changes on mod_php and FPM without the
  privileged code-changing helper. Administrator/CSRF protections and the
  last-enabled-locale guard remain. Code-changing package operations are unchanged.
- `ApplicationLanguageBackupProvider.php` wraps the generic config provider
  as application-settings schema **3**. It carries registry, catalogues, safe
  flags and revisions, never executable catalogues/source paths/cache/downloads.
  Replace removes absent packs; merge retains local-only locales but applies
  incoming bundled-only state over destination overrides. Cross-root restore
  and later-provider rollback preserve local paths and previous permissions.
  Page/Workspace-only archives do not carry installation languages.
- Menu's `AssetController.php`, `MenuRenderer.php` and
  `MenuSettingsController.php` preserve complete script/region locale names for
  installed flags, with a base-language fallback and constrained asset targets.
  No generic module dependency on application language classes was introduced.
- HFC's previously consolidated EN/HR catalogues (4,472 entries each) were
  copied into runtime JSON without losing any original key/value. Packaged
  application catalogues now contain 228 app-owned/default keys each; module
  catalogues remain in their packages. Effective EN/HR catalogues have 4,549
  keys each, including new/current module/default keys. This was a one-off
  development-site alignment, not a startup/old-release migration.
- Separate EN/HR localization, configuration, installation, container-readiness,
  README and Menu configuration guides describe the current behavior. Framework,
  HFC SSO provider configuration, remote demo/apps-test and source archives were
  not changed. No database schema migration, release, tag or push was performed.

### Verification

- Application suite: **256 tests / 6,060 assertions**, three existing Linux/root
  cases skipped on macOS. Focused language suite: **24 / 126**.
- Menu suite: **68 / 402**, including exact regional flags, base fallback,
  unsafe request rejection and complete-locale rendering.
- Production/development PHPStan and configured style checks passed in the
  application and Menu. Translation, documentation-pair/link and bilingual
  PHPDoc-presence audits report zero issues; comment-order audit remains A5.
- A fresh all-module SQLite installation passed Composer, CLI, 14 migrations
  and HTTP startup. Linux Colima FPM and secure FPM passed language install,
  replace, module fallback and remove with read-only `src/` and `lang/`, writable
  data and the declared configuration layer. Secure FPM also used a read-only
  lower configuration layer. No persistent service was registered.
- Playwright CLI on a separate synthetic installation verified JSON upload,
  activation, locale switching, disable/enable, exact flag HTTP 200 and removal,
  including removal while that locale was selected. Only JSON/SVG data changed.
  Screenshot: `output/playwright/a3-language-installed.png`.
- HFC home HTTP **200**, **14 applied / 0 pending** migrations; Auth provider
  configuration checksum unchanged. No backup of the development site was made,
  as requested; private provider settings were not replaced.
- The final complete isolated SQLite browser/API/performance rerun passed
  **80/80**, **18.4 minutes**, exit **0**, with the runner's final success summary.
  An initial run passed 79/80 and exposed an old test treating Bootstrap's accessible clipped
  1px upload input as an ordinary visible file control. The check now verifies
  clipping separately; no product behavior was disabled to satisfy the test.
- The three owned SQLite installations and the FPM input copy were removed
  without following vendor/module symlinks. The private FPM/test HTTP processes
  and browser session were stopped. Reports, screenshots, code and HFC runtime
  language data remain; disposable test data has no backup.

**Earlier handoff:** A5 was open after A1–A4; its completed record is
now below. Release publication was authorized on 12 October 2026. Full Confluence-site export support is
informational and deferred; no such importer extension was implemented here.

## A4 completed locally — 12 October 2026

[Current Docker instructions](docker_en.md) cover builds, volumes, initialization,
updates/migrations, workers, password rotation and the optional SAML overlay.
Implementation is in `docker/`, `scripts/build_container_context.php`,
`src/Deployment/`, the Setup/Composer/module/updater boundaries and isolated
`tests/src/Deployment/` / `tests/Fixtures/container_probe.php` tests.

- Sanitized ARM64 PHP 8.4 Apache image, no runtime Composer, read-only code/root,
  UID/GID 33, dropped capabilities/tmpfs. One private config/data/SAML volume,
  direct data subpath mount, separate PostgreSQL 18 volume and non-superuser app role.
- Final image clean installation: 14 migrations, fictional administrator, theme
  and ten guide pages. CLI one-time installer preparation and browser opening passed.
- Replacement/recreation preserved user/pages, synthetic DB row, attachment bytes,
  exact Auth hash and browser session. Setup GUI module disable/enable passed.
- Deliberate migration failure retained the previous ID and blocked serving;
  corrected image applied exactly one migration. Unsafe image downgrade was rejected.
  No automatic DDL rollback, multi-replica coordination or automatic writer stop is promised.
- PostgreSQL workspace archive export/merge restore and application-config language
  archive roundtrip passed; installed locale catalogue bytes remained exact while
  packaged `lang/` stayed read-only. CLI empty email worker passed without external mail.
- Sanitized SimpleSAMLphp 2.4.4 mount loaded with external private config/sessions/cache;
  fictional SP metadata returned HTTP 200. Real container SP/IdP registration/login
  remains an operator deployment check, not a result asserted here.
- First missing Auth document now initializes in the final declared layer, not
  the read-only base. Existing files/SSO secrets remain unchanged. Auth/file and OIDC
  flow checks: **16 tests / 208 assertions**. Standalone updater is **1.0.7**.
- Application: **262 tests / 6,086 assertions**, three platform skips. Deployment:
  **6 / 26**. Both static analyses, scoped style and documentation/translation/
  PHPDoc-presence audits passed. Full SQLite browser/API/performance: **80/80**,
  **17.4 minutes**, exit 0 with the wrapper's final success message.
- HFC remains classical/current: HTTP 200, 14 applied/zero pending migrations,
  unchanged private Auth SHA-256. Framework was not modified. Separate EN/HR guides
  reflect current behavior. No HFC backup, remote update, tag/push/release occurred.

Evidence is in `build/a4/` and `output/playwright/a4-immutable-setup.png`. Disposable
stacks/volumes and build staging are removed after checks; the local development
image remains. A5 was the next local correction and is recorded below. Release
approval, published package/image and vulnerability verification, and real
HTTPS/SSO deployment checks remain separate publication/deployment gates.

## A5 completed locally — 12 October 2026

A1–A5 are complete for the development candidate; release approval is still required.

### Changes

- Normalized marked PHP comments/PHPDoc across the application and all 18 public
  modules to separate EN then HR blocks. Mechanical edits require identical
  executable-token hashes and preserved annotation signatures. JavaScript and
  CI/shell/config comments were also normalized: 33 files / 239 comments, with
  JavaScript parser checks confirming unchanged executable tokens.
- Added `src/Tools/BilingualCommentRules.php` and dry-run-by-default maintenance
  tool `scripts/normalize_bilingual_comments.php`. Extended
  `scripts/audit_bilingual_phpdoc.php` to reject mixed/reversed marked comments
  throughout source/tests/templates; retained production method-documentation
  scope, included Accessibility and removed the arbitrary PHPDoc length limit.
- Extended `scripts/audit_module_documentation.php` with Accessibility,
  README/file links, heading anchors, complete PHP/JSON example syntax and ordered
  bilingual JSON example descriptions. Three new regression suites cover these
  tools. [Quality instructions](documentation-quality_en.md) explain their limits.
- Fixed stale links/incomplete examples and clarified generic host configuration
  paths/override layers. Added complete Accessibility bootstrap/layout and
  GUI-independent Auth API-key examples. Introductory services no longer log
  request bodies. Historical release files have explicit notices and are grouped
  separately in README/index. Current instructions reflect completed audit work.

### Verification

- PHP lint: **1,245 files / zero errors**. Comment, documentation/example/link and
  translation audits: **zero issues**. Final normalizer dry run: **zero changes**.
  The archived final PHP batch retains 679 matching executable hashes; earlier
  guarded batches were checked before writing as well.
- New regressions: **10 tests / 64 assertions**. Full application: **272 tests /
  6,150 assertions**, the same three platform skips. All 18 module suites:
  **1,113 tests / 9,576 assertions**, no skips. Configured production PHPStan and
  style pass for all modules; application production/development analysis and
  source/test/tool style pass.
- Full isolated SQLite browser/API/performance: **80/80**, **18.1 minutes**,
  exit 0 and final wrapper success. Later asset edits changed only parser-verified
  comments. The Auth CLI example ran on the synthetic installation and its key
  authenticated as the expected administrator. No token was printed in reports
  or retained. The owned installation was removed without following module/vendor
  symlinks; reports remain in `build/a5/`.
- HFC home/login: HTTP 200; **14 applied / zero pending migrations**; unchanged
  private Auth configuration hash. No HFC backup, schema/Framework change, remote
  update, commit/push/tag/release occurred.

**Next:** release approval, final published package/image builds, release CI and
deployment checks. Older database-matrix/A4 image evidence retains its dates;
A5 does not claim a fresh production database matrix or published-image vulnerability scan.
