# Parametri frameworka i konfiguracija aplikacije

[Engleska verzija](configuration_en.md)

Simbioza 0.3.0 polazna je verzija za novu instalaciju: nova baza i aktualan raspored
direktorija. Nadogradnja s 0.2.x i stare Simbioza backup arhive nisu podržane.
Za buduće verzije ostaju samonadogradnja updatera i rollback.
HeartPhrame framework koristi se bez izmjena.

## Centralni parametri

Privatni `framework.php` u korijenu aplikacije učitava se prije HTTP i CLI
konfiguracije. Polazni primjer je [framework.php.dist](../framework.php.dist):

```php
return [
    'HPH_APP_PATH' => __DIR__,
    'HPH_CONFIG_PATH' => __DIR__ . '/config',
    'HPH_CONFIG_OVERRIDE_PATHS' => '',
];
```

- `HPH_APP_PATH`: apsolutni korijen aplikacije.
- `HPH_CONFIG_PATH`: potpuni konfiguracijski direktorij, ne mapa samo jednog modula.
- `HPH_CONFIG_OVERRIDE_PATHS`: dodatni apsolutni direktoriji odvojeni platformskim
  `PATH_SEPARATOR`; kasniji slojevi imaju viši prioritet.

Neprazne procesne varijable imaju prednost. HTTP, CLI i workeri moraju koristiti
iste parametre. Prije odabira vanjskog direktorija pripremite potpunu konfiguraciju,
uključujući obavezne aplikacijske PHP datoteke i privatne datoteke modula.
Direktorij se ne mora zvati `config`.

`App\Configuration\ConfigurationPaths` koristi postojeći resolver frameworka.
`directories()` vraća uređene slojeve bez duplikata; `effectiveFile()` odabire
cijeli podatkovni dokument; `writableFile()` provjerava cilj u posljednjem sloju.
Ne stvara datoteke i ne spaja njihov sadržaj. PHP konfiguracija slijedi postojeća
pravila frameworka za spajanje ključeva i zamjenu lista.

Nevaljan ili nečitljiv viši sloj izričito se odbija. Zapis nikada tiho ne prelazi
u niži sloj. Konfiguracija samo za čitanje radi bez stvaranja lockova; spremanje
zahtijeva prava atomske zamjene. Deklarirani korijen smije biti symlink, ali storeovi
odbijaju symlink modula/datoteke/locka kao cilj zapisa. Koristite apsolutne putanje,
a privatne postavke držite izvan Gita i javnog web direktorija.

## Vlasništvo konfiguracije

| Aktivna putanja relativna na `HPH_CONFIG_PATH` | Vlasnik i format |
| --- | --- |
| `app.php`, `installation.php`, `setup.php`, `env.php`, `languages.php`, `modules.php` | Aplikacijska/zajednička PHP konfiguracija |
| `database.php` | Zajednička Framework/ORM veza; ostaje u korijenu |
| `auth/auth.json` | Prijava, atributi korisnika i imenovani SSO profili |
| `email/email.json` | SMTP, pošiljatelj, red slanja i opcionalni GUI/test |
| `editor-html/editor-html.json` | Instalacijske postavke generičkog Editora |
| `editor-html/external-scripts.json` | Definicije vanjskih skripti i lokalno administratorsko odobrenje; stranice ih odabiru zasebno |
| `workspace/workspace.json` | Postavke Simbioza Workspacea i globalne naslovnice |
| `calendar/calendar.php` | Instalacijska politika Kalendara |
| `backup/backup.php`, `backup/backup-providers.php` | Pohrana backupa i opcionalna registracija host providera |
| `api/api.php` | Limiti API-ja, CORS, uvjetni zapis i politika webhook workera |
| `workspace-search/workspace-search.php` | Veličina stranice rezultata, isječci i osvježavanje izvedenog indeksa |
| `confluence-import/confluence-import.php` | Ograničenja arhiva, privatni staging i politika obrade importa |
| `menu/menu.php`, `menu/top.json`, `menu/settings.json` | Globalni izbornici i Menu opcije |
| `theme/theme.php`, `theme/themes.json`, `theme/settings.json` | Sistemske Theme opcije i odabir |

Paketne `config/services.php`, API/backup deklaracije, predlošci, prijevodi i zadane
postavke dio su koda/resursa, ne privatne instalacijske konfiguracije.

## Instalacija, moduli i jezici

Installer i pokretanje koriste iste slojeve. Odabir modula nalazi se u
`modules.php`; radnje životnog ciklusa koriste `.modules.lock` u posljednjem
sloju. Postojeći GUI čita i piše tu datoteku. Composer radnje opcionalnih modula,
provjere ovisnosti i pokretanje workera ostaju dio aplikacije.

