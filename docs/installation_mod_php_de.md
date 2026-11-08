# Simbioza mit Apache mod_php installieren

<a id="install-simbioza-with-apache-mod_php"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Kroatische Version](installation_mod_php_hr.md)

Dies ist eine vollständige Neuinstallationsanleitung, von einem normalen Terminal bis zur ersten Administratoranmeldung. Es ist keine weitere Installationsanleitung erforderlich. Apache führt PHP innerhalb seines gemeinsam genutzten Webprozesses aus; Paketvorgänge und Anwendungsaktualisierungen bleiben nur CLI. Nginx kann mod_php nicht verwenden. Machen Sie den Anwendungscode niemals webschreibbar, um eine GUI-Paketinstallation zu erhalten.

<a id="1-prepare-the-host"></a>

## 1. Bereiten Sie den Host vor

Beginnen Sie mit einer normalen Administrator-Shell unter Linux oder macOS. Für die HTTPS-Beispiele benötigen Sie eine Domäne, die auf den Server verweist, und ein gültiges TLS-Zertifikat. Führen Sie nur die Befehle für Ihr Betriebssystem aus. Nach einem Fehler nicht fortfahren.

Installieren Sie unter Debian/Ubuntu die Tools und PHP-Erweiterungen:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql libapache2-mod-php
```

Installieren Sie unter macOS zuerst Homebrew, wenn es nicht installiert ist, dann:

```bash
brew install git rsync composer httpd php
```

Überprüfen Sie die installierten Tools:

```bash
php -v
php -m
composer --version
git --version
```

PHP muss **8.2 oder neuer** sein, Composer muss Version 2 sein und PHP benötigt `ctype dom fileinfo intl json libxml mbstring openssl pdo session xmlreader zip`. Verwenden Sie `pdo_sqlite`, `pdo_mysql` oder `pdo_pgsql` für die von Ihnen gewählte Datenbank. CLI PHP und der Web-Handler PHP müssen übereinstimmende Versionen und Erweiterungen haben.

<a id="2-download-a-clean-simbioza-0339-release"></a>

## 2. Laden Sie eine saubere Version Simbioza 0.3.46 herunter

Wählen Sie **ein** Anwendungsverzeichnis. Unter Linux:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

Verwenden Sie unter macOS die interne Festplatte, nicht einen Entwicklungs-Checkout oder ein mit `noowners` gemountetes Volume:

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Halten Sie diese Hülle offen. Ändern Sie jetzt den gewählten Pfad, wenn Sie eine andere Site benötigen. Das Verzeichnis darf **noch nicht existieren**; Diese Prüfung lehnt es bewusst ab, eine bestehende Installation zu überschreiben. Ein Administrator, der eine Site neu installiert, muss zunächst den alten Dienst und die alten Daten separat verwalten. Nichts hier löscht sie.

Führen Sie den vollständigen Download-Block als Ihr normales Betreuerkonto aus:

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.46 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Erwarten Sie `0.3.46` und `success` für jede Plattformanforderung. Der Git-Hinweis **detached HEAD** ist beim Auschecken eines Release-Tags normal. Führen Sie Composer nicht mit `sudo` aus, kopieren Sie nicht die Konfiguration oder Datenbank einer anderen Site, schließen Sie `.git/` nicht in die bereitgestellte Anwendung ein und verwenden Sie `--no-dev` hier nicht. Bei Version 0.3.0 handelt es sich um eine Neuinstallationsbasislinie, nicht um ein Upgrade von 0.2.x.

Das Standardkonfigurationsstammverzeichnis ist `config/` (`HPH_CONFIG_PATH`), mit einem Unterverzeichnis für jedes Modul. Persistente Dateien gehören unter `data/`. Nur `public/` wird vom Webserver verfügbar gemacht. Behalten Sie die Standardpfade für diese erste Installation bei; Externe Konfigurationsebenen sind eine erweiterte Option und keine Voraussetzung.

<a id="3-choose-and-prepare-the-database"></a>

## 3. Wählen Sie die Datenbank aus und bereiten Sie sie vor

Wählen Sie **eine** Datenbank. SQLite ist der kürzeste Weg und erfordert keinen Datenbankserver oder Datenbankkennwort: Wählen Sie SQLite im Assistenten aus und es wird `data/simbioza.sqlite` erstellt. Fahren Sie mit Schritt 4 fort.

Wenn Sie eine Serverdatenbank auswählen und deren Dienst noch nicht verfügbar ist, installieren Sie **nur die von Ihnen ausgewählte Datenbank**. Unter Debian/Ubuntu: `sudo apt-get install mariadb-server` oder `sudo apt-get install postgresql`. Unter macOS: `brew install mariadb`, dann `brew services start mariadb` oder `brew install postgresql@18`, dann `brew services start postgresql@18`. Für die versionierten Tools Homebrew PostgreSQL verwenden Sie unten `/opt/homebrew/opt/postgresql@18/bin/createuser` und `/opt/homebrew/opt/postgresql@18/bin/createdb`.

Starten Sie für **MySQL** Ihren Dienst MySQL/MariaDB und geben Sie seinen Administrator-Client ein. Erstellen Sie eine leere Datenbank und ein eingeschränktes Anwendungskonto:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-database-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Verwenden Sie Host `127.0.0.1`, den Port Ihres Dienstes (normalerweise `3306`), Datenbank `simbioza`, Benutzer `simbioza` und dieses Passwort im Assistenten. Verwenden Sie nicht den Datenbankadministrator als Konto der Anwendung.

Starten Sie für **PostgreSQL** den Dienst und führen Sie diese Befehle als PostgreSQL-Administrator aus (unter Debian/Ubuntu jeweils `sudo -u postgres` voranstellen):

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Verwenden Sie Host `127.0.0.1`, den Port Ihres Dienstes (normalerweise `5432`), Datenbank und Benutzer `simbioza` sowie das an der privaten Eingabeaufforderung eingegebene Passwort. Überprüfen Sie, ob die lokale TCP-Authentifizierung der Datenbank dieses Konto zulässt. Fügen Sie keine echten Passwörter in die Dokumentation, den Befehlsverlauf oder Git ein.

<a id="4-prepare-narrow-runtime-permissions"></a>

## 4. Bereiten Sie enge Laufzeitberechtigungen vor

Die Systempakete stellen das gemeinsame Webkonto bereit: `www-data` unter Debian/Ubuntu, `_www` für das Apache-Beispiel Homebrew. Bestätigen Sie Ihren tatsächlichen Apache-Benutzer/Ihre tatsächliche Apache-Gruppe, bevor Sie diese Befehle anwenden. Der Code bleibt Eigentum Ihres Betreuers; das Webkonto schreibt nur Laufzeitdaten und dynamische Einstellungen.

Auf Debian/Ubuntu:

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

Auf macOS:

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

Gewähren Sie keinen Web-Schreibzugriff auf das Release-Stammverzeichnis, `src/`, `scripts/`, `public/`, `vendor/`, `composer.json` oder die statische Dienstkonfiguration PHP. Sticky-Konfigurationsverzeichnisse ermöglichen den atomaren Austausch webeigener Einstellungen, ohne den Austausch von Servicecode im Besitz des Betreuers zu ermöglichen. Verwenden Sie niemals `chmod 777`.

<a id="5-confirm-that-apache-actually-runs-mod_php"></a>

## 5. Bestätigen Sie, dass Apache tatsächlich mod_php ausführt.

Die Version CLI PHP allein ist kein Beweis dafür, dass Apache dasselbe PHP verwendet. Überprüfen Sie die aktiven Apache-Module und den Handler. Stellen Sie sicher, dass PHP in einem Browser eine unterstützte Version und die erforderlichen Erweiterungen verwendet. Der übliche nicht threadsichere Build `mod_php` erfordert Apaches `prefork` MPM. Aktivieren Sie nicht sowohl den FPM-Proxy-Handler als auch den `mod_php`-Handler für diese Site.

Aktivieren Sie unter Debian/Ubuntu den PHP-Handler ohne Thread und die erforderlichen Module:

```bash
sudo a2dismod mpm_event
sudo a2enmod mpm_prefork rewrite ssl
sudo a2enmod "php$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Bearbeiten Sie unter macOS `/opt/homebrew/etc/httpd/httpd.conf` mit `sudoedit`. Aktivieren Sie `mpm_prefork_module`, `rewrite_module`, `ssl_module` und `socache_shmcb_module`; Deaktivieren Sie `mpm_event_module`. Fügen Sie `Listen 443` hinzu, wenn dort bereits keine Direktive lauscht, und fügen Sie dann Folgendes hinzu:

