# Module dependencies

This document distinguishes **required dependencies**, which Composer must
install, from **optional integrations**, which only extend behavior when the
corresponding module is present. An optional module must not become a hidden
requirement for basic operation.

All modules use the tagged Framework `^0.0.25` release and compatible internal
module releases from the `^0.1.0` line. Module repositories and Simbioza do not
commit `composer.lock`; CI runs `composer update --with-all-dependencies` on
every run, resolves the latest compatible tags, and then executes the complete
`composer on-commit` suite.

## Tagged-version policy

Check both `composer.json` and the installed packages of **each module** before
testing or releasing it. An up-to-date Simbioza installation does not update the
separate `vendor` directories used to develop individual modules. An existing
local lock file makes `composer install` reuse older resolutions.

Use the newest compatible published tag as the minimum constraint for each
required module and development integration, then run `composer update
--with-all-dependencies`, `composer validate --strict`,
`composer check-platform-reqs` and the complete `composer on-commit` suite in
that module. Inspect `composer outdated --direct` too. Dependencies use
`minimum-stability: stable`; development branches belong only in explicitly
isolated local integration fixtures. Keep optional integrations optional.

Do not select a tag solely by its numeric size: several repositories retain
historical `1.x` tags predating the current `0.1.x` line. Confirm the compatible
line, source reference and release history. Do not edit the Framework repository
or patch its installed code; consume its maintainer's published tag.

Third-party major upgrades require their own compatibility review. The current
PHP 8.2-compatible test matrix retains PHPUnit `^10.5.65 || ^11.5.56` and
PHP_CodeSniffer `^3.13.6`; Slevomat `^8.22.1` resolves to the last compatible
release because newer Slevomat releases require PHP_CodeSniffer 4. PHPStan and
Rector have minimums `^2.2.15` and `^2.6.7`. These are compatibility constraints,
not a claim that no newer major releases exist. Recheck them before each release.

## Quick reference

| Module | Required | Optional integrations |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | — |
| `module-backup` | Framework, ORM, `ext-json`, `ext-zip` | Auth and Menu for the administrator GUI; business modules register their own providers |
| `module-auth` | Framework, ORM | API, Menu, Notification |
| `module-api` | Framework, Auth, ORM | Calendar, HTML Editor, Notification, Task, Workspace; Menu and Theme for the GUI only |
| `module-menu` | Framework, ORM | Auth |
| `module-theme` | Framework, `ext-zip` | Menu |
| `module-calendar` | Framework, Auth (0.1.13+), ORM | API, Backup, HTML Editor, Menu, Notification, Theme, Workspace |
| `module-editor-html` | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | API, Backup (0.1.4+ when installed), Menu, Theme, Calendar, Workspace, Task, Comment |
| `module-email` | Framework, Auth, ORM | — |
| `module-notification` | Framework, Auth, ORM | API, Calendar, Email |
| `module-workspace` | Framework, Auth, ORM | Backup (0.1.4+ when installed), HTML Editor, Menu, Notification; Email only indirectly through Notification |
| `module-task` | Framework, Auth, ORM, HTML Editor, `ext-dom` | API, Workspace, Notification, Backup (0.1.5+ when installed) |
| `module-comment` | Framework, Auth, ORM, HTML Editor, Notification, `ext-mbstring` | Workspace, Theme, Backup (0.1.5+ when installed) |
| `module-workspace-search` | Framework, Workspace, Menu, Auth, ORM, HTML Editor | API, Backup |
| `module-audit` | Framework, Auth, ORM | Menu for Settings, Backup for portable activity-audit archives, API for `audit:read`, and every installed business-event producer |
| `simbioza-module-user` | Framework, Auth, Notification, ORM, Workspace | API, Audit, Backup, Calendar, Comment, Email, Task, Theme |
| `simbioza-module-confluence-import` | Framework, Auth, HTML Editor, Menu, ORM, Workspace, Simbioza User, `ext-dom`, `ext-fileinfo`, `ext-json`, `ext-mbstring`, `ext-zip` | API, Audit, Backup, Calendar, Comment, Task, Workspace Search |

