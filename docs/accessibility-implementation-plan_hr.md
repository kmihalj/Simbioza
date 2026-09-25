# Plan pristupačnosti i modula heartphrame-module-accessibility

Datum plana: 23. rujna 2026. Ažurirano: 25. rujna 2026. Status: lokalni modul i integracija izrađeni; završni audit pristupačnosti ostaje otvoren.

[Engleska verzija](accessibility-implementation-plan_en.md)

Ovo je nastavna točka za buduće razvojne sesije. Plan se temelji na brzom pregledu HFC-a i odabranog koda uz lokalnu oznaku Simbioze `0.1.89`. Potvrđeni nalazi navedeni su ovdje; dodatni lokalni izvještaj nalazi se u `output/playwright/audit_pristupacnosti_hr.md`. Početni pregled nije obuhvatio sve prijavljene tokove niti predstavlja potvrdu sukladnosti.

## 1. Cilj i nepromjenjiva pravila

- Cilj je pristupačna osnovna aplikacija i samostalan, višekratno upotrebljiv modul osobnih prilagodbi za aplikacije na HeartPhrameu. Mjerilo razvoja je WCAG 2.2 AA, dopunjeno provjerama stvarne upotrebljivosti; ne obećavati potpunu sukladnost prije završetka provjera.
- Osnovna semantika, tipkovnica, fokus, čitljivost i kontrast moraju raditi bez novog modula i bez modula teme. Modul nije zamjena za popravak postojećeg koda.
- Repozitoriji `heartphrame-framework`, `heartphrame` i `heartphrame-module-demo` ostaju samo za čitanje. Predložak demo modula smije se proučiti i prema njemu izraditi novi modul, ali se izvornik ne mijenja. Ne zakrpavati framework ni preko direktorija `vendor`.
- Mijenjati samo aplikacijski kod i module kojima upravljamo. Ako problem traži promjenu frameworka, dokumentirati ograničenje i pripremiti prijedlog njegovu održavatelju; ne zaobilaziti zabranu.
- Administrator ima samo uključivanje/isključivanje novog modula. Posjetitelj u panelu bira svoje prilagodbe. Ne uvoditi dva neovisna prekidača koji prikazuju proturječno stanje.
- Svi vidljivi tekstovi, pristupačne oznake i dinamičke najave moraju biti prevedeni. Izvorni aplikacijski tekstovi ostaju hrvatski.
- Ne mijenjati sadržaj dokumenata, korisničke teme, redoslijed postojećih izbornika, privatnu konfiguraciju ili poslovne podatke radi prilagodbe prikaza.
- HFC ostaje ne-FPM testna instalacija, FPMSimbioza FPM instalacija. apps-test se ne nadograđuje i na njemu se ne isključuju moduli bez nove izričite ovlasti korisnika.
- Korisnik je 24. rujna 2026. odobrio lokalni razvoj po etapama, a 25. rujna izričito zatražio izdanje. Nadogradnja apps-testa i dalje ostaje korisnikova zasebna radnja. Nove migracije uvoditi samo ako su potrebne uz sigurnosnu kopiju i zasebnu provjeru.
- Novi sadržaj izrađen u editoru mora imati pristupačnu semantiku u samom spremljenom HTML-u, ne samo nakon naknadne intervencije skripte. Autor daje sadržajne opise; aplikacija osigurava strukturu i upozorenja.
- Očuvati postojeći Confluence import. Poboljšavati samo poznate pretvorbe makroa u izvorne Simbiozine komponente, uz regresijske testove. Ne mijenjati proizvoljan uvezeni sadržaj, ne izmišljati opise i ne dodavati redundantni ARIA zapis nativnim elementima.

## 2. Redoslijed i izlazni uvjeti

Temeljne dorade etapa 0–2, samostalan modul, osobni panel i Simbiozina integracija imaju lokalne implementacije i provjere. Ručni audit svih postupaka i autorskih sadržaja, dio provjera životnog ciklusa te objava izdanja još nisu zaključeni. Prvi razvojni presjek opisan je u [izvještaju prve etape](accessibility-stage-1_hr.md). Popis od 80 predložaka nije potvrda da su svi potpuno pregledani ili popravljeni.

