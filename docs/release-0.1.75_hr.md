# Simbioza 0.1.75

Ovo izdanje dovršava stvarni životni ciklus opcionalnih modula u GUI-ju i
CLI-ju, jednako na namjenskoj FPM instalaciji i na instalaciji bez FPM-a.

Isključeni modul od sljedećeg zahtjeva više ne registrira manifest, rute,
servise ni stavke izbornika i aplikacija ne pristupa njegovim tablicama. Paket,
shema, podaci i postavke ostaju sačuvani za trenutačno ponovno uključivanje.
Potpuno uklanjanje najprije izrađuje privatnu NDJSON kopiju, a zatim uklanja
vlastite migracije, tablice i Composer paket. Ponovno dodavanje podržava praznu
instalaciju ili transakcijski povrat kopije.

Isti CLI sada radi u oba načina instalacije. Na potvrđenom FPM postavu paketne
radnje automatski predaje ograničenom deploy helperu, dok ih bez FPM-a izvodi
vlasnik instalacije. Za redovno održavanje nije potreban `sudo`. Neuspjela
paketna radnja vraća prethodno stanje, a jednokratni Composer cache sprječava
probleme vlasništva između FPM, deploy i CLI korisnika.

Trajno stanje modula premješteno je u `data/config/modules.php`. Prva
nadogradnja starije instalacije automatski izdvaja postojeći odabir iz statičkog
`config/app.php` ili stare `config/modules.php`, bez promjene uključenih modula.
Updater zatim koristi migracije samo stvarno instaliranih paketa. Podržani su i
omotači migracija kojima instalacijski čarobnjak dodijeli novu vremensku oznaku,
kao i povrat izvornog razvojnog ili tagiranog Composer ograničenja.

Regresijske provjere obuhvaćaju stvarno isključivanje i ponovno uključivanje,
sigurno uklanjanje i povrat paketa, staru konfiguraciju, FPM i ne-FPM CLI,
minimalne instalacije bez opcionalnih tablica te potpuni preglednički, API i
performansni E2E skup.
