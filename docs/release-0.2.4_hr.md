# Simbioza 0.2.4

Izolirani PHP-FPM pool sada izričito dopušta zahtjev do 900 sekundi tijekom
početne instalacije i radnji nad modulima. Na lokalnom macOS-u s PHP-om 8.5
prethodna vrijednost `max_execution_time = 0` ipak je prekidala zahtjev nakon
približno 60 sekundi: preglednik je mogao prikazati 503 premda je ograničeni
Setup radnik u pozadini uspješno završio. Potvrđeno je da novi pool održava
zahtjev od 65 sekundi i uredno vraća odgovor.

Za postojeću izoliranu FPM instalaciju izdanje aplikacije samo po sebi ne
mijenja sistemski pool. Nakon nadogradnje ponovno pokrenite
`scripts/configure_fpm_setup.php --install` s istom oznakom instance,
aplikacijskim direktorijem i portom, zatim provjerite pool i servis. Ne
primjenjujte naredbu na drugi direktorij s istom oznakom instance. Obični
FPM i Apache mod_php nisu pogođeni ovom promjenom.
Na već instaliranom siteu potom ponovite `--finalize` s istim argumentima,
čime se vraća završno vlasništvo aplikacijskih datoteka.

Nema migracija baze. Prije nadogradnje načinite obnovljivu sigurnosnu kopiju.
Potpuni koraci nalaze se u [uputi za FPM](installation_fpm_hr.md).