[Drugi razvojni presjek](accessibility-stage-2_hr.md) zatvara nalaze prelijevanja i vraćanja konfiguracije, dopunjuje obrasce Calendar/Menu/Workspace te popravlja zastarjelo web stanje nakon CLI promjena. Njegov završni E2E prolazi 76/76; dodatno prolazi 20 uzastopnih CLI → HTTP promjena uz isključenu OPcache provjeru vremenskih oznaka. [Treći presjek](accessibility-stage-3_hr.md) dodaje autorske radnje za slike i semantičke provjere, usklađuje ovisnosti svih modula, dovršava test konstrukcije i izvodi root test vlasništva u izoliranom Linuxu. Etape 1–2 još nisu zaključene: preostaju ostali obrasci, pokrivenost generatora te ručna provjera cjelokupnih tokova.

[Četvrti presjek](accessibility-stage-4_hr.md) popravlja odabir vlasnika API ključa, filtre Audita i pretraživanja, glavne orijentire i naslove izbornika. Proširuje provjere tipkovnice objavljenog sadržaja te čuva lokalne veze zaglavlja pri sigurnoj pretvorbi Confluence tablica. Provjere obuhvaćaju svijetli/tamni prikaz i prikaz bez Teme. Osobni panel još nije započet.

[Peti presjek](accessibility-stage-5_hr.md) uređuje dijalog napretka Setupa, poruke i fokus Backupa, dinamična Confluence mapiranja, komentare, zadatke i kontrole obavijesti. Puni izolirani E2E prolazi 77/77; završna dorada Setupa dodatno prolazi izdvojeni test. Provjerena su i otvorena stanja, bez Teme te na uskim zaslonima. To nije zamjena za provjeru čitačima zaslona i stvarnim povećanjem.

[Šesti presjek](accessibility-stage-6_hr.md) uvodi zaseban razvojni paket osobnih prilagodbi, lokalne resurse, središnje uključivanje i izričitu vezu s rasporedom Simbioze. Na malom broju lokalnih zaslona provjereni su tipkovnica, oporavak od oštećenog spremišta i prelamanje pri 320 CSS piksela s 200 % veličine teksta; stvarno povećanje preglednika na 400 % još nije potvrđeno. Fontovi i puna integracija nisu završeni.

[Sedmi presjek](accessibility-stage-7_hr.md) potvrđuje samostalan rad modula u minimalnoj HeartPhrame aplikaciji. Nakon tog presjeka dodani su lokalni licencirani fontovi, četiri taba, tri palete, visokokontrastni prikazi, samostalni stilovi widgeta i opcionalna veza s Temom. Modul se sada priprema za prvo izdanje; to nije završetak audita niti tvrdnja o WCAG sukladnosti. Aktualna ograničenja navedena su u dokumentaciji samog modula i [bilješkama izdanja](release-0.1.90_hr.md).

| Etapa | Rad | Uvjet završetka |
| --- | --- | --- |
| 0 | Potvrda stanja, potpuni popis prikaza i reproduciranje nalaza | Zabilježene verzije, ovlasti, matrica provjera i testovi koji reproduciraju probleme |
| 1 | Osnovni raspored, tema, izbornici i poruke | Osnovna navigacija i zajedničke komponente rade i bez novog modula |
| 2 | Kalendari, editor, područja i ostali prikazi | Potvrđeni problemi popravljeni u modulima kojima pripadaju; preostali nalazi evidentirani |
| 3 | Ugovor integracije i temelj novog modula | Modul radi u minimalnoj drugoj aplikaciji bez Simbiozinih modula i bez izmjene frameworka |
| 4 | Osobne prilagodbe i pristupačan panel | Sve funkcije prve verzije rade tipkovnicom, uz reset i očuvanje sadržaja |
| 5 | Simbioza, životni ciklus modula, prijevodi i dokumentacija | GUI/CLI i jezici dosljedni; dokumentacija i komentari dovršeni |
| 6 | Integracijske i ručne provjere | Prošli lokalni testovi i CI; dokumentirani ručni rezultati i ograničenja |
| 7 | Usklađeni release i provjera nadogradnje | Objavljene provjerene verzije i uspješna lokalna nadogradnja iz izdanja |

