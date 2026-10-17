# Simbioza 0.3.2 — nova secured-FPM instalacija

[Engleska verzija](release-0.3.2_en.md)

> Povijesna bilješka izdanja — aktualne instalacijske upute i sigurnosne Docker nadogradnje nalaze se u [izdanju 0.3.3](release-0.3.3_hr.md). Izvorni tag i image 0.3.2 ostaju nepromijenjeni.

Ova dopuna aplikacije popravlja FPM generator za nove macOS i Linux secured
instalacije. Javni moduli ostaju **0.3.0**, privatni DEMO modul **0.3.1**, a
Framework **v0.0.25**. Postojeći tagovi ne pomiču se.

## Promjene

- Prazni `HPH_CONFIG_OVERRIDE_PATHS` izostavlja se iz FPM poola umjesto nevaljane
  prazne `env` stavke. `clear_env = yes` ostaje uključen; izričito podešeni
  slojevi i njihov redoslijed ostaju nepromijenjeni.
- Regresijski testovi pokrivaju izostalu, praznu i vrijednost sastavljenu od
  razmaka te jedan i više slojeva. Stvarni FPM parser provjerava launchd i
  izričito zadane identitete na PHP-u 8.2–8.5 u izoliranim CI containerima.
- Sve aktualne instalacijske upute, ugrađene EN/HR arhive uputa i Docker
  primjeri koriste **0.3.2**. Nova instalacija ne treba ručnu zakrpu generatora.
- Cjelovita privatna DEMO uputa najprije instalira aplikaciju 0.3.2 pa dodaje
  demo modul 0.3.1, uz imenovani sigurni pool i SQLite reset.

## Instalacija i već započeti pokušaji

Slijedite [cjeloviti instalacijski indeks](installation_hr.md) ili privatnu
[cjelovitu DEMO uputu](https://github.com/kmihalj/simbioza-module-demo/blob/main/docs/demo_installation_hr.md).
Namjenski korisnici koji nedostaju stvaraju se; postojeći s usklađenim imenima koriste se ponovno.

Ako je početni 0.3.1 setup stao uz `empty value` prije dovršetka čarobnjaka,
ne treba reinstalacija: zamijenite samo `scripts/configure_fpm_setup.php`
verzijom iz ovog taga pa ponovite isti imenovani FPM setup. Ne resetirati bazu
niti ponovno stvarati korisnike. Početni setup nikada ne ponavljati preko
instalirane početne kopije DEMO reseta.

Docker drži nepromjenjivi kod aplikacije u imageu, a trajnu konfiguraciju/podatke
i PostgreSQL podatke u odvojenim postojećim volumenima. Koristite
`ghcr.io/kmihalj/simbioza:0.3.2`; ne brisati volumene radi promjene imagea.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.2)
