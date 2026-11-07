# API – Kalender und Termine

<a id="api--calendars-and-events"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

`calendar:read` liest Kalender, auf die der Schlüsselbesitzer zugreifen kann. `calendar:write` ergänzt die aktuellen Erstellungs-, Schreib- und Verwaltungsberechtigungen. Ein Kalender benötigt `name`; `type`/`calendar_type` kann `personal`, `team` oder `resource` sein, vorbehaltlich der Eigentums- und Verwaltungsregeln.

Für die Kalender PATCH und DELETE ist ein ETag erforderlich. ACL PUT akzeptiert die vollständige `rules`-Liste: `subject_type` (`user`/`group`), numerisch `subject_id`, `can_read`, `can_write`. Es ersetzt direkte Regeln. Lesen Sie daher vor dem Schreiben den aktuellen Status.

Ein Ereignis benötigt `title` und gültige Start-/Endwerte (`starts_at`, `ends_at`) mit `timezone`. `is_all_day` markiert ganztägige Ereignisse. Datums-/Uhrzeitvarianten und Aliase werden pro Vorgang aufgelistet. Für die Wiederholung werden `recurrence_rule` oder separate Häufigkeits-/Endfelder verwendet. Der Ereignisbereich GET erfordert `from` und `to`; `expand_recurring=false` liest Definitionen, während die Erweiterung Vorkommen zurückgibt. Ereignis-IDs sind numerisch, während Kalenderpfade einen UUID verwenden.

Der ICS-Import sendet den Text in `ics`, optional numerisch `calendar_id` und `merge_mode=skip` oder `update`. Das Erstellen eines Kalenders anstelle des Imports in einen vorhandenen Kalender unterliegt den aktuellen Berechtigungen und Einstellungen. ICS-Export lädt den Kalender herunter; Diese Route hat keine `from/to`-Filter. Verwenden Sie die Ereignisliste zur Datumsbereichsfilterung.

Für diesen REST API gibt es keine separaten Einladungs-, Teilnehmerantwort- oder Reservierungsgenehmigungsvorgänge. Durch das Hinzufügen solcher Felder zu einem normalen Ereignis POST wird der Besprechungsworkflow nicht aufgerufen. [CalDAV- und ICS-Protokolle](api-granice-protokola_de.md) sind vom Bearer-REST API getrennt.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/calendars`

**Zweck:** Gibt Kalender zurück, die für den Besitzer des API-Schlüssels sichtbar sind.

**Berechtigung:** `calendar:read`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Abfrageparameter:** `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars?page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Sintetički raspored",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "e1fc33cb335d26e476aea1056e00b0a2",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/calendars`

**Zweck:** Erstellt einen neuen Kalender mit Domänenberechtigungsprüfungen.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API radionice",
  "type": "team",
  "description": "Sintetički raspored",
  "color": "#1677ff",
  "is_enabled": true,
  "is_public_read": false,
  "is_authenticated_read": false
}' \
  "$BASE_URL/api/v1/calendars"
```

Tatsächliche Antwort: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "070d314efe35cc19720e469157dac2f9"
  },
  "links": {
    "self": "/api/v1/calendars"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/calendars/import`

