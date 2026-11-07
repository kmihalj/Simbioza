# Installa Simbioza con Apache mod_php

<a id="install-simbioza-with-apache-mod_php"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Versione croata](installation_mod_php_hr.md)

Questa è una guida completa per una nuova installazione, da un normale terminale al primo accesso da amministratore. Non è necessaria alcuna altra guida di installazione. Apache esegue PHP all'interno del suo processo web condiviso; le operazioni sui pacchetti e gli aggiornamenti delle applicazioni rimangono solo CLI. Nginx non può utilizzare mod_php. Non rendere mai scrivibile sul Web il codice dell'applicazione per ottenere l'installazione del pacchetto GUI.

<a id="1-prepare-the-host"></a>

## 1. Preparare l'host

Inizia con una normale shell di amministratore su Linux o macOS. Hai bisogno di un dominio che punti al server e di un certificato TLS valido per gli esempi HTTPS. Esegui solo i comandi per il tuo sistema operativo. Non continuare dopo un errore.

Su Debian/Ubuntu, installa gli strumenti e le estensioni PHP:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql libapache2-mod-php
```

Su macOS, installa prima Homebrew se non è installato, quindi:

```bash
brew install git rsync composer httpd php
```

Controlla gli strumenti installati:

```bash
php -v
php -m
composer --version
git --version
```

PHP deve essere **8.2 o successivo**, Composer deve essere la versione 2 e PHP necessita di `ctype dom fileinfo intl json libxml mbstring openssl pdo session xmlreader zip`. Utilizza `pdo_sqlite`, `pdo_mysql` o `pdo_pgsql` per il database scelto. CLI PHP e il gestore web PHP devono avere versioni ed estensioni corrispondenti.

<a id="2-download-a-clean-simbioza-0339-release"></a>

## 2. Scaricare una versione pulita di Simbioza 0.3.44

Scegli **una** directory dell'applicazione. Su Linux:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

Su macOS, utilizza il disco interno, non un checkout di sviluppo o un volume montato con `noowners`:

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Mantieni aperto questo terminale. Cambia ora il percorso scelto se hai bisogno di un altro sito. La directory deve **non esistere ancora**; questo controllo rifiuta deliberatamente di sovrascrivere un'installazione esistente. Un amministratore che reinstalla un sito deve prima gestire separatamente il vecchio servizio e i dati. Niente qui li cancella.

Esegui il blocco di download completo come normale account manutentore:

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.44 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Aspettatevi `0.3.44` e `success` per ogni requisito della piattaforma. L'avviso **detached HEAD** di Git è normale quando si effettua il checkout di un tag di rilascio. Non eseguire Composer con `sudo`, copiare la configurazione o il database di un altro sito, includere `.git/` nell'applicazione distribuita o utilizzare `--no-dev` qui. La versione 0.3.0 è una base di nuova installazione, non un aggiornamento da 0.2.x.

La root di configurazione predefinita è `config/` (`HPH_CONFIG_PATH`), con una sottodirectory per ciascun modulo. I file persistenti appartengono a `data/`. Solo `public/` verrà esposto dal server web. Mantieni i percorsi predefiniti per questa prima installazione; i livelli di configurazione esterna sono un'opzione avanzata, non un prerequisito.

<a id="3-choose-and-prepare-the-database"></a>

## 3. Scegli e prepara il database

Scegli **un** database. SQLite è il percorso più breve e non richiede server di database o password di database: seleziona SQLite nella procedura guidata e crea `data/simbioza.sqlite`. Continuare al passaggio 4.

Se scegli un database server e non disponi ancora del relativo servizio, installa **solo quello che hai scelto**. Su Debian/Ubuntu: `sudo apt-get install mariadb-server` o `sudo apt-get install postgresql`. Su macOS: `brew install mariadb`, quindi `brew services start mariadb` o `brew install postgresql@18`, quindi `brew services start postgresql@18`. Per gli strumenti con versione Homebrew PostgreSQL, utilizzare `/opt/homebrew/opt/postgresql@18/bin/createuser` e `/opt/homebrew/opt/postgresql@18/bin/createdb` di seguito.

Per **MySQL**, avvia il tuo servizio MySQL/MariaDB e inserisci il relativo client amministratore. Crea un database vuoto e un account dell'applicazione limitato:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-database-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Utilizza l'host `127.0.0.1`, la porta del servizio (normalmente `3306`), il database `simbioza`, l'utente `simbioza` e la password nella procedura guidata. Non utilizzare l'amministratore del database come account dell'applicazione.

Per **PostgreSQL**, avvia il suo servizio ed esegui questi comandi come amministratore PostgreSQL (su Debian/Ubuntu, prefisso ciascuno con `sudo -u postgres`):

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Utilizza l'host `127.0.0.1`, la porta del servizio (normalmente `5432`), il database e l'utente `simbioza` e la password immessa nel prompt privato. Verificare che l'autenticazione TCP locale del database consenta tale account. Non inserire password reali nella documentazione, nella cronologia dei comandi o in Git.

<a id="4-prepare-narrow-runtime-permissions"></a>

## 4. Preparare autorizzazioni di runtime limitate

I pacchetti di sistema forniscono l'account web condiviso: `www-data` su Debian/Ubuntu, `_www` per l'esempio Homebrew Apache. Conferma il tuo attuale utente/gruppo Apache prima di applicare questi comandi. Il codice rimane di proprietà del tuo manutentore; l'account Web scrive solo dati di runtime e impostazioni dinamiche.

Su Debian/Ubuntu:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp www-data config
sudo chmod 3770 config
sudo chgrp -R www-data data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo setfacl -R -m u:"$SIMBIOZA_MAINTAINER":rwX,u:www-data:rwX data
sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp www-data "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec setfacl -m d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx {} +
```

