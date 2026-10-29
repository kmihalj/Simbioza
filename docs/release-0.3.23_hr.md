# Simbioza 0.3.23 — pristupačnost i performanse anketa

[Engleska verzija](release-0.3.23_en.md)

Pitanja ankete zadržavaju povezanost pomoći, ograničenja i poruka pogreške s
kontrolama. Obvezna pitanja imaju tekstualnu oznaku, poveznice pogrešaka vode na
odgovarajuće pitanje, a prelazak između stranica i pregleda vraća fokus tipkovnice.
Izbornici korisnika i grupa zadržavaju fokus u pretrazi, podržavaju strelice i
Escape te prekidaju zastarjele pretrage. Matrice imaju veće ciljeve odabira.
Izbornik jezika sadržaja i navigacija koriste čitljive boje teme. Ispunjavanje
izvornim obrascem i dalje je moguće bez JavaScripta.

Zajednička tema izračunava zasjenjenje potrebno za čitljiv tekst duž gradijenta.
Spremljene boje gradijenta i teksta ostaju iste. Pregled uživo, generirani stilovi,
mobilna navigacija i izvezene teme koriste isti izračun. Već čitljivi gradijenti
zadržavaju dosadašnji izgled. Izbornik zadržava ispravnu imenovanu navigaciju i
nakon zatvaranja mobilnog dijaloga. Visoki kontrast obuhvaća i pozadine odlomaka
obavijesti i citata.

Stranice rezultata anketa skupno dohvaćaju odgovore i, kod identificiranih anketa,
odabrane identitete. Anonimni rezultati ne čitaju identitete. Zbirna pitanja dijele
grupirane upite, a numeričke vrijednosti obrađuju se u ograničenim prozorima.
Oznake ovlasti, primatelji izvještaja i profili prijave koriste skupne dohvaćaje.
Popisi područja jednom po korisniku i zahtjevu razrješavaju dodatne ovlasti anketa,
a miješana stabla stranica ne čitaju odgovore ispitanika. Izravne ovlasti Wiki
stranica koriste već skupno učitane podatke imenika. Provjere ovlasti, privatnost,
zaštita malih skupina i aktualne provjere prijave ostaju na snazi.

Izdanje uključuje Survey 0.3.3, Workspace 0.3.2, Menu 0.3.3, Theme 0.3.5 i
Accessibility 0.3.1. Verzije Autha i Frameworka nisu mijenjane. Povratna migracija
anketa dodaje indeks pretrage dodjela bez promjene ovlasti ili odgovora.
Nadogradnja ga primjenjuje samo gdje je Survey instaliran. Postojeći neobavezni
moduli ostaju neobavezni i nadogradnja ih ne aktivira automatski.

Nadogradite kroz GUI pravilno podešene secured-FPM instalacije ili dokumentirani
CLI alat. Docker instalacije koriste `ghcr.io/kmihalj/simbioza:0.3.23` uz postojeće
trajne podatke. Pogledajte [popis modula](module-dependencies_hr.md),
[upute za nadogradnju](installation_hr.md) i [Docker upute](docker_hr.md).
