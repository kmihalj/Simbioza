# Pristupačnost — peti lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-5_en.md).

Nastavak [četvrtog presjeka](accessibility-stage-4_hr.md). Ovo su popravci temelja, ne gotov modul osobnih prilagodbi niti potvrda potpune WCAG sukladnosti. Rad je lokalan: nema izdanja, commita, pusha, udaljenog CI-ja ni promjena na apps-testu. Postojeće instalacije HFC i FPMSimbioza nisu nadograđene. Izolirane instalacije koriste sintetičke podatke i lokalne izvore modula; instalirani paketi nisu ručno zakrpani.

## Što je popravljeno i zašto

- **Setup:** prikaz napretka koristi nativni dijalog. Otvaranje premješta fokus na naslov, pozadina je neaktivna, Tab/Shift+Tab ostaju među dostupnim gumbima. Escape ili Zatvori zatvara samo prikaz; pozadinski updater nastavlja raditi. Prikaz se može ponovno otvoriti gumbom uz podatke nadogradnje. Anketa statusa ne otvara samovoljno zatvoren prikaz. Pogreška dobiva odgovarajući naslov i gumb za osvježavanje. Uklonjen je ugniježđeni glavni orijentir, a retci modula više ne koriste nedopuštenu kombinaciju `article` i `role="row"`. Poslužiteljski updater, ovlasti i migracije nisu mijenjani.
- **Backup:** oznake pet birača povezane su s kontrolama i trenutačnim odabirom. Birač datoteke ima jednu oznaku i vidljiv fokus. Postotak prijenosa dostupan je i pomoćnim tehnologijama, dok provjera i vraćanje ostaju neodređeni dok nema stvarnog postotka. Poruke se ne gase vremenski. Povrat fokusa nakon radnje ili zatvaranja poruke ne prekida korisnika koji je već otišao dalje. Automatsko osvježavanje poslova čuva nepromijenjene retke i fokus; nakon uklanjanja usmjerava ga na preostalu radnju ili naslov popisa. Gumbi s ikonama dobivaju izričite nazive. Mreža komponenti stane na uski zaslon.
- **Confluence mapiranja:** svaki birač ima naziv s izvornim identitetom, vezu s panelom i točno prošireno stanje. Koriste se nativno polje pretrage i gumbi rezultata, bez nepotpune uloge kombiniranog odabira. Enter bira, Escape zatvara i vraća fokus, a izlazak iz birača zatvara panel. Otvaranje drugog birača zatvara prvi. Otkazivanje i redni broj zahtjeva sprječavaju prihvaćanje zakašnjelih odgovora. Učitavanje, broj rezultata i pogreška imaju statusnu regiju. Grupe imaju zasebne oznake; trake napretka imaju nazive. Poruke postupka ostaju do zatvaranja. Bez Teme je izmjeren kontrast sitnog identifikatora 4,03:1; rezervna boja sada slijedi čitljivu boju osnovnog teksta.
- **Komentari:** reakcije izlažu i ažuriraju `aria-pressed`. Nakon brisanja fokus prelazi na naslov komentara ako je bio na uklonjenom komentaru. Pogreške su upozorenja, potvrde nenametljive statusne poruke; ostaju dostupne do zatvaranja, koje vraća fokus na pripadajuću kontrolu ili naslov.
- **Zadaci:** nakon uspješnog ili neuspješnog spremanja fokus se vraća na kućicu samo ako se izgubio tijekom privremenog onemogućavanja. Neuspjeh vraća prethodno stanje. Zatvaranje poruke vraća fokus na zadatak ili praćenje; uklonjena je dvostruka deklaracija hitne regije.
- **Obavijesti:** gumb uklanjanja povezan je s naslovom konkretne obavijesti. Nedostupne poveznice paginacije više nisu u tabulatorskom slijedu i izlažu onemogućeno stanje. Postojeći obrazac s punom navigacijom nije zamijenjen novim asinkronim sustavom.

Izmijenjeni su HFClean i pet modula: Backup, Comment, Task, Notification te Confluence Import. Zabranjeni repozitoriji nisu mijenjani; `heartphrame-framework` ostaje čist. Nema novih migracija, fontova ni vanjskih resursa aplikacije.

## Dokazi provjere