Upravljanje jezicima koristi `languages.php` i `.languages.lock` u posljednjem
sloju. Instalirani prijevodi su u `data/languages/catalogs/<jezik>.json`;
aplikacijski/modulski `lang/` sadrži read-only paketne prijevode. Zastavice su u
`data/languages/flags`, predlošci u `data/language-packs`, a praćenje revizija u
`data/languages/repository-versions.json`. Ažuriranje isključenog jezika ga ne
uključuje; posljednji uključeni jezik ne može se isključiti.
Podatkovne GUI radnje za jezike ne trebaju privilegirani helper za pakete.
Backup postavki aplikacije uključuje registar, instalirane kataloge,
zastavice i revizije; vidi [uputu za lokalizaciju](localization_hr.md).

Dijagnostika postavljanja prikazuje stvarne putanje, vlasnika/grupu i prava, ne
tajne vrijednosti. Čitanje provjerava efektivne datoteke; zapis posljednji sloj,
prava zamjene i lockove. Izolirani worker koristi iste parametre. Invalidacija PHP
cachea čini spremljene promjene vidljivima sljedećim HTTP i CLI zahtjevima.

## JSON storeovi i opcionalni GUI

Auth odabire posljednji postojeći cijeli `auth/auth.json`. Email, Editor i
Workspace spajaju objekte kroz slojeve, a liste zamjenjuju, uključujući prazne.
GUI pod modulskim lockom ponovno čita postavke, čuva nepovezane ključeve i
atomski objavljuje u posljednji deklarirani sloj.

Generički Auth, Email i Editor mogu se podesiti bez Simbiozina GUI-ja. Email i
Editor podržavaju dokumentirani `gui.enabled=false`; Email odvojeno ima
`gui.test_enabled`. Workeri i sadržajne usluge ostaju uporabivi. SSO provjera
opcionalna je u samostalnim Auth projektima; Simbioza inicijalizira
`login.require_provider_verification=true`.

Kalendar, Workspace, Menu i Theme zadržavaju postojeći GUI. Ispravno ručno uređene
postavke uvažavaju se. Isključivanje svakog modulskog GUI-ja nije zahtjev.

### Auth

- `login`: registracija, administratorski break-glass i trajanje prijave.
- `providers.<protokol>.enabled`: uključivanje protokola.
- `providers.<protokol>.profiles.<stabilni-ključ>`: više profila, svaki s vlastitim
  uključivanjem, vezom `config` i mapiranjem atributa `mapping`.
- `user_attributes`: definicije sinkronizirane s projekcijom u bazi. Uklanjanje
  definicije uklanja njezine vrijednosti i izvedena automatska mapiranja grupa,
  ne korisnike ni grupe.

Korisnici, grupe, članstva, veze vanjskih identiteta i stabilni ID-ovi profila
ostaju u bazi. Ključ profila ostaje stabilan; naziv se mijenja kroz `profile_name`.
Tajne providera su konfiguracija, ne duplicirane postavke u bazi.

### Editor i Workspace

Editor radi samostalno. Uključeni Workspace pri izvršavanju primjenjuje rute,
objavu, sadržaj i vlasništvo bez trajnog zapisivanja nametnutih vrijednosti u
Editor JSON. Bez Workspacea ponovno vrijede samostalne postavke.
Globalne naslovnice su u Workspace JSON-u, osobni izbori u bazi. Dokumenti,
inačice, ACL, kalendari, taskovi, komentari i teme su podatci.

JavaScript trećih strana pripada zasebnom registru Editora, ne skriptama aplikacije.
U **Postavke → HTML Editor → Vanjske skripte** administrator može odobriti/opozvati
skriptu, promijeniti naziv i kroz **Gdje se koristi?** pregledati objave, nacrte i
opcionalnu povijest. Odobrenje nikada ne učitava skriptu na svakoj stranici.
Pogledajte [upute registra](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-html-editor/-/blob/0.3.0/docs/external-scripts_hr.md).

### Moduli bez zasebne politike

Audit koristi aplikacijsku vremensku zonu i log postavke. Comment, Task,
Notification i Accessibility ne trebaju prazne mape ni predloške. Komentari,
povijest izvršavanja, inbox/pročitano i mail preferencije ostaju u bazi;
Accessibility postavke u lokalnoj pohrani preglednika. SMTP pripada isključivo
Email JSON-u. Ostaju potrebne migracije, ovlasti i domenske provjere.

## Podatci su odvojeni od konfiguracije

Konfiguracijske putanje ne sele sadržaj. Filesystem HTML, povijest privitaka,
vlasnički izbornici i privatne teme slijede [raspored područja/stranica](workspace-data_hr.md).
HTML u bazi ostaje u bazi. Samostalni/zajednički Editor podatci i sistemski
Theme resursi ostaju u pripadajućim zajedničkim podatkovnim korijenima.

