# Uobičajeni tijekovi rada

Ovaj dokument opisuje tipične korake za česte razvojne zadatke u HeartPhrame
aplikaciji.

---

## Tijek od zahtjeva do odgovora

Čest zadatak je dodavanje nove stranice ili API krajnje točke. To obično
uključuje stvaranje rute, akcije kontrolera koja obrađuje zahtjev i prikaza koji
iscrtava odgovor.

### 1. Dodajte rutu

Najprije definirajte novu rutu u konfiguracijskoj datoteci ruta, najčešće
`config/routes.php`. Ruta povezuje HTTP metodu i URL putanju s određenom akcijom
kontrolera. Ovdje možete dodijeliti i naziv te middleware.

Primjere potražite u postojećoj datoteci `config/routes.php`. Dodatne pojedinosti
nalaze se u dokumentu [Rute](routes_hr.md).

### 2. Izradite akciju kontrolera

Zatim izradite metodu kontrolera na koju ruta upućuje. Kontroleri se nalaze u
direktoriju `src/Controllers/`. Akcija sadrži logiku za obradu zahtjeva i mora
vratiti instancu `Psr\Http\Message\ResponseInterface`.

Primjere potražite u direktoriju `src/Controllers`.

### 3. Izradite i iscrtajte prikaz

Za HTML odgovore obično se iscrtava prikaz. Izradite novi predložak u direktoriju
`views/`. Rasporedi omogućuju zajedničku strukturu stranice, primjerice zaglavlje
i podnožje.

U akciji kontrolera iscrtajte prikaz i vratite ga kao odgovor.

Dodatne informacije nalaze se u dokumentu [Prikazi](views_hr.md).

---

## Ostali uobičajeni zadaci

### Registriranje servisa

Novi servis registrirajte u DI spremniku kroz konfiguracijsku datoteku
`config/services.php`. Povežite implementaciju sa sučeljem ili nazivom klase pa
će je spremnik automatski ubrizgati gdje je potrebna.

Dodatne informacije potražite u odjeljku `services.php` dokumenta
[Konfiguracija](configuration_hr.md).

### Prilagođavanje middlewarea

Middleware se može primijeniti globalno na sve zahtjeve ili pridružiti
određenim rutama i grupama ruta. Globalni middleware dodaje se ili uklanja u
`config/middleware.php`, a middleware pojedine rute definira se u
`config/routes.php`.
