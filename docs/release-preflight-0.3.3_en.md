# Local release-candidate checks

[Croatian version](release-preflight-0.3.3_hr.md)

Local, unpublished candidate: Simbioza **0.3.3**, Confluence Import, Simbioza User
and Calendar **0.3.1**. Publication still requires user approval. No remote commit,
tag, release or image was published. Auth and the framework are protected; their
pre-existing unrelated changes are not part of this candidate. Demo/apps-test
were not changed.

## Checklist

**Draft-only confirmation, 16 October:** the ten duplicate pairs explicitly
confirmed by the user were saved through the HFC GUI. Exactly ten primary-account
and ten source-alias choices changed; no accounts were created and no import
started. Ten potential duplicate groups and six source creation conflicts remain.
Calendar review recomputes to 101 unfinished choices after resolving those aliases;
saved calendar/space/workflow settings and all unapproved identity choices are
unchanged. Read-only before/after hashes verify retained business tables, Auth
configuration, subscriptions, workspace content and durable import mappings.
No Auth/framework code change or publication. The following proposal counts record
the earlier baseline, not an additional approval.

The full-source job now exposes owner-only, no-store read-only mapping proposals:
20 potential duplicate account groups (ten shared full-name/identifier, seven
name-only, three differing-name/shared-identifier groups) and all 107 incomplete
calendar choices (65 proposed team, 39 personal, three undecided types). Nine
personal proposals still lack reliable owner evidence. The UI distinguishes saved
and proposed types, weak personal-space/given-name hints and unresolved identities;
it does not save choices, assign rights or start an import. Auth/framework remain
unchanged. Human confirmation and full-source GUI execution are still outstanding.

The mapping-review follow-up passes importer PHPCS/Rector/PHPStan and **281 tests /
1,769 assertions**, all four isolated importer browser scenarios, separate-language
documentation, bilingual-comment and translation audits. It preserves HFC's saved
draft, credential/SSO/admin state and original personal-content hashes, with no new
notification/email rows. This is review preparation, not mapping approval or a new
full-source HFC import; it does not refresh the whole earlier release matrix.

**Expanded full-source GUI test, 16 October: prepared, not executed.** The user's
new request selects all 352 spaces and 266 calendars in a fresh owned GUI job.
Identity setup selects 55 mapped references and 361 new inactive accounts, but
26 account collisions remain unresolved. Seven personal spaces lack resolved
owners and two source personal spaces share one target owner. Calendar setup
accepts 159 unique owner proposals; 64 personal calendars still need explicit
owners and 43 calendars need a type. Existing misclassified test calendars cannot
be silently reassigned. These are review blockers, not successful import results.
The existing personal calendar and seven active administrator subscriptions are
preserved. No final import confirmation, release or Auth/framework change occurred.
The targeted shared-site/attachment tests pass: 13 tests / 66 assertions; this
archive still cannot prove real attachment transfer because it contains no binaries.
See the separate EN/HR site-import plan for the full pending acceptance scope.

The importer now supports explicitly linking duplicate source accounts to one
existing/planned primary user and add-only imports from multiple personal sources
into one verified Personal Workspace. The HFC draft uses add-only for the two
sources of the same owner. An isolated GUI regression verifies authors, links,
private attachments and repeat-import preservation. Spaces/content settings and
calendars have independent 25/50/100-row pagination that preserves off-page drafts;
107 calendar rows remain in **Needs type or owner confirmation**. Human mapping
approval and a new actual full-source GUI execution remain outstanding, not passed.

Current follow-up checks pass: importer PHPCS/Rector/PHPStan, **273 tests / 1,739
assertions**, all four importer browser scenarios (including 131-space/132-calendar
paged drafts, duplicate personal sources/reimport and unchanged single-space ACL/
private attachments), translations, bilingual comments and separated documentation.
HFC's original personal-content hashes, credentials/SSO/admin state remain unchanged;
no new notifications/email and **16 applied / zero pending migrations**. These
targeted checks do not refresh the entire earlier PHP/database/container matrix.

