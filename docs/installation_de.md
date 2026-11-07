# Eine Simbioza-Installation auswählen

<a id="choose-a-simbioza-installation"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Kroatische Version](installation_hr.md)

Diese Anleitungen installieren Simbioza **0.3.44**. Den aktuellen Paketbestand finden Sie unter [Modulversionen und Abhängigkeiten](module-dependencies_en.md). Das Framework bleibt **0.0.25**. Die 0.3.x-Baseline beginnt mit einem neuen Anwendungsverzeichnis, einer neuen Konfiguration und einer neuen Datenbank. Ein Upgrade von 0.2.x oder die Wiederverwendung seiner Simbioza-Backups wird nicht unterstützt.

Wählen Sie unten **eine vollständige Anleitung** aus. Jeder startet an einem Administratorterminal, installiert/überprüft die Host-Tools, lädt die Version herunter, bereitet die Datenbank und Berechtigungen vor, konfiguriert den Webserver, führt den Assistenten aus und überprüft die erste Anmeldung. Nutzen Sie diese Übersicht nicht als zusätzlichen Installationsschritt.

| Installation | PHP Identität | Paketvorbereitung und spätere Anwendungsaktualisierungen | Komplette Anleitung |
|---|---|---|---|
| Apache mod_php | Geteiltes Apache-Konto | CLI, als Codebesitzer | [Starten Sie die Installation von mod_php](installation_mod_php_de.md) |
| Gewöhnlich PHP-FPM | Freigegebenes System-Webkonto in einem FPM-Pool | CLI, als Codebesitzer | [Starten Sie die normale FPM-Installation](installation_fpm_de.md) |
| Gesichert PHP-FPM (empfohlen) | Dedizierte FPM- und Bereitstellungskonten, eingeschränkter Helfer | GUI über den Worker oder CLI als Deploy-Maintainer | [Starten Sie die gesicherte FPM-Installation](installation_fpm_secured_de.md) |

Alle drei verfügen über ein grafisches **Erstinstallationsprogramm** und eine Konfigurations-GUI. Gewöhnliches FPM ist nicht dasselbe wie gesichertes FPM: Durch das alleinige Hinzufügen eines FastCGI-Handlers wird keine Bereitstellungsisolation erstellt oder GUI-Composer/Anwendungsaktualisierungen aktiviert. Nginx erfordert FPM; es kann Apaches mod_php nicht ausführen. [Docker-Installation](docker_en.md) ist eine separate, optionale Image-basierte Route.

<a id="before-choosing-a-php-mode"></a>

## Bevor Sie einen PHP-Modus auswählen

Die Standardbeispiele gelten für einen neuen Linux- oder macOS-Host, nicht für einen vorhandenen Produktionspool. Verfügen Sie über eine Administrator-Shell, ein Domänen-/TLS-Zertifikat für eine öffentliche Site und eine Datenbankauswahl. SQLite benötigt keinen Datenbankdienst. Jeder ausgewählte Führer wiederholt die genauen Vorbereitungsbefehle; es wird davon ausgegangen, dass kein Simbioza FPM/Deploy-Konto vorhanden ist.

Bei der Neuinstallation kümmert sich der Systemadministrator zunächst um die Prozesse und Daten der alten Site, prüft vorhandene Identitäten und wählt die richtige Instanz und den richtigen Port aus. Die Anleitungen löschen niemals eine andere Installation oder erstellen eine laufende Site neu, nur weil das Download-Verzeichnis vorhanden ist.

Die private Modulkonfiguration verwendet `HPH_CONFIG_PATH` (Standard `config/`); Permanente Dateien verwenden `data/`. Nur `public/` wird vom Webserver verfügbar gemacht.

<a id="after-installation"></a>

## Nach der Installation

Richten Sie [Protokollrotation](logging_en.md) für Cron-, Updater- und FPM-Ausgabe ein. Der rotierende `app.log`-Handler der Anwendung rotiert diese separaten Dateien nicht.

Jedes vollständige Handbuch enthält sein eigenes Modul, seine eigene Sprache und Update-Befehle, Backup-Grenzen und abschließende Prüfungen. Routinemäßige Paket-/Codearbeiten gehören zur Betreuer-/Bereitstellungsidentität, nicht zum Webkonto und nicht zum Root. Sprachpakete können unabhängig von einer Anwendungsversion aktualisiert werden.

Der Updater der Anwendung aktualisiert gebündelte Benutzerhandbücher aus den installierten Release-Paketen. Das Lesen von GitHub `main` ersetzt nicht automatisch Seiten, die bereits auf einer Site gespeichert sind; Ihr Inhalt kann zu einer älteren Version gehören. Die gebündelten Installationsseiten werden aus diesen Markdown-Quellen generiert.
