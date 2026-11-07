# API: primeros pasos y reglas comunes

<a id="api--getting-started-and-shared-rules"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

El API conecta Simbioza a otros sistemas. Un administrador primero instala y habilita el módulo API y luego emite una clave a un usuario activo real. Una clave tiene alcances seleccionados, caducidad opcional y restricciones de IP. Los permisos de usuario actuales aún se aplican: un ámbito administrativo no convierte a un usuario normal en administrador.

La [guía del administrador](admin-api_es.md) cubre la emisión, aprobación, rotación y revocación de claves. Este capítulo cubre la integración de aplicaciones. Cada comando y respuesta mostrada se verificó con solicitudes HTTP reales y datos sintéticos aislados. Los ID, marcas de tiempo y ETags diferirán en su instalación; Utilice los valores devueltos por sus propias solicitudes. Los secretos se reemplazan por `[REDACTED]`.

<a id="preparation"></a>

## Preparación

Configure su dirección de instalación, incluido su subdirectorio, sin una barra diagonal. Mantenga la clave fuera del código fuente y de los repositorios públicos.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

Los comandos usan `curl --globoff`, por lo que los corchetes en parámetros como `page[limit]` no se interpretan como patrones de URL. `jq` solo selecciona extractos legibles de respuestas grandes; Simbioza no lo requiere. Envía la clave en `Authorization: Bearer`, nunca en la URL ni en una cookie del navegador.

<a id="request-bodies-and-responses"></a>

## Solicitud de cuerpos y respuestas

Las escrituras utilizan `Content-Type: application/json` y un objeto de nivel superior con campos con nombre. `{}` es un objeto válido, aunque una operación puede requerir campos específicos. `[]`, los escalares y los nombres de campos numéricos de nivel superior no son válidos; Las listas anidadas siguen siendo compatibles. Un ejemplo puede omitir un cuerpo cuando la operación no lo lee. Las mutaciones de la encuesta requieren JSON y su información de versión.

Las respuestas JSON exitosas utilizan `data`, `meta` y `links`. `meta.request_id` identifica una solicitud en los registros. Una respuesta 204 no tiene cuerpo JSON. OpenAPI devuelve el documento directamente; archivos adjuntos, CSV, ICS y ZIP exportan archivos de devolución. Verifique `Content-Type` antes de decodificar JSON.

<a id="pagination"></a>

## Paginación

Muchas listas usan `page[limit]` (predeterminado 50, máximo 100) y un `page[after]` opaco. Siga `links.next` hasta que sea `null`; no construya ni modifique cursores. Un cursor no válido puede devolver la primera página. La paginación es específica de los recursos: la encuesta usa `page/per_page`, la búsqueda también usa `page/per_page` y los resúmenes del espacio de trabajo usan `page/limit`. Los seguimientos personales devuelven una lista acotada sin el contrato del cursor.

<a id="concurrent-updates"></a>

## Actualizaciones simultáneas

Para las rutas marcadas `If-Match`, primero GET el recurso o borrador actual y lea su encabezado HTTP `ETag`. Envíe el valor exacto, incluidas las comillas, en el encabezado `If-Match` de la siguiente escritura. Los ejemplos llaman a esa variable `ETAG`. No utilice el ETag de otro idioma ni el ETag de una página publicada al actualizar un borrador. De forma predeterminada, una condición previa faltante devuelve 428 y una obsoleta 412. Los borradores HTML también utilizan `draft_revision`. La encuesta utiliza sus propios `version` y `revision` en lugar de ETags.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

Esto demuestra cómo guardar encabezados de respuesta. Obtenga `ETAG` del recurso apropiado GET, no de `/me`.

<a id="repeated-writes-and-limits"></a>

## Escrituras repetidas y límites