Globalni Menu JSON dokumenti odabiru posljednji postojeći cijeli dokument;
Theme radi jednako, uz invalidaciju CSS-a hashom sadržaja. PHP politike spajaju
se po ključu. GUI ostaje preporučeni editor, pa Menu i Theme nemaju nove
`.dist` primjere. Paketne zadane vrijednosti samo se čitaju, GUI ih ne mijenja.

## HTTP sigurnosna politika

`HPH_CONFIG_PATH/middleware.php` uključuje neizmijenjene Frameworkove slojeve
pouzdanih proxyja, sigurnosnih zaglavlja i obrade grešaka prije sesije/CSRF-a.
Sigurnosni/error par treba ostati izvan request metricsa i ruta da zabrane/greške
dobiju ista zaglavlja. `app.php` dodaje CSP `base-uri 'self'; object-src 'none';
frame-ancestors 'self'`; ne predstavlja potpunu `script-src` zaštitu.
Objavljene vanjske skripte i dalje trebaju lokalno odobrenje registra i odabir
po stranici. Zadana sesija koristi Secure, HttpOnly i SameSite=Lax; pregledani
instalacijski override može ih promijeniti. Proxyje podesiti ispravno, a HSTS
koristiti samo na deploymentu koji pouzdano koristi HTTPS.

[Audit prije releasea](pre-release-audit-0.3.0_hr.md) bilježi dovršene ispravke
Autha, upita, spremišta jezika i nepromjenjive isporuke.
[Docker upute](docker_hr.md) opisuju image/volumene i GUI/CLI operacije namjerno
blokirane u nepromjenjivoj isporuci. `HPH_CONFIG_PATH` sam nije prekidač tog
načina rada. Objava još zahtijeva odobrenje i provjeru objavljenih paketa i imagea.

## Primjeri

Aktualni aplikacijski primjeri u `config/` odgovaraju paketnim primjerima:

| Primjer | Kopirati u |
| --- | --- |
| `auth.json.dist` | `HPH_CONFIG_PATH/auth/auth.json` |
| `email.json.dist` | `HPH_CONFIG_PATH/email/email.json` |
| `editor-html.json.dist` | `HPH_CONFIG_PATH/editor-html/editor-html.json` |
| `external-scripts.json.dist` | `HPH_CONFIG_PATH/editor-html/external-scripts.json` |
| `workspace.json.dist` | `HPH_CONFIG_PATH/workspace/workspace.json` |
| `calendar.php.dist` | `HPH_CONFIG_PATH/calendar/calendar.php` |
| `backup.php.dist` | `HPH_CONFIG_PATH/backup/backup.php` |
| `api.php.dist` | `HPH_CONFIG_PATH/api/api.php` |

Za novu instalaciju kopirajte samo potrebne primjere; nikada preko aktivnih
privatnih postavki. Ravne `.dist` datoteke ne učitavaju se. JSON ne dopušta
komentare: `_documentation.en` i `_documentation.hr` inertna su objašnjenja, ne
opcije. Auth ih uklanja pri validaciji; ostali storeovi bezopasno ih čuvaju.
Primjer skripti koristi jednako inertne blokove `_description_en` i `_description_hr`;
obje ogledne definicije su na čekanju, nisu odobrene.
Koristite stvarne JSON boolean/cjelobrojne vrijednosti. Placeholdere mijenjajte
samo u privatnoj datoteci. PHP primjeri frameworka/baze/env/modula ostaju valjani.

## Backup i prava

Aktualni backup koristi format arhive 2 i točne sheme providera. Potpuna arhiva
mora sadržavati sve registrirane providere za potpuni povrat. Metapodatci uključuju
javnu adresu izvornog sitea; povrat na drugi site mapira identitete stranica i
privitaka te prijavljuje nerazriješene linkove. Ne prenosi izvornu vezu baze ni
instalacijske putanje. Povrat konfiguracije koristi modulske provjere, lockove i
ciljne slojeve; rollback čuva prethodno stanje do uspjeha.

Privatne mape dijelite s runtime grupom (`2770`, datoteke/lockovi `0660`).
FPM mora moći zamijeniti GUI datoteke; vlasništvo deploy računa u sticky mapi
ne dopušta zamjenu samo zato što je datoteka group-writable. Koristite opisani
FPM setup. Izvršni PHP u vlasništvu releasea općenito ne smije biti web-writable;
GUI datoteka `modules.php` izričita je iznimka. Updater čuva privatne datoteke i
vanjske slojeve. Pokretanje ne seli stare konfiguracije i ne izvozi stare baze.

[Kontrolni popis 0.3.0](initial-release-0.3.0_hr.md) prikazuje stanje provjera;
stari P1–P10 rezultati nisu potvrda objave nove polazne verzije.