Dokumentacija i regresijski testovi nastaju uz svaku etapu, ne tek na kraju. Svaka sesija ažurira status, dokaze i sljedeći korak u obje jezične verzije plana.

## 3. Etapa 0 — priprema bez promjene poslovnih podataka

1. Pročitati ovaj plan, pravila repozitorija i aktualni ugovor o ovisnostima modula. Provjeriti stvarne putanje, grane, oznake, lokalne izmjene i postojeći CI. Tuđe izmjene sačuvati.
2. Pronaći dostupan predložak `heartphrame-module-demo` i pročitati ga bez izmjena. Kao dodatne primjere koristiti `ModuleTheme`, `ThemeMenuIntegration`, `ThemeLayoutRenderer` i njihove komentare HR/EN. Ne pretpostavljati nove framework API-je.
3. Napraviti popis ruta, zajedničkih komponenti i stanja: neprijavljen/prijavljen korisnik, administrator, prazni i popunjeni rezultati, pogreške, otvoreni dijalozi, duži prijevodi i dinamički sadržaj.
4. Obuhvatiti sve postojeće prikaze Simbioze i instaliranih modula, uključujući autentikaciju, API postavke, audit, backup, kalendare, komentare, editor, e-poštu, izbornike, obavijesti, ORM postavke, korisnike, zadatke, teme, područja, pretraživanje i uvoz. Modul bez GUI-ja označiti tako, ali provjeriti njegove poruke u GUI-ju drugih modula.
5. Reproducirati nalaze na izoliranim podacima; za prijavljene tokove koristiti odobren testni račun. Nedostupne provjere označiti kao neprovedene, ne kao uspješne.
6. Uvesti početni izvještaj automatiziranih provjera i ciljane regresijske testove. Ne isključivati pravila kako bi rezultat izgledao prolazan.

## 4. Etape 1 i 2 — popravci postojećeg koda

