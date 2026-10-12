# Simbioza 0.1.96

> Povijesna bilješka izdanja — nije aktualna uputa za instalaciju ni nadogradnju. Za čistu osnovu 0.3.0 koristite [instalaciju](installation_hr.md) i [aktualnu konfiguraciju](configuration_hr.md). Upute za nadogradnju starijih verzija u nastavku ostaju samo kao povijest.

Stavka postavki opcionalnog modula sada prati stvarno stanje njegova paketa. Aplikacija registrira rute postavki Pristupačnosti samo dok postoji direktorij paketa. Izbornik 0.1.24 skriva spremljene stavke čije rute više ne postoje, uključujući stavke najviše razine. Instalirani modul i dalje sam prijavljuje svoju stavku, bez statičkog upisa u aplikacijski izbornik. Isključivanje instaliranog modula ne uklanja njegovu administracijsku rutu postavki.

Time se popravlja i instalacija koja je nakon deinstalacije zadržala stavku Pristupačnosti. **Ne brišite spremljenu kopiju podataka modula i ne uređujte ručno izbornik postavki.** Najprije nadogradite Simbiozu. Zastarjela stavka nestaje nakon učitavanja novog koda; ponovna instalacija Pristupačnosti vraća je prijavom iz samog modula. Postojeći administratorski nazivi i redoslijed stavki ostaju sačuvani.

Izdanje zahtijeva Izbornik 0.1.24, a Pristupačnost ostaje opcionalna. Nema migracija baze ni dodatnih instalacijskih koraka. Isto ponašanje vrijedi za FPM i mod_php instalacije.
