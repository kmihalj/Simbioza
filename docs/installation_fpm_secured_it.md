# Installa Simbioza con PHP-FPM protetto

<a id="install-simbioza-with-secured-php-fpm"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Versione croata](installation_fpm_secured_hr.md)

Questa guida completa inizia da una directory dell'applicazione vuota e presuppone che non esistano ancora account FPM/deploy di Simbioza. Non hai bisogno di un'altra guida di installazione. Lo strumento di configurazione crea account dedicati, gruppi, un pool/servizio e un helper limitato: il processo web non può scrivere codice, ma può richiedere operazioni di pacchetto dal processo deploy separato. Questa è la modalità consigliata. Per la reinstallazione, un amministratore deve prima controllare le identità esistenti, i nomi delle istanze e i servizi; non creare mai un secondo sito con la stessa identità.

**SimpleSAMLphp è facoltativo.** Il login Simbioza locale, OIDC, CAS e OAuth2 non lo richiedono. Completa prima i passaggi da 1 a 7. Seguire la procedura dettagliata facoltativa SAML solo se questa particolare installazione richiede l'autenticazione SAML.

<a id="1-prepare-the-host"></a>

## 1. Preparare l'host

Inizia con una normale shell di amministratore su Linux o macOS. Hai bisogno di un dominio che punti al server e di un certificato TLS valido per gli esempi HTTPS. Esegui solo i comandi per il tuo sistema operativo. Non continuare dopo un errore.

Su Debian/Ubuntu, installa gli strumenti e le estensioni PHP:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
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

## 2. Scaricare una versione pulita di Simbioza 0.3.40

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
git clone --depth 1 --branch 0.3.40 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Aspettatevi `0.3.40` e `success` per ogni requisito della piattaforma. L'avviso **detached HEAD** di Git è normale quando si effettua il checkout di un tag di rilascio. Non eseguire Composer con `sudo`, copiare la configurazione o il database di un altro sito, includere `.git/` nell'applicazione distribuita o utilizzare `--no-dev` qui. La versione 0.3.0 è una base di nuova installazione, non un aggiornamento da 0.2.x.

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

<a id="4-create-the-dedicated-pool"></a>

## 4. Creare il pool dedicato

Su Debian, installare il pacchetto PHP-FPM per la stessa versione PHP utilizzata dal pacchetto CLI e dal pacchetto `acl`. Dalla directory di rilascio, eseguire il passaggio di sistema una tantum:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

Su macOS con Homebrew PHP, utilizza il percorso effettivo dell'applicazione e il binario FPM:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Per un secondo sito, ripetere entrambi i comandi con le opzioni corrispondenti, ad esempio `--instance=second --listen=127.0.0.1:9076`. Utilizzare nuovamente le stesse due opzioni con `--finalize` nel passaggio 7. Non ometterle accidentalmente: l'istanza predefinita senza suffisso è un servizio di sistema diverso.

<a id="what-step-4-generates-and-starts"></a>

### Cosa genera e avvia il passaggio 4

**Non creare o compilare `php-fpm.conf` manualmente.** Il comando `--install` sopra scrive la configurazione FPM dedicata completa, ne verifica la sintassi, registra il servizio e lo avvia/riavvia. Il seguente comando `--check` non modifica le impostazioni: crea e rimuove richieste diagnostiche di breve durata tramite l'helper reale. Verifica sia il round trip dell'helper che il supporto CLI per gli aggiornamenti in background. Un controllo fallito restituisce uno stato di uscita diverso da zero. Aspettatevi `[OK] Real Setup helper round trip` e `[OK] Background updater CLI runtime`; non continuare con Apache o con la procedura guidata se uno dei due manca o fallisce.

Il controllo esplicito `sudo -H -u fpm-simbioza` utilizza immediatamente l'account di runtime appena creato, senza dipendere dall'aggiornamento dell'appartenenza al gruppo da parte della sessione SSH corrente. Per un'istanza denominata, utilizza anche l'account runtime dell'istanza, ad esempio `fpm-simbioza-second`. L'helper rimane di proprietà di root e limitato alle azioni approvate; non fornire autorizzazioni di scrittura del codice al processo Web né disabilitare la sandbox del file system.

Su Linux, l'istanza predefinita utilizza `/etc/php/<major.minor>/php-fpm-simbioza/php-fpm.conf`, dove la versione corrisponde a CLI PHP utilizzato nel passaggio 4. Con PHP 8.4, il file esatto è `/etc/php/8.4/php-fpm-simbioza/php-fpm.conf`. Contiene sia `[global]` che il pool dedicato `[simbioza]`: i relativi account, il listener locale, percorsi dell'applicazione e `HPH_CONFIG_PATH`, directory temporanee/sessioni private e limiti PHP. Questo **non** è il file condiviso `/etc/php/8.4/fpm/php-fpm.conf` o `pool.d/www.conf`. Il generatore scrive anche `php-fpm-simbioza.service`, incluso l'isolamento del filesystem e la maschera delle autorizzazioni. Queste impostazioni di systemd non sono voci INI FPM. Per verificare quale configurazione utilizza il servizio dedicato, senza modificarla:

```bash
sudo systemctl show php-fpm-simbioza.service -p ExecStart &&
sudo systemctl is-active php-fpm-simbioza.service
```

Per un'istanza denominata, la directory di configurazione e il nome del servizio ricevono entrambi il suffisso corrispondente, ad esempio `php-fpm-simbioza-second`. Su macOS, lo strumento crea invece `$(brew --prefix)/etc/simbioza/php-fpm.conf`, che include `pool.conf` nella stessa directory, e registra il servizio launchd dedicato. Non sostituire un file generato correttamente con la configurazione di un altro sito. La configurazione SimpleSAMLphp viene aggiunta solo se si seguono i passaggi facoltativi SAML. Il passaggio 5 configura comunque Apache/Nginx separatamente; la generazione di FPM non configura il server web. Non è necessario alcun ulteriore riavvio manuale dell'FPM dopo un passaggio 4 riuscito e invariato.

Il generatore 0.3.4 imposta launchd `Umask` sull'intero **7** (ottale `0007`), corrispondente a `UMask=0007` di Linux. Sia il processo Web che il processo deploy con autorizzazioni limitate utilizzano questa maschera; il processo di lavoro la imposta **dopo** aver cambiato account. Il loro `TMPDIR` è `data/tmp` di questa applicazione, quindi i blocchi temporanei condivisi rimangono nella stessa directory privata del gruppo di runtime. I nuovi file di registro ordinari sono `0660`, non file `0644` di proprietà di FPM a cui il programma di aggiornamento non può accodarsi.

Su macOS, verifica il servizio generato prima di aprire la procedura guidata:

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

Per un'istanza denominata, utilizzare il file di servizio corrispondente, ad esempio `hr.simbioza-second.php-fpm.plist`. Una maschera mancante è un prerequisito non riuscito, non un motivo per rendere il codice scrivibile o eseguire il programma di aggiornamento come root. I file di sessione rimangono privati (`0600`); non rendere ricorsivamente scrivibili tutti i dati.

Il controllo non modifica le impostazioni dell'applicazione. Il pool predefinito è in ascolto su `127.0.0.1:9075`; mantieni quella porta locale sul server. Lo strumento fornisce all'account FPM l'accesso in scrittura ai dati di runtime, non all'intera versione, e installa un helper strettamente limitato per le modifiche ai pacchetti. Crea inoltre i gruppi di distribuzione e runtime. Controllare ogni risultato diagnostico prima di continuare. Non utilizzare `chmod 777`.

<a id="accounts-groups-and-permissions-created-by-the-tool"></a>

### Account, gruppi e autorizzazioni creati dallo strumento

Il comando `--install` sopra include la creazione di account/gruppi; non eseguire separatamente `useradd` o `groupadd`. Vengono create le identità mancanti, mentre le identità esistenti con questi nomi vengono riutilizzate senza sostituire il relativo UID/GID. Lo strumento garantisce i gruppi primari richiesti e le adesioni supplementari. Per un'istanza denominata, ogni nome riportato di seguito riceve lo stesso suffisso `-instance`.

Quando si reinstalla nello **stesso percorso dell'applicazione**, eseguire comunque `--install` del passaggio 4: aggiorna anche l'helper di proprietà di root all'esterno della directory dell'applicazione. L'eliminazione dei file dell'applicazione non rimuove l'helper. Gli account/gruppi esistenti vengono riutilizzati; non è prevista la ricreazione manuale dell'account. Una configurazione Apache esistente può essere conservata se corrisponde al passaggio 5, incluso il percorso pubblico reale, il gestore corretto e le classi `AllowOverride`.

| Identità | Scopo |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Account del servizio Web e gruppo primario bloccati; non è possibile scrivere il codice dell'applicazione. |
| `simbioza-deploy` / `deploy-simbioza` | Account di distribuzione e gruppo di manutenzione del codice bloccati; anche il tuo account manutentore si unisce a questo gruppo. |
| `run-simbioza` | Gruppo di runtime privato condiviso per FPM, distribuzione e manutentore. |

Su Linux, controlla il risultato dopo esserti ricollegato a SSH:

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

Il codice utilizza la proprietà della distribuzione e un sandbox FPM di sola lettura; le directory di runtime private utilizzano il gruppo `run-simbioza` (`2770`) e la root di configurazione utilizza una directory con sticky bit (`3770`) per gli aggiornamenti atomici controllati. La normale condivisione di runtime utilizza queste autorizzazioni di gruppo e non ampie concessioni di scrittura ACL. Con la configurazione privata opzionale SimpleSAMLphp, lo strumento imposta inoltre l'accesso Linux e gli ACL predefiniti fornendo `app-simbioza` **solo lettura/attraversamento** su tale configurazione. La procedura dettagliata SAML di seguito include la configurazione e la verifica ACL. Non applicare la ricetta FPM ordinaria/condivisa `www-data` write-ACL all'FPM protetto.