| Vlasnik promjene | Potreban rad i provjera |
| --- | --- |
| Simbioza, `views/layouts/main.php` | Prečac na glavni sadržaj i osnovne orijentire učiniti neovisnima o temi; izbjeći dvostruki prečac kada tema radi. Uskladiti naslov stranice, glavni sadržaj i imenovane regije. Provjeriti fokus uz ljepljivo zaglavlje, mobilnu navigaciju i povećanje prikaza. |
| `heartphrame-module-theme` | Popraviti isporučene kombinacije boja i sva stanja kontrola. U početnom pregledu tamni kalendar imao je kontrast 2,46:1, gumb lokalne prijave 2,64:1, a pomoćni tekst 3,21:1. Uvesti provjeru kontrasta pri uređivanju teme i jasna upozorenja za prilagođene palete, bez tihog prepisivanja korisničke teme. Provjeriti gradijente, fokus u prisilnim bojama i smanjeno kretanje. |
| `heartphrame-module-menu` | Provjeriti tipkovnicu, aktivnu stavku, otvoreno/zatvoreno stanje, padajuće izbornike i povrat fokusa. Nove stavke dodavati na kraj bez promjene postojećeg redoslijeda; skrivanje i vraćanje stavke pri promjeni stanja modula ne smije uništiti prilagodbe. |
| `heartphrame-module-auth` i proizvođači prolaznih poruka | Povezati oznake i pogreške obrazaca; rutinske potvrde najavljivati nenametljivo, hitne pogreške odgovarajuće istaknuti. Zamijeniti jednako automatsko skrivanje svih poruka nakon sedam sekundi ponašanjem koje omogućuje čitanje i naknadni pristup važnoj informaciji. Provjeriti sve kopije komponente, uključujući editor. |
| `heartphrame-module-calendar` | Povezati oznake s poljima; imenovati dijaloge događaja i pretplata; ispravno održavati `aria-pressed` za odabrani prikaz. Provjeriti tipkovnicu, fokus nakon promjene datuma, najave učitavanja, dostupnost punog naziva događaja i prikaz bez razlikovanja boja. Odabir boje kalendara mora dati čitljiv tekst događaja, umjesto uvijek bijelog teksta. |
| `heartphrame-module-editor-html` | Provjeriti alatne trake, dijaloge, privitke, tablice, sadržaj dokumenta, kartice, sklopive blokove, grafikone i druge dinamičke elemente. Dodati provjere autora za nedostajući smisleni alternativni tekst ili oznaku dekorativne slike, naslove, zaglavlja tablica i razumljive poveznice. Ne izmišljati opise slika i ne mijenjati objavljene dokumente automatski. |
| `simbioza-module-workspace` i `simbioza-module-workspace-search` | Provjeriti navigaciju stablom, odabire, rezultate i najave pretraživanja, obrasce, paginaciju te renderirane dokumente. Osobne prilagodbe ne smiju utjecati na ACL, objavu ili prijevode sadržaja. |
| `heartphrame-module-notification`, `heartphrame-module-task`, `heartphrame-module-comment` | Provjeriti čitljivost, stanja koja nisu označena samo bojom, rad tipkovnicom, statusne najave i položaj fokusa nakon dodavanja/uklanjanja stavke. |
| Ostali moduli te instalacija, Setup i nadogradnja | Proći svaki preostali prikaz iz inventara. Posebno provjeriti obrasce postavki, birače datoteka, potvrde brisanja, status nadogradnje, napredak i pogreške. Ne tvrditi da postoji propust dok nije potvrđen. |

Popravci se rade u izvornom repozitoriju komponente, nikad kao trajna ručna zakrpa instalirane kopije u `vendor`. Postojeće migracije ne prepisivati. Ako je nova migracija stvarno potrebna, napraviti je aditivno uz plan sigurnosne kopije i povrata.

## 5. Etapa 3 — ugovor i arhitektura novog modula

- Novi paket: `heartphrame-module-accessibility`; puni Composer naziv, prostor imena i udaljeni repozitorij potvrditi prema predlošku i postojećim konvencijama prije izrade. Autor je Krešimir Mihalj; budući commitovi koriste `kmihalj@srce.hr`.
- Jezgra ne smije zahtijevati Simbiozu, Workspace, Editor ili Theme. Menu i Auth koriste se kao opcionalne integracije. Bez provjerenog mehanizma ovlasti nema javno dostupne administratorske promjene postavki.
- Definirati uski ugovor aplikacijskog rasporeda: uključivanje resursa, mjesto pokretača i panela, korijenski element prilagodbi i identitet instalacije. Postojeći rasporedi integriraju ga izričito; ne oslanjati se na krhko prepisivanje proizvoljnog HTML-a.
- Razdvojiti registraciju modula, čitanje stanja, validaciju osobnih postavki, renderiranje panela, CSS/JS resurse i opcionalne adaptere. Testni primjer druge aplikacije napraviti zasebno, bez uređivanja demo repozitorija.
- Za prvu verziju osobne postavke čuvati u pregledniku; ne uvoditi novu bazu niti sinkronizaciju između uređaja. Ključ spremišta mora uključivati identitet instalacije/osnovnu putanju i verziju sheme kako HFC i FPMSimbioza ne bi dijelili postavke. Dokumentirati da ih dijele korisnici istog profila preglednika za istu instalaciju.
- Validirati dopuštene vrijednosti, preživjeti oštećeno ili nedostupno spremište i primijeniti sigurne zadane postavke. Ne spremati zdravstvene dijagnoze, sadržaj dokumenata ili podatke za praćenje.
- Poštovati CSP, izbjeći udaljene izvršne skripte i proizvoljan korisnički CSS/HTML. Resurse i fontove posluživati lokalno; kompatibilnost točnih licenci provjeriti i priložiti licencne datoteke.
- Ranu primjenu postavki riješiti bez nametanja nesigurnog CSP-a i bez bljeskanja pogrešnog prikaza. Bez JavaScripta osnovna aplikacija ostaje pristupačna.

