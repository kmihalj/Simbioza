# Lokalne provjere kandidata za izdanje

[Engleska verzija](release-preflight-0.3.3_en.md)

Lokalni, neobjavljeni kandidat: Simbioza **0.3.3**, Confluence Import, Simbioza User
i Calendar **0.3.1**. Objavu još treba odobriti korisnik. Nisu objavljeni udaljeni
commitovi, tagovi, izdanja ni imagei. Auth i framework su zaštićeni; njihove
postojeće nepovezane izmjene nisu dio kandidata. Demo/apps-test nisu mijenjani.

## Kontrolna lista

**Potvrda samo u nacrtu, 16. listopada:** deset duplih parova koje je korisnik
izričito potvrdio spremljeno je kroz HFC GUI. Promijenjeno je točno deset odabira
glavnog računa i deset veza izvornih oznaka; računi nisu stvoreni i uvoz nije
pokrenut. Ostalo je deset mogućih duplih skupina i šest sukoba izrade izvora.
Pregled kalendara preračunava se na 101 nedovršen odabir nakon razrješenja tih veza;
spremljene postavke kalendara/područja/postupka i svi neodobreni odabiri identiteta
ostaju isti. Otisci prije/poslije bez upisa potvrđuju očuvane poslovne tablice, Auth
konfiguraciju, pretplate, sadržaj područja i trajna mapiranja uvoza. Bez izmjena
Auth/framework koda i objave. Sljedeći brojevi prijedloga opisuju ranije početno
stanje, ne dodatno odobrenje.

Posao cijelog izvora sada prikazuje privatne prijedloge bez upisa i predmemorije,
dostupne vlasniku: 20 mogućih grupa duplih računa (deset istog punog imena/
identifikatora, sedam samo po imenu, tri različitih imena/dijeljenog identifikatora)
i svih 107 nedovršenih kalendarskih odabira (65 prijedloga timskog, 39 osobnog, tri
neodlučene vrste). Devet osobnih prijedloga još nema pouzdanog vlasnika. Sučelje
odvaja spremljene i predložene vrste, slabe tragove osobnog područja/imena i
neriješene identitete; ne sprema odabire, ne daje prava niti pokreće uvoz. Auth/
framework ostaju nepromijenjeni. Korisnička potvrda i GUI uvoz cijelog izvora još čekaju.

Dopuna pregleda mapiranja prolazi PHPCS/Rector/PHPStan importera i **281 test /
1.769 provjera**, sva četiri izdvojena preglednička scenarija importera te provjere
odvojenih jezika dokumentacije, dvojezičnih komentara i prijevoda. Čuva HFC nacrt,
vjerodajnice/SSO/admin stanje i otiske izvornog osobnog sadržaja, bez novih redaka
obavijesti/emaila. To je priprema pregleda, ne odobrenje mapiranja ni novi HFC uvoz
cijelog izvora; ne obnavlja cijelu raniju matricu provjera izdanja.

**Prošireni GUI test cijelog izvora, 16. listopada: pripremljen, nije izvršen.** Novi
korisnički zahtjev odabire sva 352 područja i 266 kalendara u novom vlastitom GUI
poslu. Postavke identiteta odabiru 55 mapiranih referenci i 361 novi neaktivni račun,
ali 26 sukoba računa ostaje neriješeno. Sedam osobnih područja nema razriješenog
vlasnika, a dva izvorna osobna područja pripadaju istom ciljnom vlasniku. Postavke
kalendara prihvaćaju 159 jednoznačnih prijedloga; 64 osobna kalendara još traže
izričitog vlasnika, a 43 kalendara vrstu. Ranije pogrešno razvrstane testne kalendare
ne smije se prešutno dodijeliti drugome. To su prepreke pregleda, ne uspješan uvoz.
Postojeći osobni kalendar i sedam aktivnih administratorskih pretplata su očuvani.
Nema završne potvrde uvoza, izdanja ni izmjena Autha/frameworka. Ciljani testovi
zajedničkog site ZIP-a/privitaka prolaze: 13 testova / 66 provjera; ova arhiva i dalje
ne dokazuje stvarni prijenos privitaka jer nema binarne datoteke. Cijeli preostali
opseg prihvata opisan je u odvojenim EN/HR planovima site importa.

