# Osobni izgled

Uz instaliran i uključen modul Simbioza User, prijavljeni korisnik može odabrati izgled aplikacije u profilu samo dok je pod
**Postavke → Tema → Postavke cijelog site-a → Način primjene** odabrano
**Automatski**.

Profil nudi:

- **Svijetla** — uvijek koristi svijetlu varijantu;
- **Tamna** — uvijek koristi tamnu varijantu;
- **Automatski** — nasljeđuje automatsku politiku aplikacije;
- **Sistemski** — izričito prati postavku uređaja `prefers-color-scheme`.

Postavka se sprema po korisniku i nakon prijave vrijedi na svakom uređaju. Ne
mijenja globalnu temu niti odabir drugog korisnika.

Ako administrator globalni način promijeni na **Samo svijetla** ili **Samo
tamna**, cijela cjelina Izgled nestaje iz profila. Poslužitelj zanemaruje
spremljene osobne vrijednosti i odbija izravnu promjenu kroz profil ili API dok
vrijedi prisilna politika. Povratkom globalnog načina na Automatski ponovno se
prikazuju cjelina i ranije spremljeni odabir.

Postavka `theme_mode` čuva se u shemi Simbioza User modula i uključena je u
njegov backup. Migracije održavajte redovnom nadogradnjom aplikacije; stupac
ne stvarajte niti preimenujte ručno.

Dropdown zaglavlja samo u Demu prebacuje cijelu temu za jedan preglednik i ne
mijenja ovu globalnu politiku. Osobni kontrast pristupačnosti smije namjerno
nadjačati prisilni svijetli/tamni izgled u tom pregledniku. Vidi
[teme](theme-collection_hr.md), [ovisnosti modula](module-dependencies_hr.md) i
[korisničke upute](simbioza_overview_hr.md).
