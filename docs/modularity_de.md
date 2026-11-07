# Den Funktionsumfang von Simbioza auswählen

<a id="choosing-simbioza-features"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Hrvatska verzija](modularity_hr.md)

Simbioza vereint Seiten, ein Wiki, Kalender, Umfragen und Zusammenarbeit in einer vollständigen Anwendung. Wenn Sie nur einige dieser Funktionen benötigen, ermöglicht das modulare HeartPhrame-Framework auch eine gezielte Installation. Die folgenden Voreinstellungen beschreiben diese zusätzliche Option.

**Simbioza** ist kroatisch und bedeutet **Symbiose**: das Zusammenleben verschiedener Organismen. Die Anwendung ist nach dieser Idee benannt, da ihre Module unterschiedliche Rollen haben, aber zusammenarbeiten können. Eine Seite erklärt ein Kalenderereignis, ein Wiki verbindet Teamwissen und eine Umfrage sammelt Feedback. Das vollständige Simbioza vereint diese Tools, Aufgaben, Kommentare, Benachrichtigungen und Integrationen in einer Umgebung.

<a id="choose-a-purpose"></a>

## Wählen Sie einen Zweck

| Was Sie brauchen | Installationsvoreinstellung | Funktionsmodule |
| --- | --- | --- |
| Alle verfügbaren Funktionen zusammen | Voll Simbioza | Alle optionalen Module |
| Eine einfache Website mit HTML-Seiten | HTML Website | HTML Herausgeber |
| Veranstaltungen, Meetings und CalDAV | Kalender | Kalender |
| Ein durchsuchbares Wiki, organisiert in Arbeitsbereiche | Wiki | Arbeitsbereich, Arbeitsbereichssuche und Simbioza Benutzer |
| Umfragen mit Fragen, Verzweigungen und Ergebnissen | Umfragen | Umfrage |

Jede Installation beinhaltet **Auth, ORM, Menü, Design, Backup und Audit**. Sie bieten Authentifizierung und Berechtigungen, Datenbankzugriff, Menüs, Erscheinungsbild, Backups und Aktivitätsprotokolle. „Standalone“ bedeutet ohne andere Geschäftsmodule, nicht ohne die gemeinsame Basis und erforderliche Abhängigkeiten.

Das Installationsprogramm fügt erforderliche Abhängigkeiten automatisch hinzu. Beispielsweise verwendet Survey den HTML-Editor für Einleitungen und Erklärungen; das Wiki benötigt außerdem den Editor und Simbioza Benutzer fügt Benachrichtigungen hinzu. API, Barrierefreiheit und andere optionale Integrationen erfordern keine unabhängigen Geschäftsfunktionen.

<a id="install-the-selected-tool"></a>

## Installieren Sie das ausgewählte Tool

1. Bereiten Sie eine neue Installation mit der entsprechenden [Installationsanleitung](installation_de.md) vor.
2. Wählen Sie im Schritt „Anwendungseinstellungen“ des Assistenten eine **Installationsvoreinstellung** aus und klicken Sie auf **Voreinstellung anwenden**.
3. Überprüfen Sie die ausgewählten Module. Erforderliche Abhängigkeiten bleiben enthalten; Wählen Sie zusätzliche Funktionen nur aus, wenn Sie sie benötigen.
4. Wählen Sie die Installationssprachen aus und schließen Sie die Einrichtung ab. Inhalts- und Übersetzungssprachen werden aus installierten, aktiven Sprachen ausgewählt.
5. Melden Sie sich als Administrator an, erstellen Sie Ihre Inhalte und fügen Sie Menülinks hinzu, damit Besucher sie finden können.

Das Ergebnis ist eine Anwendung für den von Ihnen gewählten Zweck. Bei Beispielseiten, Kalendern und Umfragen handelt es sich nicht um Geschäftsdaten, die automatisch an jede neue Website gesendet werden. Sie erstellen Ihre Inhalte nach der Installation.

<a id="share-a-standalone-html-website"></a>

## Teilen Sie eine eigenständige HTML-Website

