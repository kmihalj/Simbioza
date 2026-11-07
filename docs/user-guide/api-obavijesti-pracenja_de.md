# API – Benachrichtigungen und persönliche Abonnements

<a id="api--notifications-and-personal-follows"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Alle Vorgänge gelten für den Schlüsseleigentümer. Ein JSON-Körper kann nicht den Posteingang oder die Einstellungen eines anderen Benutzers auswählen. `notifications:read` liest den Posteingang; `notifications:write` ändert den Lesestatus und löscht Lesebenachrichtigungen. Listen verwenden `state=all`, `read` oder `unread` mit Cursorparametern. PATCH sendet den booleschen Wert `read` mit einem ETag; `read-all` markiert den gesamten Posteingang des Besitzers als gelesen. Ein fremder/fehlender UUID gibt keinen Inhalt preis; der aktuelle GET gibt für diesen Fall 422 `notification_validation_failed` zurück.

`follows:read`/`follows:write` persönliche Follower verwalten. Typen sind `workspace`, `page`, `calendar`, `task_list`. Der `target_id` einer Seite ist ihre **numerische Arbeitsbereichs-Knoten-ID**, nicht der Dokumentschlüssel HTML. Für Aufgabenlisten ist außerdem `document_id` erforderlich. Der aktuelle Zugriff auf das Ziel wird erneut überprüft. Das Verfolgen eines Kalenders und das Abonnieren desselben sind unterschiedlich: Durch das Entfernen eines Followers werden Benachrichtigungen deaktiviert, ohne dass das Kalenderabonnement entfernt wird.

Zu den persönlichen Vorlieben gehören `email_enabled`, `email_mode` (`immediate`, `daily`, `important`, `off`), `notify_own_changes`, `theme_mode` (`auto`, `light`, `dark`, `system`). Die Designauswahl gilt nur, wenn die globale Richtlinie einen persönlichen Modus zulässt. Ein Follower kann `email_mode_override` haben; `null` erbt Präferenzen. `GET personal-workspace` erfordert `workspace:read` und gibt den Status zurück; **Es wird kein Arbeitsbereich erstellt**.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/notifications`

**Zweck:** Gibt einen paginierten Posteingang mit einem optionalen `state=all|read|unread`-Filter zurück.

**Berechtigung:** `notifications:read`. Nur die Benachrichtigungen des Schlüsselbesitzers.

**Abfrageparameter:** `page[after]`, `page[limit]`, `state`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications?page[limit]=1&state=all"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
      "user_id": 1,
      "notification_key": "api.manual.example",
      "title": "API workshop reminder",
      "message": "Review the API example before the workshop.",
      "link_url": "/about",
      "source_module": "application",
      "source_reference": "api-manual",
      "dedup_key": "api-manual-notification",
      "data_json": null,
      "read_at": null,
      "created_at": "2026-11-06 22:48:09",
      "updated_at": "2026-11-06 22:48:09",
      "is_read": false,
      "data": []
    }
  ],
  "meta": {
    "request_id": "4e774695d4bd72dc9ac089f709b259bb",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/notifications?page%5Blimit%5D=1&state=all",
    "next": null
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/notifications/read-all`

**Zweck:** Markiert alle Benachrichtigungen des Schlüsseleigentümers als gelesen.