**Zweck:** Importiert iCalendar-Text in einen vorhandenen oder neuen Kalender.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `calendar_id`, `calendar_type`, `color`, `description`, `ics`, `is_authenticated_read`, `is_public_read`, `merge_mode`, `name`, `public_order`, `show_on_public_index`, `show_public_link`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "calendar_id": 1,
  "merge_mode": "skip",
  "ics": "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Simbioza API manual//EN\r\nBEGIN:VEVENT\r\nUID:api-manual-import@example.invalid\r\nDTSTAMP:20261106T100000Z\r\nDTSTART:20261112T090000Z\r\nDTEND:20261112T100000Z\r\nSUMMARY:Imported API event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
}' \
  "$BASE_URL/api/v1/calendars/import"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "calendar": {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Ažurirano API rasporedom.",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": false,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "created": 1,
    "updated": 0,
    "skipped": 0,
    "total": 1
  },
  "meta": {
    "request_id": "5e6f724385a764a7bbb5089da3bc8f01"
  },
  "links": {
    "self": "/api/v1/calendars/import"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/calendars/{calendarUuid}`

**Zweck:** Löscht einen Kalender, wenn seine Domäne ACL dies zulässt.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/calendars/{calendarUuid}`

**Zweck:** Ruft einen Kalender anhand seines öffentlichen UUID ab.

**Berechtigung:** `calendar:read`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "1386cd671b7e38072e364bc34947d19c"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 404. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PATCH /api/v1/calendars/{calendarUuid}`

**Zweck:** Aktualisiert teilweise einen Kalender.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano API rasporedom."
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "48783b78a64fa19566ee89854bffdb99"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 400, 401, 403, 412, 428. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PUT /api/v1/calendars/{calendarUuid}/acl`

**Zweck:** Ersetzt alle Benutzer-/Gruppenregeln ACL eines Kalenders.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `rules`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "rules": [
    {
      "subject_type": "user",
      "subject_id": 2,
      "can_read": true,
      "can_write": true
    }
  ]
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "dc07257329738af268ffc971c7195390"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/calendars/{calendarUuid}/events`

**Zweck:** Gibt Kalenderereignisse innerhalb des angeforderten Bereichs zurück.

**Berechtigung:** `calendar:read`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `expand_recurring`, `from`, `page[after]`, `page[limit]`, `to`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
      "calendar_id": 1,
      "title": "API radionica",
      "description": "Provjera stvarnih događaja",
      "location": "Dvorana A",
      "starts_at": "2026-11-10 10:00:00",
      "ends_at": "2026-11-10 11:00:00",
      "is_all_day": false,
      "timezone": "Europe/Zagreb",
      "recurrence_rule": "",
      "instance_key": "1",
      "is_recurring": false,
      "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
    }
  ],
  "meta": {
    "request_id": "5095e41c647508f3f3c0dbd83e2a9e60",
    "page": {
      "limit": 50,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false",
    "next": null
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/calendars/{calendarUuid}/events`

**Zweck:** Erstellt ein Ereignis in einem Kalender.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica",
  "description": "Provjera stvarnih događaja",
  "location": "Dvorana A",
  "starts_at": "2026-11-10T10:00:00",
  "ends_at": "2026-11-10T11:00:00",
  "timezone": "Europe/Zagreb"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
```

Tatsächliche Antwort: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "f4ea6360950cf37c84849ba4b9425892"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Zweck:** Löscht ein Ereignis aus einem Kalender.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`, `eventId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Zweck:** Ruft ein Ereignis aus dem in der URL genannten Kalender ab.

**Berechtigung:** `calendar:read`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`, `eventId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "fb8d07b66a9f22a0af23e3ec01c284d7"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PATCH /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Zweck:** Aktualisiert teilweise ein Ereignis.

**Berechtigung:** `calendar:write`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`, `eventId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica — ažurirano"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica — ažurirano",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "e4b119585f634b8b131a717ab7fdbaefcc129dcf"
  },
  "meta": {
    "request_id": "4aa9f46489ec917f0e3db3fcb19d348f"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/calendars/{calendarUuid}/export.ics`

**Zweck:** Lädt einen lesbaren Kalender als Standard-ICS-Datei herunter.

**Berechtigung:** `calendar:read`. Aktuelle Kalendereigentümerschaft, Lese-/Schreib-/Verwaltungsberechtigungen und Benutzer-/Gruppen-ACLs.

**Pfad:** `calendarUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.ics \
  "$BASE_URL/api/v1/calendars/e8eaf356-1af4-4a05-8943-263752ef910b/export.ics"
```

Tatsächliche Antwort: **HTTP 200**.

```text
content-type: text/calendar; charset=utf-8
content-disposition: attachment; filename="administrator-personal-calendar.ics"
content-length: 189
```

Die Datei wird als `response.*` gespeichert. Der überprüfte Inhalt hat 189 Bytes; Dekodieren Sie es nicht als JSON.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.
