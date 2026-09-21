# Simbioza 0.1.77

Ovo korektivno izdanje čini nadogradnju sigurnom za stvarni modularni sastav
postojeće instalacije.

Updater sada opcionalne module prepoznaje iz izričitih Composer zahtjeva,
zaključanih paketa i trajnog administratorskog stanja. Time nadogradnja čuva
uključene module, ali i instalirane module koji su samo isključeni. Ispravlja se
i starija instalacija kojoj je manifest već bio sveden na obveznu jezgru, bez
brisanja podataka ili ponovnog postavljanja odabira modula.

Osvježavanje ugrađenih uputa na starijim instalacijama može sigurno izvesti
osnovni put iz javnog aplikacijskog URL-a. Ako opcionalni Backup modul nije
instaliran, nadogradnja aplikacije više ne pada: tema se može osvježiti, a
zamjena paketa uputa uredno se odgađa do dostupnosti Backup servisa.

Postupak je provjeren punom statičkom i jediničnom provjerom te matricom čistih
instalacija svih pojedinačnih modula i cjelovitog sastava. HFC je vraćen na svih
17 uključenih modula, 34 izvršene migracije i nula migracija na čekanju.

