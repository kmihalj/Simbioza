# Audit prije releasea i nastavak — 0.3.0

Ranije [provjere inicijalnog izdanja](initial-release-0.3.0_hr.md) dokazuju stanje
**prije** novog registra skripti. Ne odobravaju objavu proširenog kandidata.
Nije odobren release/tag/push ni nadogradnja udaljenih instalacija.

## Registar skripti

- [x] Registar u datoteci pod `HPH_CONFIG_PATH/editor-html/`, sa slojevitim atomskim zapisom.
- [x] Administratorsko odobrenje/opoziv i odabir u editoru po stranici, bez globalnog učitavanja i H5P iznimke.
- [x] Confluence bilježi kandidate, grupira identične skripte po stranicama i omogućuje aktivaciju jedne/svih pripadajućih stranica.
- [x] Novi import automatski odabire samo točno lokalno odobrena podudaranja, nikada nove/odbijene definicije.
- [x] Administratorski pregled razlikuje aktualne objave/nacrte i opcionalnu povijest u oba backenda, uz skupne SQL upite.
- [x] Backup referenciranih definicija/kandidata ne prenosi odobrenje; lokalne odluke odredišta vrijede. Restore čisti HTML.
- [x] Odvojene EN/HR upute i opisani `.json.dist` primjer.
- [x] Promjena naziva mijenja samo oznaku za prikaz; import čuva lokalne nazive i odluke.
- [x] Šest javnih arhiva uputa ponovno pakirano za aktualne Editor sheme; sadržaj datoteka nije promijenjen.
- [x] Dovršiti integriranu GUI provjeru odobrenja, odabira, pojedinačne/skupne aktivacije importa, ponovnog importa i pregleda upotrebe.
- [x] Zapisati konačni rezultat cijelog browser paketa nakon novih promjena.
- [x] Potvrditi backup/vraćanje stranice/područja/sitea sa skriptama, uključujući prekid i provjeru na odredištu.

## Obuhvat audita

Pregled i lokalne ispravke A1–A5 dovršeni su, uz dokaze ispod. Aktualni nastavak
odobrenje je izdanja i provjera objavljenih artefakata, ne stari plan nadogradnje
konfiguracije. Lokalne provjere ne dopuštaju objavu.

- [x] Pregledati aktivne čitače konfiguracije; ispraviti Search/Confluence stablo i Search backup politiku.
- [x] Pregledati aktualne primjere/upute; dopuniti API/Backup primjere i ukloniti stare aktivne upgrade upute. Dosljednost jezika/primjera dovršena je u A5.
- [x] Izmjeriti rast SQL upita i pokrenuti postojeće Calendar/Workspace/Editor limite; Notification problem zabilježen je kao A2.
- [x] Pregledati upload/ZIP/putanje/SSRF, HTML/iframe, zaglavlja, kolačiće/sesije, CSRF/prava i API. Implementirati ciljane ispravke; Auth protokolski ispravak A1 dovršen je u zapisu ispod.
- [x] Provjeriti minimalne kombinacije ovisnosti generičkih modula bez izmjene Frameworka.
- [x] Pregledati Docker putanje upisa i zahtjeve SimpleSAMLphp-a; zabilježiti A3/A4, ne proglasiti image spremnim.
- [x] Ponoviti pogođene SQLite clean-install i stvarne Linux FPM/sigurne-FPM provjere. Shema nije mijenjana; datumi provjera drugih baza ostaju jasno navedeni.
- [x] Riješiti A1–A5 i ponoviti pogođene lokalne provjere.
- [ ] Dobiti odobrenje, pa koordinirati objavu i provjeru označenih paketa/imagea. Korisnik ponovno instalira demo/apps-test.

## Rezultati sveobuhvatnog audita — 12. listopada 2026.

Pregled je utvrdio potrebne ispravke, ne odobrio release.
Prolazak postojećih testova ne zatvara dolje navedene nalaze. HFC koristi
provjerene runtime ispravke; udaljeni demo/apps-test i Framework nisu mijenjani.

### Implementirani ispravci

- Search sada čita nativno slojevito stablo `workspace-search.workspace-search`
  na `HPH_CONFIG_PATH/workspace-search/workspace-search.php`. Confluence čita
  `confluence-import.confluence-import` u svojem modulskom direktoriju.
  Zadane vrijednosti ostaju paketni read-only resursi. Relativna Confluence
  staging putanja odbija apsolutne putanje i izlaz preko roditeljskog direktorija.
- Aplikacijski backup koristi isti Search cilj i njegovih stvarnih pet
  podržanih ključeva. Privatne instalacijske putanje ne prenose se na drugi host.
- SecurityHeaders i ErrorHandler middleware uključeni su pravilnim redoslijedom,
  uključujući request-metrics način. Pravila vrijede za obične odgovore,
  preusmjerenja, zabrane i greške; privatni detalji greške nisu javni.
  CSP zasad dodaje samo `base-uri 'self'; object-src 'none'; frame-ancestors 'self'`.
  Nije potpuna `script-src` politika niti zamjena za čišćenje HTML-a/registar.
- Editorov browser dijalog i serversko čišćenje koriste
  `allow-scripts allow-presentation`, bez `allow-same-origin`. Za vanjske
  ugradnje koje trebaju pohranu/kolačiće podrijetla treba provjeriti
  kompatibilnost; sam HTTPS ne odobrava njihov JavaScript. H5P nema iznimku.