| Završena lokalna provjera | Rezultat |
| --- | --- |
| Backup, puni `composer on-commit` | 45 testova, 146 provjera |
| Comment, puni `composer on-commit` | 7 testova, 35 provjera |
| Task, puni `composer on-commit` | 11 testova, 73 provjere |
| Notification, puni `composer on-commit` | 8 testova, 54 provjere |
| Confluence Import, puni `composer on-commit` | 100 testova, 629 provjera |
| HFClean, puni `composer on-commit` | 96 testova, 4.261 provjera; jedan Linux root test preskočen na Macu |
| Puni izolirani E2E | **77/77, izlazni kod 0, 8,0 minuta** |
| Završna izdvojena regresija Setupa | 1/1, izlazni kod 0, 3,7 sekundi |
| Katalog šest jezika | po 4.373 ključa; SHA-256 i SVG valjani |
| Provjera dokumentacijskih parova i poveznica | 0 problema |

Pet modula zajedno ima 171 test i 937 provjera. Prolaze i provjere stila te statičke analize iz njihovih skripti. Jedini preskočeni HFC test traži Linux i root; ranije stvarno izvođenje opisano je u trećem presjeku. Privremeno Linux okruženje nije ponovno instalirano.

Prvi puni prolaz bio je 75/77. Test je pokazao da Chromium na kraju nativnog dijaloga može ponuditi adresnu traku: dodana je izričita obrada Tab/Shift+Tab, tvrdnja nije uklonjena. Drugi pad bio je pogrešno očekivani prijevod `Select` umjesto stvarnog `Choose a backup archive`; ispravljeno je očekivanje testa. Završni puni prolaz koristi novu izoliranu instalaciju `e2e-all-957cdcfe`. Naknadne završne semantičke dorade Setupa dodatno su provjerene izdvojenim testom i stvarnim prikazom najnovijeg predloška u ranijoj izoliranoj instalaciji.

Prošireni E2E provjerava zatvaranje i ponovno otvaranje napretka, terminalnu pogrešku, trajanje poruke i fokus Backupa, stvarni puni backup/vraćanje, Confluence izbor tipkovnicom, stanje reakcije i brisanje komentara te uspješno i namjerno neuspješno spremanje zadatka. Mrežna pogreška zadatka i status nadogradnje su sintetički; **nije pokrenuta stvarna nadogradnja aplikacije**. Postojeće API, ACL, import i izvedbene provjere ostaju uključene.

Lokalni axe-core 4.10.3 nakon popravaka ne nalazi potvrđene povrede u pregledanim početnim i otvorenim stanjima Setupa, Backupa i Confluence mapiranja. Provjereni su tematski svijetli/tamni prikaz i prikaz bez Teme; uski tematski Backup i otvoreno mapiranje na 320 CSS piksela nemaju vodoravno prelijevanje stranice. Zaseban pokus s dva izvorna identiteta potvrđuje zatvaranje drugog birača i odbacivanje zakašnjelog odgovora nakon Escapea. Kontrast nad gradijentom ostaje nalaz za ručni pregled. Kratkotrajni nalaz kontrasta tijekom promjene teme nije se ponovio nakon završetka prijelaza. Pri isključivanju Teme zabilježen je jedan dovršavajući zahtjev stare stranice za resursom Teme; sljedeća stranica nema taj resurs niti neuspješno učitane slike.

Zapisi su u `build/a11y-stage5-quality/`; snimke u `output/playwright/`. Privremeni alat za audit nije dodan ovisnostima aplikacije. Testna priprema Confluence arhive otkazana je kroz sučelje, bez uvoza u poslovno područje.

Nakon provjere vraćeno je uključeno stanje Teme u pomoćnoj instalaciji, zatvoreni su preglednik i pomoćni HTTP poslužitelj te je uklonjena privremena kopija axe-core (2,7 MB; može se ponovno preuzeti). Izolirane E2E instalacije i zapisi ostaju za naknadni pregled.

## Dokumentacija i sljedeći korak

Upute Backupa, opisi Comment/Task modula i upute Confluence mapiranja dopunjeni su u odvojenim hrvatskim i engleskim datotekama. Novi komentari su HR/EN. Koriste se postojeći ključevi iz svih šest paketa; Confluence dobiva i vlastite rezervne HR/EN prijevode za učitavanje i broj rezultata. Nova revizija jezičnih paketa nije potrebna za ovaj presjek.

Sljedeće treba dovršiti provjeru stvarnog povećanja 200/400 %, razmaka teksta i duljih prijevoda te kontrasta nad gradijentima. Provjere VoiceOver/Safari, NVDA i korisnička procjena nisu provedene ovim automatiziranim prolazom. Posebno pregledati najavu promjena pročitanosti obavijesti i pogreške ostalih administratorskih dijaloga. Potom zaključiti uski ugovor integracije i krenuti na osobni panel `heartphrame-module-accessibility` s lokalnim, licenciranim fontovima. Početni cilj ostaje jedan administratorski prekidač i osobne prilagodbe u pregledniku, bez promjene frameworka.

Mjerila: [W3C modalni dijalog](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) i [statusne poruke](https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html).
