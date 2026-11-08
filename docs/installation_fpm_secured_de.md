# Simbioza mit abgesichertem PHP-FPM installieren

<a id="install-simbioza-with-secured-php-fpm"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Kroatische Version](installation_fpm_secured_hr.md)

Diese vollständige Anleitung beginnt mit einem leeren Anwendungsverzeichnis und geht davon aus, dass noch keine Simbioza FPM/Deploy-Konten vorhanden sind. Sie benötigen keine weitere Installationsanleitung. Das Setup-Tool erstellt dedizierte Konten, Gruppen, einen Pool/Dienst und einen eingeschränkten Helfer: Der Webprozess kann keinen Code schreiben, aber er kann Paketvorgänge vom separaten Bereitstellungs-Worker anfordern. Dies ist der empfohlene Modus. Für eine Neuinstallation muss ein Administrator zunächst vorhandene Identitäten, Instanznamen und Dienste überprüfen; Erstellen Sie niemals eine zweite Site unter derselben Identität.

**SimpleSAMLphp ist optional.** Die lokale Anmeldung Simbioza, OIDC, CAS und OAuth2 ist nicht erforderlich. Führen Sie zunächst die Schritte 1–7 aus. Befolgen Sie die optionale SAML-Komplettlösung nur, wenn diese bestimmte Installation eine SAML-Authentifizierung erfordert.

<a id="1-prepare-the-host"></a>

## 1. Bereiten Sie den Host vor

Beginnen Sie mit einer normalen Administrator-Shell unter Linux oder macOS. Für die HTTPS-Beispiele benötigen Sie eine Domäne, die auf den Server verweist, und ein gültiges TLS-Zertifikat. Führen Sie nur die Befehle für Ihr Betriebssystem aus. Nach einem Fehler nicht fortfahren.

Installieren Sie unter Debian/Ubuntu die Tools und PHP-Erweiterungen:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
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

<a id="4-create-the-dedicated-pool"></a>

## 4. Erstellen Sie den dedizierten Pool

Installieren Sie unter Debian das Paket PHP-FPM für dieselbe Version PHP, die vom Paket CLI und `acl` verwendet wird. Führen Sie im Release-Verzeichnis den einmaligen Systemschritt aus:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

Verwenden Sie unter macOS mit Homebrew PHP den tatsächlichen Anwendungspfad und die FPM-Binärdatei:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm" &&
sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

Wiederholen Sie für eine zweite Site beide Befehle mit passenden Optionen, zum Beispiel `--instance=second --listen=127.0.0.1:9076`. Verwenden Sie dieselben beiden Optionen erneut mit `--finalize` in Schritt 7. Lassen Sie sie nicht versehentlich weg: Die Standardinstanz ohne Suffix ist ein anderer Systemdienst.

<a id="what-step-4-generates-and-starts"></a>

### Was Schritt 4 generiert und startet

**Erstellen oder füllen Sie `php-fpm.conf` nicht manuell aus.** Der obige Befehl `--install` schreibt die vollständige dedizierte FPM-Konfiguration, testet ihre Syntax, registriert den Dienst und startet/neustartet ihn. Der folgende Befehl `--check` ändert die Einstellungen nicht: Er erstellt und entfernt kurzlebige Diagnoseanfragen über den echten Helfer. Es überprüft sowohl den Helper-Roundtrip als auch die CLI-Unterstützung für Hintergrundaktualisierungen. Eine fehlgeschlagene Prüfung gibt einen Exit-Status ungleich Null zurück. Erwarten Sie `[OK] Real Setup helper round trip` und `[OK] Background updater CLI runtime`; Fahren Sie nicht mit Apache oder dem Assistenten fort, wenn einer fehlt oder fehlschlägt.

Die explizite `sudo -H -u fpm-simbioza`-Prüfung verwendet das neu erstellte Laufzeitkonto sofort, ohne dass Ihre aktuelle SSH-Sitzung über eine aktualisierte Gruppenmitgliedschaft verfügt. Verwenden Sie für eine benannte Instanz auch das Laufzeitkonto dieser Instanz, zum Beispiel `fpm-simbioza-second`. Der Helfer bleibt Root-Eigentum und ist auf genehmigte Aktionen beschränkt. Erteilen Sie dem Webprozess keine Code-Schreibberechtigungen und deaktivieren Sie die Dateisystem-Sandbox nicht.

Unter Linux verwendet die Standardinstanz `/etc/php/<major.minor>/php-fpm-simbioza/php-fpm.conf`, wobei die Version mit der in Schritt 4 verwendeten Version CLI PHP übereinstimmt. Bei PHP 8.4 lautet die genaue Datei `/etc/php/8.4/php-fpm-simbioza/php-fpm.conf`. Es enthält sowohl `[global]` als auch den dedizierten `[simbioza]`-Pool: seine Konten, lokalen Listener, Anwendungs- und `HPH_CONFIG_PATH`-Pfade, private Sitzungs-/temporäre Verzeichnisse und PHP-Grenzwerte. Dies ist **nicht** der freigegebene `/etc/php/8.4/fpm/php-fpm.conf` oder `pool.d/www.conf`. Der Generator schreibt außerdem `php-fpm-simbioza.service`, einschließlich seiner Dateisystemisolation und Berechtigungsmaske. Diese Systemd-Einstellungen sind keine FPM-INI-Einträge. So überprüfen Sie, welche Konfiguration der dedizierte Dienst verwendet, ohne sie zu ändern:

```bash
sudo systemctl show php-fpm-simbioza.service -p ExecStart &&
sudo systemctl is-active php-fpm-simbioza.service
```

