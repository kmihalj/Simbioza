# Module dependencies

Simbioza combines independent modules into a complete application. Distinguish
its installation policy from a module's own Composer dependencies: a package
can be reusable in another host while Simbioza always includes its shared core.

## Simbioza's six required modules

Every Simbioza installation includes **Auth, ORM, Menu, Theme, Backup and Audit**.
They provide identities/authorization, database access, navigation, presentation,
backup/restore and activity records. Module management does not allow removing
these six. API and Accessibility are optional.

The installer offers the complete Simbioza and narrower profiles:

| Profile | Additional selection; required dependencies are included automatically |
| --- | --- |
| Complete Simbioza | Every available optional module |
| HTML website | HTML Editor |
| Calendar | Calendar |
| Wiki | Workspace, Workspace Search and Simbioza User |
| Surveys | Survey, including its HTML Editor dependency |
| Shared core | Only the six required modules |

More modules can be installed later through **Settings → Modules** or the
`modules` CLI. A disabled package is not the same as an uninstalled package.
Before removing an optional module, check dependants and its data-backup workflow;
do not bypass lifecycle checks by editing Composer or menu JSON alone.
A correctly configured secured PHP-FPM deployment supports GUI maintenance;
other classic installations use the documented CLI update procedure.
See [installation](installation_en.md) and [modularity](modularity_en.md).

## Direct package dependencies

The table reflects each package's `composer.json` `require` and `suggest` lists.
Framework and transitive PHP extensions are not repeated recursively. Composer
must satisfy the full graph; use `composer check-platform-reqs` against the
installed packages and PHP binary. Optional domain owners may also contribute
API routes, events or Backup providers without becoming dependencies of the core.

| Package | Direct required packages/extensions | Optional suggested integrations |
| --- | --- | --- |
| ORM | `ext-intl`, `ext-pdo`, Framework | — |
| Auth | Framework, ORM | Backup, API, Menu, Notification |
| Menu | Framework, ORM | Backup, Auth |
| Theme | `ext-intl`, `ext-zip`, Framework | Backup, Menu |
| Backup | `ext-json`, `ext-zip`, Framework, ORM | Auth, Menu |
| Audit | `ext-json`, `ext-mbstring`, Framework, Auth, ORM | API, Backup, Menu |
| Accessibility | Framework | Menu |
| API | Framework, Auth, ORM | Backup, Menu, Notification, Theme |
| Calendar | Framework, Auth, ORM | Backup, API, HTML Editor, Menu, Notification, Theme, Workspace |
| HTML Editor | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | Backup, Menu, Theme, Calendar, Workspace, Task, Comment, API |
| Email | Framework, Auth, ORM | Audit, Backup |
| Notification | Framework, Auth, ORM | Backup, API, Email |
| Survey | `ext-json`, `ext-dom`, `ext-mbstring`, `ext-pdo`, `ext-openssl`, Framework, ORM, Auth, HTML Editor | Workspace, API, Backup, Theme, Menu, Notification, Email, Audit |
| Task | `ext-dom`, Framework, Auth, ORM, HTML Editor | Backup, API, Workspace, Notification |
| Comment | `ext-mbstring`, Framework, Auth, HTML Editor, Notification, ORM | Backup, Theme, Workspace |
| Workspace | Framework, Auth, ORM | Backup, HTML Editor, API, Menu, Notification, Email, Theme |
| Workspace Search | Framework, Auth, HTML Editor, Menu, ORM, Workspace | Backup, API |
| Simbioza User | Framework, Auth, Notification, ORM, Workspace | API, Audit, Backup, Calendar, Comment, Email, Task, Theme |
| Confluence Import | `ext-dom`, `ext-fileinfo`, `ext-libxml`, `ext-xmlreader`, `ext-zip`, Framework, Auth, HTML Editor, Menu, ORM, Workspace, Simbioza User | Audit, Backup, Calendar, Comment, Task, Workspace Search |

## Loading and ownership

- Generic HeartPhrame hosts enable their packages in `app.modules.enabled`,
  with required modules available before their dependants. Simbioza instead
  derives that list from its central catalog and persisted module selection.
