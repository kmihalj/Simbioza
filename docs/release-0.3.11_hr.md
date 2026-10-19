# Simbioza 0.3.11 — prenosive poveznice uputa i povrat na isti site

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.11/docs/release-0.3.11_en.md)

Ovo izdanje popravlja neispravne poveznice Korisničkih uputa nakon instalacije i
prenosivi povrat HTML-a na izvorni site. HTML Editor je **0.3.2**, a Backup
**0.3.1**. Auth, API, Email i Theme ostaju **0.3.1**, Menu **0.3.2**, ostali
javni moduli **0.3.0**, privatni DEMO **0.3.6**, Framework **v0.0.25**, a
updater **1.0.8**. Postojeći tagovi i migracije baze ostaju nepromijenjeni.

## Popravci

- Nova instalacija više ne dodaje već razriješeni aplikacijski prefiks drugi
  put (`/demo/demo/...` ili `/simbioza/simbioza/...`).
- Nadogradnja popravlja postojeće poznate poveznice ugrađenih uputa s ponovljenim
  prefiksom ili zabilježenom izvornom adresom, i kada arhiva nije izmijenjena.
  Razrješavaju se samo isporučene ciljne upute koje lokalno postoje. Tekst,
  primjeri koda i nepovezane vanjske poveznice ostaju sačuvani.
- Popravak ne zamjenjuje teme, privatnu konfiguraciju, identitete stranica,
  tijek objave ili prava. Ponovljeno izvršavanje ne dodaje promjene ni verzije.
- Povrat Editora na isti site vraća instalacijski prefiks uklonjen iz prenosive
  arhive. Popravak vrijedi za backup područja/stranice i potpuni/komponentni
  backup, uz bazu ili filesystem HTML, uključujući promjenu načina pohrane.
- Prijenos na drugi site i dalje mapira identitete uvezenih stranica i privitaka,
  čuva vanjske URL-ove, upite i sidra te prijavljuje nerazriješene ciljeve umjesto
  nagađanja nepovezanih stranica. Sama relativna poveznica ne može razriješiti
  cilj koji nije uključen u arhivu.
- Aktualne instalacijske naredbe, ugrađene EN/HR upute i Docker primjeri koriste
  0.3.11. Docker izdanje podržava Linux AMD64 i ARM64.

Nisu potrebni reinstalacija, ručna zamjena URL-ova ni migracija baze. Privatni
DEMO repozitorij ostaje privatan; njegov GitHub 404 bez prijave nije neispravna
lokalna poveznica sadržaja ili privitka.

## Nadogradnja

Na podešenoj secured-FPM instalaciji u **Postavke → Setup i moduli** pokrenite
nadogradnju aplikacije na **0.3.11**. Ona instalira kompatibilne verzije modula
i popravlja upravljane upute. Nakon toga provjerite poveznice Korisničkih uputa.

Za već pripremljeni lokalni DEMO pokrenite kao njegov održavatelj:

```bash
cd /Users/Shared/Simbioza/DemoSimbioza &&
sh data/demo-local/updatedemo.sh --check &&
sh data/demo-local/updatedemo.sh 0.3.11
```

Skripta prije nadogradnje vraća početnu kopiju reseta, a nakon nje sprema novu.
Objava ovog izdanja ne nadograđuje DEMO ni apps-test.

Vidi [instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.11/docs/installation_hr.md),
[konfiguraciju i backup](https://github.com/kmihalj/Simbioza/blob/0.3.11/docs/configuration_hr.md#backup-i-prava)
i [Docker instalaciju](https://github.com/kmihalj/Simbioza/blob/0.3.11/docs/docker_hr.md).
