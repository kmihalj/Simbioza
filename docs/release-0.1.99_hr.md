# Simbioza 0.1.99

Ovo izdanje dodaje opcionalne priključke za module privatne pojedinoj
instalaciji, bez uključivanja takvih paketa u javni katalog Simbioze.
Administrator može držati paket u lokalnom Composerovu repozitoriju s putanjom,
navesti ga u `config/modules.local.php` i njegovu zaštitu zahtjeva registrirati
u `config/middleware.local.php`. Aplikacija čita te datoteke samo kada postoje.
U `config/installation.php` moguće je postaviti zaseban `session_name` za više
instalacija na istoj domeni; zadana vrijednost ostaje `HEARTPHRAME_SESSION`.

Nadograđivač čuva `composer.local.json` i lokalnu PHP konfiguraciju, spaja samo
nove zahtjeve za paketima iz postojećih Composerovih repozitorija s apsolutnom
lokalnom putanjom te ne dopušta da lokalni zahtjev zamijeni ovisnost javnog
izdanja. Privatnim paketima ne upravlja GUI popis modula. To je opći mehanizam;
privatni demonstracijski modul i njegov sadržaj nisu dio ovog javnog izdanja.

Nema migracija baze. Instalacije bez lokalnih dodataka rade kao prije i mogu se
nadograditi uobičajeno kroz GUI ili naredbeni redak. Instalacija koja već koristi
privatni paket s lokalnom putanjom najprije mora zasebno postaviti i provjeriti
`update.php` iz ovog izdanja, a tek zatim pokrenuti potpunu nadogradnju: stari
nadograđivač iz 0.1.98 još ne zna sačuvati taj paket. Nakon sigurnosne kopije i
nadogradnje treba provjeriti prisutnost paketa, djelovanje zaštite zahtjeva,
kolačić sesije i redovne HTTP odgovore. Privatne vjerodajnice i putanje paketa
ne objavljuju se u javnom katalogu modula.