- Webhook transport spaja se na numeričku adresu koju je provjerio, uz izvorni
  Host i TLS naziv. Odbija miješane javne/privatne DNS odgovore, nejavne IPv4/IPv6
  adrese, nesigurne URL-ove/zaglavlja i preusmjerenja. Odgovor i vrijeme ograničeni
  su. Stvarni izolirani HTTP test potvrđuje vezanje na adresu i izostanak praćenja
  preusmjerenja; uspješni TLS/SNI s produkcijskim certifikatom još treba vlastitu
  integracijsku provjeru prije tvrdnje da su provjereni svi webhook pružatelji.
- API i Backup imaju po jedan kanonski PHP `.dist` u korijenu modula, preslikan
  u Simbiozine primjere, s opisom svake opcije u cjelovitim EN/HR blokovima.
  Nativno učitavanje datoteka provjereno je bez GUI-ja i privatnih HFC postavki.
  Aktivne modulske upute više ne obećavaju izvoz stare 0.2.x konfiguracije.
  Početničke Backup naredbe sada koriste stvarni `vendor/bin/hph` i
  `orm-migrate:up`, nakon uključivanja modula. Host bez Autha mora sam zaštititi
  web konzolu ili koristiti samo CLI.

### Izmjereni SQL upiti

`scripts/verify_audit_scaling.php` prihvaća samo novu, zadržanu, privremenu
SQLite instalaciju svih modula i stvara izmišljene korisnike/stranice. Svako
mjerenje koristi novi skup servisa i broji samo upite promatranog čitanja.

| Čitanje | Jedan zapis | 100 zapisa | Nalaz |
| --- | --- | --- | --- |
| Popis komentara, limit 30 | 4 upita | 4 upita | Ograničeno u ovoj provjeri |
| Inbox obavijesti, limit 30 | 9 upita | 504 upita | Stanje prije A2; popravak zapisan ispod |
| Auth-user SELECT upiti unutar inboxa | 1 | 100 | Ponovno čitanje istog korisnika |

Prije A2 `NotificationService::visibleRows()` čitao je cijeli inbox prije
paginacije. Simbiozin visibility provider pozivao je `FollowTargetService` za
svaku obavijest; čitanje čvorova/područja/ACL-a i korisnika uzrokovalo je rast.
Popravak koristi skupnu vidljivost/ciljeve, bez zadržavanja pozitivnih odluka
između operacija. Izvorni zahtjev dopuštao je samo request-local predmemoriju
prava s ispravnim poništavanjem.
Ne preskakati ACL niti prikrivati problem zagrijanim cacheom. Dodati ograničenja
broja upita za različite ciljeve, read/unread zbrojeve i korisnike različitih prava.

Prošli su postojeći Calendar performance testovi: popis 30 kalendara koristi
najviše pet relevantnih SELECT upita, a import 122 ICS događaja najviše 35 upita.
Prošli su i Workspace/Editor browser limiti te pregled upotrebe skripti
(dva upita za više od 200 verzija, oba backenda). Ti limiti vrijede za konkretne
provjere, ne dokazuju da je svaki aplikacijski upit optimalan.

### Ispravci, redoslijedom

- [x] **A1 — Auth/OIDC sigurnost (uvjet releasea).** Implementirano i provjereno na HFC-u: potpis/nonce/PKCE/subjekt i ograničeni TLS transport. Vidi zapis dovršenog A1 ispod; bez promjena Frameworka ili udaljenih siteova.
- [x] **A2 — Skaliranje obavijesti.** Skupni aktualni ACL, ograničena memorija,
  stabilan cursor i grupni zapisi primijenjeni su na HFC. Prošli su hladni
  zahtjevi i četiri baze; browser/API paket je 80/80. Vidi zapis A2 ispod.
- [x] **A3 — Runtime jezični paketi.** Instalirani JSON katalozi su u podacima;
  paketni aplikacijski/modulski `lang/` ostaje kod. Provjereni su HFC, jezični
  GUI/CLI, transakcijski backup/vraćanje i FPM s read-only kodom. Vidi A3 niže;
  Framework nije mijenjan.
- [x] **A4 — Nepromjenjivi deployment.** Image/Compose i izričite zaključane
  migracije prošli su u Colimi. Implementirani su runtime/DB volumeni, zaštita
  koda, očuvani GUI postavki/sadržaja/jezika i opcionalni read-only SAML.
  Dokazi i aktualne Docker upute su ispod.
- [x] **A5 — Dosljednost uputa/komentara.** Označeni komentari imaju odvojene
  EN pa HR blokove, uz provjeru redoslijeda stvarnih tokena. Početnički primjeri,
  aktualne konfiguracijske putanje, poveznice README-ja/datoteka/naslova i granice
  povijesnih izdanja ispravljeni su. Zapis A5 je ispod.
- [x] Nakon ispravaka ponoviti pogođene lokalne provjere.
- [ ] Dobiti izričito odobrenje releasea. Objava i udaljene reinstalacije nisu dio ovog audita.