## 6. Etapa 4 — funkcije prve verzije

| Skupina | Planirana funkcionalnost |
| --- | --- |
| Čitanje i disleksija | Zadani sistemski font, Atkinson Hyperlegible i opcionalni OpenDyslexic nakon provjere licence i znakova; veličina teksta, prored, razmak slova/riječi, širina retka i prikladno poravnanje. Izbor je osobna prilagodba, ne tvrdnja o liječenju ili univerzalno boljem fontu. |
| Slabovidnost | Provjerene kontrastne palete, istaknute poveznice i fokus; prilagodba tekstu i povećanju preglednika bez odrezanih kontrola. Ne koristiti samo `transform: scale()` nad cijelom stranicom. |
| Otežano razlikovanje boja | Palete s provjerenim kontrastom uz tekst/simbol/uzorak u relevantnim komponentama. Simulacije različitog raspoznavanja boja služe testiranju, a nisu univerzalni korisnički filtar. |
| Smanjeno kretanje i koncentracija | Poštovanje sistemskog `prefers-reduced-motion`, mogućnost dodatnog smanjenja animacija, opcionalno ravnalo za čitanje i mirniji prikaz sadržaja. Ni jedna opcija ne skriva ključne kontrole, upozorenja ili sadržaj bez dostupnog izlaza. |
| Sam panel | Lokalizirane oznake i stanja, rad tipkovnicom, vidljiv fokus, pristupačan naziv, predvidivo zatvaranje i povrat fokusa te gumb za reset svih osobnih postavki. Pokretač ne prekriva važne kontrole na mobitelu ili pri povećanju. |

Kombinacije prilagodbi moraju raditi zajedno. CSS ciljano prilagođava komponente i sadržaj: ne smije pokvariti ikone, blokove koda, tablice ili pisma kojima razmicanje znakova ne odgovara. Provjeriti hrvatske dijakritike, latinično proširenje i ćirilični zamjenski font; usmjerenost teksta mora poštovati dokument. Ne obećavati potpunu podršku jeziku/pismu bez provjere.

Mirniji prikaz ne smije stvarati duple identifikatore, zaobilaziti ovlasti ili mijenjati spremljeni dokument. Tuđe ugrađene stranice, PDF-ovi i drugi privitci imaju zasebna ograničenja koja treba dokumentirati.

Izvan prve verzije: vlastiti čitač zaslona, automatsko pisanje opisa slika, OCR i prepravljanje privitaka, zdravstveni profili, univerzalni filtri za vid, sinkronizacija među uređajima i tvrdnja o automatskoj certifikaciji. To ne isključuje provjeru izvorne pristupačnosti sadržaja.

## 7. Etapa 5 — životni ciklus, jezici i dokumentacija

### Uključivanje, instalacija i ažuriranje

- Upotrijebiti postojeći središnji izvor stanja modula. Prekidač u njegovim postavkama, „Moduli i provjere” i CLI moraju završiti istim rezultatom. Nakon potpunog isključivanja ponovno uključivanje ostaje dostupno kroz središnje upravljanje modulima/CLI, ne kroz isključenu rutu.
- Kada nije instaliran ili je isključen, modul ne registrira vlastite javne resurse, panel ili stavke postavki; aplikacija i osnovni popravci ostaju funkcionalni. Uključivanje vraća integraciju bez duplikata i bez gubitka osobnih postavki. Nova stavka prvi put ide na kraj izbornika.
- Deinstalacija ne smije ukloniti poslovne podatke drugih modula. Politiku zadržavanja konfiguracije dokumentirati; stare osobne postavke preglednika ostaju neaktivne i uklanjaju se resetom, a ne obećanjem da ih poslužitelj može izbrisati na svim uređajima.
- Testirati instalaciju, uključivanje, isključivanje, uklanjanje i nadogradnju na izoliranim instalacijama. GUI za pakete koristi postojeći ograničeni FPM mehanizam, a CLI mora raditi za FPM i ne-FPM instalacije. Ne širiti sudo ovlasti ili prava pisanja web procesa radi novog modula.

