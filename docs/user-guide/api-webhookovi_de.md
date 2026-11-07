# API – Webhooks und Zustellungen

<a id="api--webhooks-and-deliveries"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Ein Webhook informiert ein anderes System über ein Ereignis, ohne wiederholt Listen abzufragen. Ein Administrator muss Webhooks aktivieren und Zielhosts zulassen. `webhooks:read` oder `webhooks:manage` ist erforderlich; ein Nicht-Administrator verwaltet Abonnements, die mit demselben Schlüssel API erstellt wurden, während ein Administrator mit dem entsprechenden Scope alle Abonnements verwalten kann.

POST erfordert `name`, einen zulässigen HTTPS, `target_url`, eine `events`-Liste (z. B. `['*']`) und optional einen booleschen Wert `active`. Das HMAC-Signaturgeheimnis wird nur zurückgegeben, wenn es erstellt oder rotiert wird. Speichern Sie es im Empfangssystem. Andere GET-Antworten machen es nicht sichtbar. PATCH, DELETE, Rotationsgeheimnis und Zustellungswiederholung verwenden ETags.

Eine Inhaltsmutation fügt eine Zustellung in der Warteschlange hinzu. GET `deliveries` zeigt Versuche, Status und Zielantworten. `retry` plant eine bestehende Zustellung neu; Dies bedeutet nicht, dass das Remote-System es empfangen hat. Ein separat eingeplanter Worker führt die Zustellung durch und muss auf dem Server konfiguriert werden. Diese REST-Beispiele wurden anhand der Warteschlange überprüft, ohne an eine externe Adresse zu senden.

Die technische Dokumentation des Moduls API beschreibt Webhook-Signaturen und Payloads. Der Empfänger muss die HMAC-SHA256-Signatur anhand der ursprünglichen Anforderungsbytes überprüfen und vor erneutes Senden derselben Anfrage (Replay) schützen. Der Schlüssel API, der ein Abonnement verwaltet, ist nicht das Webhook-Signaturgeheimnis.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/webhooks`

**Zweck:** Gibt eine paginierte Liste der für den aktuellen Schlüssel verfügbaren Abonnements zurück.

**Berechtigung:** `webhooks:read`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Abfrageparameter:** `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks?page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "b621cf9d278e6902b10f96a7c2f6d79e",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/webhooks?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/webhooks`

**Zweck:** Erstellt ein Abonnement und gibt nur in dieser Antwort sein neues Signaturgeheimnis zurück.

**Berechtigung:** `webhooks:manage`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `active`, `events`, `name`, `target_url`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API observer",
  "target_url": "https://example.com/hooks/simbioza",
  "events": [
    "*"
  ],
  "active": true
}' \
  "$BASE_URL/api/v1/webhooks"
```

Tatsächliche Antwort: **HTTP 201**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "673b6de22148feaba0dc6b194655cda1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/webhooks/{uuid}`

**Zweck:** Entfernt dauerhaft ein Abonnement mit optimistischem Parallelitätsschutz.

**Berechtigung:** `webhooks:manage`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/webhooks/{uuid}`

**Zweck:** Gibt ein zulässiges Abonnement ohne sein Signaturgeheimnis zurück.

**Berechtigung:** `webhooks:read`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "ce22c9cc6d5493b57b2b1026ddc69a27"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 404. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PATCH /api/v1/webhooks/{uuid}`

**Zweck:** Aktualisiert ein Abonnement nur, wenn der Client den aktuellen If-Match sendet.

