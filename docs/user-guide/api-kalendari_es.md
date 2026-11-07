# API: calendarios y eventos

<a id="api--calendars-and-events"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

`calendar:read` lee calendarios accesibles al propietario de la clave. `calendar:write` complementa los permisos actuales de creación, escritura y administración. Un calendario necesita `name`; `type`/`calendar_type` puede ser `personal`, `team` o `resource`, sujeto a reglas de propiedad y administración.

Los calendarios PATCH y DELETE requieren un ETag. ACL PUT acepta la lista completa de `rules`: `subject_type` (`user`/`group`), numérica `subject_id`, `can_read`, `can_write`. Reemplaza las reglas directas, así que lea el estado actual antes de escribir.

Un evento necesita `title` y valores de inicio/fin válidos (`starts_at`, `ends_at`), con `timezone`. `is_all_day` marca eventos de todo el día. Las variantes de fecha/hora y los alias se enumeran por operación. La recurrencia utiliza `recurrence_rule` o campos de frecuencia/fin separados. El rango de eventos GET requiere `from` y `to`; `expand_recurring=false` lee definiciones, mientras que la expansión devuelve ocurrencias. Los ID de eventos son numéricos, mientras que las rutas del calendario usan UUID.

La importación de ICS envíe el texto en `ics`, numérico opcional `calendar_id` y `merge_mode=skip` o `update`. Crear un calendario en lugar de importarlo a uno existente está sujeto a los permisos y configuraciones actuales. La exportación de ICS descarga el calendario; esa ruta no tiene filtros `from/to`. Utilice la lista de eventos para filtrar por rango de fechas.

Este REST API no tiene operaciones separadas de invitación, respuesta de asistente ni aprobación de reserva. Agregar dichos campos a un evento normal POST no invoca el flujo de trabajo de la reunión. [Los protocolos CalDAV y ICS](api-granice-protokola_es.md) están separados del REST del portador API.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/calendars`

**Propósito:** Devuelve calendarios visibles para el propietario de la clave de API.

**Permiso:** `calendar:read`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars?page[limit]=1"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/calendars`

**Propósito:** Crea un nuevo calendario con comprobaciones de permisos de dominio.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 201**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/calendars/import`

**Propósito:** Importa texto de iCalendar a un calendario nuevo o existente.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `calendar_id`, `calendar_type`, `color`, `description`, `ics`, `is_authenticated_read`, `is_public_read`, `merge_mode`, `name`, `public_order`, `show_on_public_index`, `show_public_link`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/calendars/{calendarUuid}`

**Propósito:** Elimina un calendario cuando su dominio ACL lo permite.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/calendars/{calendarUuid}`

**Propósito:** Obtiene un calendario por su público UUID.

**Permiso:** `calendar:read`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 404. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PATCH /api/v1/calendars/{calendarUuid}`

**Propósito:** Actualiza parcialmente un calendario.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 400, 401, 403, 412, 428. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PUT /api/v1/calendars/{calendarUuid}/acl`

**Propósito:** Reemplaza todas las reglas de usuario/grupo ACL de un calendario.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `rules`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/calendars/{calendarUuid}/events`

**Propósito:** Devuelve eventos del calendario dentro del rango solicitado.

**Permiso:** `calendar:read`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `expand_recurring`, `from`, `page[after]`, `page[limit]`, `to`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/calendars/{calendarUuid}/events`

**Propósito:** Crea un evento en un calendario.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 201**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Propósito:** Elimina un evento de un calendario.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`, `eventId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Propósito:** Obtiene un evento del calendario nombrado en la URL.

**Permiso:** `calendar:read`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`, `eventId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Propósito:** Actualiza parcialmente un evento.

**Permiso:** `calendar:write`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`, `eventId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/calendars/{calendarUuid}/export.ics`

**Propósito:** Descarga un calendario legible como un archivo ICS estándar.

**Permiso:** `calendar:read`. Propiedad actual del calendario, permisos de lectura/escritura/administración y ACL de usuario/grupo.

**Ruta:** `calendarUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.ics \
  "$BASE_URL/api/v1/calendars/e8eaf356-1af4-4a05-8943-263752ef910b/export.ics"
```

Respuesta real: **HTTP 200**.

```text
content-type: text/calendar; charset=utf-8
content-disposition: attachment; filename="administrator-personal-calendar.ics"
content-length: 189
```

El archivo se guarda como `response.*`. El contenido verificado tiene 189 bytes; no lo decodifique como JSON.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.