Importer sada podržava izričito povezivanje duplih izvornih računa na jednog
postojećeg/planiranog glavnog korisnika te dodavanje više osobnih izvora u jedno
provjereno osobno područje. HFC nacrt koristi dodavanje za dva izvora istog vlasnika.
Izdvojeni GUI regresijski test provjerava autore, poveznice, privatne privitke i
očuvanje pri ponavljanju. Područja/postavke sadržaja i kalendari imaju zasebnu
paginaciju 25/50/100 koja čuva nacrte ostalih stranica; 107 kalendarskih redaka još
je pod **Potrebna potvrda vrste ili vlasnika**. Korisničko odobrenje mapiranja i
novo stvarno GUI izvršavanje cijelog izvora još čekaju, nisu označeni prolazom.

Aktualne ciljane provjere prolaze: PHPCS/Rector/PHPStan importera, **273 testa /
1.739 provjera**, sva četiri preglednička scenarija importera (uključujući nacrt
s paginacijom 131 područja/132 kalendara, duple osobne izvore/ponavljanje i
nepromijenjeni uvoz jednog područja s ACL-om/privatnim privitcima), prijevodi,
dvojezični komentari i odvojeni jezici dokumentacije. Izvorni HFC otisci osobnog
sadržaja i stanje vjerodajnica/SSO-a/admina ostaju isti; nema novih obavijesti/emaila,
uz **16 izvršenih / nula pending migracija**. Ove ciljane provjere ne obnavljaju
cijelu raniju PHP/baza/kontejnersku matricu.

**Aktualna odluka nakon otključanog GUI pregleda 16. listopada: nije spremno za objavu.**
Početne provjere izvršavanja ispod nisu dokaz semantičke ispravnosti vlasništva/
vrste kalendara. Sedamdeset osobnih službenih kalendara označeno je timskima, a
mnogi imaju zajedničkog izvornog kreatora koji nije nužno osobni vlasnik. Importer
sada traži izričit odabir osobnog vlasnika, čuva imenovane izvorne boje i ne
pretplaćuje administratora uvoza. Na HFC-u je bez ponovnog uvoza uklonjeno 162
pogrešnih pretplata izvršitelja i ispravljeno 162 zadanih boja; ostalo je šest ranijih
pretplata i vlastiti uvezeni kalendar administratora. Događaji, semantičke ovlasti,
vlasnici/vrste i pretplate drugih korisnika ostali su isti. Izvještaji koriste
sadržajne kartice teme. Prije objave treba potvrditi preostala osobna vlasništva
i ponoviti završne provjere kandidata/kontejnera. Raniji potpuni rezultati PHP
matrice, cijelog testnog skupa i kontejnera ispod prethode ovim jutarnjim ispravcima.

- [x] R1 Uskladiti minimalne ovisnosti, opcionalni Calendar i CI pokrivenost.
- [x] R2 Provjeriti nadogradnju s 0.3.2 kroz CLI i sigurni FPM GUI uz očuvanje podataka.
- [x] R3 Izgraditi očišćeni image; provjeriti PostgreSQL, nepromjenjivi kod, trajne volumene, backup i jezike.
- [x] R4 Ponoviti provjere kvalitete na PHP-u 8.2–8.5 i puni integracijski skup nakon izmjena naslova/napretka.
- [x] G0 Provjeriti odabir osobni/timski; osobni kalendar traži izričito odabranog postojećeg lokalnog ili mapiranog/planiranog izvornog vlasnika.
- [x] G2 Dovršiti stvarni HFC uvoz i provjere izvještaja/sadržaja/vlasništva/pristupa/očuvanja bez upisa.
- [x] G1 Na otključanom Macu pregledati stvarni HFC pregled, korisnički/kalendarski izvještaj i izvještaj područja, novu uvezenu stranicu, očuvani osobni sadržaj, pretplate i administraciju kalendara. Završna potvrda ranijeg stvarnog uvoza i dalje je izvršena CLI-jem; izdvojeni preglednički test provjerava taj gumb bez ponovnog HFC uvoza.
- [ ] G3 Potvrditi preostale osobne vlasnike/vrste kalendara i osvježiti završnu provjeru izdanja nakon jutarnjih ispravaka.

Dopuna pripreme/birača vlasnika od 16. listopada dodaje automatsko praćenje pripreme
kalendara, faze/brojače napretka, prepoznavanje prekinutog postupka i pretragu postojećih
vlasnika paginiranu u bazi (najviše 25 rezultata po zahtjevu, uključujući odabrane
buduće račune). Izmjene ostaju u importeru: Auth i framework kod nisu mijenjani.
Priprema/pretraga ne izvršava novi stvarni HFC uvoz niti promjenu vlasništva/prava;
novi posao i dalje zahtijeva korisnički pregledane odabire.

Ne ponavljati dovršeni uvoz samo radi zatvaranja G1. Sačuvati razliku između
uspjelog izvršavanja u pozadini i preostale GUI provjere.

