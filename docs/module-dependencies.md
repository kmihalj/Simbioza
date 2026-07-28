# Ovisnosti modula / Module dependencies

Ovaj dokument razlikuje **obavezne** ovisnosti, koje Composer mora instalirati, od
**opcionalnih integracija**, koje samo proširuju ponašanje kada je odgovarajući
modul prisutan. Opcionalni modul ne smije postati skriveni uvjet za osnovni rad.

This document distinguishes **required** dependencies, which Composer must
install, from **optional integrations**, which only extend behavior when the
corresponding module is present. An optional module must not become a hidden
requirement for basic operation.

Svi moduli koji koriste Framework zahtijevaju najmanje `v0.0.22` i dopuštaju
buduće stabilne verzije do `1.0`. Time nova instalacija ne može neprimjetno
ostati na starijem Frameworku iz zastarjelog locka, a `composer update` može
preuzeti sljedeću stabilnu `0.x` verziju.

All modules using the Framework require at least `v0.0.22` and allow future
stable versions below `1.0`. This prevents a new installation from silently
remaining on an older Framework version retained by a stale lock file while
allowing `composer update` to select the next stable `0.x` release.

## Brzi pregled / Quick reference

| Modul / Module | Obavezno / Required | Opcionalno / Optional |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | - |
| `module-auth` | Framework, ORM | Menu, Notification |
| `module-menu` | Framework | Auth |
| `module-theme` | Framework, `ext-zip` | Menu |
| `module-calendar` | Framework, Auth, ORM | Menu, Theme |
| `module-editor-html` | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | Menu, Theme, Calendar, Workspace, Task |
| `module-email` | Framework, Auth, ORM | - |
| `module-notification` | Framework, Auth, ORM | Email |
| `module-workspace` | Framework, Auth, ORM | HTML Editor, Menu, Notification; Email samo posredno kroz Notification / Email only indirectly through Notification |
| `module-task` | Framework, Auth, ORM, HTML Editor, `ext-dom` | Workspace, Notification |
| `module-demo` | PHP | - |

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
- `module-editor-html` radi samostalno bez Workspacea, Calendara, Taska, Themea i
  Menua; pojedine kontrole i renderer pojavljuju se samo uz instaliranu integraciju.
- `module-task` namjerno zahtijeva HTML Editor jer su definicije i stabilni UUID-evi
  zadataka dio verzioniranog HTML dokumenta.

EN:

- Required modules must be installed and listed before the dependent module in
  `app.modules.enabled`.
- Optional integrations use late service resolution and must fail closed without
  breaking the base module when a package or service is unavailable.
- `module-notification` does not require `module-email`. Without it,
  notifications remain in-app only.
- `module-workspace` does not require `module-notification`. Without it, the
  workflow works but sends no notifications.
- `module-editor-html` works standalone without Workspace, Calendar, Task, Theme,
  and Menu; each control and renderer appears only when its integration is installed.
- `module-task` intentionally requires HTML Editor because task definitions and
  stable task UUIDs belong to the versioned HTML document.

## Graf / Graph

Strelica označava obaveznu ovisnost. Isprekidana veza označava opcionalnu
integraciju.

An arrow denotes a required dependency. A dashed relationship denotes an
optional integration.

```text
ORM ----------> Framework
Auth ---------> ORM + Framework
Calendar -----> Auth + ORM + Framework
Email --------> Auth + ORM + Framework
Notification -> Auth + ORM + Framework
Workspace ----> Auth + ORM + Framework
Editor HTML --> Auth + ORM + Framework
Task ---------> Editor HTML + Auth + ORM + Framework

Notification - - > Email
Workspace    - - > Editor HTML, Menu, Notification
Editor HTML  - - > Menu, Theme, Calendar, Workspace, Task
Task         - - > Workspace, Notification
```