Bei einer benannten Instanz erhalten sowohl das Konfigurationsverzeichnis als auch der Dienstname das entsprechende Suffix, zum Beispiel `php-fpm-simbioza-second`. Unter macOS erstellt das Tool stattdessen `$(brew --prefix)/etc/simbioza/php-fpm.conf`, das `pool.conf` im selben Verzeichnis enthält, und registriert den dedizierten launchd-Dienst. Ersetzen Sie eine korrekt generierte Datei nicht durch die Konfiguration einer anderen Site. Die Konfiguration SimpleSAMLphp wird nur hinzugefügt, wenn Sie die optionalen SAML-Schritte befolgen. Schritt 5 konfiguriert Apache/Nginx weiterhin separat; Durch die Generierung von FPM wird der Webserver nicht konfiguriert. Nach einem erfolgreichen, unveränderten Schritt 4 ist kein zusätzlicher manueller FPM-Neustart erforderlich.

Der 0.3.4-Generator setzt launchd `Umask` auf die Ganzzahl **7** (oktal `0007`), passend zu Linuxs `UMask=0007`. Sowohl der Webprozess als auch der eingeschränkte Deployment-Worker verwenden diese Maske; der Mitarbeiter legt es **nach** dem Kontowechsel fest. Ihr `TMPDIR` ist der `data/tmp` dieser Anwendung, sodass gemeinsam genutzte temporäre Sperren im selben privaten Laufzeitgruppenverzeichnis verbleiben. Neue gewöhnliche Protokolldateien sind `0660`, keine FPM-eigenen `0644`-Dateien, an die der Updater keine Anhänge anhängen kann.

Überprüfen Sie unter macOS den generierten Dienst, bevor Sie den Assistenten öffnen:

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

Verwenden Sie für eine benannte Instanz die entsprechende Dienstdatei, zum Beispiel `hr.simbioza-second.php-fpm.plist`. Eine fehlende Maske ist eine fehlgeschlagene Voraussetzung und kein Grund, Code beschreibbar zu machen oder den Updater als Root auszuführen. Sitzungsdateien bleiben privat (`0600`); Machen Sie nicht alle Daten rekursiv beschreibbar.

Die Prüfung ändert keine Anwendungseinstellungen. Der Standardpool überwacht `127.0.0.1:9075`; Halten Sie diesen Port lokal auf dem Server. Das Tool gewährt dem FPM-Konto Schreibzugriff auf Laufzeitdaten, nicht auf das gesamte Release, und installiert einen eng begrenzten Helfer für Paketänderungen. Außerdem werden die Bereitstellungs- und Laufzeitgruppen erstellt. Überprüfen Sie jedes Diagnoseergebnis, bevor Sie fortfahren. Verwenden Sie nicht `chmod 777`.

<a id="accounts-groups-and-permissions-created-by-the-tool"></a>

### Vom Tool erstellte Konten, Gruppen und Berechtigungen

Der obige Befehl `--install` umfasst die Erstellung eines Kontos/einer Gruppe; Führen Sie `useradd` oder `groupadd` nicht separat aus. Fehlende Identitäten werden erstellt, während bestehende Identitäten mit diesen Namen wiederverwendet werden, ohne ihre UID/GID zu ersetzen. Das Tool stellt die benötigten Hauptgruppen und Zusatzmitgliedschaften sicher. Für eine benannte Instanz erhält jeder unten aufgeführte Name das gleiche Suffix `-instance`.

Führen Sie bei einer Neuinstallation im **gleichen Anwendungspfad** weiterhin `--install` aus Schritt 4 aus: Dadurch wird auch der Root-eigene Helfer außerhalb des Anwendungsverzeichnisses aktualisiert. Durch das Löschen von Anwendungsdateien wird dieser Helfer nicht entfernt. Vorhandene Konten/Gruppen werden wiederverwendet; es gibt keine manuelle Kontowiederherstellung. Eine vorhandene Apache-Konfiguration kann beibehalten werden, wenn sie mit Schritt 5 übereinstimmt, einschließlich des echten öffentlichen Pfads, des richtigen Handlers und der `AllowOverride`-Klassen.

| Identität | Zweck |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Gesperrtes Webdienstkonto und primäre Gruppe; Ich kann keinen Anwendungscode schreiben. |
| `simbioza-deploy` / `deploy-simbioza` | Gesperrtes Bereitstellungskonto und Code-Wartungsgruppe; Ihr Betreuerkonto tritt ebenfalls dieser Gruppe bei. |
| `run-simbioza` | Gemeinsame private Laufzeitgruppe für FPM, Bereitstellung und den Betreuer. |

Überprüfen Sie unter Linux das Ergebnis nach der erneuten Verbindung mit SSH:

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

Code verwendet Bereitstellungseigentum und eine schreibgeschützte FPM-Sandbox; Private Laufzeitverzeichnisse verwenden die Gruppe `run-simbioza` (`2770`) und das Konfigurationsstammverzeichnis verwendet ein Sticky-Verzeichnis (`3770`) für kontrollierte atomare Updates. Bei der normalen Laufzeitfreigabe werden diese Gruppenberechtigungen verwendet, keine umfassenden ACL-Schreibberechtigungen. Mit der optionalen privaten SimpleSAMLphp-Konfiguration legt das Tool zusätzlich den Linux-Zugriff und Standard-ACLs fest, sodass `app-simbioza` **Nur Lesen/Traversieren** für diese Konfiguration ergibt. Die folgende exemplarische Vorgehensweise für SAML umfasst die Einrichtung und die ACL-Überprüfung. Wenden Sie das Rezept „normales/gemeinsames FPM `www-data` write-ACL“ nicht auf gesichertes FPM an.