## Stvarni HFC prihvat — 16. listopada 2026.

Stvarni GUI prenio je izvorni ZIP i prihvatio zajednički pregled. Zaključani Mac
blokirao je završni nativni dijalog potvrde. Po korisničkoj uputi za samostalan
nastavak isti zaključani, pregledani odabir dovršen je uobičajenim aplikacijskim CLI
koordinatorom. Prije obrade sadržaja koristio je pravi HTTP URL kontekst `/hfc`.
To nije uspjeh cjelovitog GUI pokretanja.

| Uvezena stavka | Rezultat |
| --- | ---: |
| Mapirani izvorni identiteti / novi korisnici | 55 / 0 |
| Nove obične grupe / mapirane grupe | 8 / 1 |
| Nova članstva | 105 |
| Odabrana osobna područja | 30 |
| Nova područja / postojeći cilj uz dodavanje | 29 / 1 |
| Stranice | 346 |
| Osobni / timski kalendari | 41 / 122 |
| Uvezeni / izričito preskočeni događaji | 32.755 / 131 |
| Dovršeni detaljni izvještaji | 32 |

Nisu uvezena 103 neodabrana kalendara s nejasnim/nemapiranim osobnim vlasništvom.
Kod dva osobna područja istog vlasnika koristi se samo korisnički odabrani izvor.
Prošle su sve 43 provjere bez upisa:

- Točna brojanja novih objekata i očuvani početni ID-jevi.
- Svih 346 autora i zadnjih urednika dokumenata odgovara potvrđenim mapiranjima;
  344 reference zadnjeg urednika mapirane su izravno, a dvije koriste mapiranog autora.
- Svih 346 datotečnih dokumenata učitava se kroz Editor i nalazi u podacima područja.
- Svih 30 vlasnika osobnih područja odgovara; svih 163 kalendara odbija anonimni
  pristup i nema široki javni/prijavljeni pristup. Osobni vlasnici mogu čitati svoje.
- Dva duga izvorna naslova (najviše 706 znakova) i lokacija od 468 znakova sačuvani
  su u cijelosti unutar odobrenih granica od 765/510 znakova.
- Sažeci postojećih čvorova, dokumenata, početne stranice i ACL-a osobnog cilja ostali su jednaki.
- Vjerodajnice, SSO postavke, globalne administratorske oznake i Auth konfiguracija
  nisu promijenjeni. Nisu nastale nove obavijesti niti redovi izlazne e-pošte.
- Sve razriješene lokalne poveznice koriste `/hfc/`; izvorni ZIP-ovi i UUID-jevi izvještaja sačuvani su.

Izvještaji imaju **197 razriješenih / 656 nerazriješenih poveznica**, **83 stavke
pregleda** i **pet stavki vanjskih skripti koje čekaju odobrenje**. To ostaju vidljivi
rezultati pregleda, ne fatalne greške uvoza. Nedostajući ciljevi izvan odabranog
opsega ne izmišljaju se niti potajno uvoze. Od 784 izvorna članstva, 105 je bilo u
odobrenom mapiranom opsegu; 679 ostaje nerazriješeno umjesto stvaranja neodobrenih
identiteta/grupa.

Odabrane stvarne stranice nemaju ugradnji kalendara za aktiviranje. Aktivacija
ranijih stranica pokrivena je fixtureima stvarnih servisa. Izvorni ZIP izričito nema
binarnih privitaka, pa ova proba ne dokazuje njihov uvoz; taj put pokrivaju
nepromijenjeni E2E fixturei jednog područja.

Jedna referenca dokumenta starog osobnog cilja već prije uvoza nije bila čitljiva.
Sažeci njezina retka/reference ostali su jednaki. Nije popravljana ni brisana;
završno vidljivo stanje dio je preostalog GUI pregleda.

### Neuspjeli prvi pokušaj i novo ponavljanje

Prvi stvarni GUI pokušaj stao je na starom Calendar MySQL stupcu `title VARCHAR(255)`.
Po točnim ID-jevima uklonjeno je samo njegovih osam novih grupa, 105 članstava,
22 kalendara, 4.025 događaja, 27 tipova događaja i novih mapiranja importera.
Potvrđeni su nepromijenjeni sažeci/ID-jevi početnih redova. Neuspjeli izvještaji i
izvorne arhive sačuvani su; neuspjeli roditelj nije preimenovan u uspješan.

