# Rješavanje problema

Počnite adresom, vremenom, prijavljenom ulogom i prikazanom porukom. Ponovite
jednu radnju pa u preglednikovu prikazu mrežnih zahtjeva provjerite status i
request ID. U izvještaje ne stavljajte lozinke, sesijske kolačiće ni API ključeve.

## Prazna stranica ili interna pogreška

Pregledajte konfigurirani `app.logs.dir` (obično `data/logs`) i pripadajući
PHP-FPM/Apache log. Tražite **prvu** iznimku tog zahtjeva, umjesto nagađanja prema
općoj stranici 500. Iz direktorija aplikacije, odgovarajućim PHP-om i runtime
konfiguracijom pokrenite provjere bez upisa:

```bash
php -v
composer check-platform-reqs --no-dev
php vendor/bin/hph modules list
php vendor/bin/hph modules migrate-status
```

HTTP i CLI moraju koristiti iste putanje iz `framework.php`, `HPH_CONFIG_PATH`
i eventualni `HPH_CONFIG_OVERRIDE_PATHS`. Provjerite je li modul instaliran i
uključen te je li migracija primijenjena. Zbog nedostajuće tablice ne ponavljajte
installer ili destruktivan restore.

Web runtime mora čitati privatne postavke i pisati u svoje runtime direktorije.
U zaštićenoj FPM instalaciji ne smije pisati po kodu. Prije promjene vlasništva
ili ACL-a pogledajte [logove](logging_hr.md), [konfiguraciju](configuration_hr.md)
i [upute postavljanja](installation_hr.md).

## Nedostaje stranica, meni ili ovlast

- Provjerite HTTP metodu, poddirektorij instalacije i naziv rute. Odgovor 404
  može namjerno skrivati privatni ili isključeni sadržaj.
- Za privatnu stranicu/anketu usporedite račun, članstvo u grupi, objavu/nacrt
  i izričita prava stranice. Vanjska prijava na anketu odvojena je od računa
  Simbioze.
- Rute obično nema kada modul nije instaliran ili je isključen. Koristite
  upravljanje modulima umjesto ručnog menija prema nedostupnom kodu.
- Samostalni HTML/Ankete ulaz skriven je uz uključeni Wiki kada nema preostalog
  samostalnog sadržaja. Sadržaj područja ostaje u njegovu stablu.
- Nakon dodavanja Područja postojeće samostalne HTML stranice nastavljaju raditi.
  Administrator ih može premjestiti kroz postavke Editora; to je kontrolirano
  seljenje, a ne popravak baze. Pogledajte korisničke upute.

## Odbijena forma ili CSRF pogreška

Otvorite novu formu i pokušajte jednom. Provjerite šalje li POST očekivano CSRF
polje/zaglavlje, pripada li sesijski kolačić istoj putanji instalacije i poslužuje
li proxy/cache tuđu sesijsku formu. Kada TLS završava na proxyju, provjerite
javni HTTPS URL i pouzdane proxyje. Vrijeme i postavke sesije moraju biti
usklađeni na svim aplikacijskim procesima.

Ne isključujte CSRF niti dodajte redovnu formu za upis među izuzetke. Ponavljanje
pogreške traži pronalazak izgubljenog tokena/sesije. Bearer API rute imaju
vlastitu autentikaciju; sesija preglednika ne daje pristup API-ju.
Vidi [API ugovor](api-v1-contract_hr.md).

## HTML Editor, skripte ili izgled

Za polje obrasca usporedite učitavanje datoteka, sinkronizaciju editora s formom
i serversku validaciju s [izvršivim primjerom integracije](html-editor-forms_hr.md).
Editor dokumenta i samostalno polje obrasca imaju različite vlasnike pohrane.

Ako odobrena skripta sadržaja ne radi, provjerite odabir na toj stranici,
odobrenje, točan kod/URL, eventualnu integrity vrijednost i CSP/SRI poruku
konzole preglednika. Skripte namjerno ne rade pri uređivanju i u povijesnim
prikazima. Ne slabite pravila skripti zbog odbijenog paketa.

Osobni izbor svijetlog/tamnog prikaza postoji samo uz globalni automatski način.
Provjerite aktivnu temu, prisilni način i osobne prilagodbe pristupačnosti;
vidi [osobni izgled](personal-appearance_hr.md).

Prije prijave zabilježite verzije aplikacije/modula, očišćeni request ID,
korake i stvarni rezultat. Priložite sliku relevantnog stanja. [Izolirani testni
runner](end-to-end-testing_hr.md) služi razvojnim provjerama; ne pokreće se nad
produkcijskom bazom.
