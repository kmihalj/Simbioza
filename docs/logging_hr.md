# Logovi i rotacija

[English version](logging_en.md)

Konfiguracija pripada u `HPH_CONFIG_PATH`, a **podaci** logova ne. Aplikacijski
`app.logs.dir` zadano je `data/logs`. Ako ste ga promijenili, prilagodite sve
putanje u nastavku. Logovi i izvozi mogu sadržavati osobne podatke: držite ih
izvan `public/`, ograničite pristup i odaberite rok čuvanja prema svojoj politici.

## Tko upravlja kojim logom?

| Log | Tko zapisuje | Rotacija |
| --- | --- | --- |
| `data/logs/app.log` | Zajednički aplikacijski logger | Auditov `RotatingFileLogHandler`: zadano 10 MiB po datoteci i 10 datoteka uključujući aktivnu; zajedničke postavke `app.logs.max_bytes` / `app.logs.max_files` |
| `data/logs/email-worker.log` | Preusmjeravanje izlaza u primjeru crona za Email | Sistemski `logrotate`; instalacija Emaila ili dodavanje crona **ne** postavlja rotaciju |
| `data/logs/application-update.log` | Ograničeni deploy helper, standardni izlaz i greške | Sistemski `logrotate`; očuvati deploy vlasništvo |
| `data/logs/installer.log` | Početni installer | Sistemski `logrotate`; očuvati privatna prava |
| `data/logs/php-fpm.log`, `php-fpm-launchd.log` | Namjenski FPM / macOS launchd | Sistemska rotacija uz očuvanje vlasništva; ne restartati tuđe poolove |
| `data/saml/logs/*.log` | Opcionalni, zasebno podešeni SimpleSAMLphp | Provjeriti stvarno odredište logiranja i postaviti zasebno sistemsko pravilo |
| Apache, PostgreSQL i sistemski journal | Operacijski sustav ili kontejner | Vlastite sistemske politike, ne PHP aplikacijski handler |
| Datoteke mjerenja i E2E testova pod `build/` | Samo izričito pokrenuti testovi | Nisu uključene na običnim instalacijama; dovršene testne artefakte čistiti prema razvojnoj politici |
| `audit_events` | Dnevnik aktivnosti, baza | Nije datotečni log; nikada ga ne brisati ni prazniti logrotateom. Čuvanje poslovne povijesti zahtijeva zasebnu svjesnu politiku |

Aplikacija koristi Auditov rotirajući handler kada je taj paket instaliran.
Generički HeartPhrame projekt mora ga izričito povezati sa svojim PSR-3
loggerom; uključivanje Audit GUI-a ne mijenja proizvoljni logger drugog projekta.
Bez tog handlera postavite sistemsku rotaciju i za aplikacijski log. Istu
datoteku ne rotirajte dvama mehanizmima. Handler obuhvaća rotaciju i upis
stalnom bravom `.rotation.lock`; ne brišite je dok postoje aktivni zapisivači.
Pojedinačni iznimno veliki zapis može premašiti zadanu veličinu.

## Linux: FPM Secured

Ovo je **sistemsko podešavanje**, ne aplikacijska konfiguracija. Instalirajte
`logrotate` paketnim alatom distribucije (Debian/Ubuntu:
`sudo apt-get install --no-install-recommends logrotate`). Kao administrator kreirajte
`/etc/logrotate.d/simbioza`:

```conf
/srv/simbioza/data/logs/email-worker.log /srv/simbioza/data/logs/application-update.log /srv/simbioza/data/logs/installer.log /srv/simbioza/data/logs/php-fpm.log /srv/simbioza/data/logs/php-fpm-launchd.log {
    su root root
    daily
    maxsize 10M
    rotate 14
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    nocreate
}
```

Zamijenite **svaku** putanju `/srv/simbioza` stvarnim korijenom aplikacije,
primjerice `/data/www/simbioza`. Pravilo treba biti `root:root`, prava `0644`,
bez prava pisanja web korisnika. `su root root` omogućuje administratorskom
servisu obradu različitih runtime/deploy/FPM vlasnika u privatnom direktoriju.
To **ne** pokreće Email worker kao root. Izričita imena izbjegavaju zahvaćanje
tuđih datoteka, baze ili podataka sigurnosnih kopija.

