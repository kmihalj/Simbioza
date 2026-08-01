# Struktura projekta

Ovaj dokument opisuje organizaciju projekta na visokoj razini, bez
implementacijskih pojedinosti.

---

## Raspored na najvišoj razini

U korijenu repozitorija nalaze se sljedeći dijelovi.

### Aplikacijski kod (`src/`)

Izvorni kod aplikacije, primjerice kontroleri i ostala logika.

- Uobičajena podstruktura:
  - `Controllers/` — obrađivači zahtjeva koji koriste servise i vraćaju
    odgovore

Kako projekt raste, dodajte slojeve poput `Domain/`, `Services/` ili
`Repositories/` kako bi odgovornosti ostale jasne.

### Konfiguracija (`config/`)

Središnja konfiguracija aplikacije: postavke, okruženje, rute, middleware, DI
servisi i bootstrap kod. Više informacija nalazi se u dokumentu
[Konfiguracija](configuration_hr.md).

### Runtime podaci (`data/`)

Direktorij za podatke nastale tijekom rada. Zadano sadrži:

- `logs/` — aplikacijske zapise; direktorij mora biti zapisiv korisniku
  web-poslužitelja
- `cache/` — predmemoriju; također mora biti zapisiva

Lokacije se mogu promijeniti. Postupak je opisan u dokumentu
[Konfiguracija](configuration_hr.md).

Osigurajte prava pisanja i nemojte spremati runtime artefakte u sustav kontrole
verzija.

### Artefakti izgradnje (`build/`)

Razvojni alati poput Composera, statičkih analizatora i testnih alata ovdje
spremaju privremene podatke i izvještaje. Direktorij se u pravilu ne sprema u
sustav kontrole verzija.

### Dokumentacija (`docs/`)

Dokumentacija projekta na engleskom i hrvatskom jeziku.

### Web-korijen (`public/`)

Web-korijen s prednjim kontrolerom, odnosno ulaznom PHP skriptom. Web-poslužitelj
mora koristiti ovaj direktorij kao document root.

- To je jedini direktorij izravno izložen webu.
- Može sadržavati statičke resurse koje aplikacija poslužuje.

Zahtjevi se usmjeravaju prednjem kontroleru, koji pokreće aplikaciju, izvršava
middleware, razrješava rute i šalje odgovore.

### Testovi (`tests/`)

- Sadrže automatizirane testove.
- Koriste konfiguraciju testnog alata iz korijena projekta.
- Preporučeni tijek:
  - pokrenuti testove lokalno prije commita
  - pokrenuti ih u CI-ju radi potvrde promjena

### Composer paketi (`vendor/`)

Composer paketi i automatsko učitavanje klasa. Direktorij se ne sprema u VCS.

### Prikazi (`views/`)

Sadrži predloške i rasporede za HTML odgovore.

Uobičajena struktura:

- `layouts/` — osnovni rasporedi zajednički većem broju stranica
- direktoriji po funkcionalnostima — grupiranje prema mogućnosti ili kontroleru

Zadanu putanju prikaza možete promijeniti u `config/app.php`. Više informacija
nalazi se u dokumentu [Prikazi](views_hr.md).

### Ostale datoteke

- konfiguracija alata i metapodaci za ovisnosti, testiranje, statičku analizu,
  standarde koda i refaktoriranje, primjerice `composer.json`, `phpunit.xml`,
  `phpstan.neon`, `rector.php`, `phpcs.xml` i `README.md`
