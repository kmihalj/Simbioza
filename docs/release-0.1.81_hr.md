# Simbioza 0.1.81

> Povijesna bilješka izdanja — nije aktualna uputa za instalaciju ni nadogradnju. Za čistu osnovu 0.3.0 koristite [instalaciju](installation_hr.md) i [aktualnu konfiguraciju](configuration_hr.md). Upute za nadogradnju starijih verzija u nastavku ostaju samo kao povijest.

Ovo korektivno izdanje uvodi trajan prikaz napretka GUI nadogradnje.

Nakon pokretanja nadogradnje Setup odmah prikazuje zaseban ekran s trenutačnom
fazom, postotkom i proteklim vremenom. Prikaz nastavlja raditi i tijekom
maintenance faze, kada se aplikacijski kod i Composer moduli zamjenjuju, pa
kratki `503` odgovor više ne izgleda kao neuspjela nadogradnja.

Pozadinski updater atomski objavljuje ograničeni status svake važne faze:
dohvat izdanja, sigurnosnu kopiju, sinkronizaciju koda, Composer module,
provjere, migracije, cache i eventualni rollback. Tehnički izlaz i dalje ostaje
u privatnom administratorskom zapisu.

CLI način rada ostaje nepromijenjen i ne ovisi o GUI izvjestitelju.

Izdanje nema migracija ni promjena poslovnih podataka.
