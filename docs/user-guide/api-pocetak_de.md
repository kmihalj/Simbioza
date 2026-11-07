# API – Einstieg und gemeinsame Regeln

<a id="api--getting-started-and-shared-rules"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Die API verbindet Simbioza mit anderen Systemen. Ein Administrator installiert und aktiviert zunächst das Modul API und gibt dann einen Schlüssel an einen echten aktiven Benutzer aus. Ein Schlüssel verfügt über ausgewählte Scopes, optionales Ablaufdatum und IP-Einschränkungen. Die aktuellen Benutzerberechtigungen gelten weiterhin: Ein administrativer Scope macht einen normalen Benutzer nicht zu einem Administrator.

Das [Administratorhandbuch](admin-api_de.md) behandelt das Ausstellen, Genehmigen, Rotieren und Widerrufen von Schlüsseln. In diesem Kapitel geht es um die Anwendungsintegration. Jeder Befehl und jede angezeigte Antwort wurde mit echten HTTP-Anfragen und isolierten synthetischen Daten überprüft. IDs, Zeitstempel und ETags unterscheiden sich je nach Installation. Verwenden Sie die von Ihren eigenen Anfragen zurückgegebenen Werte. Geheimnisse werden durch `[REDACTED]` ersetzt.

<a id="preparation"></a>

## Vorbereitung

Legen Sie Ihre Installationsadresse einschließlich des Unterverzeichnisses ohne abschließenden Schrägstrich fest. Halten Sie den Schlüssel aus dem Quellcode und öffentlichen Repositorys fern.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

Die Befehle verwenden `curl --globoff`, sodass Klammern in Parametern wie `page[limit]` nicht als URL-Muster interpretiert werden. `jq` wählt nur lesbare Auszüge aus umfangreichen Antworten aus; Simbioza erfordert es nicht. Senden Sie den Schlüssel in `Authorization: Bearer`, niemals in der URL oder einem Browser-Cookie.

<a id="request-bodies-and-responses"></a>

## Anforderungstexte und Antworten

Schreibvorgänge verwenden `Content-Type: application/json` und ein Objekt der obersten Ebene mit benannten Feldern. `{}` ist ein gültiges Objekt, obwohl für einen Vorgang möglicherweise bestimmte Felder erforderlich sind. `[]`, Skalare und numerische Feldnamen der obersten Ebene sind ungültig; Verschachtelte Listen werden weiterhin unterstützt. Ein Beispiel kann sein, dass ein Textkörper weggelassen wird, wenn er von der Operation nicht gelesen wird. Umfragemutationen erfordern JSON und ihre Versionsinformationen.

Erfolgreiche JSON-Antworten verwenden `data`, `meta` und `links`. `meta.request_id` identifiziert eine Anfrage in Protokollen. Eine 204-Antwort hat keinen JSON-Text. OpenAPI gibt das Dokument direkt zurück; Anhänge, CSV, ICS und ZIP exportieren Rückgabedateien. Überprüfen Sie `Content-Type`, bevor Sie JSON dekodieren.

## Pagination

Viele Listen verwenden `page[limit]` (Standard 50, maximal 100) und ein undurchsichtiges `page[after]`. Folgen Sie `links.next`, bis es `null` ist; Erstellen oder ändern Sie keine Cursor. Ein ungültiger Cursor gibt möglicherweise die erste Seite zurück. Die Paginierung ist ressourcenspezifisch: Die Umfrage verwendet `page/per_page`, die Suche verwendet ebenfalls `page/per_page` und Arbeitsbereichszusammenfassungen verwenden `page/limit`. Persönliche Follower geben eine begrenzte Liste ohne den Cursorvertrag zurück.

<a id="concurrent-updates"></a>

## Gleichzeitige Updates

Für Routen, die mit `If-Match` gekennzeichnet sind, GET zuerst die aktuelle Ressource oder den aktuellen Entwurf und den HTTP `ETag` Header lesen. Senden Sie den genauen Wert, einschließlich Anführungszeichen, im Header `If-Match` des nächsten Schreibvorgangs. Beispiele nennen diese Variable `ETAG`. Verwenden Sie beim Aktualisieren eines Entwurfs nicht den ETag einer anderen Sprache oder den ETag einer veröffentlichten Seite. Standardmäßig gibt eine fehlende Vorbedingung 428 und eine veraltete Vorbedingung 412 zurück. HTML-Entwürfe verwenden zusätzlich `draft_revision`. Survey verwendet seine eigenen `version` und `revision` anstelle von ETags.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

Dies zeigt das Speichern von Antwortheadern. Erhalten Sie `ETAG` von der entsprechenden Ressource GET, nicht von `/me`.