- An optional integration must remain inactive when its owner is absent or
  disabled. Hiding a menu item is never a replacement for route authorization.
- HTML Editor works without Workspace. In a form it can edit a textarea without
  creating a document; follow the [complete embedding example](html-editor-forms_en.md).
  Adding Workspace later does not automatically move existing standalone pages.
- Workspace owns hierarchy and publication permissions; Editor owns HTML and
  attachments. Task and Comment use published document identity and access.
- API owns Bearer transport/discovery. Enabled owner modules register their
  routes and check their permissions. Survey, Audit, Search and personal User
  integrations are conditional; see the [actual API contract](api-v1-contract_en.md).
- Backup owns portable archives. Enabled modules register their own content,
  settings and finalizers. Search indexes, queues, sessions and technical logs
  are transient; they are not restored as business data.
- Notification remains an in-app inbox without Email. Installing Email does not
  start a worker; configure SMTP and explicitly schedule delivery. Survey report
  jobs also require their documented scheduler and current recipient rights.
- Simbioza User owns follows, preferences and optional personal Workspaces.
  Auth remains usable without those features. Accessibility stores personal
  browser adaptations locally and does not grant content access.
- Confluence Import converts an external archive into ordinary Workspace/Editor
  content. Its link-resolution records are integration metadata, not a separate
  type of area. Imported scripts still need explicit administrator approval.

With Wiki, user instructions stay in their **User guides** Workspace. Without
Wiki, the **Guides** menu uses the same bundled manual with HTML navigation.

## Versions and verification

Use the supported stable constraints in each manifest. The current Framework
line is `^0.0.25`; consume the upstream maintainer's release and never patch its
repository or installed vendor code. Current internal packages use the `0.3.x`
compatibility line; historical `1.x` tags are not a reason to jump major lines.

An application's current vendor directory does not update a sibling module's
separate development dependencies. In an isolated source checkout inspect
`composer show`, run `composer validate --strict`, resolve the intended manifest,
then run platform and quality checks. Keep a generated production artifact's
resolved lock/inventory for reproducibility even though these source libraries
do not commit `composer.lock`.

Simbioza's ordinary CI resolves its declared stable catalog; explicit `--local`
mode uses allowed sibling checkouts. Module branch CI can use development
aliases, while module tag CI checks stable constraints. JavaScript tools use
tracked `package-lock.json` files and `npm ci`. See
[end-to-end testing](end-to-end-testing_en.md) and
[documentation maintenance](documentation-quality_en.md).

## Using generic modules in your own project

Each link opens the module-owned beginner guide, with a complete minimal PHP project and executable examples. These projects load HeartPhrame directly and do not require Simbioza. The framework itself is maintained separately.

| Module | Guide | Release |
| --- | --- | --- |
| Accessibility | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-accessibility/-/blob/main/docs/how_to_en.md) | `0.3.3` |
| API | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/how_to_en.md) | `0.3.5` |
| Audit | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-audit/-/blob/main/docs/how_to_en.md) | `0.3.4` |
| Auth | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-auth/-/blob/main/docs/how_to_en.md) | `0.3.4` |
| Backup | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-backup/-/blob/main/docs/how_to_en.md) | `0.3.6` |
| Calendar | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-calendar/-/blob/main/docs/how_to_en.md) | `0.3.5` |
| Comment | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-comment/-/blob/main/docs/how_to_en.md) | `0.3.5` |
| HTML Editor | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-html-editor/-/blob/main/docs/how_to_en.md) | `0.3.19` |
| Email | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/main/docs/how_to_en.md) | `0.3.7` |
| Menu | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-menu/-/blob/main/docs/how_to_en.md) | `0.3.6` |
| Notification | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-notification/-/blob/main/docs/how_to_en.md) | `0.3.4` |
| ORM | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-orm/-/blob/main/docs/how_to_en.md) | `0.3.3` |
| Survey | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-survey/-/blob/main/docs/how_to_en.md) | `0.3.7` |
| Task | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-task/-/blob/main/docs/how_to_en.md) | `0.3.5` |
| Theme | [How-to](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-theme/-/blob/main/docs/how_to_en.md) | `0.3.14` |
