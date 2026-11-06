# Ovisnosti modula

Simbioza povezuje neovisne module u cjelovitu aplikaciju. Razlikujte pravilo
instalacije Simbioze od Composer ovisnosti samog modula: paket može raditi u
drugoj aplikaciji, a Simbioza uvijek uključuje svoju zajedničku jezgru.

## Šest obaveznih modula Simbioze

Svaka instalacija ima **Auth, ORM, Menu, Theme, Backup i Audit**: identitete i
ovlasti, rad s bazom, navigaciju, izgled, sigurnosne kopije i dnevnik aktivnosti.
Upravljanje modulima ne dopušta uklanjanje tih šest modula. API i Accessibility
su opcionalni.

Installer nudi cijelu Simbiozu i uže profile:

| Profil | Dodatni odabir; nužne ovisnosti uključuju se automatski |
| --- | --- |
| Cijela Simbioza | Svi dostupni opcionalni moduli |
| HTML stranice | HTML Editor |
| Kalendar | Calendar |
| Wiki | Workspace, Workspace Search i Simbioza User |
| Ankete | Survey, uključujući njegovu ovisnost o HTML Editoru |
| Zajednička jezgra | Samo šest obaveznih modula |

Dodatne module možete kasnije instalirati kroz **Postavke → Moduli** ili CLI
`modules`. Isključeni paket nije isto što i uklonjeni. Prije uklanjanja provjerite
ovisne module i sigurnosnu kopiju podataka; ne zaobilazite provjere izmjenom
samo Composera ili JSON menija. Ispravno podešen zaštićeni PHP-FPM omogućuje
GUI održavanje; ostale klasične instalacije koriste dokumentirani CLI postupak
nadogradnje. Vidi [instalaciju](installation_hr.md) i [modularnost](modularity_hr.md).

## Izravne ovisnosti paketa

Tablica odgovara popisima `require` i `suggest` u `composer.json` svakog paketa.
Framework i posredna PHP proširenja nisu ponavljani rekurzivno. Composer mora
zadovoljiti cijeli graf; pokrenite `composer check-platform-reqs` za instalirane
pakete i stvarni PHP. Opcionalni vlasnički moduli mogu dodavati API rute,
događaje ili Backup providere bez stvaranja ovisnosti u jezgri.

| Paket | Izravno obavezni paketi/proširenja | Opcionalne predložene integracije |
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

## Učitavanje i vlasništvo

- Generički HeartPhrame domaćin uključuje pakete u `app.modules.enabled`, uz
  dostupne obavezne module prije ovisnih. Simbioza taj popis izvodi iz središnjeg
  kataloga i spremljenog odabira modula.
- Opcionalna integracija ostaje neaktivna bez uključenog vlasničkog modula.
  Skrivena stavka menija nikada ne zamjenjuje autorizaciju rute.
- HTML Editor radi bez Područja. U formi može uređivati textarea polje bez
  stvaranja dokumenta; slijedite [cjelovit primjer integracije](user-guide/html-editor-u-obrascu_hr.md).
  Naknadna instalacija Područja ne premješta samostalne stranice automatski.
- Workspace upravlja hijerarhijom i pravima objave, a Editor HTML-om i privitcima.
  Task i Comment koriste identitet objavljenog dokumenta i njegova prava.
- API upravlja Bearer prijenosom i otkrivanjem mogućnosti. Uključeni vlasnički
  moduli prijavljuju rute i provjeravaju ovlasti. Survey, Audit, Search i osobne
  User integracije uvjetne su; vidi [stvarni API ugovor](api-v1-contract_hr.md).
- Backup upravlja prenosivim arhivima. Uključeni moduli prijavljuju svoj sadržaj,
  postavke i završne poslove. Indeksi pretrage, redovi, sesije i tehnički logovi
  prolazni su podaci i ne vraćaju se kao poslovni sadržaj.
- Notification bez Emaila ostaje inbox u aplikaciji. Instalacija Emaila ne
  pokreće worker; podesite SMTP i izričito rasporedite slanje. Poslovi izvještaja
  anketa također trebaju svoj raspored i aktualna prava primatelja.
- Simbioza User upravlja praćenjima, osobnim postavkama i opcionalnim osobnim
  područjima. Auth radi i bez toga. Accessibility čuva prilagodbe u pregledniku
  i ne dodjeljuje pravo pristupa sadržaju.
- Confluence Import pretvara vanjski arhiv u običan Workspace/Editor sadržaj.
  Zapisi za razrješavanje poveznica metapodaci su integracije, ne druga vrsta
  područja. Uvezene skripte i dalje traže izričito administratorsko odobrenje.

Uz Wiki, upute ostaju u području **Korisničke upute**. Bez Wikija stavka
**Upute** prikazuje isti ugrađeni priručnik s HTML navigacijom.

## Verzije i provjera

Koristite podržana stabilna ograničenja iz manifesta. Aktualna linija Frameworka
jest `^0.0.25`; koristite izdanje njegova održavatelja i ne krpajte repozitorij
ni instalirani vendor kod. Interni paketi koriste kompatibilnu liniju `0.3.x`;
povijesni tagovi `1.x` nisu razlog za preskakanje na drugu glavnu liniju.

Ažuran vendor aplikacije ne ažurira zasebne razvojne ovisnosti susjednog modula.
U izdvojenom izvornom checkoutu provjerite `composer show`, pokrenite
`composer validate --strict`, razriješite predviđeni manifest pa pokrenite
provjere platforme i kvalitete. U generiranom produkcijskom paketu čuvajte
stvarni lock/popis paketa za ponovljivost, iako izvorne biblioteke ne spremaju
`composer.lock` u Git.

Redovni CI Simbioze koristi deklarirani stabilni katalog; izričiti `--local`
koristi dopuštene susjedne checkoutove. CI grana modula može koristiti razvojne
aliase, a CI označenih izdanja provjerava stabilna ograničenja. JavaScript alati
koriste `package-lock.json` u Gitu i `npm ci`. Vidi
[end-to-end testiranje](end-to-end-testing_hr.md) i
[održavanje dokumentacije](documentation-quality_hr.md).
