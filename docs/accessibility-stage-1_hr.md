# Pristupačnost — prvi lokalni razvojni presjek

Datum: 24. rujna 2026. Polazište: Simbioza `0.1.89`.

[Engleska verzija](accessibility-stage-1_en.md) · [Plan svih etapa](accessibility-implementation-plan_hr.md)

## Opseg i stanje

Ovo je prvi skup popravaka postojećih komponenti, a ne dovršen modul niti potvrda sukladnosti. Etape 0–2 ostaju otvorene. Promijenjeni su samo izvorni repozitoriji HFClean, Auth, Calendar, HTML Editor i Theme. Zaštićeni repozitoriji, postojeći poslovni podaci, korisničke teme i apps-test nisu mijenjani. Nema novih migracija, objave ni nadogradnje postojećih instalacija.

Provjere koriste izoliranu lokalnu instalaciju s testnim podacima. Nove ovisnosti aplikacije, fontovi i vanjski izvršni resursi nisu dodani. Budući fontovi moraju biti u novom modulu, zajedno s licencama. Alat axe-core upotrijebljen je samo lokalno za provjeru, nije ugrađen u aplikaciju.

## Što je promijenjeno i zašto

1. **Glavni raspored:** prečac na glavni sadržaj postoji i kada tema nije dostupna ili ne ispisuje svoj prečac. Cilj može primiti programski fokus. Rezervni CSS ispisuje se samo kada je potreban; ne šalju se dva prečaca.
2. **Tema:** tekst šest obitelji gumba dobiva čitljivu boju izračunatu iz pozadine. Obrubljeni gumbi i pomoćni tekst imaju izvedene čitljive boje. Spremljena korisnička paleta ne prepisuje se. Promjena izračuna kontrasta osvježava i predmemorirani CSS. Naslovno područje dobiva ime orijentira, fokus kartica ostaje vidljiv bez sjene, a vlastiti odabrani prijelazi poštuju smanjeno kretanje.
3. **Poruke:** obične potvrde Autha imaju nenametljivi status, pogreške hitno upozorenje. Auth poruke ne nestaju nakon sedam sekundi. Editorove poruke također se zadano zadržavaju do zatvaranja; pozivatelj još može izričito zatražiti automatsko skrivanje. Dugotrajne sesije s mnogo poruka i prekrivanje sadržaja ostaju dio sljedeće provjere.
4. **Kalendar:** povezana su polja događaja, uvoza, pretraživanja termina i profila resursa. Imenovani su dijalozi događaja, pretplata, uvoza i postavki. Odabrani prikaz održava `aria-pressed`, a razdoblje se nenametljivo najavljuje. Oznake događaja računaju kontrast neovisno o temi, i u pregledniku i pri poslužiteljskom ugrađivanju u dokument. Povećani su premali ciljevi kontrola popisa kalendara te popravljen kontrast odabranog dana u tamnom mobilnom prikazu.
5. **Spremljeni HTML editora:** čuvaju se `h6`, valjani `lang` i `dir`, ograničeni opisni ARIA atributi i veze zaglavlja tablica. Opasni URL-ovi, skripte, proizvoljne uloge i skrivanje sadržaja ostaju blokirani. To je temelj za pristupačno autorstvo; još nije provjera potpunosti sadržaja niti jamstvo da je svaka slika smisleno opisana.
6. **Prijevodi:** prečac je dodan aplikacijskim katalozima HR/EN da ne ovisi o temi. Odgovarajući ključ već postoji u svih šest objavljenih jezičnih paketa; nije dodana privatna lokalna zamjena paketa.

## Editor i Confluence: granica promjene

Postojeći uvezeni dokumenti nisu masovno prepravljani. Uvoznik već pretvara podržani Expand u nativni `details`/`summary`, a neke tablične makroe u tablice sa zaglavljima. Takvim elementima ne treba dodavati statički `aria-expanded` koji se ne bi ažurirao.

U sljedećem koraku treba provjeriti svaki generator editorovih komponenti i put spremanja/ponovnog otvaranja. Dodati upozorenja za nedostajuće opise slika, naslove, tablična zaglavlja, nejasne poveznice i grafičke prikaze bez tekstualne alternative. Autor odlučuje je li slika dekorativna i piše opis; naziv datoteke nije zamjena. Upozorenja ne smiju blokirati legitimno spremanje proizvoljnim pretpostavkama.

Za Confluence se poboljšava samo dobro poznata pretvorba makroa koja ima regresijski test. Nepodržani makroi, vanjski umetci i privitci trebaju evidentirano ograničenje ili preporuku za ručni pregled, ne izmišljenu oznaku pristupačnosti. Semantička pravila moraju ostati jednaka u editoru, objavljenoj stranici i izvozu.

## Inventar prikaza i preostala provjera

Izbrojeno je 80 PHP predložaka u vlastitoj aplikaciji i modulima, bez `vendor` kopija. Broj nije broj dovršenih provjera; dinamički prikazi mogu nastajati i izvan predložaka.

| Repozitorij ili modul | Predložaka | Sljedeći obuhvat |
| --- | ---: | --- |
| HFClean | 4 | Osnovni raspored bez teme, instalacija i nadogradnja |
| API | 3 | Obrasci, validacija, tablice |
| Audit | 2 | Filtri i rezultati |
| Auth | 10 | Ponavljani retci postavki, greške i dinamička polja |
| Backup | 1 | Izbor komponenti, napredak i potvrde |
| Calendar | 9 | Preostala administracija i profil, ACL odabiri, ostali prikazi događaja |
| Comment | 0 | Kontrole u drugim modulima |
| HTML Editor | 7 | Autorstvo, dijalozi, privitci, dinamički elementi i generirani HTML |
| E-mail | 2 | SMTP postavke i poruke |
| Menu | 7 | Tipkovnica, padajući izbornici i ponavljani obrasci |
| Notification | 2 | Čitanje, oznake stanja i fokus |
| ORM | 0 | Poruke u prikazima koji ga koriste |
| Task | 0 | Generirane kontrole i promjena stanja |
| Theme | 2 | Uređivanje palete, proizvoljne kombinacije i raspored |
| Confluence Import | 2 | Mapiranja, izvještaj i semantika podržanih pretvorbi |
| Simbioza User | 4 | Profil i korisnička polja |
| Workspace | 23 | Stablo, odabiri, obrasci, navigacija i sadržaj |
| Workspace Search | 2 | Filtri, odabiri i najave rezultata |

