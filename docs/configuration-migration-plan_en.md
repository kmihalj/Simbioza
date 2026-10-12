# Unified Simbioza and module configuration plan

[Croatian version](configuration-migration-plan_hr.md)

Created: 9 October 2026. Updated: 12 October 2026.

**Current continuation:** the configuration upgrade below is historical.
Use [audit corrections](pre-release-audit-0.3.0_en.md#corrections-in-execution-order):
A1/Auth, A2/Notification, A3/runtime languages, A4/immutable deployment and
A5/documentation/comment consistency are complete locally. Next is explicit
release approval, followed by published-artifact and release CI verification.
Do not resume old migration steps or the deferred full Confluence-site import work.

**New release boundary:** 0.3.0 is a fresh-install initial release. Older Simbioza upgrades and backup compatibility are no longer requirements. Continue with [the clean 0.3.0 checklist](initial-release-0.3.0_en.md). Previous details and verification below are historical, not current installation instructions.

This document is the persistent continuation point between development
sessions. Work proceeds one verifiable task at a time, not across every module
at once. The plan describes the agreed target, not a claim that every component
already uses the new paths.

## P3 and P4 completed locally — HTML Editor and Workspace

Global settings use `HPH_CONFIG_PATH/editor-html/editor-html.json` and
`HPH_CONFIG_PATH/workspace/workspace.json`. Standalone Editor behavior and
Workspace runtime overrides remain intact without writing those overrides back.
Global Workspace homepages moved out of the database; content, ACL and personal
choices remain there. Only application upgrades export legacy installation
settings. Generic Editor administration is optional; Workspace retains its
Simbioza GUI. Legacy Simbioza backup records were verified against current JSON
without recreating obsolete configuration tables. This does not confirm complete
portability of arbitrary absolute URLs; that gate remains in P10.

## Resume here

- **P10 remains open only for authored cross-site link portability.**
  Local configuration, installation and upgrade verification is complete:
  full application/module quality checks, the 21-case SQLite matrix,
  PostgreSQL/MySQL/MariaDB clean all-module installs, six Linux/root checks,
  real standard/secure FPM and isolated Apache/mod_php checks pass.
  Full browser/API/performance suites pass on SQLite, PostgreSQL and MySQL,
  **80/80 each**. Task-owned test installations and databases were removed.
  Authored links have a confirmed portability defect, detailed below; do not
  close P10 or claim complete cross-site restore yet. No release is authorized.
  See [P10 verification evidence](#p10-verification-evidence).
  P9 is complete locally: Comment, Task and Notification retain necessary
  document/user data in the database; Accessibility retains browser preferences.
  There are no additional global policies to migrate or empty module config
  directories to create. Shared configuration/module selection follows
  `HPH_CONFIG_PATH` and overrides without a settings GUI. Read-only standalone
  integration, domain state and existing browser/API/backup flows pass.
  P8 is complete
  locally: Backup uses `HPH_CONFIG_PATH/backup/`, API uses `api/api.php`, and
  both honor external roots and overrides without a GUI. Audit uses shared app
  settings; ORM keeps shared `database.php` at the configuration root.
  Auth's optional provider-verification setting was checked without repeating
  its migration. P7R is complete locally:
  filesystem HTML, attachments/history, private themes and explicitly owned menus
  use workspace/page data directories. HFC remains on filesystem HTML storage.
  Both storage-driver transitions and new Confluence imports follow the same layout.
  Its readable-label follow-up is also complete: initial labels plus stable IDs/UUIDs,
  updater directory moves and DB/index alignment, verified duplicate cleanup and
  separate recovery storage for unknown old Editor files.
  See [workspace data storage](workspace-data_en.md) and the P7R evidence below.
  P7 Menu and Theme are complete
  locally: their PHP/JSON settings use `HPH_CONFIG_PATH/menu/` and `theme/`;
  GUI and valid manual edits share the same targets. Current/legacy backup
  import uses destination paths and exact rollback.
  P6 Calendar is complete
  locally: active configuration is `HPH_CONFIG_PATH/calendar/calendar.php`,
  with unchanged GUI and domain database. P1A–P1D, P2 Email, P3 HTML Editor and
  P4 Workspace are also complete locally. D3 standardized bilingual comments
  and examples as complete English blocks followed by complete Croatian blocks.
  The updater and application settings backup/restore follow the shared paths.
  External-path upgrades, rollback, layered restore, and secure-FPM configuration
  generation were verified on isolated installations. P2 also used real standard
  and secure FPM in Colima. Email uses `HPH_CONFIG_PATH/email/email.json`; HFC is
  aligned and existing SMTP settings were preserved. Evidence and limits are below.
- P3/P4 passed full module/application tests, browser/API checks and real standard
  and secure FPM in Colima. HFC is aligned and now has
  **45 migrations applied / 0 pending** after the readable P7R follow-up.
  The obsolete Email PHP template is removed; Auth/Email/Editor/Workspace have
  documented JSON examples.
- Languages, lifecycle configuration reads,
  diagnostics, and the Setup worker follow the shared paths. HFC's module selection lives in
  `config/modules.php`; the original contents and other private settings were preserved.
- V1 is resolved: full PHPUnit and production/development PHPStan checks pass.
  Platform-specific root/Linux checks pass in isolated Colima containers,
  including the background deployment worker; current coverage is recorded below.
- Auth's configuration work is implemented locally; on 9 October the user
  confirmed that HFC works correctly. This does not confirm a published release
  or the final installation matrix.
- Current introduction: [framework parameters, shared paths, and module configuration](configuration_en.md).

To resume, read this section, the open list, and the next task's details. Do not
repeat completed items unless a regression is found. Check the actual files
before relying on this record.

## Goal and boundaries

1. All modules and application workflows use `HPH_CONFIG_PATH` for active
   installation configuration. Package templates and service code remain in
   their repositories; content, attachments, logs, caches, and temporary jobs
   are not configuration. Each module owns a subdirectory:
   `HPH_CONFIG_PATH/<module>/<file>`, for example `calendar/calendar.php`.
   Root-level application and Framework files remain separate from module files.
2. Manual configuration and any GUI that edits configuration use the same
   source and services. Respecting `HPH_CONFIG_PATH` is mandatory; disabling the
   GUI in every module is not. Generic modules hosted on opencode.hr must not
   depend on Simbioza's GUI for installation configuration. Optional
   administration is introduced only where it makes sense; existing interfaces
   for calendars, content and domain data remain. When an administration switch
   exists, it must not disable module operation, CLI, API or SSO callbacks, and
   must protect direct administration routes, not merely hide menus.
   Simbioza-specific modules hosted on GitHub retain their application
   integration and GUI; standalone operation is not a requirement.
3. There are no duplicate authoritative sources for a setting. The database
   retains domain data and necessary identities/relationships; any permitted
   configuration projection has one source of truth.
4. Do not modify HeartPhrame Framework, including its `vendor` copy. Use the
   existing `HeartPhrame\Config\ConfigPathResolver::fromEnvironment()`.
   Generic modules must not acquire mandatory Simbioza, Auth, or GUI dependencies
   merely to read or write configuration.
5. Preserve existing private settings. `.dist` is an example, not a replacement
   for active configuration. Never record secrets, private URLs, or real config
   files in the plan, test reports, or Git.
6. Legacy settings conversion is a separate upgrade. Fresh installations create
   the current state directly, without legacy export or schema cleanup.
   The agreed source-release boundary is **0.2.12 or older**. Newer releases
   already use the target layout and must not repeat legacy moves or cleanup.
   Updater handoff must retain the original source version, run the updated
   updater first, and verify data/configuration at the destination before
   removing the old files, columns or tables. Cleanup includes only obsolete
   settings, never the necessary users, groups, relationships or content.
7. HFC is the integrated development installation and receives current code
   after a task is completed. The user explicitly waived HFC backups for this
   development; required existing settings must still be preserved. The user
   updates `apps-test` and the demo; this plan does not authorize changing them.
8. Code comments, method documentation and bilingual example descriptions use
   one complete English block first, followed by one complete Croatian block.
   Do not alternate translated lines. README and documentation files remain
   language-separated and are updated with each behavior change.
9. Commits, pushes, tags, and releases are not automatic steps in this plan.
   Publication requires a separate user decision and final verification.

## Progress tracking rule

- Keep only unfinished tasks in the open list, with stable identifiers.
- Once a task meets its completion criteria, remove it from the open list and
  add a completion-log row: date, changed repositories/files, checks actually
  performed, HFC state, and any limitations.
- Update both language versions and the “Resume here” section together.
- If a session stops during a task, record completed work, unverified work, and
  the exact next small step. Do not mark a task complete because the session or
  token allowance ended. Do not report a planned check as already performed.
- Local implementation, HFC verification, and release publication are separate states.

## Open to-do list

- [ ] **P10:** complete authored-link portability and its final verification.
  - [x] Local configuration, installation/upgrade, platform and quality checks.
  - [x] Full browser/API/performance suites: SQLite, PostgreSQL and MySQL, 80/80 each.
  - [x] Align HFC module runtime and documentation; remove task-owned test installations.
  - [ ] Distinguish and remap internal HTML/Workspace link URLs across domains,
    base paths and slugs, preserving deliberate external links and legacy archive import.
  - [ ] Verify the remaining URL behavior and rerun the affected suites.

## Task details

### P7R completed locally

Current layout: `data/workspaces/<storage-slug>--<ID>/pages/<page-label>--<document-UUID>/content/`,
`attachments/` and `menus/`; workspace menus and private themes use
`data/workspaces/<storage-slug>--<ID>/menus/` and `theme/`. Explicit ownership selects the
directory; standalone/shared documents retain the configured Editor roots.
Database HTML stays in the database. Generic modules remain usable without Workspace.

`20261010220000_organize_workspace_data` and the readable-label follow-up
`20261011120000_readable_workspace_data` are one-time application upgrades for
original source releases 0.2.12 or older. Fresh installations stamp it without
loading legacy move code. Labels are ASCII, limited to 48 characters and paired
with stable IDs/full UUIDs. `workspaces.storage_slug` retains the initial label;
existing page file references retain their label on rename or trash/restore.
Verified old duplicates are removed; other unreferenced default Editor files move
to `data/recovered/editor-html/`, not active content. GUI/CLI updater, storage-driver conversion, new
Confluence imports and backup/restore use the current layout. Destinations are
checked before moves; restored owners are remapped before paths are written.
See [workspace data storage](workspace-data_en.md) for the contract and the
completion entry below for performed checks. Later tasks must not repeat this migration.

### P8 completed locally

Backup policy and optional host-provider registrations use
`HPH_CONFIG_PATH/backup/backup.php` and `backup/backup-providers.php`.
API policy uses `HPH_CONFIG_PATH/api/api.php`. The unchanged Framework loads
their nested trees; module adapters normalize only their own policy. Maps merge
and lists replace, including empty lists. The last existing host-provider
descriptor wins as a complete file; package provider descriptors remain package
code. Missing or unreadable declared layers fail instead of silently falling back.

Audit has no separate installation configuration: it uses `app.timezone` and
`app.logs`; audit records remain in the database and technical logs remain data.
ORM and Framework Database share `database.connections`, so
`HPH_CONFIG_PATH/database.php` deliberately remains a root-level shared file,
not a duplicate `orm/database.php`. Auth's `login.require_provider_verification`
remains optional for generic hosts; no new GUI switch or Auth migration was added.

`20261011000000_move_backup_api_configuration` is an application-only upgrade
for original source releases 0.2.12 or older. It verifies preserved PHP values,
layers, permissions and ownership before removing old files. Updater handoff
performs the move before new-module bootstrap; release defaults cannot replace
private settings, and failure recovery restores the old paths. Fresh installation
records the migration without loading legacy move code. Separate current examples
are `config/backup.php.dist` and `config/api.php.dist`; they are templates to copy
to the nested active paths, not active root configuration.

### P1A — completed local foundation

Implemented as `src/Configuration/ConfigurationPaths.php`, registered in
`config/services.php`, and used by the normal HTTP entry point. Current behavior
and the API are documented in the [configuration guide](configuration_en.md).
The following items remain recorded scope and verification criteria, not open tasks.

Scope: only Simbioza's path contract, an application helper, and isolated tests.
Do not move settings, alter the database, or implement Email in the same task.

1. Inspect `bootstrap.php`, `framework.php.dist`, `public/index.php`, and the
   existing framework resolver. Preserve non-empty process environment priority
   over private `framework.php`, and the default application `config/` directory.
2. Establish one application mechanism for obtaining the base directory, ordered
   `HPH_CONFIG_OVERRIDE_PATHS` layers, and individual file paths. Do not copy the
   framework resolver into a new custom parser.
3. Define layered reads and GUI/CLI write targets: never write to a lower-priority
   file overridden by another layer. Read-only layers must work without the GUI;
   saving must clearly report the restriction.
4. Retain existing formats in this first task. During each module change,
   separately agree its writable data configuration, preferably JSON like Auth,
   apart from executable PHP code. Avoid framework name collisions such as
   `auth.php` and an `auth/` directory in the same configuration layer.
5. Test the default path, an absolute external directory, layer priority,
   inaccessible/invalid paths, and matching HTTP/CLI rules. Use isolated fake
   settings, never HFC secrets.

Completion criterion: the helper and focused tests pass, default-path HFC
continues working, and no active setting was moved or overwritten. Record
actual new file names and executed checks in the completion log.

### P1B — completed local installation and module selection

- `src/Installation/InstallationPaths.php`, `InstallationConfigWriter.php`,
  `InstallationRunner.php`, and the installer part of `public/index.php` use the
  same path contract before full application initialization.
- Move authoritative administrator selection from `data/config/modules.php`
  into the configuration directory; adapt `src/Module/ModuleStateStore.php`
  and startup loading. Completed-job records and the installation lock remain
  runtime state, not configuration.
- Preserve selection during upgrades; read old locations only during an explicit
  transition, not as permanent secondary sources. Prepare a complete required
  configuration before activating an external directory.
- Fresh installation, existing configuration, and GUI/CLI module switches were
  tested. Updater integration of the upgrade transition is completed in P1D.

Implemented and checked as recorded below. The module-state permission check in
`SetupDiagnostics.php` was adapted in this task because the existing GUI requires
it; the other diagnostics and lifecycle paths were completed in P1C.

### P1C — completed local languages and diagnostics

- `src/Localization/LanguagePackManager.php` and `RepositoryLanguageManager.php`
  use shared paths for the complete highest-layer `languages.php` registry and
  merged `installation.php` activation settings. GUI/CLI changes serialize on
  `.languages.lock` and write only the final declared layer with mode `0660`.
  Translation resources and repository revision state remain in application data.
- `PhpConfigurationReader::readDocument()` and startup in `config/app.php`
  preserve registry deletions instead of resurrecting locales from lower layers.
  Unrelated language-file changes and private HFC settings remain untouched.
- `ModuleLifecycleManager.php` reads the effective layered editor storage setting
  for Confluence-removal protection. `SetupDiagnostics.php` checks actual paths
  and locks without displaying secret contents or creating configuration files.
  Language activation has its own GUI permission decision.
- `scripts/setup_worker.php` reads layered `setup.php`. Diagnostics reports the
  gateway's actual request directory. Default-path secure FPM runtime files now
  include `languages.php` and `.languages.lock`; external path generation is
  completed in P1D, while the full service matrix remains P10.
- External-layer, read-only, HTTP/CLI, OPcache, and Linux/root checks passed as
  recorded below. HFC retains its default config directory; external settings
  are tested on isolated installations rather than relocating HFC's private settings.

### P1D — completed local updater and application configuration backup

- `update.php` version `1.0.2` reads native framework configuration layers before
  full application bootstrap, preserves updater self-update priority, and protects
  private layers from release synchronization and code archives.
- The explicit module-state transition runs after dependency checks and before
  application preflight. Identical targets keep their inodes. Rollback uses the
  shared module lock, temporary hard links for ownership, and separate original-byte
  copies for recovery; concurrent edits stop rollback without overwriting them.
- The application backup factory resolves trusted local definitions into active
  layers. Dynamic `app.php` remains code, including with old descriptors. Archives
  cannot choose destination paths; only allowed settings are restored to the final layer.
- The reusable Backup provider supports native layered reads, complete documents,
  optional host-provided locks, atomic writes, ownership/mode preservation, and
  abort/complete cleanup. It needs neither Simbioza nor its GUI.
- Secure-FPM pool/helper generation pins the same configuration parameters and
  creates lower-layer read-only/final-layer writable sandbox bindings without
  taking ownership of external directories. The worker uses the early bootstrap
  even in a sanitized environment. Full service execution remains a P10 check.
- HFC uses the updated application and Backup module in place; its private
  configuration remains at the default path. apps-test, the demo, and the
  framework were not changed. Verification evidence is recorded below.

### P2 — completed Email

First individual module after the foundation. Scope includes SMTP, sender,
delivery policies, and worker options; `email_outbox` remains in the database.

- Generic `EmailConfigurationStore.php` uses the unchanged framework resolver
  and ordered `email/email.json` layers. It requires neither Simbioza code nor
  legacy application `email.php`; package `config/email.php` contains defaults only.
- Manual configuration, GUI, CLI, and workers share one source. Writes target
  only the last declared layer, with locking and atomic replacement, preserving
  permissions/ownership, unknown options, and the newest hidden password.
  Invalid/unreadable configuration never silently activates another source.
  Read-only configuration allows operation without GUI writes.
- `gui.enabled` and `gui.test_enabled` protect direct routes as well as menus.
  The template is `heartphrame-module-email/config/email.json.dist`; separate
  guides live in the module's `docs/configuration_en.md` and `docs/configuration_hr.md`.
- Simbioza upgrade `20261010140000_file_email_configuration.php` exports and
  verifies existing settings before removing unchanged legacy files. Fresh
  installation records the upgrade without loading export code. The generic
  module has no bootstrap legacy migration. Backup uses the same JSON layers,
  lock, and local validation; configuration restore does not restore the outbox.

### P3–P9 — individual changes

| Task | Changes | Data retained separately from installation configuration |
| --- | --- | --- |
| P3 HTML Editor | Global options and configuration reads/writes; preservation and template | Documents, versions, attachments, included content, and ACL |
| P4 Workspace | Fixed PHP configuration; public/authenticated global homepages and personal-selection permission | Workspaces, pages, permissions, personal homepages, and private workspace themes |
| P6 Calendar | Read calendar/calendar.php through HPH_CONFIG_PATH and override layers; upgrade-only move of existing root PHP, no GUI or format changes | All calendar data, including system and personal event types, stays in the database |
| P7 Menu | Global PHP/JSON configuration under HPH_CONFIG_PATH/menu | Owned definitions in workspace/page menus directories, with a small shared data/menu/contexts.json index; release-check cache and job results |
| P7 Theme | Global options and active JSON stores; preserve customizations | Theme files, images, and other theme assets; private workspace-linked themes |
| P8 Backup | Completed: layered backup/backup.php and backup/backup-providers.php; generic reads without GUI | Jobs, upload sessions, and archives |
| P8 API, Audit, ORM | Completed: API api/api.php adapter; Audit shared app settings; ORM shared root database.php, without duplicate configuration | User keys/subscriptions, deliveries, audit records, and migrations |
| P8 Auth | Checked optional provider verification and external configuration; no additional migration or mandatory GUI change | Users, groups, stable IDs, and necessary relationships |
| P9 Comment, Task, Notification, Accessibility | Completed: no separate global settings; shared host paths and module selection verified without settings GUI; EN/HR guides and regression tests | Comments/document-draft commenting choices, tasks, notifications, and personal adjustments |

For each row separately: confirm actual scope before editing, preserve settings,
adapt participating backup/updater paths, add a template only where agreed and documentation,
verify generic module configuration without Simbioza's GUI, then align HFC.
Do not automatically add a GUI switch to every module. Workspace retains
its application GUI and only changes configuration storage. This table
describes task scope; it is not a second checklist to mark complete. P7 Menu/Theme
deliberately has no `.dist` examples; its existing GUI remains.

### Simbioza modules kept unchanged

The user narrowed the scope on 10 October: P5 Simbioza User, Workspace Search and
Confluence Import remain application-specific modules with their existing GUI.
Do not refactor their working behavior for standalone framework use or optional
administration. Workspace remains application-specific too; P4 changes its
configuration location and participating backup/upgrade paths, not that boundary.
The earlier P5 identifier is retired rather than renumbering later tasks.

### P10 — final verification

- External `HPH_CONFIG_PATH`, default path, and supported configuration layers;
  HTTP/CLI, writable configuration, and read-only configuration.
- Fresh installation and upgrades: removed settings tables are not created on
  fresh installs, and legacy tables are cleaned only after verified export.
- Supported databases and installation modes: mod_php, FPM, isolated/secure FPM,
  demo mode, and minimal module combinations, using the existing isolated matrix.
- Focused checks per task; finally run the full quality and E2E suites using
  current project scripts. `composer on-commit`,
  `php scripts/verify_clean_install_matrix.php`, and `composer e2e` do not replace
  each other. Distinguish passed checks from checks not run.
- When migrations change, apply HFC migrations before GUI verification and
  confirm zero pending migrations, without making the backup the user waived.
- Before publication, release changed reusable modules, including the P1D
  Backup provider, then align Simbioza's dependency constraints with those releases.
- Remove task-owned temporary test installations. Do not declare a release
  ready before verification; publication remains a separate user decision.
- Verify Simbioza archive import between sites with different domains, base paths,
  IDs and slugs. Managed asset/include references and Workspace configuration IDs
  are remapped, but arbitrary absolute HTML links and Workspace link-node URLs
  are not currently guaranteed to move. Internal and deliberately external URLs
  must be distinguished; do not silently rewrite every URL. This is a separate
  portability check, not a Confluence import change.

### P10 verification evidence

Checked on 11 October 2026 using unpublished local module sources and the tagged
Framework dependency. Production dependency metadata, apps-test and demo were
not replaced. HFC's changed module runtime files and documentation were aligned
with local sources without replacing its dependency installation. Existing development changes
were preserved. HFC remains at 45 applied migrations / 0 pending, with HTTP 200
and a read-only payload audit reporting 1476 filesystem HTML records and 10325
attachment/history records, zero missing files and zero wrong owners.

| Check | Confirmed result |
| --- | --- |
| Full application `composer on-commit` in isolated QA copy | Passed: strict autoload, PHPCS, Rector dry run, production/development PHPStan, documentation/translation audits; 302 PHPUnit tests, 6202 assertions, six platform-specific skips |
| Full quality checks of changed modules | Passed: Auth 86/600, Email 12/70, Backup 54/191, Menu 65/368, Theme 36/758, API 44/210, Calendar 85/677, HTML Editor 211/1462, Workspace 134/989 (tests/assertions) |
| Skipped Linux/root paths on real Colima Linux as root | Passed separately: six tests, 103 assertions, including deployment worker, GUI updater ownership, module rollback, configuration and Workspace imports/moves |
| Minimal local clean-install matrix with SQLite | All 21 combinations passed |
| Clean all-module install with PostgreSQL 18, MySQL 8.4 and MariaDB 11.4 | Passed on separate disposable databases |
| Real standard and secure FPM | Passed: highest-layer writes, manual file edits, read-only rejection, preserved inode/ownership, separate writable data and protected code/lower layers |
| Isolated Apache 2.4 mod_php | Passed: default/external/override paths and read-only writes; demo-style 0700 directories with umask 0077 remain usable by the runtime owner |
| Default FPM filesystem finalization on Colima Linux | Passed with a synthetic root/www-data fixture: atomic Backup descriptor writes, protected code and release PHP settings, no recreated `data/config`; no account/service changes |
| Full browser/API/performance suite | SQLite 80/80 (18.4 min), PostgreSQL 80/80 (25.8 min), MySQL 80/80 (19.1 min); initial failed/interrupted runs are not counted as success |
| Delayed post-restore job-response regression | Passed separately on MariaDB: the GUI waits before announcing success, and subsequent login works |
| Cross-site backup data/configuration identity mapping | Existing isolated archive fixtures cover distinct destination IDs/slugs, both HTML drivers, legacy layouts and exact rollback |
| Cross-site arbitrary HTML and link-node URLs | Confirmed open defect: managed assets rebase, absolute source links remain unchanged, ordinary root-relative Workspace links do not receive the destination prefix, and link-node `target_url` is copied unchanged |

P10 repaired local test dependency resolution: transitive development aliases
are permitted only in generated local manifests, with stable dependency
preference. `scripts/build_ci_composer.php --local` uses the same allowlisted
module repositories and accepts `HPH_LOCAL_MODULE_ROOT` for a separate QA copy.
`tests/src/Tools/LocalMatrixRepositoriesTest.php` covers this boundary.
Production Composer metadata and the upstream Framework remain unchanged.

The first parallel E2E attempts shared Playwright artifact paths and the host's
PHP 8.5 opcode cache crashed natively while compiling generated fixture files.
The test runner now separates artifacts by database driver and disables opcode
caching only for its temporary PHP server. Final functional/browser repeats
passed on all three databases. Production caching is unchanged.
Quality-only formatting also aligned the changed application/configuration tests
with the existing PHPCS/Rector rules; it did not migrate active HFC settings.

P10 also fixed a real Backup GUI restore race. Job polling is now single-flight,
paused and drained before restore, and its final refresh finishes before success
is published. This prevents a late job response from replacing the session cookie
during the user's next login. The browser regression deliberately delays that
response; authentication and CSRF rules were not weakened. The first regression
test needed a waiting route cleanup to avoid cancelling its own pending handler.

Cold multi-step browser flows now have explicit workflow deadlines, without
changing request-performance budgets. The homepage-policy fixture restores its
original JSON in `finally`; an earlier MySQL timeout left a custom homepage behind
and invalidated a later home-route performance measurement. The affected full
suite passed on a fresh database; the earlier run is not counted as passed.

The final FPM review removed obsolete `data/config` creation, legacy per-module
PHP permission targets and a dead mutable-directory loop. Default-path Backup
configuration now receives the runtime directory rights needed to publish its
provider descriptors. `FpmConfigurationPathsTest` guards current paths, and the
real filesystem finalizer was executed on a synthetic Linux root as root/www-data.
External layers remain administrator-managed; their permissions were not changed.

The initial ad-hoc mod_php default-path probe incorrectly loaded HFC's bootstrap
instead of its own isolated copy, overwriting only Theme and Menu settings.
Both files were restored byte-for-byte from the earlier QA fixture. The corrected
probe uses its own bootstrap and rejects paths outside its temporary directory;
all four modes passed again, with HFC settings unchanged afterwards. No database
or content payload was modified by this probe.

Final cleanup removed the isolated QA copy, completed/failed clean-install and
E2E application fixtures, synthetic FPM/mod_php fixtures and all three P10
database containers with their disposable volumes. Existing host services,
Colima itself, development databases, private HFC settings and user data were
not removed. Small logs/reports remain under ignored `build/p10/` and
driver-specific test/report directories. The empty obsolete HFC `data/config`
directory was removed; no files were stored there.

These synthetic platform checks do not claim a new live sign-in/callback/logout
round trip against every external SSO provider. Existing HFC provider settings
were preserved. Releasing reusable modules, aligning Simbioza's constraints and
verifying remote tagged sources remain separate publication work.

### Next P10 step — authored link portability

The focused formatter probe is retained in `build/p10/link-portability.json`.
With source base `/old` and destination `/new`, a managed asset becomes
`/new/editor-html/asset/test-uuid`, but an ordinary link becomes
`/workspace/upute` without the destination prefix. An absolute source link also
keeps its old domain/path. `WorkspaceScopedBackupProvider` exports/imports
`target_url` unchanged. This is a functional limitation, not an unrun test.
No global URL replacement or new archive URL contract was implemented in P10.

The next small task is to define trusted source-origin/base-path metadata for
new archives and an explicit source hint for old archives where necessary;
identify managed internal routes using the import identity/slug mappings,
preserve query strings/fragments and deliberately external links, and test both
HTML drivers, differing domains/base paths/slugs, legacy archives and rollback.
Recheck affected Backup/Editor/Workspace suites afterwards. Do not repeat
completed configuration or data migrations merely to resume this work.

## After configuration migration: Docker and SimpleSAMLphp

This records a future requirement, not current P2 implementation or a claim
that Docker installation is ready. Traditional installation remains supported.

- SimpleSAMLphp must be optional: local or other authentication methods do not
  require an additional SAML service or dependencies.
- Consider an optional Simbioza image variant containing SimpleSAMLphp code;
  settings, metadata and certificates must remain outside the immutable image.
- A separate SimpleSAMLphp container needs a designed integration: current Auth
  loads a local `autoload_path` and uses `SimpleSAML\Auth\Simple`. Another
  container's HTTP URL does not replace that PHP library. Verify session storage,
  cookie policy, callbacks and logout, not only a metadata URL.
- Consider an existing installation outside Docker through safe read-only
  mounting of compatible code/configuration and suitable session storage, or a
  separately designed protocol integration. Do not promise URL-only operation
  or mount an entire host directory containing secrets.
- Auth settings remain under `HPH_CONFIG_PATH/auth/`; separately plan native
  SimpleSAMLphp configuration and certificates, and sessions/cache/logs as data.
  The complete login–callback–logout flow must pass with a reverse proxy and
  secure permissions.

[Official integration documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html)
describes the local PHP library and session handling. Select the final deployment
approach and document limitations in the Docker phase.

## Completion log

| Identifier | Date | Outcome and evidence | Publication state |
| --- | --- | --- | --- |
| P10-V — local configuration/install/upgrade verification | 2026-10-11 | Full application 302/6202 and all nine changed module quality suites pass; six skipped platform cases pass on real Linux/root; SQLite 21-case clean matrix plus PostgreSQL/MySQL/MariaDB all-module installs; actual FPM/secure FPM/mod_php path and permission checks; full SQLite/PostgreSQL/MySQL browser suites 80/80 each. Fixed Backup polling/session race and stale default FPM configuration permissions; added regression tests and local QA/E2E isolation fixes. HFC aligned, 45/0 migrations, HTTP 200 and zero missing/wrong-owner payload files. Test installations removed; evidence above. | Verification subtask complete locally; P10 remains open for authored URLs; no publication |
| P7R-R — readable directories and legacy Editor cleanup | 2026-10-11 | Added Workspace `WorkspaceDataDirectory.php`, immutable `workspaces.storage_slug` metadata and unit checks. Adapted Editor payload selection, existing-version metadata reuse, Workspace menu/theme ownership and backup target-ID mapping. Simbioza adds `WorkspaceReadableDataMigration.php` and `20261011120000_readable_workspace_data.php`, fresh-install stamping, actual updater release-boundary and interruption/collision/restore tests. Updated language-separated data guides and affected module README pairs. HFC moved 1411 directories, updated 11795 references, removed 13 verified duplicates and retained 115 unknown files in recovery; 45/0 migrations and clean payload audit. Details below. | Complete locally; no commit, push or release |
| P9 — domain and personal state boundaries | 2026-10-11 | Added HFC `tests/src/Configuration/DomainStateConfigurationTest.php`; expanded Accessibility `tests/StandaloneHostTest.php` with default/external/read-only/override enable-disable cases. Updated four module README pairs and `docs/index_en.md`/`index_hr.md` or Accessibility `integration_en.md`/`integration_hr.md`; aligned those HFC vendor files. Updated Simbioza README/configuration guides and this plan. No runtime policy, empty config directory, `.dist`, data move or schema cleanup is needed. HFC settings unchanged, 44/0 migrations; checks below. | Complete locally; no commit, push or release |
| P8 — Backup, API, Audit, ORM and Auth check | 2026-10-11 | Updated Backup `BackupConfig.php`/`BackupProviderRegistry.php`; added API `ApiConfiguration.php` and adapted its consumers. Added module `backup.php.dist`/`api.php.dist`, tests and language-separated guides; documented Audit shared settings and ORM's shared root database.php. Simbioza adds `BackupApiConfigurationMigration.php`, `20261011000000_move_backup_api_configuration.php`, nested active config and examples; adapts pre-bootstrap migration, updater preservation/rollback, application-config restore, fresh install and E2E helpers. Added `BackupConfigTest.php`, `ApiConfigurationTest.php`, application migration/restore/updater regressions and `backup_api_fpm_probe.php`. HFC aligned, preserved settings, 44/0 migrations. Verification and limits are below. | Complete locally; no commit, push or release |
| P7R — workspace/page data layout | 2026-10-10 | Added Editor `EditorPayloadLayout.php`/`EditorPayloadRelocator.php`, Menu `MenuContextFiles.php`/ownership contract, Workspace `WorkspacePayloadOwner.php`/menu ownership bridge, and application `WorkspaceDataMigration.php` with `20261010220000_organize_workspace_data.php`. Adapted storage transitions, import attachment history, file/owned-menu backup providers, bundled guide installation/updating and Calendar backup HTML reads. Added integration/FPM/audit fixtures and separate language guides. HFC filesystem payload audit: 1472 owned HTML records and 10323 owned attachment records, no missing files or incorrect owner paths; 43/0 migrations. Detailed verification is below. | Complete locally; no commit, push or release |
| P7 — Menu and Theme | 2026-10-10 | Added module `MenuConfigurationFiles.php`/`ThemeConfigurationFiles.php` and portable `MenuBackupProvider.php`/`ThemeBackupProvider.php`; adapted GUI repositories, optional backup integration, component options, tests and language-separated guides. Simbioza adds `MenuConfigurationMigration.php`, `ThemeConfigurationMigration.php`, migrations `20261010200000_move_menu_configuration` and `20261010210000_move_theme_configuration`, nested host config, installation/updater/FPM paths and regression fixtures. HFC aligned with 42/0 migrations and preserved settings. Updater uses original source-version boundary 0.2.12. P7R added separately. Verification and limits are below. | Complete locally; no commit, push or release |
| P3/P4 — HTML Editor and Workspace | 2026-10-10 | Added `EditorHtmlConfigurationStore.php`, `EditorHtmlSettingsMiddleware.php`, `WorkspaceConfigurationStore.php`, `WorkspaceConfigurationIdentityTransform.php` and `WorkspaceDatabaseBackupProvider.php`. Adapted settings/storage/homepage services, backup registration, schemas and tests, with separate configuration/backup guides. Simbioza adds `EditorHtmlConfigurationMigration.php`, `WorkspaceConfigurationMigration.php`, migrations `20261010160000_file_editor_html_configuration` and `20261010170000_file_workspace_configuration`, current backup policies and FPM probes. HFC aligned, migrations 39/0; existing settings preserved. Evidence is below. | Local; no commit, push, or release |
| D2 — current examples and obsolete-file cleanup | 2026-10-10 | Removed `HFClean/config/email.php.dist`; added flat `config/auth.json.dist`, `email.json.dist`, `editor-html.json.dist` and `workspace.json.dist`, aligned with source modules. Bilingual JSON `_documentation` explains fields, layers, privacy and GUI boundaries. README/current guides remain language-separated; historical notes are marked as historical. `ConfigurationExamplesTest.php` checks all four examples using real stores. `scripts/update_bundled_assets.php` no longer reads legacy Email PHP; `PhpConfigurationReader.php` isolates executable-config variables with a regression test. Active JSON was not replaced with examples. | Local; no commit, push, or release |
| P2 — Email and real FPM | 2026-10-10 | Added Email `EmailConfigurationStore.php`, `EmailSettingsMiddleware.php`, `config/email.json.dist`, two configuration guides, and an isolated SMTP test; adapted configuration/settings/worker/manifest/GUI/tests. Backup supports validated JSON. Simbioza adds `EmailConfigurationMigration.php`, its upgrade migration, updater runtime-path protection, fresh-install skip, and FPM private-subdirectory permissions. Added real FPM/Email fixture probes; aligned HFC Email/Backup vendor copies, preserved SMTP in `config/email/email.json`, migrations 37/0. Checks are below. | Local; no commit, push, or release |
| P1D — updater and application configuration backup | 2026-10-10 | Updated `update.php`, `ModuleStateStore.php`, `config/backup.php`, `config/services.php`, both Setup scripts, and `phpstan-dev.neon`. Added `src/Configuration/ApplicationBackupConfiguration.php`, `tests/src/Configuration/ApplicationBackupConfigurationTest.php`, and `tests/src/Setup/FpmConfigurationPathsTest.php`; extended updater/module-state tests. Updated `heartphrame-module-backup/src/Service/StructuredConfigBackupProvider.php` and aligned its HFC vendor copy. README/configuration/installation/provider guides and this plan describe current behavior in separate languages. Checks and limits are below. | Local; no commit, push, or release |
| D0 — Auth foundation | 2026-10-09 | Local Auth uses `HPH_CONFIG_PATH/auth/auth.json`; profiles are nested under providers, and the template is `heartphrame-module-auth/auth.json.dist`. HFC is aligned; the user confirmed correct operation. Private values are not recorded here. | Local code; does not claim publication or the final installation matrix |
| D1 — module audit and plan | 2026-10-09 | Reviewed 18 source modules and Simbioza auxiliary paths. Confirmed database candidates: global personal-workspace policies and global homepages. Created both language versions of this plan. | Documentation; no new behavior changes |
| P1A — shared configuration paths | 2026-10-09 | New `src/Configuration/ConfigurationPaths.php`; integration in `config/services.php` and `public/index.php`; updates to `framework.php.dist`, README, and both configuration guides. Checks and limitations are recorded below. | Local; no commit, push, or release |
| P1B — installation and module selection | 2026-10-09 | Added `PhpConfigurationReader.php`, `scripts/migrate_module_configuration.php`, and `config/modules.php.dist`; adapted installation, startup, `ModuleStateStore.php`, and narrowly required diagnostics/backup/updater/FPM paths. HFC's legacy module-selection file moved without content changes. Evidence is recorded below. | Local; no commit, push, or release |
| V1 — existing verification findings | 2026-10-09 | Confirmed fresh Auth schema and upgrade-only baseline rather than running the legacy cleanup on new installs; corrected migration expectations, coverage metadata, and `$excludedNames` PHPDoc. Full PHPUnit and both PHPStan configurations pass. | Local verification; final release matrix remains open |
| P1C — languages, lifecycle, and diagnostics | 2026-10-10 | Updated `PhpConfigurationReader.php`, `config/app.php`, both language managers, `ModuleLifecycleManager.php`, `SetupDiagnostics.php`, `SetupController.php`, the Setup view/gateway/request store/worker, the default FPM runtime-file list, and `.gitignore`. Added `SetupWorkerConfigurationTest.php`, extended configuration/language/lifecycle/diagnostic tests, and isolated updater test fixtures for Colima. Updated README, configuration/localization guides, and this plan in both languages. HFC private settings preserved; checks are below. | Local; no commit, push, or release |

| P6 — Calendar configuration paths | 2026-10-10 | Updated module `CalendarConfig.php` and isolated tests; added `calendar.php.dist` and separate configuration guides. Added application `CalendarConfigurationMigration.php` and `20261010180000_move_calendar_configuration.php`; adapted fresh-install skipping, updater preservation and tests. HFC now uses `config/calendar/calendar.php`, with 40 applied / 0 pending migrations. Calendar 85/677, HFC 247/5573 with four known platform skips, Linux configuration and migration checks, static checks and full browser/API E2E 80/80 passed. Test installations moved to Trash. | Complete locally; no commit, push or release |
| D3 — bilingual comment layout | 2026-10-10 | Standardized comments and example descriptions in P1–P6 files and matching HFC module copies: complete EN block before complete HR block. JSON example descriptions are English-first. Added example language-order assertions; syntax, unchanged executable PHP tokens/JSON values and 2987 bilingual blocks checked. README and guides remain language-separated. | Complete locally; no runtime-setting changes |

### P9 verification

- Audited the four modules' services, package configuration descriptors,
  initial schemas, migration commands, optional integrations and backup
  registrations. There are no separate global installation options to export.
  Package service/API/provider descriptors remain code. ORM's shared
  `database.php` and the host's central module selection use declared config
  layers; the existing GUI stays. No framework or runtime module code changed.
- New `DomainStateConfigurationTest.php`: **3 tests / 133 assertions**.
  Default/external/layered roots use read-only files/directories. An invalid
  excluded default/lower database driver proves the effective ORM layer is
  used. Comments retain document/language draft/publication isolation; Task
  retains document scope and idempotent audit; Notification retains personal
  opt-in, owner-only read state and inbox delivery without Email. No settings
  GUI is instantiated; config bytes, file list and absence of locks/directories
  are checked after business writes. These are isolated SQLite fixtures,
  not changes to development data.
- Accessibility's real standalone host uses the unchanged framework resolver:
  default/external loading, an empty overriding module list, and re-enabling
  over a disabled base. Routes and renderer follow central state without
  Simbioza/Auth/ORM or a settings GUI. Full module suite **19 / 1381** and
  its `composer on-commit` pass; scoped PHPCS and test PHPStan also pass.
  This checks bootstrap enable/disable, not the complete install/remove lifecycle.
- Comment **7 / 35**, Task **11 / 73**, and Notification **8 / 54** pass;
  their production/development static analysis passes. Full HFC PHPUnit is
  **296 tests / 6088 assertions**, no failures/warnings/risky tests, with the
  same six root/Linux-specific macOS skips as P8. Those ownership tests were
  not rerun in Colima for this documentation/test-only step; P8 evidence is
  recorded below. HFC production/development PHPStan and changed-test PHPCS pass.
  PHPDoc/documentation/translation audits report zero issues.
- Local-source SQLite browser/API E2E: **12/12**. Includes Comment creation,
  reactions/report/moderation and Notification UI; profile e-mail opt-in;
  Task state/history and table editing; Accessibility controls/preferences;
  full-site archive creation/upload/preflight/restore and Workspace copy.
  Temporary E2E installation is removed by the runner after success.
- HFC remains **44 applied / 0 pending migrations** and local HTTPS returns
  HTTP 200 without redirects. Its configuration tree has **44 unchanged files**;
  no active setting or domain data was moved. HFC source-module runtime code
  already matched the four source repositories; updated documentation and
  Accessibility's test were synchronized only. No HFC backup, apps-test/demo
  update, Git publication or release was performed.
- P9 is removed from the open list. P10 still owns the full database/service
  installation and upgrade matrix, whole-repository quality command, complete
  E2E and remaining portability/lifecycle release checks.

### P8 verification

- HFC uses `config/backup/backup.php`, `backup/backup-providers.php`, and
  `api/api.php`; obsolete active root files are removed. Existing values were
  compared during the move. The application-backup API target was updated to
  the current nested path. HFC has **44 applied / 0 pending** migrations and
  its actual local HTTPS homepage returns HTTP 200.
- Full application PHPUnit: **293 tests / 5955 assertions**, no failures.
  The six macOS skips require root/Linux ownership or process identities.
  All six pass together in isolated Colima: **6 tests / 103 assertions**, no skips.
  The broader Linux updater/migration subset also passed **36 / 632**, with
  one unrelated bridge test skipped because that synthetic copy has no old Git tag.
- Backup **54 / 191**, API **44 / 210**, Audit **18 / 99**, ORM **95 / 864**,
  and focused Auth **19 / 149** pass. API's new adapter has 100% line/method
  coverage; that is not a claim of 100% whole-module coverage.
  Production/development HFC static analysis and Backup/API static analysis pass.
  Scoped formatting checks pass; pre-existing formatting findings remain a P10 gate.
- External roots, override precedence, replacing lists with empty lists,
  invalid policy, missing/unsearchable layers, highest host descriptor, conflicts,
  idempotent moves and unchanged relative-PHP behavior are covered. Root moves
  preserve file and new-directory ownership/group/mode. GUI and CLI updater
  fixtures verify preserved private settings and exact recovery, including a
  release carrying conflicting defaults and concurrent configuration edits.
  Old API archive records restore to the destination's current highest layer,
  preserve local-only options and roll back to exact prior bytes/mode.
- Local-source browser/API E2E on isolated SQLite passes **41/41**, including
  full archive creation/upload/preflight/restore, Workspace-copy import, API keys,
  CORS, Webhooks and audit access boundaries. Real standard and secured FPM pass
  all ten P8 probe checks each, including shared ORM config, logs as data,
  read-only secure configuration and external provider registration.
- Documentation and bilingual PHPDoc audits report **0 issues**. Five missing
  anonymous-method comments in the preceding Workspace migration were completed
  as EN then HR blocks, without changing behavior. Guides remain language-separated.
- Temporary normal/secured FPM installations and their dedicated processes were
  removed; ordinary Colima services remain running. No HFC backup, framework
  change, apps-test/demo update, Git publication or release was performed.
  The complete database/installation matrix, full E2E and combined on-commit
  verification remain P10; these targeted checks do not claim release readiness.

### P7R readable-layout follow-up verification

- HFC now has **45 applied / 0 pending** migrations. The real move renamed
  **1411 directories** and updated **11795 payload references**. The full audit
  verifies 1476 filesystem HTML records (1472 owned) and 10325 attachment/history
  records (10323 owned): **0 missing files / 0 incorrect owner paths**.
  A subsequent preflight reports zero directory moves, path changes, duplicates
  or recovery files. Public HFC Workspace pages render successfully after the move.
- Cleanup removed **13 verified duplicate HTML files** only after matching the
  same document/language/version fragment. **115 unknown unreferenced files**
  were moved, not discarded, to `data/recovered/editor-html/`. Referenced standalone
  documents, shared CSS and runtime/cache data remain in their Editor roots.
- The actual application migration is tested with original sources 0.2.11,
  0.2.12 and 0.2.13 while the target VERSION is 0.2.99. It moves eligible old
  data, skips newer-source legacy moves and is repeatable. Fresh-install tests
  stamp the upgrade without executing the legacy mover. Durable-journal tests
  cover interruptions before and after DB commit, and collisions preserve old data.
- Focused application migration/installation checks pass **38 tests / 3947
  assertions**, with one macOS root-only skip. The final layout subset in Colima
  passes **15 / 163**, no skips. Workspace's full coverage-enabled suite passes
  **134 / 988**, without risky tests; the directory helper has full line/method
  coverage. Focused Editor storage/backup checks pass **36 / 319**.
  Full application PHPUnit passes **300 / 6138**, with the six known platform-specific
  macOS skips; the root-only layout case passes separately in Colima as recorded above.
  Changed runtime static analysis and new migration formatting checks pass;
  documentation, bilingual method-comment and translation audits report zero issues.
- Browser/API verification passes **6/6** for full-site backup/restore, new
  Confluence import and all four existing performance scenarios. Workspace-copy
  import passes separately **1/1**. The initial readable-layout performance run
  exceeded the unchanged page-creation SQL budget (73 > 70); persisted-version
  metadata reuse removed redundant queries without caching ownership or relaxing
  the budget. Cross-site/ID and filesystem/database restore cases, private-theme
  target mapping, both storage transitions and old archives pass integration tests.
- Real standard and secured FPM in Colima pass owned HTML, attachment, menu and
  private-theme reads/writes using the final module sources. Root migration
  permissions pass separately. The dedicated FPM/nginx test processes and
  `/srv/simbioza-config-test` installation were stopped and removed; ordinary
  services were not changed. Browser runners remove successful temporary sites.
  No HFC backup was made, as requested; Framework, apps-test and demo were not
  changed. The full release matrix remains P10; this does not publish a release.

### P7R verification before readable labels

- HFC: **43 applied / 0 pending** migrations. Its HTML driver remains
  `filesystem`; all 1472 uniquely owned HTML records and 10323 owned attachment/
  history records resolve below their workspace/page directories. The complete
  audit also checked the unowned records: **0 missing / 0 incorrect owner paths**.
  Existing content, themes and special-menu definitions remain readable.
- The migration first exposed accumulating indentation in standalone HTML
  round trips. The formatter now preserves literal `<pre>` content and strips
  only common horizontal indentation. The actual HFC fragments passed the
  round-trip audit before the successful migration; no referenced source was
  missing after the aborted attempt.
- Editor **211/1462**, Workspace **131/977**, Menu **65/368** and Calendar
  **85/677** unit checks pass. Confluence Import **115/711** also passes.
  HFC: **281/5787**, five platform-specific skips.
  P7R integration: macOS **11 tests / 94 assertions**,
  one Linux/root-only skip; Colima Linux/root **11/113**, no skips. Tests cover
  both HTML drivers, both storage transitions, conflicting destinations,
  trash/restore, old archives, different workspace/page IDs, and exact rollback.
  Explicit menu ownership also selects contexts whose custom routes do not
  contain the workspace slug, without claiming another workspace's owned menus.
  Replace preparation can select page ownership even after its old node is removed.
  Permanent page/workspace deletion removes exact owned menu files and index
  references without Menu GUI services, preserves shared contexts and supports
  subsequent writes after owner deletion. `OptionalMenuRepository.php` supports
  the standalone cleanup regression test; the integration fixture verifies real files.
- Production/development static analysis passes for the changed modules and
  HFC. New method comments use complete EN then HR blocks; guides are separate.
- Real standard and secured FPM pass owned HTML, attachment, theme and menu
  reads/writes, including runtime ownership. Root-created layout permissions
  remain usable by `www-data`. Reproducible probes live in
  `workspace_data_fpm_probe.php` and the existing isolated FPM runner.
- Focused browser tests pass full-site backup/restore, workspace-copy import
  and new Confluence import. The actual HFC public Workspace page and all three
  displayed image downloads returned HTTP 200 after migration.
- The first full local-source browser run finished **79/80**: page creation
  exceeded the unchanged SQL budget (**78 > 70**). `EditorPayloadLayout` now
  reuses request-local document metadata without caching ownership; initial
  payloads do not search for an impossible persisted owner. `EditorPayloadRelocator`
  checks only candidate source references in bounded portable queries rather
  than scanning all payload tables. A regression test preserves files still
  referenced by another document. The focused performance suite passes **4/4**:
  creation **69**, publication **53**, public view **36** SQL queries. A final
  full local-source repeat passes **80/80** (11.4 minutes). After the final
  menu-cleanup change, focused browser tests also pass **2/2**: publication/draft/
  version isolation and resumable permanent workspace deletion. The integration
  and standalone cleanup tests use the final source state.
- An initial runner invocation omitted `--local` and mixed the development
  application with published module packages: **48/80**, with session/UI failures.
  This is not validation of the current local module set or release readiness.
  The local-source suite is repeated separately; the remote release matrix remains P10.
- No HFC backup was made, as explicitly requested. Framework, apps-test and
  demo remain untouched. Isolated FPM services and their `/srv/simbioza-config-test`
  installation were stopped/removed; normal Colima services remain running.
  Browser runners remove their temporary installations automatically. The earlier
  retained fixture and unused duplicate vendor copy were moved to Trash for recovery.
  Publication and the final release matrix remain P10.

### P7 verification before P7R

- Active global Menu files: `menu/menu.php`, `menu/top.json`,
  `menu/settings.json` under `HPH_CONFIG_PATH`; Theme equivalents are
  `theme/theme.php`, `theme/themes.json`, `theme/settings.json`. PHP layers merge
  keys; each global JSON document comes wholly from the highest existing layer.
  Writes use only the last declared layer. Package defaults remain read-only;
  no `.dist` was introduced. Invalid/read-only config fails explicitly instead
  of silently saving in a lower layer. Affected comments are complete EN then HR.
- Special menus moved to `data/menu/contexts.json` as content, not global config.
  Shared assets remain `data/themes`; private workspace themes, HTML storage
  driver and attachments are not reorganized in P7.
- Upgrade helpers verify targets/permissions and copied values before removing
  installation-owned legacy files. Equivalent targets retain bytes/inodes;
  conflicts retain sources; custom external JSON is copied, not deleted. Fresh
  installation creates current paths and records the upgrades without loading
  them. GUI/CLI updater captures the original source release, skips the legacy
  transition after 0.2.12 and rejects unknown versions. The standalone transition
  follows the same boundary.
- Menu/Theme retain schema-1 identities and accept actual old filesystem-provider
  archives. Current/old ZIPs restore to another site's config/data paths. Tests
  cover rollback, copy conflicts, unsafe filenames, missing-library read-only
  preflight and nested asset access. PHP option backup exports allowlisted values,
  not raw code or source-site paths. Theme staging retains local ownership/group;
  root CLI restore of `www-data`-owned nested assets passed in Colima.
- Tests: Menu **61/356**, Theme **36/758**, HFC full **270/5693** with four known
  platform-condition skips on macOS; focused P7 **17/99**. Subsequent updater
  regression: **56/683**, three platform skips. Production/development PHPStan
  pass for both modules and HFC. Module PHPCS and scoped new P7 application-file
  PHPCS pass. The wider application check has no errors, but still reports older
  updater/setup/audit line-length warnings.
- Real standard and secure FPM passed all six HTTP probes in Colima: highest
  writes, manual PHP/JSON edits, read-only rejection, other-owner inode retention,
  unchanged non-writable secure code/lower config. System pools were not modified.
  `run_menu_theme_fpm.sh`, `menu_theme_fpm_probe.php`, `verify_menu_theme_fpm.sh`
  and `menu_theme_backup_permissions_probe.php` retain synthetic reproducible checks.
- The full SQLite browser/API/performance wrapper finished **79/80**: P7 GUI and
  site/workspace backup/import passed; one Calendar scenario exceeded 60 seconds.
  That exact scenario passed alone in **57.6 seconds**, without Calendar code
  changes. Do not report the initial full run as 80/80; the final matrix remains
  P10. P7 FPM services/fixture were removed; retained E2E fixture moved to Trash.
- HFC has **42 applied / 0 pending** migrations. No HFC backup was made per the
  user's instruction; settings retained. Framework, apps-test and demo were
  untouched. No publication or P7R data/schema migration was performed.

### P6 completed locally — Calendar paths only

- Scope confirmed on October 10: active `calendar/calendar.php` beneath each
  `HPH_CONFIG_PATH` layer. The GUI, routes, database schema and system/personal
  event types stay unchanged. The new application upgrade moves files only;
  fresh installation records it without loading legacy conversion code.
  Package `config/services.php` and default `config/calendar.php` remain with
  code; they are not active installation configuration to relocate.
- `CalendarConfig.php` uses unchanged `ConfigPathResolver` and
  `ConfigArrayMerger`. Later layers win; invalid/unreadable or unsearchable
  layers fail without fallback. Reading neither writes nor creates locks.
- Calendar: **85 tests / 677 assertions**; production/development PHPStan,
  targeted PHPCS and Rector pass. Isolated configuration checks on Linux/PHP 8.3
  in Colima: **18 / 58**, including denied file reading and directory searching.
  Upgrade-only file migration: **7 / 25** on macOS and Colima, including
  all-target conflict checks, byte/permission preservation and idempotence.
- HFC PHPUnit including language-order assertions: **247 / 5573**, no errors;
  four previously documented platform skips on macOS. Production/development
  PHPStan and targeted PHPCS pass. Five example checks including English-first
  descriptions: **5 / 42**.
  HFC vendor is aligned; the active guide configuration moved to
  `config/calendar/calendar.php` with its PHP values preserved. No old root
  file remains. Migration status is **40 / 0**.
- Added package and HFC `calendar.php.dist`, separate module configuration
  guides and corrected the outdated menu-options example. README, application
  guides and this plan describe actual scope without universal GUI disablement.
- The earlier full browser run passed 79/80 scenarios; only login after full
  backup restore failed with invalid CSRF. Its focused repeat passed both backup
  scenarios without code changes. The final full
  `php scripts/run_e2e.php --local --keep` passed **80/80** in 10.8 minutes,
  including full-site restore, Calendar GUI/API/ICS/CalDAV and SQL budgets.
  All three task-owned isolated installations were moved to the macOS Trash
  after their servers stopped; they remain recoverable. HFC was not removed.
  P6 is closed locally. Commit, push, release, apps-test and demo were not changed.

### D3 — English-first bilingual comments and examples

- The user clarified that bilingual means one complete English block followed
  by one complete Croatian block, not alternating translations. This rule now
  applies to PHP comments, method documentation and `.dist` descriptions in the
  files changed through P1–P6, including corresponding HFC module copies.
- Auth, Email, HTML Editor and Workspace JSON example descriptions use `en`
  before `hr`; Calendar and application PHP examples use separated language
  blocks. README and guide files remain separated by language.
- Mechanical verification preserved executable PHP tokens and JSON values.
  Inspection of **2987 bilingual comment blocks** found no reversed or same-line
  mixed descriptions. PHP syntax validation passed for **250 changed PHP files**;
  configuration examples now test the language order to prevent regressions.
- Auth, Email, Editor, Workspace and Backup focused configuration tests passed
  (**6/92**, **6/23**, **9/61**, **11/41**, **6/43** respectively). Production
  PHPStan passed for all six affected modules and both HFC configurations passed.
  The Framework and private runtime settings were not reformatted.

### P3/P4 and D2 checks and limitations

- HTML Editor: **206 tests / 1384 assertions** with strict coverage metadata;
  Workspace: **130 / 973**; Backup: **49 / 180**. Email including the example
  check: **12 / 70**; focused Auth file-configuration tests: **6 / 92**.
- Application PHPUnit: **239 tests / 5530 assertions**, no failures; four previously
  documented root/Linux/POSIX tests skip on macOS. Production/development PHPStan
  pass. New and adapted P3/P4/D2 PHP passes focused formatting; the earlier Auth
  test has ten existing line-length warnings, with no new errors. `git diff --check`
  passes in every affected repository.
- Full isolated SQLite browser/API/performance suite: **80/80 passed**, including
  settings GUI, editing and backup/restore. The later config-reader regression
  and installation-updater step also passed the full application PHPUnit suite;
  browser tests were not repeated for that narrow CLI change.
- Real standard/secure FPM probes verify standalone Editor slug behavior,
  non-persistent Workspace runtime overrides, optional Editor administration,
  homepage JSON writes and preserved extension keys. A read-only final layer
  allows reads and independently rejects Editor/Workspace writes; checksums of
  every lower/upper JSON file remain unchanged after rejected writes.
- Legacy Simbioza configuration policies and Workspace homepages import into
  current JSON. Selected imported IDs are remapped while retained keys remain
  destination-local; prepare/complete/abort share locks and restore original
  state on failure. New backups export only current configuration providers.
- All four JSON templates match their package examples, contain HR/EN descriptions
  and pass real module stores. Still-current PHP templates for framework parameters,
  database, encryption and module selection remain. Legacy PHP names occur only
  as package defaults or migration/archive inputs.
- Removed owned Colima FPM installations, service, helper, sudoers/nginx entry,
  and test accounts/groups. Ordinary nginx/FPM services remain active. Two local
  isolated E2E installations were moved to the user's Trash.
- At the end of P3/P4, HFC had **39/0** migrations; P6's current count is recorded
  above. `apps-test`, the demo and framework remain unchanged. The final
  installation-mode matrix, coordinated module releases and arbitrary site-bound
  URLs remain P10 work; nothing was published.

### P2 checks and limitations

- Email PHPUnit: **11 tests, 64 assertions** on macOS PHP 8.5 and Colima Linux
  PHP 8.3 as both an ordinary user and root, without skips. A local SMTP sink
  accepted one synthetic worker message with manual configuration and GUI
  disabled; changed settings were loaded before the next run. No real mail was sent.
- Backup PHPUnit: **46 tests, 153 assertions**; JSON export/restore/abort preserves
  existing local keys, secrets, and permissions with local validation.
- Full browser/API/performance suite on an isolated SQLite installation:
  **80/80 passed**, including Email GUI and full backup/restore. The existing
  E2E wrapper removed its temporary HTTP process and installation.
- Full application PHPUnit: **219 tests, 5435 assertions**, no failures.
  Four ownership/privilege tests skip on macOS: three need Linux/root for
  GUI/CLI deployment and rollback; the fourth needs root/POSIX for imported
  attachment ownership. All four pass separately in Colima, **69 assertions**
  in total. The legacy `0.2.11` updater bridge also passes on macOS and is not skipped.
- Application/Email production and development PHPStan, Backup production
  PHPStan, focused PHPCS/Rector, and bilingual PHPDoc/module-documentation/
  translation audits pass.
- Real standard FPM and dedicated secure FPM ran as systemd services inside
  Colima's Linux VM, not just generated configurations. HTTP probes verify
  upper-layer precedence, writable data, and read-only code/lower configuration
  in secure FPM. Email reads/writes only upper-layer JSON, preserves the secret,
  and rejects writes when that layer becomes read-only. Fixed secure FPM startup
  when optional system SimpleSAMLphp directories do not exist.
- Read-only `apps-test` inspection confirmed active secure FPM, the expected
  login redirect, and SimpleSAMLphp metadata HTTP 200. No remote configuration,
  services, or code were changed. This is not a full SSO login/logout test.
- Removed task-owned standard/secure installations in `/srv/simbioza-config-test`,
  their FPM service/helper/sudoers/nginx entry, test users/groups, and the locally
  generated copy under `output/fpm-verification`. Test ports no longer listen.
  No HFC data or unrelated services were removed.
- Development HFC has current Email/Backup code, HTTP 200, and **37 applied
  migrations, 0 pending**. Legacy `config/email.php` was removed only after a
  verified transfer. Framework/demo remain unchanged; no HFC backup or release
  was made. The full multi-database/six-mode matrix and combined
  `composer on-commit` remain P10, not a result of this step.

### P1D checks and limitations

- Full macOS PHPUnit: **215 tests, 5422 assertions**, no failures. Four
  cross-identity/root Linux tests skipped on macOS ran successfully in Colima's
  updater/FPM suite: **76 tests, 774 assertions**, one skip because the container
  lacks the old `0.2.11` Git tag. That legacy-bridge test passed on macOS. This is
  not a claim that the entire Linux suite was run.
- Non-root Linux path/backup/module-state/FPM checks: **51 tests, 192 assertions**.
  The backup module's own suite: **45 tests, 146 assertions**. Production and
  development application PHPStan and backup-module PHPStan pass. Bilingual
  PHPDoc, module documentation, and translation audits report zero issues.
  Scoped PHPCS has no errors, but the standalone scripts and an existing updater
  test retain style warnings; whole-repository formatting is not claimed to pass.
- Isolated updater subprocess tests exercise CLI and GUI entry paths, external
  and embedded private config, a symlinked config root, preflight failure rollback,
  separate runtime ownership, and a concurrent in-place edit. The concurrent edit
  stays untouched; maintenance remains active and a private original-byte recovery
  copy survives. Normal jobs remove their temporary copies and links.
- Backup tests use real archive/provider code with fictional settings: layered
  export/restore/abort, highest-layer document deletion, local-key preservation,
  shared-lock rejection, read-only targets, approved directory links, old
  descriptors, file modes, and unchanged dynamic application code.
- FPM tests verify generated pool parameters and sandbox path policy. No host FPM
  service or systemd unit was installed/restarted. Actual systemd sandbox execution,
  the full six-mode installation matrix, full browser E2E, and the combined
  whole-repository quality command remain P10 checks before release.
- HFC returns **HTTP 200** with Simbioza content, registers `application-config`,
  and reports **36 executed migrations, 0 pending**. P1D adds no database migration
  and did not rerun the Auth upgrade or relocate HFC's private settings.
- Task-owned temporary installations, HTTP server, and Colima container were
  removed after verification. No HFC backup, apps-test/demo changes, framework
  modifications, commits, pushes, or releases were performed.

### P1C checks and limitations

- Full macOS PHPUnit: **198 tests, 5217 assertions**, no failures, warnings, or
  risky tests. Its three platform-specific skips were executed separately in
  Colima's Linux PHP 8.4 container: **3 tests, 43 assertions, no skips**. These cover
  runtime identity on root import, separate runtime ownership during GUI updater
  self-update, and maintainer CLI delegation to the background deployment worker.
  They are not a claim that the whole Linux suite was run.
- Focused P1C checks pass on macOS and as a non-root Linux user:
  **32 tests, 191 assertions**. The real Setup worker health-check includes six
  assertions for the shared override request directory and also passes as root.
  Test migrations and updater subprocesses now use their own app/config paths,
  not HFC paths inherited through an autoloader.
- Full production PHPStan (level 10) and development PHPStan (level 4) pass.
  Scoped PHPCS and Rector dry-run pass for the changed classes/config/tests;
  bilingual PHPDoc, module documentation, and translation audits report zero
  issues. Existing sibling tools were reused without modifying the framework or
  source-module code. The two affected scripts retain pre-existing PHPCS issues:
  `setup_worker.php` has six errors/one warning (HEAD had eight/one), and
  `configure_fpm_setup.php` has five errors/four warnings unchanged from HEAD.
  Full repository formatting and `composer on-commit` are not claimed to pass.
- Existing browser scenarios for the sticky language selector and Setup version
  checks both pass. Actual GUI disable/enable, CLI activation, a read-only target,
  and the worker's configured request directory were verified on a temporary
  external-config installation. Only the external activation file changed;
  lower settings stayed unchanged. Read-only writes failed clearly and the GUI
  hid unavailable language actions without disabling unrelated module switches.
  The registry test verifies highest-layer deletion and install/uninstall behavior;
  a runtime test verifies it with OPcache timestamp checks disabled.
- HFC migration status is **36 ran, 0 pending**; P1C needs no new migration and
  did not rerun the Auth upgrade. Comparison of all 31 config-tree files captured
  after the intentional startup-code change found no further changes. Existing
  language data and private provider settings were preserved. HFC uses the updated
  code in place but its complete configuration was not relocated.
- The task-owned GUI fixture, HTTP server/browser session, and Linux container
  were removed after verification. No HFC backup, apps-test/demo changes, Git
  publication, or framework/source-module synchronization was performed.
  The full E2E suite, six-mode installation matrix, full external secure-FPM
  provisioning, updater, and backup/restore remain outside this completed step.

### P1B checks and limitations

- Full PHPUnit: **187 tests, 5136 assertions**, no failures, warnings, or risky
  tests. Three tests were skipped because they need root/POSIX ownership or an
  isolated root Linux deployment worker unavailable on this macOS installation.
- Full production PHPStan (level 10) and development PHPStan (level 4) pass.
  Scoped PHPCS and Rector dry-run for the P1B classes/configuration/tests pass;
  bilingual PHPDoc, module documentation, and translation audits report zero
  issues. Existing sibling tools were reused; no dependencies were installed
  and framework/source-module code was unchanged.
- Fresh-install tests cover a complete external directory, missing/empty
  configuration, last-layer preflight, and unchanged existing settings. Current
  Auth tables are created directly without legacy settings tables/columns.
  Full/minimal installs record 36/23 migration baselines; a deliberately throwing
  legacy upgrade fixture is never loaded on a fresh installation.
- Module-state tests cover exact legacy transfer, conflict refusal, idempotence,
  unknown-key preservation, malformed files, symlinks, read-only targets, and
  concurrent changes. Layered PHP reads use the framework's native merger;
  module selection is a complete document from the highest existing layer.
- The existing `application.spec.js` “Setup checks” E2E scenario passes on an
  isolated SQLite installation. Actual GUI disable/enable and CLI changes were
  also verified, including a higher-priority external layer: only that layer
  changed, the lower file remained unchanged, and read-only writes failed clearly
  while the GUI disabled the unavailable action. The temporary installation,
  its HTTP process, and browser session were removed afterwards.
- HFC's old `data/config/modules.php` was explicitly moved to ignored
  `config/modules.php`, byte-for-byte unchanged, and the old file was removed.
  Repeating the transition is a no-op. Comparison of 33 existing configuration
  files found no unintended setting changes. HFC returns HTTP 200 with Simbioza
  content; CLI module listing works and migration status is **36 ran, 0 pending**.
  P1B did not run database migrations or the old Auth upgrade on HFC.
- The default backup module target, canonical-state updater guard, module GUI
  diagnostics, and isolated-FPM writable-file list were aligned only as needed
  for this step. Full external updater/backup behavior and the remaining
  configuration consumers were unfinished P1C/P1D work at that stage. P1C is now
  recorded above, as is P1D. HFC still uses its default
  configuration directory; do not claim release readiness or switch it wholesale.
- `composer on-commit` as a combined command, the full E2E suite, and the six-mode
  installation matrix were not run. No HFC backup, apps-test/demo changes, Git
  publication, or framework/source-module synchronization was performed.
- A broader explicit PHPCS sweep also found remaining formatting issues in
  `public/index.php`, `update.php`, `scripts/configure_fpm_setup.php`, and the
  earlier `AuthConfigurationBackupTest.php`. New P1B lines were aligned; this is
  not a claim that whole-repository formatting verification passes. Recheck the
  full configured scope during P10 rather than silently formatting unrelated work.

### P1A completion summary

The path-helper foundation passed 36 targeted tests and 160 assertions with
100% helper line/method coverage. HFC continued working without moving settings
in that stage. Its broader verification findings were investigated and resolved
as V1 in P1B; the current verification state is recorded above.

## Next-session handoff

- Workspace root: `/Volumes/Ext/Development/CR`; Simbioza/HFC:
  `/Volumes/Ext/Development/CR/HFClean`. Source modules are sibling repositories.
- At the plan date, the Simbioza repository is on branch `main`, HEAD `59cb9c8`.
  This is a historical reference, not an instruction to restore that commit.
- Uncommitted Auth/Simbioza-bootstrap work and other local changes exist. Inspect
  every affected repository's status first; do not reset or include unrelated
  changes in this work.
- Framework and language files have existing local changes; preserve them.
  P10 aligned changed HFC module runtime with local sources;
  `EditorWorkspaceIntegration.php` now matches its source. Do not overwrite
  private runtime configuration when synchronizing code.
- Do not rerun the Auth upgrade merely because work on this plan resumes.
- Use the completed `App\Configuration\ConfigurationPaths`; do not create a
  second parameter parser or a fallback that bypasses a higher-priority layer.
- **Next: P10 authored-link portability**, using the concrete defect and next
  small task recorded above. Configuration/platform/full quality checks and all
  three 80/80 browser suites are complete; do not repeat them without a relevant
  change. P9 is complete; the four modules have no
  additional installation settings to migrate. Keep document/user/browser
  state separate and do not create empty config folders or repeat schema/data
  migrations. P9 regression and 12 browser/API cases pass. P8 is complete;
  Backup/API use their nested module paths, Audit uses shared app settings and
  ORM keeps shared root `database.php`. HFC has **45/0 migrations**, filesystem
  HTML and the documented workspace/page
  data layout. Do not rerun the data migration or change the storage driver on HFC
  merely to resume. Test conversions on isolated fixtures. Legacy migration applies
  only to original source releases 0.2.12 or older; newer/fresh installations use
  current paths directly. P6 reads module-owned
  `calendar/calendar.php` through the Framework resolver; its upgrade-only file
  move is applied in HFC and browser verification passed. Preserve its GUI and
  domain data. Use complete EN then HR blocks in code and example descriptions.
  Do not repeat P1–P4 without a regression
  or rerun completed upgrade migrations. The four current JSON examples are
  described in the configuration guides; private JSON is not a Git template.
  Docker/SimpleSAMLphp remains recorded for the later Docker phase.
