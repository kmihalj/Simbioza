# Module dependencies

This document distinguishes **required dependencies**, which Composer must
install, from **optional integrations**, which only extend behavior when the
corresponding module is present. An optional module must not become a hidden
requirement for basic operation.

All modules use the Framework and internal module dependencies from the moving
`dev-main` branch. Module repositories and Simbioza do not commit
`composer.lock`; CI runs `composer update --with-all-dependencies` on every run,
resolves the latest `main` heads, and then executes the complete
`composer on-commit` suite.

## Quick reference

| Module | Required | Optional integrations |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | — |
| `module-auth` | Framework, ORM | API, Menu, Notification |
| `module-api` | Framework, Auth, ORM | Calendar, HTML Editor, Notification, Task, Workspace; Menu and Theme for the GUI only |
| `module-menu` | Framework | Auth |
| `module-theme` | Framework, `ext-zip` | Menu |
| `module-calendar` | Framework, Auth, ORM | API, HTML Editor, Menu, Theme |
| `module-editor-html` | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | API, Menu, Theme, Calendar, Workspace, Task, Comment |
| `module-email` | Framework, Auth, ORM | — |
| `module-notification` | Framework, Auth, ORM | API, Email |
| `module-workspace` | Framework, Auth, ORM | HTML Editor, Menu, Notification; Email only indirectly through Notification |
| `module-task` | Framework, Auth, ORM, HTML Editor, `ext-dom` | API, Workspace, Notification |
| `module-comment` | Framework, Auth, ORM, HTML Editor, Notification, `ext-mbstring` | Workspace, Theme |

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

## Graph

An arrow denotes a required dependency. A dashed relationship denotes an
optional integration.

```text
ORM ----------> Framework
Auth ---------> ORM + Framework
API ----------> Auth + ORM + Framework
Calendar -----> Auth + ORM + Framework
Email --------> Auth + ORM + Framework
Notification -> Auth + ORM + Framework
Workspace ----> Auth + ORM + Framework
Editor HTML --> Auth + ORM + Framework
Task ---------> Editor HTML + Auth + ORM + Framework
Comment ------> Editor HTML + Notification + Auth + ORM + Framework

Notification - - > Email
API          - - > Calendar, Workspace, Editor HTML, Notification, Task
Auth         - - > API, Menu, Notification
Calendar     - - > API, Editor HTML, Menu, Theme
Notification- - > API, Email
Workspace    - - > Editor HTML, Menu, Notification
Editor HTML  - - > API, Menu, Theme, Calendar, Workspace, Task, Comment
Task         - - > API, Workspace, Notification
Comment      - - > Workspace, Theme
```
