# Simbioza 0.1.82

Ovo korektivno izdanje dovršava FPM zaštitu prava nakon GUI i CLI nadogradnje.

Kada instalacija ima opcionalne module, updater ponovno izrađuje `composer.json`
prije razrješavanja paketa. Uz restriktivni `umask 0007` namjenskog deploy
procesa ta je datoteka nakon uspješne nadogradnje mogla ostati nečitljiva FPM
procesu, zbog čega je otvaranje Postavki vraćalo HTTP 500.

Updater sada eksplicitno ostavlja Composerov manifest čitljivim web procesu,
bez promjene vlasnika, grupe ili prava pisanja. Završni korak novog izdanja
primjenjuje isto pravilo i kada je nadogradnju pokrenuo stariji updater, pa je
izravna GUI nadogradnja s 0.1.81 sigurna bez prethodne ručne zamjene updatera.

Dodani su regresijski testovi za restriktivni FPM `umask` i prijelaz sa starije
verzije updatera.

Izdanje nema migracija ni promjena poslovnih podataka.
