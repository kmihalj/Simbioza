# API: webhooks y entregas

<a id="api--webhooks-and-deliveries"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

Un webhook informa a otro sistema sobre un evento sin sondear repetidamente las listas. Un administrador debe habilitar webhooks y permitir hosts de destino. Se requiere `webhooks:read` o `webhooks:manage`; un no administrador administra las suscripciones creadas con la misma clave de API, mientras que un administrador con el alcance coincidente puede administrar todas las suscripciones.

POST requiere `name`, un HTTPS `target_url` permitido, una lista `events` (por ejemplo, `['*']`) y un booleano opcional `active`. El secreto de firma HMAC se devuelve sólo cuando se crea o se rota; guárdelo en el sistema receptor. Otras respuestas GET no lo exponen. PATCH, DELETE, el secreto de rotación y el reintento de entrega utilizan ETags.

Una mutación de contenido agrega una entrega en cola. GET `deliveries` muestra intentos, estado y respuestas de destino. `retry` reprograma una entrega existente; no significa que el sistema remoto lo haya recibido. Un trabajador programado por separado realiza la entrega y debe configurarse en el servidor. Estos ejemplos REST se verificaron con la cola sin enviarlos a una dirección externa.

La documentación técnica del módulo API describe las cargas útiles y las firmas de webhooks. El receptor debe verificar la firma HMAC-SHA256 con los bytes de solicitud originales y proteger contra la reproducción. La clave de API que administra una suscripción no es el secreto de firma del webhook.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/webhooks`

**Propósito:** Devuelve una lista paginada de suscripciones disponibles para la clave actual.

**Permiso:** `webhooks:read`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks?page[limit]=1"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/webhooks`

**Propósito:** Crea una suscripción y devuelve su nuevo secreto de firma solo en esta respuesta.

**Permiso:** `webhooks:manage`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `active`, `events`, `name`, `target_url`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 201**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/webhooks/{uuid}`

**Propósito:** Elimina permanentemente una suscripción con protección de simultaneidad optimista.

**Permiso:** `webhooks:manage`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/webhooks/{uuid}`

**Propósito:** Devuelve una suscripción permitida sin su secreto de firma.

**Permiso:** `webhooks:read`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 404. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PATCH /api/v1/webhooks/{uuid}`

**Propósito:** Actualiza una suscripción solo cuando el cliente envía el If-Match actual.

**Permiso:** `webhooks:manage`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `active`, `events`, `name`, `target_url`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 422. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `GET /api/v1/webhooks/{uuid}/deliveries`

**Propósito:** Devuelve el historial de entregas paginado para una suscripción.

**Permiso:** `webhooks:read`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page[limit]=1"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}`

**Propósito:** Devuelve un intento de entrega.

**Permiso:** `webhooks:read`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`, `deliveryUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry`

**Propósito:** Pone en cola una entrega existente después de verificar su ETag.

**Permiso:** `webhooks:manage`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`, `deliveryUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/webhooks/{uuid}/rotate-secret`

**Propósito:** Rota el secreto con If-Match y devuelve el nuevo valor solo una vez.

**Permiso:** `webhooks:manage`. Clave de API activa y política de destino permitida; los no administradores están limitados a las suscripciones creadas con esta clave, mientras que los administradores pueden administrar todas.

**Ruta:** `uuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

<a id="receiver--signatures-and-repeated-messages"></a>

## Receptor: firmas y mensajes repetidos

El servidor envía un POST con estos encabezados. Verifique la firma antes de la decodificación JSON; cambiar los espacios en blanco o volver a codificar JSON cambia la firma.

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

La tolerancia de 300 segundos es la política de este ejemplo. Conserve los valores `X-HeartPhrame-Webhook-Id` procesados ​​y no ejecute la misma acción comercial dos veces. Una firma válida por sí sola no impide la repetición.

Los selectores de eventos admiten un nombre exacto, un espacio de nombres con `.*` (por ejemplo, `pages.*`) o `*`. El cuerpo del webhook contiene la identidad del evento y metadatos depurados de los cambios; no envía la solicitud original ni el contenido completo del documento.

<a id="server-worker"></a>

## trabajador del servidor

Estos son comandos de administrador CLI en su servidor, separados de las solicitudes REST:

```sh
vendor/bin/hph api webhooks:status
vendor/bin/hph api webhooks:worker --batch-size=20
vendor/bin/hph api webhooks:worker --watch --sleep=5
```

`--watch` se ejecuta continuamente y debe supervisarse en producción. Evite entregas de pruebas manuales innecesarias. Para un evento fallido, inspeccione el historial de entrega y use REST `retry` después de solucionar la causa.