Con la directory di configurazione dell'applicazione predefinita, Auth, Email, HTML Editor, Workspace, Menu, Theme e Backup dispongono di directory dei moduli scrivibili in runtime. Backup ne ha bisogno per la pubblicazione atomica del descrittore del provider. La configurazione del servizio PHP gestito dal rilascio rimane di proprietà della distribuzione e di sola lettura per FPM. Lo strumento non ricrea `data/config` né concede diritti di runtime ai file legacy `editor-html.php`, `email.php` o `workspace.php` rimossi.

<a id="5-configure-the-web-server"></a>

## 5. Configurare il server web

**Scegli il percorso prima di eseguire i comandi:**

- **L'host virtuale HTTPS esistente e un prefisso URL come `/simbioza`:** vai direttamente alle [istruzioni sul prefisso URL](#optional-install-under-a-url-prefix-such-as-simbioza). Modifica quel vhost esistente. Non creare `simbioza.conf` e non eseguire `a2ensite simbioza`; quel comando abilita solo un vhost separato appena creato.
- **Un nuovo nome host dedicato:** utilizza la configurazione Apache o Nginx di seguito.

Scegli **una** di queste configurazioni. Servono un nome host dedicato su `/`; sostituire il nome host, il percorso di rilascio e le impostazioni del certificato TLS. Per l'installazione di una sottodirectory, configurare anche il prefisso URL nel server Web e passare l'URL completo a `install:prepare` nel passaggio 6.

<a id="apache-with-php-fpm"></a>

### Apache con PHP-FPM

Abilita Apache `rewrite`, `proxy`, `proxy_fcgi` e TLS. Dirigere le richieste PHP per questo sito al pool dedicato; non instradarli attraverso `mod_php`.

Per Apache su Debian/Ubuntu, abilita i suoi moduli proxy FPM e crea il sito:

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Su macOS, modifica `/opt/homebrew/etc/httpd/httpd.conf` con `sudoedit`. Abilita `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module` e `socache_shmcb_module`. Aggiungi `Listen 443` se nessuna direttiva esistente è in ascolto lì. Non aggiungere un gestore mod_php per questo sito.

Per un nuovo Apache Homebrew, imposta esplicitamente l'identità dei processi di lavoro condivisi in `httpd.conf` (non eseguire i processi web con l'account proprietario del codice):

```apache
User _www
Group _www
```

Nella configurazione seguente, sostituisci il dominio, i percorsi del certificato TLS e **entrambi** i percorsi `/srv/simbioza/public` con il percorso effettivo dell'applicazione (su macOS, in genere `/Users/Shared/Simbioza/simbioza/public`). Apache non espande `$SIMBIOZA_APP_ROOT` dal tuo terminale.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public
    ProxyTimeout 900

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler "proxy:fcgi://127.0.0.1:9075"
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

Il `public/.htaccess` incluso utilizza `DirectoryIndex index.php`, che richiede la classe di override `Indexes`; le regole di riscrittura necessitano di `FileInfo` e `Options -Indexes` necessitano di `Options`. Mantieni tutte e tre le classi in `AllowOverride`. Ciò **non** abilita gli elenchi di directory: `Options -Indexes` li disabilita comunque. Una classe mancante può produrre HTTP 500 (`DirectoryIndex not allowed here`) anche quando `apache2ctl configtest` riporta `Syntax OK`, perché `.htaccess` viene letto quando una richiesta raggiunge quella directory. Dopo il ricaricamento, controlla l'URL pubblico effettivo prima di continuare con la procedura guidata. Consultare il [riferimento Apache DirectoryIndex](https://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryindex). Non esporre la cartella superiore della versione installata come root del documento.

Solo per il **nuovo `simbioza.conf` separato creato sopra**, testare e avviare Apache. Salta questo blocco quando usi un vhost condiviso esistente. Su Debian/Ubuntu:

```bash
test -f /etc/apache2/sites-available/simbioza.conf &&
sudo a2ensite simbioza &&
sudo apache2ctl configtest &&
sudo systemctl enable --now apache2 &&
sudo systemctl reload apache2
```

Su macOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Aspettatevi `Syntax OK`; non avviare/ricaricare dopo un controllo fallito. Per un Apache già in esecuzione, utilizzare un ricaricamento corretto convalidato invece di avviare un secondo servizio. Proseguire con l'installatore; l'alternativa Nginx di seguito non è un passaggio aggiuntivo.

<a id="nginx-with-php-fpm"></a>

### Nginx con PHP-FPM

Nginx non può eseguire `mod_php`; invia richieste PHP allo stesso pool FPM dedicato. Mantieni insieme le regole `SCRIPT_FILENAME` e `try_files`:

Scegli questa alternativa **invece di Apache**: utilizza `nginx` anziché `apache2` nell'elenco dei pacchetti del passaggio 1 (`brew install nginx` su macOS) e non eseguire i comandi di configurazione/avvio di Apache. Modifica `/etc/nginx/sites-available/simbioza` su Debian/Ubuntu o `/opt/homebrew/etc/nginx/nginx.conf` su macOS. Sostituisci il dominio, i percorsi dei certificati e la radice dell'applicazione nel blocco server completo di seguito.

```nginx
server {
    listen 443 ssl;
    server_name simbioza.example.org;
    root /srv/simbioza/public;
    index index.php;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9075;
        fastcgi_read_timeout 900s;
    }

    location ~ /\. {
        deny all;
    }
}
```

Su Debian/Ubuntu, abilita il nuovo blocco server, prova e avvia:

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

Su macOS: `sudo /opt/homebrew/bin/nginx -t`, quindi `sudo /opt/homebrew/bin/brew services start nginx`. Se il collegamento o il servizio esiste già, ispezionalo anziché duplicarlo.

Testare la configurazione di Nginx prima di ricaricare. Il listener FPM non deve essere raggiungibile da una rete non attendibile. La preparazione del pacchetto tramite il programma di installazione grafico può richiedere diversi minuti; l'attesa del gateway Apache/Nginx di 900 secondi impedisce un 504 predefinito di 60 secondi mentre il lavoratore limitato è ancora in esecuzione. Modificare di conseguenza eventuali timeout aggiuntivi del proxy inverso upstream, ma non disattivare i limiti delle richieste FPM o del server Web per siti non correlati. Il pool dedicato generato da `configure_fpm_setup.php` imposta anche `max_execution_time` di PHP su 900 secondi. Questo è importante: sulla build macOS/PHP 8.5 testata, un valore di `0` ha comunque terminato una richiesta lunga dopo un minuto, anche se non dovrebbe significare alcun limite. Controlla entrambe le impostazioni di timeout nel pool generato e prova una preparazione del pacchetto più lunga.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Facoltativo: installazione con un prefisso URL come /simbioza

Per un nome host condiviso, posiziona questo blocco **all'interno dell'host virtuale HTTPS esistente**, invece di creare un altro host virtuale con lo stesso nome. Sostituisci il percorso di rilascio, il prefisso URL e (per FPM) la porta del pool:

I tre percorsi hanno finalità diverse:

| Impostazione | Esempio |
| --- | --- |
| Root dell'applicazione utilizzata nei passaggi 2 e 4 | `/srv/simbioza` |
| Directory pubblica utilizzata sia da `Alias` che da `<Directory>` | `/srv/simbioza/public` |
| Prefisso URL pubblico | `/simbioza` |

La radice dell'applicazione non deve trovarsi all'interno del nome host condiviso `DocumentRoot`. Tienilo fuori da quell'albero pubblico. Per questa configurazione non è richiesto alcun collegamento simbolico alla directory dell'applicazione. Il collegamento simbolico di abilitazione del sito Nginx riportato sopra non è correlato: abilita la configurazione del server, non i file dell'applicazione.

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9075"
    </FilesMatch>
</Directory>
```

Imposta anche `ProxyTimeout 900` in questo vhost esistente. Se una vecchia configurazione contiene `<Directory "/var/www/html/simbioza">` per un precedente collegamento simbolico alla directory pubblica, **sostituisci quel blocco** con il vero blocco della directory pubblica sopra; aggiungere solo `Alias` non è sufficiente. Il gestore PHP deve applicarsi alla destinazione effettiva del file system dell'alias. Non esporre la directory principale dell'applicazione, `config/`, `data/` o `vendor/` per far funzionare un percorso obsoleto.

Per una root dell'applicazione `/data/www/simbioza`, entrambi i percorsi nel blocco diventano `/data/www/simbioza/public`; l'URL pubblico rimane `/simbioza`. Non modificare `SIMBIOZA_APP_ROOT` né rigenerare il pool semplicemente per modificare la mappatura dell'URL. Se la root dell'applicazione stessa cambia, anche i suoi percorsi FPM/helper necessitano di una riconfigurazione esplicita; un alias Apache non esegue tale riposizionamento.

Con Puppet/Ansible, apporta la modifica equivalente nella definizione di vhost gestito autorevole. Non modificare il vhost generato come correzione permanente, non creare un vhost HTTPS duplicato o rimuovere gli alias di altre applicazioni. Se l'opzione SAML ha già un alias più lungo come `/simbioza/simplesaml`, mantieni quell'alias **prima** dell'alias generale `/simbioza`. SAML rimane facoltativo.

Dopo aver modificato **solo la configurazione dell'alias/directory di Apache**, convalidala e ricarica Apache in modalità graceful. Lo Step 4 ha già avviato il servizio FPM dedicato:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Utilizzare questo blocco Linux invece dei comandi `a2ensite simbioza` del nuovo sito quando il vhost condiviso HTTPS esiste già. I comandi di convalida/ricaricamento Homebrew sono elencati nella sezione Apache precedente. Un servizio FPM in esecuzione e una sintassi Apache valida non dimostrano che la richiesta utilizza il percorso corretto dell'applicazione: testare anche l'URL pubblico `/simbioza/`. Interrompi su HTTP 500 o un `bootstrap.php` mancante; ispezionare l'alias, il gestore di directory e i percorsi FPM invece di continuare con la procedura guidata o modificare le autorizzazioni in generale.

<a id="reinstallation-only-an-obsolete-custom-fpm-bind-mount"></a>

#### Solo reinstallazione: un bind mount FPM personalizzato obsoleto

Un'installazione pulita non crea un drop-in per un vecchio collegamento simbolico a una directory pubblica. Se un'installazione esistente ne aveva uno, controllalo prima di riavviare:

```bash
sudo systemctl cat php-fpm-simbioza.service
sudo systemctl show php-fpm-simbioza.service -p DropInPaths
```

Utilizzare il nome del servizio con suffisso per un'istanza denominata. Dopo essere passato all'alias diretto, rimuovi **solo un drop-in personalizzato obsoleto confermato** il cui unico scopo era vincolare il percorso del collegamento simbolico rimosso. Non eliminare mai l'intera directory `.service.d` né rimuovere i bind mount effettivi dell'applicazione generata. Un collegamento obsoleto a un percorso inesistente può impedire il successivo avvio di FPM. Dopo la rimozione specifica, utilizza questa sequenza Linux completa invece del ricaricamento solo Apache sopra. Si applica anche dopo aver modificato deliberatamente la configurazione FPM dedicata; `daemon-reload` è necessario quando un'unità systemd o un drop-in è cambiato, non semplicemente perché `php-fpm.conf` è cambiato:

```bash
SIMBIOZA_PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')" &&
sudo systemctl daemon-reload &&
sudo "/usr/sbin/php-fpm$SIMBIOZA_PHP_VERSION" --test \
  --fpm-config="/etc/php/$SIMBIOZA_PHP_VERSION/php-fpm-simbioza/php-fpm.conf" &&
