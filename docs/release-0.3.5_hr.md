# Simbioza 0.3.5 — zaštita privatnih konfiguracijskih direktorija

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/release-0.3.5_en.md)

> Povijesna bilješka izdanja — za novu instalaciju koristite [aktualni pregled instalacije](installation_hr.md). Izvorni tag i imagei 0.3.5 ostaju nepromijenjeni.

Ova aplikacijska zakrpa donosi updater **1.0.8**. Framework ostaje **v0.0.25**,
API **0.3.1**, ostali javni moduli **0.3.0**, a privatni DEMO modul **0.3.6**.
Postojeći tagovi ne pomiču se i nema novih migracija baze.

## Promjene

- Updater čuva roditeljske direktorije privatnih konfiguracijskih datoteka i
  deklariranih konfiguracijskih slojeva ako tih direktorija nema u izvoru
  sinkronizacije. To vrijedi i za dodatne module i ugniježđene putanje, ne samo
  za ugrađene nazive modula.
- Nadogradnja i povrat prije migracija koriste jednaku zaštitu direktorija.
  Privatni direktorij poput `config/demo/` više ne uzrokuje zbunjujuće rsync
  upozorenje `not empty, cannot delete` dok njegove datoteke ostaju zaštićene.
- Direktoriji koji dolaze s izdanjem zadržavaju pravila za pojedine datoteke:
  datoteke izdanja i `.dist` primjeri mogu se ažurirati ili ukloniti. Stvarne
  rsync pogreške i njihove poruke ne skrivaju se niti zanemaruju.
- Regresijske provjere pokrivaju CLI i GUI nadogradnje, restriktivne maske
  sigurnog FPM-a i privatnog dema, povrat, ugniježđene vanjske konfiguracijske
  slojeve, vlasništvo/prava privatnih datoteka te namjerno neuspjelu rsync naredbu.
- Instalacijske naredbe, Docker primjeri i ugrađene EN/HR upute koriste **0.3.5**.
  Confluence import jednog područja, Auth, kod modula i Framework nisu mijenjani.

## Instalacija i postojeći siteovi

Koristite [pregled instalacije](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/installation_hr.md),
[uputu za sigurni FPM](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/installation_fpm_secured_hr.md)
ili [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.5/docs/docker_hr.md).
[Aktualna privatna DEMO uputa](https://github.com/kmihalj/simbioza-module-demo/blob/main/docs/demo_installation_hr.md)
zahtijeva pristup tom repozitoriju; DEMO modul 0.3.6 ostaje kompatibilan.

Klasične instalacije koriste postojeći CLI ili GUI postupak nadogradnje. Updater
prvo provjerava vlastitu najnoviju verziju pa je zaštita direktorija aktivna prije
sinkronizacije aplikacijskih datoteka. Ne treba brisati konfiguraciju, lockove ni
reset kopije, ponavljati instalaciju ili širiti FPM prava pisanja.

Već uspješno dovršenu nadogradnju nije potrebno ponavljati samo zbog upozorenja
o direktoriju. Ovo izdanje sprječava takvu poruku pri sljedećim nadogradnjama;
poruka ne znači da je zaštićena konfiguracija izgubljena.

Za nepromjenjivi Docker deployment zamijenite aplikacijski image s
`ghcr.io/kmihalj/simbioza:0.3.5`; sačuvajte postojeće volumene konfiguracije/
podataka i PostgreSQL-a. Linux AMD64/ARM64 imagei zadržavaju postojeće testove
i sigurnosne provjere. Objava izdanja ne nadograđuje demo ni apps-test.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.5)
