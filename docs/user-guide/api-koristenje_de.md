# API – andere Systeme anbinden

<a id="api--connecting-other-systems"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

Dieser Leitfaden richtet sich an Entwickler, die ihre Anwendung mit Simbioza verbinden. Die API funktioniert mit Benutzern, Inhalten, Arbeitsbereichen, Kalendern, Umfragen und persönlichen Daten, die dem Schlüsseleigentümer bereits gestattet sind. Es ersetzt nicht die Installationsverwaltung oder die Anmeldung der Teilnehmer bei Umfragen.

<a id="getting-started"></a>

## Erste Schritte

1. Ein Administrator installiert/aktiviert API und stellt einen Schlüssel mit den erforderlichen Scopes aus.
2. Verwenden Sie [Erste Schritte](api-pocetak_de.md), um die Adresse festzulegen und `/api/v1/me` zu lesen.
3. Überprüfen Sie die verfügbaren Ressourcen und OpenAPI Ihrer Installation.
4. Öffnen Sie das Ressourcenkapitel und folgen Sie dem Beispiel zum Erstellen, Lesen, Aktualisieren oder Exportieren.
5. Behandeln Sie auch Fehler: Der Schlüssel gewährt keinen Zugriff auf die privaten Inhalte eines anderen Benutzers.

<a id="chapters"></a>

## Kapitel

| Kapitel | Verifizierte Operationen |
|---|---|
| [API – Erste Schritte und gemeinsame Regeln](api-pocetak_de.md) | 3 |
| [API – Benutzer und Gruppen](api-korisnici-grupe_de.md) | 11 |
| [API – Arbeitsbereiche, Bäume, Berechtigungen und Designs](api-podrucja_de.md) | 30 |
| [API – HTML-Seiten, Entwürfe und Anhänge](api-html-stranice_de.md) | 22 |
| [API – Kalender und Ereignisse](api-kalendari_de.md) | 13 |
| [API – Umfragen und Ergebnisse](api-ankete_de.md) | 18 |
| [API – Aufgaben im Inhalt](api-zadaci_de.md) | 4 |
| [API – Benachrichtigungen und persönliche Follower](api-obavijesti-pracenja_de.md) | 11 |
| [API – Webhooks und Zustellungen](api-webhookovi_de.md) | 9 |
| [API – Prüfung und Suche](api-audit-pretraga_de.md) | 2 |
| [API Grenzen, CalDAV und ICS](api-granice-protokola_de.md) | — |

Dieses Handbuch deckt alle **114 registrierten REST-Routen** in einer vollständigen Installation ab. Die benannte Aktionsroute „Umfrage“ unterstützt 10 Vorgänge, was **123 separat verifizierte Vorgänge** ergibt. Bei einer modularen Installation werden nur Ressourcen verfügbar gemacht, deren Module installiert und aktiv sind. CORS `OPTIONS` und das separate Protokoll CalDAV liegen außerhalb dieser Summe.

In jedem Beispiel werden Methode, Pfad, Berechtigungen, Parameter, Curl-Befehl, tatsächliche Antwort und verifizierte Fehlercodes angegeben. Die Adresse wird durch Ihren Installationsplatzhalter ersetzt; Schlüssel und Geheimnisse werden nicht veröffentlicht. Antworten verwenden synthetische Daten, daher unterscheiden sich Namen, IDs und Zeitstempel von Ihren. Einige große Antworten verwenden eine explizite `jq`-Projektion; der angezeigte JSON ist dann die tatsächliche Ausgabe dieses Filters.

[Administrator API-Schlüsselverwaltung](admin-api_de.md). [Gemeinsame Regeln, Fehler und ein vollständiger PHP-Client](api-pocetak_de.md).
