# Simbioza 0.1.69

Uključuje sve dorade iz 0.1.68: planiranje sastanaka, administratorsku hitnu
prijavu za članove grupe Administrator, backup/uvoz/prijenos stranica,
aktualnu temu Simbioza i dvojezične upute sa 72 slike u svijetloj temi.
Tema i uputa za sastanke isporučuju se i kroz installer i kroz update postojećih instalacija.

Završna provjera nadogradnje otkrila je da Composerov cache postojećeg
Unix korisnika može zaustaviti update pokrenut s `sudo`. Updater sada za sve
Composerove korake, uključujući eventualni rollback, koristi zaseban privatni
cache unutar privremenog direktorija procesa. Postojeći cache, Gitove provjere
vlasništva i globalne postavke ostaju netaknuti. Okruženje se vraća i kada naredba
ne uspije, a privatni cache uklanja se pri završetku updatera.

Ako stari, već učitani updater zapne na cacheu drugog Unix korisnika, pokrenite
aktualni `update.php` ili mu izričito postavite `COMPOSER_CACHE_DIR` na nov,
privatan direktorij. Ne isključujte Gitove provjere vlasništva.

Apps-test nije automatski nadograđen. Administrator pokreće:

```bash
cd /data/www/simbioza && sudo php update.php
```
