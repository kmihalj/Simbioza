# Ovisnosti modula

Ovaj dokument razlikuje **obavezne ovisnosti**, koje Composer mora instalirati,
od **opcionalnih integracija**, koje samo proširuju ponašanje kada je
odgovarajući modul prisutan. Opcionalni modul ne smije postati skriveni uvjet
za osnovni rad.

Svi moduli koriste Framework i međusobne interne ovisnosti s pomične grane
`dev-main`. Repozitoriji modula i HFClean ne spremaju `composer.lock`; CI pri
svakom pokretanju izvršava `composer update --with-all-dependencies`, dohvaća
najnovija stanja grana `main` i zatim pokreće puni `composer on-commit`.

## Brzi pregled

| Modul | Obavezno | Opcionalne integracije |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | — |
| `module-auth` | Framework, ORM | API, Menu, Notification |
| `module-api` | Framework, Auth, ORM | Calendar, HTML Editor, Notification, Task, Workspace; Menu i Theme samo za GUI |
| `module-menu` | Framework | Auth |
| `module-theme` | Framework, `ext-zip` | Menu |
| `module-calendar` | Framework, Auth, ORM | API, HTML Editor, Menu, Theme |
| `module-editor-html` | Framework, Auth, ORM, `ext-dom`, `ext-fileinfo`, `ext-mbstring`, `ext-zip` | API, Menu, Theme, Calendar, Workspace, Task, Comment |
| `module-email` | Framework, Auth, ORM | — |
| `module-notification` | Framework, Auth, ORM | API, Email |
| `module-workspace` | Framework, Auth, ORM | HTML Editor, Menu, Notification; Email samo posredno kroz Notification |
| `module-task` | Framework, Auth, ORM, HTML Editor, `ext-dom` | API, Workspace, Notification |
| `module-comment` | Framework, Auth, ORM, HTML Editor, Notification, `ext-mbstring` | Workspace, Theme |

## Pravila učitavanja

- Obavezni moduli moraju biti instalirani i navedeni prije ovisnog modula u
  `app.modules.enabled`.
- Opcionalne integracije koriste kasno razrješavanje servisa i moraju se sigurno
  isključiti bez prekida osnovnog modula kada paket ili servis nije dostupan.
- `module-notification` ne zahtijeva `module-email`. Bez njega obavijesti ostaju
  samo u aplikaciji.
- `module-workspace` ne zahtijeva `module-notification`. Bez njega workflow radi,
  ali ne šalje obavijesti.
- `module-editor-html` radi samostalno bez Workspacea, Calendara, Taska, Commenta,
  Themea i Menua; pojedine kontrole i renderer pojavljuju se samo uz instaliranu
  integraciju.
- `module-task` namjerno zahtijeva HTML Editor jer su definicije i stabilni
  UUID-evi zadataka dio verzioniranog HTML dokumenta.
- `module-comment` koristi Editorov dokument i prava čitanja, Notification za
  prijave neprimjerenog sadržaja te opcionalno Workspaceova prava objavljivanja.
- `module-api` zahtijeva samo Auth i ORM. Calendar, HTML Editor, Notification,
  Task i Workspace rute registrira samo kada je odgovarajući paket instaliran i
  modul uključen.

## Graf

Strelica označava obaveznu ovisnost. Isprekidana veza označava opcionalnu
integraciju.

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