Eine HTML-Website benötigt kein Wiki, um mit mehreren Autoren oder privaten Seiten zu arbeiten. Öffnen Sie als Administrator **Einstellungen → Editor HTML → Seitenberechtigungen** und fügen Sie Benutzer oder Gruppen hinzu. **Hinzufügen** ermöglicht das Erstellen und Bearbeiten eigener Seiten. **Bearbeiten** ermöglicht die Verwaltung aller eigenständigen Seiten, einschließlich privater; es gewährt keinen Zugriff auf Administratoreinstellungen.

Öffnen Sie auf einer einzelnen Seite **Seiteneinstellungen und Berechtigungen**. Wählen Sie öffentliches Lesen, alle angemeldeten Benutzer oder nur ausgewählte Benutzer und Gruppen. Diese Auswahl schützt auch Anhänge, direkte Links und API-Lesevorgänge. Verwenden Sie in einem Wiki stattdessen weiterhin die vorhandenen Arbeitsbereichs- und Seitenberechtigungen.

<a id="extend-an-existing-installation"></a>

## Erweitern Sie eine bestehende Installation

Fügen Sie beispielsweise einen Kalender oder Umfragen zu einer HTML-Website hinzu oder fügen Sie Umfragen, Aufgaben und Kommentare zu einem Wiki hinzu. **Einstellungen → Setup und Module** zeigt an, was installiert ist, was aktiviert ist und welche Abhängigkeiten erforderlich sind.

Bei einer korrekt konfigurierten dedizierten FPM-Installation kann ein Administrator neben einem anderen Modul auf **Installieren** klicken. Für mod_php und normales FPM führt ein Betreuer den entsprechenden Befehl CLI vom Anwendungsstamm aus unter Verwendung des Wartungskontos aus:

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

Dies sind separate Beispiele: Wählen Sie das Modul aus, das Sie benötigen. Das System fügt die erforderlichen Abhängigkeiten hinzu. Verwenden Sie `task` und `comment` für Aufgaben und Kommentare; ein Wiki kann später `confluence-import` hinzufügen, um vorhandene Inhalte zu importieren. Konfigurieren Sie nach der Erweiterung der Installation die Berechtigungen, Menüs und Einstellungen des neuen Moduls. Durch die Installation eines Moduls erhält nicht jeder Benutzer die Berechtigung, Inhalte zu bearbeiten.

Erstellen Sie ein Backup, bevor Sie die Zusammensetzung der Installation ändern. Das Deaktivieren eines optionalen Moduls ist nicht dasselbe wie dessen Deinstallation: Durch das Deaktivieren bleiben die Daten erhalten, während bei der Deinstallation seine Tabellen und Pakete nach der Vorbereitung eines Wiederherstellungs-Backups entfernt werden. Die sechs erforderlichen Module können nicht deaktiviert oder deinstalliert werden.

<a id="add-workspaces-to-an-existing-html-website"></a>

### Fügen Sie Arbeitsbereiche zu einer vorhandenen HTML-Website hinzu

Durch das Hinzufügen von Arbeitsbereichen werden vorhandene HTML-Seiten nicht verschoben. Sie behalten ihre Adressen, Inhalte, Übersetzungen, Anhänge und eigenständigen Berechtigungen. Eigenständige HTML-Seiten und Seiten in Arbeitsbereiche können zusammen verwendet werden, auch über API.

Nur ein Administrator kann eine vorhandene Seite in einen Arbeitsbereich verschieben:

1. Öffnen Sie **Einstellungen → Editor HTML → Seiten außerhalb von Arbeitsbereichen**.
2. Suchen Sie die Seite und wählen Sie **In den Arbeitsbereich verschieben**.
3. Öffnen Sie die Zielliste oder suchen Sie nach Namen. Beim Scrollen werden weitere Arbeitsbereiche geladen.
4. Überprüfen Sie die Sichtbarkeit des Ziels, bestätigen Sie seine Berechtigungen und verschieben Sie dann die Seite.