```apache
LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so
```

Legen Sie für einen neuen Homebrew-Apache explizit die Shared-Worker-Identität in `httpd.conf` fest (führen Sie Web-Worker nicht als Ihren Code besitzenden Login aus):

```apache
User _www
Group _www
```

Verwenden Sie den folgenden virtuellen Host in der ausgewählten Konfigurationsdatei. Ersetzen Sie die Domänen- und Zertifikatspfade und ersetzen Sie **beide** Anwendungspfade durch den von Ihnen gewählten `SIMBIOZA_APP_ROOT/public`. Die Apache-Konfiguration erweitert Ihre Shell-Variablen nicht.

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

Die `LoadModule`-Direktive des Moduls PHP ist verteilungsspezifisch und muss auf das tatsächlich installierte Modul verweisen. Testen Sie die Apache-Konfiguration vor einem ordnungsgemäßen Neuladen. Konfigurieren Sie für eine Unterverzeichnisinstallation den URL-Alias ​​und verwenden Sie in Schritt 7 die vollständige Basis-URL.

Testen und starten Sie nur diesen konfigurierten Apache-Dienst. Auf Debian/Ubuntu:

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

Auf macOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Erwarten Sie `Syntax OK`. Wenn der Dienst bereits vorhanden ist, verwenden Sie ein validiertes, ordnungsgemäßes Neuladen, anstatt nicht verwandte Websites neu zu starten.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Optional: Installieren Sie unter einem URL-Präfix wie /simbioza

