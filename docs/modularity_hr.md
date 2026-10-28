# Odabir funkcionalnosti Simbioze

[Engleska verzija](modularity_en.md)

Simbioza povezuje stranice, Wiki, kalendare, ankete i suradnju u jednoj cjelovitoj aplikaciji. Ako trebate samo dio tih mogućnosti, modularnost HeartPhrame frameworka omogućuje i užu instalaciju. Sljedeći predlošci opisuju tu dodatnu mogućnost.

Riječ **simbioza** znači zajednički život različitih organizama. Aplikacija je
tako nazvana jer njezini moduli imaju različite uloge, ali mogu raditi zajedno:
stranica objašnjava događaj iz kalendara, Wiki povezuje znanje tima, a anketa
prikuplja povratne informacije. Cjelovita Simbioza povezuje sve te alate,
zadatke, komentare, obavijesti i integracije u jedno okruženje.

## Odaberite namjenu

| Što trebate | Predložak u instalaciji | Funkcionalni moduli |
| --- | --- | --- |
| Sve dostupne funkcionalnosti zajedno | Cijela Simbioza | Svi opcionalni moduli |
| Jednostavan web s HTML stranicama | HTML stranice | HTML Editor |
| Događaje, sastanke i CalDAV | Kalendar | Calendar |
| Wiki s područjima i pretraživanjem | Wiki | Workspace, Workspace Search i Simbioza User |
| Ankete s pitanjima, grananjem i rezultatima | Ankete | Survey |

Svaka instalacija uključuje **Auth, ORM, Menu, Theme, Backup i Audit**. Oni
osiguravaju prijavu i ovlasti, rad s bazom, izbornike, izgled, sigurnosne kopije
i dnevnike aktivnosti. „Samostalno” znači bez ostalih poslovnih modula, a ne
bez zajedničkog temelja i nužnih ovisnosti.

Instalacijski program automatski dodaje potrebne ovisnosti. Primjerice, Survey
koristi HTML Editor za uvodni i objašnjavajući sadržaj; Wiki također treba
Editor, a Simbioza User dodaje obavijesti. API, pristupačnost i ostale
opcionalne integracije nisu razlog za instaliranje nepovezanih funkcionalnosti.

## Instalirajte odabrani alat

1. Pripremite novu instalaciju prema odgovarajućim [instalacijskim uputama](installation_hr.md).
2. U čarobnjaku, u koraku s postavkama aplikacije, odaberite **Predložak
   instalacije** i kliknite **Primijeni predložak**.
3. Pregledajte označene module. Potrebne ovisnosti ostaju uključene; dodatne
   mogućnosti odaberite samo ako ih trebate.
4. Odaberite jezike instalacije i dovršite instalaciju. Jezici sadržaja i
   prijevoda biraju se među instaliranim, aktivnim jezicima.
5. Prijavite se kao administrator, izradite vlastiti sadržaj i u izborniku
   dodajte poveznice kojima će posjetitelji do njega doći.

Konačni rezultat je jedna aplikacija s odabranom namjenom. Ogledne stranice,
kalendari i ankete nisu poslovni podaci koji se automatski isporučuju svakom
novom siteu; svoj sadržaj izrađujete nakon instalacije.

## Podijelite samostalni HTML web

HTML web ne treba Wiki za više autora ili privatne stranice. Administrator u
**Postavke → HTML editor → Ovlasti stranica** dodaje korisnike ili grupe.
**Dodavanje** dopušta stvaranje i uređivanje vlastitih stranica. **Uređivanje**
dopušta upravljanje svim samostalnim stranicama, uključujući privatne, ali ne
daje pristup administratorskim postavkama aplikacije.

Na pojedinoj stranici otvorite **Postavke i prava stranice**. Odaberite javno
čitanje, sve prijavljene korisnike ili samo određene korisnike i grupe. Odabir
štiti i privitke, izravne poveznice te API pristup. U Wikiju i dalje koristite
postojeće ovlasti područja i stranica.

## Proširite postojeću instalaciju

Primjerice, HTML webu možete dodati kalendar ili ankete, a Wikiju ankete,
zadatke i komentare. U **Postavke → Moduli i provjere** vidite što je
instalirano, što je uključeno i koje su ovisnosti potrebne.

Na pravilno podešenom namjenskom FPM-u administrator može kliknuti
**Instaliraj** uz dodatni modul. Kod mod_php-a i običnog FPM-a održavatelj to
radi iz korijena aplikacije preko CLI-ja, svojim računom za održavanje:

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

