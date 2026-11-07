# API – Aufgaben in Inhalten

<a id="api--tasks-in-content"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Aufgaben sind Teil des veröffentlichten Seiteninhalts. Die API erstellt ihre Definition nicht; Autoren fügen es im HTML-Editor oder durch strukturierten Inhalt in der [Seite API](api-html-stranice_de.md) hinzu. `task:read` erfordert Zugriff auf die veröffentlichte Seite. `task:write` überprüft zusätzlich die `toggle_scope` und aktuellen Benutzerberechtigungen der Liste.

PUT erfordert einen booleschen Wert `completed` und den ETag der aktuellen Aufgabe GET. Durch das Ändern des Status wird keine weitere Seitenversion erstellt. Die Verlauf zeichnet den ausführenden Benutzer auf. Der Verlauf verwendet die Cursor-Paginierung innerhalb eines begrenzten verfügbaren Datensatzfensters. Wählen Sie die Sprache mit `lang` aus.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/pages/{documentId}/tasks`

**Zweck:** Gibt alle Aufgaben aus der aktuellen Veröffentlichung der Seite zurück.

**Berechtigung:** `task:read`. Zugriff auf die veröffentlichte Seite und Berechtigung gemäß der Regel zur Statusänderung der Aufgabenliste.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks?lang=hr&page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "text": "Pregledati upute",
      "toggle_scope": "viewers",
      "state": {
        "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
        "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "completed": false,
        "updated_by_user_id": null,
        "updated_by_display_name": "",
        "updated_at": "",
        "version": 0
      }
    }
  ],
  "meta": {
    "request_id": "8520a08a8fe81a37a03f5a9e0644ba2d",
    "page": {
      "limit": 1,
      "total": 2,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1",
    "next": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}`

**Zweck:** Gibt eine Aufgabe und ihren aktuellen Status zurück.

**Berechtigung:** `task:read`. Zugriff auf die veröffentlichte Seite und Berechtigung gemäß der Regel zur Statusänderung der Aufgabenliste.

**Pfad:** `documentId`, `taskUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "text": "Pregledati upute",
    "toggle_scope": "viewers",
    "state": {
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": false,
      "updated_by_user_id": null,
      "updated_by_display_name": "",
      "updated_at": "",
      "version": 0
    }
  },
  "meta": {
    "request_id": "8e191e76e361b0b48ab9c57852a654a2"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}/history`

**Zweck:** Gibt die Zustandsänderungsprüfung einer Aufgabe zurück.

**Berechtigung:** `task:read`. Zugriff auf die veröffentlichte Seite und Berechtigung gemäß der Regel zur Statusänderung der Aufgabenliste.

**Pfad:** `documentId`, `taskUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "e8222ff1-2de8-4e43-b7ea-3632fdc5e9fb",
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": true,
      "changed_by_user_id": 1,
      "changed_by_display_name": "Administrator",
      "changed_at": "2026-11-06 22:49:03"
    }
  ],
  "meta": {
    "request_id": "fb30976da54e1510df5467626307a71b",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page%5Blimit%5D=1",
    "next": null
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PUT /api/v1/pages/{documentId}/tasks/{taskUuid}/state`

**Zweck:** Legt den Aufgabenstatus fest, ohne eine neue Seitenversion zu erstellen.

**Berechtigung:** `task:write`. Zugriff auf die veröffentlichte Seite und Berechtigung gemäß der Regel zur Statusänderung der Aufgabenliste.

**Pfad:** `documentId`, `taskUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `completed`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "completed": true
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "completed": true,
    "updated_by_user_id": 1,
    "updated_by_display_name": "Administrator",
    "updated_at": "2026-11-06 22:49:03",
    "version": 1
  },
  "meta": {
    "request_id": "32fc68d59ba7a6ba20b12cc7736128f5"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 400, 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).
