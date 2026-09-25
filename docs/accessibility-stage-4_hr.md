# Pristupačnost — četvrti lokalni razvojni presjek

Datum: 24. rujna 2026. [Engleska verzija](accessibility-stage-4_en.md).

Ovo je nastavak popravaka temelja iz [trećeg presjeka](accessibility-stage-3_hr.md), a ne završetak arhitekturne etape 4. Modul osobnih prilagodbi još nije izrađen. Nema izdanja, commita, pusha, udaljenog CI-ja ni promjene na apps-testu ili postojećim instalacijama. Provjere koriste izoliranu lokalnu instalaciju sa sintetičkim podacima.

## Popravci i razlozi

- **API, odabir vlasnika ključa:** aktivni rezultat povezan je s poljem preko `aria-activedescendant`; opcije imaju jedinstvene identifikatore i stanje odabira, a fokus ostaje u polju. Strelice biraju rezultat, Enter potvrđuje, Escape zatvara i otkazuje zahtjev. Zakašnjeli odgovor više ne otvara zatvoreni popis. Gumb za dodatne rezultate radi i tipkovnicom; fokus se vraća u polje prije skrivanja tog gumba. Učitavanje, broj rezultata i pogreške imaju zasebnu nenametljivu statusnu regiju, izvan popisa opcija. Provjera obveznog odabira označava nevaljano polje.
- **Audit i pretraživanje područja:** oznake su povezane s prilagođenim biračima, a njihovi pristupačni nazivi uključuju i trenutačni odabir. Učitavanje, prazan rezultat i pogreške imaju odgovarajuće najave. Odabir vraća fokus na gumb. Paginacija Audita dobiva naziv te tekstualna imena prethodne i sljedeće stranice. Zasebna provjera bez Teme pronašla je kontrast oznake uspjeha 4,35:1; potamnjena je samo njezina rezervna boja teksta i ponovna provjera prolazi.
- **Struktura cijele stranice:** iz 13 djelomičnih prikaza uklonjen je dodatni `main`; glavni orijentir daje raspored aplikacije. Ispravljeni su API, Audit, User, Confluence Import, prikazi sastanaka Kalendara, Editor i Workspace. Naslovi bočnih izbornika sada su H2, u predlošcima i rezervnom iscrtavanju, umjesto drugog glavnog naslova stranice. Vizualne klase ostaju iste. Aplikacija koja samostalno ugrađuje ove prikaze mora dati svoj glavni orijentir.
- **Confluence, poznati HTML makro tablice:** čuvaju se naslov, opseg zaglavlja i valjane veze `headers`. Identifikatori se preslikavaju zasebno po makrou; ugniježđene tablice imaju zaseban opseg veza. Nevaljane, višeznačne, samoreferentne i međutablične reference ne prenose se, ali vidljivi sadržaj ostaje. Nema nagađanja opisa ni proizvoljnog dodavanja ARIA oznaka. Izvorni sigurnosni popis dopuštenih elemenata i URL-ova ostaje na snazi. Postojeći uvezeni dokumenti nisu skupno mijenjani.
- **Birač Confluence arhive:** jedna oznaka umjesto dvije, povezan opis odabrane datoteke i pomoći te vidljiv fokus na lokaliziranoj oznaci nativnog birača. Ne koristi se vanjski dodatak.
- **Editor, spremljeni i objavljeni sadržaj:** proširena je regresija za kartice i sklopive blokove. Provjerava veze kartice i panela, odabrano stanje, strelice, Home/End, prijelaz Tabom u panel i Enter na nativnom sažetku. Postojeće provjere slika, tablica i grafikona ostaju. Testne slike sada koriste stvarno dostupan lokalni resurs, ne raniju nedostupnu putanju. Za ove tipkovničke radnje nije bilo potrebno mijenjati postojeći generator.

Promijenjeni su izvorni repozitoriji HFClean te devet modula: API, Audit, Calendar, HTML Editor, Menu, Simbioza User, Workspace, Workspace Search i Confluence Import. Instalirane kopije paketa nisu ručno zakrpane. `heartphrame-framework` ostaje čist i samo za čitanje; ostali zabranjeni repozitoriji nisu mijenjani.

## Provjere

| Lokalna provjera | Rezultat |
| --- | --- |
| API, puni `composer on-commit` | 39 testova, 200 provjera |
| Audit, puni `composer on-commit` | 18 testova, 99 provjera |
| Calendar, puni `composer on-commit` | 69 testova, 633 provjere |
| HTML Editor, puni `composer on-commit` | 190 testova, 1.281 provjera |
| Menu, puni `composer on-commit` | 54 testa, 349 provjera |
| Simbioza User, puni `composer on-commit` | 20 testova, 195 provjera |
| Workspace, puni `composer on-commit` | 109 testova, 876 provjera |
| Workspace Search, puni `composer on-commit` | 29 testova, 186 provjera |
| Confluence Import, puni `composer on-commit` | 99 testova, 622 provjere |
| HFClean, puni `composer on-commit` | 95 testova, 4.253 provjere; jedan Linux root test preskočen na Macu |
| Šest jezičnih paketa | Svaki ima 4.373 ključa; valjani SHA-256 i SVG |

