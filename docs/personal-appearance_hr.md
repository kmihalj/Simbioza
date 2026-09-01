# Osobni izgled

Prijavljeni korisnik može odabrati izgled aplikacije u profilu samo dok je pod
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

Postojeća instalacija dobiva postavku `theme_mode` redovnom aplikacijskom
migracijom uključenom u sljedeću nadogradnju Simbioze. Čista instalacija stupac
odmah izrađuje u početnoj shemi Simbioza User modula.