sudo apache2ctl configtest &&
sudo systemctl restart php-fpm-simbioza.service &&
sudo systemctl is-active php-fpm-simbioza.service &&
sudo systemctl reload apache2
```

Utilizza il file binario FPM, il percorso di configurazione e il suffisso del servizio corrispondenti per la tua installazione. È previsto un test di configurazione FPM riuscito, `Syntax OK` e `active`. La catena `&&` si arresta dopo un comando fallito; non continuare con la procedura guidata dopo un errore. Un riavvio di FPM interrompe le richieste correnti, quindi non eseguirlo durante il lavoro di installazione/aggiornamento. Si tratta di una pulizia dell'host una tantum o dell'applicazione di una modifica intenzionale della configurazione, **non è un passaggio aggiuntivo richiesto in un'installazione pulita**. Una configurazione FPM invariata non necessita di riavvio solo per la modifica dell'alias Apache.

Utilizzare `--base-url=https://your-host.example/simbioza` per il programma di installazione, non il nome host senza il prefisso. Per un'installazione in `/wiki`, sostituire **entrambe** le occorrenze del prefisso e utilizzare `--base-url=https://your-host.example/wiki`. Il percorso della directory è un percorso del file system; il prefisso è un percorso URL, non il nome della directory dell'applicazione. Mantieni privati `config/` e `data/` fuori dall'alias esposto. Convalida Apache prima del suo normale ricaricamento.

<a id="6-run-the-graphical-installer"></a>

## 6. Eseguire il programma di installazione grafico

Genera un URL di installazione monouso dalla directory di rilascio:

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Se hai scelto `--instance=second`, utilizza invece `-u fpm-simbioza-second`. Viene eseguito PHP come identità FPM, non come root. Per un sito all'indirizzo `/simbioza`, utilizzare invece `--base-url=https://simbioza.example.org/simbioza`. Apri l'URL in una finestra del browser privata; non condividere o fare screenshot del suo token. La procedura guidata controlla i requisiti e l'accesso al database, richiede l'identità del sito, le lingue, il fuso orario, il primo amministratore e i moduli opzionali, quindi mostra una revisione prima dell'installazione. Tutti i moduli opzionali sono preselezionati; deseleziona quelli che non desideri. Con l'helper dedicato in funzione, i pacchetti opzionali selezionati vengono installati tramite la GUI.

Se ometti intenzionalmente l'helper dedicato, prepara i pacchetti opzionali selezionati come utente di distribuzione con `php scripts/installation_packages.php prepare` per tutti i moduli o aggiungi `--modules=calendar` per una selezione più piccola. Abbina l'elenco alle tue selezioni e ricarica il programma di installazione. Senza l'helper, le modifiche successive al pacchetto GUI non sono disponibili. L'installazione/aggiornamento/rimozione della lingua scrive solo i dati di runtime e la configurazione e non richiede questo helper. Assicurarsi che tali directory siano scrivibili da PHP; vedere [localizzazione](localization_en.md).

