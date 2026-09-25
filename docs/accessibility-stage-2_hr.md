# Pristupačnost — drugi lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-2_en.md).

Ovaj presjek nastavlja etape 1–2. Ne predstavlja dovršen accessibility modul ni potvrdu sukladnosti cijele aplikacije.

## Popravci

### Dinamička konfiguracija nakon vraćanja kopije

Potvrđeni uzrok bio je u strukturiranom vraćanju konfiguracije: izračunate vrijednosti zamijenile su dinamički `config/app.php`. CLI je zatim mijenjao stanje modula, ali aplikacija više nije čitala tu datoteku.

Backup provider sada podržava lokalno definirana odredišta i mapiranje ključeva. Simbioza vraća instalacijske postavke u `config/installation.php`, a uključene module u `data/config/modules.php`; `config/app.php` ostaje netaknut. Prenosive opcije sesije čitaju se kroz ograničen popis ključeva. Jezici se i dalje provjeravaju prema stvarno instaliranim paketima, a rezervni jezik prati zadani jezik aplikacije.

Arhiva ne određuje putanje i ne donosi izvršivi kod. Sačuvane su neodabrane lokalne vrijednosti i grupni povrat pri pogrešci. Format zapisa ostaje kompatibilan sa starim kopijama. Test pokriva i tekst koji izgleda kao PHP kod, ali mora ostati običan podatak.

Popravak sprječava novo pretvaranje konfiguracije u statičku. Ne popravlja automatski instalaciju kojoj je ranije vraćanje već prepisalo `app.php`; takvu instalaciju treba zasebno provjeriti i obnoviti iz pouzdanog izdanja. Na apps-testu ništa nije mijenjano.

Ponovljeni puni test otkrio je i zaseban povremeni problem: CLI je pravilno spremio isključivanje teme, ali je web proces još čitao staro stanje iz OPcachea. Lokalni HTTP pokus i regresijski test s prethodno popunjenom predmemorijom reproducirali su uzrok. Bootstrap sada ciljano poništava samo promjenjive datoteke instalacije, jezika i modula prije čitanja, a spremište modula radi isto za svoja čitanja. Ne isključuje se predmemorija aplikacijskog koda. Test prolazi i kada je automatska provjera vremenskih oznaka potpuno isključena; nema umjetnog čekanja ni ponavljanja zahtjeva radi prikrivanja problema.

### Uski prijavljeni prikaz

Skriveni opis broja obavijesti bio je izvan granica svoje kontrole. Na prikazu širine 320 CSS piksela uzrokovao je širinu dokumenta od 353 piksela. Kontrola sada zadržava opis unutar svojih granica; skraćuje se samo korisničko ime, dok broj obavijesti ostaje vidljiv.

U provjerenom Chromiumu dokument je nakon popravka širok 305 piksela, jednako raspoloživoj širini uz klasičnu pomičnu traku. Nije dodano opće skrivanje vodoravnog prelijevanja. Potpuno korisničko ime ostaje dostupno pomoćnim tehnologijama.

Zaseban pregled bez učitanog modula teme otkrio je kontrast 2,28:1 na datumima susjednog mjeseca. Uzrok je bila dodatna prozirnost cijelog gumba. Uklonjena je u oba mobilna CSS pravila; ponovljeni automatski pregled kalendara na 320 piksela više ne prijavljuje tu povredu.

### Obrasci i dinamičke kontrole

- Calendar: povezano još 17 vidljivih oznaka u glavnom i administratorskom obrascu; imenovana prava čitanja/pisanja, izbor grupe, uklanjanje retka i administratorska pretraga.
- Calendar i Workspace: udaljeni birači najavljuju učitavanje, prazan rezultat i pogrešku; pretraga područja/stranica ima izričit pristupačni naziv.
- Menu: imenovane strelice za pomicanje/uvlačenje te kontrole aktivacije, oznake, odredišta, URL-a, upita i uklanjanja u običnim i posebnim menijima. Koriste se postojeći hrvatski ključevi prevedeni u svih šest jezičnih paketa; dodani su i samostalnom katalogu modula gdje je trebalo.
- Workspace: povezane oznake za slug, vrstu stavke, redoslijed, dokument, internu rutu i ciljni URL. Identifikatori zadržavaju ID čvora, a automatski dokument prikazuje se kao imenovani izlazni podatak.

Nije promijenjen poslani oblik podataka, ACL logika, sadržaj dokumenata ni Confluence pretvorba. Nema novih migracija ni mrežnih resursa za krajnjeg korisnika.

## Provjere

| Provjera | Rezultat |
| --- | --- |
| Backup, puni lokalni `composer on-commit` | Prolaz; 45 testova, 146 provjera |
| Menu, puni lokalni `composer on-commit` | Prolaz; 53 testa, 344 provjere |
| Calendar, puni lokalni `composer on-commit` | Prolaz; 69 testova, 633 provjere |
| Auth, puni lokalni `composer on-commit` | Prolaz; 79 testova, 503 provjere |
| Theme, puni lokalni `composer on-commit` | Prolaz; 35 testova, 754 provjere |
| Workspace, puni lokalni `composer on-commit` | Prolaz; 109 testova, 876 provjera; postojeća PHP 8.5 deprecijacija u neizmijenjenom ORM-u |
| HFClean, puni lokalni `composer on-commit` | Prolaz; 95 testova, 4241 provjera; 1 postojeći preskočeni test vlasništva koji zahtijeva root i 1 postojeći nedovršeni test konstrukcije aplikacije |
| Komentari, dokumentacija, katalozi prijevoda | Bez prijavljenih problema |
| Puni lokalni E2E | Završni prolaz nakon svih popravaka: 76/76, izlazni kod 0, približno 7,5 minuta. Ranije ponavljanje 75/76 otkrilo je OPcache problem opisan iznad. |
| CLI → HTTP uz isključenu OPcache provjeru vremenskih oznaka | Prošlo 20 uzastopnih promjena (10 ciklusa uključi/isključi); svaki prvi sljedeći HTTP zahtjev prikazao je ispravno stanje |