**Current decision after the unlocked 16 October GUI review: not ready to publish.**
The original execution checks below did not prove semantic correctness of calendar
ownership/classification. Seventy work-personal calendars were labelled team, and
many have a common source creator who is not necessarily their personal owner.
The importer now requires an explicitly chosen personal owner, preserves named
source colors and does not subscribe the importing administrator. HFC's 162 erroneous
actor subscriptions and 162 defaulted colors were corrected without reimporting;
the six previous subscriptions and the administrator's own imported calendar remain.
Events, semantic ACLs, owners/types and other users' subscriptions are unchanged.
Reports now use theme-owned content cards. Confirm remaining personal ownership
and rerun final candidate/container checks before publication. Earlier complete
PHP-version/full-suite/container results below predate these morning fixes.

- [x] R1 Align dependency minima, optional Calendar compatibility and CI coverage.
- [x] R2 Verify 0.3.2 upgrades through CLI and secured-FPM GUI with retained data.
- [x] R3 Rebuild sanitized image; verify PostgreSQL, immutable code, persistent volumes, backups and languages.
- [x] R4 Repeat quality checks on PHP 8.2–8.5 and the full integrated suite after title/progress changes.
- [x] G0 Verify personal/team choice; a personal calendar requires an explicitly selected existing local or mapped/planned source owner.
- [x] G2 Complete the real HFC import and read-only reports/content/ownership/access/preservation checks.
- [x] G1 Inspect the actual HFC overview, identity/calendar/space reports, a new imported page, preserved personal content, subscriptions and calendar administration on an unlocked Mac. The earlier actual import's final native confirmation remains CLI-executed; isolated browser tests verify that control without reimporting HFC.
- [ ] G3 Confirm remaining personal calendar owners/types and refresh final release verification after the morning corrections.

The 16 October preparation/owner-picker follow-up adds automatically followed
calendar preparation, phase/counter progress, interrupted-worker detection and
database-paged existing-owner search (at most 25 results per request, including
selected future accounts). These changes stay in the importer: Auth and framework
code are unchanged. No new actual HFC import or owner/access change is performed
by preparing/searching; the new job still requires the user's reviewed choices.

Do not rerun the completed import merely to close G1. Retain the exact distinction
between successful backend execution and the outstanding GUI check.

## Real HFC acceptance — 16 October 2026

The real GUI uploaded the source ZIP and accepted the combined review. The locked
Mac blocked the final native confirmation dialog. Following the user's instruction
to continue unattended, the same frozen, reviewed selection completed through the
normal application coordinator CLI. Before content processing it used the real
`/hfc` HTTP URL context. This is not an end-to-end GUI-start success.

| Imported item | Result |
| --- | ---: |
| Mapped source identities / new users | 55 / 0 |
| New ordinary groups / mapped groups | 8 / 1 |
| New memberships | 105 |
| Selected personal spaces | 30 |
| New workspaces / existing add-only target | 29 / 1 |
| Pages | 346 |
| Personal / team calendars | 41 / 122 |
| Imported / explicitly skipped events | 32,755 / 131 |
| Completed detailed reports | 32 |

The 103 unselected calendars with unclear/unmapped personal ownership were not
imported. The duplicate personal-space owner uses only the user's selected source.
All 43 read-only acceptance checks passed:

- Exact new object counts and retained baseline IDs.
- All 346 document creators and latest editors match confirmed mappings; 344
  latest-editor references map directly, and two use the mapped source creator.
- All 346 filesystem documents load through Editor and are under workspace data.
- All 30 personal-space owners match; all 163 calendars deny anonymous access and
  do not enable broad public/authenticated access. Personal owners can read theirs.
- Two long source titles (maximum 706 characters) and a 468-character location
  survive unchanged under the approved 765/510-character limits.
- Existing personal-target nodes, documents, homepage and ACL hashes are unchanged.
- Credentials, SSO settings, global administrator flags and Auth configuration are
  unchanged. No new notifications or email-outbox rows were created.