Das Ergebnis ist eine Seite auf der obersten Ebene des Zielbaums. Alle Übersetzungen, der Versionsverlauf und die Anhangsversionen werden mit verschoben. Dateien werden in den Speicher des Arbeitsbereichs übertragen; die Datenbank HTML bleibt mit dem neuen Arbeitsbereichs-Eigentümer in den gleichen Versionsdatensätzen. Links in gespeicherten Seiten werden repariert, einschließlich Sprachauswahl und Anker, und vorhandene Seiten- und Anhangsadressen bleiben mit den Zugriffsprüfungen des Ziels verwendbar. Veröffentlichte Versionen bleiben veröffentlicht und Arbeitsentwürfe bleiben Entwürfe.

**Zielberechtigungen ersetzen den eigenständigen Zugriff.** Die alten Leser werden nicht automatisch als Ausnahmen hinzugefügt. Durch das Verschieben einer öffentlichen Seite in einen privaten Arbeitsbereich kann diese eingeschränkt werden. Durch das Verschieben einer privaten Seite in einen öffentlichen Arbeitsbereich können die veröffentlichten Inhalte öffentlich gemacht werden. Überprüfen Sie das Ziel, bevor Sie es bestätigen. Erstellen Sie zunächst eine Sicherungskopie, wenn Sie einen Rückkehrpunkt benötigen: Dieser Bildschirm bietet keine umgekehrte Bewegung.

<a id="updates-and-backups"></a>

## Updates und Backups

Neuinstallationen erhalten unabhängige Anmeldecookies, auch wenn mehrere Websites eine Domain teilen. Bei einer Wiederherstellung bleibt die lokale Sitzungsidentität der Ziel-Site erhalten, anstatt die Anmelde-Cookie-Identität einer anderen Site zu kopieren.

Dediziertes FPM mit einem korrekt konfigurierten eingeschränkten Helfer unterstützt Updates über **Einstellungen → Setup und Module → Update ausführen**. Die bloße Verwendung von FPM reicht nicht aus: Die Umgebungsprüfung muss bestätigen, dass GUI-Updates verfügbar sind.

Bei anderen klassischen Installationen führt der Betreuer diese Befehle im Stammverzeichnis der Anwendung aus:

```bash
php update.php --check --lang=en
php update.php --lang=en
```

Bei Updates bleiben die ausgewählten Module und vorhandenen Daten erhalten. Ein neues optionales Modul steht zur Installation zur Verfügung; es wird nicht automatisch aktiviert. Eine vollständige Sicherung umfasst die Daten und tragbaren Einstellungen aktivierter Module. Bewahren Sie außerdem eine separate Kopie der Hostkonfiguration und der lokalen Geheimnisse auf, die nicht durch Geschäftsdatensicherungen übertragen werden.

## Docker

Docker verwendet die gleiche Funktionsauswahl. Wenn ein Image alle Module enthält, wählen und aktivieren Sie während der Installation nur die erforderlichen Module. Erstellen Sie für ein kleineres benutzerdefiniertes Image mit ausgewählten Modulen und ihren erforderlichen Abhängigkeiten. Ein im Image fehlendes Modul erfordert ein Ersatzimage, keine Codeänderung innerhalb eines laufenden Containers. Updates ersetzen das Image, während die Datenbank- und Laufzeitdaten erhalten bleiben. Befolgen Sie die [Docker-Anleitung](docker_en.md).

<a id="guides-and-integration-with-other-systems"></a>

## Anleitungen und Integration mit anderen Systemen

Bei einem Wiki befinden sich diese Seiten im Arbeitsbereich **Benutzerhandbücher**. Ohne Wiki ist das gleiche Handbuch über **Guides** verfügbar, mit Kapitelliste und Navigation. Es ist nicht nötig, ein Wiki zu installieren, nur um das Handbuch zu lesen.

API kann zu jedem Zweck hinzugefügt werden. Ein API-Schlüssel bestimmt zulässige Aufrufe, umgeht jedoch niemals die Berechtigungen seines Besitzers. Verfügbare Routen und Scopes folgen installierten, aktivierten Modulen. Dies unterstützt die Integration mit anderen Systemen und benutzerdefinierten HeartPhrame-Anwendungen, ohne dass Authentifizierung, Navigation, Datenbankzugriff und andere gemeinsame Grundlagen neu erstellt werden müssen.