Calendar upravlja migracijom `resources/migrations/widen_calendar_event_text.php`;
Simbioza je poziva kroz `database/migrations/20261016130000_widen_calendar_event_text.php`.
Nova i nadograđena shema koriste `VARCHAR(765)` za naslov i `VARCHAR(510)` za
lokaciju. API/ICS i pregled izvora odbijaju/prijavljuju višak bez rezanja. Pristupačna
traka napretka prikazuje potvrđeni napredak trenutačnog koraka uz zaseban vidljivi
postotak; priprema/povezivanje bez mjerljivog ukupnog broja prikazuje se neodređeno.

Novo ponavljanje dovršeno je bez greške uvoza. Točni odabiri, sažeci prije/poslije,
novi ID-jevi i stabilni UUID-jevi izvještaja privatni su dokazi u ignoriranom
`build/release-preflight-20261015/evidence/`. Taj direktorij ne objavljivati.

## Aktualne lokalne provjere

### Jutarnji ispravci izvještaja i pretplata

Aktualne provjere kvalitete na PHP-u 8.5 prolaze za izdvojenu aplikaciju (283 testa /
6.377 provjera, tri preskakanja koja traže root), Importer (247 / 1.565) i Calendar
(92 / 726, tri preskakanja ovisna o okruženju). Prolaze PHPCS, Rector, PHPStan i
provjere dvojezičnosti/dokumentacije/prijevoda. Prolaze dva ciljana preglednička
scenarija: zajednički GUI uvoz, odvojeni izvještaji na karticama teme i nepromijenjen
uvoz jednog područja. Fixturei stvarnog Calendar API-ja prolaze za datotečni i
bazni HTML: provjeravaju izvorne boje, izostanak administratorske pretplate,
vlasničku pretplatu i nepromijenjene mapirane ovlasti. Potrošni fixture je uklonjen.

Stvarni HFC vizualni pregled obuhvaća zajednički, korisnički/kalendarski izvještaj
i izvještaj područja, uvezenu stranicu, očuvani osobni sadržaj, Moje kalendare i
administraciju kalendara. Ispravak čuva svih 32.755 uvezenih događaja, semantičke
kalendarske ovlasti, vlasnike/vrste i pretplate drugih korisnika. HFC i dalje ima
16 migracija i nijednu na čekanju; Auth/SSO/admin sažeci su isti, bez novih
obavijesti/pošte. Ovo je ciljana dodatna provjera, ne nova potpuna PHP/preglednička/
kontejnerska matrica. Raniji dokazi matrice ispod prethode ovim ispravcima.

Sve provjere kvalitete `composer on-commit` prolaze na PHP-u 8.2–8.5, uključujući
PHPCS, Rector, PHPStan i pripadajuće provjere dokumentacije/prijevoda.

| Skup | Testovi | Provjere | Uvjetna preskakanja |
| --- | ---: | ---: | ---: |
| Aplikacija | 283 | 6377 | 3 samo pod rootom |
| Confluence Import | 242 | 1509 | 0 |
| Calendar | 91 | 714 | 3 ovisna o okruženju |

Tri aplikacijska root slučaja zasebno prolaze pod Linux rootom: tri testa / 43
provjere. Calendar migracija popunjene stare sheme zasebno prolazi na SQLiteu,
MySQL-u 8.4 i PostgreSQL-u 18 (šest provjera po driveru), uz ponovljeno izvršavanje,
očuvane vrijednosti/ID-jeve i pune Unicode vrijednosti od 765/510 znakova.
HFC ima **16 izvršenih / nijednu migraciju na čekanju**.

Aktualni puni `php scripts/run_e2e.php --local --keep` prolazi **81/81
browser/API/performance testova u 23,2 minute**, uz nepromijenjeni samostalni
ACL/privitke/pretragu i novu vidljivu traku napretka. Raniji neuspjeli pokušaji
ostaju zabilježeni: regresija izvornog konteksta ugrađene pretrage ispravljena je bez
slabljenja izolacije izvora, a utrka testne navigacije pri čišćenju čekanjem
reloada. Ti pokušaji ne prikazuju se kao uspješni.

Fixturei stvarnih servisa pokrivaju HTML na filesystemu/u bazi, osobni uvoz
dodavanjem, ponavljanje bez prepisivanja/duplikata, zastarjele preglede, istodobne/
nastavljene zahtjeve, autorstvo budućeg korisnika, dvosmjerne poveznice i naknadnu
aktivaciju kalendara. Zasebni izvještaji zadržavaju UUID dok se brojanja spremljenih
poveznica osvježavaju; miješani samostalni/site izvori povezuju se samo preko
potvrđene iste izvorne instance. Privremeni lokalni odabiri područja/roditelja
isključeni su iz prenosivih backupa.

