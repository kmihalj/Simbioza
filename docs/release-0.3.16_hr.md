# Simbioza 0.3.16 — paginacija, dijagnostika workera i rotacija logova

[English version](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/release-0.3.16_en.md)

Izdanje uključuje **Audit 0.3.1** i **Email 0.3.3**. HTML Editor/Menu/Theme ostaju
**0.3.2**, Backup/Auth/API **0.3.1**, ostali javni moduli **0.3.0**, Framework
**v0.0.25** i updater **1.0.8**. Postojeći tagovi ne pomiču se.

## Promjene

- Dnevnik aktivnosti i Tehnički log prikazuju broj pronađenih zapisa i brojčanu
  paginaciju iznad i ispod tablice. Velik broj stranica sažima se trotočkama,
  filtri i veličina stranice čuvaju se, a navigacija se prelama na uskom zaslonu.
- Tehnički log pretražuje cijelu aktivnu datoteku, ne samo njezine zadnje retke,
  strujnim čitanjem uz ograničenu memoriju stranice. Brojanje ne uključuje
  rotirane arhive. Za iznimno duge retke filtri vrijede za prikazani dio;
  izvoz i dalje preuzima cijelu aktivnu datoteku.
- Instalirani i uključeni moduli neutralnim paketnim opisom prijavljuju
  postojeće logove. Email nudi `email-worker.log` kada ga podešeni worker
  kreira u `app.logs.dir`. Odabire se pod **Tehnički log → Izvor loga**.
  Time se ne instalira niti pregledava sistemski cron; Email radi i bez
  Audita. Prikaz i izvoz ostaju dostupni samo administratorima.
- Aplikacijski handler zajedničkom bravom obuhvaća rotaciju i upis među
  procesima, čuva ograničen broj kopija i privatna prava datoteka.
- Docker Compose paket ograničava standardni izlaz i greške logging driverom
  `local` (10 MiB, pet datoteka po servisu). Logovi unutar runtime volumena
  zasebni su i ta ih Docker rotacija ne obuhvaća.
- Odvojene EN/HR upute za rotaciju pokrivaju cron, updater, installer, FPM,
  opcionalni SimpleSAMLphp, macOS i Docker. Aktualni instalacijski primjeri i
  ugrađene upute koriste **0.3.16**.
- Lokalni razvojni Docker konteksti koriste traženu donju granicu svakog
  paketa umjesto jedne opće verzije kandidata, pa novije međumodulske ovisnosti
  ostaju zadovoljene. Objavljene gradnje i dalje koriste stvarno izdane pakete.

## Nadogradnja i sistemska rotacija

Na podešenoj secured-FPM instalaciji u Setupu pokrenite nadogradnju aplikacije
na **0.3.16**. Nisu potrebni reinstalacija ni promjena sheme baze. Postojeće
SMTP postavke, teme, konfiguracijske datoteke, sadržaj i povijest aktivnosti
čuvaju se; ne uvodi se automatsko brisanje povijesti.

**Nadogradnja PHP aplikacije ne postavlja privilegirano logrotate pravilo.**
Datoteka preusmjerenog izlaza crona zahtijeva zasebno sistemsko pravilo i
raspored. Slijedite [uputu za rotaciju](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/logging_hr.md):
primjer koristi prag 10 MiB i najviše 14 arhiva. Prag se provjerava pri
pokretanju logrotatea, ne neprekidno; odaberite prikladnu učestalost provjere.
`app.log` ne rotirajte dvama mehanizmima. Dokumentirani `copytruncate` čuva
vlasništvo aktivne datoteke i otvorene zapisivače, ali tijekom kopiranja i
pražnjenja može izgubiti manji dio istodobnog izlaza.

Za Docker slijedite [uputu za instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/docker_hr.md).
Sačuvajte runtime i PostgreSQL volumene, a servise ponovno kreirajte radi novog
imagea i Compose postavki logiranja. Worker koji piše samo na standardni
izlaz pregledava se s `docker compose logs email-worker`; za prikaz zajedničke
datoteke u Auditu izričito preusmjerite izlaz i zasebno postavite njezinu rotaciju.

Reference modula: [Audit 0.3.1](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-audit/-/blob/0.3.1/docs/audit_hr.md)
i [postavljanje Email 0.3.3 workera](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/0.3.3/docs/workers_hr.md).