### Višejezičnost

- Registrirati hrvatske izvorne ključeve i dopuniti sve objavljene jezike u `simbioza-languages`, uključujući oznake za čitače zaslona, validacije, dinamičke poruke i nazive opcija.
- Koristiti postojeći postupak izdvajanja novih ključeva i verzioniranja paketa. Testirati svježu instalaciju i nadogradnju instaliranih paketa; ne oslanjati se na privatne lokalne prijevode.
- Testirati sve ponuđene jezike i dulje prijevode. Namjerno ne miješati jezike u sučelju; dokumentirati uobičajeni rezervni jezik samo za stvarno nedostupan prijevod.

### Obvezna dokumentacija i komentari

- Modul dobiva zasebne `README.md` na engleskom i `README_hr.md` na hrvatskom.
- U `docs/` izraditi jezično odvojene parove za korisničke upute, administraciju/GUI/CLI, instalaciju i integraciju u drugu HeartPhrame aplikaciju, arhitekturu i proširenja, testiranje pristupačnosti te poznata ograničenja. Koristiti nazive `*_en.md` i `*_hr.md`.
- Ažurirati Simbiozine korisničke i instalacijske upute, popis modula, ovisnosti i bilješke izdanja. Engleske snimke prikazuju englesko sučelje, hrvatske hrvatsko; svi primjeri i snimke bez tajni i osobnih podataka.
- **Sav vlastiti kod novog modula te dodani ili promijenjeni kod postojećih modula mora imati dvojezične komentare HR/EN, po uzoru na postojeći kod.** To obuhvaća dokumentaciju klasa, servisa, metoda i funkcija, smislenih konstanti, konfiguracijskih ugovora, predložaka, JavaScripta, CSS-a i testnih scenarija. Objasniti svrhu, ponašanje, ograničenja i razloge netrivijalnih odluka u oba jezika; nije potrebno prepričavati svaku očitu naredbu.
- Identifikatori i standardne oznake poput `@param` ostaju prema konvenciji koda. Isporučeni tuđi kod/fontovi zadržavaju izvornu licencu i autorstvo, bez umjetnog prepisivanja njihovih komentara.
- Svaka dokumentacijska datoteka ostaje jednojezična; samo komentari i dokumentacija unutar koda namjerno sadrže HR i EN. U pregled promjena i uvjete releasea uključiti provjeru oba pravila i usklađenosti dviju verzija uputa.

## 8. Etapa 6 — matrica provjera i prihvat

1. **Stanja:** Accessibility nije instaliran / isključen / uključen; Theme nije instaliran / isključen / uključen; svijetli i tamni prikaz; osnovna i prilagođena tema područja. Kritične zajedničke tokove provjeriti u svim relevantnim kombinacijama.
2. **Načini rada:** miš, samo tipkovnica, povećanje teksta 200 %, stvarno povećanje preglednika 400 %, širina 320 CSS piksela, prilagođeni razmaci, smanjeno kretanje i prisilne sistemske boje. Dvodimenzionalne tablice/kalendare provjeriti uz odgovarajuće iznimke i dostupne kontrole.
3. **Čitači zaslona:** VoiceOver/Safari na Macu te NVDA s podržanim preglednikom kada je dostupan odgovarajući sustav/tester. Ako test nije dostupan, to zapisati; axe nije zamjena.
4. **Automatizacija:** jedinični testovi validacije, spremišta i stanja modula; integracijski testovi opcionalnih ovisnosti; preglednički testovi panela, fokusa, lokalizacije, učitavanja fontova, životnog ciklusa i axe provjere reprezentativnih stranica/stanja. Prijavljene WCAG A/AA probleme u obuhvaćenom vlastitom sučelju riješiti prije prihvata; nalaze za ručnu provjeru stvarno pregledati, ne prešutno zanemariti.
5. **Sigurnost i regresije:** CSRF i ovlasti administratorske promjene, nedostupno spremište, pogrešne vrijednosti, CSP, uklanjanje modula, očuvanje ACL-a i dokumenata te izostanak nepotrebnih vanjskih zahtjeva.
6. **Instalacije:** lokalni HFClean kao integracijska točka; ako se uvedu migracije, prvo obnovljiva sigurnosna kopija, migracija do nula čekajućih i provjera sheme. Zatim ciljani testovi i puni postojeći lokalni E2E postupak do konačnog izlaznog rezultata. HFC i FPMSimbioza služe provjeri stvarne ne-FPM/FPM instalacije, bez međusobnog miješanja postavki.
7. **Prihvat korisnika:** kratki zadaci s korisnicima kojima su prilagodbe namijenjene; zabilježiti poteškoće i popraviti ih prije tvrdnji da je rješenje bolje od alternativa. Ne prikupljati nepotrebne zdravstvene podatke.