Mit dem standardmäßigen Anwendungskonfigurationsverzeichnis verfügen Auth, E-Mail, HTML-Editor, Arbeitsbereich, Menü, Design und Backup über zur Laufzeit beschreibbare Modulverzeichnisse. Backup benötigt dies für die Veröffentlichung von atomaren Provider-Deskriptoren. Die freigabeverwaltete Dienstkonfiguration PHP bleibt im Besitz der Bereitstellung und für FPM schreibgeschützt. Das Tool erstellt keine `data/config` neu und gewährt keine Laufzeitrechte für entfernte ältere `editor-html.php`-, `email.php`- oder `workspace.php`-Dateien.

<a id="5-configure-the-web-server"></a>

## 5. Konfigurieren Sie den Webserver

**Wählen Sie die Route, bevor Sie Befehle ausführen:**

- **Vorhandener virtueller Host HTTPS und ein URL-Präfix wie `/simbioza`:** gehen direkt zu [den URL-Präfix-Anweisungen](#optional-install-under-a-url-prefix-such-as-simbioza). Bearbeiten Sie den vorhandenen Vhost. Erstellen Sie nicht `simbioza.conf` und führen Sie `a2ensite simbioza` nicht aus. Dieser Befehl aktiviert nur einen neu erstellten, separaten Vhost.
- **Ein neuer dedizierter Hostname:** Verwenden Sie die unten stehende Apache- oder Nginx-Konfiguration.

Wählen Sie **eine** dieser Konfigurationen. Sie bedienen einen dedizierten Hostnamen unter `/`; Ersetzen Sie den Hostnamen, den Release-Pfad und die Zertifikatseinstellungen TLS. Konfigurieren Sie für eine Unterverzeichnisinstallation auch das URL-Präfix im Webserver und übergeben Sie die vollständige URL in Schritt 6 an `install:prepare`.

<a id="apache-with-php-fpm"></a>

### Apache mit PHP-FPM

Aktivieren Sie Apache `rewrite`, `proxy`, `proxy_fcgi` und TLS. Leiten Sie PHP-Anfragen für diese Site an den dedizierten Pool weiter; Leiten Sie sie nicht über `mod_php` weiter.

Aktivieren Sie für Apache unter Debian/Ubuntu die FPM-Proxy-Module und erstellen Sie die Site:

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

Bearbeiten Sie unter macOS `/opt/homebrew/etc/httpd/httpd.conf` mit `sudoedit`. Aktivieren Sie `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module` und `socache_shmcb_module`. Fügen Sie `Listen 443` hinzu, wenn dort keine vorhandene Direktive lauscht. Fügen Sie für diese Site keinen mod_php-Handler hinzu.

Legen Sie für einen neuen Homebrew-Apache explizit die Shared-Worker-Identität in `httpd.conf` fest (führen Sie Web-Worker nicht als Ihren Code besitzenden Login aus):

```apache
User _www
Group _www
```

Ersetzen Sie in der folgenden Konfiguration die Domäne, die Zertifikatspfade TLS und **beide** `/srv/simbioza/public`-Pfade durch Ihren tatsächlichen Anwendungspfad (unter macOS normalerweise `/Users/Shared/Simbioza/simbioza/public`). Apache erweitert `$SIMBIOZA_APP_ROOT` nicht von Ihrem Terminal aus.

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

Das ausgelieferte `public/.htaccess` verwendet `DirectoryIndex index.php`, was die Überschreibungsklasse `Indexes` erfordert; Umschreiberegeln benötigen `FileInfo` und `Options
-Indexes` benötigen `Options`. Behalten Sie alle drei Klassen in `AllowOverride`. Dadurch werden Verzeichnislisten **nicht** aktiviert: `Options -Indexes` deaktiviert sie weiterhin. Eine fehlende Klasse kann HTTP 500 (`DirectoryIndex not allowed here`) erzeugen, selbst wenn `apache2ctl configtest` `Syntax OK` meldet, da `.htaccess` gelesen wird, wenn eine Anforderung dieses Verzeichnis erreicht. Überprüfen Sie nach dem erneuten Laden die tatsächliche öffentliche URL, bevor Sie mit dem Assistenten fortfahren. Siehe die [Apache DirectoryIndex-Referenz](https://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryindex). Machen Sie das übergeordnete Release-Verzeichnis nicht als Dokumentstamm verfügbar.

Testen und starten Sie Apache nur für **das oben erstellte neue separate `simbioza.conf`**. Überspringen Sie diesen Block, wenn Sie einen vorhandenen gemeinsam genutzten Vhost verwenden. Auf Debian/Ubuntu:

```bash
test -f /etc/apache2/sites-available/simbioza.conf &&
sudo a2ensite simbioza &&
sudo apache2ctl configtest &&
sudo systemctl enable --now apache2 &&
sudo systemctl reload apache2
```

Auf macOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Erwarten Sie `Syntax OK`; Nach einer fehlgeschlagenen Prüfung nicht starten/neu laden. Verwenden Sie für einen bereits laufenden Apache einen validierten Graceful Reload, anstatt einen zweiten Dienst zu starten. Fahren Sie mit dem Installationsprogramm fort; die folgende Nginx-Alternative ist kein zusätzlicher Schritt.

<a id="nginx-with-php-fpm"></a>

### Nginx mit PHP-FPM

Nginx kann `mod_php` nicht ausführen; es sendet PHP-Anfragen an denselben dedizierten FPM-Pool. Halten Sie die Regeln `SCRIPT_FILENAME` und `try_files` zusammen:

Wählen Sie diese Alternative **anstelle von Apache**: Verwenden Sie `nginx` anstelle von `apache2` in der Paketliste von Schritt 1 (`brew install nginx` unter macOS) und führen Sie die Apache-Konfigurations-/Startbefehle nicht aus. Bearbeiten Sie `/etc/nginx/sites-available/simbioza` unter Debian/Ubuntu oder `/opt/homebrew/etc/nginx/nginx.conf` unter macOS. Ersetzen Sie die Domäne, die Zertifikatspfade und das Anwendungsstammverzeichnis im vollständigen Serverblock unten.

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

Aktivieren Sie unter Debian/Ubuntu den neuen Serverblock, testen Sie ihn und starten Sie:

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

Unter macOS: `sudo /opt/homebrew/bin/nginx -t`, dann `sudo /opt/homebrew/bin/brew services start nginx`. Wenn der Link oder Dienst bereits vorhanden ist, überprüfen Sie ihn, anstatt ihn zu duplizieren.

Testen Sie die Nginx-Konfiguration vor dem Neuladen. Der FPM-Listener darf nicht über ein nicht vertrauenswürdiges Netzwerk erreichbar sein. Die Paketvorbereitung über das grafische Installationsprogramm kann mehrere Minuten dauern. Die 900-sekündige Wartezeit des Apache/Nginx-Gateways verhindert einen standardmäßigen 504-Fehler von 60 Sekunden, während der eingeschränkte Worker noch ausgeführt wird. Passen Sie alle zusätzlichen Upstream-Reverse-Proxy-Timeouts entsprechend an, deaktivieren Sie jedoch nicht die FPM- oder Webserver-Anforderungsbeschränkungen für nicht verwandte Sites. Der von `configure_fpm_setup.php` generierte dedizierte Pool legt auch `max_execution_time` von PHP auf 900 Sekunden fest. Das ist wichtig: Beim getesteten Build macOS/PHP 8.5 beendete der Wert `0` immer noch eine lange Anfrage nach einer Minute, obwohl dies bedeuten sollte, dass es keine Begrenzung gibt. Überprüfen Sie beide Timeout-Einstellungen im generierten Pool und testen Sie eine längere Paketvorbereitung.

<a id="optional-install-under-a-url-prefix-such-as-simbioza"></a>

### Optional: Installieren Sie unter einem URL-Präfix wie /simbioza

Platzieren Sie diesen Block für einen gemeinsam genutzten Hostnamen **in seinem vorhandenen virtuellen Host HTTPS**, anstatt einen anderen virtuellen Host mit demselben Namen zu erstellen. Ersetzen Sie den Release-Pfad, das URL-Präfix und (für FPM) den Pool-Port:

Die drei Wege haben unterschiedliche Zwecke:

| Einstellung | Beispiel |
| --- | --- |
| Anwendungsstammverzeichnis, das in den Schritten 2 und 4 verwendet wird | `/srv/simbioza` |
| Öffentliches Verzeichnis, das sowohl von `Alias` als auch von `<Directory>` verwendet wird | `/srv/simbioza/public` |
| Öffentliches URL-Präfix | `/simbioza` |

Das Anwendungsstammverzeichnis muss sich nicht innerhalb des freigegebenen Hostnamens `DocumentRoot` befinden. Bewahren Sie es außerhalb dieses öffentlichen Baums auf. Für dieses Setup ist kein symbolischer Link zum Anwendungsverzeichnis erforderlich. Der obige Nginx-Site-aktivierende Symlink hat nichts damit zu tun: Er ermöglicht die Serverkonfiguration, nicht Anwendungsdateien.

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

Legen Sie außerdem `ProxyTimeout 900` in diesem vorhandenen Vhost fest. Wenn eine alte Konfiguration `<Directory "/var/www/html/simbioza">` für einen ehemaligen öffentlichen Verzeichnis-Symlink enthält, **ersetzen Sie diesen Block** durch den echten öffentlichen Verzeichnisblock oben; es reicht nicht aus, nur `Alias` hinzuzufügen. Der PHP-Handler muss für das tatsächliche Dateisystemziel des Alias ​​gelten. Machen Sie das übergeordnete Verzeichnis der Anwendung, `config/`, `data/` oder `vendor/`, nicht verfügbar, damit ein veralteter Pfad funktioniert.

Für einen Anwendungsstamm `/data/www/simbioza` werden beide Pfade im Block zu `/data/www/simbioza/public`; die öffentliche URL bleibt `/simbioza`. Ändern Sie `SIMBIOZA_APP_ROOT` nicht und generieren Sie den Pool nicht neu, nur um die URL-Zuordnung zu ändern. Wenn sich der Anwendungsstamm selbst ändert, müssen auch seine FPM-/Hilfspfade explizit neu konfiguriert werden. Ein Apache-Alias ​​führt diese Verschiebung nicht durch.

Nehmen Sie mit Puppet/Ansible die entsprechende Änderung in der autorisierenden verwalteten Vhost-Definition vor. Bearbeiten Sie den generierten Vhost nicht als dauerhafte Lösung, erstellen Sie kein Duplikat des Vhosts HTTPS und entfernen Sie die Aliase anderer Anwendungen nicht. Wenn der optionale SAML bereits einen längeren Alias ​​wie `/simbioza/simplesaml` hat, behalten Sie diesen Alias ​​**vor** dem allgemeinen Alias ​​`/simbioza` bei. SAML bleibt optional.

Nachdem Sie **nur die Alias-/Verzeichniskonfiguration von Apache** geändert haben, validieren Sie sie und laden Sie Apache ordnungsgemäß neu. Schritt 4 hat den dedizierten FPM-Dienst bereits gestartet:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Verwenden Sie diesen Linux-Block anstelle der New-Site-Befehle `a2ensite simbioza`, wenn der freigegebene vhost HTTPS bereits vorhanden ist. Die Validierungs-/Neuladebefehle Homebrew sind im Apache-Abschnitt oben aufgeführt. Ein laufender FPM-Dienst und eine gültige Apache-Syntax beweisen nicht, dass die Anfrage den richtigen Anwendungspfad verwendet: Testen Sie auch die öffentliche URL `/simbioza/`. Stoppen Sie bei HTTP 500 oder einem fehlenden `bootstrap.php`; überprüfen Sie den Alias, den Verzeichnishandler und die FPM-Pfade, anstatt mit dem Assistenten fortzufahren oder Berechtigungen umfassend zu ändern.

<a id="reinstallation-only-an-obsolete-custom-fpm-bind-mount"></a>

#### Nur Neuinstallation: ein veralteter benutzerdefinierter FPM-Bind-Mount

Bei einer Neuinstallation wird kein Drop-In für einen alten symbolischen Link zum öffentlichen Verzeichnis erstellt. Wenn eine bestehende Installation über eine solche verfügte, überprüfen Sie sie vor dem Neustart:

```bash
sudo systemctl cat php-fpm-simbioza.service
sudo systemctl show php-fpm-simbioza.service -p DropInPaths
```

Verwenden Sie den angehängten Dienstnamen für eine benannte Instanz. Entfernen Sie nach dem Wechsel zum direkten Alias ​​**nur ein bestätigtes veraltetes benutzerdefiniertes Drop-In**, dessen einziger Zweck darin bestand, den entfernten Symlink-Pfad zu binden. Löschen Sie niemals das gesamte Verzeichnis `.service.d` oder entfernen Sie die tatsächlichen Bind-Mounts der generierten Anwendung. Eine veraltete Bindung an einen nicht vorhandenen Pfad kann den nächsten FPM-Start verhindern. Verwenden Sie nach dieser spezifischen Entfernung diese vollständige Linux-Sequenz anstelle des oben beschriebenen Neuladens nur für Apache. Dies gilt auch nach einer bewussten Bearbeitung der dedizierten FPM-Konfiguration; `daemon-reload` wird benötigt, wenn sich eine Systemd-Einheit oder ein Drop-In geändert hat, nicht nur, weil `php-fpm.conf` geändert wurde:

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

Verwenden Sie für Ihre Installation die passende FPM-Binärdatei, den entsprechenden Konfigurationspfad und das entsprechende Dienstsuffix. Erwarten Sie einen erfolgreichen FPM-Konfigurationstest, `Syntax OK` und `active`. Die `&&`-Kette stoppt nach einem fehlgeschlagenen Befehl; Fahren Sie nach einem Fehler nicht mit dem Assistenten fort. Ein FPM-Neustart unterbricht seine aktuellen Anforderungen. Führen Sie ihn daher nicht während der Installations-/Aktualisierungsarbeiten aus. Hierbei handelt es sich um eine einmalige Host-Bereinigung oder die Anwendung einer absichtlichen Konfigurationsänderung, **kein erforderlicher zusätzlicher Schritt bei einer Neuinstallation**. Eine unveränderte FPM-Konfiguration erfordert allein für eine Apache-Alias-Änderung keinen Neustart.

Verwenden Sie `--base-url=https://your-host.example/simbioza` für das Installationsprogramm, nicht den Hostnamen ohne Präfix. Ersetzen Sie für eine Installation unter `/wiki` **beide** Vorkommen des Präfixes und verwenden Sie `--base-url=https://your-host.example/wiki`. Der Verzeichnispfad ist ein Dateisystempfad; das Präfix ist ein URL-Pfad, nicht der Name des Anwendungsverzeichnisses. Halten Sie `config/` und `data/` außerhalb des offengelegten Alias ​​privat. Validieren Sie Apache vor dem ordnungsgemäßen Neuladen.

<a id="6-run-the-graphical-installer"></a>

## 6. Führen Sie das grafische Installationsprogramm aus

Generieren Sie eine einmalige Installations-URL aus dem Release-Verzeichnis:

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Wenn Sie `--instance=second` gewählt haben, verwenden Sie stattdessen `-u fpm-simbioza-second`. Dadurch wird PHP als FPM-Identität und nicht als Root ausgeführt. Verwenden Sie für eine Site unter `/simbioza` stattdessen `--base-url=https://simbioza.example.org/simbioza`. Öffnen Sie die URL in einem privaten Browserfenster. Geben Sie das Token nicht weiter und machen Sie auch keinen Screenshot davon. Der Assistent prüft die Anforderungen und den Datenbankzugriff, fragt nach der Site-Identität, den Sprachen, der Zeitzone, dem ersten Administrator und optionalen Modulen und zeigt dann vor der Installation eine Überprüfung an. Alle optionalen Module sind vorausgewählt; Deaktivieren Sie diejenigen, die Sie nicht möchten. Wenn der dedizierte Helfer funktioniert, werden ausgewählte optionale Pakete über die GUI installiert.

Wenn Sie den dedizierten Helfer absichtlich weglassen, bereiten Sie die ausgewählten optionalen Pakete als Bereitstellungsbenutzer mit `php scripts/installation_packages.php prepare` für alle Module vor oder fügen Sie `--modules=calendar` für eine kleinere Auswahl hinzu. Passen Sie die Liste an Ihre Auswahl an und laden Sie das Installationsprogramm neu. Ohne den Helfer sind spätere GUI-Paketänderungen nicht verfügbar. Bei der Sprachinstallation/-aktualisierung/-entfernung werden nur Laufzeitdaten und -konfigurationen geschrieben und dieser Helfer ist nicht erforderlich. Stellen Sie sicher, dass diese Verzeichnisse von PHP beschreibbar sind. siehe [Lokalisierung](localization_en.md).

Melden Sie sich nach der Installation als Administrator an. Überprüfen Sie unter **Einstellungen → Setup und Module**, ob die dedizierten Pool-, Hilfs- und Laufzeitberechtigungen alle als bereit gemeldet sind. Die Hintergrundaktualisierungsprüfung CLI muss ebenfalls bestanden werden und GUI-Paketvorgänge und Anwendungsaktualisierungen müssen verfügbar sein. Ein graues Ergebnis **Nicht geprüft** ist nicht bestanden: Lösen Sie zuerst die zuvor fehlgeschlagene Hilfsprüfung auf; Dies bedeutet nicht, dass die Erweiterungen CLI fehlen. Der Assistent importiert die ausgewählten Sprachen des Benutzerhandbuchs.

<a id="7-finalize-permissions-and-verify"></a>

## 7. Finalisieren Sie die Berechtigungen und überprüfen Sie sie

Melden Sie sich nach dem Assistenten ab und wieder bei SSH/dem Terminal an, um die neuen Bereitstellungs-/Laufzeitgruppenmitgliedschaften zu aktivieren. Stellen Sie `SIMBIOZA_APP_ROOT` im in Schritt 2 ausgewählten Pfad wieder her und führen Sie dann die entsprechenden Befehle aus:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" &&
vendor/bin/hph modules migrate-status &&
composer check-platform-reqs
```

Fügen Sie unter macOS dasselbe `--php-fpm`-Argument und denselben Anwendungspfad hinzu, die in Schritt verwendet wurden
4. Melden Sie sich ab und wieder an, damit der Betreuer die neuen Gruppenmitgliedschaften erhält.
Erwarten Sie keine ausstehenden Migrationen, erfolgreiche Anmeldung und Startseiten sowie eine gesperrte `/install`-Route. Sichern Sie die Datenbank, `config/`, Benutzerdateien und Designdaten.

Das einmalige `sudo`-Setup bedeutet **nicht**, dass für normale Updates Root erforderlich ist. Die Wartungsbefehle finden Sie am Ende dieser Anleitung.

<a id="optional-simplesamlphp-integration"></a>

## Optionale SimpleSAMLphp-Integration

**Überspringen Sie diesen gesamten Abschnitt, es sei denn, Sie benötigen SAML.** Es hat nichts mit der Konfiguration von OIDC, CAS, OAuth2 oder der lokalen Anmeldung zu tun. Halten Sie den lokalen Administrator und die Break-Glass-Anmeldung verfügbar, bis SAML funktioniert.

<a id="s1-select-the-library-and-public-addresses"></a>

### S1. Wählen Sie die Bibliothek und die öffentlichen Adressen aus

Beenden Sie zunächst die normale Installation. Legen Sie in Ihrer Betreuer-Shell die tatsächlichen Pfade und URLs fest. Beispiel für einen dedizierten Hostnamen:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

Verwenden Sie für eine Site unter `https://your-host.example/simbioza` stattdessen `SIMBIOZA_SAML_URL=https://your-host.example/simbioza/simplesaml/`. Behalten Sie den letzten Schrägstrich bei. Das Stammverzeichnis der Bibliothek ist **nicht** das Verzeichnis von Simbioza. Eine vorhandene, vom System verwaltete Bibliothek kann wiederverwendet werden, zum Beispiel `/usr/share/simplesamlphp-aai`; Ändern Sie nicht die gemeinsame Konfiguration, die Zertifikate oder den SP einer anderen Anwendung.

Wenn bereits eine kompatible Bibliothek installiert ist, überspringen Sie den Download-Block. Andernfalls installiert dieses Beispiel den Upstream **2.5.3.1 full** mit seiner veröffentlichten Prüfsumme. Es erfordert PHP **8.3 oder neuer** und die Erweiterungen aus den [SimpleSAMLphp Anforderungen](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html). Überprüfen Sie [Upstream-Versionen](https://simplesamlphp.org/download/) auf Sicherheitsupdates. Wenn Sie eine andere Version wählen, ändern Sie sowohl die URL als auch die Prüfsumme zusammen. Führen Sie Folgendes nur für ein **neues**, nicht vorhandenes Bibliotheksverzeichnis aus:

Unter Debian/Ubuntu installieren Sie zunächst die Download-Tools mit `sudo apt-get install curl openssl`. macOS stellt bereits `curl` und `openssl` bereit.

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

Stoppen Sie, wenn ein Befehl fehlschlägt. Überprüfen Sie die ausgewählte Bibliothek:

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

<a id="s2-prepare-private-configuration-and-trusted-metadata"></a>

### S2. Bereiten Sie private Konfigurationen und vertrauenswürdige Metadaten vor

Erstellen Sie ausgehend von `$SIMBIOZA_APP_ROOT` private Verzeichnisse und kopieren Sie die **Vorlagen**. Dieser Block überschreibt keine vorhandene Konfiguration. Wenn Sie gesicherte Einstellungen für **denselben SP** wiederherstellen, verwenden Sie stattdessen dessen `config`, Zertifikate und erforderliche Metadaten. Kopieren Sie keine Zugangsdaten einer anderen Installation und stellen Sie weder Sitzungen und Cache noch die frühere Anwendungsdatenbank wieder her.

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

Bearbeiten Sie `config.php` mit `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`. Legen Sie diese Schlüssel innerhalb des vorhandenen Arrays `$config` fest. Ersetzen Sie Beispieldateisystempfade durch Ihren Anwendungspfad; PHP-Dateien erweitern keine Terminalvariablen.

| Schlüssel | Wert oder Aktion |
| --- | --- |
| `baseurlpath` | Der vollständige HTTPS `SIMBIOZA_SAML_URL`-Wert, einschließlich des letzten Schrägstrichs. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` und `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | Die entsprechenden privaten Verzeichnisse `data`, `cache`, `logs`, `tmp` unter `/srv/simbioza/data/saml`. |
| `metadata.sources` | Eine vertrauenswürdige Quelle, z.B. `[['type' => 'flatfile']]` mit privatem `metadatadir`; Nutzen Sie ggf. den verifizierten Mechanismus Ihres Verbandes. |
| `secretsalt`, `assets['salt']` | Zwei verschiedene Zufallswerte, jeweils generiert mit `openssl rand -hex 32`; Bearbeiten Sie `salt` innerhalb des vorhandenen `assets`-Arrays, nicht einen neuen `assets.salt`-Schlüssel der obersten Ebene. Verwenden Sie niemals Vorlagenwerte und veröffentlichen Sie diese niemals. |
| `auth.adminpassword` | Ein interaktiv mit `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"` generierter Hash; getrennt von Ihrem Simbioza-Passwort. |
| `store.type` | `phpsession` für diese exemplarische Vorgehensweise. |
| `session.phpsession.cookiename` | Ein standortspezifischer Name, z. B. `SimbiozaSAML`, anders als die Sitzungscookies von Simbioza und anderen Websites. |
| `session.phpsession.savepath` | `null`, sodass beide Endpunkte den privaten `data/sessions` dieses Pools verwenden. |
| `session.cookie.path` | `/` für einen dedizierten Hostnamen oder `/simbioza/` für dieses Präfix; muss sowohl die Anwendung als auch den Endpunkt SAML abdecken. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true`; Verwenden Sie HTTPS. |
| `showerrors` | `false` außerhalb der kontrollierten Fehlerbehebung. |

Konfigurieren Sie in `authsources.php` `default-sp` als `saml:SP` mit Ihrem eigenen stabilen `entityID` und der Entitäts-ID `idp` des vertrauenswürdigen IdP. Entfernen Sie nicht verwendete Beispielquellen. Erhalten Sie IdP-Metadaten von Ihrem Verbund-/IdP-Administrator. Speichern Sie vertrauenswürdige PHP-Metadaten als `data/saml/metadata/saml20-idp-remote.php` oder konfigurieren Sie den unterstützten Metadatenmechanismus des Verbunds. Deaktivieren Sie keine Signaturprüfungen und vertrauen Sie nicht einem nicht verifizierten Signaturzertifikat.

Wenn der IdP ein SP-Zertifikat erfordert, generieren Sie einen privaten Schlüssel/ein privates Zertifikat für einen **neuen** SP im `data/saml/cert` dieser Site und referenzieren Sie die Dateinamen über `privatekey` und `certificate` in `default-sp` gemäß der Verbundrichtlinie. Behalten Sie für den **gleichen registrierten SP** dessen EntityID, vorhandene Schlüssel/Zertifikate und relevante Metadaten bei, anstatt Ersatz zu generieren.

<a id="s3-configure-the-dedicated-pool-and-verify-acls"></a>

### S3. Konfigurieren Sie den dedizierten Pool und überprüfen Sie die ACLs

Führen Sie ab `$SIMBIOZA_APP_ROOT` den Generator für **dieselbe Anwendung und Instanz** erneut aus und fügen Sie das private Konfigurationsargument hinzu. Dadurch wird nur dieser dedizierte Pool aktualisiert und neu gestartet, wobei seine vorhandenen Konten/Gruppen wiederverwendet werden:

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

Fügen Sie unter macOS `--php-fpm="$(brew --prefix)/sbin/php-fpm"` zu **allen drei** Befehlen hinzu. Wiederholen Sie für eine benannte Instanz auch die ursprünglichen Optionen `--instance` und `--listen`. Der Generator exportiert `SIMPLESAMLPHP_CONFIG_DIR` trotz `clear_env = yes` in den FPM-Worker; ein SSH `export` oder Apache `SetEnv` allein reicht nicht aus.

Überprüfen Sie unter Linux den Lesezugriff und die privaten Konfigurations-ACLs:

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

Erwarten Sie `group:app-simbioza:r-x` und einen passenden Standardwert ACL im Verzeichnis. Dateien sind für FPM lesbar, nicht beschreibbar. Die Bereitstellung bleibt Eigentum. Verwenden Sie Suffix-Identitäten für eine benannte Instanz. Unter macOS verwendet der Generator Gruppenbesitz anstelle von Linux POSIX ACL-Befehlen.

<a id="s4-send-the-saml-endpoint-to-the-same-fpm-pool"></a>

### S4. Senden Sie den Endpunkt SAML an denselben FPM-Pool

Fügen Sie diesen Block dem **vorhandenen HTTPS vhost** hinzu, nicht einem zweiten vhost oder einem gemeinsam genutzten globalen SimpleSAMLphp-Alias. Ersetzen Sie das URL-Präfix und die Dateisystempfade durch die Werte von S1. Apache erweitert keine Shell-Variablen. Ein längerer Alias ​​SAML muss **vor** einem allgemeinen Anwendungsalias `/simbioza` stehen.

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

Verwenden Sie für `/simbioza` `Alias /simbioza/simplesaml` und den regulären Ausdruck `^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. Ersetzen Sie für ein Systempaket **beide** `/opt/simplesamlphp/public`-Pfade durch das tatsächliche Verzeichnis `public/`. Verwenden Sie den tatsächlichen Port Ihres Pools. Der explizite Proxy behält `module.php/...` PATH_INFO bei und verwendet die FPM-Identität, die Umgebung und das Sitzungsverzeichnis der Anwendung. Sein gewöhnlicher Handler PHP deckt diesen separaten Alias ​​nicht ab.

Validieren Sie unter Debian/Ubuntu und laden Sie dann Apache ordnungsgemäß neu:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

Verwenden Sie auf Homebrew Apache `sudo /opt/homebrew/bin/httpd -t` und, nur bei Erfolg, `sudo /opt/homebrew/bin/httpd -k graceful`. Ändern Sie mit Puppet/Ansible den autorisierenden verwalteten Vhost und nicht eine generierte Datei. Verwenden Sie für Nginx die [offizielle Alias-/PATH_INFO-Konfiguration](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx) mit demselben dedizierten Pool und demselben URL-Präfix, nicht mit einem gemeinsam genutzten Standardpool. Geben Sie niemals `config/`, `cert/` oder private Metadaten über den Web-Alias ​​bekannt.

<a id="s5-register-the-sp-map-attributes-and-test-authentication"></a>

### S5. Registrieren Sie den SP, ordnen Sie Attribute zu und testen Sie die Authentifizierung

Öffnen Sie die Basis-URL SAML und rufen Sie die Metadaten `default-sp` über die Administratorschnittstelle SimpleSAMLphp ab. Senden Sie es an den IdP-/Verbundadministrator. Überprüfen Sie, ob die registrierte **EntityID, ACS und SLO** das Präfix HTTPS SAML dieser Site verwenden, und warten Sie, bis die Registrierung aktiv ist.

Aktivieren Sie unter Simbioza **Einstellungen → Authentifizierung** das Protokoll SimpleSAMLphp. Erstellen Sie ein SAML-Profil mit dem absoluten `vendor/autoload.php`-Pfad und `auth_source = default-sp` der Bibliothek. Verwenden Sie **Testprofil**, um tatsächliche IdP-Attribute zu erfassen; Wählen Sie ein stabiles Betreff-/Anmeldeattribut und ordnen Sie E-Mail-/Namensfelder zu. Erraten Sie keine Attributnamen von einem anderen Anbieter. Testen Sie erneut, bis das Profil betriebsbereit ist, aktivieren Sie es und prüfen Sie, ob die vorgesehenen Benutzer es verwenden dürfen. Die automatische Kontoerstellung ist eine explizite Profilauswahl.

Die GUI schreibt die Einstellungen von Simbioza in `HPH_CONFIG_PATH/auth/auth.json`; es werden keine externen SimpleSAMLphp-Dateien bearbeitet. Beginnen Sie bei der manuellen Authentifizierungskonfiguration mit dem `auth.json.dist` des Authentifizierungsmoduls und ersetzen Sie niemals eine Live-Datei.

Testen Sie in einem privaten Browser: SAML anmelden → zurück zu Simbioza → geschützte Seite → abmelden → erneut anmelden. Das Laden von Metadaten allein beweist noch keine Authentifizierung. Mit `phpsession` kann ein anderes Pool-/Sitzungsverzeichnis Anmeldeschleifen verursachen: Korrigieren Sie Routing/Umgebung, anstatt umfassendere Berechtigungen zu gewähren. Behalten Sie den lokalen Administratorzugriff bei, bis der gesamte Browserablauf erfolgreich ist.

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

<a id="external-configuration-layers"></a>

## Externe Konfigurationsebenen

Bereiten Sie die vollständige Konfiguration vor, bevor Sie sie im privaten `framework.php` auswählen. Der generierte Pool und der feste Helfer fixieren dieselben Basis- und Überschreibungsparameter. Sie sind nicht auf geerbte Variablen angewiesen, die `clear_env` oder `sudo` überleben. Linux-Sandbox-Regeln binden niedrigere Schichten schreibgeschützt und machen die letzte Schicht sowie das konfigurierte Setup-Anforderungsverzeichnis verfügbar. Dadurch sind Schreibvorgänge nur dann zulässig, wenn die Unix-Rechte dies bereits zulassen. Das Tool wendet chown/chmod nicht auf externe Ebenen an.

Gewähren Sie dem FPM und den Bereitstellungskonten Lese-/Suchzugriff. Halten Sie den Dienstcode PHP im Besitz der Bereitstellung und schreibgeschützt für FPM; Erteilt laufzeiteigene beschreibbare Datendateien/sperrt die freigegebene Laufzeitgruppe und die Berechtigung zum atomaren Ersetzen. Verwenden Sie für die private Konfiguration kein geschütztes Home-Verzeichnis oder ein öffentliches Dokumentenstammverzeichnis. Erstellen Sie vorab ein nicht standardmäßiges Anforderungsverzeichnis mit den erforderlichen gemeinsamen Berechtigungen. Nachdem Sie die Pfade auf einer vorhandenen Site geändert haben, wiederholen Sie `--install` und dann `--finalize`, wobei Sie dieselben Optionen für Instanz, Port und SAML verwenden. `--finalize` allein aktualisiert den Helfer/Besitz, nicht den FPM-Pool. Weitere Informationen finden Sie im [Konfigurationsleitfaden](configuration_en.md).
