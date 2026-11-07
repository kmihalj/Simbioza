# API – Grenzen, CalDAV und ICS

<a id="api--boundaries-caldav-and-ics"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

In diesem Kapitel wird der öffentliche REST API von anderen Integrationsmechanismen unterschieden. OpenAPI listet registrierte `/api/v1`-Routen auf; es handelt sich nicht um eine Bestandsaufnahme aller Simbioza Webseiten oder Protokolle.

<a id="features-without-a-separate-rest-endpoint"></a>

## Funktionen ohne separaten REST-Endpunkt

- Menü und Design haben keine globalen Einstellungen REST API; es gibt private Arbeitsbereichs-Designsrouten.
- Backup verfügt über keinen REST-Site-/Arbeitsbereichs-Backup- oder Wiederherstellungsendpunkt. HTML ZIP Export ist ein anderes Format, kein Backup.
- ORM, E-Mail und Barrierefreiheit stellen keine separaten öffentlichen REST-Ressourcen bereit.
- Kommentar verfügt über keine REST-Erweiterung zum Erstellen/Löschen von Kommentaren.
- Aufgabe API ändert den bestehenden Aufgabenstatus; Definitionen gehören zum Inhalt des Editors.
- Die Umfrage API akzeptiert keine Teilnehmerbeiträge und ersetzt auch keine externe Anmeldung.
- Calendar REST stellt keine Einladungen/Antworten von Teilnehmern oder Reservierungsgenehmigungen bereit.
- Administrative Web-POST-Routen sind kein REST-Ersatz: Sie verwenden Sitzungen, CSRF und ihre eigenen Berechtigungen.

## CalDAV

CalDAV ist ein separates Protokoll unter `/caldav`, das HTTP Basic mit einer lokalen Anmeldekennung und einem lokalen Passwort verwendet. Ein API Inhaberschlüssel ist kein CalDAV-Berechtigungsnachweis. Verwenden Sie HTTPS. Ein Benutzer, der nur SSO hat, benötigt ein lokales Passwort für CalDAV; Durch die Einstellung wird die lokale Webanmeldung nicht aktiviert. Siehe [Kalender-Clients verbinden](kalendari-povezivanje_de.md).

Die Basissynchronisierung unterstützt OPTIONS/HEAD-Probes, PROPFIND für Root/Principal/Collection, REPORT für einen Kalender und GET/PUT/DELETE für `/{uid}.ics`. PUT/DELETE erfordern aktuellen Kalenderschreibzugriff. Der Objektdateiname folgt seiner UID und muss URL-codiert sein. Vollständig CalDAV Scheduling/iTIP ist nicht implementiert; Besprechungseinladungen werden über die Weboberfläche beantwortet.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Tatsächliches Ergebnis: HTTP 207, `application/xml; charset=utf-8`; die Liste XML enthält nur zugängliche Kalender. Ohne Standardauthentifizierung oder mit einem gewöhnlichen Bearer-Schlüssel gibt derselbe PROPFIND 401 zurück. Diese Methoden wurden zusätzlich mit echten Curl-Anfragen überprüft:

| Verfahren | Weg | Ergebnis |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

<a id="ics-links-and-export"></a>

## ICS-Links und Export

REST `/api/v1/calendars/{calendarUuid}/export.ics` erfordert einen Inhaberschlüssel und `calendar:read`. Bei öffentlichen/privaten ICS-Abonnement-Links über die Webschnittstelle handelt es sich um einen separaten Vertrag mit eigenen Zugriffsregeln oder Token. Fügen Sie diesen URLs keinen API-Schlüssel hinzu. Ein öffentliches ICS-Abonnement ist schreibgeschützt. Erhalten Sie Abonnement-Links aus Ihrem eigenen Kalender, anstatt sie aus den Test-URLs einer anderen Installation zu erstellen.
