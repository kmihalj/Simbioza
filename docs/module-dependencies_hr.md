# Ovisnosti modula

Ovaj dokument razlikuje **obavezne ovisnosti**, koje Composer mora instalirati,
od **opcionalnih integracija**, koje samo proširuju ponašanje kada je
odgovarajući modul prisutan. Opcionalni modul ne smije postati skriveni uvjet
za osnovni rad.

Svi moduli koriste označeno izdanje Frameworka `^0.0.25` i kompatibilna izdanja
internih modula iz linije `^0.1.0`. Repozitoriji modula i Simbioza ne spremaju
`composer.lock`; CI pri svakom pokretanju izvršava
`composer update --with-all-dependencies`, dohvaća najnovije kompatibilne tagove
i zatim pokreće puni `composer on-commit`.

## Brzi pregled

| Modul | Obavezno | Opcionalne integracije |
|---|---|---|
| `module-orm` | Framework, `ext-pdo` | — |
| `module-backup` | Framework, ORM, `ext-json`, `ext-zip` | Auth i Menu za administratorski GUI; poslovni moduli prijavljuju vlastite providere |
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
| `module-workspace-search` | Framework, Workspace, Menu, Auth, ORM, HTML Editor | API, Backup |
| `module-audit` | Framework, Auth, ORM | Menu za Postavke, Backup za prenosivi dnevnik aktivnosti, API za `audit:read` te svi instalirani proizvođači poslovnih događaja |
| `simbioza-module-user` | Framework, Auth, Notification, ORM, Workspace | API, Audit, Backup, Calendar, Comment, Email, Task, Theme |
| `simbioza-module-confluence-import` | Framework, Auth, HTML Editor, Menu, ORM, Workspace, Simbioza User, `ext-dom`, `ext-fileinfo`, `ext-json`, `ext-mbstring`, `ext-zip` | API, Audit, Backup, Calendar, Comment, Task, Workspace Search |

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
- `module-backup` zahtijeva samo ORM. CLI ostaje dostupan bez Autha i Menua;
  u Simbiozi Auth štiti `/settings/backups`, a Menu prikazuje stavku
  **Postavke → Sigurnosne kopije → Backup i vraćanje**.
- Poslovni moduli ne ovise tvrdo o Backupu. Kada je Backup uključen, svaki od
  njih opcionalno prijavljuje vlastite tablične, datotečne ili završne providere.
- `module-workspace-search` zahtijeva Workspace i Menu. Indeks nije dio arhiva;
  Backup nakon uspješnog vraćanja pokreće njegovu ponovnu izgradnju.
- `module-audit` čuva poslovni dnevnik aktivnosti u bazi, a odvojeni PSR-3
  tehnički log u rotirajućim datotekama. Poslovni moduli rade i bez Audita te
  objavljuju neutralne događaje gdje je potreban precizniji zapis. Datoteke
  tehničkog loga nikad se ne registriraju u Backupu.
- `simbioza-module-user` sluša neutralni Auth događaj uspješne prijave i tada
  prema pravilima izrađuje ograničeno osobno područje. Auth zato i dalje radi
  bez Workspacea i Simbioza Usera. Backup čuva korisnička mapiranja,
  administratorska pravila i mapiranja unutar pojedinog područja.
- `simbioza-module-confluence-import` pretvara Confluence XML arhivu u područje,
  dokumente, privitke, identitete i prava. Simbioza User osigurava stabilno
  povezivanje uvezenih identiteta, dok se dodatne integracije uključuju samo ako
  je odgovarajući modul instaliran.

## Graf

Strelica označava obaveznu ovisnost. Isprekidana veza označava opcionalnu
integraciju.

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
Audit        - - > Menu, Backup, API, svi proizvođači poslovnih događaja
Simbioza User- - > API, Audit, Backup, Calendar, Comment, Email, Task, Theme
Confluence Import - - > API, Audit, Backup, Calendar, Comment, Task, Workspace Search
```