Svih devet izmijenjenih modula zajedno: **627 testova i 4.441 provjera**, uz prolaz provjera stila i statičke analize definiranih u njihovim skriptama. Linux root test nije ponavljan; njegovo zasebno stvarno izvođenje i uklanjanje privremenog okruženja dokumentirani su u trećem presjeku. Nema nove migracije.

**Završni puni E2E: 77/77, izlazni kod 0, 7,6 minuta.** Naredba `php scripts/run_e2e.php --local --keep` provjerila je preglednik, API, ovlasti, backup, uvoz, objavu i granice performansi na izoliranom SQLiteu. Zadržana je instalacija `e2e-all-7e1797f6`. Novi Confluence scenarij potvrđuje i da četiri jedinstvena ID-a zaglavlja prežive stvarni uvoz, spremanje i prikaz, a postojeće provjere privatnih privitaka i ACL-a ostaju uspješne.

Prvi puni E2E završio je s 76/77: prošireni Confluence scenarij otkrio je da uklanjanje prethodnog makroa mijenja XPath preostalih susjeda, pa dvije tablice mogu dobiti iste ID-ove. Izvorni položaji sada se snimaju prije pretvorbe; isti stabilni položaj koristi i identitet sklopivog bloka. Dodani su jedinični primjeri s dodatnim susjednim makroom i odvojenim sklopivim blokovima. Tvrdnja o jedinstvenim ID-ovima nije uklonjena. Pokušaj izdvojenog ponavljanja na ranije korištenoj instalaciji stao je na očekivanju početnog naziva područja jer je tamo isti Confluence izvor već bio uvezen; završno ponavljanje koristi novu izoliranu instalaciju.

Automatska provjera stvarnog prikaza pomoću lokalnog axe-core 4.10.3 nakon popravaka ne prijavljuje potvrđene povrede na provjerenim API, Audit, Workspace Search i Confluence početnim zaslonima, u svijetloj i tamnoj temi te bez modula Teme. Osnovni prikazi tehničkog dnevnika, osobnih područja, e-pošte i obavijesti također su prošli usku provjeru. To ne uključuje svako moguće stanje i korisnički sadržaj.

Preostali automatski nalazi za ručni pregled nisu prešućeni: kontrast nad gradijentima alat ne može pouzdano izračunati. Upozorenje za naziv zatvorenog navigacijskog spremnika provjereno je u mobilnom otvorenom stanju: Bootstrap dodaje `role="dialog"` i `aria-modal="true"`, fokus ulazi unutra, Escape zatvara i vraća fokus; tada nema tog upozorenja. Prva provjera poslala je Escape prije završetka animacije i ulaska fokusa; ponovljena provjera čeka stvarno aktivan dijalog. To nije provjera čitačem zaslona.

## Dokumentacija i prijevodi

Upute Editora dopunjene su opisom tipkovnice i ugovorom glavnog orijentira; upute Confluence Importa opisuju očuvanje veza tabličnih zaglavlja. Hrvatski i engleski ostaju u odvojenim datotekama, a novi komentari u kodu su HR/EN. API i Audit koriste već postojeće prevedene ključeve; šest jezičnih paketa zato ne treba novu reviziju za ovaj presjek.

API upute opisuju izbor vlasnika tipkovnicom. Automatske provjere dokumentacijskih parova/poveznica i izvlačenja UI ključeva prolaze. Privremeni preglednik, pomoćni HTTP poslužitelj i preuzeta kopija axe-core uklonjeni su nakon pregleda; alat nije dodan ovisnostima aplikacije. Izolirane instalacije E2E-a zadržavaju se za pregled rezultata.

Mjerila: [W3C kombinirani odabir](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/), [oznake obrazaca](https://www.w3.org/WAI/tutorials/forms/labels/) i [kartice](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/).

## Preostali posao i sljedeći korak

1. Zatvoriti pregled preostalih stanja inventara: pogreške i potvrde Setup/Backup postupaka, administratorski dijalozi i dinamična mapiranja Confluence korisnika/grupa te fokus nakon promjena obavijesti, komentara i zadataka. Prolaz početnog zaslona nije dovoljan.
2. Dovršiti provjeru širine 320 CSS piksela, stvarnog povećanja 200/400 %, razmaka teksta i kontrasta nad gradijentima, uključujući dulje prijevode. Ručne provjere VoiceOverom/Safarijem i NVDA-om te korisničko testiranje ostaju otvoreni.
3. Potom zaključiti ugovor integracije i krenuti na samostalan `heartphrame-module-accessibility`, lokalne fontove s licencama, višejezični osobni panel i jedinstveno uključivanje/isključivanje prema planu. Nema tvrdnje o potpunoj WCAG sukladnosti.

Ovaj razvoj nije nadogradnja postojećeg HFC-a ili FPMSimbioze. Kombinacija izvornog aplikacijskog koda i modula provjerava se izoliranim `--local` postupkom; postojeće instalacije trebaju kasniju usklađenu instalaciju izdanja. apps-test ostaje korisnikova granica ručne nadogradnje.