Prošireni E2E test nakon stvarnog vraćanja pune kopije potvrđuje da je izvor `app.php` nepromijenjen. Isključuje i uključuje temu kroz GUI, provjerava nestanak/povrat zaglavlja i stavke postavki, zatim ponavlja promjenu kroz CLI. Bez učitane teme provjerava Tab/Enter prečac i fokus na glavnom sadržaju. Mobilni test provjerava širinu dokumenta, granice oznake obavijesti i tipkovničko otvaranje/zatvaranje korisničkog menija u svijetlom i tamnom prikazu.

Playwrightom i axe-core 4.10.3 provjereni su kalendarska administracija, otvoreni dijalog kalendara, dodani korisnički/grupni retci u tamnoj temi te obični editor menija. Na tim stanjima nema automatskih povreda odabranih WCAG A/AA pravila. To ne isključuje nalaze koji zahtijevaju ručni pregled niti potvrđuje sve prikaze ili sve jezike.

Na zasebnoj izoliranoj instalaciji nakon kopije provjeren je i stvarno isključen modul teme: njegova administratorska ruta vraća 404, zaglavlje teme nije prisutno, a kalendar i kalendarska administracija prolaze isti automatski pregled nakon popravka kontrasta datuma. Testno stanje teme vraća se nakon provjere.

Provjereno je i stvarno CLI isključivanje hrvatskog jezika nakon vraćanja kopije: idući zahtjev prelazi na engleski, a nakon ponovnog uključivanja hrvatski se može odabrati i dokument ima `lang="hr"`. Na kraju su oba jezika i tema ponovno uključeni.

Završni E2E koristi zadržanu izoliranu instalaciju `e2e-all-0e29929c`. Na njoj je zasebno pokrenut lokalni HTTP poslužitelj s `opcache.validate_timestamps=0` i `opcache.file_update_protection=0`: deset CLI ciklusa promjene teme provjereno je odmah na stvarnoj stranici prijave, bez čekanja ili osvježavanja radi ponovnog pokušaja. Tema je potom ponovno uključena, a privremeni poslužitelj zaustavljen. To provjerava problem predmemorije, ali ne zamjenjuje još neprovedenu matricu stvarnih FPM/ne-FPM nadogradnji.

Lokalna snimka uskog prikaza: `output/playwright/accessibility-stage-2-narrow-dark.png`.

## Preostalo i sljedeći korak

1. Dovršiti semantičke i tipkovničke provjere preostalih Auth obrazaca, posebnih menija i svih stanja udaljenih birača. Provjeriti da naziv kontrole opisuje i njezinu svrhu nakon odabira vrijednosti, ne samo trenutačnu vrijednost.
2. Editor: nadograditi postojeći provjerivač sadržaja u `heartphrame-module-editor-html/views/editor/index.php`, ne uvoditi drugi paralelni. Već postoje `checkContentHeadings`, `checkContentImages`, `checkContentTables` i provjera poveznica. Trenutačno se prazan `alt` uvijek smatra pogreškom, provjera naslova očekuje H1 unutar sadržaja bez konteksta rasporeda, a tablici je dovoljan bilo koji `th`. Potrebno je razlikovati namjerno dekorativne slike, uzeti u obzir naslov koji već prikazuje aplikacija i provjeriti odnose zaglavlja tablica. Generatori i poruke moraju ostati usklađeni sa sanitizerom, bez izmišljanja opisa ili blokiranja ispravnog sadržaja. Dodati ciljane provjere poznatih Confluence pretvorbi bez masovne prerade uvezenih dokumenata.
3. Zatim izraditi samostalan modul osobnih prilagodbi s lokalnim fontovima i licencama, višejezičnim panelom i jednim administratorskim prekidačem.
4. Prije izdanja: zasebne FPM/ne-FPM provjere, stvarni čitač zaslona, povećanje, prisilne boje, jezici te pregled svih ruta. Za Backup i Simbiozu potrebno je usklađeno izdanje; nije dovoljno promijeniti samo definiciju aplikacijskog providera uz staru verziju modula.

Framework i zabranjeni repozitoriji nisu mijenjani. Nema commita, pusha, izdanja ni nadogradnje postojećih instalacija. Razvojni i integracijski testovi koriste izolirane lokalne podatke.

Instalirana kopija Backup modula u `HFClean/vendor` još nema novo mapiranje; nije ručno zakrpana. Zajednički razvojni kod provjeren je preko `--local` integracijske instalacije. Za upotrebu u postojećem HFC-u ili FPMSimbiozi prvo treba provesti usklađenu instalaciju novih verzija, a ne zamijeniti samo aplikacijsku konfiguraciju.

Mjerila: [prilagodba uskom prikazu](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) i [imenovanje kontrola](https://www.w3.org/WAI/tutorials/forms/labels/).
