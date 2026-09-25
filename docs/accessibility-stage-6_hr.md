# Pristupačnost — šesti lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-6_en.md).

Nastavak [petog presjeka](accessibility-stage-5_hr.md). Ovo je prototip temelja modula, ne gotovo izdanje niti potvrda WCAG sukladnosti. Sve promjene su lokalne; nema commita, pusha, releasea ni promjene apps-testa, HFC-a ili FPMSimbioze. Repozitoriji `heartphrame-framework`, `heartphrame` i `heartphrame-module-demo` nisu mijenjani. Demo predložak pregledan je iz privremene kopije samo za čitanje.

## Što je napravljeno

- Izrađen je zaseban lokalni paket `heartphrame-module-accessibility`, prema konvencijama manifesta i Composer paketa. Ovisi samo o frameworku; ne o Simbiozi, Temi, Područjima ili Editoru. Ima dvije rute lokalnih CSS/JS resursa i globalni renderer prikaza koji se registrira samo dok je modul učitan.
- Razvojni raspored Simbioze izričito uključuje resurse u zaglavlje i panel na kraju tijela, bez prepisivanja tuđeg HTML-a. U središnji dopušteni popis modula dodan je paket, ali nije dodan javni katalog ili produkcijska Composer ovisnost jer izdanje paketa još ne postoji. Uključen je samo u ignoriranu lokalnu Composer konfiguraciju radi testiranja. Nema novog administratorskog prekidača, baze ili migracije.
- Panel prve probe nudi veličinu teksta, razmake, isticanje poveznica i smanjeno kretanje; ima nativni dijalog, zatvaranje Escapeom, vraćanje fokusa i reset. Vrijednosti su ograničene na dopuštene izbore, a ključ spremišta obuhvaća ishodište/osnovnu putanju i verziju sheme. Oštećen ili blokiran zapis ne ruši stranicu. Bez JavaScripta pokretač je skriven i osnovni prikaz ostaje dostupan.
- Oznake i dinamične poruke dobile su hrvatske izvorne ključeve i lokalne prijevode na engleski, njemački, francuski, španjolski i talijanski. Dokumentacija paketa jezično je odvojena, a novi komentari u kodu su hrvatsko-engleski.
- Pri pregledniku je pronađeno preveliko zauzimanje prostora pokretačem i prelijevanje dugog naslova na uskom zaslonu s 200 % tekstom. Pokretač je sada sažet, a naslov se smije prelomiti. To ne dokazuje prihvatljivost svih kombinacija povećanja i jezika.

## Dovršene provjere

| Provjera | Rezultat |
| --- | --- |
| Lint novih PHP/JS datoteka i stil PHP-a paketa | Prolaz; 5 PHP datoteka bez upozorenja |
| Nova PHPUnit provjera renderera i šest jezičnih mapa | 3 testa, 212 provjera, izlazni kod 0 |
| HFClean `composer on-commit` nakon povezivanja | 96 testova, 4.261 provjera, jedan postojeći Mac skip; izlazni kod 0 |
| HTTP s privremeno uključenim modulom | Početna stranica i oba lokalna resursa 200; nakon isključivanja resurs 404 i nema panela u HTML-u |
| Preglednik i tipkovnica | Otvaranje, promjena na 200 %, spremanje, osvježavanje, Escape i povrat fokusa prošli |
| Uski prikaz | Pri 320 CSS piksela i osobnoj veličini teksta 200 % nema vodoravnog prelijevanja pregledane početne stranice ni panela; panel se okomito pomiče |
| Oštećeni zapis | Nakon namjerno nevaljanog JSON-a učitane su zadane vrijednosti bez prekida prikaza |

Za pregled stvarnog lokalnog sučelja korišten je Playwright CLI. Provjera širine 320 CSS piksela nije isto što i stvarno povećanje preglednika na 400 %. Nisu provedeni puni E2E cijele instalacije, VoiceOver/Safari, NVDA, ručni pregled svih ruta ni udaljeni CI. Nakon testa je razvojno stanje modula vraćeno na isključeno, a lokalni preglednik i HTTP poslužitelj zatvoreni. Ignorirana lokalna Composer konfiguracija i njezina veza s paketom ostaju za daljnji razvoj; produkcijski `composer.json` nije mijenjan.

## Otvoreno i točan sljedeći korak

Modul još nema lokalne fontove, visokokontrastne i čitalačke prilagodbe, punu provjeru dugih prijevoda i gradijenata, samostalan drugi primjer HeartPhrame aplikacije, katalog/GUI/CLI životni ciklus ni sve obvezne dokumentacijske parove. Kandidati za fontove su pregledani na izvornim stranicama, ali licencije i pokrivenost znakova za konkretne datoteke još treba potvrditi prije uključivanja. Nisu dotaknuti dokumenti korisnika ni importirani sadržaj.

Sljedeće: napraviti samostalan minimalan HeartPhrame host bez Simbiozinih modula i provjeriti isti ugovor prikaza; zatim dodati lokalne fontove tek s licencama i provjerom dijakritika/ćirilice. Nakon toga proširiti panel i CI, uvesti paket u katalog te provjeriti instalaciju/isključenje/ponovno uključenje i uklanjanje. Odvojeno nastaviti audit stvarnog povećanja 200/400 %, kontrasta gradijenata, najava pročitanosti obavijesti i pogrešaka administratorskih dijaloga.