**Berechtigung:** `notifications:write`. Nur die Benachrichtigungen des Schlüsselbesitzers.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/notifications/read-all"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "updated": 0
  },
  "meta": {
    "request_id": "85f72e80bda9476062b33acf913e71e0"
  },
  "links": {
    "self": "/api/v1/notifications/read-all"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/notifications/{uuid}`

**Zweck:** Entfernt dauerhaft eine eigene Lesebenachrichtigung.

**Berechtigung:** `notifications:write`. Nur die Benachrichtigungen des Schlüsselbesitzers.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/notifications/{uuid}`

**Zweck:** Gibt eine Benachrichtigung zurück, die dem Schlüsseleigentümer gehört.

**Berechtigung:** `notifications:read`. Nur die Benachrichtigungen des Schlüsselbesitzers.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": null,
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": false,
    "data": []
  },
  "meta": {
    "request_id": "c7fcdc0d24f01935cd578d7f201b921b"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PATCH /api/v1/notifications/{uuid}`

**Zweck:** Legt den Lese-/Ungelesen-Status einer eigenen Benachrichtigung fest.

**Berechtigung:** `notifications:write`. Nur die Benachrichtigungen des Schlüsselbesitzers.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `read`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "read": true
}' \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": "2026-11-06 22:49:03",
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": true,
    "data": []
  },
  "meta": {
    "request_id": "6a1d831097fc07de5556f61468f959b6"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/me/follow-preferences`

**Zweck:** Gibt kombinierte persönliche Kanal- und Kadenzpräferenzen zurück.

**Berechtigung:** `follows:read`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": null,
    "email_enabled": false
  },
  "meta": {
    "request_id": "98b2df0845176673b3e4ccdf749b2a12"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PATCH /api/v1/me/follow-preferences`

**Zweck:** Aktualisiert teilweise persönliche Einstellungen, ohne die Zeilen eines anderen Benutzers zu ändern.

**Berechtigung:** `follows:write`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `email_enabled`, `email_mode`, `notify_own_changes`, `theme_mode`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "email_enabled": false,
  "email_mode": "off",
  "notify_own_changes": false
}' \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": "2026-11-06 22:49:04",
    "email_enabled": false
  },
  "meta": {
    "request_id": "0073fb95a0562c45f0f28bdebd4dcd34"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/me/follows`

**Zweck:** Gibt ACL-gefilterte persönliche Follower zurück.

**Berechtigung:** `follows:read`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Abfrageparameter:** `search`, `type`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows?type=page&search=API"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
      "user_id": 1,
      "target_type": "page",
      "target_id": "36",
      "workspace_id": 4,
      "page_id": 36,
      "document_id": "api-plan-radionice",
      "label_snapshot": "Plan API radionice",
      "email_mode_override": null,
      "created_at": "2026-11-06 22:50:09",
      "updated_at": "2026-11-06 22:50:09",
      "accessible": true,
      "label": "Plan API radionice",
      "url": "/workspace/api-ogledno-podrucje/api-plan-radionice",
      "following": true,
      "automatic_excluded": false
    }
  ],
  "meta": {
    "request_id": "50ba4b674bc6f0ab007022025f0f4ecd"
  },
  "links": {
    "self": "/api/v1/me/follows?type=page&search=API"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/me/follows`

**Zweck:** Fügt nach der Domänenprüfung ACL einen eigenen Follower hinzu.

**Berechtigung:** `follows:write`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `document_id`, `email_mode_override`, `label`, `target_id`, `target_type`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "target_type": "page",
  "target_id": "36",
  "label": "API workshop plan"
}' \
  "$BASE_URL/api/v1/me/follows"
```

Tatsächliche Antwort: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
    "user_id": 1,
    "target_type": "page",
    "target_id": "36",
    "workspace_id": 4,
    "page_id": 36,
    "document_id": "api-plan-radionice",
    "label_snapshot": "Plan API radionice",
    "email_mode_override": null,
    "created_at": "2026-11-06 22:50:09",
    "updated_at": "2026-11-06 22:50:09",
    "accessible": true,
    "label": "Plan API radionice",
    "url": "/workspace/api-ogledno-podrucje/api-plan-radionice"
  },
  "meta": {
    "request_id": "21c4c68788859bf941d61d7d43fc8e8b"
  },
  "links": {
    "self": "/api/v1/me/follows"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/me/follows/{type}/{targetId}`

**Zweck:** Entfernt nur einen Follower, der dem Schlüsseleigentümer gehört.

**Berechtigung:** `follows:write`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Pfad:** `type`, `targetId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows/page/36"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/me/personal-workspace`

**Zweck:** Gibt den persönlichen Arbeitsbereich des API-Schlüsselbesitzers ohne die Daten anderer Benutzer zurück.

**Berechtigung:** `workspace:read`. Nur die Follower/Präferenzen des Schlüsseleigentümers; der aktuelle Zugriff auf verfolgte Ressourcen wird erneut überprüft.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/personal-workspace"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "exists": false,
    "deleted": false,
    "automatic_creation_enabled": true,
    "self_creation_enabled": false,
    "can_create": false,
    "workspace": null
  },
  "meta": {
    "request_id": "2d5ab5b5583445fea4b560c4645137cd"
  },
  "links": {
    "self": "/api/v1/me/personal-workspace"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.
