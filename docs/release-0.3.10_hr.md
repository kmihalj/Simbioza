# Simbioza 0.3.10 — očuvanje postavki i odabranih boja teme

[Engleska verzija](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/release-0.3.10_en.md)

> Povijesna bilješka izdanja. Za aktualne naredbe instalacije i nadogradnje koristite
> [uputu za instalaciju](installation_hr.md) i
> [Simbiozu 0.3.13](release-0.3.13_hr.md).

Ispravak čuva administratorske konfiguracije pri nadogradnji, uklanja automatsku
zamjenu instalirane teme i usklađuje spremljenu boju prigušenog teksta s pregledom
nakon osvježavanja. Auth, HTML Editor, Email, Theme i API su **0.3.1**, Menu je **0.3.2**, ostali javni moduli
**0.3.0**, Framework **v0.0.25** i updater **1.0.8**.
Postojeći tagovi i migracije baze ostaju nepromijenjeni. Kandidati 0.3.8 i 0.3.9
nisu objavljeni kao izdanja; koristite 0.3.10.

## Očuvanje konfiguracije

- Nadogradnja aplikacije više ne uvozi tvorničku Simbiozinu temu. Uređene boje,
  raspored, odabir aktivne teme i cijela biblioteka ostaju pod upravljanjem
  administratora, čak i ako se isporučeni ZIP promijeni ili nema stare evidencije
  nadogradnje. Nova instalacija i dalje dobiva isporučenu temu.
- Središnji `framework.php` ostaje privatan, uključujući vlasnika, grupu i prava.
  Završna provjera prava više ga ne pokušava učiniti javno čitljivim niti ga
  mijenjati kroz deploy račun. Podržan je i slučaj kada datoteka pripada
  održavatelju, a runtime grupa ima samo pravo čitanja.
- Regresijske provjere uređuju samu isporučenu temu `simbioza`, a ne samo kopiju,
  te provjeravaju bajtove konfiguracije i administratorske datoteke teme.
- Testovi updatera obuhvaćaju Auth, Email, Editor/vanjske skripte, Workspace,
  Calendar, Backup, API, Search, Confluence Import, Menu, Theme, odabir jezika/modula
  i aplikacijske postavke. Postojeće vrijednosti i privatna vlasništva/prava čuvaju
  se pri sinkronizaciji izvora, vanjskoj/unutarnjoj konfiguraciji, GUI/CLI radu i
  povratu. Nedostajuće stavke izbornika mogu se dodati bez zamjene postojećih
  oznaka, redoslijeda, uključenosti ili opcija.
- Menu 0.3.2 pri ponovljenoj registraciji modula čuva oznake, odredište,
  isključenost, djecu i položaj postojeće stavke. Nove zadane vrijednosti modula
  pri pokretanju više ne zamjenjuju te administratorske odluke.
- Email, HTML Editor i statička Auth navigacija također čuvaju spremljene
  oznake, odredišta, isključene stavke i položaj. SSO-izvedeni status i dalje
  prati Auth konfiguraciju. Prijava, provjera protokola, SMTP slanje, čišćenje
  HTML-a i način pohrane nisu promijenjeni.
- Ekvivalentni metapodaci navigacije ne uzrokuju prepisivanje pri svakom zahtjevu.
- Docker provjere uspoređuju kontrolne zbrojeve svih privatnih konfiguracija i
  datoteka biblioteke tema prije i nakon zamjene containera. Privatni sadržaj ne
  ispisuje se. Zamjena imagea zadržava runtime i bazne volumene.

## Odabrane boje

Theme 0.3.1 valjanu izričitu boju `muted_text` koristi točno kako je spremljena
u obje varijante, pregledu uživo, generiranom CSS-u i prenosivom izvozu. Nakon
osvježavanja više je tiho ne zamjenjuje bojom prilagođenom kontrastu. Izvedene
prednje boje gumba i izričite prilagodbe pristupačnosti ostaju podržane.
Odaberite čitljivu kombinaciju za predviđenu podlogu.

Reinstalacija niti migracija konfiguracije nisu potrebne. Ispravak ne vraća stare
postavke automatski: prethodna kopija za oporavak može sadržavati vrijednosti
starije od vaših posljednjih izmjena.

## Nadogradnja

Na podešenoj FPM Secured instalaciji otvorite **Postavke → Setup i moduli**,
provjerite novo izdanje i pokrenite nadogradnju na **0.3.10**. Najmanje objavljene
verzije modula navedene su iznad. Nakon dovršetka osvježite editor teme. Aktualni
instalacijski i Docker primjeri te ugrađene EN/HR upute koriste 0.3.10.

Stare privatne kopije u `data/backups/bundled-assets/` ostaju sačuvane. Vraćajte
samo namjerno odabrane postavke nakon usporedbe s novijim administratorskim
izmjenama.

Vidi [očuvanje konfiguracije](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/configuration_hr.md#nadogradnja-čuva-administratorske-postavke),
[FPM Secured uputu](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/installation_fpm_secured_hr.md)
i [Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/docker_hr.md).
