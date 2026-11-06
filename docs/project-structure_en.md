# Project structure

Simbioza composes independent HeartPhrame modules into one application.
Its `src/` namespace is `App`; domain modules retain their own repositories,
services, routes and tests. Framework is installed as an upstream Composer
package and is not edited in this project.

| Path | Purpose |
| --- | --- |
| `src/Controllers/`, `views/` | Application Home/About, setup, content entry points and read-only guides; layouts assemble optional module renderers. |
| `src/Module/`, `src/Installation/`, `src/Setup/` | Module catalog/lifecycle, fresh installation and deployment-aware administrative operations. |
| `src/Configuration/`, `config/`, `framework.php` | Configuration resolution and trusted bootstrap/routes/services. Private installation values use the configured layers. |
| `src/Localization/`, `lang/` | Installed languages, consolidated dictionaries and language maintenance. |
| `src/Update/`, `resources/` | Upgrade support and versioned installation assets: manuals and theme collections. |
| `src/Guides/`, `docs/user-guide/` | Bundled-manual reader and editable bilingual user-guide sources/screenshots. |
| `src/Security/`, `src/Performance/` | Host script policy and optional request/query measurements. |
| `database/migrations/` | Versioned schema changes; migration application state stays in the database. |
| `public/` | The front controller and deliberately public files. The web document root points here. |
| `scripts/`, `tests/` | Maintenance/build tools, unit/integration tests and the isolated browser/API suite. |
| `docs/` | Current developer/administrator references and historical release notes. |
| `vendor/` | Composer dependencies; managed by Composer, not edited or committed. |
| `build/` | Ignored test reports, temporary quality manifests and generated development artifacts. |
| `data/` | Private runtime files such as logs, caches, attachments, temporary archives and administrator-owned theme assets. |

## Configuration and data boundaries

`HPH_CONFIG_PATH` chooses the host configuration root; `HPH_CONFIG_OVERRIDE_PATHS`
adds stronger layers. HTTP and CLI must resolve the same directories. The code
root is not necessarily the configuration root. See [configuration](configuration_en.md).

Do not replace private JSON/PHP documents with `.dist` examples on an existing
installation. Keep database credentials, encryption keys, SMTP/provider secrets,
logs, uploads, sessions and backup archives outside Git and the public web root.
Do not make application code writable by the web account to solve a runtime
permission error.

Some maintained files under `data/themes/` are tracked **release assets**, not
administrator uploads. `resources/installation/theme/` contains importable theme
packages; collection receipts prevent an update from repeatedly restoring a
theme an administrator deleted. The [Theme documentation](theme-collection_en.md) explains
collection import/export and the gallery.

## Manuals and module ownership

Edit `docs/user-guide/*_en.md` / `*_hr.md` and their screenshots, then use the
[manual synchronization procedure](documentation-quality_en.md). The generated
Workspace package also powers HTML guides on installations without Wiki.

For an application's form field, use the [embedded HTML Editor
example](html-editor-forms_en.md). Saving a form field is separate
from creating a published document or a Workspace page; the host owns that
form's validation, authorization and storage.

Change module behavior in its owning repository, not in `vendor/` or by copying
its private implementation into Simbioza. Keep optional integrations optional;
see [module dependencies](module-dependencies_en.md) and
[common workflows](common-workflows_en.md).
