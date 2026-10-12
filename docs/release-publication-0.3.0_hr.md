# Objava Simbioze 0.3.0

[Engleska verzija](release-publication-0.3.0_en.md)

Opseg: svih 18 javnih modula, privatni demo modul i Simbioza s tagom 0.3.0; Docker s PostgreSQL-om, volumenom konfiguracije/podataka i zasebnim volumenom baze. Zadani TCP port hosta je 1511 i može se promijeniti. Framework ostaje nepromijenjen, kao objavljena ovisnost ^0.0.25.

## Popis za objavu

- [x] Pregledati promjene; sačuvati privatne postavke i izmjene Frameworka.
- [x] Provjeriti kvalitetu modula i izolirani integrirani E2E (80/80).
- [x] Objaviti main grane; prošlo je svih 19 CI provjera za točne commitove.
- [x] Objaviti tagove 0.3.0 za 18 javnih modula i privatni demo modul.
- [ ] Provjeriti CI objavljenih tagova sa stabilnim ovisnostima.
- [ ] Provjeriti novu instalaciju iz objavljenih paketa, bez lokalnih aliasa.
- [ ] Objaviti main/tag aplikacije i provjeriti cijelu matricu baze/browsera/API-ja.
- [ ] Izgraditi i skenirati Linux amd64/arm64 imagee; objaviti GHCR image i Compose paket.
- [ ] Provjeriti registar i release datoteke; zapisati commitove, CI i digeste imagea.

CI grane modula koristi privremeni composer.ci.json s razvojnim aliasima za runtime ovisnosti deklariranih ovisnosti. Produkcijski composer.json ostaje nepromijenjen. CI tagova zadržava stabilne uvjete ^0.3.0. Framework nikada ne dobiva razvojni alias. Provjere kvalitete ne preskaču se.

Logovi i dokazi idu u ignorirani build/release-0.3.0/. Objavljene tagove ne premještati. Ponovna instalacija dema i apps-testa zaseban je korisnikov korak; ne uklanjati niti resetirati aktivne siteove.