<a id="repeated-writes-and-limits"></a>

## Wiederholte Schreibvorgänge und Einschränkungen

JSON-Schreibvorgänge können einen eindeutigen `Idempotency-Key` verwenden. Der gleiche Schlüssel und die gleiche Anforderung geben die vorherige Antwort wieder; die Wiederverwendung des Schlüssels mit einem anderen Textkörper gibt 409 zurück. Mehrteilige und binäre Uploads unterstützen diesen Header nicht und geben 422 zurück. Der Standardwert beträgt 120 Anfragen pro Minute und Schlüssel, mit `X-RateLimit-*`-Headern und `Retry-After` auf 429; Administratoren können ein anderes Limit konfigurieren. Der standardmäßige maximale JSON-Körper beträgt 2 MiB, und einzelne Module erzwingen zusätzliche Grenzwerte. CORS ist eine separate Administratorrichtlinie und ersetzt nicht die Bearer-Authentifizierung.

Lesen Sie OpenAPI mit einem gültigen Schlüssel. Es listet registrierte Routen und Scopes von aktiven Modulen auf, aber seine generischen Schemata beschreiben Geschäftsparameter, mehrteilige Felder oder binäre Antworten nicht vollständig. Nutzen Sie auch die Ressourcenkapitel. CalDAV und öffentliche ICS-Links sind separate Protokolle außerhalb dieses Dokuments.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1`

**Zweck:** Gibt die Version, die verfügbaren Ressourcen und die Scopes der aktivierten Module zurück.

**Berechtigung:** ein gültiger API-Schlüssel. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Tatsächliche Antwort: **HTTP 200**. Dies ist die tatsächliche Ausgabe des angezeigten `jq`-Filters.

```json
{
  "data": {
    "name": "HeartPhrame API",
    "version": "v1",
    "resources": [
      "users",
      "groups",
      "audit",
      "webhooks",
      "notifications",
      "page",
      "attachment",
      "task",
      "workspace",
      "workspace-search",
      "surveys",
      "calendar",
      "follows"
    ],
    "scopes": [
      "users:read",
      "users:create",
      "users:update",
      "users:delete",
      "groups:read",
      "groups:manage",
      "audit:read",
      "webhooks:read",
      "webhooks:manage",
      "notifications:read",
      "notifications:write",
      "page:read",
      "page:write",
      "page:publish",
      "attachment:read",
      "attachment:write",
      "task:read",
      "task:write",
      "workspace:read",
      "workspace:manage",
      "workspace-search:read",
      "surveys:read",
      "surveys:write",
      "surveys:publish",
      "surveys:results",
      "surveys:export",
      "calendar:read",
      "calendar:write",
      "follows:read",
      "follows:write"
    ],
    "security": {
      "authentication": "bearer",
      "rate_limit_headers": true,
      "idempotency_key_for_writes": true,
      "problem_format": "RFC 9457",
      "webhooks": {
        "enabled": true,
        "delivery": "asynchronous",
        "signature": "hmac-sha256"
      }
    }
  },
  "meta": {
    "request_id": "cc6b890db5532c15134ac71575045fb5"
  },
  "links": {
    "self": "/api/v1"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/me`

**Zweck:** Gibt Identität, Scopes und öffentliche Benutzerdaten ohne Passwortfelder zurück.

**Berechtigung:** ein gültiger API-Schlüssel. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Tatsächliche Antwort: **HTTP 200**.

```json
{
  "data": {
    "key": {
      "id": 1,
      "public_id": "72ea299cf26253dc485c6a95",
      "scopes": [
        "users:read",
        "users:create",
        "users:update",
        "users:delete",
        "groups:read",
        "groups:manage",
        "audit:read",
        "webhooks:read",
        "webhooks:manage",
        "notifications:read",
        "notifications:write",
        "page:read",
        "page:write",
        "page:publish",
        "attachment:read",
        "attachment:write",
        "task:read",
        "task:write",
        "workspace:read",
        "workspace:manage",
        "workspace-search:read",
        "surveys:read",
        "surveys:write",
        "surveys:publish",
        "surveys:results",
        "surveys:export",
        "calendar:read",
        "calendar:write",
        "follows:read",
        "follows:write"
      ]
    },
    "user": {
      "id": 1,
      "login_identifier": "Admin",
      "display_name": "Administrator",
      "email": "admin@example.test",
      "first_name": "Admin",
      "last_name": "Simbioza",
      "group_ids": [
        1
      ],
      "group_keys": [
        "administrator"
      ],
      "is_admin": true,
      "is_active": true,
      "auth_source": "local"
    }
  },
  "meta": {
    "request_id": "bd11f1fa32846abeb0f47349982394fb"
  },
  "links": {
    "self": "/api/v1/me"
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401, 429. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/openapi.json`

**Zweck:** Gibt OpenAPI 3.1 JSON für derzeit aktivierte Module zurück.

**Berechtigung:** ein gültiger API-Schlüssel. Aktiver API-Schlüssel und zulässige Zielrichtlinie; Nicht-Administratoren sind auf die mit diesem Schlüssel erstellten Abonnements beschränkt, während Administratoren alle verwalten können.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Tatsächliche Antwort: **HTTP 200**. Dies ist die tatsächliche Ausgabe des angezeigten `jq`-Filters.

```json
{
  "openapi": "3.1.0",
  "info": {
    "title": "HeartPhrame API",
    "version": "1.0.0",
    "description": "Versioned modular API. Domain ACL is always evaluated in addition to key scopes."
  },
  "path_count": 77,
  "security": [
    {
      "bearerAuth": []
    }
  ],
  "components": {
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "HeartPhrame API key"
      }
    }
  }
}
```

**Für diesen Vorgang überprüfte Fehler:** 401. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

<a id="errors-and-diagnostics"></a>

## Fehler und Diagnose

Überprüfen Sie den Status HTTP, bevor Sie `data` lesen. Abgelehnte Anfragen geben `application/problem+json` zurück, mit `status`, `code`, `detail` und `request_id`. A404 für private Inhalte bestätigt nicht, ob die Ressource vorhanden ist. Deaktivieren Sie nicht die Berechtigungsprüfungen oder den Webformular-Schutz CSRF, um einen API-Fehler zu umgehen. Die Inhaberauthentifizierung ist von der Websitzung getrennt.

| HTTP | Bedeutung |
|---|---|
| 400 | ungültig JSON oder Anfrage |
| 401 | fehlender, ungültiger oder unzulässiger Schlüssel |
| 403 | fehlender Bereich oder aktuelle Berechtigung |
| 404 | Die Ressource ist für diesen Benutzer nicht verfügbar oder fehlt |
| 409 | Versions-, Status- oder Idempotenzkonflikt |
| 412 | veraltetes If-Match |
| 415 | Nicht unterstützter, nicht leerer Textinhaltstyp |
| 422 | Ungültige Geschäftsfelder oder nicht unterstützter Upload |
| 428 | erforderlicher If-Match fehlt |
| 429 | Ratenlimit erreicht; Folgen Sie Retry-After |

<a id="without-authentication"></a>

### Ohne Authentifizierung

Tatsächliche Antwort HTTP 401:

```json
{
  "type": "about:blank",
  "title": "Authentication failed",
  "status": 401,
  "detail": "Send a valid active API key as a Bearer token.",
  "code": "invalid_api_key",
  "instance": "/api/v1",
  "request_id": "1022fef5ddfad19a8c78dd5c87f8bf70"
}
```

<a id="missing-update-precondition"></a>

### Fehlende Update-Voraussetzung

Tatsächliche Antwort HTTP 428:

```json
{
  "type": "about:blank",
  "title": "The change precondition was not met",
  "status": 428,
  "detail": "Read the resource before changing it and send its ETag as If-Match.",
  "code": "if_match_required",
  "instance": "/api/v1/workspaces/api-ogledno-podrucje",
  "request_id": "a0153b5a77ba484de02d0c9e35974996"
}
```

<a id="same-key-different-body"></a>

### Gleicher Schlüssel, anderer Körper

Tatsächliche Antwort HTTP 409:

```json
{
  "type": "about:blank",
  "title": "The Idempotency-Key has already been used",
  "status": 409,
  "detail": "The same key must not be used for different requests.",
  "code": "idempotency_key_reused",
  "instance": "/api/v1/groups",
  "request_id": "622b2c534cf92ddc3f402f0de733122a"
}
```

<a id="rate-limit-reached"></a>

### Ratenlimit erreicht

Tatsächliche Antwort HTTP 429:

```json
{
  "type": "about:blank",
  "title": "Too many API requests",
  "status": 429,
  "detail": "Wait before the next request.",
  "code": "rate_limit_exceeded",
  "instance": "/api/v1/me",
  "request_id": "a43267398714c9a8ac74f166a959cc41"
}
```

<a id="php--a-complete-small-client"></a>

## PHP – ein kompletter kleiner Client

Dies ist ein separates PHP-Beispiel; sie müssen Curl-Befehle nicht in Code übersetzen. Speichern Sie die Funktion als `api-client.php`. Die cURL-Erweiterung PHP ist erforderlich. Produktions-URLs müssen HTTPS verwenden; HTTP ist nur für die lokale Loopback-Entwicklung zulässig. Die Funktion unterstützt JSON, mehrteilige und binäre Antworten, folgt keinen Weiterleitungen und löst HTTP-Fehler aus. Lesen Sie Geheimnisse aus Ihrem Geheimspeicher oder Ihrer Umgebung.

```php
<?php

declare(strict_types=1);

/**
 * EN: Calls the REST API; redirects are disabled so credentials stay on the configured host.
 * HR: Poziva REST API; preusmjeravanja su isključena kako vjerodajnice ne bi otišle drugom hostu.
 *
 * @param array<string,mixed>|stdClass|null $body
 * @param list<string> $extraHeaders
 * @param array<string,string|CURLFile>|null $multipart
 * @return array{status:int,headers:array<string,string>,json:mixed,body:string}
 */
function simbiozaApi(
    string $baseUrl,
    #[SensitiveParameter] string $key,
    string $method,
    string $path,
    array|stdClass|null $body = null,
    array $extraHeaders = [],
    ?array $multipart = null,
): array {
    if (!extension_loaded('curl')) {
        throw new RuntimeException('PHP cURL extension is required.');
    }
    $url = parse_url($baseUrl);
    $local = in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true);
    if (!is_array($url) || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
        || (($url['scheme'] ?? '') !== 'https' && !$local)
        || !isset($url['host']) || isset($url['user']) || isset($url['pass'])
        || isset($url['query']) || isset($url['fragment']) || trim($key) === '') {
        throw new InvalidArgumentException('Set a valid HTTPS installation URL and API key.');
    }
    if (($path !== '/api/v1' && !str_starts_with($path, '/api/v1/'))
        || str_contains($path, "\r") || str_contains($path, "\n")
        || ($body !== null && $multipart !== null)) {
        throw new InvalidArgumentException('Use an /api/v1 path and one request-body format.');
    }
    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $key, ...$extraHeaders];
    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ];
    if ($multipart !== null) {
        // EN: cURL supplies the multipart boundary. HR: cURL sam postavlja multipart razdjelnik.
        $options[CURLOPT_POSTFIELDS] = $multipart;
    } elseif ($body !== null) {
        // EN: An empty named-field object must be {}, not []. HR: Prazan objekt polja mora biti {}, ne [].
        $options[CURLOPT_POSTFIELDS] = json_encode($body === [] ? new stdClass() : $body, JSON_THROW_ON_ERROR);
        $headers[] = 'Content-Type: application/json';
    }
    $responseHeaders = [];
    $options[CURLOPT_HTTPHEADER] = $headers;
    $options[CURLOPT_HEADERFUNCTION] = static function ($handle, string $line) use (&$responseHeaders): int {
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[strtolower(trim($name))] = trim($value);
        }
        return strlen($line);
    };
    $handle = curl_init(rtrim($baseUrl, '/') . $path);
    curl_setopt_array($handle, $options);
    $raw = curl_exec($handle);
    if ($raw === false) {
        $message = curl_error($handle);
        unset($handle);
        throw new RuntimeException('HTTP transport failed: ' . $message);
    }
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    unset($handle);
    $json = str_contains($responseHeaders['content-type'] ?? '', 'json') && $raw !== ''
        ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
    if ($status >= 400) {
        $detail = is_array($json) ? (string)($json['detail'] ?? 'Request rejected') : 'Request rejected';
        $requestId = $responseHeaders['x-request-id'] ?? '';
        throw new RuntimeException("HTTP $status: $detail (request_id=$requestId)");
    }
    return ['status' => $status, 'headers' => $responseHeaders, 'json' => $json, 'body' => $raw];
}
```

Verwendung (legen Sie zunächst die Umgebungsvariablen aus der Vorbereitung fest):

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Tatsächliche Ausgabe auf dem synthetischen Administratorkonto:

```text
Administrator
```

Übergeben Sie benannte Felder als fünftes Argument für einen JSON-Schreibvorgang. Verwenden Sie `[]` für ein leeres Objekt (der Helfer kodiert es als `{}`). Fügen Sie `['If-Match: ' . $etag]` als sechstes Argument für ETags hinzu. Multipart ist das siebte Argument, zum Beispiel `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, mit dem fünften Argument `null`. Legen Sie den mehrteiligen Inhaltstyp nicht manuell fest. Speichern Sie Dateien mit `file_put_contents()` mit `body`; dekodiert JSON ist in `json`. Ressourcenpfade, Payloads und Berechtigungen sind die gleichen wie in den Curl-Beispielen in den anderen Kapiteln.