Platzieren Sie diesen Block für einen gemeinsam genutzten Hostnamen **in seinem vorhandenen virtuellen Host HTTPS**, anstatt einen anderen virtuellen Host mit demselben Namen zu erstellen. Ersetzen Sie den Release-Pfad, das URL-Präfix und (für FPM) den Pool-Port:

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

Verwenden Sie `--base-url=https://your-host.example/simbioza` für das Installationsprogramm, nicht den Hostnamen ohne Präfix. Ersetzen Sie für eine Installation unter `/wiki` **beide** Vorkommen des Präfixes und verwenden Sie `--base-url=https://your-host.example/wiki`. Der Verzeichnispfad ist ein Dateisystempfad; das Präfix ist ein URL-Pfad, nicht der Name des Anwendungsverzeichnisses. Halten Sie `config/` und `data/` außerhalb des offengelegten Alias ​​privat. Validieren Sie Apache vor dem ordnungsgemäßen Neuladen.

<a id="6-prepare-optional-packages-in-the-cli"></a>

## 6. Bereiten Sie optionale Pakete im CLI vor.

Das grafische Installationsprogramm kann Composer nicht als Apache-Webkonto ausführen. Aus dem Release-Verzeichnis bereitet der Codeeigentümer optionale Module vor. Ohne eine explizite Liste bereitet der Befehl **alle** optionalen Module vor, da der Assistent sie standardmäßig alle auswählt:

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

Für einen kleineren Satz zusätzlicher Funktionen wie Kalender und E-Mail bereiten Sie diese Module vor und deaktivieren Sie die anderen optionalen Module im Assistenten:

```bash
php scripts/installation_packages.php prepare --modules=calendar,email
php scripts/installation_packages.php status
```

Um keine optionalen Module zu installieren, verwenden Sie `php scripts/installation_packages.php prepare --modules=` und deaktivieren Sie alle optionalen Module im Assistenten. **Auth, ORM, Menü, Design, Backup und Audit** sind bei jeder Installation erforderlich und bleiben installiert und aktiviert. Listen Sie sie nicht in `--modules` auf, das nur optionale Module akzeptiert.

Der Befehl `status` zeigt an, welche Pakete tatsächlich installiert sind. Der Assistent markiert ein fehlendes Paket für die Vorbereitung CLI; Laden Sie die Seite anschließend neu. Gewähren Sie dem Webkonto keinen Schreibzugriff auf `vendor/` oder `composer.json`, um diese Grenze zu umgehen.

