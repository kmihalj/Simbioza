# Pristupačnost — treći lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-3_en.md).

Ovaj presjek nastavlja popravke temelja u etapama 1–2. Ne znači da su arhitekturna etapa 3 ili modul osobnih prilagodbi dovršeni. Sve je lokalno; nije napravljen release, push, udaljeni CI niti promjena na apps-testu.

## Usklađenost ovisnosti

Manifesti svih 17 vlastitih modula sada kao minimum zahtijevaju najnovije kompatibilne objavljene interne tagove, uključujući razvojne integracije. Svaki modul koristi `minimum-stability: stable`. Ponovno su razriješene i provjerene njegove vlastite lokalne ovisnosti; ažuriranje samo Simbioze ne osvježava razvojnu instalaciju pojedinog modula.

Workspace sada zahtijeva i lokalno koristi ORM 0.1.6. Ranije opažena PHP 8.5 deprecijacija nestaje s aktualnim tagiranim ORM-om, bez ručne zakrpe instalirane ovisnosti. Repozitorij Frameworka ostaje neizmijenjen i samo za čitanje; Composer koristi službeni tag 0.0.25 njegova održavatelja. Povijesni tagovi modula 1.x ne zamjenjuju se za nasljednike aktualne linije 0.1.x.

Osvježeni su PHPStan, Rector i kompatibilni alati za testiranje i stil koda. PHPUnit 10/11 i PHP_CodeSniffer 3 ostaju namjerne granice kompatibilnosti; PHPUnit 13 i PHP_CodeSniffer 4 zahtijevaju zasebnu migraciju. [Pravilo ovisnosti](module-dependencies_hr.md) sada izričito traži razrješavanje paketa, provjeru platforme i puni skup provjera svakog modula prije izdanja. Ograničenje s karetom dopušta buduća kompatibilna izdanja; samo po sebi ne osvježava postojeću lock datoteku ni instalirani paket.

Stroge provjere otkrile su i manje razvojne probleme: prostore imena Auth testova i automatsko učitavanje izričitih SimpleSAML zamjena, učitavanje neobvezne Workspace testne usluge te ponavljanje operacije dostave u modulu User koja mijenja stanje. Ispravljeni su bez uklanjanja tvrdnji ili gašenja provjera. Postojeće razvojne kopije i simboličke poveznice paketa sačuvane su u zanemarenim direktorijima `build` odgovarajućih modula prije instalacije tagiranih ovisnosti.

## Konstrukcija aplikacije i Linux test vlasništva

Ranije nedovršen test konstrukcije sada izrađuje stvarnu aplikaciju s izoliranom konfiguracijom, stvarnim tvornicama aplikacijskih usluga i rutama, bez poslovnih modula i baze. Provjerava relevantne usluge i konfiguraciju te potvrđuje da konstrukcija ne stvara aplikacijske datoteke.

Test vlasništva stvarno je izveden kao root u privremenom Ubuntu 24.04 Linux virtualnom stroju: **1 test, 12 provjera, bez preskakanja**. Preneseni su samo klasa za prava, njezin test, namjenski pokretač i izolirana instalacija PHPUnit-a. Virtualni stroj nije imao montirane direktorije Maca i nije učitavao privatnu konfiguraciju aplikacije.

Novi pokretač odbija izvođenje izvan Linuxa ili bez roota te pada na preskočenom ili praznom testu. Posebni koraci GitHub/GitLab CI-ja pokreću ga odvojeno od običnih testova. Njihova konfiguracija provjerena je lokalno; udaljeni CI nije pokrenut jer ništa nije poslano na repozitorije.

Colima i njezina novoinstalirana ovisnost Lima potom su deinstalirane. Uklonjeni su privremeni virtualni stroj, slika, predmemorije, dnevnici i novostvoreno stanje Colime; postojeća Docker konfiguracija sačuvana je. apps-test nije korišten za ovaj test.

## Autorstvo u editoru i spremljeni HTML