Dopo l'installazione, accedi come amministratore. In **Impostazioni → Configurazione e moduli**, verifica che il pool dedicato, l'helper e le autorizzazioni di runtime siano tutti segnalati come pronti. Deve essere superato anche il controllo dell'aggiornamento in background CLI e devono essere disponibili le operazioni del pacchetto GUI e gli aggiornamenti dell'applicazione. Un risultato grigio **Non verificato** non equivale a una verifica riuscita: risolvi prima il precedente controllo dell'helper fallito; ciò non significa che manchino le estensioni CLI. La procedura guidata importa le lingue della guida utente selezionate.

<a id="7-finalize-permissions-and-verify"></a>

## 7. Finalizzare le autorizzazioni e verificare

Dopo la procedura guidata, disconnettersi e rientrare in SSH/terminale per attivare le nuove appartenenze al gruppo di distribuzione/runtime. Ripristina `SIMBIOZA_APP_ROOT` nel percorso selezionato nel passaggio 2, quindi esegui i comandi corrispondenti:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
vendor/bin/hph modules migrate-status &&
composer check-platform-reqs
```

Su macOS, aggiungi lo stesso argomento `--php-fpm` e il percorso dell'applicazione utilizzati nel passaggio 4. Esci e rientra in modo che il manutentore riceva le nuove appartenenze al gruppo.
Verifica che non ci siano migrazioni in sospeso, che accesso e pagine iniziali funzionino e che il percorso `/install` sia bloccato. Esegui il backup di database, `config/`, file degli utenti e dati del tema.

La configurazione una tantum di `sudo` **non** significa che i normali aggiornamenti richiedono root. I comandi di manutenzione sono inclusi alla fine di questa guida.

<a id="optional-simplesamlphp-integration"></a>

## Integrazione SimpleSAMLphp opzionale

**Salta l'intera sezione a meno che non ti serva SAML.** Non è correlata alla configurazione di OIDC, CAS, OAuth2 o dell'accesso locale. Mantieni disponibili l'amministratore locale e l'accesso break-glass finché SAML non funziona.

<a id="s1-select-the-library-and-public-addresses"></a>

### S1. Seleziona la libreria e gli indirizzi pubblici

Completare prima l'installazione normale. Nella shell del manutentore, imposta i percorsi e gli URL effettivi. Esempio di un nome host dedicato:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

Per un sito all'indirizzo `https://your-host.example/simbioza`, utilizzare invece `SIMBIOZA_SAML_URL=https://your-host.example/simbioza/simplesaml/`. Mantieni la barra finale. La radice della libreria **non** è la directory di Simbioza. È possibile riutilizzare una libreria esistente gestita dal sistema, ad esempio `/usr/share/simplesamlphp-aai`; non modificare la configurazione condivisa, i certificati o l'SP di un'altra applicazione.

Se è già installata una libreria compatibile, salta il blocco del download. Altrimenti questo esempio installa l'upstream **2.5.3.1 full**, con il relativo checksum pubblicato. Richiede PHP **8.3 o successivo** e le estensioni dai [requisitiSimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html). Controlla le [versioni upstream](https://simplesamlphp.org/download/) per gli aggiornamenti di sicurezza; quando scegli un'altra versione, modifica insieme URL e checksum. Eseguire quanto segue solo per una directory di libreria **nuova** e inesistente:

Su Debian/Ubuntu, installare prima gli strumenti di download con `sudo apt-get install curl openssl`. macOS fornisce già `curl` e `openssl`.

```bash
test ! -e "$SIMBIOZA_SAML_ROOT" && test ! -L "$SIMBIOZA_SAML_ROOT" &&
SIMBIOZA_SAML_FETCH="$(mktemp -d)" &&
curl --fail --location --proto '=https' --tlsv1.2 \
  --output "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  https://github.com/simplesamlphp/simplesamlphp/releases/download/v2.5.3.1/simplesamlphp-2.5.3.1-full.tar.gz &&
test "$(openssl dgst -sha256 "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" | awk '{print $NF}')" = \
  b199ebcba6e30220e7b5df2783ca42f8947dd9bdf046e9e6302fef3d3a438d01 &&
sudo install -d -m 0755 "$SIMBIOZA_SAML_ROOT" &&
sudo tar -xzf "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  --strip-components=1 -C "$SIMBIOZA_SAML_ROOT"
```

Arresta se qualsiasi comando fallisce. Verifica la libreria selezionata:

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

<a id="s2-prepare-private-configuration-and-trusted-metadata"></a>

### S2. Preparare la configurazione privata e i metadati attendibili

Da `$SIMBIOZA_APP_ROOT`, crea directory private e copia **modelli**. Questo blocco rifiuta di sovrascrivere la configurazione. Quando si ripristinano le impostazioni conservate per lo **stesso SP**, ripristinare invece `config`, certificati e metadati richiesti; non copiare le credenziali di un altro sito né ripristinare le sessioni/la cache/il database dell'applicazione precedente.