<a id="7-run-the-graphical-installer"></a>

## 7. Führen Sie das grafische Installationsprogramm aus

Erstellen Sie die Einmal-Installations-URL:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Fügen Sie das Unterverzeichnis gegebenenfalls in `--base-url` ein. Öffnen Sie die Token-URL in einem privaten Browserfenster, ohne sie zu teilen oder einen Screenshot davon zu machen. Der Assistent prüft die Anforderungen und den Datenbankzugriff, fragt nach der Site-Identität, mindestens einer Sprache, der Zeitzone, dem ersten Administrator und optionalen Modulen (alle standardmäßig ausgewählt) und zeigt dann vor der Installation eine Überprüfung an. Es importiert die ausgewählten Sprachen des Benutzerhandbuchs.

Der Assistent erstellt private Dateien als Webkonto. Schließen Sie vor dem ersten Betreuerbefehl CLI den gemeinsamen Zugriff nur auf seine Laufzeitdateien ab; Dadurch werden auch geerbte Linux-Masken ACL für Dateien aktiviert, die ursprünglich mit dem Modus `0600` erstellt wurden. Der statische Servicecode PHP wird nicht geändert:

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

Entfernen Sie nach erfolgreicher Installation den abgeschlossenen Vorbereitungsdatensatz. `cleanup` deinstalliert keine Pakete und löscht keine Daten. Alle sechs erforderlichen Module, einschließlich Backup, bleiben installiert:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Erwarten Sie keine ausstehenden Migrationen, erfolgreiche Anmeldung und Startseiten sowie eine gesperrte `/install`-Route. Sichern Sie die Datenbank, `config/`, Benutzerdateien und Design-Daten, bevor Sie live gehen.

<a id="maintenance-after-installation"></a>

## Wartung nach der Installation

Behalten Sie routinemäßige CLI-Befehle unter dem Konto des Codebesitzers bei, niemals unter dem Webkonto und nicht unter Root. Sichern Sie vor einem Update die Datenbank, die aktiven Verzeichnisse `HPH_CONFIG_PATH` und die persistenten Verzeichnisse `data/`.

Nur-Lese-Prüfungen:

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

Um später ein Modul hinzuzufügen, führen Sie `vendor/bin/hph modules add calendar --fresh` aus. `modules disable calendar` behält seine Daten und `modules enable calendar` aktiviert sie wieder. Beim Entfernen wird `modules remove calendar --yes` verwendet und eine Modulsicherung erstellt. Überprüfen Sie es mit `modules backups calendar` vor einem absichtlichen `modules add calendar --restore`. Dies sind Alternativen, kein Block, der nacheinander ausgeführt werden muss.

Sprachpakete sind unabhängig von Anwendungsversionen. Verwenden Sie `vendor/bin/hph languages install de` oder `vendor/bin/hph languages update`; die Sprach-GUI funktioniert auch, wenn ihre Laufzeitverzeichnisse beschreibbar sind.

Wenn Sie beabsichtigen, die Anwendung zu aktualisieren, verwenden Sie `php update.php` als Codebesitzer/Betreuer der Bereitstellung. Der Updater aktualisiert sich zunächst selbst, behält ausgewählte Module und private Konfigurationen bei, wendet Migrationen an und aktualisiert gebündelte Anleitungen. Ersetzen Sie nicht einen eigenständigen `composer update` auf einer installierten Site. Vor Migrationen kann ein fehlgeschlagenes Update den Code zurücksetzen. Nach Beginn der Migrationen bleibt die Wartung für eine kontrollierte Wiederherstellung aktiviert. Löschen Sie niemals `data/update-maintenance.json`, um ein unvollendetes Update zu verbergen.

Überprüfen Sie anschließend, ob Migrationen, echte Anmeldungen, Inhalte und Anhänge auf null ausstehen. In den Beispielen werden die Standardpfade `config/` und `data/` verwendet. Wenn Sie sich später für externe Konfigurationsebenen entscheiden, achten Sie darauf, dass deren Berechtigungen und Backup-Abdeckung explizit bleiben.
