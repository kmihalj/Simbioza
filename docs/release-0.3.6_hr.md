# Simbioza 0.3.6 — provjerena instalacija sigurnog FPM-a

> Povijesna bilješka izdanja. Za aktualne instalacijske upute koristite [instalacijsku uputu](installation_hr.md).

[English version](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/release-0.3.6_en.md)

Ova aplikacijska zakrpa popravlja Linux Setup helper i dopunjuje postupak nove
FPM-secured instalacije. Framework ostaje **v0.0.25**, API **0.3.1**, ostali
javni moduli **0.3.0**, a privatni DEMO modul **0.3.6**. Updater **1.0.8** i
migracije baze nisu mijenjani. Postojeći tagovi se ne pomiču.

## Promjene

- Generirani Linux helperi izostavljaju prazne bind popise i za svaku podešenu
  putanju stvaraju zasebnu sigurno navedenu postavku. Zadana instalacija više
  ne pada s porukom `Source path is not absolute` prije pokretanja Setup workera.
- Konfigurator provjerava stvarni prolaz helpera i CLI funkcije pozadinske
  nadogradnje, a ne samo postojanje datoteke i sudoers pravila. Neuspjele
  provjere vraćaju izlazni status različit od nule prije nastavka na čarobnjak.
- Setup dijagnostika prikazuje stvarnu grešku helpera. Ovisne provjere koje
  nisu izvršene označene su s **Nije provjereno**, umjesto netočne tvrdnje da
  nedostaju `pcntl` ili `posix` ekstenzije.
- Cjelovite EN/HR upute uključuju stvaranje računa/grupa, radna prava,
  generiranje namjenskog servisa, aliase zajedničkog vhosta i neobveznu
  integraciju sa SimpleSAMLphp-om. Apache primjeri dopuštaju `DirectoryIndex`
  iz isporučenog `.htaccess` uz i dalje isključen ispis direktorija.
- Aktualne instalacijske naredbe, Docker primjeri i ugrađene upute koriste
  **0.3.6**. Izdanje sadrži aplikacijske imagee za Linux AMD64 i ARM64.
- Privremeni Debian/systemd/Apache/PHP-FPM test provjerava postavljanje,
  stvarni HTTP čarobnjak, finalizaciju i dostupnost GUI nadogradnje. Postojeći
  browser/API i testovi baza i dalje su obvezni uvjeti objave.

## Nova instalacija

Krenite od [cjelovitog pregleda instalacije](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/installation_hr.md)
ili [upute za sigurni FPM](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/installation_fpm_secured_hr.md).
Upute pretpostavljaju prazan poslužitelj; generator stvara nepostojeće identitete
i koristi postojeće bez zamjene UID/GID vrijednosti. Provedite jednu odgovarajuću
web-konfiguraciju, stanite nakon neuspjele naredbe i uz sintaksu provjerite
stvarni HTTP pristup.

Na postojećem Linux FPM-secured siteu sama nadogradnja aplikacijskih datoteka
ne zamjenjuje root-owned helper izvan aplikacijskog direktorija. Osvježite ga
dokumentiranim administratorskim generatorom uz iste opcije instance/putanje
prije oslanjanja na GUI paketne radnje. Nemojte kao zaobilazno rješenje dati
FPM-u pravo pisanja po aplikacijskom kodu.

Za nepromjenjivi Docker deployment slijedite
[Docker uputu](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/docker_hr.md)
i koristite `ghcr.io/kmihalj/simbioza:0.3.6`, uz očuvanje postojećih radnih i
volumena baze. Objava izdanja ne nadograđuje demo ni apps-test.

[Izdanje i preuzimanja](https://github.com/kmihalj/Simbioza/releases/tag/0.3.6)
