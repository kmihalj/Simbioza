# Simbioza 0.1.68

Koordinirani release donosi planiranje sastanaka i popravak lokalne hitne
prijave za sve aktivne članove grupe Administrator, ne samo početni račun
Administrator. Uključuje dorade backupa/uvoza/prijenosa stranica i HTML
dropdown sadržaja razvijene uz sastanke.

## Tema i upute pri instalaciji i nadogradnji

Nova instalacija sadrži aktualnu temu Simbioza i dvojezične upute pod
Korisničke upute → Kalendari → Sastanci. Uputa je obična Workspace stranica
sa slikama kao privitcima i može se izvesti. Sve slike koriste svijetlu temu.

Nakon uobičajene nadogradnje koda, ovisnosti i migracija `update.php` pokreće
CLI korak za isporučene pakete. Ažuriraju se samo tema Simbioza i stranica
uputa. Aplikacijski CLI adapter pokreće korak i nakon uspješne migracije
updatera, tako da već učitana stara verzija `update.php` primijeni nove
pakete u prvom pokretanju. Obične migracije izvan maintenance moda updatera,
druge veze i druge putanje migracija ne uvoze pakete. Stranica uputa za
sastanke zadržava identitet, položaj u stablu i postojeća prava. Druge teme,
odabir aktivne teme i druge stranice uputa ostaju sačuvani. Za promijenjene pakete
postoje privatne povratne kopije; isti paket ne uvozi se pri svakom updateu.

Na starijim instalacijama base path se čita iz instalacijskih podataka ili
postojećih slika uputa, i kada je HTML verzije spremljen u datoteci.
Neusklađene putanje zaustavljaju uvoz umjesto pogađanja. Administrator
može izričito pokrenuti taj korak:

```bash
php scripts/update_bundled_assets.php --base-path=/simbioza
```

Privatno stanje nalazi se u `data/bundled-assets.json`, s pravima 0600.
Ne objavljujte ga: sadrži lozinku povratne kopije stranice prije nadogradnje.
Javna lozinka početnog paketa uputa ne koristi se za tu privatnu kopiju.

Release ne pokreće update na apps-testu. Njegov administrator pokreće:

```bash
cd /data/www/simbioza && sudo php update.php
```