## Loading rules

- Required modules must be installed and listed before the dependent module in
  `app.modules.enabled`.
- Optional integrations use late service resolution and must fail closed
  without breaking the base module when a package or service is unavailable.
- `module-notification` does not require `module-email`. Without it,
  notifications remain in-app only.
- `module-workspace` does not require `module-notification`. Without it, the
  workflow works but sends no notifications.
- `module-editor-html` works standalone without Workspace, Calendar, Task,
  Comment, Theme, and Menu; each control and renderer appears only when its
  integration is installed.
- `module-task` intentionally requires HTML Editor because task definitions and
  stable task UUIDs belong to the versioned HTML document.
- `module-comment` uses Editor documents and read access, Notification for
  inappropriate-content reports, and optionally Workspace publish permissions.
- `module-api` requires only Auth and ORM. Calendar, HTML Editor, Notification,
  Task, and Workspace routes are registered only when the corresponding package
  is installed and the module is enabled.
- `module-backup` requires only ORM. Its CLI remains available without Auth and
  Menu; in Simbioza, Auth protects `/settings/backups` and Menu exposes
  **Settings → Backups → Backup and restore**.
- Business modules do not hard-depend on Backup. When Backup is enabled, each
  module optionally registers its own database, filesystem, or finalizer providers.
- `module-workspace-search` requires Workspace and Menu. The derived index is
  not archived; Backup rebuilds it after a successful restore.
- `module-audit` keeps the business activity audit in the database and the
  separate PSR-3 technical log in rotating files. Business modules remain
  usable without Audit and publish neutral events where richer records are
  useful. Technical log files are never registered with Backup.
- `simbioza-module-user` listens to Auth's neutral successful-sign-in event to
  provision a restricted personal Workspace. Auth therefore remains usable
  without Workspace or Simbioza User. Backup preserves user mappings,
  administrator policies, and Workspace-scoped mappings.
- `simbioza-module-confluence-import` converts a Confluence XML archive into a
  Workspace, documents, attachments, identities, and permissions. Simbioza User
  provides stable imported-identity linkage, while additional integrations are
  enabled only when the corresponding module is installed.

## Graph

An arrow denotes a required dependency. A dashed relationship denotes an
optional integration.

```text
ORM ----------> Framework
Backup -------> ORM + Framework
Auth ---------> ORM + Framework
API ----------> Auth + ORM + Framework
Calendar -----> Auth + ORM + Framework
Email --------> Auth + ORM + Framework
Notification -> Auth + ORM + Framework
Workspace ----> Auth + ORM + Framework
Editor HTML --> Auth + ORM + Framework
Task ---------> Editor HTML + Auth + ORM + Framework
Comment ------> Editor HTML + Notification + Auth + ORM + Framework
Workspace Search -> Workspace + Menu + Editor HTML + Auth + ORM + Framework
Audit --------> Auth + ORM + Framework
Simbioza User -> Workspace + Notification + Auth + ORM + Framework
Confluence Import -> Simbioza User + Workspace + Menu + Editor HTML + Auth + ORM + Framework

Notification - - > Email
API          - - > Calendar, Workspace, Editor HTML, Notification, Task
Auth         - - > API, Menu, Notification
Calendar     - - > API, Editor HTML, Menu, Theme
Notification- - > API, Email
Workspace    - - > Editor HTML, Menu, Notification
Editor HTML  - - > API, Menu, Theme, Calendar, Workspace, Task, Comment
Task         - - > API, Workspace, Notification
Comment      - - > Workspace, Theme
Business modules - - > Backup provider registration
Workspace Search - - > API, Backup index rebuild
Audit        - - > Menu, Backup, API, all business-event producers
Simbioza User- - > API, Audit, Backup, Calendar, Comment, Email, Task, Theme
Confluence Import - - > API, Audit, Backup, Calendar, Comment, Task, Workspace Search
```
