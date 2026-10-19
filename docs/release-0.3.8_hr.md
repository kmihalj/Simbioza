# Simbioza 0.3.8 — očuvanje postavki teme i odabranih boja

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/release-0.3.8_en.md)

Ispravak uklanja automatsku zamjenu teme tijekom nadogradnje i usklađuje
spremljenu boju prigušenog teksta s pregledom nakon ponovnog učitavanja.
Theme je **0.3.1**, API ostaje **0.3.1**, ostali javni moduli **0.3.0**,
privatni DEMO modul **0.3.6**, Framework **v0.0.25** i updater **1.0.8**.
Postojeći tagovi i migracije baze ostaju nepromijenjeni.

## Očuvanje konfiguracije

- Završni korak za ugrađeni sadržaj više ne uvozi tvorničku Simbiozinu temu.
  Uređene boje, raspored, odabir aktivne teme i cijela biblioteka ostaju pod
  upravljanjem administratora, čak i kada se isporučeni ZIP promijeni ili nema
  prethodne evidencije nadogradnje teme. Nova instalacija i dalje dobiva temu.
- Regresijska provjera izričito uređuje isporučenu temu `simbioza`, a ne samo
  njezinu kopiju, te provjerava sadržaj konfiguracije i administratorske datoteke.
- Prošireni testovi updatera obuhvaćaju Auth, Email, Editor/vanjske skripte,
  Workspace, Calendar, Backup, API, Search, Confluence Import, Menu, Theme i
  aplikacijske postavke pri sinkronizaciji izvora, vanjskoj/unutarnjoj konfiguraciji,
  GUI/CLI radu i povratu. Postojeće vrijednosti, vlasništvo i prava ostaju sačuvani.
- Docker provjere uspoređuju kontrolne zbrojeve svih privatnih konfiguracija i
  datoteka biblioteke tema prije i nakon zamjene containera, bez otkrivanja
  privatnog sadržaja. Zamjena imagea zadržava postojeće runtime i bazne volumene.

## Odabrane boje

Theme 0.3.1 valjanu izričitu boju `muted_text` koristi točno kako je spremljena
u obje varijante, uključujući pregled uživo, generirani CSS i prenosivi izvoz.
Više je tiho ne mijenja radi izračuna kontrasta. Izvedene prednje boje gumba i
izričite prilagodbe pristupačnosti ostaju podržane. Odaberite čitljivu
kombinaciju za predviđenu podlogu.

Prethodno ponašanje moglo je ispravno spremiti odabranu boju, ali nakon
učitavanja prikazati njezinu tamniju/svjetliju inačicu. Nije potrebna ručna
reinstalacija niti migracija konfiguracije.

## Nadogradnja

Na podešenoj FPM Secured instalaciji otvorite **Postavke → Setup i moduli**,
provjerite novo izdanje i pokrenite nadogradnju na **0.3.8**. Aplikacija zahtijeva
objavljeni Theme **0.3.1**. Nakon dovršetka osvježite editor teme.
Instalacijski primjeri i ugrađene EN/HR upute koriste 0.3.8.

Za postojeći pripremljeni lokalni DEMO iz njegova korijena pokrenite privatni
`data/demo-local/updatedemo.sh --check`, a zatim
`data/demo-local/updatedemo.sh 0.3.8`. Omotač prije nadogradnje vraća čistu kopiju
za reset, a nakon provjera snima novu. Objava izdanja ne nadograđuje DEMO ni
apps-test automatski.

Stara privatna kopija koju je raniji updater stvorio ostaje u
`data/backups/bundled-assets/`; ovaj ispravak ne briše je niti je automatski
vraća. Vraćajte samo namjerno odabrane postavke nakon usporedbe s eventualnim
novijim administratorskim izmjenama.

Vidi [očuvanje konfiguracije](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/configuration_hr.md#nadogradnja-čuva-administratorske-postavke),
[FPM Secured uputu](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/installation_fpm_secured_hr.md)
i [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/docker_hr.md).
