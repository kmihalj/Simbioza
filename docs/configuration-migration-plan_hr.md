# Plan jedinstvene konfiguracije Simbioze i modula

[Engleska verzija](configuration-migration-plan_en.md)

Izrađeno: 9. listopada 2026. Ažurirano: 12. listopada 2026.

**Aktualni nastavak:** donja nadogradnja konfiguracije povijesni je zapis.
Koristiti [audit ispravke](pre-release-audit-0.3.0_hr.md#ispravci-redoslijedom):
A1/Auth, A2/Notification, A3/runtime jezici, A4/nepromjenjivi deployment i
A5/dosljednost uputa/komentara lokalno su dovršeni. Sljedeće je izričito odobrenje
izdanja, pa provjera objavljenih artefakata i release CI-ja.
Ne nastavljati stare migracijske korake ni odgođeni import cijelog Confluence sitea.

**Nova granica izdanja:** 0.3.0 je početno izdanje za novu instalaciju. Nadogradnje starih Simbioza verzija i kompatibilnost starih kopija više nisu zahtjevi. Nastaviti prema [popisu za čisto izdanje 0.3.0](initial-release-0.3.0_hr.md). Raniji detalji i provjere ispod povijesni su zapis, ne aktualne instalacijske upute.

Ovaj dokument je trajna točka nastavka između razvojnih sesija. Promjene se
rade po jednom provjerljivom zadatku, ne u svim modulima odjednom. Plan opisuje
dogovoreno ciljno stanje; ne tvrdi da već svi dijelovi koriste nove putanje.

## P3 i P4 dovršeni lokalno — HTML Editor i Workspace

Globalne postavke koriste `HPH_CONFIG_PATH/editor-html/editor-html.json` i
`HPH_CONFIG_PATH/workspace/workspace.json`. Samostalni Editor i runtime
nadjačavanja Workspacea očuvani su bez njihovog trajnog zapisa. Globalne
Workspace naslovnice premještene su iz baze; sadržaj, ACL i osobni izbori
ostaju u njoj. Stare instalacijske postavke izvozi samo aplikacijski upgrade.
Generički Editor ima opcionalni administratorski GUI; Workspace zadržava
Simbiozin GUI. Uvoz starih Simbioza backup zapisa provjeren je prema sadašnjem
JSON-u, bez vraćanja suvišnih konfiguracijskih tablica. To nije potvrda potpune
prenosivosti proizvoljnih apsolutnih URL-ova; taj uvjet ostaje u P10.

## Gdje nastaviti

- **P10 ostaje otvoren samo za prenosivost autorskih poveznica između siteova.**
  Lokalne provjere konfiguracije, instalacija i nadogradnji dovršene su:
  pune aplikacijske/modulne provjere kvalitete, SQLite matrica s 21 kombinacijom,
  čiste PostgreSQL/MySQL/MariaDB instalacije svih modula, šest Linux/root
  provjera, stvarni standardni/sigurni FPM i izolirani Apache/mod_php prolaze.
  Cijeli browser/API/performance skup prolazi na SQLiteu, PostgreSQL-u i
  MySQL-u, **po 80/80**. Vlastite testne instalacije i baze uklonjene su.
  Potvrđen je nedostatak prenosivosti autorskih poveznica, opisan niže;
  ne zatvarati P10 niti tvrditi da je restore između siteova potpuno prenosiv.
  Objava izdanja nije odobrena. Vidi [dokaze P10](#dokazi-završne-provjere-p10).
  P9 je lokalno dovršen: Comment, Task i Notification čuvaju nužne podatke
  dokumenata/korisnika u bazi, a Accessibility osobne odabire u pregledniku.
  Nema dodatnih globalnih politika za selidbu niti praznih config direktorija
  za stvaranje. Zajednička konfiguracija/odabir modula prate `HPH_CONFIG_PATH`
  i override slojeve bez GUI-ja postavki. Read-only samostalna integracija,
  domensko stanje i postojeći browser/API/backup tokovi prolaze.
  P8 je lokalno
  dovršen: Backup koristi `HPH_CONFIG_PATH/backup/`, API `api/api.php`; oba
  poštuju vanjski korijen i override slojeve bez GUI-ja. Audit koristi zajedničke
  app postavke, a ORM zadržava zajednički `database.php` u korijenu konfiguracije.
  Provjerena je Authova opcionalna verifikacija providera, bez ponavljanja migracije.
  P7R je lokalno dovršen:
  vlasnički HTML, privitci, posebni meniji i privatne teme grupirani su unutar
  `data/workspaces/<oznaka-pohrane>--<ID>/`. HFC zadržava filesystem HTML driver; HTML iz baze
  ostaje u bazi. Uvoz i promjena drivera poštuju vlasništvo i lokalne ciljeve.
  Dovršena je i dorada čitljivih oznaka uz stabilne ID-eve/UUID-ove, updater selidbe
  s usklađivanjem baze/indeksa, uklanjanje provjerenih duplikata i zaseban oporavak
  nepoznatih starih Editor datoteka.
  Pogledati [pohranu podataka područja](workspace-data_hr.md) i dokaze P7R niže.
  P7 Menu i Theme koriste `HPH_CONFIG_PATH/menu/` i `theme/`; GUI i valjane
  ručne izmjene koriste iste ciljeve.
  P6 Calendar lokalno
  je dovršen: aktivna konfiguracija je `HPH_CONFIG_PATH/calendar/calendar.php`,
  uz nepromijenjeni GUI i bazu kalendarskih podataka. P1A–P1D, P2 Email te P3
  HTML Editor i P4 Workspace također su lokalno dovršeni. D3 je uskladio komentare
  i primjere: cijeli engleski blok, a zatim cijeli hrvatski blok.
  Updater i backup/vraćanje aplikacijskih postavki koriste zajedničke putanje.
  Nadogradnje s vanjskim configom, oporavak, slojeviti restore i generiranje
  konfiguracije sigurnog FPM-a provjereni su na izoliranim instalacijama.
  P2 je dodatno provjeren na stvarnom standardnom i sigurnom FPM-u u Colimi.
  Email koristi `HPH_CONFIG_PATH/email/email.json`; HFC je usklađen i stare SMTP
  postavke su očuvane. Dokazi i ograničenja su niže.
- P3/P4 provjereni su punim modulskim i aplikacijskim testovima, preglednikom/API-jem
  te stvarnim standardnim i sigurnim FPM-om u Colimi. HFC je usklađen; nakon P8
  migracije su sada **45 izvršenih / 0 preostalih** nakon dorade čitljivih P7R direktorija.
  Stari Email PHP predložak uklonjen
  je; Auth/Email/Editor/Workspace imaju dokumentirane JSON primjere.
- Jezici, čitanje konfiguracije pri upravljanju
  modulima, dijagnostika i Setup worker koriste zajedničke putanje. Odabir modula HFC-a je u
  `config/modules.php`; izvorni sadržaj i ostale privatne postavke su očuvani.
- V1 je riješen: puni PHPUnit te produkcijske/razvojne PHPStan provjere prolaze.
  Platformske root/Linux provjere prolaze u izoliranim Colima kontejnerima,
  uključujući pozadinski deployment worker; aktualni opseg provjera je niže.
- Auth je lokalno doradio konfiguraciju; korisnik je 9. listopada potvrdio da
  HFC radi korektno. To nije potvrda objave izdanja ni završne matrice instalacija.
- Aktualni uvod: [framework parametri, zajedničke putanje i konfiguracije modula](configuration_hr.md).

Za nastavak pročitaj ovaj odjeljak, otvorenu listu i detalje sljedećeg zadatka.
Ne ponavljaj završene stavke bez pronađene regresije. Provjeri stvarno stanje
datoteka prije oslanjanja na ovaj zapis.

## Cilj i granice

1. Svi moduli i aplikacijski postupci koriste `HPH_CONFIG_PATH` za aktivnu
   konfiguraciju instalacije. Paketni predlošci i servisni kod ostaju u svojim
   repozitorijima; sadržaj, privitci, logovi, cache i privremeni poslovi nisu config.
   Svaki modul ima svoj poddirektorij: `HPH_CONFIG_PATH/<modul>/<datoteka>`,
   primjerice `calendar/calendar.php`. Korijenske aplikacijske i framework
   datoteke ostaju odvojene od modulovih datoteka.
2. Ručno podešavanje i GUI koji uređuje konfiguraciju koriste isti izvor i iste
   servise. Obvezno je poštovanje `HPH_CONFIG_PATH`, ne isključivanje GUI-ja u
   svakom modulu. Generički moduli na opencode.hr ne ovise o Simbiozinu GUI-ju za
   podešavanje instalacije. Opcionalna administracija uvodi se samo gdje ima
   smisla; postojeći GUI za kalendare, sadržaj i domenske podatke ostaje.
   Kada postoji prekidač administracije, ne smije gasiti rad modula, CLI, API ni
   SSO callback rute, a mora zaštititi izravne administratorske rute, ne samo
   sakriti izbornik. Simbiozini moduli na GitHubu zadržavaju GUI i povezanost s
   aplikacijom; njihov samostalni rad nije zahtjev.
3. Nema dva autoritativna izvora iste postavke. Baza čuva domenske podatke i
   nužne identitete/veze, a dopuštene projekcije konfiguracije imaju jedan izvor.
4. HeartPhrame framework ne mijenjamo, uključujući njegovu kopiju u `vendor`.
   Koristimo postojeći `HeartPhrame\Config\ConfigPathResolver::fromEnvironment()`.
   Generički moduli ne smiju dobiti obveznu ovisnost o Simbiozi, Authu ili GUI-ju
   samo radi čitanja/zapisivanja konfiguracije.
5. Sačuvati postojeće privatne postavke. `.dist` je primjer, ne zamjena za
   aktivnu konfiguraciju. Tajne, privatne URL-ove i stvarne config datoteke ne
   zapisivati u plan, testne izvještaje ili Git.
6. Migracija starih postavki zaseban je upgrade. Čista instalacija neposredno
   stvara aktualno stanje, bez izvoza i čišćenja stare sheme.
   Dogovorena granica izvorne verzije je **0.2.12 ili starija**. Novija izdanja
   već koriste ciljnu strukturu i ne ponavljaju stare prijenose ni čišćenje.
   Predaja updateru mora zadržati izvornu verziju, prvo pokrenuti novi updater
   i potvrditi podatke/konfiguraciju na odredištu prije uklanjanja starih
   datoteka, stupaca ili tablica. Čisti se samo suvišna konfiguracija, nikad
   nužni korisnici, grupe, veze ili sadržaj.
7. HFC je integrirana razvojna instalacija i nakon zatvorenog zadatka dobiva
   najnoviji kod. Korisnik je izričito isključio backup HFC-a za ovaj razvoj;
   potrebne postojeće postavke ipak moraju biti očuvane. `apps-test` i demo
   nadograđuje korisnik; ovaj plan ne ovlašćuje njihovo mijenjanje.
8. Komentari u kodu, dokumentacija metoda i dvojezični opisi primjera prvo imaju
   cijeli engleski blok, a zatim cijeli hrvatski blok. Ne izmjenjivati prevedene
   retke. README i docs ostaju jezično odvojeni i ažuriraju se uz svaku promjenu
   ponašanja.
9. Commit, push, oznake i release nisu automatski dio ovog plana. Objava traži
   zasebnu korisnikovu odluku i završnu provjeru.

## Pravilo praćenja napretka

- U otvorenoj listi ostaju samo nedovršeni zadaci, s nepromjenjivim oznakama.
- Kada zadatak prođe svoje uvjete završetka, ukloni ga iz otvorene liste i dodaj
  redak u zapis dovršenog: datum, promijenjeni repozitoriji/datoteke, provedene
  provjere, stanje HFC-a i eventualna ograničenja.
- Istodobno ažuriraj obje jezične verzije i odjeljak „Gdje nastaviti”.
- Ako sesija stane usred zadatka, upiši što je napravljeno, što nije provjereno
  i točan sljedeći mali korak. Nedovršen zadatak ne označavati završenim zbog
  prekida ili manjka tokena. Ne tvrditi da je planirana provjera već provedena.
- Status lokalne implementacije, provjere HFC-a i objave izdanja odvojene su stvari.

## Otvorena to-do lista

- [ ] **P10:** dovršiti prenosivost autorskih poveznica i njezinu završnu provjeru.
  - [x] Lokalne konfiguracijske, instalacijske/upgrade, platformske i provjere kvalitete.
  - [x] Cijeli browser/API/performance skup: SQLite, PostgreSQL i MySQL, po 80/80.
  - [x] Uskladiti runtime i dokumentaciju HFC modula; ukloniti vlastite testne instalacije.
  - [ ] Razlikovati i remapirati interne HTML/Workspace URL-ove između domena,
    početnih putanja i slugova, uz očuvanje vanjskih poveznica i uvoza starih arhiva.
  - [ ] Provjeriti preostalo ponašanje URL-ova i ponoviti zahvaćene testne skupove.

## Detalji zadataka

### P7R lokalno dovršen

Aktualna struktura: `data/workspaces/<oznaka-pohrane>--<ID>/pages/<oznaka-stranice>--<UUID-dokumenta>/content/`,
`attachments/` i `menus/`; meniji područja i privatne teme koriste
`data/workspaces/<oznaka-pohrane>--<ID>/menus/` i `theme/`. Izričito vlasništvo određuje direktorij;
samostalni/zajednički dokumenti zadržavaju konfigurirane korijene Editora.
HTML iz baze ostaje u bazi. Generički moduli i dalje rade bez Workspacea.

`20261010220000_organize_workspace_data` i dorada čitljivih oznaka
`20261011120000_readable_workspace_data` jednokratne su aplikacijske nadogradnje za
izvorna izdanja 0.2.12 ili starija. Čiste instalacije evidentiraju ga bez
učitavanja starog koda prijenosa. ASCII oznake ograničene su na 48 znakova i
povezane s trajnim ID-em/potpunim UUID-om. `workspaces.storage_slug` čuva početnu
oznaku; postojeće reference stranice čuvaju njezinu oznaku pri preimenovanju ili
brisanju/vraćanju. Potvrđeni stari duplikati se uklanjaju; ostale nereferencirane
datoteke zadanog Editora sele u `data/recovered/editor-html/`, ne u aktivni sadržaj.
GUI/CLI updater, promjena drivera pohrane,
novi Confluence uvozi i backup/vraćanje koriste aktualnu strukturu. Ciljevi se
provjeravaju prije prijenosa; pri obnovi se vlasnici preslikavaju prije zapisa
putanja. Pogledati [pohranu podataka područja](workspace-data_hr.md) za pravila
i zapis dovršenog niže za provedene provjere. Sljedeći zadaci ne smiju ponavljati ovu migraciju.

### P8 lokalno dovršen

Backup politika i opcionalne registracije host providera koriste
`HPH_CONFIG_PATH/backup/backup.php` i `backup/backup-providers.php`.
API politika koristi `HPH_CONFIG_PATH/api/api.php`. Neizmijenjeni framework
učitava ugniježđeno stablo, a adapter modula normalizira samo njegovu politiku.
Mape se spajaju, popisi zamjenjuju, uključujući prazne popise. Zadnji postojeći
host descriptor vrijedi kao cijela datoteka; opisi paketnih providera ostaju
kod paketa. Nedostajući ili nečitljivi deklarirani slojevi prekidaju rad
umjesto tihog povratka na drugi izvor.

Audit nema zaseban instalacijski config: koristi `app.timezone` i `app.logs`;
audit zapisi ostaju u bazi, tehnički logovi u podacima. ORM i Framework Database
dijele `database.connections`, zato `HPH_CONFIG_PATH/database.php` namjerno
ostaje zajednička korijenska datoteka, ne dupli `orm/database.php`.
Authov `login.require_provider_verification` ostaje opcionalan u generičkom
projektu; nisu dodani novi GUI prekidač ni Auth migracija.

`20261011000000_move_backup_api_configuration` aplikacijski je upgrade samo
za izvorna izdanja 0.2.12 ili starija. Prije uklanjanja starih datoteka
provjerava očuvanje PHP vrijednosti, slojeva, prava i vlasništva. Updater
obavlja prijenos prije bootstrapa novih modula; zadani config izdanja ne smije
zamijeniti privatne postavke, a oporavak nakon greške vraća stare putanje.
Čista instalacija evidentira migraciju bez učitavanja starog koda prijenosa.
Aktualni primjeri su `config/backup.php.dist` i `config/api.php.dist`; kopiraju
se u ugniježđene aktivne putanje, nisu aktivne korijenske konfiguracije.

### P1A — dovršen lokalni temelj

Implementiran kao `src/Configuration/ConfigurationPaths.php`, registriran u
`config/services.php` i korišten u normalnoj HTTP početnoj točki. Aktualno
ponašanje i API opisani su u [konfiguracijskim uputama](configuration_hr.md).
Sljedeće točke ostaju zabilježeni uvjeti opsega i provjere, ne otvoreni zadaci.

Opseg: samo Simbiozin dogovor o putanjama, aplikacijski pomoćni sloj i njegovi
izolirani testovi. Ne seliti još postavke, ne mijenjati bazu i ne doraditi Email
u istom zadatku.

1. Provjeriti `bootstrap.php`, `framework.php.dist`, `public/index.php` i postojeći
   framework resolver. Zadržati prednost nepraznog okruženja procesa pred
   privatnim `framework.php` te zadani aplikacijski `config/` kada nema prilagodbe.
2. Odrediti jedan aplikacijski način dobivanja baznog direktorija, uređenih
   slojeva `HPH_CONFIG_OVERRIDE_PATHS` i putanja konkretnih datoteka. Ne kopirati
   framework resolver u novi vlastiti parser.
3. Definirati čitanje slojeva i cilj GUI/CLI zapisa: ne zapisivati u datoteku
   nižeg prioriteta koju drugi sloj nadjačava. Sloj samo za čitanje mora raditi
   bez GUI-ja, a pokušaj spremanja mora jasno prijaviti ograničenje.
4. U prvom zadatku zadržati postojeće formate. Pri doradi modula zasebno
   dogovoriti zapisivu podatkovnu konfiguraciju, po mogućnosti JSON poput Autha,
   odvojeno od izvršivog PHP koda. Ne stvarati framework sudar naziva kao što su
   `auth.php` i direktorij `auth/` u istom konfiguracijskom sloju.
5. Testirati zadanu putanju, apsolutni vanjski direktorij, prioritet slojeva,
   nedostupnu/nevaljanu putanju i jednaka pravila za HTTP i CLI. Testovi koriste
   izolirane lažne postavke, ne HFC-ove tajne.

Uvjet završetka: pomoćni sloj i ciljani testovi prolaze, postojeći zadani HFC
nastavlja raditi i nijedna aktivna postavka nije premještena ili prepisana.
Upisati stvarne nazive novih datoteka i provedene provjere u zapis dovršenog.

### P1B — dovršena lokalna instalacija i odabir modula

- `src/Installation/InstallationPaths.php`, `InstallationConfigWriter.php`,
  `InstallationRunner.php` i instalacijski dio `public/index.php` koriste isti
  dogovor o putanjama prije pune inicijalizacije aplikacije.
- Premjestiti autoritativni administratorski odabir iz `data/config/modules.php`
  pod konfiguracijski direktorij; prilagoditi `src/Module/ModuleStateStore.php`
  i početno učitavanje. Evidencija dovršenih poslova i instalacijski lock ostaju
  izvršno stanje, ne konfiguracija.
- Prelazak postojeće instalacije očuva odabir; stare putanje čitati samo u
  eksplicitnom prijelazu, ne kao trajni drugi izvor. Prije aktivacije vanjskog
  direktorija pripremiti njegovu potpunu potrebnu konfiguraciju.
- Provjerene su nova instalacija, postojeća konfiguracija i GUI/CLI uključivanje
  i isključivanje modula. P1D dovršava updaterovu integraciju prijenosa pri nadogradnji.

Implementirano i provjereno kako je zapisano niže. Provjera prava za config
odabira modula u `SetupDiagnostics.php` prilagođena je u ovom zadatku jer o njoj
ovisi postojeći GUI; ostala dijagnostika i putanje upravljanja dovršeni su u P1C.

### P1C — dovršeni lokalni jezici i dijagnostika

- `src/Localization/LanguagePackManager.php` i `RepositoryLanguageManager.php`
  koriste zajedničke putanje za potpuni registar `languages.php` najvišeg sloja
  i spojene aktivacijske postavke `installation.php`. GUI/CLI promjene zaključava
  `.languages.lock`, a zapis ide samo u zadnji deklarirani sloj s pravima `0660`.
  Prijevodi i evidencija revizija repozitorija ostaju u aplikacijskim podacima.
- `PhpConfigurationReader::readDocument()` i početno učitavanje u `config/app.php`
  poštuju uklanjanje iz registra umjesto vraćanja jezika iz nižeg sloja.
  Nepovezane jezične izmjene i privatne postavke HFC-a ostale su netaknute.
- `ModuleLifecycleManager.php` čita efektivne slojevite postavke pohrane editora
  za zaštitu pri uklanjanju Confluence Importa. `SetupDiagnostics.php` provjerava
  stvarne putanje i lockove bez prikaza tajni ili stvaranja config datoteka.
  Aktivacija jezika ima vlastitu GUI odluku o pravima.
- `scripts/setup_worker.php` čita slojeviti `setup.php`. Dijagnostika prikazuje
  stvarni direktorij zahtjeva gatewaya. Secure FPM na zadanoj putanji sada među
  runtime datoteke uključuje `languages.php` i `.languages.lock`; generiranje
  vanjskih putanja dovršeno je u P1D, a puna matrica servisa ostaje P10.
- Vanjski sloj, read-only config, HTTP/CLI, OPcache i Linux/root provjere prošle
  su kako je zapisano niže. HFC zadržava zadani config direktorij; vanjske postavke
  provjeravaju se na izoliranim instalacijama, bez premještanja privatnih HFC postavki.

### P1D — dovršeni lokalni updater i backup aplikacijske konfiguracije

- `update.php` verzije `1.0.2` čita izvorne slojeve konfiguracije frameworka prije
  punog pokretanja aplikacije, zadržava prednost samonadogradnje updatera i štiti
  privatne slojeve od sinkronizacije izdanja i arhiviranja koda.
- Eksplicitni prijelaz odabira modula radi nakon provjera ovisnosti, a prije
  aplikacijskog preflighta. Identičan cilj zadržava inode. Povrat koristi zajednički
  lock modula, privremene hardlinkove za vlasništvo i zasebne kopije izvornih bajtova
  za oporavak; istodobne izmjene zaustavljaju povrat bez prepisivanja tih promjena.
- Tvornica aplikacijskog backupa pretvara pouzdane lokalne definicije u aktivne
  slojeve. Dinamički `app.php` ostaje kod i uz stare opise. Arhiv ne bira ciljne
  putanje; u zadnji sloj vraćaju se samo dopuštene postavke.
- Generički Backup provider podržava izvorno slojevito čitanje, potpune dokumente,
  opcionalne lockove hosta, atomski zapis, očuvanje prava/vlasništva i čišćenje nakon
  prekida/dovršetka. Ne ovisi o Simbiozi ili njezinom GUI-ju.
- Generiranje sigurnog FPM poola/helpera fiksira iste config parametre i stvara
  read-only sandbox veze nižih slojeva i zapisivog zadnjeg sloja bez preuzimanja
  vlasništva nad vanjskim direktorijima. Worker koristi rani bootstrap i uz očišćeno
  okruženje. Stvarno izvršavanje pune matrice servisa ostaje provjera P10.
- HFC koristi novi aplikacijski kod i Backup modul na mjestu; privatna
  konfiguracija ostaje na zadanoj putanji. apps-test, demo i framework nisu
  mijenjani. Dokazi provjera zapisani su niže.

### P2 — dovršen Email

Prvi pojedinačni modul nakon dovršenog temelja. Opseg su SMTP, pošiljatelj,
pravila slanja i opcije radnika; `email_outbox` ostaje u bazi.

- Generički `EmailConfigurationStore.php` koristi neizmijenjeni framework resolver
  i uređene JSON slojeve `email/email.json`. Ne koristi Simbiozin kod ni stari
  aplikacijski `email.php`; paketni `config/email.php` sadrži samo zadane vrijednosti.
- Ručni config, GUI, CLI i worker imaju isti izvor. Zapis ide samo u zadnji
  deklarirani sloj, pod lockom i atomskom zamjenom, uz očuvanje prava/vlasništva,
  nepoznatih opcija i najnovije skrivene lozinke. Nevaljan/nedostupan config ne
  aktivira tiho drugi izvor. Read-only config omogućuje rad bez GUI zapisa.
- `gui.enabled` i `gui.test_enabled` štite i izravne rute, ne samo izbornik.
  Predložak je `heartphrame-module-email/config/email.json.dist`, a zasebne upute
  su u modulovim `docs/configuration_en.md` i `docs/configuration_hr.md`.
- Simbiozin upgrade `20261010140000_file_email_configuration.php` izvozi stare
  postavke, provjerava ih i tek tada uklanja nepromijenjene stare datoteke.
  Čista instalacija evidentira ovaj upgrade bez učitavanja izvoznog koda.
  Generički modul nema legacy migraciju pri pokretanju. Backup koristi iste JSON
  slojeve, lock i lokalnu validaciju; outbox se ne vraća iz backupa konfiguracije.

### P3–P9 — pojedinačne dorade

| Zadatak | Što mijenjamo | Što ostaje podatak, a ne config instalacije |
| --- | --- | --- |
| P3 HTML Editor | Globalne opcije i čitanje/zapisivanje configa; očuvanje postavki i predložak | Dokumenti, verzije, privitci, uključeni sadržaj i ACL |
| P4 Workspace | Fiksni PHP config; globalna javna/prijavljena naslovnica i dopuštanje osobnog izbora | Područja, stranice, ovlasti, osobne naslovnice i privatne teme područja |
| P6 Calendar | Čitanje calendar/calendar.php kroz HPH_CONFIG_PATH i override slojeve; upgrade premješta stari korijenski PHP, bez promjena GUI-ja ili formata | Svi kalendarski podaci, uključujući sistemske i osobne tipove, ostaju u bazi |
| P7 Menu | Globalna PHP/JSON konfiguracija pod HPH_CONFIG_PATH/menu | Vlasnički posebni meniji u direktorijima područja/stranica; data/menu/contexts.json je mali indeks i spremište zajedničkih konteksta; cache izdanja i rezultati poslova |
| P7 Theme | Globalne opcije i aktivna JSON spremišta; očuvanje prilagodbi | Datoteke tema, slike i drugi resursi tema; privatne teme vezane na područja |
| P8 Backup | Dovršeno: slojeviti backup/backup.php i backup/backup-providers.php; generičko čitanje bez GUI-ja | Poslovi, upload sesije i arhive |
| P8 API, Audit, ORM | Dovršeno: API adapter za api/api.php; Audit zajedničke app postavke; ORM zajednički korijenski database.php, bez duplog configa | Ključevi i pretplate korisnika, dostave, audit zapisi i migracije |
| P8 Auth | Provjerene opcionalna verifikacija providera i vanjska konfiguracija; bez nove migracije ili obvezne promjene GUI-ja | Korisnici, grupe, stabilni ID-evi i nužne veze |
| P9 Comment, Task, Notification, Accessibility | Dovršeno: nema zasebnih globalnih postavki; zajedničke putanje i odabir modula provjereni bez GUI-ja postavki; EN/HR upute i regresijski testovi | Komentari i postavka komentiranja dokumenta/nacrta, zadaci, obavijesti i osobne prilagodbe |

Za svaki redak zasebno: potvrditi stvarni opseg prije izmjena, očuvati postavke,
prilagoditi backup i updater gdje taj modul sudjeluje, dodati predložak samo gdje je dogovoren i upute,
provjeriti konfiguriranje generičkih modula bez Simbiozina GUI-ja, zatim uskladiti HFC.
Ne uvoditi GUI prekidač automatski u svaki modul.
Workspace zadržava aplikacijski GUI i mijenja samo spremište konfiguracije. Tablica je
opis opsega zadataka, ne drugi popis koji se označava završenim. P7 Menu/Theme
namjerno nema `.dist` primjere; postojeći GUI ostaje.

### Simbiozini moduli koji ostaju nepromijenjeni

Korisnik je 10. listopada suzio opseg: P5 Simbioza User, Workspace Search i
Confluence Import ostaju aplikacijski moduli s postojećim GUI-jem. Ne prepravljati
njihovo ispravno ponašanje radi samostalnog framework projekta ili opcionalne
administracije. Workspace također ostaje dio Simbioze; P4 mijenja konfiguracijsku
putanju i povezane backup/upgrade tokove, ne tu granicu. Raniji P5 povučen je iz
plana bez promjene oznaka ostalih zadataka.

### P10 — završna provjera

- Vanjski `HPH_CONFIG_PATH`, zadana putanja i podržani konfiguracijski slojevi;
  HTTP/CLI, zapisivi config i config samo za čitanje.
- Čista instalacija i nadogradnja; nijedna uklonjena settings tablica ne stvara
  se na svježoj instalaciji, a stara se čisti tek nakon provjerenog izvoza.
- Podržane baze i instalacijski načini: mod_php, FPM, izolirani/sigurni FPM,
  demo način i minimalne kombinacije modula, u postojećoj izoliranoj matrici.
- Ciljane provjere uz svaki zadatak; završno potpuni skup kvalitete i E2E
  prema aktualnim projektnim skriptama. `composer on-commit`,
  `php scripts/verify_clean_install_matrix.php` i `composer e2e` nisu zamjena
  jedan za drugi. Odvojiti što je prošlo od onoga što nije pokrenuto.
- Pri promjeni migracije primijeniti HFC migracije prije GUI provjere i potvrditi
  nula preostalih migracija, bez izrade backupa koji je korisnik izričito isključio.
- Prije objave izdati promijenjene generičke module, uključujući P1D Backup
  provider, pa uskladiti Simbiozine zahtjeve ovisnosti s tim izdanjima.
- Ukloniti vlastite privremene testne instalacije. Ne označiti izdanje spremnim
  prije provjera; objavu i dalje posebno dogovara korisnik.
- Provjeriti uvoz Simbioza arhiva između siteova s različitim domenama, početnim
  putanjama, ID-evima i slugovima. Upravljani privitci/uključeni sadržaj i ID-evi u
  Workspace konfiguraciji mapiraju se, ali potpuna prenosivost proizvoljnih
  apsolutnih HTML poveznica i URL-ova link čvorova još nije zajamčena. Razlikovati
  interne i namjerno vanjske URL-ove; ne prepisivati sve poveznice automatski.
  To je zasebna provjera prenosivosti, ne dorada Confluence importa.

### Dokazi završne provjere P10

Provjereno 11. listopada 2026. s neobjavljenim lokalnim modulima i označenom
ovisnošću Frameworka. Produkcijski metapodaci ovisnosti, apps-test i demo nisu
zamijenjeni. Promijenjene runtime datoteke i dokumentacija HFC modula usklađene
su s lokalnim izvorima bez zamjene instalacije ovisnosti. Postojeće razvojne izmjene su očuvane.
HFC ima 45 izvršenih migracija / 0 preostalih, HTTP 200 i provjeru sadržaja bez
zapisivanja: 1476 filesystem HTML zapisa i 10325 zapisa privitaka/povijesti,
bez nedostajućih datoteka i bez pogrešnog vlasništva.

| Provjera | Potvrđeni rezultat |
| --- | --- |
| Cijeli aplikacijski `composer on-commit` u izoliranoj QA kopiji | Prolazi: strogi autoload, PHPCS, Rector dry run, produkcijski/razvojni PHPStan, dokumentacijski/prijevodni auditi; 302 PHPUnit testa, 6202 tvrdnje, šest platformskih preskakanja |
| Pune provjere kvalitete promijenjenih modula | Prolaze: Auth 86/600, Email 12/70, Backup 54/191, Menu 65/368, Theme 36/758, API 44/210, Calendar 85/677, HTML Editor 211/1462, Workspace 134/989 (testovi/tvrdnje) |
| Preskočeni Linux/root tokovi u stvarnom Colima Linuxu kao root | Zasebno prolaze: šest testova, 103 tvrdnje, deployment worker, GUI updater vlasništvo, rollback modula, konfiguracijski i Workspace uvozi/selidbe |
| Minimalna lokalna matrica čistih SQLite instalacija | Prošla svih 21 kombinacija |
| Čista instalacija svih modula na PostgreSQL 18, MySQL 8.4 i MariaDB 11.4 | Prolazi na zasebnim privremenim bazama |
| Stvarni standardni i sigurni FPM | Prolaze: zapis u najviši sloj, ručno uređivanje, zabrana read-only zapisa, očuvan inode/vlasništvo, zasebni zapisivi podaci i zaštićen kod/donji slojevi |
| Izolirani Apache 2.4 mod_php | Prolaze zadane/vanjske/override putanje i read-only zabrana; demo prava 0700 i umask 0077 upotrebljiva su runtime vlasniku |
| Finalizacija zadanih FPM prava u Colima Linuxu | Prolazi sa sintetičkom root/www-data fixture: atomski Backup zapisi providera, zaštićen kod i PHP konfiguracija izdanja, bez ponovnog `data/config`; bez promjena računa/servisa |
| Cijeli browser/API/performance skup | SQLite 80/80 (18,4 min), PostgreSQL 80/80 (25,8 min), MySQL 80/80 (19,1 min); početni neuspješni/prekinuti prolazi nisu uračunati kao uspjeh |
| Regresija odgođenog odgovora popisa poslova nakon restorea | Zasebno prolazi na MariaDB-u: GUI čeka prije potvrde uspjeha i sljedeća prijava radi |
| Mapiranje podataka i konfiguracijskih identiteta backupa između siteova | Postojeće izolirane arhivne fixture pokrivaju različite ciljne ID-eve/slugove, oba HTML drivera, stari raspored i točan rollback |
| Proizvoljne HTML poveznice i URL-ovi link čvorova između siteova | Potvrđen otvoreni nedostatak: upravljani privitci se prilagođavaju, apsolutni izvorni linkovi ostaju isti, obične relativne Workspace poveznice ne dobivaju ciljni prefiks, a `target_url` link čvora kopira se nepromijenjen |

P10 je popravio lokalno razrješavanje testnih ovisnosti: posredni razvojni aliasi
dopušteni su samo u generiranom lokalnom manifestu, uz prednost stabilnim
ovisnostima. `scripts/build_ci_composer.php --local` koristi isti dopušteni skup
modula i prihvaća `HPH_LOCAL_MODULE_ROOT` za odvojenu QA kopiju.
`tests/src/Tools/LocalMatrixRepositoriesTest.php` provjerava tu granicu.
Produkcijski Composer metapodaci i uzvodni Framework nisu izmijenjeni.

Prvi paralelni E2E pokušaji dijelili su putanje Playwright rezultata, a PHP 8.5
opcode cache hosta srušio se u izvornom kodu PHP-a pri kompiliranju generiranih
testnih datoteka. Runner sada odvaja rezultate po driveru baze i isključuje
opcode cache samo privremenom PHP poslužitelju. Završni ponovljeni browser
prolazi uspješno su završili na sve tri baze. Produkcijski cache nije mijenjan.
Formatiranje za provjere kvalitete uskladilo je promijenjeni aplikacijski kod i
konfiguracijske testove s postojećim PHPCS/Rector pravilima; nije selilo aktivne
HFC postavke.

P10 je popravio i stvarnu utrku pri restoreu u Backup GUI-ju. Pozadinski popis
poslova sada ima samo jedan zahtjev u tijeku, pauzira se i dovršava prije restorea,
a završno osvježavanje završava prije objave uspjeha. Kasni odgovor tako ne mijenja
sesijski kolačić tijekom sljedeće prijave. Browser regresija namjerno odgađa taj
odgovor; pravila autentikacije i CSRF zaštite nisu oslabljena. Prvi regresijski test
trebao je čišćenje route handlera uz čekanje, da ne prekida vlastiti zahtjev.

Hladni višekoračni browser tokovi imaju izričite rokove cijelog postupka, bez
izmjene granica performansi pojedinačnog zahtjeva. Test pravila naslovnice vraća
izvorni JSON u `finally`; raniji MySQL istek roka ostavio je prilagođenu naslovnicu
i poništio valjanost kasnijeg mjerenja početne rute. Taj cijeli skup prošao je
na novoj bazi; raniji neuspješan prolaz nije proglašen uspješnim.

Završni FPM pregled uklonio je stvaranje starog `data/config`, davanje prava
starim modulnim PHP datotekama i mrtvu petlju zapisivih direktorija. Backup
konfiguracija na zadanoj putanji dobiva runtime prava potrebna za objavu zapisa
providera. `FpmConfigurationPathsTest` čuva aktualne putanje, a stvarna funkcija
finalizacije prava izvršena je na sintetičkom Linux korijenu kao root/www-data.
Vanjski slojevi ostaju pod upravljanjem administratora; njihova prava nisu mijenjana.

Početni ad-hoc mod_php test zadane putanje pogrešno je učitao HFC-ov bootstrap
umjesto vlastite izolirane kopije te prepisao samo postavke Theme i Menu modula.
Obje datoteke vraćene su bajt po bajt iz ranije QA fixture kopije. Ispravljeni
test koristi vlastiti bootstrap i odbija putanje izvan svojega privremenog
direktorija; sva četiri načina ponovno prolaze, a HFC postavke nakon njih su
nepromijenjene. Test nije mijenjao bazu ni sadržajne datoteke.

Završnim čišćenjem uklonjeni su izolirana QA kopija, dovršene/neuspjele
clean-install i E2E instalacije, sintetičke FPM/mod_php fixture i sva tri P10
kontejnera baza s privremenim volumenima. Postojeći servisi hosta, sama Colima,
razvojne baze, privatne HFC postavke i korisnički podaci nisu uklanjani.
Mali logovi/izvještaji ostaju u ignoriranom `build/p10/` i direktorijima testova
i izvještaja po driveru. Uklonjen je prazni stari HFC `data/config` direktorij;
u njemu nije bilo datoteka.

Ove sintetičke platformske provjere ne tvrde da je napravljen novi stvarni
prijava–callback–odjava prolaz za svaki vanjski SSO provider. Postojeće HFC
postavke providera očuvane su. Objava generičkih modula, usklađivanje Simbiozinih
ovisnosti i provjera udaljenih označenih izvora zaseban su dio objave izdanja.

### Sljedeći P10 korak — prenosivost autorskih poveznica

Ciljana provjera formattera sačuvana je u `build/p10/link-portability.json`.
S izvornom putanjom `/old` i odredišnom `/new` upravljani privitak postaje
`/new/editor-html/asset/test-uuid`, ali obična poveznica postaje
`/workspace/upute` bez ciljnog prefiksa. Apsolutna izvorna poveznica također
zadržava staru domenu/putanju. `WorkspaceScopedBackupProvider` izvozi i uvozi
`target_url` nepromijenjen. To je funkcionalno ograničenje, ne nepokrenut test.
U P10 nisu uvedeni globalna zamjena URL-ova ni novi arhivni URL ugovor.

Sljedeći mali zadatak je definirati pouzdane metapodatke domene/početne putanje
za nove arhive i izričitu izvornu putanju za stare arhive kada je potrebna;
prepoznati upravljane interne rute pomoću import mapiranja identiteta/slugova,
očuvati query parametre/fragmente i namjerno vanjske poveznice te provjeriti oba
HTML drivera, različite domene/putanje/slugove, stare arhive i rollback.
Zatim ponoviti zahvaćene Backup/Editor/Workspace testove. Ne ponavljati dovršene
konfiguracijske i podatkovne migracije samo radi nastavka rada.

## Nakon konfiguracijskih migracija: Docker i SimpleSAMLphp

Ovo je zabilježen budući zahtjev, ne dio trenutačne implementacije P2 niti
potvrda da je Docker instalacija već spremna. Klasična instalacija ostaje podržana.

- SimpleSAMLphp mora biti neobvezan: instalacije s lokalnom ili drugom prijavom
  ne trebaju dodatni SAML servis ni ovisnosti.
- Razmotriti zasebnu opcionalnu varijantu Simbiozina imagea sa SimpleSAMLphp kodom;
  postavke, metadata i certifikati moraju biti izvan nepromjenjivog imagea.
- Zasebni SimpleSAMLphp kontejner moguć je samo uz projektiranu integraciju:
  sadašnji Auth učitava lokalni `autoload_path` i koristi `SimpleSAML\Auth\Simple`.
  Sam HTTP URL drugog kontejnera ne zamjenjuje tu PHP biblioteku. Treba provjeriti
  zajedničku pohranu sesija, cookie pravila, callback i odjavu, a ne samo metadata URL.
- Postojeća instalacija izvan Dockera može se razmotriti uz sigurno read-only
  montiranje kompatibilnog koda/konfiguracije i odgovarajuću pohranu sesija, ili
  putem zasebno projektiranog protokolnog povezivanja. Ne obećavati rad samo
  zadavanjem njezina URL-a niti montirati cijeli host direktorij s tajnama.
- Auth postavke ostaju pod `HPH_CONFIG_PATH/auth/`; odvojeno planirati native
  SimpleSAMLphp konfiguraciju i certifikate, te sesije/cache/logove kao podatke.
  Potpuni prijava–callback–odjava test mora proći uz reverse proxy i sigurne ovlasti.

[Službena integracijska dokumentacija](https://simplesamlphp.org/docs/stable/simplesamlphp-sp.html)
opisuje lokalnu PHP biblioteku i sesije; konačni odabir načina instalacije i
ograničenja napraviti tek u Docker fazi.

## Zapis dovršenog

| Oznaka | Datum | Ishod i dokazi | Stanje objave |
| --- | --- | --- | --- |
| P10-V — lokalna provjera konfiguracije/instalacija/nadogradnji | 2026-10-11 | Aplikacijski skup 302/6202 i svih devet promijenjenih modulnih skupova kvalitete prolaze; šest preskočenih platformskih slučajeva prolazi na stvarnom Linuxu/rootu; SQLite matrica s 21 kombinacijom i instalacije svih modula na PostgreSQL/MySQL/MariaDB; stvarne FPM/sigurne FPM/mod_php provjere putanja i prava; cijeli SQLite/PostgreSQL/MySQL browser skupovi po 80/80. Popravljeni Backup utrka pozadinskih zahtjeva/sesije i stara zadana FPM konfiguracijska prava; dodani regresijski testovi i izolacija lokalnih QA/E2E provjera. HFC usklađen, 45/0 migracija, HTTP 200 i sadržaj bez nedostajućih datoteka/pogrešnog vlasništva. Testne instalacije uklonjene; dokazi iznad. | Podzadatak provjere lokalno dovršen; P10 ostaje otvoren za autorske URL-ove; bez objave |
| P7R-R — čitljivi direktoriji i čišćenje starih Editor datoteka | 2026-10-11 | Dodani Workspace `WorkspaceDataDirectory.php`, nepromjenjivi metapodatak `workspaces.storage_slug` i jedinične provjere. Prilagođeni odabir Editor putanja, korištenje već učitanih metapodataka verzije, vlasništvo Workspace menija/tema i remapiranje ciljnih ID-eva pri obnovi. Simbioza dodaje `WorkspaceReadableDataMigration.php`, `20261011120000_readable_workspace_data.php`, označavanje čiste instalacije te provjere stvarne updater granice izdanja, prekida, sukoba i obnove. Ažurirani jezično odvojeni vodiči pohrane i README parovi modula. HFC premjestio 1411 direktorija, uskladio 11795 referenci, uklonio 13 provjerenih duplikata i sačuvao 115 nepoznatih datoteka u oporavku; 45/0 migracija i čist audit sadržaja. Detalji niže. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| P9 — granice domenskih i osobnih podataka | 2026-10-11 | Dodan HFC `tests/src/Configuration/DomainStateConfigurationTest.php`; proširen Accessibilityjev `tests/StandaloneHostTest.php` za zadani/vanjski/read-only/override config i uključivanje/isključivanje. Ažurirani parovi README-ja četiriju modula te `docs/index_en.md`/`index_hr.md` ili Accessibilityjevi `integration_en.md`/`integration_hr.md`; usklađene te HFC vendor datoteke. Ažurirani Simbiozini README/config vodiči i ovaj plan. Nisu potrebni runtime politika, prazni config direktoriji, `.dist`, selidba podataka niti čišćenje sheme. HFC postavke očuvane, 44/0 migracija; provjere niže. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| P8 — Backup, API, Audit, ORM i Auth provjera | 2026-10-11 | Dorađeni Backup `BackupConfig.php`/`BackupProviderRegistry.php`; dodan API `ApiConfiguration.php` i prilagođeni njegovi potrošači. Dodani modulovi `backup.php.dist`/`api.php.dist`, testovi i jezično odvojene upute; dokumentirane zajedničke Audit postavke i ORM-ov zajednički korijenski database.php. Simbioza dodaje `BackupApiConfigurationMigration.php`, `20261011000000_move_backup_api_configuration.php`, ugniježđeni aktivni config i primjere; prilagođeni migracija prije bootstrapa, zaštita/oporavak updatera, restore aplikacijskog configa, čista instalacija i E2E pomoćne skripte. Dodani `BackupConfigTest.php`, `ApiConfigurationTest.php`, aplikacijske migracijske/restore/updater regresije i `backup_api_fpm_probe.php`. HFC usklađen, postavke očuvane, 44/0 migracija. Provjere i ograničenja su niže. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| P7R — struktura podataka područja/stranica | 2026-10-10 | Dodani Editorovi `EditorPayloadLayout.php`/`EditorPayloadRelocator.php`, Menuov `MenuContextFiles.php`/ugovor vlasništva, Workspaceov `WorkspacePayloadOwner.php`/povezivanje vlasništva menija te aplikacijski `WorkspaceDataMigration.php` s `20261010220000_organize_workspace_data.php`. Dorađeni promjena pohrane, povijest uvezenih privitaka, backup datoteka/vlasničkih menija, instalacija/nadogradnja ugrađenih uputa i čitanje HTML-a za Calendar backup. Dodani integracijski/FPM/audit fixture i jezično odvojene upute. HFC filesystem provjera: 1472 vlasnička HTML zapisa i 10323 zapisa privitaka, bez nedostajućih datoteka ili pogrešnih vlasničkih putanja; 43/0 migracija. Detaljne provjere su niže. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| P7 — Menu i Theme | 2026-10-10 | Dodani modulovi `MenuConfigurationFiles.php`/`ThemeConfigurationFiles.php` i prenosivi `MenuBackupProvider.php`/`ThemeBackupProvider.php`; prilagođeni GUI repozitoriji, opcionalni backup, postavke komponenti, testovi i jezično odvojene upute. Simbioza dodaje `MenuConfigurationMigration.php`, `ThemeConfigurationMigration.php`, migracije `20261010200000_move_menu_configuration` i `20261010210000_move_theme_configuration`, ugniježđeni host config, instalacijske/updater/FPM putanje i regresijske fixture. HFC usklađen, 42/0 migracija, postavke očuvane. Updater koristi granicu izvorne verzije 0.2.12. P7R dodan zasebno. Provjere i ograničenja su niže. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| P3/P4 — HTML Editor i Workspace | 2026-10-10 | Dodani `EditorHtmlConfigurationStore.php`, `EditorHtmlSettingsMiddleware.php`, `WorkspaceConfigurationStore.php`, `WorkspaceConfigurationIdentityTransform.php` i `WorkspaceDatabaseBackupProvider.php`. Dorađeni settings/storage/homepage servisi, backup registracija, sheme i testovi te odvojene konfiguracijske/backup upute. Simbioza ima `EditorHtmlConfigurationMigration.php`, `WorkspaceConfigurationMigration.php`, migracije `20261010160000_file_editor_html_configuration` i `20261010170000_file_workspace_configuration`, aktualne backup politike i FPM probeove. HFC usklađen, 39/0 migracija; postojeće postavke očuvane. Dokazi su niže. | Lokalno; bez commita, pusha ili izdanja |
| D2 — aktualni primjeri i uklanjanje zaostataka | 2026-10-10 | Uklonjen `HFClean/config/email.php.dist`; dodani ravni `config/auth.json.dist`, `email.json.dist`, `editor-html.json.dist` i `workspace.json.dist`, usklađeni s izvornim modulima. Dvojezični JSON `_documentation` objašnjava polja, slojeve, privatnost i granice GUI-ja. README i aktualne upute odvojeni su po jezicima; povijesne bilješke označene su kao povijesne. `ConfigurationExamplesTest.php` provjerava sva četiri primjera stvarnim spremištima. `scripts/update_bundled_assets.php` više ne čita stari Email PHP; `PhpConfigurationReader.php` izolira varijable izvršivog configa, uz regresijski test. Aktivni JSON nije zamijenjen primjerom. | Lokalno; bez commita, pusha ili izdanja |
| P2 — Email i stvarni FPM | 2026-10-10 | Dodani Email `EmailConfigurationStore.php`, `EmailSettingsMiddleware.php`, `config/email.json.dist`, dvije konfiguracijske upute i izolirani SMTP test; dorađeni config/settings/worker/manifest/GUI/testovi. Backup provider podržava validirani JSON. Simbioza ima `EmailConfigurationMigration.php`, upgrade migraciju, zaštitu runtime putanja u updateru, fresh-install preskok i FPM prava za privatne poddirektorije. Dodani stvarni FPM/Email fixture probeovi; HFC vendor Email/Backup usklađen, SMTP očuvan u `config/email/email.json`, migracije 37/0. Provjere su niže. | Lokalno; bez commita, pusha ili izdanja |
| P1D — updater i backup aplikacijske konfiguracije | 2026-10-10 | Dorađeni `update.php`, `ModuleStateStore.php`, `config/backup.php`, `config/services.php`, obje Setup skripte i `phpstan-dev.neon`. Dodani `src/Configuration/ApplicationBackupConfiguration.php`, `tests/src/Configuration/ApplicationBackupConfigurationTest.php` i `tests/src/Setup/FpmConfigurationPathsTest.php`; prošireni updater/module-state testovi. Dorađen `heartphrame-module-backup/src/Service/StructuredConfigBackupProvider.php` i usklađena njegova HFC vendor kopija. README/config/instalacijske/provider upute i ovaj plan opisuju aktualno ponašanje u odvojenim jezicima. Provjere i ograničenja su niže. | Lokalno; bez commita, pusha ili izdanja |
| D0 — Auth temelj | 2026-10-09 | Lokalni Auth koristi `HPH_CONFIG_PATH/auth/auth.json`; profili su pod providerom, predložak je `heartphrame-module-auth/auth.json.dist`. HFC je usklađen; korisnik je potvrdio ispravan rad. Privatne vrijednosti nisu dio ovog zapisa. | Lokalni kod; ne tvrdi objavu ni završnu matricu svih instalacija |
| D1 — pregled modula i plan | 2026-10-09 | Pregledano 18 izvornih modula i Simbiozine pomoćne putanje. Potvrđeni kandidati iz baze: globalna pravila osobnih područja i globalne naslovnice. Izrađene dvije jezične verzije ovog plana. | Dokumentacija, bez novih promjena ponašanja |
| P1A — zajedničke konfiguracijske putanje | 2026-10-09 | Novi `src/Configuration/ConfigurationPaths.php`; integracija u `config/services.php` i `public/index.php`; dopuna `framework.php.dist`, README i obje konfiguracijske upute. Provjere i ograničenja navedeni su niže. | Lokalno; bez commita, pusha ili izdanja |
| P1B — instalacija i odabir modula | 2026-10-09 | Dodani `PhpConfigurationReader.php`, `scripts/migrate_module_configuration.php` i `config/modules.php.dist`; dorađeni instalacija, početno učitavanje, `ModuleStateStore.php` te nužne uske putanje dijagnostike/backupa/updatera/FPM-a. Stari config odabira modula HFC-a premješten je bez promjene sadržaja. Dokazi su niže. | Lokalno; bez commita, pusha ili izdanja |
| V1 — zatečeni problemi provjera | 2026-10-09 | Potvrđena aktualna shema nove Auth instalacije i upgrade-only evidencija bez pokretanja starog čišćenja na novoj instalaciji; usklađeni brojevi migracija, coverage oznake i PHPDoc `$excludedNames`. Puni PHPUnit i obje PHPStan konfiguracije prolaze. | Lokalna provjera; završna matrica izdanja ostaje otvorena |
| P1C — jezici, upravljanje modulima i dijagnostika | 2026-10-10 | Dorađeni `PhpConfigurationReader.php`, `config/app.php`, oba upravitelja jezika, `ModuleLifecycleManager.php`, `SetupDiagnostics.php`, `SetupController.php`, Setup prikaz/gateway/spremište zahtjeva/worker, popis runtime datoteka zadanog FPM-a i `.gitignore`. Dodan `SetupWorkerConfigurationTest.php`, prošireni config/jezični/lifecycle/dijagnostički testovi i izolirane updater testne instalacije za Colimu. Ažurirani README, config/jezične upute i ovaj plan u oba jezika. Privatne postavke HFC-a očuvane; provjere su niže. | Lokalno; bez commita, pusha ili izdanja |

| P6 — Calendarove konfiguracijske putanje | 2026-10-10 | Dorađeni modulov `CalendarConfig.php` i izolirani testovi; dodani `calendar.php.dist` i odvojene konfiguracijske upute. Dodani aplikacijski `CalendarConfigurationMigration.php` i `20261010180000_move_calendar_configuration.php`; prilagođeni preskakanje u čistoj instalaciji, zaštita updatera i testovi. HFC koristi `config/calendar/calendar.php`, uz 40 izvršenih / 0 preostalih migracija. Calendar 85/677, HFC 247/5573 s četiri poznata platformska preskakanja, Linux config/migracijske provjere, statičke provjere i puni browser/API E2E 80/80 prolaze. Testne instalacije premještene u Smeće. | Lokalno dovršeno; bez commita, pusha ili izdanja |
| D3 — raspored dvojezičnih komentara | 2026-10-10 | Usklađeni komentari i opisi primjera u P1–P6 datotekama te odgovarajućim HFC kopijama modula: cijeli EN blok prije cijelog HR bloka. JSON primjeri imaju engleske opise na prvom mjestu. Dodane provjere redoslijeda jezika u primjerima; provjereni sintaksa, očuvanje izvršivih PHP tokena/JSON vrijednosti i 2987 dvojezičnih blokova. README i upute ostaju jezično odvojeni. | Lokalno dovršeno; bez promjena runtime postavki |

### P9 provjere

- Pregledani su servisi četiriju modula, paketni opisi konfiguracije, početne
  sheme, migracijske naredbe, opcionalne integracije i backup registracije.
  Nema zasebnih globalnih instalacijskih opcija za izvoz. Paketni opisi
  servisa/API-ja/providera ostaju kod. ORM-ov zajednički `database.php` i
  središnji odabir modula koriste deklarirane config slojeve; postojeći GUI
  ostaje. Nije mijenjan framework niti runtime kod modula.
- Novi `DomainStateConfigurationTest.php`: **3 testa / 133 provjere**.
  Zadani/vanjski/slojeviti korijeni koriste read-only datoteke/direktorije.
  Nevaljani driver izuzetog zadanog/slabijeg configa dokazuje korištenje
  efektivnog ORM sloja. Komentari čuvaju odvojenost dokumenta/jezika i nacrta/objave;
  Task opseg dokumenta i idempotentni audit; Notification osobni odabir,
  vlasništvo pročitanosti i inbox bez Emaila. GUI postavki se ne stvara;
  nakon poslovnih upisa provjeravaju se config bajtovi, popis datoteka i izostanak
  novih lockova/direktorija. To su izolirani SQLite fixture, ne izmjene razvojnih podataka.
- Accessibilityjeva stvarna samostalna aplikacija koristi neizmijenjeni resolver:
  zadani/vanjski config, praznu override listu modula i ponovno uključivanje
  nad isključenom bazom. Rute i renderer prate središnje stanje bez
  Simbioze/Autha/ORM-a ili GUI-ja postavki. Puni modul **19 / 1381** i njegov
  `composer on-commit` prolaze; ciljane PHPCS i test PHPStan provjere prolaze.
  Time je provjereno uključivanje/isključivanje pri bootstrapu, ne cijeli životni ciklus instalacije/uklanjanja.
- Comment **7 / 35**, Task **11 / 73** i Notification **8 / 54** prolaze,
  kao i njihove produkcijske/razvojne statičke analize. Puni HFC PHPUnit:
  **296 testova / 6088 provjera**, bez grešaka/upozorenja/rizičnih testova,
  uz istih šest root/Linux macOS preskakanja kao u P8. Ti testovi vlasništva
  nisu ponovno izvođeni u Colimi za ovaj korak dokumentacije/testova; P8 dokazi
  su niže. HFC produkcijski/razvojni PHPStan i PHPCS izmijenjenog testa prolaze.
  PHPDoc/dokumentacijski/jezični auditi prijavljuju nula problema.
- Browser/API E2E lokalnih izvora na SQLiteu: **12/12**. Obuhvaćeni su kreiranje,
  reakcije/prijava/moderiranje komentara i Notification GUI; osobni e-mail odabir;
  Task stanje/povijest i uređivanje tablice; Accessibility kontrole/prilagodbe;
  izrada/upload/preflight/restore cijelog site arhiva i kopiranje Workspacea.
  Runner nakon uspjeha uklanja privremenu E2E instalaciju.
- HFC ima **44 izvršene / 0 preostalih migracija**, a lokalni HTTPS vraća
  HTTP 200 bez preusmjeravanja. Konfiguracijsko stablo ima **44 neizmijenjene
  datoteke**; nijedna aktivna postavka ili domenski podatak nije premješten.
  Runtime kod četiriju HFC modula već je odgovarao izvornim repozitorijima;
  usklađene su samo ažurirane upute i Accessibilityjev test. Nije rađen HFC
  backup, apps-test/demo nadogradnja, Git objava niti izdanje.
- P9 je uklonjen s otvorene liste. P10 zadržava punu matricu baza/servisa,
  instalacija i nadogradnji, objedinjenu provjeru kvalitete repozitorija,
  puni E2E te preostale provjere prenosivosti/životnog ciklusa prije izdanja.

### P8 provjere

- HFC koristi `config/backup/backup.php`, `backup/backup-providers.php` i
  `api/api.php`; stare aktivne korijenske datoteke uklonjene su. Postojeće
  vrijednosti uspoređene su tijekom premještanja. API cilj u backupu aplikacijskih
  postavki usklađen je s aktualnom putanjom. HFC ima **44 izvršene / 0 preostalih**
  migracija, a stvarna lokalna HTTPS naslovnica vraća HTTP 200.
- Puni aplikacijski PHPUnit: **293 testa / 5955 provjera**, bez grešaka.
  Šest macOS preskakanja traži root/Linux vlasništvo ili identitete procesa.
  Svih šest zajedno prolazi u izoliranoj Colimi: **6 testova / 103 provjere**, bez preskakanja.
  Širi Linux updater/migracijski skup prošao je **36 / 632**, uz jedno nepovezano
  preskakanje bridge testa jer sintetička kopija nema stari Git tag.
- Backup **54 / 191**, API **44 / 210**, Audit **18 / 99**, ORM **95 / 864**
  i ciljani Auth **19 / 149** prolaze. Novi API adapter ima 100% pokrivenost
  linija/metoda; to nije tvrdnja o 100% pokrivenosti cijelog modula.
  Produkcijska/razvojna statička analiza HFC-a te Backup/API analiza prolaze.
  Ciljane provjere formatiranja prolaze; zatečeni širi nalazi ostaju uvjet P10.
- Provjereni su vanjski korijen, prioritet slojeva, zamjena popisa praznim popisom,
  nevaljana politika, nedostajući/nepretraživ sloj, najviši host descriptor,
  sukobi, ponavljanje selidbe i očuvanje relativnog PHP ponašanja. Root selidba
  čuva vlasnika/grupu/prava datoteke i novog direktorija. GUI/CLI updater fixture
  provjeravaju očuvanje privatnih postavki i točan oporavak, uključujući izdanje
  s konfliktnim zadanim configom i paralelne ručne izmjene.
  Stari API zapisi iz arhiva vraćaju se u aktualni najviši sloj ciljnog sitea,
  čuvaju lokalne dodatne opcije i vraćaju točne prethodne bajtove/prava pri grešci.
- Browser/API E2E s lokalnim izvorima na izoliranom SQLiteu prolazi **41/41**,
  uključujući kreiranje/upload/preflight/restore cijelog arhiva, Workspace-copy
  import, API ključeve, CORS, Webhooks i granice pristupa auditu. Stvarni standardni
  i sigurni FPM prolaze svih deset P8 provjera svaki, uključujući zajednički ORM
  config, logove u data, read-only sigurni config i vanjsku registraciju providera.
- Audit dokumentacije i dvojezičnog PHPDoca prijavljuju **0 problema**. Dopunjeno
  je pet komentara anonimnih metoda prethodne Workspace migracije, cijeli EN pa
  HR blok, bez promjene ponašanja. Upute ostaju jezično odvojene.
- Privremene standardne/sigurne FPM instalacije i njihovi zasebni procesi
  uklonjeni su; uobičajeni Colima servisi ostali su pokrenuti. Nije rađen HFC
  backup, izmjena frameworka, apps-test/demo nadogradnja, Git objava ili izdanje.
  Potpuna matrica baza/instalacija, puni E2E i kombinirana on-commit provjera
  ostaju P10; ove ciljane provjere nisu tvrdnja o spremnosti izdanja.

### Provjere dorade čitljivog P7R rasporeda

- HFC sada ima **45 izvršenih / 0 preostalih migracija**. Stvarna selidba
  preimenovala je **1411 direktorija** i uskladila **11795 referenci sadržaja**.
  Puni audit provjerava 1476 filesystem HTML zapisa (1472 vlasnička) i 10325
  zapisa privitaka/povijesti (10323 vlasnička): **0 nedostajućih datoteka /
  0 pogrešnih vlasničkih putanja**. Ponovljeni preflight nema novih selidbi,
  izmjena putanja, duplikata niti datoteka za oporavak. Javne HFC stranice
  područja ispravno se prikazuju nakon selidbe.
- Čišćenje je uklonilo samo **13 provjerenih HTML duplikata**, nakon usporedbe
  fragmenta istog dokumenta/jezika/verzije. **115 nepoznatih nereferenciranih
  datoteka** premješteno je, ne odbačeno, u `data/recovered/editor-html/`.
  Referencirani samostalni dokumenti, zajednički CSS i runtime/cache podaci
  ostaju u Editorovim korijenima.
- Stvarna aplikacijska migracija provjerena je s izvornim izdanjima 0.2.11,
  0.2.12 i 0.2.13 dok ciljna VERSION datoteka sadrži 0.2.99. Premješta dopuštene
  stare podatke, preskače stare selidbe za novija izdanja i može se ponoviti.
  Provjere čiste instalacije označavaju upgrade bez izvršavanja starog koda.
  Provjere trajnog dnevnika obuhvaćaju prekide prije i nakon DB commita;
  sukobi ciljeva čuvaju stari sadržaj.
- Ciljane aplikacijske migracijske/instalacijske provjere prolaze **38 testova /
  3947 provjera**, uz jedan macOS root-only skip. Završni raspored u Colimi
  prolazi **15 / 163**, bez skipova. Puni Workspace skup s coverageom prolazi
  **134 / 988**, bez rizičnih testova; pomoćni sloj direktorija ima potpunu
  pokrivenost redaka/metoda. Ciljane Editor pohrana/backup provjere: **36 / 319**.
  Puni aplikacijski PHPUnit prolazi **300 / 6138**, uz šest poznatih platformskih
  macOS skipova; root-only raspored prolazi zasebno u Colimi, kako je navedeno iznad.
  Statička analiza promijenjenog runtime koda i formatiranje nove migracije prolaze;
  auditi dokumentacije, dvojezičnih komentara metoda i prijevoda imaju nula problema.
- Preglednik/API prolazi **6/6**: puni backup/restore sitea, novi Confluence
  uvoz i sva četiri postojeća scenarija brzine. Kopiranje područja prolazi
  zasebno **1/1**. Početni test čitljivog rasporeda prekoračio je nepromijenjeni
  SQL prag stvaranja stranice (73 > 70); korištenje već učitanih metapodataka
  verzije uklonilo je suvišne upite bez cacheiranja vlasništva ili povećanja praga.
  Integracijski testovi pokrivaju obnovu na drugi site/ID, filesystem/bazu,
  ciljne privatne teme, oba prijelaza pohrane i stare arhive.
- Stvarni standardni i sigurni FPM u Colimi prolaze čitanje/pisanje vlasničkog
  HTML-a, privitaka, menija i privatnih tema s konačnim modulskim izvorima.
  Root prava migracije provjerena su zasebno. Namjenski FPM/nginx testni procesi
  i instalacija `/srv/simbioza-config-test` zaustavljeni su i uklonjeni;
  redovne usluge nisu mijenjane. Preglednički runneri uklanjaju uspješne privremene siteove.
  HFC backup nije rađen, prema uputi; Framework, apps-test i demo nisu mijenjani.
  Puna matrica izdanja ostaje P10; ovo ne objavljuje release.

### P7R provjere prije čitljivih oznaka

- HFC: **43 izvršene / 0 preostalih** migracija. HTML driver ostaje `filesystem`;
  svih 1472 jednoznačno vlasničkih HTML zapisa i 10323 zapisa privitaka/povijesti
  razrješava se u direktorijima područja/stranica. Potpuna provjera obuhvatila je
  i zapise bez vlasnika: **0 nedostajućih / 0 pogrešnih vlasničkih putanja**.
  Postojeći sadržaj, teme i posebni meniji i dalje se mogu čitati.
- Migracija je prvo otkrila nakupljanje uvlačenja pri pretvorbi samostalnog HTML-a.
  Formatter sada čuva doslovni `<pre>` sadržaj i uklanja samo zajedničko vodoravno
  uvlačenje. Stvarni HFC fragmenti prošli su provjeru pretvorbe prije uspješne
  migracije; nakon prekinutog pokušaja nije nedostajao nijedan referencirani izvor.
- Editor **211/1462**, Workspace **131/977**, Menu **65/368** i Calendar
  **85/677** prolaze. Confluence Import također prolazi: **115/711**.
  HFC: **281/5787**, pet platformskih preskakanja.
  P7R integracija: macOS **11 testova / 94 provjera**, jedno
  preskakanje samo za Linux/root; Colima Linux/root **11/113**, bez preskakanja.
  Provjere obuhvaćaju oba HTML drivera, promjene pohrane u oba smjera, sukobe
  ciljeva, koš/vraćanje, stare arhive, različite ID-eve područja/stranica i točan rollback.
  Izričito vlasništvo menija odabire i kontekste čije prilagođene rute ne sadrže
  slug područja, bez uključivanja vlasničkih menija drugog područja.
  Priprema zamjene može odabrati vlasništvo stranice i nakon uklanjanja njezina starog čvora.
  Trajno brisanje stranice/područja uklanja točne vlasničke datoteke menija i
  reference indeksa bez Menu GUI servisa, čuva zajedničke kontekste i dopušta
  daljnje upise nakon brisanja vlasnika. `OptionalMenuRepository.php` podržava
  samostalni regresijski test čišćenja; integracijska fixture provjerava stvarne datoteke.
- Produkcijska/razvojna statička analiza prolazi za promijenjene module i HFC.
  Novi komentari metoda imaju cijeli EN pa HR blok; upute su jezično odvojene.
- Stvarni standardni i sigurni FPM prolaze čitanje/zapis vlasničkog HTML-a,
  privitaka, tema i menija, uključujući vlasništvo runtime datoteka. Prava strukture
  stvorene kao root ostaju upotrebljiva za `www-data`. Ponovljive provjere su u
  `workspace_data_fpm_probe.php` i postojećem izoliranom FPM runneru.
- Ciljani testovi preglednikom prolaze backup/vraćanje cijelog sitea, uvoz kopije
  područja i novi Confluence uvoz. Stvarno javno područje HFC-a i sva tri prikazana
  preuzimanja slika vraćaju HTTP 200 nakon migracije.
- Prvi puni test s lokalnim izvorima završio je **79/80**: kreiranje stranice
  prešlo je nepromijenjeni SQL limit (**78 > 70**). `EditorPayloadLayout` sada
  ponovno koristi metapodatke dokumenta iz cachea zahtjeva, bez spremanja vlasništva;
  prvi sadržaj ne traži spremljenog vlasnika koji još ne može postojati.
  `EditorPayloadRelocator` provjerava samo referencije mogućih izvora ograničenim
  prenosivim upitima umjesto čitanja svih tablica sadržaja. Regresijski test čuva
  datoteke koje još referencira drugi dokument. Ciljani test performansi prolazi
  **4/4**: kreiranje **69**, objava **53**, javni prikaz **36** SQL upita.
  Završno puno ponavljanje lokalnih izvora prolazi **80/80** (11,4 minute).
  Nakon posljednje promjene čišćenja menija ciljani testovi preglednikom također
  prolaze **2/2**: odvojenost objave/skice/verzija i nastavak trajnog brisanja
  područja. Integracijski i samostalni testovi čišćenja koriste završno stanje izvora.
- Prvi poziv runnera izostavio je `--local` i spojio razvojnu aplikaciju s
  objavljenim paketima modula: **48/80**, uz pogreške sesija/GUI-ja. To nije
  provjera aktualnih lokalnih modula niti spremnosti izdanja. Paket s lokalnim
  izvorima ponavlja se zasebno; matrica objavljenih paketa ostaje u P10.
- Prema izričitoj uputi nije rađen backup HFC-a. Framework, apps-test i demo nisu
  dirani. Izolirani FPM servisi i njihova instalacija `/srv/simbioza-config-test`
  zaustavljeni su/uklonjeni; redovni Colima servisi ostaju pokrenuti.
  Runneri preglednika automatski uklanjaju svoje privremene instalacije.
  Ranije zadržana fixture i nekorištena dvostruka kopija vendora premještene su
  u Koš radi oporavka. Objava i završna matrica izdanja ostaju u P10.

### P7 provjere prije P7R

- Globalne Menu datoteke su `menu/menu.php`, `menu/top.json`, `menu/settings.json`
  pod `HPH_CONFIG_PATH`; Theme koristi `theme/theme.php`, `theme/themes.json`,
  `theme/settings.json`. PHP slojevi spajaju ključeve; svaki globalni JSON uzima
  se cijeli iz najvišeg postojećeg sloja. Zapis ide samo u zadnji zadani sloj.
  Paketne zadane postavke samo se čitaju; nema `.dist` primjera. Nevaljan ili
  read-only config izričito odbija zapis umjesto tihog spremanja u niži sloj.
  Zahvaćeni komentari imaju cijeli EN pa HR blok.
- Posebni meniji preneseni su u `data/menu/contexts.json` kao sadržaj, ne globalni
  config. Zajednički asseti ostaju `data/themes`; privatne teme područja, način
  pohrane HTML-a i privitci ne reorganiziraju se u P7.
- Upgrade pomoćnici provjeravaju ciljeve/prava i kopirane vrijednosti prije
  uklanjanja starih datoteka instalacije. Jednaki ciljevi čuvaju bajtove/inode;
  sukobi čuvaju izvore; prilagođeni vanjski JSON kopira se, ne briše. Čista
  instalacija stvara aktualne putanje i evidentira upgrade bez učitavanja koda.
  GUI/CLI updater čuva izvorno izdanje, preskače stari prijenos nakon 0.2.12 i
  odbija nepoznatu verziju. Samostalni prijenos poštuje istu granicu.
- Menu/Theme čuvaju identitete sheme 1 i prihvaćaju stvarne arhive starog
  datotečnog providera. Aktualni/stari ZIP vraća se u config/data putanje drugog
  sitea. Testirani su rollback, sukobi pri kopiranju, nesigurna imena, read-only
  preflight nedostajuće biblioteke i pristup ugniježđenim assetima. PHP backup
  izvozi dopuštene vrijednosti, ne sirovi kod ili putanje izvora. Priprema Theme
  biblioteke čuva lokalnog vlasnika/grupu; root CLI povrat `www-data`-ovih
  ugniježđenih asseta prošao je u Colimi.
- Testovi: Menu **61/356**, Theme **36/758**, puni HFC **270/5693** uz četiri
  poznata platformska preskakanja na macOS-u; ciljani P7 **17/99**. Naknadna
  updater regresija **56/683**, tri platformska preskakanja. Produkcijski/razvojni
  PHPStan prolaze za oba modula i HFC. Modulov PHPCS i PHPCS novih P7 aplikacijskih
  datoteka prolaze. Šira aplikacijska provjera nema grešaka, ali prijavljuje starija
  upozorenja duljine redaka u updater/setup/audit datotekama.
- Stvarni standardni i sigurni FPM prošli su svih šest HTTP provjera u Colimi:
  zapis u najviši sloj, ručni PHP/JSON, read-only odbijanje, čuvanje inodea drugog
  vlasnika te nepromijenjen nezapisiv sigurni kod/niži config. Sistemski poolovi
  nisu promijenjeni. `run_menu_theme_fpm.sh`, `menu_theme_fpm_probe.php`,
  `verify_menu_theme_fpm.sh` i `menu_theme_backup_permissions_probe.php` čuvaju
  ponovljive sintetičke provjere.
- Puni SQLite browser/API/performance paket završio je **79/80**: P7 GUI i
  site/workspace backup/import prolaze; jedan Calendar scenarij prekoračio je
  60 sekundi. Isti scenarij prošao je zasebno za **57,6 sekundi**, bez promjene
  Calendar koda. Prvi puni prolaz ne prikazivati kao 80/80; završna matrica je P10.
  P7 FPM servisi/fixture uklonjeni su; zadržana E2E fixture premještena je u Smeće.
- HFC ima **42 izvršene / 0 preostalih** migracija. Prema uputi korisnika nije
  rađen backup HFC-a; postavke su očuvane. Framework, apps-test i demo nisu dirani.
  Nema objave izdanja niti P7R migracije sadržaja/sheme.

### P6 lokalno dovršen — samo Calendarove putanje

- Opseg je potvrđen 10. listopada: aktivni `calendar/calendar.php` unutar svakog
  `HPH_CONFIG_PATH` sloja. GUI, rute, shema baze i sistemski/osobni tipovi događaja
  ostaju isti. Novi aplikacijski upgrade samo premješta datoteke; čista instalacija
  evidentira ga bez učitavanja stare pretvorbe.
  Paketni `config/services.php` i zadani `config/calendar.php` ostaju uz kod;
  to nisu aktivne instalacijske konfiguracije koje treba premještati.
- `CalendarConfig.php` koristi neizmijenjeni `ConfigPathResolver` i
  `ConfigArrayMerger`. Kasniji slojevi imaju prednost; nevaljan/nečitljiv ili
  nepretraživ sloj daje grešku bez fallbacka. Čitanje ne zapisuje niti stvara lock.
- Calendar: **85 testova / 677 provjera**; produkcijski i razvojni PHPStan,
  ciljani PHPCS i Rector prolaze. Izolirani config testovi na Linuxu/PHP 8.3
  u Colimi: **18 / 58**, uključujući zabranu čitanja i pretraživanja direktorija.
  Upgrade-only migracija datoteka: **7 / 25** na macOS-u i u Colimi, uključujući
  provjeru svih konflikata prije zapisa, očuvanje bajtova/prava i idempotentnost.
- HFC PHPUnit s provjerama jezičnog redoslijeda: **247 / 5573**, bez grešaka;
  četiri ranije dokumentirana platformska preskakanja na macOS-u. Produkcijski
  i razvojni PHPStan te ciljani PHPCS prolaze. Pet provjera primjera s engleskim
  opisima na prvom mjestu: **5 / 42**.
  HFC vendor kopija je usklađena; aktivne upute premještene su u
  `config/calendar/calendar.php`, uz očuvane PHP vrijednosti. Stara korijenska
  datoteka više ne postoji. Stanje migracija je **40 / 0**.
- Dodani su paketni i HFC `calendar.php.dist`, odvojene modulove konfiguracijske
  upute i ispravljen zastarjeli primjer opcija izbornika. README, aplikacijske
  upute i plan opisuju stvarni opseg bez univerzalnog zahtjeva za isključivanjem GUI-ja.
- Prethodni puni browser test prošao je 79/80 scenarija; samo prijava nakon
  povrata cijelog backupa nije prošla zbog nevaljanog CSRF-a. Ciljano ponavljanje
  oba backup scenarija prošlo je bez promjene koda. Završni puni
  `php scripts/run_e2e.php --local --keep` prošao je **80/80** u 10,8 minuta,
  uključujući povrat cijelog sitea, Calendar GUI/API/ICS/CalDAV i SQL ograničenja.
  Sve tri vlastite izolirane instalacije premještene su u macOS Smeće nakon
  zaustavljanja njihovih poslužitelja; moguće ih je vratiti. HFC nije uklonjen.
  P6 je lokalno zatvoren. Commit, push, release, apps-test i demo nisu mijenjani.

### D3 — engleski blok prije hrvatskog u komentarima i primjerima

- Korisnik je pojasnio da dvojezično znači cijeli engleski blok pa cijeli hrvatski
  blok, ne izmjenjivanje prijevoda. Pravilo sada vrijedi za PHP komentare,
  dokumentaciju metoda i `.dist` opise u datotekama mijenjanima kroz P1–P6,
  uključujući odgovarajuće kopije modula u HFC-u.
- JSON opisi primjera Autha, Emaila, HTML Editora i Workspacea imaju `en` prije
  `hr`; Calendarovi i aplikacijski PHP primjeri imaju odvojene blokove. README
  i upute ostaju u odvojenim datotekama po jezicima.
- Mehanička provjera potvrdila je očuvanje izvršivih PHP tokena i JSON vrijednosti.
  Pregled **2987 dvojezičnih blokova komentara** nije našao obrnuti redoslijed
  ni miješanje u istom retku. Sintaksa **250 izmijenjenih PHP datoteka** prolazi;
  testovi primjera sada provjeravaju jezični redoslijed radi sprječavanja regresija.
- Ciljani config testovi Autha, Emaila, Editora, Workspacea i Backupa prolaze
  (**6/92**, **6/23**, **9/61**, **11/41**, **6/43** redom). Produkcijski PHPStan
  prolazi za svih šest zahvaćenih modula, a obje HFC konfiguracije prolaze.
  Framework i privatne runtime postavke nisu preformatirani.

### Provjere i ograničenja P3/P4 i D2

- HTML Editor: **206 testova / 1384 provjere** s uključenom provjerom coverage
  metapodataka; Workspace: **130 / 973**; Backup: **49 / 180**. Email nakon
  provjere primjera: **12 / 70**; ciljani Auth file-config testovi: **6 / 92**.
- Aplikacijski PHPUnit: **239 testova / 5530 provjera**, bez grešaka; četiri
  ranije dokumentirana root/Linux/POSIX testa preskaču se na macOS-u.
  Produkcijski i razvojni PHPStan prolaze. Novi i dorađeni P3/P4/D2 PHP prolazi
  ciljano formatiranje; Authov raniji test ima deset postojećih upozorenja
  duljine retka, bez novih grešaka. `git diff --check` prolazi u svim zahvaćenim
  repozitorijima.
- Puni izolirani SQLite preglednički/API/performance skup: **80/80 prolazi**,
  uključujući GUI postavke, uređivanje i backup/restore. Kasniji regresijski test
  učitavača configa i instalacijski updater korak dodatno su obuhvaćeni punim
  aplikacijskim PHPUnitom; preglednički skup nije ponavljan zbog te uske CLI dorade.
- Stvarni standardni i sigurni FPM probeovi potvrđuju samostalni slug Editora,
  runtime nadjačavanje Workspacea bez trajnog zapisa, opcionalni Editor GUI,
  JSON spremanje naslovnica i očuvanje dodatnih ključeva. Read-only završni sloj
  dopušta čitanje i zasebno odbija Editor/Workspace zapis; kontrolni sažeci svih
  nižih/gornjih JSON datoteka ostali su isti nakon odbijenih zapisa.
- Stare Simbioza konfiguracijske politike i Workspace naslovnice uvoze se u
  sadašnji JSON. Odabrani uvezeni ID-evi remapiraju se, nepreuzeti lokalni ključevi
  ostaju lokalni; prepare/complete/abort dijele lock i vraćaju izvorno stanje pri
  neuspjehu. Novi backup izvozi samo aktualne konfiguracijske providere.
- Sva četiri JSON predloška jednaka su svojim paketnim primjerima, imaju HR/EN
  opise i prolaze stvarne modulske učitavače. Još valjani PHP predlošci frameworka,
  baze, enkripcije i odabira modula nisu uklonjeni. Stari PHP nazivi ostaju samo
  gdje označavaju paketne zadane vrijednosti ili ulaz migracije/arhive.
- Uklonjene su vlastite FPM instalacije, servis, helper, sudoers/nginx zapis i
  testni računi/grupe u Colimi. Obični nginx/FPM servisi ostali su aktivni.
  Dvije lokalne izolirane E2E instalacije premještene su u korisnikov koš.
- Na kraju P3/P4 HFC je imao **39/0** migracija; aktualni broj nakon P6 je iznad.
  `apps-test`, demo i framework nisu mijenjani. Završna matrica svih načina
  instalacije, koordinirana izdanja modula i proizvoljni site-bound URL-ovi
  ostaju P10; ništa nije objavljeno.

### Provjere i ograničenja P2

- Email PHPUnit: **11 testova, 64 provjere** na macOS PHP 8.5 i Linux PHP 8.3
  u Colimi, kao obični korisnik i root, bez preskoka. Lokalni SMTP prihvatio je
  jednu sintetičku poruku workera s ručnim configom i isključenim GUI-jem;
  promjena configa učitana je prije sljedećeg rada. Nije slana stvarna pošta.
- Backup PHPUnit: **46 testova, 153 provjere**; JSON export/restore/abort čuva
  postojeće lokalne ključeve, tajnu i prava, uz lokalnu validaciju.
- Puna preglednička/API/performance provjera na izoliranoj SQLite instalaciji:
  **80/80 prolazi**, uključujući Email GUI i cijeli backup/restore. Privremeni
  HTTP proces i instalaciju uklonio je postojeći E2E wrapper.
- Puni aplikacijski PHPUnit: **219 testova, 5435 provjera**, bez grešaka.
  Četiri testa vlasništva/ovlasti preskaču se na macOS-u: tri traže Linux/root
  za GUI/CLI deployment i rollback, četvrti root/POSIX za vlasništvo uvezenih
  privitaka. Zasebno u Colimi sva četiri prolaze, ukupno **69 provjera**.
  Prijelaz starog updatera `0.2.11` također prolazi na macOS-u i nije preskok.
- Produkcijske i razvojne PHPStan provjere aplikacije/Emaila, Backup produkcijski
  PHPStan, ciljane PHPCS/Rector te auditi dvojezičnog PHPDoca, dokumentacije
  modula i prijevoda prolaze.
- Stvarni standardni FPM i zasebni sigurni FPM pokrenuti su kao systemd servisi
  u Coliminoj Linux VM, ne samo simulirani generiranjem konfiguracije. HTTP
  probeovi potvrđuju prednost gornjeg sloja, zapisive podatke, read-only kod i
  niži config u sigurnom FPM-u. Email čita/piše samo gornji JSON sloj, čuva tajnu
  i odbija zapis kada gornji sloj postane read-only. Ispravljen je početak
  sigurnog FPM-a kada opcionalni SimpleSAMLphp sistemski direktoriji ne postoje.
- `apps-test` pregledan je samo read-only: sigurni FPM aktivan, očekivani login
  redirect i SimpleSAMLphp metadata HTTP 200. Nisu mijenjani udaljeni config,
  procesi ili kod. To nije puni SSO prijava/odjava test.
- Uklonjene su vlastite standardna/sigurna testna instalacija u
  `/srv/simbioza-config-test`, pripadni FPM servis/helper/sudoers/nginx zapis,
  testni korisnici/grupe i lokalna generirana kopija pod `output/fpm-verification`.
  Testni portovi više ne slušaju. Nisu uklonjeni HFC podaci ni drugi servisi.
- HFC je razvojna instalacija s aktualnim Email/Backup kodom, HTTP 200 i
  **37 izvršenih migracija, 0 preostalih**. Stari `config/email.php` uklonjen je
  tek nakon provjerenog prijenosa. Framework i demo nisu mijenjani; nije izrađen
  HFC backup niti objavljeno izdanje. Puna višebazna/šest-načina matrica i
  objedinjeni `composer on-commit` ostaju P10, ne rezultat ovog koraka.

### Provjere i ograničenja P1D

- Puni PHPUnit na macOS-u: **215 testova, 5422 provjere**, bez grešaka. Četiri
  root/Linux testa s različitim identitetima preskočena na macOS-u uspješno su
  izvršena u Coliminom updater/FPM skupu: **76 testova, 774 provjere**, uz jedan
  preskok jer kontejner nema stari Git tag `0.2.11`. Taj test starog updater
  prijelaza prošao je na macOS-u. Nije pokrenut cijeli Linux skup testova.
- Linux provjere putanja/backupa/odabira modula/FPM-a pod običnim korisnikom:
  **51 test, 192 provjere**. Skup samog Backup modula: **45 testova, 146 provjera**.
  Prolaze produkcijski i razvojni aplikacijski PHPStan te PHPStan Backup modula.
  Provjere dvojezičnog PHPDoca, dokumentacije modula i prijevoda nemaju problema.
  Ciljani PHPCS nema grešaka, ali samostalne skripte i postojeći updater test
  zadržavaju stilska upozorenja; ne tvrdi se prolaz formatiranja cijelog repozitorija.
- Izolirani updater podprocesi provjeravaju CLI i GUI ulaze, vanjski i ugrađeni
  privatni config, symlink config korijena, povrat nakon preflight greške,
  zasebnog runtime vlasnika i istodobno uređivanje na mjestu. Tuđa izmjena ostaje
  netaknuta; održavanje ostaje uključeno, a privatna kopija izvornih bajtova ostaje
  za oporavak. Uspješni poslovi uklanjaju svoje privremene kopije i veze.
- Backup testovi koriste stvarni arhiv/provider i izmišljene postavke: slojeviti
  izvoz/vraćanje/prekid, uklanjanje iz dokumenta najvišeg sloja, očuvanje lokalnih
  ključeva, odbijanje zajedničkog locka, read-only ciljeve, odobrene direktorijske
  veze, stare opise, prava datoteka i nepromijenjen dinamički aplikacijski kod.
- FPM testovi provjeravaju generirane pool parametre i politiku sandbox putanja.
  Nije instaliran/restartan FPM servis ili systemd jedinica na računalu. Stvarno
  izvršavanje systemd sandboxa, puna matrica šest instalacijskih načina, potpuni
  browser E2E i objedinjena provjera kvalitete cijelog repozitorija ostaju P10
  provjere prije releasea.
- HFC vraća **HTTP 200** sa Simbioza sadržajem, registrira `application-config`
  i ima **36 izvršenih migracija, 0 pending**. P1D ne dodaje migraciju baze,
  nije ponovno pokrenuo Auth upgrade niti premjestio privatne postavke HFC-a.
- Nakon provjera uklonjene su vlastite privremene instalacije, HTTP proces i
  Colima kontejner. Nije bilo backupa HFC-a, promjena apps-testa/demo instalacije,
  mijenjanja frameworka, commita, pusha ili releasea.

### Provjere i ograničenja P1C

- Puni PHPUnit na macOS-u: **198 testova, 5217 provjera**, bez grešaka,
  upozorenja ili risky testova. Tri platformska preskoka zasebno su izvršena u
  Linux PHP 8.4 kontejneru Colime: **3 testa, 43 provjere, bez preskoka**. Pokrivaju
  runtime identitet pri root importu, zasebno runtime vlasništvo pri GUI
  samonadogradnji updatera i delegiranje administratorskog CLI-ja pozadinskom
  deployment workeru. Time se ne tvrdi da je pokrenut cijeli Linux skup testova.
- Ciljane P1C provjere prolaze na macOS-u i pod običnim Linux korisnikom:
  **32 testa, 191 provjera**. Health-check stvarnog Setup workera uključuje šest
  provjera zajedničkog override direktorija zahtjeva i prolazi i kao root.
  Testne migracije i updater podprocesi sada koriste vlastite app/config putanje,
  ne HFC putanje naslijeđene preko autoloadera.
- Puni produkcijski PHPStan (level 10) i razvojni PHPStan (level 4) prolaze.
  Ciljani PHPCS i Rector dry-run prolaze za izmijenjene klase/config/testove;
  provjere dvojezičnog PHPDoca, dokumentacije modula i prijevoda nemaju problema.
  Korišteni su postojeći susjedni alati bez promjene frameworka ili izvornih
  modula. Dvije zahvaćene skripte zadržavaju zatečene PHPCS nalaze:
  `setup_worker.php` ima šest grešaka/jedno upozorenje (HEAD je imao osam/jedno),
  a `configure_fpm_setup.php` pet grešaka/četiri upozorenja kao u HEAD-u.
  Ne tvrdi se prolaz formatiranja cijelog repozitorija ili `composer on-commit`.
- Oba postojeća browser scenarija, za ljepljivi odabir jezika i Setup provjeru
  verzija, prolaze. Stvarni GUI isključi/uključi, CLI aktivacija, read-only cilj
  i podešeni direktorij zahtjeva workera provjereni su na privremenoj instalaciji
  s vanjskim configom. Mijenjala se samo vanjska aktivacijska datoteka; niže
  postavke ostale su iste. Read-only zapis jasno je odbijen, a GUI je sakrio
  nedostupne jezične radnje bez gašenja nepovezanih prekidača modula.
  Test registra provjerava uklanjanje iz najvišeg sloja te instalaciju/uklanjanje;
  runtime test to provjerava i uz isključenu OPcache provjeru vremena datoteka.
- HFC ima **36 izvršenih, 0 pending migracija**; P1C ne treba novu migraciju
  i nije ponovno pokretao Auth upgrade. Usporedba svih 31 datoteke config stabla
  zabilježenih nakon namjerne izmjene početnog učitavanja nije našla daljnje
  promjene. Jezični podaci i privatne postavke providera očuvani su. HFC koristi
  novi kod na mjestu, ali cijela konfiguracija nije premještena.
- Vlastita GUI testna instalacija, HTTP proces/sesija preglednika i Linux
  kontejner uklonjeni su nakon provjera. Nije bilo backupa HFC-a, promjena
  apps-testa/demo instalacije, Git objave ili sinkronizacije frameworka/izvornih
  modula. Puna E2E provjera, matrica šest instalacijskih načina, potpuna priprema
  vanjskog sigurnog FPM-a, updater i backup/vraćanje nisu dio ovog završenog koraka.

### Provjere i ograničenja P1B

- Puni PHPUnit: **187 testova, 5136 provjera**, bez grešaka, upozorenja ili risky
  testova. Tri testa preskočena su jer traže root/POSIX vlasništvo ili izolirani
  root Linux deployment worker koji ova macOS instalacija nema.
- Puni produkcijski PHPStan (level 10) i razvojni PHPStan (level 4) prolaze.
  Ciljani PHPCS i Rector dry-run za P1B klase/config/testove prolaze; provjere
  dvojezičnog PHPDoca, dokumentacije modula i prijevoda nemaju problema. Korišteni
  su postojeći alati susjednih repozitorija; nisu instalirane ovisnosti niti
  mijenjani framework/izvorni moduli.
- Testovi čiste instalacije pokrivaju potpun vanjski direktorij, nedostajući/prazan
  config, provjeru svih ciljeva zadnjeg sloja prije zapisa i očuvanje postavki.
  Aktualne Auth tablice stvaraju se neposredno, bez starih config tablica/polja.
  Puna/minimalna instalacija evidentira 36/23 migracije; namjerno neispravan
  predložak starog upgradea nikada se ne učita pri čistoj instalaciji.
- Testovi odabira modula pokrivaju točan prijenos stare datoteke, odbijanje sukoba,
  idempotentnost, očuvanje nepoznatih ključeva, nevaljane datoteke, simboličke veze,
  config samo za čitanje i istodobne promjene. Slojeviti PHP config koristi izvorno
  spajanje frameworka; odabir modula je potpuni dokument iz najvišeg postojećeg sloja.
- Postojeći E2E scenarij „Setup checks” iz `application.spec.js` prolazi na
  izoliranoj SQLite instalaciji. Provjereni su stvarni GUI isključi/uključi i CLI,
  uključujući vanjski sloj višeg prioriteta: promijenjen je samo taj sloj, donja
  datoteka ostala je ista, zapis u read-only config jasno je odbijen, a GUI je
  onemogućio nedostupnu radnju. Privremena instalacija, njezin HTTP proces i
  sesija preglednika uklonjeni su nakon provjere.
- Stari HFC `data/config/modules.php` eksplicitno je premješten u ignorirani
  `config/modules.php`, uz identičan sadržaj, i stara datoteka uklonjena je.
  Ponovljeni prijelaz ne radi promjene. Usporedba 33 postojeće config datoteke nije
  našla neželjene promjene postavki. HFC vraća HTTP 200 i Simbioza sadržaj;
  CLI popis modula radi, a status migracija je **36 izvršenih, 0 pending**.
  P1B nije pokretao migracije baze ni stari Auth upgrade na HFC-u.
- Zadani backup cilj odabira modula, updater zaštita kanonske datoteke, GUI
  dijagnostika modula i popis zapisivih datoteka sigurnog FPM-a usklađeni su samo
  koliko je nužno za ovaj korak. Potpuni vanjski updater/backup i preostali
  potrošači konfiguracije tada su ostali otvoreni P1C/P1D. P1C je sada zabilježen
  iznad, kao i P1D. HFC i dalje koristi zadani
  config direktorij; ne tvrditi da je release spreman niti ga cjelovito premještati.
- Nisu pokretani objedinjeni `composer on-commit`, puna E2E provjera ni matrica
  šest instalacijskih načina. Nije bilo backupa HFC-a, promjena apps-testa/demo
  instalacije, Git objave ili sinkronizacije frameworka/izvornih modula.
- Šira eksplicitna PHPCS provjera našla je i preostale probleme formatiranja u
  `public/index.php`, `update.php`, `scripts/configure_fpm_setup.php` i ranijem
  `AuthConfigurationBackupTest.php`. Nove P1B linije su usklađene; ne tvrdi se da
  formatiranje cijelog repozitorija prolazi. Puni konfigurirani opseg ponovno
  provjeriti u P10, bez tihog formatiranja nepovezanih izmjena.

### Sažetak dovršenog P1A

Temelj zajedničkih putanja prošao je 36 ciljanih testova i 160 provjera uz
100% pokrivenost linija/metoda pomoćne klase. HFC je nastavio raditi bez
premještanja postavki u tom koraku. Tadašnji širi nalazi provjereni su i riješeni
kao V1 u P1B; aktualno stanje provjera zapisano je iznad.

## Bilješka za sljedeću sesiju

- Radni korijen: `/Volumes/Ext/Development/CR`; Simbioza/HFC:
  `/Volumes/Ext/Development/CR/HFClean`. Izvorni moduli su susjedni repozitoriji.
- Na dan plana Simbiozin repozitorij je na grani `main`, HEAD `59cb9c8`.
  To je vremenska referenca, ne zahtjev za vraćanjem na taj commit.
- Postoje nezabilježene promjene Autha i njegova Simbiozina bootstrapa te druge
  lokalne izmjene. Prije rada pregledati status svakog zahvaćenog repozitorija;
  ne resetirati niti uključivati nepovezane izmjene u vlastiti posao.
- Framework i jezične datoteke imaju postojeće lokalne izmjene; sačuvati ih.
  P10 je uskladio promijenjeni runtime HFC modula s lokalnim izvorima;
  `EditorWorkspaceIntegration.php` sada odgovara izvoru. Pri sinkronizaciji
  koda ne prepisivati privatnu runtime konfiguraciju.
- Ne pokretati ponovno Auth upgrade samo zato što se nastavlja ovaj plan.
- Koristi dovršeni `App\Configuration\ConfigurationPaths`; ne stvaraj drugi
  parser parametara ili fallback koji zaobilazi sloj višeg prioriteta.
- **Sljedeće: P10 prenosivost autorskih poveznica**, prema potvrđenom nedostatku
  i malom zadatku opisanom iznad. Konfiguracijske/platformske/pune provjere
  kvalitete i sva tri browser skupa po 80/80 dovršeni su; ne ponavljati ih bez
  relevantne izmjene. P9 je dovršen; četiri modula nemaju
  dodatne instalacijske postavke za selidbu. Sačuvati odvojenost podataka
  dokumenata/korisnika/preglednika; ne stvarati prazne config mape niti ponavljati
  migracije sheme/podataka. P9 regresije i 12 browser/API provjera prolaze.
  P8 je dovršen;
  Backup/API koriste svoje poddirektorije, Audit zajedničke app postavke, a ORM
  zajednički korijenski `database.php`. HFC ima **45/0 migracija**, filesystem HTML
  i dokumentiranu vlasničku strukturu
  podataka. Ne ponavljati migraciju podataka niti mijenjati HTML driver HFC-a samo
  radi nastavka rada. Pretvorbe testirati na izoliranim fixture. Stara migracija
  vrijedi samo za izvorna izdanja 0.2.12 ili starija; novije/čiste instalacije
  izravno koriste aktualne putanje. P6 čita modulov
  `calendar/calendar.php` kroz framework resolver; upgrade-only premještanje
  datoteke primijenjeno je u HFC-u i provjera preglednikom prolazi. Sačuvati GUI
  i kalendarske podatke. U kodu i opisima primjera koristiti cijeli EN pa HR blok.
  P1–P4 ne ponavljati bez regresije;
  ne pokretati ponovno dovršene upgrade migracije. Četiri aktualna JSON primjera
  opisana su u konfiguracijskim uputama; privatni JSON nije predložak za Git.
  Docker/SimpleSAMLphp rješenje ostaje zabilježeno za kasniju Docker fazu.