**Berechtigung:** `webhooks:manage`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `active`, `events`, `name`, `target_url`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "name": "API observer updated"
}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer updated",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a50a566b4cb87453a1d4835a86d8ab96"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/webhooks/{uuid}/deliveries`

**Zweck:** Gibt den paginierten Zustellungsverlauf für ein Abonnement zurück.

**Berechtigung:** `webhooks:read`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 7,
      "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
      "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
      "event": "calendar_events.deleted",
      "status": "pending",
      "attempts": 0,
      "available_at": "2026-11-06 23:08:58",
      "delivered_at": null,
      "response_status": null,
      "response_body": null,
      "last_error": null,
      "created_at": "2026-11-06 23:08:58",
      "updated_at": "2026-11-06 23:08:58"
    }
  ],
  "meta": {
    "request_id": "79ac9c3580e735f6146257343e05f331",
    "page": {
      "limit": 1,
      "total": 7,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1",
    "next": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}`

**Zweck:** Gibt einen Zustellversuch zurück.

**Berechtigung:** `webhooks:read`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`, `deliveryUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a1a068157f70f58e25aaca7e441ee20b"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry`

**Zweck:** Stellt eine vorhandene Zustellung erneut in die Warteschlange, nachdem ihr ETag überprüft wurde.

**Berechtigung:** `webhooks:manage`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`, `deliveryUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "186121f98aed702b812e57d49f3b06c8"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/webhooks/{uuid}/rotate-secret`

**Zweck:** Rotiert das Geheimnis mit If-Match und gibt den neuen Wert nur einmal zurück.

**Berechtigung:** `webhooks:manage`. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Pfad:** `uuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer updated",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:58"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "205b440690d3939f8600c5588c94c0b1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

<a id="receiver--signatures-and-repeated-messages"></a>

## Empfänger – Signaturen und wiederholte Nachrichten

Der Server sendet ein POST mit diesen Headern. Prüfen Sie die Signatur vor dem Dekodieren von JSON; Änderungen an Leerzeichen oder erneutes Kodieren von JSON verändern die Signatur.

```text
Content-Type: application/json
X-HeartPhrame-Webhook-Id: <event UUID>
X-HeartPhrame-Webhook-Event: <event name>
X-HeartPhrame-Webhook-Timestamp: <Unix seconds>
X-HeartPhrame-Webhook-Signature: v1=<hex HMAC-SHA256>
```

```php
<?php
/**
 * EN: Checks a webhook signature over the unmodified raw body.
 * HR: Provjerava potpis webhooka nad neizmijenjenim izvornim tijelom.
 */
function verifySimbiozaWebhook(
    string $rawBody,
    string $timestamp,
    string $signature,
    #[SensitiveParameter] string $secret,
    ?int $now = null,
): bool {
    if (!ctype_digit($timestamp) || abs(($now ?? time()) - (int)$timestamp) > 300) {
        return false;
    }
    $expected = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    return hash_equals($expected, $signature);
}
```

Die 300-Sekunden-Toleranz ist die Richtlinie dieses Beispiels. Behalten Sie verarbeitete `X-HeartPhrame-Webhook-Id`-Werte bei und führen Sie dieselbe Geschäftsaktion nicht zweimal aus. Eine gültige Signatur allein verhindert die erneutes Senden derselben Anfrage (Replay) nicht.

Ereignisselektoren unterstützen einen genauen Namen, einen Namespace mit `.*` (zum Beispiel `pages.*`) oder `*`. Der Webhook-Körper enthält die Ereignisidentität und bereinigte Mutationsmetadaten. Es wird weder die ursprüngliche Anfrage noch der vollständige Inhalt des Dokuments gesendet.

<a id="server-worker"></a>

## Serverarbeiter

Dies sind Administratorbefehle CLI auf Ihrem Server, getrennt von REST-Anfragen:

```sh
vendor/bin/hph api webhooks:status
vendor/bin/hph api webhooks:worker --batch-size=20
vendor/bin/hph api webhooks:worker --watch --sleep=5
```

`--watch` läuft kontinuierlich und sollte in der Produktion überwacht werden. Vermeiden Sie unnötige manuelle Testlieferungen. Überprüfen Sie für ein fehlgeschlagenes Ereignis den Zustellungsverlauf und verwenden Sie REST `retry`, nachdem Sie die Ursache behoben haben.