Svaka etapa završava provjerljivim rezultatom: testom/izvještajem, popisom promijenjenih repozitorija, statusom dokumentacije i preostalim ograničenjima. Objavljivanje čeka završetak svih potrebnih provjera, ne samo odsutnost kritičnih automatskih upozorenja. Polazišne naredbe su `composer on-commit` u promijenjenim repozitorijima koji ga definiraju i `php scripts/run_e2e.php --local --keep` u HFCleanu; u etapi 0 provjeriti da još odgovaraju aktualnom postupku.

## 9. Etapa 7 — izdavanje i nadogradnja

1. Potvrditi opseg i ovlast za release kada razvoj bude dovršen. Odabrati verzije tek tada.
2. Pokrenuti i pričekati sve obvezne provjere i CI svakog promijenjenog repozitorija. Objaviti ovisnosti i novi modul prije Simbioze; jezične pakete objaviti prije izdanja aplikacije koje ih zahtijeva.
3. Ažurirati aplikacijski katalog modula, kompatibilne verzije, prijevode i bilješke izdanja. Poštovati tadašnje pravilo repozitorija o Composer lock datotekama i razdvajanju razvoja od instalacija.
4. Iz objavljenog izdanja nadograditi lokalni HFC CLI postupkom te provjeriti migracije; na FPMSimbiozi provjeriti odvojeno CLI i GUI put nadogradnje iz prethodne podržane verzije. Ne primjenjivati prečace s ručnim kopiranjem koda.
5. Provjeriti da nadogradnja čuva privatnu konfiguraciju, menije, teme, jezike, osobne prilagodbe i rad FPM helpera te da siguran povrat ne gubi podatke.
6. Korisniku predati oznake izdanja, stvarne rezultate CI-ja/testova, poznata ograničenja i provjerenu CLI naredbu za apps-test. apps-test ostaje korisnikov GUI/CLI korak dok ne zatraži drukčije.

## 10. Preostali koraci

Ne ponavljati dovršene dorade temelja, samostalnog modula i lokalnih fontova. Dovršiti provjeru životnog ciklusa iz objavljenog paketa, stvarno povećanje 200/400 %, razmake teksta, dulje prijevode i kontrast nad gradijentima te pregledati najavu pročitanosti obavijesti i pogreške preostalih administratorskih dijaloga. VoiceOver/Safari, NVDA i korisnička provjera ostaju zasebni otvoreni zadaci. Početni zaslon bez automatskih nalaza ne znači da su sva stanja pregledana.

Pri prekidu svake sesije zapisati: dovršeno, nedovršeno, izmijenjene datoteke/repozitorije, pokrenute i stvarno završene testove, otvorene odluke i točnu sljedeću radnju. Procjenu vremena i tokena osvježiti nakon etape 0; ovaj plan ne zadaje potrošački budžet niti pokreće razvoj u pozadini.
