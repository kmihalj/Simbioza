# Framework parameters and application configuration

[Croatian version](configuration_hr.md)

Simbioza 0.3.0 is a fresh-install baseline. Install with a new database and the
current directory layout. There is no upgrade from 0.2.x or importer for old
Simbioza backup formats. Future updates retain updater self-update and rollback.
The HeartPhrame framework is used unchanged.

## Central parameters

The private application-root `framework.php` is loaded before HTTP and CLI
configuration. Start from [framework.php.dist](../framework.php.dist):

```php
return [
    'HPH_APP_PATH' => __DIR__,
    'HPH_CONFIG_PATH' => __DIR__ . '/config',
    'HPH_CONFIG_OVERRIDE_PATHS' => '',
];
```

- `HPH_APP_PATH`: absolute application root.
- `HPH_CONFIG_PATH`: complete configuration directory, not just one module's folder.
- `HPH_CONFIG_OVERRIDE_PATHS`: additional absolute directories separated by
  the platform's `PATH_SEPARATOR`; later layers have higher priority.

Nonempty process environment values take precedence. Use the same parameters for
HTTP, CLI and workers. Prepare the complete configuration before choosing an
external directory: it must include required application PHP files and private
module files. The directory need not be named `config`.

`App\Configuration\ConfigurationPaths` delegates to the existing framework
resolver. `directories()` returns ordered, deduplicated layers;
`effectiveFile()` selects a complete data document; `writableFile()` validates a
target in the last declared layer. It does not create files or merge document
contents. PHP configuration follows the framework's key/list merging semantics.

Invalid or unreadable higher layers fail explicitly. Writes never silently fall
back to a lower writable layer. Read-only configuration can be used without
creating locks; saving requires permission for atomic replacement. A declared
configuration-root symlink is permitted, but stores reject module/file/lock
symlinks as write targets. Use absolute paths and keep private settings outside
Git and the public web directory.

## Configuration ownership

| Active path relative to `HPH_CONFIG_PATH` | Owner and format |
| --- | --- |
| `app.php`, `installation.php`, `setup.php`, `env.php`, `languages.php`, `modules.php` | Application/shared PHP configuration |
| `database.php` | Shared Framework/ORM connection configuration; stays at the root |
| `auth/auth.json` | Login policy, user attributes and named SSO profiles |
| `email/email.json` | SMTP, sender, queue policy and optional administration/test GUI |
| `editor-html/editor-html.json` | Generic Editor installation settings |
| `editor-html/external-scripts.json` | Third-party script definitions and local administrator approval; pages select scripts separately |
| `workspace/workspace.json` | Simbioza Workspace settings and global homepage policy |
| `calendar/calendar.php` | Calendar installation policy |
| `backup/backup.php`, `backup/backup-providers.php` | Backup storage policy and optional host provider registrations |
| `api/api.php` | API limits, CORS, conditional writes and webhook worker policy |
| `workspace-search/workspace-search.php` | Search page-size, snippets and derived-index refresh policy |
| `confluence-import/confluence-import.php` | Archive limits, private staging and import processing policy |
| `menu/menu.php`, `menu/top.json`, `menu/settings.json` | Global menus and Menu options |
| `theme/theme.php`, `theme/themes.json`, `theme/settings.json` | System Theme options and selection |

Package `config/services.php`, API/backup descriptors, views, translations and
default settings are code/resources, not private installation configuration.

## Installation, module selection and languages

The installer and startup use the same configuration layers. Module selection
lives in `modules.php`; lifecycle changes use `.modules.lock` in the final layer.
The existing GUI reads and writes that file. Optional-module Composer operations,
dependency checks and worker execution remain application-managed.

Language administration uses `languages.php` and `.languages.lock` in the final
layer. Installed translations live in `data/languages/catalogs/<locale>.json`;
application/module `lang/` contains read-only packaged translations. Flags live in
`data/languages/flags`, templates in `data/language-packs`, and revision tracking
in `data/languages/repository-versions.json`. Updating a disabled language does
not enable it, and the last enabled locale cannot be disabled.
Data-only language GUI actions do not need the privileged package helper.
The application-settings backup includes the registry, installed catalogues,
flags and revisions; see the [localization guide](localization_en.md).

Setup diagnostics reports resolved paths, owner/group and permissions, not
secret values. Reads inspect the effective files; writes inspect the final layer,
directory replacement rights and locks. The isolated worker uses those same
parameters. PHP cache invalidation makes saved configuration visible to subsequent
HTTP and CLI requests.

## JSON stores and optional GUI

Auth selects the last existing complete `auth/auth.json`. Email, Editor and
Workspace merge objects across layers and replace lists, including empty lists.
Their GUI saves reread effective settings under the module lock, preserve
unrelated keys and publish atomically into the last declared layer.

Generic Auth, Email and Editor can be configured without Simbioza's GUI.
Email and Editor support their documented `gui.enabled=false`; Email has an
independent `gui.test_enabled` setting. Workers and content services remain
usable. SSO verification is optional in standalone Auth projects;
Simbioza initializes `login.require_provider_verification=true`.

Calendar, Workspace, Menu and Theme retain their existing GUI. Valid manual
configuration is respected. Disabling every module's GUI is not a requirement.

### Auth

- `login`: registration, administrator break-glass and session lifetime.
- `providers.<protocol>.enabled`: protocol-level enablement.
- `providers.<protocol>.profiles.<stable-key>`: multiple named profiles with
  their own enablement, connection `config` and attribute `mapping`.
- `user_attributes`: definitions synchronized with their database projection.
  Removing a definition removes its values and derived automatic group mappings,
  not users or groups.