Su macOS:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp _www config
sudo chmod 3770 config
sudo chgrp -R _www data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp _www "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

Non concedere l'accesso in scrittura Web alla root della release, `src/`, `scripts/`, `public/`, `vendor/`, `composer.json` o alla configurazione statica del servizio PHP. Le directory di configurazione con sticky bit consentono la sostituzione atomica delle impostazioni di proprietà del web senza consentire la sostituzione del codice di servizio di proprietà del manutentore. Non utilizzare mai `chmod 777`.

<a id="5-confirm-that-apache-actually-runs-mod_php"></a>

## 5. Conferma che Apache esegua effettivamente mod_php

La versione CLI PHP da sola non è la prova che Apache utilizzi lo stesso PHP. Ispezionare i moduli e il gestore Apache attivi; verificare che PHP in un browser utilizzi una versione supportata e le estensioni richieste. La solita build `mod_php` non thread-safe richiede `prefork` MPM di Apache. Non abilitare sia il gestore proxy FPM che il gestore `mod_php` per questo sito.

Su Debian/Ubuntu, abilita il gestore PHP non thread-safe e i moduli richiesti:

```bash
sudo a2dismod mpm_event
sudo a2enmod mpm_prefork rewrite ssl
sudo a2enmod "php$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Su macOS, modifica `/opt/homebrew/etc/httpd/httpd.conf` con `sudoedit`. Abilita `mpm_prefork_module`, `rewrite_module`, `ssl_module` e `socache_shmcb_module`; disabilita `mpm_event_module`. Aggiungi `Listen 443` se nessuna direttiva è già in ascolto lì, quindi aggiungi:

```apache
LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so
```

Per un nuovo Apache Homebrew, imposta esplicitamente l'identità dei processi di lavoro condivisi in `httpd.conf` (non eseguire i processi web con l'account proprietario del codice):

```apache
User _www
Group _www
```

Utilizza il seguente host virtuale nel file di configurazione selezionato. Sostituisci i percorsi del dominio e del certificato e sostituisci **entrambi** i percorsi dell'applicazione con quello scelto `SIMBIOZA_APP_ROOT/public`. La configurazione di Apache non espande le variabili della shell.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

La direttiva `LoadModule` del modulo PHP è specifica della distribuzione e deve puntare al modulo effettivamente installato. Testa la configurazione di Apache prima di un ricaricamento regolare. Per l'installazione di una sottodirectory, configura l'alias URL e utilizza l'URL di base completo nel passaggio 7.

Testa e avvia solo questo servizio Apache configurato. Su Debian/Ubuntu:

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

Su macOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Aspettatevi `Syntax OK`. Se il servizio esiste già, utilizzare un ricaricamento normale convalidato anziché riavviare siti non correlati.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Facoltativo: installazione con un prefisso URL come /simbioza

Per un nome host condiviso, posiziona questo blocco **all'interno dell'host virtuale HTTPS esistente**, invece di creare un altro host virtuale con lo stesso nome. Sostituisci il percorso di rilascio, il prefisso URL e (per FPM) la porta del pool:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "application/x-httpd-php"
    </FilesMatch>
</Directory>
```

Utilizzare `--base-url=https://your-host.example/simbioza` per il programma di installazione, non il nome host senza il prefisso. Per un'installazione in `/wiki`, sostituire **entrambe** le occorrenze del prefisso e utilizzare `--base-url=https://your-host.example/wiki`. Il percorso della directory è un percorso del file system; il prefisso è un percorso URL, non il nome della directory dell'applicazione. Mantieni privati `config/` e `data/` fuori dall'alias esposto. Convalida Apache prima del suo normale ricaricamento.

<a id="6-prepare-optional-packages-in-the-cli"></a>

## 6. Preparare i pacchetti opzionali in CLI

