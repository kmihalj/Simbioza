# Prijava i korisnici

Ove upute vode administratora kroz sve postavke prijave, korisničke atribute, grupe, korisnike, lokalnu registraciju, preuzimanje korisnika, API ključeve, SSO providere te osobnu sigurnost i pristup.

<div class="alert alert-info">

**Primjeri su izrađeni na čistoj SQLite instalaciji.** Prikazani računi, grupe i ključevi služe samo za dokumentaciju. Tajne vrijednosti i osobni atributi uklonjeni su sa screenshotova. U produkciji koristite vlastite nazive, domene, rokove važenja i načelo najmanjih ovlasti.

</div>

## Sadržaj

1.  [Postavke prijave](#postavke-prijave)
2.  [Atributi korisnika](#atributi)
3.  [Grupe](#grupe)
4.  [Korisnici i lokalni računi](#korisnici)
5.  [Lokalna prijava i registracija](#lokalna-prijava)
6.  [Preuzimanje korisnika](#preuzimanje)
7.  [API ključevi](#api-kljucevi)
8.  [Zajednički postupak za SSO](#sso)
9.  [SAML](#saml)
10. [SAML proxy](#saml-proxy)
11. [CAS](#cas)
12. [OIDC](#oidc)
13. [OAuth2](#oauth2)
14. [Osobni profil: sigurnost i pristup](#osobni-profil)
15. [Provjera i sigurnosne preporuke](#provjera)

<a id="postavke-prijave"></a>

## 1. Postavke prijave

Otvorite **Postavke → Korisnici → Postavke prijave**. Ovdje se uključuju načini prijave za cijelu aplikaciju. Uključivanje providera samo omogućuje njegovu konfiguraciju i prikaz u meniju; svaki konkretan profil mora biti zasebno spremljen, aktiviran, testiran i ispravno mapiran.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/54a2833a-530a-4ecf-a71e-9b4fc41abec9.png" class="img-fluid rounded border" alt="Pregled postavki prijave" /><span class="figure-caption d-block">Pregled postavki prijave</span></span>
<figcaption>Postavke prijave na čistoj instalaciji.</figcaption>
</figure>

<figure class="my-4">

<figcaption><span>Što znače glavne postavke</span><br />
</figcaption>
</figure>

- **Lokalna prijava** omogućuje prijavu korisničkom oznakom i lozinkom iz Simbioze.
- **SimpleSAMLphp, CAS, OIDC i OAuth2** uključuju odgovarajuće skupine SSO profila.
- **Break-glass lokalna prijava** ostavlja zasebnu, izravnu administratorsku adresu kada se redovna lokalna prijava isključi. Taj račun zaštitite snažnom jedinstvenom lozinkom i koristite samo u nuždi.
- **Trajanje prijave** određuje dopuštenu neaktivnost u danima (1–365); rok se obnavlja dok je prijavljeni korisnik aktivan. Kraći rok je sigurniji za administrativna i javna računala.

<div class="alert alert-warning">

**„Profil aktivan” nije isto što i zelena kvačica.** Aktivna kvačica znači da se profil smije koristiti. Zelena kvačica i poruka *Testirano i operativno* pojavljuju se tek nakon uspješnog testa veze i ispravnog mapiranja obaveznih atributa. Profil može biti aktivan, a ipak neoperativan.

</div>

<a id="atributi"></a>

## 2. Atributi korisnika

Otvorite **Atributi korisnika**. Model obično već sadrži e-mail, ime i prezime. Dodatni atribut dodajte kada aplikacija treba pohraniti podatak koji nije dio osnovnog korisničkog profila, primjerice ustanovu, odjel, broj zaposlenika ili ulogu iz vanjskog imenika.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/72365dbd-0ddf-4304-96dd-b775ee5add6e.png" class="img-fluid rounded border" alt="Zadani korisnički atributi" /><span class="figure-caption d-block">Zadani korisnički atributi</span></span>
<figcaption>Zadani atributi korisnika na čistoj instalaciji.</figcaption>
</figure>

### Dodavanje atributa

1.  Unesite stabilnu programsku oznaku, malim slovima i bez razmaka, primjerice `affiliation`.
2.  Unesite odvojeni hrvatski i engleski naziv koji će korisnici vidjeti.
3.  Odaberite vrstu podatka te zasebno postavite **Obavezno**, **Registracija**, **Profil** i **Aktivno**.
4.  Spremite atribut. Novo polje pojavljuje se u korisničkim profilima i u tablici mapiranja svakog SSO profila.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/d0cd516c-bca3-4f35-9c8d-6bc408e97b41.png" class="img-fluid rounded border" alt="Primjer dvojezičnog atributa" /><span class="figure-caption d-block">Primjer dvojezičnog atributa</span></span>
<figcaption>Tehničko polje affiliation ima nazive Ustanova / Institution, vidljivo je u profilu i SSO mapiranju, ali nije uključeno u samostalnu registraciju.</figcaption>
</figure>

**Mapiranje** povezuje polje modela Simbioze s nazivom atributa koji vraća provider. Nazivi su osjetljivi na točan zapis: `mail`, `givenName` i `given_name` nisu ista polja. Najprije testirajte profil i pročitajte stvarno vraćene nazive, a tek zatim ih upišite u mapiranje. SSO mapiranje ne dodaje polje automatski u javni obrazac registracije.

<div class="alert alert-info">

**Koja polja ulaze u samostalnu registraciju?** To bira administrator za svaki atribut. **Registracija** određuje prikazuje li se polje u javnom obrascu, a **Obavezno** zabranjuje praznu vrijednost u kontekstima u kojima se polje koristi. Ako su uključene obje postavke, polje je obavezno pri samostalnoj registraciji. Obavezni atribut mora imati i valjano SSO mapiranje da bi test profila bio operativan. Polje `affiliation` u ovom primjeru služi za mapiranje ustanove providera i prikaz u profilu, pa je namjerno isključeno iz registracije.

</div>

<a id="grupe"></a>

## 3. Grupe

Grupe okupljaju korisnike i nose skup prava. Administratorska grupa je sistemska i ne treba je koristiti kao običnu poslovnu grupu. Za odjele, projekte ili uloge izradite zasebne grupe s najmanjim potrebnim skupom prava.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="assets/group-list_hr.jpg" class="img-fluid rounded border" alt="Popis sistemskih grupa" /><span class="figure-caption d-block">Popis sistemskih grupa</span></span>
<figcaption>Sistemske grupe na čistoj instalaciji.</figcaption>
</figure>

### Primjer nove grupe

1.  Odaberite **Dodaj grupu** (gumb s plusom), unesite prepoznatljiv **Naziv grupe** i provjerite kućicu **Aktivno**.
2.  Odaberite **Spremi grupe**. Na ovom se ekranu ne unose opis ni prava na sadržaj.
3.  U detaljima korisnika dodajte ili uklonite članstvo u grupi.
4.  Potrebna prava dodijelite toj grupi u postavkama područja, stranice, kalendara ili ankete. Za SSO korisnike članstvo se može određivati i pravilima mapiranog atributa.

**Rezultat:** grupa postoji i može se odabrati pri dodjeli ovlasti. Samo stvaranje grupe ne otvara sadržaj njezinim članovima.

Za privremeno isključivanje nesistemske grupe uklonite kvačicu **Aktivno** i spremite. Za trajno brisanje odaberite **Obriši grupu**. Na posebnom ekranu provjerite naziv grupe i prikazane posljedice za članstva i pravila mapiranja, zatim odaberite **Potvrdi brisanje** ili odustanite. Korisnički računi ostaju. Prazan naziv više ne briše grupu. Prije potvrde provjerite gdje se grupa koristi za pristup sadržaju. Nova grupa istog imena dobiva drugi identitet i ne vraća stara članstva ni ovlasti. Sistemske grupe ne mogu se obrisati.

<figure class="my-4">
<img src="assets/group-deletion_hr.jpg" class="img-fluid rounded border" alt="Prije potvrde provjerite naziv grupe i posljedice. Odustani vraća popis bez brisanja." />
<figcaption>Prije potvrde provjerite naziv grupe i posljedice. Odustani vraća popis bez brisanja.</figcaption>
</figure>

<a id="korisnici"></a>

## 4. Korisnici i lokalni računi

Otvorite **Postavke → Korisnici → Korisnici** da biste pronašli račun, provjerili njegovo stanje te uredili članstvo i pristup.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/6358a0f3-4366-41a2-b778-de4056b7862d.png" class="img-fluid rounded border" alt="Responzivni popis korisnika" /><span class="figure-caption d-block">Responzivni popis korisnika</span></span>
<figcaption>Popis korisnika s uravnoteženim širinama stupaca.</figcaption>
</figure>

### Stvaranje lokalnog korisnika

1.  Odaberite **Novi korisnik**.
2.  Unesite jedinstvenu login oznaku, ime za prikaz i e-mail.
3.  Odaberite lokalni izvor prijave i dodijelite privremenu snažnu lozinku.
4.  Uključite obaveznu promjenu lozinke pri prvom loginu.
5.  Dodijelite samo potrebne grupe; administratorska ovlast ne smije biti zadana.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/883515df-80ab-4e25-8e81-2a3600ea1b19.png" class="img-fluid rounded border" alt="Obrazac za lokalnog korisnika" /><span class="figure-caption d-block">Obrazac za lokalnog korisnika</span></span>
<figcaption>Primjer obrasca za stvaranje lokalnog korisnika bez prikazane lozinke.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/8c733e20-8475-4f9e-ab16-232ec568657b.png" class="img-fluid rounded border" alt="Novi korisnik na popisu" /><span class="figure-caption d-block">Novi korisnik na popisu</span></span>
<figcaption>Dokumentacijski korisnik nakon stvaranja.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/1df5c610-a6b4-4053-8e6c-ab04e36c131f.png" class="img-fluid rounded border" alt="Detalji korisnika" /><span class="figure-caption d-block">Detalji korisnika</span></span>
<figcaption>Detalji računa, povezani provideri i članstva u grupama.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/f1f48b8a-8b97-4676-bca0-cbf3624dc7c9.png" class="img-fluid rounded border" alt="Obavezna promjena lozinke" /><span class="figure-caption d-block">Obavezna promjena lozinke</span></span>
<figcaption>Privremena lozinka vodi na obaveznu promjenu prije nastavka rada.</figcaption>
</figure>

U detaljima računa provjerite je li korisnik aktivan, koji je izvor prijave, koje su vanjske prijave povezane i kojim grupama pripada. Deaktivacija je sigurniji prvi korak od brisanja jer čuva trag aktivnosti. Za vanjske račune ne mijenjajte ručno stabilni identifikator providera.

<a id="lokalna-prijava"></a>

## 5. Lokalna prijava i registracija

U **Lokalnoj prijavi** određujete smiju li se korisnici sami registrirati. Za zatvorene sustave registraciju ostavite isključenom i račune stvarajte administrativno ili kroz provjereni SSO. Uključena registracija odmah stvara aktivan običan račun i prijavljuje korisnika. Ne šalje poruku za potvrdu e-maila niti dokazuje vlasništvo unesene adrese. Za zatvoreni sustav koristite račune koje stvara administrator ili provjereni SSO. Prije prihvata registracija objavite odgovarajuću obavijest o privatnosti. Javni obrazac prikazuje samo korisničke atribute kojima je u **Atributima korisnika** uključena postavka **Registracija**; SSO-only polje *Ustanova* zato se ovdje ne prikazuje.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/ce8ea6c0-f452-4033-a5f4-19d146278859.png" class="img-fluid rounded border" alt="Registracija isključena" /><span class="figure-caption d-block">Registracija isključena</span></span>
<figcaption>Preporučeno početno stanje za zatvorenu aplikaciju.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/0c67320d-8de3-4717-9760-80780a44d122.png" class="img-fluid rounded border" alt="Registracija uključena" /><span class="figure-caption d-block">Registracija uključena</span></span>
<figcaption>Registracija uključena samo za kontrolirani primjer.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/bd8db205-cfc3-4bff-86dd-4d5861239c10.png" class="img-fluid rounded border" alt="Poveznica za registraciju" /><span class="figure-caption d-block">Poveznica za registraciju</span></span>
<figcaption>Ekran prijave prikazuje poveznicu tek kada je registracija dopuštena.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/3f1816f5-b913-4f01-977a-cac3387ae3cf.png" class="img-fluid rounded border" alt="Obrazac registracije" /><span class="figure-caption d-block">Obrazac registracije</span></span>
<figcaption>Javni obrazac s odabranim registracijskim poljima; atribut Ustanova iz SSO mapiranja namjerno je skriven.</figcaption>
</figure>

Nakon demonstracije vratite željeno produkcijsko stanje. Lozinke moraju biti jedinstvene, dovoljne duljine i po mogućnosti spremljene u upravitelju lozinki. Administrator nikada ne treba tražiti korisnikovu postojeću lozinku.

<a id="preuzimanje"></a>

## 6. Preuzimanje korisnika

**Preuzmi korisnika** privremeno otvara aplikaciju u kontekstu odabranog korisnika. Služi dijagnostici ovlasti i prikaza, ne radu umjesto korisnika.

1.  Odaberite korisnika i zabilježite razlog u internom postupku podrške.
2.  Potvrdite preuzimanje. Aplikacija jasno označava da je impersonacija aktivna.
3.  Provjerite samo prijavljeni problem; ne mijenjajte poslovne podatke bez odobrenja.
4.  Odmah odaberite povratak na administratorski račun.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/942b6253-d6f4-49e1-a58c-a1f760579170.png" class="img-fluid rounded border" alt="Odabir korisnika za preuzimanje" /><span class="figure-caption d-block">Odabir korisnika za preuzimanje</span></span>
<figcaption>Administrator odabire račun za kontroliranu dijagnostiku.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/d6789ae4-5fff-401f-9c11-380967351acf.png" class="img-fluid rounded border" alt="Aktivno preuzimanje korisnika" /><span class="figure-caption d-block">Aktivno preuzimanje korisnika</span></span>
<figcaption>Jasna oznaka da se radi u kontekstu drugog korisnika.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/bbfdf33d-0fa4-4a11-8c7e-aa5c32cc6075.png" class="img-fluid rounded border" alt="Povratak administratoru" /><span class="figure-caption d-block">Povratak administratoru</span></span>
<figcaption>Radnju završite povratkom na svoj administratorski račun.</figcaption>
</figure>

<div class="alert alert-warning">

Preuzimanje ne testira stvarnu SSO autentikaciju, MFA ni pravila providera. Za to koristite test profila ili zaseban testni račun.

</div>

<a id="api-kljucevi"></a>

## 7. API ključevi

Ovi se ekrani prikazuju kada je API modul instaliran i uključen. API ključ predstavlja korisnika koji ga je dobio. Dopušteni opsezi mogu samo suziti njegove postojeće ovlasti; ne mogu mu dati prava koja vlasnik nema. Za svaku integraciju koristite zaseban ključ, opisnu oznaku, najkraći potreban rok i najmanji skup opsega.

### Izravno izdavanje ključa

1.  Odaberite vlasnika i samo potrebne opsege.
2.  Postavite datum isteka i, prema potrebi, dopuštenu IP adresu ili mrežu.
3.  Stvorite ključ i odmah kopirajte tajnu u sigurno spremište.
4.  Tajna se više ne prikazuje. Ako je izgubljena, opozovite ključ i izdajte novi.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/023dde98-eac3-4f0f-9b9b-cb1bae93f5a5.png" class="img-fluid rounded border" alt="API ključ s najmanjim ovlastima" /><span class="figure-caption d-block">API ključ s najmanjim ovlastima</span></span>
<figcaption>Primjer ograničenog API ključa prije stvaranja.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/28a6d5be-d46f-4a15-aa5f-6d22641ce4c0.png" class="img-fluid rounded border" alt="Jednokratna API tajna" /><span class="figure-caption d-block">Jednokratna API tajna</span></span>
<figcaption>Tajna je namjerno uklonjena sa screenshota.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/85db90db-eb9e-462e-8dd4-2b800921e361.png" class="img-fluid rounded border" alt="Aktivni API ključevi" /><span class="figure-caption d-block">Aktivni API ključevi</span></span>
<figcaption>Popis aktivnih ključeva za reviziju i opoziv.</figcaption>
</figure>

### Zahtjev korisnika i odobrenje administratora

Korisnik može u osobnom profilu zatražiti ključ i obrazložiti namjenu. Administrator pregleda vlasnika, opsege, rok i razlog te zahtjev odobri ili odbije. Nakon odobrenja korisnik u osobnom profilu može jednom preuzeti tajnu. Ako je modul Obavijesti uključen, do tog ga ekrana vodi i obavijest u aplikaciji. Tajna se ne šalje u obavijesti ili e-mailu.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/3a2fa1f8-06c9-439d-92fa-d415541366ee.png" class="img-fluid rounded border" alt="Zahtjev za API ključ" /><span class="figure-caption d-block">Zahtjev za API ključ</span></span>
<figcaption>Korisnik traži ograničeni ključ i navodi namjenu.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/1bb48303-71fa-4cdf-a35a-6fd37e682e80.png" class="img-fluid rounded border" alt="Poslan zahtjev" /><span class="figure-caption d-block">Poslan zahtjev</span></span>
<figcaption>Zahtjev čeka administratorsku odluku.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/d9c84f55-cf37-412c-af31-ee42ccb66267.png" class="img-fluid rounded border" alt="Zahtjev na čekanju" /><span class="figure-caption d-block">Zahtjev na čekanju</span></span>
<figcaption>Administrator provjerava detalje prije odobrenja.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/321c28b7-8f5f-491f-8a64-63f65f0d917d.png" class="img-fluid rounded border" alt="Odobren API zahtjev" /><span class="figure-caption d-block">Odobren API zahtjev</span></span>
<figcaption>Odobrenje stvara ključ bez otkrivanja tajne administratoru.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/9013534f-d509-464f-af75-5d35cde338fb.png" class="img-fluid rounded border" alt="Obavijest o API ključu" /><span class="figure-caption d-block">Obavijest o API ključu</span></span>
<figcaption>Korisnik dobiva obavijest o odluci.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/ed936842-72eb-47ef-bb7f-609323a15c69.png" class="img-fluid rounded border" alt="Odobren zahtjev korisnika" /><span class="figure-caption d-block">Odobren zahtjev korisnika</span></span>
<figcaption>Obavijest vodi na jednokratno preuzimanje tajne.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/937ca36c-fe6e-4b45-adfb-0ce466362d20.png" class="img-fluid rounded border" alt="Jednokratno preuzimanje API tajne" /><span class="figure-caption d-block">Jednokratno preuzimanje API tajne</span></span>
<figcaption>Tajna je uklonjena sa slike i nakon zatvaranja se ne može ponovno prikazati.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/a8a42032-6e56-430e-8210-c6d9117adcd3.png" class="img-fluid rounded border" alt="Dovršen API zahtjev" /><span class="figure-caption d-block">Dovršen API zahtjev</span></span>
<figcaption>Dovršen zahtjev ostaje evidentiran bez otkrivanja tajne.</figcaption>
</figure>

<a id="sso"></a>

## 8. Zajednički postupak za SSO profile

Za svaki SSO profil koristite isti sigurni slijed:

1.  Registrirajte aplikaciju kod providera i upišite samo nužne endpointove, Client ID i tajnu.
2.  Spremite profil kao aktivan. Tajna se pri kasnijem uređivanju ne prikazuje; prazno polje zadržava postojeću tajnu.
3.  Prvi put odaberite **Testiraj profil** bez nagađanja mapiranja. Nakon stvarne prijave prikazat će se zadnje dohvaćeni atributi.
4.  Prema vraćenim nazivima upišite atribut subjekta, atribut za prijavu, prikazno ime i korisnička polja.
5.  Ponovno testirajte. Tek poruka **Testirano i operativno** i zelena kvačica potvrđuju cjelovit profil.

### Dopuštene domene

Popis ograničava prijavu na organizacijske domene. Jedna domena ide u svaki red ili se koristi popis odvojen zarezima. Vrijednost `domena.hr` dopušta tu domenu i njezine poddomene; kraći sufiks poput `hr` dopušta sve identifikatore čija domena završava s `.hr`. Prazan popis ne ograničava prijavu.

**Atribut koji sadrži domenu** mora biti stvarni atribut providera s vrijednošću oblika `korisnik@domena` ili samom domenom. Obavezan je samo kada je popis dopuštenih domena popunjen. Ne oslanjajte se na prikazno ime ili drugi promjenjivi atribut.

### Javne povratne adrese

Provider mora vratiti preglednik na javnu adresu iste instalacije. Za aplikaciju u poddirektoriju uključite i poddirektorij, primjerice `https://simbioza.example.org/simbioza`. To je osnovna javna adresa koju korisnički preglednik vidi, a ne interna adresa PHP-FPM-a, kontejnera ili baze. Konkretne callback adrese prikazane na profilu registrirajte točno, bez preusmjeravanja na drugu instalaciju.

<a id="saml"></a>

## 9. SAML

SAML profil ovisi o zasebno instaliranom i ispravno podešenom **SimpleSAMLphp**. Prije konfiguracije Simbioze moraju raditi njegov autoload, SP auth source, certifikati, metadata i povratne adrese. Putanja do `vendor/autoload.php` i naziv auth sourcea moraju odgovarati stvarnoj instalaciji.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/56d48fbb-0696-4526-9968-9ed6f1558cf6.png" class="img-fluid rounded border" alt="Početna SAML konfiguracija" /><span class="figure-caption d-block">Početna SAML konfiguracija</span></span>
<figcaption>Profil je spremljen, ali mapiranje još nije potvrđeno.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/8fecbf47-8633-4f20-bb1a-a8c600c526a5.png" class="img-fluid rounded border" alt="Prvi SAML test" /><span class="figure-caption d-block">Prvi SAML test</span></span>
<figcaption>Veza radi i atributi su dohvaćeni; vrijednosti su uklonjene radi privatnosti.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/ae470a73-add1-4650-933f-b571f627756c.png" class="img-fluid rounded border" alt="AAI prijava bez podataka" /><span class="figure-caption d-block">AAI prijava bez podataka</span></span>
<figcaption>Čist ekran stvarne AAI prijave bez korisničke oznake i lozinke.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/439e1d77-f1a3-4a23-afd6-d84059a69cf3.png" class="img-fluid rounded border" alt="SAML mapiranje" /><span class="figure-caption d-block">SAML mapiranje</span></span>
<figcaption>Obavezni atributi mapirani prema stvarno vraćenim nazivima.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/45cb01e9-5463-41bc-9abd-d232d8158c38.png" class="img-fluid rounded border" alt="Operativni SAML profil" /><span class="figure-caption d-block">Operativni SAML profil</span></span>
<figcaption>Ponovljeni test potvrđuje ispravnu vezu i mapiranje.</figcaption>
</figure>

<a id="saml-proxy"></a>

## 10. SAML proxy i podprofili

Proxy instanca predstavlja jedan SimpleSAMLphp auth source iza kojega se može nalaziti više upstream providera. Na kartici **Standardno** postavljaju se zajednička autoload putanja, auth source i osnovna mapiranja. Dodatni podprofili nasljeđuju te zajedničke vrijednosti, a svaka kartica dobiva vlastito mapiranje atributa.

**Proxy nema Dopuštene domene ni Atribut koji sadrži domenu.** To je namjerno: različiti upstream provideri mogu vraćati različite vrste stabilnih identifikatora i domene. Ograničenje treba provesti na proxyju ili pojedinom upstream provideru, a ne jednom zajedničkom domenom u Simbiozi.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/740d9159-05e8-4025-995d-ad5f752281bf.png" class="img-fluid rounded border" alt="Početna proxy konfiguracija" /><span class="figure-caption d-block">Početna proxy konfiguracija</span></span>
<figcaption>Standardna kartica proxy instance prije mapiranja.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/f7dca96b-578a-4a56-9d04-7d28591b5091.png" class="img-fluid rounded border" alt="Dodavanje proxy podprofila" /><span class="figure-caption d-block">Dodavanje proxy podprofila</span></span>
<figcaption>Gumb plus dodaje zasebnu karticu za novi upstream izvor.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/ebe9c04d-81f0-43f9-9788-760dc428fac9.png" class="img-fluid rounded border" alt="Tri proxy podprofila" /><span class="figure-caption d-block">Tri proxy podprofila</span></span>
<figcaption>Standardno, Facebook i Google prije dovršetka testova.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/08c299d3-24e9-4e7c-8d9b-b31e4acf84db.png" class="img-fluid rounded border" alt="Izbor providera na proxyju" /><span class="figure-caption d-block">Izbor providera na proxyju</span></span>
<figcaption>Stvarna proxy prijava nudi AAI@EduHR, Facebook i Google.</figcaption>
</figure>

### Standardno / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/bde41578-768d-4dff-815d-70651c282ea8.png" class="img-fluid rounded border" alt="Prvi test standardnog proxy profila" /><span class="figure-caption d-block">Prvi test standardnog proxy profila</span></span>
<figcaption>Dohvaćeni atributi prije mapiranja, s uklonjenim vrijednostima.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/30b06cdc-c758-4fec-9b88-86db42d016dd.png" class="img-fluid rounded border" alt="Mapiranje standardnog proxy profila" /><span class="figure-caption d-block">Mapiranje standardnog proxy profila</span></span>
<figcaption>Mapiranje standardnog AAI izvora.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/2a39a75d-acba-4d23-a73d-f06a423da82b.png" class="img-fluid rounded border" alt="Operativni standardni proxy profil" /><span class="figure-caption d-block">Operativni standardni proxy profil</span></span>
<figcaption>Standardna kartica nakon uspješnog ponovljenog testa.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/f2f320f1-0ca1-4012-b81c-5a0e93c63a64.png" class="img-fluid rounded border" alt="Prvi Facebook proxy test" /><span class="figure-caption d-block">Prvi Facebook proxy test</span></span>
<figcaption>Dohvaćeni Facebook atributi prije mapiranja, s uklonjenim vrijednostima.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/a984dc58-149f-474d-96fc-ce9bb3fd3631.png" class="img-fluid rounded border" alt="Mapiranje Facebook podprofila" /><span class="figure-caption d-block">Mapiranje Facebook podprofila</span></span>
<figcaption>Stabilni Facebook identifikator, login i korisnička polja mapirani su zasebno.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/11ced115-6c93-4526-ad00-52f5c9bbe3e3.png" class="img-fluid rounded border" alt="Operativni Facebook podprofil" /><span class="figure-caption d-block">Operativni Facebook podprofil</span></span>
<figcaption>Facebook kartica nakon uspješnog testa.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/661460a9-8525-47d1-8f41-7c67ca89939a.png" class="img-fluid rounded border" alt="Prvi Google proxy test" /><span class="figure-caption d-block">Prvi Google proxy test</span></span>
<figcaption>Dohvaćeni Google atributi prije mapiranja, s uklonjenim vrijednostima.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/93870f66-f6b9-4f24-bb33-887de0721d71.png" class="img-fluid rounded border" alt="Mapiranje Google podprofila" /><span class="figure-caption d-block">Mapiranje Google podprofila</span></span>
<figcaption>Google UID je subjekt, Google e-mail login, a ime i prezime mapiraju se u model.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/e0733520-d5ab-4334-83c4-3215c6ca518d.png" class="img-fluid rounded border" alt="Operativni Google podprofil" /><span class="figure-caption d-block">Operativni Google podprofil</span></span>
<figcaption>Google kartica nakon uspješnog ponovljenog testa.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

CAS profil traži bazni URL poslužitelja te login, validate i logout endpoint. Ako se koristi vlastiti CA lanac, navedite čitljivu putanju do odgovarajućeg certifikata. Ne isključujte TLS provjeru radi zaobilaženja pogrešne konfiguracije certifikata.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/3d462b59-010e-47b7-a23b-c8269afb4cfa.png" class="img-fluid rounded border" alt="Početna CAS konfiguracija" /><span class="figure-caption d-block">Početna CAS konfiguracija</span></span>
<figcaption>CAS veza je pripremljena, a mapiranje još nije dovršeno.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/2a425f13-2d2a-4ce9-974c-7886e7a8b6cb.png" class="img-fluid rounded border" alt="Prvi CAS test" /><span class="figure-caption d-block">Prvi CAS test</span></span>
<figcaption>CAS autentikacija radi i vraća atribute; vrijednosti su uklonjene.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/dc0339a1-2f52-4d0d-a8db-973ac8096959.png" class="img-fluid rounded border" alt="CAS mapiranje" /><span class="figure-caption d-block">CAS mapiranje</span></span>
<figcaption>Stabilni subjekt, login, ime i korisnička polja mapirani su prema rezultatu testa.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/b6c251c7-de67-4e04-8a0d-64023c5b7fd1.png" class="img-fluid rounded border" alt="Operativni CAS profil" /><span class="figure-caption d-block">Operativni CAS profil</span></span>
<figcaption>Ponovljeni test potvrđuje operativan CAS profil.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC koristi Client ID i tajnu, authorization, token, userinfo i po mogućnosti end-session endpoint. Scope mora sadržavati `openid`, a zatim samo podatke koji su potrebni, primjerice `profile` i `email`. **Javni redirect base URL** je javna osnovna adresa ove instalacije; za `https://simbioza.example.org/simbioza` upisuje se upravo cijela ta vrijednost s poddirektorijem.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/225ef9fd-c842-4ddd-9842-ba368854229c.png" class="img-fluid rounded border" alt="Početna OIDC konfiguracija" /><span class="figure-caption d-block">Početna OIDC konfiguracija</span></span>
<figcaption>OIDC endpointovi i javna osnovna adresa prije mapiranja.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/95340429-2d71-419d-9b55-89843541b1c9.png" class="img-fluid rounded border" alt="Prvi OIDC test" /><span class="figure-caption d-block">Prvi OIDC test</span></span>
<figcaption>Dohvaćeni OIDC claimovi prije mapiranja; vrijednosti su uklonjene.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/281169db-78a0-473a-8f63-e5b1f4bf574d.png" class="img-fluid rounded border" alt="OIDC mapiranje i domena" /><span class="figure-caption d-block">OIDC mapiranje i domena</span></span>
<figcaption>Subjekt, login, prikazno ime, korisnička polja i domenski atribut nakon pregleda claimova.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/e5ad83fd-4f64-4cb5-8183-54ca0f11e297.png" class="img-fluid rounded border" alt="Operativni OIDC profil" /><span class="figure-caption d-block">Operativni OIDC profil</span></span>
<figcaption>Ponovljeni test potvrđuje OIDC profil i prikazuje zelenu kvačicu.</figcaption>
</figure>

Parametar forsiranja prijave, primjerice `max_age=0`, koristite samo kada provider podržava tu semantiku i kada je uključeno **Forsiraj prijavu**. Inače ostavite prazno kako se ne bi nepotrebno prekidala postojeća SSO sesija.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 profil koristi authorization, token i userinfo endpoint te scopeove potrebne za korisničke podatke. Za providere koji nude OpenID Connect prednost dajte OIDC profilu jer standardizira identitet i validaciju tokena. OAuth2 logout može tražiti poseban endpoint, naziv redirect parametra i dodatni hint; popunite samo ono što dokumentacija providera izričito zahtijeva.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/377837c8-4774-4c0f-a8d9-9fe2ebf19a4b.png" class="img-fluid rounded border" alt="Početna OAuth2 konfiguracija" /><span class="figure-caption d-block">Početna OAuth2 konfiguracija</span></span>
<figcaption>OAuth2 profil prije mapiranja atributa.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/8aff5c5b-1f5f-4c88-87fd-7b2ea38a0d9d.png" class="img-fluid rounded border" alt="Prvi OAuth2 test" /><span class="figure-caption d-block">Prvi OAuth2 test</span></span>
<figcaption>Userinfo atributi dohvaćeni su stvarnom prijavom; vrijednosti su uklonjene.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/2ad57418-0732-4b62-88c1-c67addb91f17.png" class="img-fluid rounded border" alt="OAuth2 mapiranje i domena" /><span class="figure-caption d-block">OAuth2 mapiranje i domena</span></span>
<figcaption>Mapiranje i domensko ograničenje upisani prema rezultatima testa.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/b8338768-6cd0-4bf4-8f00-6d584f441949.png" class="img-fluid rounded border" alt="Operativni OAuth2 profil" /><span class="figure-caption d-block">Operativni OAuth2 profil</span></span>
<figcaption>Ponovljeni test potvrđuje operativan OAuth2 profil.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/27db1b0c-26f3-4b47-8d76-5de3a7231f3a.png" class="img-fluid rounded border" alt="Svi SSO profili operativni" /><span class="figure-caption d-block">Svi SSO profili operativni</span></span>
<figcaption>Zelene kvačice u meniju potvrđuju sve testirane profile i proxy kartice.</figcaption>
</figure>

<a id="osobni-profil"></a>

## 14. Osobni profil: sigurnost i pristup

U korisničkom meniju otvorite **Osobni profil**. Osnovni dio prikazuje identitet i prilagođene atribute. U odjeljku **Sigurnost i pristup** korisnik može provjeriti grupe, lokalnu lozinku, povezane vanjske prijave i zahtjeve za API ključeve.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/0e35ab18-42e6-4244-8266-7b959fbb3658.png" class="img-fluid rounded border" alt="Prilagođeni atribut u profilu" /><span class="figure-caption d-block">Prilagođeni atribut u profilu</span></span>
<figcaption>Dodatni atribut prikazuje se u osobnom profilu.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/01405cc4-5b0e-44f8-b484-e7c7b563113f.png" class="img-fluid rounded border" alt="Sigurnost i pristup" /><span class="figure-caption d-block">Sigurnost i pristup</span></span>
<figcaption>Sažetak sigurnosnih postavki osobnog računa.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"><img src="images/69af3c1b-b9e6-458e-8059-9ced2e83df5d.png" class="img-fluid rounded border" alt="Proširena sigurnost i pristup" /><span class="figure-caption d-block">Proširena sigurnost i pristup</span></span>
<figcaption>Grupe, lokalna prijava, povezani provideri i API zahtjevi na jednom mjestu.</figcaption>
</figure>

Vanjski račun povežite samo dok ste sigurni da upravljate oba identiteta. Nepoznatu ili više nepotrebnu vezu uklonite, ali prije toga provjerite postoji li drugi način prijave kako se korisnik ne bi zaključao izvan računa. Promjenu lokalne lozinke obavite kroz ovaj sigurni obrazac, nikada e-poštom ili administrativnom porukom.

<a id="provjera"></a>

## 15. Završna provjera i sigurnosne preporuke

- Najmanje jedan kontrolirani administratorski način prijave mora ostati dostupan; break-glass račun čuvajte izvan redovne uporabe.
- Za svaki SSO profil provjerite točne callback adrese, TLS certifikate, najmanje scopeove i rotaciju tajni.
- Ne smatrajte profil ispravnim samo zato što je aktivan. Tražite zeleno operativno stanje nakon ponovljenog testa.
- Stabilni subjekt mora biti nepromjenjiv identifikator providera. Login i e-mail mogu se promijeniti i nisu uvijek dobar primarni ključ.
- Domensko ograničenje testirajte dopuštenim i nedopuštenim testnim računom.
- Redovito pregledavajte administratore, članstva grupa, povezana vanjska prijavljivanja, aktivne API ključeve i zahtjeve na čekanju.
- API tajne, lozinke, tokene i sirove osobne atribute ne stavljajte u screenshots, dokumentaciju, tickete ni repozitorij.
- Nakon testiranja obrišite ili deaktivirajte dokumentacijske račune i ključeve koji više nisu potrebni.

<div class="alert alert-success">

Nakon spremanja isprobajte redovnu prijavu i račun kojem pristup nije dopušten. Provjerite da svaki korisnik vidi samo sadržaj i radnje dodijeljene njegovim grupama.

</div>