`copytruncate` čuva aktivni inode, vlasnika, ACL i prava pa cron, FPM i aktivna
nadogradnja nastavljaju pisati bez restarta. Između kopiranja i pražnjenja
moguć je gubitak nekoliko istodobno zapisanih bajtova. Ako politika ne dopušta
takav gubitak, koristite centralizirani sakupljač ili odgovarajuće ponovno
otvaranje/zaključavanje zapisivača; nemojte samo preimenovati aktivni FPM log.
Ostaju aktivni log i najviše 14 arhiva, uz odgodu kompresije za jednu rotaciju.
Takve arhive nisu zamjena za politiku sigurnosnih kopija.

Provjerite bez promjene datoteka, zatim provjerite raspored:

```sh
sudo chown root:root /etc/logrotate.d/simbioza &&
sudo chmod 0644 /etc/logrotate.d/simbioza &&
sudo logrotate --debug /etc/logrotate.d/simbioza &&
sudo systemctl status logrotate.timer --no-pager
```

`maxsize` se provjerava **kada se logrotate pokrene**, ne neprekidno: dnevni
timer može dopustiti rast tijekom dana. Za provjeru svaki sat na systemd
distribuciji pokrenite `sudo systemctl edit logrotate.timer` i upišite:

```ini
[Timer]
OnCalendar=
OnCalendar=hourly
AccuracySec=1min
```

Zatim `sudo systemctl daemon-reload && sudo systemctl enable --now logrotate.timer`.
Time mijenjate učestalost provjere cijelog sistemskog logrotate servisa;
uskladite to sa sistemcem ili vlasnikom sustava za upravljanje konfiguracijom.
Ako sustav koristi cron umjesto timera, raspored prilagodite ondje. Ne
pokrećite dvije zasebne rotacije istih datoteka/stanja. Ograničenje veličine
između provjera približno je, nije diskovna kvota.

Za opcionalni SimpleSAMLphp prvo provjerite podešeni `loggingdir` i logger.
U zasebno pravilo dodajte samo stvarna imena logova uz prikladna prava;
nikada ne uključujte certifikate, konfiguraciju ni metapodatke.

## mod_php i macOS

Za Linux mod_php koristite isto pravilo sa stvarnim putanjama instalacije.
`copytruncate` čuva postojećeg vlasnika `www-data` ili drugog Apache računa.
FPM logovi koji ne postoje ne smetaju (`missingok`). Apache logovi obično već
imaju distribucijska pravila: provjerite ih umjesto dodavanja preklapajućeg pravila.

macOS nema Debianov `systemctl` ni takvu postavu `/etc/cron.d`. Koristite
postojeći administratorski servis rotacije ili instalirajte Homebrew
`logrotate` i podesite root-owned launchd posao s izričitim apsolutnim
putanjama njegove izvršne datoteke, privatne konfiguracije i trajne datoteke
stanja. Isto pravilo prilagodite macOS putanjama i `su` identitetima
(ne Linuxovim `root:root`). Najprije provjerite s `logrotate --debug`.
Instalacija PHP modula ne instalira privilegirani launchd posao. Jedna
datoteka treba imati samo jedan mehanizam rotacije.

## Docker

Priloženi Compose koristi Dockerov logging driver `local` s
`max-size: "10m"` i `max-file: "5"` za svaki servis. Time je ograničen
prikupljeni **standardni izlaz i izlaz grešaka**; nakon izmjene Composea
kontejnere treba ponovno kreirati, ne samo restartati. Ne uređujte sami
datoteke logova kojima upravlja Docker.

To **ne** rotira datoteke u runtime volumenu. `app.log` i dalje koristi PHP
handler. Ako Email worker preusmjerite u
`/runtime/data/logs/email-worker.log` radi pregleda u Auditu, postavite zaseban
posao rotacije s pristupom tom volumenu; Dockerov logging driver ne vidi to
preusmjeravanje. Izlaz workera koji piše samo na standardni izlaz pregledava
se s `docker compose logs email-worker`, ne u Auditu. Pogledajte
[uputu za Email worker](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/main/docs/workers_hr.md).

Izvori: [priručnik za logrotate](https://man7.org/linux/man-pages/man8/logrotate.8.html)
i [Dockerov local logging](https://docs.docker.com/engine/logging/drivers/local/).