Il programma di installazione grafico non può eseguire Composer come account Web Apache. Dalla directory di rilascio, il proprietario del codice prepara i moduli opzionali. Senza un elenco esplicito, il comando prepara **tutti** i moduli opzionali perché la procedura guidata li seleziona tutti per impostazione predefinita:

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

Per un insieme più piccolo di funzionalità aggiuntive, come Calendario ed E-mail, prepara questi moduli e deseleziona gli altri moduli opzionali nella procedura guidata:

```bash
php scripts/installation_packages.php prepare --modules=calendar,email
php scripts/installation_packages.php status
```

Per non installare moduli opzionali, utilizzare `php scripts/installation_packages.php prepare --modules=` e deselezionare tutti i moduli opzionali nella procedura guidata. **Auth, ORM, Menu, Theme, Backup e Audit** sono richiesti in ogni installazione e rimangono installati e abilitati. Non elencarli in `--modules`, che accetta solo moduli opzionali.

Il comando `status` mostra quali pacchetti sono effettivamente installati. La procedura guidata contrassegna un pacchetto mancante per la preparazione di CLI; ricaricare la pagina in seguito. Non concedere all'account Web l'accesso in scrittura a `vendor/` o `composer.json` per aggirare questo limite.

<a id="7-run-the-graphical-installer"></a>

## 7. Eseguire il programma di installazione grafico

Crea l'URL del programma di installazione monouso:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Includere la sottodirectory in `--base-url` quando applicabile. Apri l'URL del token in una finestra del browser privata, senza condividerlo o crearne uno screenshot. La procedura guidata controlla i requisiti e l'accesso al database, richiede l'identità del sito, almeno una lingua, il fuso orario, il primo amministratore e i moduli opzionali (tutti selezionati per impostazione predefinita), quindi mostra una revisione prima dell'installazione. Importa le lingue della guida utente selezionate.

La procedura guidata crea file privati come account Web. Prima del primo comando CLI del manutentore, finalizzare l'accesso condiviso solo ai relativi file di runtime; questo attiva anche le maschere Linux ereditate ACL sui file inizialmente creati con la modalità `0600`. Il codice di servizio statico PHP non è stato modificato:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo find data -type f -exec chmod 0660 {} +
sudo find config -type f \( -name '*.json' -o -name '*.lock' \) -exec chmod 0660 {} +
for SIMBIOZA_FILE in database.php env.php installation.php modules.php languages.php; do
  if [ -f "config/$SIMBIOZA_FILE" ]; then
    sudo chmod 0660 "config/$SIMBIOZA_FILE"
  fi
done
```

Al termine dell'installazione, rimuovere il record di preparazione completato. `cleanup` non disinstalla pacchetti né elimina dati. Tutti e sei i moduli richiesti, incluso il Backup, rimangono installati:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Verifica che non ci siano migrazioni in sospeso, che accesso e pagine iniziali funzionino e che il percorso `/install` sia bloccato. Esegui il backup di database, `config/`, file degli utenti e dati del tema prima di rendere il sito operativo.

<a id="maintenance-after-installation"></a>

## Manutenzione dopo l'installazione

Conserva i comandi CLI di routine nell'account del proprietario del codice, mai nell'account Web e non in root. Eseguire il backup del database, delle directory attive `HPH_CONFIG_PATH` e persistenti `data/` prima di un aggiornamento.

Controlli di sola lettura:

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

Per aggiungere un modulo in un secondo momento, esegui `vendor/bin/hph modules add calendar --fresh`; `modules disable calendar` conserva i propri dati e `modules enable calendar` li abilita nuovamente. La rimozione utilizza `modules remove calendar --yes` e crea un backup del modulo; ispezionarlo con `modules backups calendar` prima di un `modules add calendar --restore` deliberato. Queste sono alternative, non un blocco da eseguire uno dopo l'altro.

I Language Pack sono indipendenti dalle versioni dell'applicazione. Utilizza `vendor/bin/hph languages install de` o `vendor/bin/hph languages update`; la GUI della lingua funziona anche quando le sue directory di runtime sono scrivibili.

Quando intendi aggiornare l'applicazione, utilizza `php update.php` come proprietario del codice/manutentore della distribuzione. Il programma di aggiornamento si aggiorna prima, conserva i moduli selezionati e la configurazione privata, applica le migrazioni e aggiorna le guide in bundle. Non usare al suo posto un `composer update` autonomo su un sito installato. Prima delle migrazioni, un aggiornamento non riuscito può eseguire il rollback del codice; dopo l'inizio delle migrazioni, la manutenzione rimane abilitata per il ripristino controllato. Non eliminare mai `data/update-maintenance.json` per nascondere un aggiornamento non completato.

Successivamente controlla zero migrazioni in sospeso, accesso reale, contenuti e allegati. Gli esempi utilizzano i percorsi predefiniti `config/` e `data/`; se in seguito si scelgono livelli di configurazione esterni, mantenere esplicite le relative autorizzazioni e la copertura del backup.