## Dokazi nadogradnje, ovisnosti i containera

Ranije stvarne CLI i sigurna FPM GUI nadogradnja krenule su s aplikacijom 0.3.2,
objavljenim modulima 0.3.0 i 14 migracija. Završile su na tada aktualnom privatnom
kandidatu s 15 migracija, očuvanim Auth/privatnim configom, izmišljenim privitkom,
SQL testnim retkom i isključenim održavanjem. Nakon migracije naslova osvježeni
PostgreSQL image završava sa 16 migracija bez čekanja i istim sačuvanim stanjem.
Prvi dokaz s 15 migracija povijestan je, ne tvrdnja da je pokrio kasniju migraciju naslova.

Linux systemd/FPM/deploy provjere pronašle su i popravile prazan argument
`BindReadOnlyPaths` i politiku dodatnih slojeva koja je deploy workeru pogrešno
postavila isporučeni `config/` kao read-only. Zasebna deploy politika dopušta
nadogradnju koda uz očuvanje zaštite FPM-a/privatnih slojeva. Deset regresija
generatora / 39 provjera prolazi na PHP-u 8.2–8.5. Pogođene izvorne Linux helper
instalacije 0.3.2 trebaju prvu nadogradnju kao stvarni deploy/vlasnik koda pa ponovno
generiranje helpera; to ne znači da stari helper radi bez promjene.

Kandidat traži Calendar/User `^0.3.1`; Importer traži User `^0.3.1` i odbija
instalirani Calendar stariji od `0.3.1`, ali Calendar nije obavezan. Čista proba
samog importera prolazi bez Calendara. Očišćeni izvoz imagea koristi objavljeni
Framework `^0.0.25`, ne nepovezano izmijenjeno razvojno stablo, te razrješava
19 stabilnih paketa. Nakon odobrenja objaviti User/Calendar, zatim Importer i
aplikaciju; nepromijenjeni moduli ostaju `0.3.0`.

Prošli su nativna ARM64/PostgreSQL instalacija, aplikacijska bazna uloga bez superuser
prava, nepromjenjivi aplikacijski kod, backup/područje/jezični povrati i zamjena
imagea. Runtime i bazni volumeni zadržavaju jednake sažetke konfiguracije, privitka
i SQL retka. Lokalni image je `simbioza:0.3.3-preflight-workflow`, ID
`sha256:4bff09873fdea31491c00f8cddffb0a91f52a5d9734d7c88b0ecc885643b851f`.

### Preostali sigurnosni nalaz imagea

CycloneDX 1.7 popis sastavnica ima 166 komponenti. Sačuvani lokalni sken ima
**nula popravljivih HIGH/CRITICAL nalaza**, ali i dalje 91 HIGH, jedan CRITICAL,
131 MEDIUM, 198 LOW i četiri UNKNOWN nalaza bez ponuđenog popravljenog paketa za
HIGH/CRITICAL. Kritični `CVE-2026-6653` je u distribucijskom `libxml2`; nije
skriven. [Debianov sigurnosni pregled](https://security-tracker.debian.org/tracker/CVE-2026-6653)
također označava instalirani Trixie paket ranjivim, ali problem ocjenjuje manjim i
ne planira zasebnu objavu. Nijedna ocjena sama ne dokazuje ranjivost ili zaštićenost
konkretnog XML puta importera. Preostali rizik izričito razmotriti prije odobrenja
izdanja i sačuvati cijeli JSON izvještaj.

## Preostalo za nastavak

- Dovršiti završnu nativnu potvrdu/vizualnu HFC provjeru na otključanom Macu.
  Dovršeni uvoz ne duplicirati niti zamijeniti sačuvani osobni sadržaj.
- Korisničko odobrenje ostaje uvjet objave. Udaljeni CI, javne datoteke izdanja,
  nativni AMD64 CI i objavljeni višeplatformski registar ne proizlaze iz lokalnih provjera.
- Sačuvati privatne dokaze i izvorne arhive. Zaustaviti izdvojene testne servise dok
  se čeka interaktivni prihvat; uklanjati samo točno određene potrošne fixturee,
  nikada HFC uvezene podatke niti korisničke arhive.

Izdvojeni Docker aplikacija/baza i sigurni FPM servis su zaustavljeni. Nakon
uspješnih provjera uklonjena su dva potrošna MySQL/PostgreSQL containera za
migraciju naslova i njihovi anonimni testni volumeni. HFC i dalje radi; njegovi
uvezeni objekti, izvorne arhive i izvještaji nisu uklonjeni.