Naredbe su odvojeni primjeri: odaberite modul koji vam treba. Sustav dodaje
njegove nužne ovisnosti. Za dodatne zadatke i komentare koristite `task` i
`comment`; Wiki može naknadno dobiti `confluence-import` za uvoz postojećeg
sadržaja. Nakon proširenja podesite ovlasti, izbornike i postavke novog modula.
Instaliranje modula samo po sebi ne daje svim korisnicima pravo uređivanja.

Prije promjene sastava napravite sigurnosnu kopiju. Isključivanje opcionalnog
modula nije isto što i deinstalacija: isključivanje zadržava podatke, dok
deinstalacija uklanja njegove tablice i paket uz pripremu kopije za oporavak.
Šest obaveznih modula ne može se isključiti ili deinstalirati.

### Dodavanje Područja postojećem HTML webu

Dodavanje Područja ne premješta postojeće HTML stranice. Zadržavaju adrese,
sadržaj, prijevode, privitke i prava samostalnih stranica. HTML stranice izvan
područja i stranice u područjima mogu se koristiti zajedno, uključujući preko API-ja.

Samo administrator može postojeću stranicu premjestiti u područje:

1. Otvorite **Postavke → HTML editor → Stranice izvan područja**.
2. Pronađite stranicu i odaberite **Premjesti u područje**.
3. Otvorite popis odredišta ili pretražite naziv. Dodatna područja učitavaju se pomicanjem popisa.
4. Provjerite vidljivost odredišta, potvrdite njegova prava i premjestite stranicu.

Rezultat je jedna stranica na vrhu stabla odredišnog područja. Premještaju se svi
prijevodi, povijest verzija i verzije privitaka. Datoteke prelaze u pohranu područja;
HTML u bazi ostaje u istim zapisima verzija, uz novo vlasništvo područja. Poveznice
u spremljenim stranicama se popravljaju, uključujući jezik i sidra, a postojeće
adrese stranice i privitaka ostaju upotrebljive uz provjeru novih prava pristupa.
Objavljene verzije ostaju objavljene, a radni nacrti ostaju nacrti.

**Prava odredišta zamjenjuju pristup samostalnoj stranici.** Dosadašnji čitatelji
ne dodaju se automatski kao iznimke. Javna stranica u privatnom području može
postati ograničena, a privatna stranica u javnom području može postati javna u
objavljenim verzijama. Provjerite odredište prije potvrde. Ako trebate mogućnost
povratka, prethodno napravite backup: ovaj ekran ne nudi obrnuto premještanje.

## Nadogradnja i sigurnosne kopije

Nove instalacije dobivaju zasebne kolačiće prijave, čak i kada više instalacija
dijeli istu domenu. Vraćanje kopije čuva lokalni sesijski identitet odredišne
instalacije umjesto preuzimanja identiteta kolačića druge instalacije.

Namjenski FPM s ispravnim ograničenim helperom podržava nadogradnju kroz
**Postavke → Moduli i provjere → Pokreni nadogradnju**. Samo postojanje FPM-a
nije dovoljno: početna provjera mora potvrditi mogućnost GUI nadogradnje.

Za ostale klasične instalacije održavatelj iz korijena aplikacije pokreće:

```bash
php update.php --check --lang=hr
php update.php --lang=hr
```

Nadogradnja čuva odabir modula i postojeće podatke. Novi opcionalni modul
postaje dostupan za instalaciju, a ne uključuje se sam od sebe. Potpuna
sigurnosna kopija obuhvaća podatke i prenosive postavke uključenih modula;
čuvajte i zasebnu kopiju konfiguracije poslužitelja i lokalnih tajni, koje se
ne prenose poslovnim backupom.

## Docker

Docker koristi isti izbor funkcionalnosti. Ako image već sadrži sve module,
u instalaciji odaberite i uključite samo potrebne. Za manji vlastiti image
gradite paket s odabranim modulima i njihovim nužnim ovisnostima. Modul koji
nije ugrađen u image dodaje se gradnjom zamjenskog imagea, ne izmjenom koda u
aktivnom kontejneru. Nadogradnja se provodi zamjenom imagea, uz očuvane bazu i
runtime podatke. Postupak je u [Docker uputama](docker_hr.md).

## Upute i povezivanje s drugim sustavima

S Wikijem su ove stranice u području **Korisničke upute**. Bez Wikija iste se
upute čitaju kroz stavku **Upute**, s popisom poglavlja i navigacijom; nije
potrebno instalirati Wiki samo zbog priručnika.

API se može dodati bilo kojoj namjeni. API ključ određuje dopuštene pozive,
ali ne zaobilazi ovlasti korisnika kojem pripada. Dostupne rute i opsezi prate
instalirane i uključene module. To omogućuje povezivanje s drugim sustavima i
razvoj prilagođenih aplikacija na HeartPhrameu bez ponovne izrade prijave,
navigacije, pristupa podacima i ostalih zajedničkih temelja.
