# Odaberite instalaciju Simbioze

[Engleska verzija](installation_en.md)

Ove upute instaliraju Simbiozu **0.3.13**. HTML Editor koristi **0.3.2**;
Backup, Auth, API, Email i Theme
koriste **0.3.1**, Menu **0.3.2**, a ostali javni moduli **0.3.0**; Framework je
nepromijenjen. Osnova 0.3.x kreće s novim direktorijem aplikacije, konfiguracijom i bazom.
Nadogradnja s 0.2.x ili korištenje njezinih Simbioza backup arhiva nije podržano.

Odaberite **jednu cjelovitu uputu** ispod. Svaka kreće iz administratorskog
terminala, instalira/provjerava alate, preuzima izdanje, priprema bazu i prava,
podešava web-poslužitelj, provodi čarobnjak i potvrđuje prvu prijavu.
Ovaj pregled nije dodatni korak instalacije.

| Instalacija | PHP identitet | Priprema paketa i kasnije nadogradnje aplikacije | Cjelovita uputa |
|---|---|---|---|
| Apache mod_php | Zajednički Apache račun | CLI, kao vlasnik koda | [Počnite mod_php instalaciju](installation_mod_php_hr.md) |
| Obični PHP-FPM | Zajednički sistemski web-račun u FPM poolu | CLI, kao vlasnik koda | [Počnite običnu FPM instalaciju](installation_fpm_hr.md) |
| Secured PHP-FPM (preporučeno) | Zasebni FPM i deploy računi, ograničeni helper | GUI preko radnika ili CLI kao deploy održavatelj | [Počnite secured FPM instalaciju](installation_fpm_secured_hr.md) |

Sva tri načina imaju grafički **početni installer** i GUI konfiguracije.
Obični i secured FPM nisu isto: sam FastCGI handler ne stvara deploy izolaciju
niti omogućuje GUI Composer radnje i nadogradnje aplikacije.
Nginx treba FPM; ne može izvršavati Apacheov mod_php.
[Docker instalacija](docker_hr.md) zaseban je, opcionalan postupak s imageima.

## Prije odabira načina izvršavanja PHP-a

Zadani primjeri namijenjeni su novom Linux ili macOS poslužitelju, ne postojećem
produkcijskom poolu. Trebate administratorski terminal, domenu/TLS certifikat
za javni site i odabranu bazu. SQLite ne treba bazni servis.
Svaka odabrana uputa ponavlja točne naredbe pripreme i ne pretpostavlja
postojeće Simbioza FPM/deploy račune.

Pri reinstalaciji sistemac prethodno zbrinjava procese i podatke starog sitea,
provjerava postojeće identitete i odabire odgovarajuću instancu i port.
Upute ne brišu drugu instalaciju niti ponovno stvaraju aktivni site samo zato
što direktorij preuzimanja postoji.

Privatna modulna konfiguracija koristi `HPH_CONFIG_PATH` (zadano `config/`);
trajni datotečni sadržaj koristi `data/`. Web-poslužitelj izlaže samo `public/`.

## Nakon instalacije

Svaka cjelovita uputa sadrži vlastite naredbe modula, jezika i nadogradnje,
granice sigurnosnog kopiranja i završne provjere. Redovne paketne/kodne radnje
izvršava održavatelj/deploy identitet, ne web-račun i ne root. Jezični paketi
mogu se nadograditi neovisno o izdanju aplikacije.

Updater osvježava ugrađene Korisničke upute iz paketa instaliranog izdanja.
Čitanje GitHubova `main` ne zamjenjuje automatski stranice pohranjene na siteu;
njihov sadržaj može pripadati starijem izdanju. Ugrađene instalacijske stranice
izrađuju se iz ovih Markdown izvora.