Za A1 sam izostanak lokalne provjere potpisa nije dokaz anonimnog zaobilaženja
prijave JWT-om: OIDC code-flow odgovor može koristiti provjeren izravni TLS pod
uvjetima specifikacije. Provjere izdavatelja, publike, trajanja i identiteta ipak
su potrebne. Pratiti
[OpenID Connect pravila provjere ID tokena](https://openid.net/specs/openid-connect-core-1_0.html#IDTokenValidation).

### Zapisane provjere ovog pregleda

- Prošlo je svih 18 javnih modulskih paketa: 1.063 testa / 9.092 provjere,
  uključujući završni regression browser predloška sandboxa.
- Aplikacijski paket prošao je 242 testa / 5.995 provjera, uz tri Linux/root
  slučaja preskočena na macOS-u. Ta tri prošla su zasebno u Colimi (43 provjere),
  bez izmjene konfiguracije host servisa.
- Prošla je 21 nova SQLite kombinacija sa stvarnim Composer/CLI/migracijama
  i HTTP pokretanjem, uključujući minimalne generičke instalacije.
- Cijeli izolirani SQLite browser/API/performance paket prošao je 80/80
  u 11,5 minuta, uz izlazni status 0 i završnu potvrdu alata.
  Kasnija izmjena iframe literala browser dijaloga prošla je ciljane testove
  predloška/čišćenja; to nije drugo pokretanje svih 80 scenarija.
- Stvarni Linux FPM i sigurni FPM prošli su početnu/prijavu te Auth/Menu/Theme/
  registry upise uz read-only kod, zapisive podatke i završni config sloj.
- Integracijski testovi konfiguracije/primjera/sigurnosnih zaglavlja prošli su
  14 testova / 223 provjere; koriste stvarne slojeve Frameworka bez HFC tajni.
- Audit instaliranih ne-razvojnih ovisnosti nije našao poznate advisories.
  To ne obuhvaća zasebni SimpleSAMLphp niti certificira sigurnost izvornog koda.
- Podešeni PHPStan i stil prošli su za aplikaciju i promijenjene runtime module
  (razvojna analiza gdje je podešena; Confluence ima samo produkcijsku PHPStan
  konfiguraciju). Prijevodi, EN/HR parovi/poveznice uputa i prisutnost oba jezika
  u metodskoj dokumentaciji nemaju problema; zadnja provjera ne provjerava
  redoslijed jezika i zato ne zatvara A5.
- HFC početna/prijava vraćaju HTTP 200; 14 migracija izvršeno, nula na čekanju.
  Ovi ispravci nisu trebali novu migraciju sheme.

PostgreSQL 18/MySQL 8.4/MariaDB 11.4 rezultati od 11. listopada ostaju povijesni
dokaz, ne ponovno pokrenute baze ovog pregleda. Izvještaji su u
`build/audit-0.3.0/`, `build/clean-install-matrix-sqlite.json` i browser izvještaju.
Točne Docker granice opisuje [spremnost za containere](container-readiness_hr.md).
Testne instalacije imaju samo sintetičke podatke; čišćenje nikada ne slijedi
modulske/vendor symlinkove. Zadržane scaling/E2E instalacije i FPM ulazna kopija
uklonjene su bez backupa nakon provjere; testni zapisi namjerno su privremeni.
Izvještaji i alati ostaju. Linux FPM alat zaustavlja svoj proces i uklanja svoj fixture.

## Zapisane provjere tijekom implementacije

11. listopada 2026.: prošli su ciljani Editor paket od 224 testa / 1.628 provjera
i Confluence paket od 116 testova / 722 provjere. Produkcijski PHPStan prošao je
za oba modula, kao i razvojna analiza Editora. Prošle su provjere stila modula.
Test upotrebe pokriva više jezika, oba backenda, obrisane stranice,
povijest, nedostajuće datoteke i dva skupna upita za više od 200 verzija.
To su ciljani rezultati, ne dovršetak cijelog gore navedenog audita.

Zaštićeni razvojni alat `scripts/verify_external_script_import.php` pokrenut je
na zadržanoj izoliranoj SQLite E2E instalaciji, nikada na HFC-u ili udaljenom siteu.
Provjerio je stvarni Confluence ZIP import i reimport na engleskom, prepoznavanje
točnog odobrenja, uključivanje jedne/svih stranica, običnu stranicu bez skripti,
upotrebu kroz dva importa i stvarno vraćanje arhive stranice/područja/sitea.
Arhive su sadržavale sve referencirane definicije, ali ne status odobrenja.
Unit provjera odredišta/prekida potvrdila je da nove skripte ostaju na čekanju,
a paralelne lokalne administratorske odluke preživljavaju rollback. Engleski
import otkrio je i ispravio nedostajuću jezičnu politiku novih područja/čvorova;
postojeći Confluence preglednički scenarij ponovno je prošao (1/1).

Ručne provjere u pregledniku potvrdile su dodavanje/odobrenje, odabir u editoru,
spremanje nacrta, objavu, opoziv, promjenu naziva, gumbe jedne/svih stranica i
pregled upotrebe. Objavljene stranice izvršile su svaku odabranu odobrenu skriptu
jednom; nepovezane stranice, editor/nacrt/povijest/izvještaj nisu ih izvršili.
Obični korisnik dobio je HTTP 403 na ruti upotrebe. Ekran upotrebe vizualno je
provjeren na 1280 i 390 piksela: tablica se pomiče lokalno, stranica ne prelijeva.
Screenshotovi su u `output/playwright/external-script-usage*.png`. Privremena
instalacija i testni backupi uklonjeni su; privatne HFC postavke nisu prepisane.

Šest arhiva uputa koristi aktualne Editor sheme bez promjene sadržajnih zapisa
i datoteka. Prošla su oba testa ugrađenih uputa (2 testa / 65 provjera).
HFC ima 14 izvršenih migracija i nula na čekanju; auditi prijevoda,
dokumentacije i dvojezičnog PHPDoc-a prijavljuju nula problema.

Prošla je nova lokalna SQLite instalacija slučaja `editor-html` u 11,285 sekundi:
Composer, CLI pokretanje, dvije migracije i HTTP odgovor početne stranice.
Instalirani su samo ORM, Auth i Editor, bez Simbioze, Menua, Themea i Workspacesa.
Alat je nakon provjere uklonio privremenu instalaciju.

Konačni lokalni SQLite paket pregledničkih/API/performance testova prošao je
svih 80 scenarija u 10,3 minute, uz izlazni status 0 i završnu potvrdu alata.
Pokriva postojeće tokove Confluence importa, backupa/vraćanja, prava pristupa i
ograničenja SQL upita. Gore opisane zasebne integracijske i ručne provjere skripti
pokrivaju odobrenje, automatsko povezivanje i pregled upotrebe; nisu dodatni
scenariji unutar tog paketa od 80 testova. Alat je počistio izoliranu instalaciju.

HFC ostaje razvojna instalacija. Privatne postavke i Framework zaštićeni su;
za skripte nije potrebna nova migracija sheme baze.

## A1 dovršen — 12. listopada 2026.

A1 je dovršen za aktualnog razvojnog kandidata. To nije odobrenje releasea,
zatvaranje A2–A5 niti dopuštenje nadogradnje demo/apps-test instalacija.

### Implementacija

- Auth ima nativni `OidcIdTokenValidator`, `AuthBackchannelClient` i zamjenjivi
  `AuthBackchannelClientInterface`. Framework nije promijenjen i nema nove
  kriptografske Composer ovisnosti. Zahtjevi cURL/OpenSSL dokumentirani su kao
  opcionalne ekstenzije potrebne odgovarajućim protokolima.
- OIDC koristi izričiti issuer/JWKS i provjerava RSA potpis, issuer, publiku
  klijenta/azp, subjekt, istek/izdavanje/not-before, nonce i traženu starost
  autentikacije. UserInfo mora imati isti potpisani subjekt i ne može dodati
  ili zamijeniti rezervirane sigurnosne claimove. Nema nepotpisanog JWT fallbacka
  za identitet. Podržano je RS256/RS384/RS512, zadano RS256; nepodržani
  HMAC/EC/JWE algoritmi izričito su dokumentirani.
- OIDC zadano koristi S256 PKCE; generički OAuth2 ima izričito uključivanje i
  i dalje autentificira samo iz UserInfo. Callback state jednokratan je, istječe
  nakon 600 sekundi i veže profil/protokol, izvorni callback i konfiguraciju/
  mapiranja. Troši se i pri grešci providera. Postojeće aplikacijske sjednice
  ostaju valjane; nedovršeni callbackovi starog formata traže novi pokušaj.
- Nativni backchannel provjerava TLS lanac/naziv hosta, podržava privatni/interni
  HTTPS i vlastiti CA, ne prati preusmjerenja, dopušta samo GET/POST te ograničava
  povezivanje/ukupno vrijeme na 10/30 sekundi i odgovor na 2 MiB.
  Nesigurni HTTP izričita je neprodukcijska opcija, ne isključivanje TLS provjere.
  CAS koristi isti transport uz očuvanje CA-a i mapiranja.
- GUI i ručni JSON imaju iste opcije povjerenja/PKCE/CA-a.
  `heartphrame-module-auth/docs/oidc_security_hr.md` i engleski par objašnjavaju
  svaku novu opciju uz početnički JSON primjer. README modula, upute providera/
  konfiguracije/instalacije i EN/HR prijevodi ažurirani su.
  Opisni `auth.json.dist` i Simbiozin `config/auth.json.dist` jednaki su.

### HFC i provjere

- HFC koristi novi kod modula. Samo postojeći OIDC profil dobio je issuer/JWKS
  iz pouzdanih javnih AAI discovery metapodataka; tajna klijenta, endpointi,
  mapiranja, ključ profila i korisničke veze nisu prepisani. Sačuvani su ID-evi
  svih sedam SSO profila i dvije veze korisničkih identiteta.
- Korisnik je u pregledniku dovršio stvarni test AAI OIDC profila. Ekran uspjeha
  i baza potvrđuju da je profil ID 3 ponovno provjeren/operativan.
  SAML/CAS/OAuth2 konfiguracija i status provjere nisu promijenjeni.
  Ovo potvrđuje stvarni OIDC callback, ne tvrdi da su svi ostali vanjski
  provideri danas ručno ponovno testirani; CAS/privatni CA i protokolska matrica
  imaju regresijske provjere.
- Auth: **123 testa / 933 provjere**, PHP 8.5.11. Novi testovi potpisa, potpunog
  authorization toka, OAuth2 i nativnog transporta pokrivaju nevaljane/izmijenjene
  tokene, issuer/publiku/nonce/subjekt, starost/istek, PKCE, ponavljanje,
  nepodudaranje profila/konfiguracije/mapiranja/callbacka, greške providera,
  rezervirane parametre, CAS CA, zabranu preusmjerenja, veličinu odgovora i
  stvarno TLS prihvaćanje/odbijanje CA-a i naziva hosta.
- Colima Linux PHP 8.4.26: **38 sigurnosnih testova / 345 provjera**, uz kod samo
  za čitanje i privremeni zapisivi `/tmp`; bez stvarnih vjerodajnica ili mrežnog
  providera. Nativni HTTPS koristio je samo sintetičke localhost certifikate.
- Prošli su produkcijski/razvojni PHPStan i stil izvornog/testnog koda.
  Auditi prijevoda/dokumentacije/prisutnosti dvojezičnih komentara imaju nula
  problema. To ne zatvara preostali redoslijed jezika iz A5.
- Minimalna generička Auth + ORM + Framework SQLite instalacija prošla je
  Composer, CLI/migracije i HTTP pokretanje u **11,868 sekundi**, bez Simbioze,
  GUI konfiguriranja ili privatnih HFC postavki.
- Unit paket aplikacije: **242 testa / 5.995 provjera**, tri nepromijenjena
  Linux/root slučaja preskočena na macOS-u. Za A1 nisu ponovno pokrenuta.
- Cijeli izolirani SQLite preglednički/API/performance paket: **80/80**,
  **17,9 minuta**, izlaz 0 i završna potvrda alata. Server je zaustavljen i
  privremena instalacija uklonjena bez praćenja module/vendor symlinkova;
  izvještaji ostaju u `build/playwright-report/`, `build/test-results/` i E2E logovima.
- HFC početna/prijava radi; **14 migracija izvršeno, nula na čekanju**. Nije
  potrebna nova migracija sheme. Framework, udaljeni demo/apps-test i nepovezane
  izmjene ostali su netaknuti; nema commita, pusha, taga ni releasea.

### A2 dovršen — 12.10.2026.

- Notification dodaje opcionalni `NotificationBatchVisibilityProviderInterface`;
  registar spaja pravila s AND i odbija iznimke, nevaljane liste i ne-boolean
  odobrenja. Pojedinačna pravila u memoriji ostaju podržana; generički modul
  ne ovisi o Simbiozi.
- `NotificationService::visibleBatches()` čita kandidate vlasnika/stanja u
  grupama do 128 redaka, po nepromjenjivom datumu stvaranja i ID-u. Memorija
  zadržava grupu i traženu/zadnju vidljivu stranicu; točan zbroj i dalje prolazi
  sve grupe. Skupno čitanje/brisanje zapisuje samo vidljive ID-eve uz dodatne
  uvjete vlasnika/stanja, bez preskakanja redaka offsetom.
- Novi `FollowTargetVisibilityResolverInterface` implementira
  `FollowTargetService::canViewTargets()`: jedan aktivni korisnik po grupi,
  ciljana područja/stranice/preci i svježi skupni ACL. Workspace zadržava svoja
  ograničenja i izravna prava; nepotpuni/ciklični preci uskraćuju pristup.
  Calendarov `readableCalendarIds()` koristi vlastitu politiku i ostaje
  opcionalan. Ugrađeni kalendar/lista zadataka zahtijevaju i pravo na stranicu.

| Hladni inbox test | Prije A2 | Nakon A2 | Dohvati aktivnog korisnika nakon A2 |
| --- | --- | --- | --- |
| 1 različita stranica | 9 upita | 9 upita | 1 |
| 100 različitih stranica | 504 upita | 9 upita | 1 |
| 1.000 različitih stranica | Nije mjereno | 65 upita | 8 grupa / 8 dohvata |

Isti budžet 9/9/65 prolazi na SQLiteu, PostgreSQL-u 18, MySQL-u 8.4 i MariaDB-u
11.4, uz rekurzivne pretke, izravna prava i oduzimanje prava u istom servisu.
Komentari ostaju na četiri upita. Izvještaji su
`build/notification-scaling-{sqlite,pgsql,mysql,mariadb}.json`;
`scripts/verify_audit_scaling.php` prihvaća samo privremenu matrix instalaciju,
s namjenskom loopback bazom `codex_a2` za mrežne drivere.

Provjere:

- Notification **11 testova / 86 provjera**; Simbioza User **22 / 211**;
  Workspace **126 / 967**; Calendar **86 / 690**.
- Regresije pokrivaju više od tri grupe, iste datume, skrivene retke, točne
  zbrojeve stanja, zadnju stranicu, skupne izmjene, različite korisnike, efektivne
  grupe, izravna prava, oduzimanje ACL-a/članstva u istom servisu, nestale ciljeve,
  neaktivnog korisnika, opcionalni Calendar i pravo izvora plus stranice.
  Zaseban hladni test koristi 100 različitih područja.
- Produkcijski/razvojni PHPStan i stil izmijenjenog koda/testova prolaze za sva
  četiri modula. Prošli su auditi dokumentacije, prijevoda i prisutnosti
  dvojezičnih komentara; A5 ostaje otvoren za postojeći redoslijed jezika drugdje.
- Cijeli izolirani SQLite browser/API/performance paket **80/80, 18,6 minuta**,
  završni izlaz 0 i potvrda alata. Prolaze i aplikacijski unit testovi (konačan
  broj u zapisu dodatnih popravaka).
- Prošle su četiri čiste instalacije svih modula, CLI/migracije/HTTP.
  HFC ima **14 izvršenih migracija / nula na čekanju**; nema A2 migracije ili
  config promjene. Checksum privatnog Auth configa ostaje isti. Nema promjena
  Frameworka, udaljenog demo/apps-test sitea, commita, pusha, taga ni releasea.

### Dodatni korisnički nalazi — 12.10.2026.

Konačni aplikacijski unit paket: **243 testa / 6.004 provjere**, tri nepromijenjena
Linux/root slučaja preskočena na macOS-u. Jezične regresije: **11 / 71**.
Izolirani administratorski browser potvrdio je mod_php/CLI upute, odvojene
revizije, oznake neevidentirane revizije i poruku uspješne svježe provjere.
Nakon testova uklonjene su naše matrix/browser instalacije i tri privremena
bazna containera; izvještaji i korisnikova izvorna arhiva ostaju.

- DABAR: dostavljeni izvor sadrži prazne `time[datetime]` elemente. Datum se
  materijalizira prije sanitizacije i ponovno nakon pretvorbe makroa; oznake i
  susjedno doslovno vrijeme ostaju isti. Pokazana ranije uvezena HFC stranica
  već je izgubila datum; samo ta vrijednost vraćena je iz arhive, bez ponovnog
  uvoza područja. Izvorna arhiva nije mijenjana. Confluence Import: **117 / 725**.
- Setup Jezici: izričita svježa provjera kataloga dostupna je i na mod_php;
  lokalna/udaljena revizija su odvojene, a neevidentirana lokalna revizija ne
  proglašava se aktualnom. Neuspjela svježa provjera ne koristi prešutno stari
  cache. GUI pokazuje `languages available --refresh`, `languages update` i,
  po potrebi, `languages install <locale> --replace` za početnu evidenciju.
  Pri završetku A2 postojeća FPM/helper ograničenja zapisivanja još su vrijedila.
  Promjena samo prijevoda/digesta testirana je bez aplikacijskog/modulskog
  releasea. Runtime spremanje jezika tada nije mijenjano; A3 niže zamjenjuje
  ta ograničenja načina zapisivanja.

Točka nastavka nakon A2 bila je A3; provedeni obuhvat i provjere zapisani su niže.

## A3 — Runtime jezični paketi (12. listopada 2026.)

### Implementirano i primijenjeno na HFC

- `RuntimeLanguageStorage.php` sprema instalirane kataloge kao ograničeni JSON
  u `data/languages/catalogs/<jezik>.json`, zastavice u `data/languages/flags/`,
  a revizije/digeste repozitorija u `data/languages/repository-versions.json`.
  JSON se nikada ne izvršava kao PHP. Kanonske oznake, fiksni relativni ciljevi,
  odbijanje podređenih symlinkova i atomski zapis ograničavaju objavu podataka.
- `RuntimeTranslator.php`, `config/app.php` i `config/services.php` ostavljaju
  aplikacijske/modulske `lang/` direktorije kao read-only izvorne resurse.
  Instalirane vrijednosti nadjačavaju iste ključeve; nedostajući ključevi koriste
  paketne/modulske prijevode i uobičajeni fallback neizmijenjenog Frameworka.
- `LanguagePackManager.php` i `RepositoryLanguageManager.php` koriste
  `.languages.lock` posljednjeg sloja, zatim podatkovni `.packs.lock`.
  Instalacija/uklanjanje prvo provjerava sve ciljeve te vraća bajtove/prava pri
  neuspjeloj objavi. GUI, CLI i backup dijele iste lockove. Izvorni digest
  evidentira se atomski s repository paketom; ručna zamjena briše prethodnu
  evidenciju revizije. Klasični updater više ne čuva generirane aplikacijske
  `lang/<jezik>.php` datoteke; čuva `data/`, kojem pripada instalirano jezično stanje.
- `SetupController.php`, `SetupDiagnostics.php`, `views/setup/index.php` i
  installer dopuštaju podatkovne jezične radnje na mod_php i FPM-u bez
  privilegiranog helpera za izmjenu koda. Administratorska/CSRF zaštita i zaštita
  zadnjeg uključenog jezika ostaju. Paketne radnje koje mijenjaju kod nisu mijenjane.
- `ApplicationLanguageBackupProvider.php` omata generički config provider kao
  postavke aplikacije, shema **3**. Prenosi registar, kataloge, sigurne zastavice
  i revizije, nikada izvršive kataloge/putanje izvora/cache/preuzimanja.
  Replace uklanja izostavljene pakete; merge čuva lokalne jezike, ali dolazno
  isključivo paketno stanje primjenjuje umjesto odredišnih overlaya. Vraćanje na
  drugi root i rollback kasnijeg providera čuvaju lokalne putanje i izvorna prava.
  Backup samo stranice/područja ne prenosi instalacijske jezike.
- Menuov `AssetController.php`, `MenuRenderer.php` i
  `MenuSettingsController.php` čuvaju cijelu pismovnu/regionalnu oznaku jezika
  za instalirane zastavice, uz osnovni fallback i ograničene ciljeve resursa.
  Generički modul nije dobio ovisnost o aplikacijskim jezičnim klasama.
- HFC-ovi prethodno objedinjeni EN/HR katalozi (po 4.472 zapisa) kopirani su
  u runtime JSON bez gubitka izvornog ključa/vrijednosti. Paketni aplikacijski
  katalozi sada imaju po 228 aplikacijskih/zadanih ključeva; modulski katalozi
  ostaju u paketima. Efektivni EN/HR katalozi imaju po 4.549 ključeva, uključujući
  nove/aktualne modulske/zadane ključeve. To je jednokratno usklađivanje razvojnog
  sitea, ne migracija pri pokretanju ili nadogradnja starog izdanja.
- Odvojene EN/HR upute za lokalizaciju, konfiguraciju, instalaciju, containere,
  README i Menu konfiguraciju opisuju aktualno ponašanje. Framework, HFC SSO
  provider postavke, udaljeni demo/apps-test i izvorne arhive nisu mijenjani.
  Nema migracije sheme baze, releasea, taga ni pusha.

### Provjere

- Aplikacija: **256 testova / 6.060 provjera**, tri postojeća Linux/root slučaja
  preskočena na macOS-u. Ciljani jezični paket: **24 / 126**.
- Menu: **68 / 402**, uključujući točne regionalne zastavice, osnovni fallback,
  odbijanje nesigurnih zahtjeva i prikaz cijele oznake jezika.
- Produkcijski/razvojni PHPStan i konfigurirane stilske provjere prošli su u
  aplikaciji i Menuu. Audit prijevoda, parova/poveznica dokumentacije i prisutnosti
  dvojezičnog PHPDoca ima nula problema; redoslijed komentara ostaje A5.
- Nova all-module SQLite instalacija prošla je Composer, CLI, 14 migracija i
  početni HTTP. Linux Colima FPM i secure FPM prošli su instalaciju/zamjenu jezika,
  modulski fallback i uklanjanje uz read-only `src/` i `lang/`, zapisive podatke
  i deklarirani konfiguracijski sloj. Secure FPM imao je i read-only donji sloj
  konfiguracije. Nije registriran trajni servis.
- Playwright CLI na zasebnoj sintetičkoj instalaciji provjerio je JSON upload,
  aktivaciju, promjenu jezika, isključivanje/uključivanje, zastavicu HTTP 200 i
  uklanjanje dok je taj jezik odabran. Mijenjani su samo JSON/SVG podaci.
  Snimka: `output/playwright/a3-language-installed.png`.
- HFC početna HTTP **200**, **14 izvršenih / 0 pending** migracija; checksum Auth
  provider konfiguracije nepromijenjen. Prema korisničkoj uputi nije izrađen
  backup razvojnog sitea; privatne provider postavke nisu zamijenjene.
- Završni puni izolirani SQLite browser/API/performance paket prošao je
  **80/80**, **18,4 minute**, izlaz **0**, uz završnu poruku uspjeha pokretača.
  Prvi prolazak imao je 79/80 i pokazao staru provjeru koja Bootstrapov pristupačni
  odrezani upload input od 1px smatra običnom vidljivom kontrolom datoteke.
  Test sada odvojeno provjerava odrezivanje; ponašanje aplikacije nije isključeno
  radi prolaska testa.
- Uklonjene su tri vlastite SQLite testne instalacije i FPM ulazna kopija,
  bez praćenja vendor/modulskih symlinkova. Zaustavljeni su privatni FPM/testni
  HTTP procesi i browser sesija. Izvještaji, snimke, kod i HFC runtime jezici
  ostaju; potrošni testni podaci nemaju backup.

**Raniji nastavak:** A5 je nakon A1–A4 bio otvoren; njegov dovršeni zapis
sada je ispod. Izričito odobrenje releasea ostaje otvoreno. Podrška punom Confluence site exportu ostaje
informativna i odgođena; ovdje nije proširivan taj importer.

## A4 dovršen lokalno — 12. listopada 2026.

[Aktualne Docker upute](docker_hr.md) pokrivaju gradnju, volumene, inicijalizaciju,
nadogradnje/migracije, workere, rotaciju lozinki i opcionalni SAML overlay.
Implementacija je u `docker/`, `scripts/build_container_context.php`,
`src/Deployment/`, Setup/Composer/modulskim/updater granicama te izoliranim
`tests/src/Deployment/` / `tests/Fixtures/container_probe.php` provjerama.

- Očišćeni ARM64 PHP 8.4 Apache image bez runtime Composera, read-only kod/root,
  UID/GID 33, bez dodatnih capabilities i uz tmpfs. Jedan privatni config/data/SAML
  volumen, izravni data subpath mount, zaseban PostgreSQL 18 volumen i app uloga bez superusera.
- Završna čista instalacija imagea: 14 migracija, testni administrator, tema i
  deset stranica vodiča. Prošla je CLI jednokratna priprema i otvaranje installera.
- Zamjena/ponovno stvaranje očuvali su korisnika/stranice, testni DB red, bajtove
  privitka, točan Auth hash i browser sesiju. GUI uključivanje/isključivanje prošlo je.
- Namjerno neuspjela migracija ostavila je stari ID i blokirala posluživanje;
  ispravni image primijenio je točno jednu migraciju. Nesigurni downgrade odbijen je.
  Ne obećava se automatski DDL rollback, multi-replica koordinacija ili zaustavljanje pisaca.
- Prošli su PostgreSQL workspace export/merge restore i application-config jezični
  roundtrip; bajtovi instaliranog kataloga ostali su isti, a paketni `lang/` read-only.
  CLI email worker prošao je s praznim redom, bez slanja vanjske pošte.
- Očišćeni SimpleSAMLphp 2.4.4 mount učitan je s privatnim configom/sesijama/cacheom;
  testni SP metapodaci vratili su HTTP 200. Stvarna container SP/IdP registracija i
  prijava ostaju održavateljeva deployment provjera, ne ovdje potvrđeni rezultat.
- Novi nedostajući Auth dokument stvara se u zadnjem deklariranom sloju, ne
  read-only bazi. Postojeće datoteke/SSO tajne ostaju. Auth/file i OIDC flow:
  **16 testova / 208 tvrdnji**. Samostalni updater je **1.0.7**.
- Aplikacija: **262 testa / 6.086 tvrdnji**, tri platform skipa. Deployment:
  **6 / 26**. Prošle su obje statičke analize, ciljani stil i auditi dokumentacije/
  prijevoda/PHPDoc prisutnosti. Puni SQLite browser/API/performance: **80/80**,
  **17,4 minute**, izlaz 0 i završna poruka uspjeha pokretača.
- HFC ostaje klasičan/aktualan: HTTP 200, 14 izvršenih/0 pending migracija i
  isti privatni Auth SHA-256. Framework nije mijenjan. Odvojene EN/HR upute prate
  novo stanje. Nije bilo HFC backupa, remote updatea niti taga/pusha/releasea.

Dokazi su u `build/a4/` i `output/playwright/a4-immutable-setup.png`. Privremeni
stackovi/volumeni i build staging uklanjaju se nakon provjera; lokalni razvojni
image ostaje. A5 je bio sljedeća lokalna ispravka i zabilježen je ispod. Odobrenje
izdanja, provjera objavljenih paketa/imagea i ranjivosti te stvarni HTTPS/SSO
deployment ostaju zasebne provjere pri objavi i isporuci.

## A5 dovršen lokalno — 12. listopada 2026.

A1–A5 dovršeni su za razvojni kandidat; odobrenje izdanja još je potrebno.

### Promjene

- Označeni PHP komentari/PHPDoc aplikacije i svih 18 javnih modula uređeni su
  u odvojene EN pa HR blokove. Mehaničke izmjene zahtijevaju jednake sažetke
  izvršivih tokena i očuvane potpise anotacija. Uređeni su i JavaScript te
  CI/shell/config komentari: 33 datoteke / 239 komentara, uz JavaScript parser
  koji je potvrdio isti izvršivi kod.
- Dodani su `src/Tools/BilingualCommentRules.php` i alat održavanja
  `scripts/normalize_bilingual_comments.php` koji zadano ne piše. Proširen je
  `scripts/audit_bilingual_phpdoc.php`: odbija pomiješane/obrnute označene komentare
  izvora/testova/predložaka, čuva opseg dokumentacije produkcijskih metoda,
  uključuje Pristupačnost i uklanja proizvoljno ograničenje duljine PHPDoca.
- `scripts/audit_module_documentation.php` proširen je na Pristupačnost,
  README/datotečne poveznice, sidra naslova, sintaksu cjelovitih PHP/JSON primjera
  i uređene dvojezične JSON opise. Tri nove regresijske grupe pokrivaju alate.
  [Upute provjera](documentation-quality_hr.md) objašnjavaju ograničenja.
- Ispravljene su stare poveznice/nepotpuni primjeri te pojašnjene konfiguracijske
  putanje i override slojevi generičke aplikacije. Dodani su cjeloviti primjeri
  pokretanja/rasporeda Pristupačnosti i Auth API ključa bez GUI-ja. Uvodni servisi
  više ne zapisuju tijela zahtjeva. Povijesna izdanja imaju napomene i zasebne grupe
  u README-ju/indeksu. Aktualne upute prate dovršene ispravke audita.

### Provjere

- PHP sintaksa: **1.245 datoteka / nula grešaka**. Auditi komentara,
  dokumentacije/primjera/poveznica i prijevoda: **nula problema**. Završna probna
  normalizacija: **nula izmjena**. Arhivirana završna PHP serija čuva 679 jednakih
  sažetaka izvršivog koda; ranije zaštićene serije također su provjerene prije upisa.
- Novi regresijski testovi: **10 testova / 64 tvrdnje**. Cijela aplikacija:
  **272 testa / 6.150 tvrdnji**, ista tri platform preskakanja. Svih 18 modula:
  **1.113 testova / 9.576 tvrdnji**, bez preskakanja. Podešeni produkcijski PHPStan
  i stil prolaze za sve module; prolaze produkcijska/razvojna analiza aplikacije
  i stil izvora/testova/alata.
- Puni izolirani SQLite browser/API/performance: **80/80**, **18,1 minuta**,
  izlaz 0 i završna poruka uspjeha. Naknadno su resursima mijenjani samo komentari,
  uz potvrdu parsera. Auth CLI primjer pokrenut je na oglednoj instalaciji, a
  njegov je ključ autentificirao očekivanog administratora. Token nije ispisan
  u izvještaje niti sačuvan. Vlastita testna instalacija uklonjena je bez praćenja
  vendor/module symlinkova; izvještaji ostaju u `build/a5/`.
- HFC početna/prijava: HTTP 200; **14 izvršenih / nula pending migracija**;
  isti sažetak privatne Auth konfiguracije. Nije bilo HFC backupa, promjene sheme/
  Frameworka, udaljene nadogradnje, commita/pusha/taga/releasea.

**Sljedeće:** odobrenje izdanja, konačni objavljeni paketi/image, release CI i
deployment provjere. Ranija matrica baza/A4 image zadržavaju datume; A5 ne tvrdi
novu provjeru produkcijskih baza ni skeniranje ranjivosti objavljenog imagea.