- U izbornik Mediji i kontekstni izbornik slike dodano je uređivanje alternativnog teksta te izričito označavanje ukrasne slike. Promjena vrijedi za tu pojavu slike, ne za sva korištenja privitka.
- Ukrasna slika sprema standardni `alt=""` i usko validiranu oznaku autorove odluke. Sukobljene oznake, opisi i pomoćni naslovi uklanjaju se samo pri autorovu izričitom izboru te radnje. Uređivanje opisa uklanja oznaku ukrasne slike.
- Sam prazan opis i dalje se prijavljuje. Postojeće i uvezene slike ne proglašavaju se automatski ukrasnima niti se izmišljaju njihovi opisi.
- Provjera fragmenta više ne zahtijeva drugi H1 kada naslov stranice može dati izgled aplikacije. Ostaju provjere praznih, preskočenih i višestrukih naslova; strukturu cijele stranice i dalje treba provjeriti.
- Ograničena provjera poveznica prepoznaje sliku s opisom i izričite ARIA nazive. Prijavljuju se nevaljane, višestruke, samoreferentne i međutablične reference zaglavlja; zaglavlje ugniježđene tablice više ne skriva nedostatak zaglavlja vanjske tablice.
- Iz usluge slikovnih varijanti Editora uklonjeni su zastarjeli PHP 8.5 pozivi `imagedestroy()`; oslobađanje referenci na objekte `GdImage` zadržava postojeće čišćenje.

Regresija u pregledniku izrađuje sadržaj, pokreće provjeru, koristi obje radnje slike, ispravlja vezu zaglavlja, sprema i objavljuje te provjerava spremljenu i prikazanu semantiku. Nije mijenjana konverzija Confluencea niti su skupno prepisivani postojeći dokumenti.

U repozitoriju Editora dodane su zasebne hrvatske (`docs/accessibility_hr.md`) i engleske upute, povezane iz kazala dokumentacije. Četiri nova izvorna ključa prevedena su u svih šest održavanih paketa. Lokalna revizija jezičnog kataloga je `2026.09.24.1`, s 4.373 ključa po paketu; kontrolni sažeci i SVG zastavice prolaze provjeru. Promjene paketa još nisu objavljene.

## Provjera

| Provjera | Rezultat |
| --- | --- |
| Svih 17 modula, puni `composer on-commit` | Prolaz; ukupno 901 test i 6.834 provjere |
| Zahtjevi platforme i provjera razvojnih grana modula | Prolaz u svih 17; nema instaliranih razvojnih verzija; 65 internih ograničenja odgovara provjerenim instaliranim tagovima |
| HFClean, puni `composer on-commit` | Prolaz; 95 testova i 4.253 provjere; nema nedovršenih testova; Linux test vlasništva preskočen je na macOS-u i zasebno je prošao na Linuxu |
| Izolirani Linux root test vlasništva | Prolaz; 1 test i 12 provjera |
| Ciljana regresija Editora u pregledniku | Prolaz, uključujući spremanje/objavu i prikazani HTML |
| Puni lokalni E2E | Prolaz; 76/76, izlazni kod 0, 7,5 minuta; zadržana izolirana SQLite instalacija `e2e-all-929594dc` |
| Jezični katalozi | Svih šest valjano; po 4.373 ključa |

Dodatnom Playwright CLI provjerom odabrana je slika, izbornik Mediji otvoren Enterom, nova radnja za ukrasnu sliku dosegnuta strelicama i aktivirana Enterom. DOM je zatim sadržavao prazan `alt` i izričitu oznaku. Ta uska provjera nije potpuni pregled rada tipkovnicom. Testne slike koriste nedostupnu lokalnu putanju; provjere pokrivaju semantiku, ne vizualno učitavanje slike. Privremena sesija preglednika i HTTP poslužitelj potom su ugašeni.

Početni puni E2E završio je s 75/76 jer je novi test tražio nalaze u napuštenom ugrađenom panelu umjesto u stvarnoj obavijesti provjere. Ispravljeni su selektor i radnja ponovne provjere prema stvarnom sučelju; nije uklonjena nijedna provjera ponašanja aplikacije. Početno paralelno izvođenje provjera modula Task također je naišlo na koliziju zajedničke privremene Rector predmemorije; zasebno potpuno ponavljanje prošlo je.

## Preostali posao i točan sljedeći korak

Nastaviti postojeći popis prikaza, počevši od preostalih postavki, stanja pogrešaka i dinamičkih odabira. U Editoru proširiti provjere tipkovnice i prikazane semantike generatora tablica, tabova, harmonika i grafikona; provjeriti naslove cijele stranice s Temom i bez nje. Sigurne dorade pretvorbe Confluence makroa zahtijevaju vlastite regresijske primjere, a ne neselektivno dodavanje ARIA atributa.

Ručna provjera čitačem zaslona i šira matrica jezika, tema i povećanja još su otvorene. Ovo su pomoć autoru i regresijski popravci, ne izjava o WCAG sukladnosti. U ovom presjeku nema migracije, vanjskih resursa za krajnjeg korisnika, novog fonta ni panela osobnih prilagodbi. Višekratno upotrebljiv modul pristupačnosti slijedi nakon dovršetka tih temelja.