Las escrituras JSON pueden utilizar un `Idempotency-Key` único. La misma clave y solicitud reproducen la respuesta anterior; reutilizar la clave con un cuerpo diferente devuelve 409. Las cargas binarias y de varias partes no admiten este encabezado y devuelven 422. El valor predeterminado es 120 solicitudes por minuto por clave, con encabezados `X-RateLimit-*` y `Retry-After` en 429; Los administradores pueden configurar otro límite. El cuerpo máximo predeterminado de JSON es 2MiB y los módulos individuales imponen límites adicionales. CORS es una política de administrador independiente y no reemplaza la autenticación Bearer.

Lea OpenAPI con una clave válida. Enumera rutas registradas y alcances de módulos activos, pero sus esquemas genéricos no describen completamente los parámetros comerciales, campos multiparte o respuestas binarias. Utilice también los capítulos de recursos. CalDAV y los enlaces ICS públicos son protocolos separados fuera de ese documento.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1`

**Propósito:** Devuelve la versión, los recursos disponibles y los alcances del módulo habilitado.

**Permiso:** una clave de API válida. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

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

**Errores verificados para esta operación:** 401. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/me`

**Propósito:** Devuelve identidad, alcances y datos de usuario públicos sin campos de contraseña.

**Permiso:** una clave de API válida. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 429. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/openapi.json`

**Propósito:** Devuelve OpenAPI 3.1 JSON para los módulos actualmente habilitados.

**Permiso:** una clave de API válida. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

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

**Errores verificados para esta operación:** 401. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

<a id="errors-and-diagnostics"></a>

## Errores y diagnósticos.

Verifique el estado de HTTP antes de leer `data`. Las solicitudes rechazadas devuelven `application/problem+json`, con `status`, `code`, `detail` y `request_id`. A404 para contenido privado no confirma si el recurso existe. No deshabilite las comprobaciones de permisos ni la protección del formulario web CSRF para solucionar un error API. La autenticación Bearer es independiente de la sesión web.

| HTTP | Significado |
|---|---|
| 400 | JSON inválido o solicitud |
| 401 | clave faltante, no válida o no permitida |
| 403 | alcance faltante o permiso actual |
| 404 | El recurso no está disponible para este usuario o falta. |
| 409 | conflicto de versión, estado o idempotencia |
| 412 | Si coincide obsoleto |
| 415 | tipo de contenido de cuerpo no vacío no admitido |
| 422 | campos comerciales no válidos o carga no admitida |
| 428 | Falta If-Match requerido |
| 429 | límite de tarifa alcanzado; seguir Reintentar después |

<a id="without-authentication"></a>

### Sin autenticación

Respuesta real HTTP 401:

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

### Falta condición previa de actualización

Respuesta real HTTP 428:

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

### Misma llave, diferente cuerpo

Respuesta real HTTP 409:

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

### Límite de tarifa alcanzado

Respuesta real HTTP 429:

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

## PHP — un pequeño cliente completo

Este es un ejemplo PHP separado; no es necesario traducir los comandos curl a código. Guarde la función como `api-client.php`. Se requiere la extensión cURL PHP. Las URL de producción deben utilizar HTTPS; HTTP solo se permite para el desarrollo local en la interfaz local (loopback). La función admite JSON, respuestas binarias y multiparte, no sigue redirecciones y genera errores HTTP. Lea secretos de su tienda o entorno secreto.

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

Uso (primero configure las variables de entorno desde Preparación):

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Salida real en la cuenta de administrador sintética:

```text
Administrator
```

Pase campos con nombre como quinto argumento para una escritura JSON; use `[]` para un objeto vacío (el ayudante lo codifica como `{}`). Agregue `['If-Match: ' . $etag]` como sexto argumento para ETags. Multiparte es el séptimo argumento, por ejemplo `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, con el quinto argumento `null`. No establezca manualmente el tipo de contenido multiparte. Guarde archivos con `file_put_contents()` usando `body`; decodificado JSON está en `json`. Las rutas de recursos, las cargas útiles y los permisos son los mismos que los ejemplos de curl en los otros capítulos.