- All resolved local links use `/hfc/`; source ZIPs and report UUIDs are retained.

Reports show **197 resolved / 656 unresolved links**, **83 review issues** and
**five pending external-script issues**. They remain visible review results, not
fatal import errors. Missing targets outside the selected scope are not invented
or silently imported. Of 784 source memberships, 105 were in the approved mapped
scope; 679 remain unresolved rather than creating unapproved identities/groups.

The selected real pages contain no calendar embeds to activate. Earlier-page
calendar activation is covered by real-service fixtures. The source ZIP explicitly
omits attachment binaries, so this run does not prove binary attachment migration;
the unchanged single-space E2E fixtures cover that path.

One original personal-target document reference was already unreadable before the
import. Its row/reference hashes remain unchanged. It was not repaired or deleted;
its final visible state remains part of the outstanding GUI inspection.

### Failed first attempt and fresh retry

The first real GUI attempt failed on Calendar's old MySQL `title VARCHAR(255)`.
Exact attempt-owned cleanup removed eight new groups, 105 memberships, 22 calendars,
4,025 events, 27 event types and new importer mappings. Baseline row hashes/IDs were
verified unchanged. The failed reports and original archives remain intact; the
failed parent is not relabelled as successful.

Calendar owns `resources/migrations/widen_calendar_event_text.php`; Simbioza calls
it through `database/migrations/20261016130000_widen_calendar_event_text.php`.
Fresh and upgraded schemas use `VARCHAR(765)` for titles and `VARCHAR(510)` for
locations. API/ICS validation and source review reject/report excess without
truncation. The accessible progress bar shows committed current-step progress,
with a separate visible percentage; unmeasurable preparation/linking is indeterminate.

The fresh retry completed without an import error. Exact selections, before/after
hashes, newly created IDs and stable report UUIDs are private evidence under ignored
`build/release-preflight-20261015/evidence/`. Do not publish that directory.

## Current local verification

### Morning report/subscription corrections

Current PHP 8.5 quality runs pass for the isolated application (283 tests / 6,377
assertions, three root-only skips), Importer (247 / 1,565) and Calendar (92 / 726,
three environment-dependent skips). PHPCS, Rector, PHPStan and bilingual/docs/
translation audits pass. The two focused browser scenarios pass, including the
combined GUI workflow, separate themed reports and unchanged single-space import.
Real Calendar API fixtures pass with filesystem and database HTML stores; they
assert source palette preservation, no importing-admin subscription, an owner
subscription and unchanged mapped access. The disposable fixture was removed.

Actual HFC visual checks cover the overview, user/calendar/space reports, an
imported page, retained personal content, My calendars and calendar administration.
The live correction preserves all 32,755 imported event rows, semantic calendar
ACLs, owners/types and other users' subscriptions. HFC remains at 16 migrations,
zero pending; Auth/SSO/admin hashes are unchanged, with zero new notifications/mail.
This is focused follow-up verification, not a new complete PHP-version/browser/
container matrix. The earlier matrix evidence below predates these fixes.

All `composer on-commit` quality checks pass on PHP 8.2–8.5, including PHPCS,
Rector, PHPStan and the configured documentation/translation checks.

| Suite | Tests | Assertions | Conditional skips |
| --- | ---: | ---: | ---: |
| Application | 283 | 6377 | 3 root-only |
| Confluence Import | 242 | 1509 | 0 |
| Calendar | 91 | 714 | 3 environment-dependent |

The three application root cases pass separately on Linux root: 3 tests / 43
assertions. Calendar's populated legacy-schema upgrade passes separately on SQLite,
MySQL 8.4 and PostgreSQL 18 (six assertions per driver), including repeated execution,
retained values/IDs and full 765/510-character Unicode values. HFC has **16 executed /
zero pending migrations**.