```bash
mkdir -p "$SIMBIOZA_APP_ROOT/data/saml/config" \
  "$SIMBIOZA_APP_ROOT/data/saml/metadata" "$SIMBIOZA_APP_ROOT/data/saml/cert" \
  "$SIMBIOZA_APP_ROOT/data/saml/data" "$SIMBIOZA_APP_ROOT/data/saml/cache" \
  "$SIMBIOZA_APP_ROOT/data/saml/logs" "$SIMBIOZA_APP_ROOT/data/saml/tmp" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/config.php" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/config.php.dist" "$SIMBIOZA_SAML_CONFIG/config.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/authsources.php.dist" "$SIMBIOZA_SAML_CONFIG/authsources.php"
```

Modifica `config.php` con `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`. Imposta queste chiavi all'interno dell'array `$config` esistente. Sostituisci i percorsi del filesystem di esempio con il percorso dell'applicazione; I file PHP non espandono le variabili del terminale.

| Chiave | Valore o azione |
| --- | --- |
| `baseurlpath` | Il valore completo HTTPS `SIMBIOZA_SAML_URL`, inclusa la barra finale. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` e `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | Le directory private corrispondenti `data`, `cache`, `logs`, `tmp` in `/srv/simbioza/data/saml`. |
| `metadata.sources` | Una fonte attendibile, ad es. `[['type' => 'flatfile']]` utilizzando privato `metadatadir`; utilizza il meccanismo verificato della tua federazione quando applicabile. |
| `secretsalt`, `assets['salt']` | Due diversi valori casuali, ciascuno generato con `openssl rand -hex 32`; modifica `salt` all'interno dell'array `assets` esistente, non una nuova chiave `assets.salt` di livello superiore. Non utilizzare mai i valori del modello né pubblicarli. |
| `auth.adminpassword` | Un hash generato in modo interattivo con `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"`; separato dalla tua password Simbioza. |
| `store.type` | `phpsession` per questa procedura dettagliata. |
| `session.phpsession.cookiename` | Un nome specifico del sito, ad es. `SimbiozaSAML`, diverso dai cookie di sessione di Simbioza e di altri siti. |
| `session.phpsession.savepath` | `null`, quindi entrambi gli endpoint utilizzano il `data/sessions` privato di questo pool. |
| `session.cookie.path` | `/` per un nome host dedicato o `/simbioza/` per quel prefisso; deve coprire sia l'applicazione che l'endpoint SAML. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true`; utilizzare HTTPS. |
| `showerrors` | `false`, salvo durante una risoluzione dei problemi svolta in modo controllato. |

In `authsources.php`, configura `default-sp` come `saml:SP` con il tuo `entityID` stabile e l'ID entità `idp` dell'IdP attendibile. Rimuovi le fonti di esempio inutilizzate. Ottieni i metadati IdP dalla tua federazione/amministratore IdP; salvare i metadati attendibili PHP come `data/saml/metadata/saml20-idp-remote.php` o configurare il meccanismo dei metadati supportato dalla federazione. Non disabilitare i controlli della firma né considerare attendibile un certificato di firma non verificato.

Se l'IdP richiede un certificato SP, generare una chiave/certificato privato per un **nuovo** SP in `data/saml/cert` di questo sito e fare riferimento ai nomi dei file tramite `privatekey` e `certificate` in `default-sp`, seguendo la policy di federazione. Per lo **stesso SP registrato**, conservare l'EntityID, le chiavi/certificati esistenti e i metadati pertinenti anziché generare sostituzioni.

<a id="s3-configure-the-dedicated-pool-and-verify-acls"></a>

### S3. Configura il pool dedicato e verifica gli ACL

Da `$SIMBIOZA_APP_ROOT`, esegui nuovamente il generatore per la **stessa applicazione e istanza**, aggiungendo l'argomento di configurazione privata. Questo aggiorna e riavvia solo quel pool dedicato, riutilizzando i suoi account/gruppi esistenti:

```bash
cd "$SIMBIOZA_APP_ROOT" &&
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG"
```

Su macOS aggiungi `--php-fpm="$(brew --prefix)/sbin/php-fpm"` a **tutti e tre** i comandi. Per un'istanza denominata, ripetere anche le opzioni originali `--instance` e `--listen`. Il generatore esporta `SIMPLESAMLPHP_CONFIG_DIR` all'interno del processo di lavoro FPM nonostante `clear_env = yes`; un SSH `export` o Apache `SetEnv` da solo non è sufficiente.

Su Linux, verificare l'accesso in lettura e gli ACL di configurazione privata:

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

È previsto `group:app-simbioza:r-x` e un valore predefinito corrispondente ACL nella directory; i file sono leggibili, non scrivibili, da FPM. La distribuzione mantiene la proprietà. Utilizza identità con suffisso per un'istanza denominata. Su macOS il generatore utilizza la proprietà del gruppo invece dei comandi POSIX ACL di Linux.

<a id="s4-send-the-saml-endpoint-to-the-same-fpm-pool"></a>

### S4. Invia l'endpoint SAML allo stesso pool FPM

Aggiungi questo blocco al **vhost HTTPS esistente**, non a un secondo vhost o a un alias globale SimpleSAMLphp condiviso. Sostituisci il prefisso URL e i percorsi del file system con i valori di S1; Apache non espande le variabili della shell. Un alias SAML più lungo deve apparire **prima** di un alias generale dell'applicazione `/simbioza`.

```apache
Alias /simplesaml "/opt/simplesamlphp/public"
<Directory "/opt/simplesamlphp/public">
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    AcceptPathInfo On
</Directory>
ProxyPassMatch "^/simplesaml/(.+\.php(?:/.*)?)$" "fcgi://127.0.0.1:9075/opt/simplesamlphp/public/$1"
```

Per `/simbioza`, utilizzare `Alias /simbioza/simplesaml` e l'espressione regolare `^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. Per un pacchetto di sistema, sostituire **entrambi** i percorsi `/opt/simplesamlphp/public` con la directory effettiva `public/`. Utilizza la porta effettiva del tuo pool. Il proxy esplicito preserva `module.php/...` PATH_INFO e utilizza l'identità FPM, l'ambiente e la directory della sessione dell'applicazione. Il suo gestore PHP ordinario non copre questo alias separato.

