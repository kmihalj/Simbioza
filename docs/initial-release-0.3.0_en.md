# Simbioza 0.3.0 — clean initial release

[Croatian version](initial-release-0.3.0_hr.md)

Decision: 11 October 2026. This supersedes the older upgrade and archive-compatibility requirements in the configuration migration plan.

0.3.0 is a fresh-install baseline: a new database, current configuration under `HPH_CONFIG_PATH/<module>/`, and workspace/page data directories. There is no supported upgrade from 0.2.x, bridge-release requirement, or importer for old Simbioza backup schemas. Keep updater self-update and rollback for future releases.

Current backup/restore remains portable between sites: store the source public site address, preserve same-site links, resolve cross-site internal page/attachment links using imported target identities, preserve external links, and report unresolved references. Old Confluence exports remain supported as external input, not as old Simbioza backups.

## Checklist

- [x] R030-A: backup format 2 and exact provider schemas; old Simbioza conversions removed, same-site and cross-site link behavior verified.
- [x] R030-B: Auth configuration files are authoritative; obsolete database configuration fallbacks and upgrade utilities removed. Users, groups, identities and required projections remain in the database.
- [x] R030-C: fresh installation uses 14 initial schema migrations; historical upgrade/move utilities removed. Updater self-update, process handoff and rollback remain for 0.3.x and newer.
- [x] R030-D: obsolete module configuration, identity, API and payload-format fallbacks removed. Optional integrations, valid defaults and external Confluence format support retained.
- [x] R030-E: separate EN/HR instructions, all six bundled guide archives and demo seed/reset tooling use the current format.
- [x] R030-F: clean installations, unit/static checks and all 80 GUI scenarios passed; HFC uses the current module sources and has zero pending migrations.
- [x] R030-G: application version 0.3.0, module constraints and the final local verification matrix prepared. Publication is a separate, still-pending step.

## Completed verification — 11 October 2026

These results cover the clean initial baseline before the external-script registry,
not old P1–P10 runs or published tags. Follow the additional
[pre-release audit](pre-release-audit-0.3.0_en.md) for the expanded candidate;
its pending checks must not be treated as completed by this table.

| Check | Result |
| --- | --- |
| All 18 public module unit suites | 1,045 tests / 8,943 assertions passed |
| Application unit suite | 233 tests / 5,808 assertions; three root/Linux-only cases verified separately in Colima |
| Fresh SQLite installation matrix | All 21 module combinations passed |
| Full PostgreSQL 18, MySQL 8.4 and MariaDB 11.4 installations | Passed; 14 initial migrations, zero pending migrations per installation |
| Full isolated browser/API suite | All 80 scenarios passed, including backup/restore and Confluence import |
| Real Linux FPM and secure FPM | HTTP home/login and Auth/Menu/Theme writes passed with read-only code and writable runtime paths |
| Bundled instructions | All six current-format encrypted archives and their hashes verified |
| Private demo module | 12 tests / 231 assertions and all six isolated Linux reset scenarios passed |
| HFC | Home/login return HTTP 200; 14 migrations applied, zero pending |
| Code and documentation | Required style/static-analysis checks, Composer validation and documentation/translation audits passed |

Reports remain in `build/r030/`, `build/clean-install-matrix-*.json` and `build/playwright-report/index.html`. Disposable installation fixtures, database containers and guest-only reset-test accounts are removed after verification. Framework sources and private SSO settings were not changed.

## Instructions and demo content

`resources/installation/workspace/` contains current-format archives for general installation, Apache mod_php, PHP-FPM, user instructions, meetings and Confluence import. Their manifests use backup format 2 and application version 0.3.0. Internal page/attachment references use canonical paths and imported identities, not a fixed destination site address.

The private demo module source under `DemoSeed/simbioza-module-demo/` uses current configuration paths and workspace/page ownership. Its reset baseline format is 2, including selected configuration layers, application data and a consistent SQLite snapshot. A fresh demo needs freshly seeded content and a newly captured baseline; editing these sources does not update or reset a running demo.

## Remaining publication step

- [ ] After separate authorization, publish coordinated 0.3.0 module/application tags and verify installation from those published packages.
- [ ] The user reinstalls demo and apps-test, then captures a new demo reset baseline.

Until publication, dependency examples using `^0.3.0` describe the prepared release; development verification uses local package sources. Do not substitute old 0.2.x tags or attempt an upgrade from them.

## Boundaries and continuation

Do not change HeartPhrame Framework. Preserve unrelated edits and private settings. The user reinstalls demo/apps-test; do not mutate those running sites or their databases. HFC is the development site and needs no content backup. No tags, commits or releases have been published by this task.

2026-10-11: clean baseline verification complete. The expanded pre-release audit
is in progress; publication and live-site reinstallation remain separate.
Older P1–P10 evidence is retained only as historical context.
