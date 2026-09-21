# Simbioza 0.1.81

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