Users, groups, memberships, external identity links and stable profile IDs remain
in the database. Keep the profile key stable; change `profile_name` to rename it.
Provider secrets are configuration, not duplicate database settings.

### Editor and Workspace

Editor remains independently usable. Enabled Workspace applies routing,
publication, outline and ownership rules at runtime without persisting forced
values into Editor JSON. Standalone settings apply again without Workspace.
Global homepage policy is in Workspace JSON; personal selections stay in the
database. Documents, versions, ACL, calendars, tasks, comments and themes are data.

Third-party JavaScript belongs to the Editor's separate registry, not application
JavaScript. In **Settings → HTML Editor → External scripts**, an administrator
can approve/revoke scripts, rename them and use **Where is it used?** to inspect
published pages, drafts and optional history. Approval never loads a script on
every page. See the [registry instructions](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-html-editor/-/blob/0.3.0/docs/external-scripts_en.md).

### Modules without separate policy files

Audit uses shared application timezone/log settings. Comment, Task, Notification
and Accessibility need no empty module directories or templates. Comments,
completion history, inbox/read state and mail preferences remain in the database;
Accessibility preferences remain in browser-local storage. SMTP belongs only to
Email JSON. Normal migrations, authorization and domain checks still apply.

## Data is separate from configuration

Configuration paths never relocate content. Filesystem HTML, attachment history,
owned menus and private themes use the [workspace/page layout](workspace-data_en.md).
Database HTML remains in the database. Unowned/shared Editor data and system-theme
assets keep their respective shared data roots.

Menu's global JSON files are complete last-existing-layer documents; Theme works
the same way and invalidates CSS using content hashes. PHP policy maps merge by
key. Their GUI remains the recommended editor, so no new `.dist` templates are
provided for Menu or Theme. Package defaults are read-only, never GUI write targets.

## HTTP security policy

`HPH_CONFIG_PATH/middleware.php` enables the unchanged Framework's trusted-proxy,
security-header and error-handler layers before session/CSRF handling. Keep the
security/error pair outside request metrics and routes so denials/errors receive
the same headers. `app.php` adds CSP `base-uri 'self'; object-src 'none';
frame-ancestors 'self'`; it deliberately does not claim full `script-src` coverage.
Published third-party scripts still require local registry approval and per-page
selection. Session defaults are Secure, HttpOnly and SameSite=Lax; reviewed
installation session overrides may change them. Configure proxies accurately,
and use HSTS only for a deployment known to be consistently HTTPS.

The [pre-release audit](pre-release-audit-0.3.0_en.md) records completed Auth,
query, language-storage and immutable-deployment corrections. The
[Docker guide](docker_en.md) documents the image/volume layout and the GUI/CLI
operations deliberately blocked in immutable deployments. `HPH_CONFIG_PATH`
alone is not the immutable-mode switch. Publication still requires approval
and verification of the published packages and image.

## Examples

Current examples in application `config/` mirror their package examples:

| Example | Copy to |
| --- | --- |
| `auth.json.dist` | `HPH_CONFIG_PATH/auth/auth.json` |
| `email.json.dist` | `HPH_CONFIG_PATH/email/email.json` |
| `editor-html.json.dist` | `HPH_CONFIG_PATH/editor-html/editor-html.json` |
| `external-scripts.json.dist` | `HPH_CONFIG_PATH/editor-html/external-scripts.json` |
| `workspace.json.dist` | `HPH_CONFIG_PATH/workspace/workspace.json` |
| `calendar.php.dist` | `HPH_CONFIG_PATH/calendar/calendar.php` |
| `backup.php.dist` | `HPH_CONFIG_PATH/backup/backup.php` |
| `api.php.dist` | `HPH_CONFIG_PATH/api/api.php` |

Copy only needed examples for a new installation; never overwrite active private
settings. Flat `.dist` files are not loaded. JSON permits no comments:
`_documentation.en` and `_documentation.hr` are explanatory inert data, not options.
Auth removes them during validation; other stores retain them harmlessly.
The script example uses the equivalent inert `_description_en` and `_description_hr`
blocks; both illustrated definitions are pending, not approved.
Use real JSON booleans/integers. Replace placeholders only in private live files.
PHP examples for framework/database/env/modules remain valid.

## Backup and permissions

Current backups use archive format 2 and exact provider schemas. Full archives
require all registered full-restore providers. Source metadata includes the public
site address; cross-site restore maps page/attachment identities and reports
unresolved links. It does not copy source database credentials or deployment
paths. Configuration restore uses the module's validation, locks and destination
layers; rollback retains prior configuration until restore succeeds.

Private module directories should be shared with the runtime group
(`2770`, files/locks `0660`). FPM must be able to replace GUI-managed files;
a deploy-owned file in a sticky directory is not replaceable solely because it
is group-writable. Use the documented FPM setup tooling. Release-owned executable
PHP must not generally be web-writable; GUI-managed `modules.php` is an explicit
exception. The updater preserves private configuration and declared external
layers. No old configuration relocation or database export runs at startup.

During updates and rollback, the updater also protects parent directories of
preserved private paths when those directories are absent from the synchronization
source. This applies to nested configuration and additional modules, not just
built-in module names. For example, a private `config/my-module/settings.json`
keeps its directory even when the public release does not contain `my-module`.
Directories present in the source still use per-file rules: release-owned files
and `.dist` examples can be refreshed or removed without overwriting private
settings. The updater does not suppress rsync diagnostics or ignore errors.

See [the 0.3.0 checklist](initial-release-0.3.0_en.md) for current verification
status; historical P1–P10 results are not a claim that the new baseline is released.