Statički pregled pokazao je kandidate za nepovezane oznake u ponavljanim retcima Auth/Menu postavki, dijelu kalendarske administracije, poljima čvorova područja i dinamičkim odabirima. Svaki se mora potvrditi u stvarnom prikazu: skriveno polje, oznaka koja obuhvaća kontrolu i prilagođeni odabir nisu isti slučaj. Ne dodavati jednake identifikatore u petljama niti prekrivati problem globalnom skriptom koja nagađa oznake.

## Provedene provjere

| Provjera | Rezultat |
| --- | --- |
| Auth, `composer on-commit` | Prolaz; 79 testova, 503 provjere |
| Calendar, `composer on-commit` | Prolaz; 68 testova, 599 provjera |
| Theme, `composer on-commit` | Prolaz; 35 testova, 754 provjere |
| HTML Editor, `composer on-commit` | Prolaz; 188 testova, 1262 provjere; 3 upozorenja o zastarjelim PHP 8.5 pozivima u nepromijenjenom slikovnom/ORM kodu |
| HFClean, `composer on-commit` | Prolaz; 94 testa, 4228 provjera; 1 postojeći nedovršeni test konstrukcije aplikacije i 1 preskočeni test vlasništva koji zahtijeva root |
| Bilingvalni komentari, dokumentacija i katalozi prijevoda | Nema prijavljenih problema u aplikacijskim provjerama |
| Postojeći testovi Confluence pretvorbe | Prolaz; 43 testa, 275 provjera; bez promjene uvoznika |
| Puni lokalni E2E, `php scripts/run_e2e.php --local --keep` | Prolaz; svih 76 scenarija, izlazni kod 0, približno 7,5 minuta; preglednik, API i granice performansi |

Prvi E2E prolaz imao je 74/76 uspješnih testova: test pretraživanja očekivao je stari `alert` umjesto novog nenametljivog `status`, a javna stranica prešla je granicu veličine odgovora za 60 bajtova. Ispravljene su semantička provjera i nepotrebna isporuka rezervnog CSS-a; granice testa nisu povećane.

U Chromiumu na izoliranoj instalaciji provjereni su prečac tipkama Tab/Enter, imenovani dijalog događaja, zadržavanje fokusa, Escape i povrat na pokretač. Nakon popravka ciljeva i kontrasta axe-core 4.10.3 nije prijavio automatske povrede odabranih WCAG A/AA pravila na provjerenom prijavljenom kalendaru u svijetlom/tamnom prikazu ni u obrascu događaja. Ostali su nalazi za ručni pregled kontrasta i ARIA atributa. To nije potvrda cijelog sitea niti test stvarnim čitačem zaslona.

### Dodatni otvoreni nalazi

Naknadno, 24. rujna: prelijevanje i gubitak dinamičke konfiguracije popravljeni su u [drugom razvojnom presjeku](accessibility-stage-2_hr.md). Prošao je i test bez učitanog modula teme. Sljedeći popis ostaje povijesni zapis prvog presjeka, ne aktualni popis neotklonjenih grešaka.

- Na širini od 320 CSS piksela provjereni prijavljeni prikaz ima širinu dokumenta 353 piksela. Kontrole kalendara imaju cilj 24 × 24 piksela; izvan širine se pojavljuje i skriveni opis broja obavijesti u zaglavlju. Treba zasebno potvrditi i popraviti uzrok prelijevanja; provjera suženog prikaza nije proglašena prolaznom.
- Nakon postojećeg E2E testa vraćanja pune sigurnosne kopije, izolirani `config/app.php` sadrži izračunati statički popis `modules.enabled`, dok CLI mijenja `data/config/modules.php`. U tom testnom stanju isključivanje modula Theme nije uklonilo njegov CSS. To je dokaz za daljnju provjeru povrata dinamičke konfiguracije, ne tvrdnja o stanju apps-testa. Životni ciklus nakon vraćanja kopije mora dobiti zaseban regresijski test prije izdanja. Promjena stanja testnog modula vraćena je.
- Rezervni prečac bez tematskog CSS-a provjeren je zasebno, isključivanjem prikaza u vlastitim postavkama teme unutar izolirane instalacije. To nije zamjena za provjeru potpuno odsutnog modula. Testna postavka potom je vraćena.

## Točan sljedeći korak

Nastaviti etapama 1–2: cijeli obrasci Calendar/Auth/Menu/Workspace i dinamički odabiri, uz provjeru bez teme. Zatim editorovi generatori i upozorenja autoru, uz provjeru poznatih uvoznih pretvorbi. Tek potom izraditi `heartphrame-module-accessibility`, njegove lokalne fontove i osobni panel prema planu. Potrebne su provjere čitačima zaslona, povećanja, svih jezika te zasebne FPM/ne-FPM instalacije prije izdanja.

Referentna mjerila: [oznake i upute](https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html), [najmanji kontrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html) i [obrazac prikaza/skrivanja sadržaja](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/). Automatske provjere ne zamjenjuju ručno ispitivanje.