Su Debian/Ubuntu convalida, quindi ricarica con garbo Apache:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Su Homebrew Apache utilizza `sudo /opt/homebrew/bin/httpd -t` e, solo in caso di successo, `sudo /opt/homebrew/bin/httpd -k graceful`. Con Puppet/Ansible, modifica il vhost gestito autorevole anziché un file generato. Per Nginx, utilizza l'[alias ufficiale/configurazione PATH_INFO](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx) con lo stesso pool dedicato e prefisso URL, non un pool predefinito condiviso. Non esporre mai `config/`, `cert/` o metadati privati tramite l'alias web.

<a id="s5-register-the-sp-map-attributes-and-test-authentication"></a>

### S5. Registra l'SP, mappa gli attributi e verifica l'autenticazione

Apri l'URL di base SAML e ottieni i metadati `default-sp` tramite l'interfaccia amministratore SimpleSAMLphp. Invialo all'IdP/amministratore della federazione. Verificare che **EntityID, ACS e SLO** registrati utilizzino il prefisso HTTPS SAML di questo sito e attendere finché la registrazione non sarà attiva.

In Simbioza **Impostazioni → Autenticazione**, abilitare il protocollo SimpleSAMLphp. Crea un profilo SAML con il percorso assoluto `vendor/autoload.php` e `auth_source = default-sp` della libreria. Utilizza **Profilo di prova** per acquisire gli attributi IdP effettivi; scegli un attributo soggetto/login stabile e mappa i campi email/nome. Non indovinare i nomi degli attributi di un provider diverso. Testare nuovamente finché il profilo non sarà operativo, abilitarlo e verificare che gli utenti previsti possano utilizzarlo. La creazione automatica dell'account è una scelta esplicita del profilo.

La GUI scrive le impostazioni di Simbioza su `HPH_CONFIG_PATH/auth/auth.json`; non modifica i file SimpleSAMLphp esterni. Per la configurazione manuale dell'autenticazione, iniziare con `auth.json.dist` del modulo Auth, mai sostituendo un file attivo.

Prova in un browser privato: SAML accedi → torna a Simbioza → pagina protetta → esci → un altro accesso. Il solo caricamento dei metadati non dimostra l'autenticazione. Con `phpsession`, una directory pool/sessione diversa può causare loop di accesso: correggere il routing/ambiente invece di concedere autorizzazioni più ampie. Mantieni l'accesso come amministratore locale fino al completamento del flusso del browser.

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

<a id="external-configuration-layers"></a>

## Livelli di configurazione esterni

Prepara la configurazione completa prima di selezionarla nel file privato `framework.php`. Il pool generato e l'helper fisso impostano esplicitamente gli stessi parametri di base e di override; non dipendono dalla sopravvivenza delle variabili ereditate a `clear_env` o `sudo`. Le regole di isolamento Linux montano i livelli inferiori in sola lettura ed espongono il livello finale insieme alla cartella configurata per le richieste di Setup. Questo permette la scrittura soltanto dove i diritti Unix la consentono già; lo strumento non esegue chown/chmod sui livelli esterni.

Concedi agli account FPM e deploy l'accesso in lettura e attraversamento. Mantieni il codice di servizio PHP di proprietà di deploy e in sola lettura per FPM; assegna ai file di dati e ai lock scrivibili di proprietà del runtime il gruppo runtime condiviso e le autorizzazioni per la sostituzione atomica. Non usare una cartella home protetta o una document root pubblica per la configurazione privata. Crea in anticipo una cartella delle richieste non predefinita con le autorizzazioni condivise richieste. Dopo aver cambiato i percorsi in un sito esistente, ripeti `--install`, poi `--finalize`, usando le stesse opzioni di istanza, porta e SAML. Il solo `--finalize` aggiorna helper e proprietà, non il pool FPM. Consulta la [guida alla configurazione](configuration_en.md).
