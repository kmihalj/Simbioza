# Simbioza 0.1.79

Ovo korektivno izdanje dovršava prijelaz starijih instalacija na siguran
namjenski FPM Setup.

Završna FPM konfiguracija sada uvijek stvara zapisivi privatni direktorij
`data/config` za trajno stanje modula. Time instalacije nadograđene updaterom
izdanja 0.1.74 ili starijeg više ne prikazuju pogrešku **Stanje modula je
zapisivo**, a GUI instalacija i uklanjanje modula postaju dostupni bez promjene
postojećeg popisa uključenih modula.

Provjera FPM postavki pokrenuta kao običan održavatelj sada stvarno ispituje
ograničeni Setup helper. Više ne prijavljuje neodređeno stanje samo zato što je
`/etc/sudoers.d` namjerno nedostupan za izravno čitanje.

Izdanje nema migracija ni promjena poslovnih podataka.
