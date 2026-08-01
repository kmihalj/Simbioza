# Rješavanje problema

- Prazna stranica ili pogreška 500:
  - Provjerite zapise u direktoriju `data/` i osigurajte da su direktoriji zapisivi.
  - Provjerite postoji li konfiguracijska datoteka okruženja i je li ispravno podešena.
- Rute nisu pronađene:
  - Osigurajte da web-poslužitelj koristi `public/` kao korijenski direktorij
    dokumenata te da je uključeno prepisivanje URL-ova prema prednjem
    kontroleru.
  - Provjerite odgovara li definicija rute očekivanoj HTTP metodi i putanji.
- Problemi s CSRF zaštitom:
  - Osigurajte da obrasci sadrže očekivani CSRF token ili, kada je to opravdano,
    prilagodite iznimke u postavkama aplikacije.