The complete current `php scripts/run_e2e.php --local --keep` passes **81/81
browser/API/performance tests in 23.2 minutes**, including unchanged single-space
ACL/attachment/search behavior and the new visible progress bar. Earlier unsuccessful
runs remain recorded: an embedded-search source-context regression was fixed without
weakening source isolation, and a cleanup-navigation race was corrected by waiting
for the reload. Those attempts are not presented as successful runs.

Real-service fixtures cover filesystem/database HTML, personal add-only import,
repeat imports without overwrites/duplicates, stale reviews, concurrent/resumed
requests, future-user authorship, two-way cross-space links and later calendar
activation. Separate reports keep their UUIDs while current persisted link counts
update; mixed standalone/site references cross only a verified same-source boundary.
Transient local workspace/parent choices are excluded from portable backups.

## Upgrade, dependency and container evidence

The earlier real CLI and secured-FPM GUI upgrades started with application 0.3.2,
published modules 0.3.0 and 14 migrations. They reached the then-current private
candidate with 15 migrations, retained Auth/private config, a synthetic attachment,
a SQL fixture row and cleared maintenance. After the title migration, the refreshed
PostgreSQL image reaches 16 migrations with zero pending and the same retained
state. The first 15-migration evidence is historical, not a claim that it exercised
the later title migration.

Linux systemd/FPM/deploy checks found and fixed the empty `BindReadOnlyPaths`
argument and an override-layer policy that incorrectly made release-managed
`config/` read-only to the deployment worker. Separate deploy policy permits
release updates while retaining FPM/private-layer protection. Ten generator
regressions / 39 assertions pass on PHP 8.2–8.5. Affected original 0.3.2 Linux helper
installations need the first update as the actual deploy/code owner, then helper
regeneration; this does not claim the old helper works unchanged.

The candidate requires Calendar/User `^0.3.1`; Importer requires User `^0.3.1`
and rejects an installed Calendar below `0.3.1` without making Calendar mandatory.
The clean importer-only fixture passes without Calendar. Sanitized image export
uses published Framework `^0.0.25`, not the unrelated dirty framework tree, and
resolves 19 stable packages. After approval publish User/Calendar, then Importer and
the application; unchanged modules remain `0.3.0`.

Native ARM64/PostgreSQL installation, non-superuser database role, immutable app
code, backup/workspace/language roundtrips and image replacement passed. Runtime and
database volumes retain identical configuration, attachment and SQL fixture hashes.
The local-only image is `simbioza:0.3.3-preflight-workflow`, ID
`sha256:4bff09873fdea31491c00f8cddffb0a91f52a5d9734d7c88b0ecc885643b851f`.

### Residual image-security finding

The CycloneDX 1.7 SBOM has 166 components. The retained local scan reports **zero
fixable HIGH/CRITICAL findings**, but still 91 HIGH, one CRITICAL, 131 MEDIUM,
198 LOW and four UNKNOWN findings without an offered HIGH/CRITICAL fixed package.
The critical `CVE-2026-6653` is in distribution `libxml2`; it is not suppressed.
The [Debian security tracker](https://security-tracker.debian.org/tracker/CVE-2026-6653)
also marks the installed Trixie package vulnerable, but assesses the issue as minor
and does not plan a dedicated advisory. Neither assessment proves the importer's
specific XML path exploitable or unaffected. Review this residual risk explicitly
before release approval; retain the full JSON report.

## Remaining handoff

- Finish the final native-confirmation/visual HFC check with the Mac unlocked.
  The completed retry must not be duplicated or the preserved personal content replaced.
- User approval remains required before publication. Remote CI, public release
  assets, native AMD64 CI and published multi-architecture registry availability
  cannot be inferred from local checks.
- Keep private evidence and source archives. Stop isolated test services while
  awaiting interactive acceptance; remove only explicitly identified disposable
  fixtures, never HFC imported data or user archives.

The isolated Docker app/database and secured-FPM service are stopped. The two
disposable MySQL/PostgreSQL title-migration containers and their anonymous test
volumes were removed after successful checks. HFC remains running; its imported
objects, source archives and reports were not removed.
