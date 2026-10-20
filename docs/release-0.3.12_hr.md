# Simbioza 0.3.12 — prenosive poveznice uputa i povrat na isti site

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/release-0.3.12_en.md)

> Povijesna bilješka izdanja. Koristite [aktualnu uputu za instalaciju](installation_hr.md)
> i [Simbiozu 0.3.13](release-0.3.13_hr.md) za osvježene javne upute.

Ovo izdanje popravlja neispravne poveznice Korisničkih uputa nakon instalacije i
prenosivi povrat HTML-a na izvorni site. HTML Editor je **0.3.2**, a Backup
**0.3.1**. Auth, API, Email i Theme ostaju **0.3.1**, Menu **0.3.2**, ostali
javni moduli **0.3.0**, Framework **v0.0.25**, a
updater **1.0.8**. Postojeći tagovi i migracije baze ostaju nepromijenjeni.
Kandidat 0.3.11 nije objavljen; njegov nepromjenjivi tag ostaje sačuvan.

## Popravci

- Nova instalacija više ne dodaje već razriješeni aplikacijski prefiks drugi
  put (`/demo/demo/...` ili `/simbioza/simbioza/...`).
- Nadogradnja popravlja postojeće poznate poveznice ugrađenih uputa s ponovljenim
  prefiksom ili zabilježenom izvornom adresom, i kada arhiva nije izmijenjena.
  Razrješavaju se samo isporučene ciljne upute koje lokalno postoje. Tekst,
  primjeri koda i nepovezane vanjske poveznice ostaju sačuvani.
- Popravak ne zamjenjuje teme, privatnu konfiguraciju, identitete stranica,
  tijek objave ili prava. Ponovljeno izvršavanje ne dodaje promjene ni verzije.
  Izvedeni backlinkovi osvježavaju se samo za izmijenjene upute i jezike.
- Povrat Editora na isti site vraća instalacijski prefiks uklonjen iz prenosive
  arhive. Popravak vrijedi za backup područja/stranice i potpuni/komponentni
  backup, uz bazu ili filesystem HTML, uključujući promjenu načina pohrane.
- Prijenos na drugi site i dalje mapira identitete uvezenih stranica i privitaka,
  čuva vanjske URL-ove, upite i sidra te prijavljuje nerazriješene ciljeve umjesto
  nagađanja nepovezanih stranica. Sama relativna poveznica ne može razriješiti
  cilj koji nije uključen u arhivu.
- Kada je opcionalni Backup paket uklonjen, rad s arhivama uputa preskače se
  prije pristupa njegovim klasama ili arhivama, neovisno o evidenciji nadogradnji.
  Instaliran, ali isključen Backup i dalje može osvježavati ugrađene upute.
- Aktualne instalacijske naredbe, ugrađene EN/HR upute i Docker primjeri koriste
  0.3.12. Docker izdanje podržava Linux AMD64 i ARM64.

Nisu potrebni reinstalacija, ručna zamjena URL-ova ni migracija baze.

## Nadogradnja

Na podešenoj secured-FPM instalaciji u **Postavke → Setup i moduli** pokrenite
nadogradnju aplikacije na **0.3.12**. Ona instalira kompatibilne verzije modula
i popravlja upravljane upute. Nakon toga provjerite poveznice Korisničkih uputa.

Vidi [instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/installation_hr.md),
[konfiguraciju i backup](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/configuration_hr.md#backup-i-prava)
i [Docker instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/docker_hr.md).
