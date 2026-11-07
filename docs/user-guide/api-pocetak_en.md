# API — getting started and shared rules

[← API — contents](api-koristenje_en.md)

The API connects Simbioza to other systems. An administrator first installs and enables the API module, then issues a key to a real active user. A key has selected scopes, optional expiry and IP restrictions. Current user permissions still apply: an administrative scope does not turn an ordinary user into an administrator.

The [administrator guide](admin-api_en.md) covers issuing, approving, rotating and revoking keys. This chapter covers application integration. Every command and displayed response was checked with real HTTP requests and isolated synthetic data. IDs, timestamps and ETags will differ on your installation; use values returned by your own requests. Secrets are replaced with `[REDACTED]`.

## Preparation

Set your installation address, including its subdirectory, without a trailing slash. Keep the key out of source code and public repositories.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

The commands use `curl --globoff` so brackets in parameters such as `page[limit]` are not interpreted as URL patterns. `jq` only selects readable excerpts from large responses; Simbioza does not require it. Send the key in `Authorization: Bearer`, never in the URL or a browser cookie.

## Request bodies and responses

Writes use `Content-Type: application/json` and a top-level object with named fields. `{}` is a valid object, although an operation may require specific fields. `[]`, scalars and numeric top-level field names are invalid; nested lists remain supported. An example may omit a body when the operation does not read it. Survey mutations require JSON and their version information.

Successful JSON responses use `data`, `meta` and `links`. `meta.request_id` identifies a request in logs. A 204 response has no JSON body. OpenAPI returns the document directly; attachments, CSV, ICS and ZIP exports return files. Check `Content-Type` before decoding JSON.

## Pagination

Many lists use `page[limit]` (default 50, maximum 100) and an opaque `page[after]`. Follow `links.next` until it is `null`; do not construct or modify cursors. An invalid cursor may return the first page. Pagination is resource-specific: Survey uses `page/per_page`, search also uses `page/per_page`, and workspace summaries use `page/limit`. Personal follows return a bounded list without the cursor contract.

## Concurrent updates

For routes marked `If-Match`, first GET the current resource or draft and read its HTTP `ETag` header. Send the exact value, including quotes, in the next write's `If-Match` header. Examples call that variable `ETAG`. Do not use another language's ETag, or a published page's ETag when updating a draft. By default, a missing precondition returns 428 and a stale one412. HTML drafts additionally use `draft_revision`. Survey uses its own `version` and `revision` instead of ETags.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

This demonstrates saving response headers. Obtain `ETAG` from the appropriate resource GET, not from `/me`.

## Repeated writes and limits

JSON writes may use a unique `Idempotency-Key`. The same key and request replay the previous response; reusing the key with a different body returns 409. Multipart and binary uploads do not support this header and return422. The default is 120 requests per minute per key, with `X-RateLimit-*` headers and `Retry-After` on 429; administrators may configure another limit. The default maximum JSON body is 2MiB, and individual modules enforce additional limits. CORS is separate administrator policy and does not replace Bearer authentication.

Read OpenAPI with a valid key. It lists registered routes and scopes from active modules, but its generic schemas do not fully describe business parameters, multipart fields or binary responses. Use the resource chapters as well. CalDAV and public ICS links are separate protocols outside that document.

## Operations and verified examples

### `GET /api/v1`

**Purpose:** Returns the version, available resources, and enabled-module scopes.

**Permission:** a valid API key. Active API key and permitted destination policy; non-administrators are limited to subscriptions created by this key, while administrators may manage all.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

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

**Errors verified for this operation:** 401. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/me`

**Purpose:** Returns identity, scopes, and public user data without password fields.

**Permission:** a valid API key. Active API key and permitted destination policy; non-administrators are limited to subscriptions created by this key, while administrators may manage all.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 429. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/openapi.json`

**Purpose:** Returns OpenAPI 3.1 JSON for currently enabled modules.

**Permission:** a valid API key. Active API key and permitted destination policy; non-administrators are limited to subscriptions created by this key, while administrators may manage all.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

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

**Errors verified for this operation:** 401. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

## Errors and diagnostics

Check the HTTP status before reading `data`. Rejected requests return `application/problem+json`, with `status`, `code`, `detail` and `request_id`. A404 for private content does not confirm whether the resource exists. Do not disable permission checks or web-form CSRF protection to work around an API error. Bearer authentication is separate from the web session.

| HTTP | Meaning |
|---|---|
| 400 | invalid JSON or request |
| 401 | missing, invalid or disallowed key |
| 403 | missing scope or current permission |
| 404 | resource is unavailable to this user or missing |
| 409 | version, state or idempotency conflict |
| 412 | stale If-Match |
| 415 | unsupported nonempty body content type |
| 422 | invalid business fields or unsupported upload |
| 428 | required If-Match missing |
| 429 | rate limit reached; follow Retry-After |

### Without authentication

Actual response HTTP 401:

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

### Missing update precondition

Actual response HTTP 428:

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

### Same key, different body

Actual response HTTP 409:

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

### Rate limit reached

Actual response HTTP 429:

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

## PHP — a complete small client

This is a separate PHP example; you do not need to translate curl commands into code. Save the function as `api-client.php`. The PHP cURL extension is required. Production URLs must use HTTPS; HTTP is allowed only for loopback local development. The function supports JSON, multipart and binary responses, does not follow redirects, and throws on HTTP errors. Read secrets from your secret store or environment.

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

Usage (first set the environment variables from Preparation):

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Actual output on the synthetic administrator account:

```text
Administrator
```

Pass named fields as the fifth argument for a JSON write; use `[]` for an empty object (the helper encodes it as `{}`). Add `['If-Match: ' . $etag]` as the sixth argument for ETags. Multipart is the seventh argument, for example `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, with the fifth argument `null`. Do not set multipart Content-Type manually. Save files with `file_put_contents()` using `body`; decoded JSON is in `json`. Resource paths, payloads and permissions are the same as the curl examples in the other chapters.
