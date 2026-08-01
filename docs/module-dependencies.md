# Ovisnosti modula / Module dependencies

Ovaj dokument razlikuje **obavezne** ovisnosti, koje Composer mora instalirati, od
**opcionalnih integracija**, koje samo proširuju ponašanje kada je odgovarajući
modul prisutan. Opcionalni modul ne smije postati skriveni uvjet za osnovni rad.

This document distinguishes **required** dependencies, which Composer must
install, from **optional integrations**, which only extend behavior when the
corresponding module is present. An optional module must not become a hidden
requirement for basic operation.

Svi moduli koriste Framework i međusobne interne ovisnosti s pomične grane
`dev-main`. Repozitoriji modula i HFClean ne spremaju `composer.lock`; CI svaki
put pokreće `composer update --with-all-dependencies`, dohvaća najnovija stanja
grana `main` i zatim izvršava puni `composer on-commit`.

All modules use the Framework and internal module dependencies from the moving
`dev-main` branch. Module repositories and HFClean do not commit
`composer.lock`; CI runs `composer update --with-all-dependencies` on every run,
resolves the latest `main` heads, and then executes the complete
`composer on-commit` suite.

## Brzi pregled / Quick reference

| Modul / Module | Obavezno / Required | Opcionalno / Optional |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | - |
| `module-auth` | Framework, ORM | API, Menu, Notification |
| `module-api` | Framework, Auth, ORM | Calendar, HTML Editor, Notification, Task, Workspace; Menu i Theme samo za GUI / Menu and Theme for GUI only |
| `module-menu` | Framework | Auth |
| `module-theme` | Framework, `ext-zip` | Menu |
| `module-calendar` | Framework, Auth, ORM | API, HTML Editor, Menu, Theme |
| `module-editor-html` | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | API, Menu, Theme, Calendar, Workspace, Task, Comment |
| `module-email` | Framework, Auth, ORM | - |
| `module-notification` | Framework, Auth, ORM | API, Email |
| `module-workspace` | Framework, Auth, ORM | HTML Editor, Menu, Notification; Email samo posredno kroz Notification / Email only indirectly through Notification |
| `module-task` | Framework, Auth, ORM, HTML Editor, `ext-dom` | API, Workspace, Notification |
| `module-comment` | Framework, Auth, ORM, HTML Editor, Notification, `ext-mbstring` | Workspace, Theme |

## Pravila učitavanja / Loading rules

HR:

- Obavezni moduli moraju biti instalirani i navedeni prije ovisnog modula u
  `app.modules.enabled`.
- Opcionalne integracije koriste kasno razrješavanje servisa i moraju se mirno
  isključiti kada paket ili servis nije dostupan.
- `module-notification` ne zahtijeva `module-email`. Bez njega obavijesti ostaju
  samo u aplikaciji.
- `module-workspace` ne zahtijeva `module-notification`. Bez njega workflow radi,
  ali ne šalje obavijesti.
- `module-editor-html` radi samostalno bez Workspacea, Calendara, Taska, Commenta,
  Themea i Menua; pojedine kontrole i renderer pojavljuju se samo uz instaliranu
  integraciju.
- `module-task` namjerno zahtijeva HTML Editor jer su definicije i stabilni UUID-evi
  zadataka dio verzioniranog HTML dokumenta.
- `module-comment` koristi Editorov dokument i prava čitanja, Notification za
  prijave neprimjerenog sadržaja te opcionalno Workspaceova prava objavljivanja.
- `module-api` zahtijeva samo Auth i ORM. Calendar, HTML Editor, Notification,
  Task i Workspace rute registrira samo kada je odgovarajući paket instaliran i
  modul uključen.

EN:

- Required modules must be installed and listed before the dependent module in
  `app.modules.enabled`.
- Optional integrations use late service resolution and must fail closed without
  breaking the base module when a package or service is unavailable.
- `module-notification` does not require `module-email`. Without it,
  notifications remain in-app only.
- `module-workspace` does not require `module-notification`. Without it, the
  workflow works but sends no notifications.
- `module-editor-html` works standalone without Workspace, Calendar, Task, Comment,
  Theme, and Menu; each control and renderer appears only when its integration is
  installed.
- `module-task` intentionally requires HTML Editor because task definitions and
  stable task UUIDs belong to the versioned HTML document.
- `module-comment` uses Editor documents and read access, Notification for
  inappropriate-content reports, and optionally Workspace publish permissions.
- `module-api` requires only Auth and ORM. Calendar, HTML Editor, Notification,
  Task, and Workspace routes are registered only when the corresponding package
  is installed and the module is enabled.

## Graf / Graph

Strelica označava obaveznu ovisnost. Isprekidana veza označava opcionalnu
integraciju.

An arrow denotes a required dependency. A dashed relationship denotes an
optional integration.

```text
ORM ----------> Framework
Auth ---------> ORM + Framework
API ----------> Auth + Framework
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
