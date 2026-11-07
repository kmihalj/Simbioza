# API: usuarios y grupos

<a id="api--users-and-groups"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

Estas operaciones requieren un propietario de clave de administrador y el alcance `users:*` o `groups:*` apropiado. Gestionan cuentas locales y grupos locales. Un usuario normal no puede leer el directorio administrativo simplemente por tener esos ámbitos.

Un nuevo usuario necesita `login_identifier` y una contraseña válida; un grupo necesita `name`. `attributes` contiene atributos de perfil. `display_name`, `first_name`, `last_name` y `email` también admiten entradas de nivel superior. Los mapas `provider_access` permitieron métodos de inicio de sesión (`local`, `saml`, `cas`, `oidc`, `oauth2`). `group_ids` es una lista de ID numéricos de grupos locales. La membresía PUT reemplaza la lista completa en lugar de agregar un grupo.

PATCH requiere al menos un campo modificado. Las actualizaciones de usuarios/grupos, el reemplazo y la eliminación de membresías necesitan el ETag actual. El usuario DELETE desactiva la cuenta y revoca sus claves API; no borra todos los datos comerciales relacionados. El sistema y las cuentas/grupos administrados externamente tienen restricciones adicionales. Reemplace `[REDACTED]` en el ejemplo con su propia contraseña segura.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/groups`

**Propósito:** Devuelve la lista de grupos locales.

**Permiso:** `groups:read`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups?page[limit]=1"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "key": "administrator",
      "name": "Administrator",
      "is_system": true,
      "is_enabled": true,
      "sort_order": 10
    }
  ],
  "meta": {
    "request_id": "ffc6243b21b6ad990f3e9fd642cb88d9",
    "page": {
      "limit": 1,
      "total": 5,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/groups?page%5Blimit%5D=1",
    "next": "/api/v1/groups?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/groups`

**Propósito:** Crea un grupo local que no pertenece al sistema.

**Permiso:** `groups:manage`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `is_enabled`, `name`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API urednici / API editors",
  "is_enabled": true
}' \
  "$BASE_URL/api/v1/groups"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": 5,
    "key": "api_urednici_api_editors",
    "name": "API urednici / API editors",
    "is_system": false,
    "is_enabled": true,
    "sort_order": 50
  },
  "meta": {
    "request_id": "4234d5238d039d2641b6e983b4dc91f5"
  },
  "links": {
    "self": "/api/v1/groups/5"
  }
}
```

**Errores verificados para esta operación:** 400, 401, 403, 409, 415. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `DELETE /api/v1/groups/{groupId}`

**Propósito:** Elimina un grupo local que no pertenece al sistema y sus membresías.

**Permiso:** `groups:manage`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `groupId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/groups/5"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/groups/{groupId}`

**Propósito:** Devuelve un grupo local.

**Permiso:** `groups:read`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `groupId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups/5"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 5,
    "key": "api_urednici_api_editors",
    "name": "API urednici / API editors",
    "is_system": false,
    "is_enabled": true,
    "sort_order": 50
  },
  "meta": {
    "request_id": "3d2d9c22881a73fd170a603ec1b9a6cd"
  },
  "links": {
    "self": "/api/v1/groups/5"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/groups/{groupId}`

**Propósito:** Actualiza un grupo local que no pertenece al sistema.

**Permiso:** `groups:manage`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `groupId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `is_enabled`, `name`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "name": "API suradnici"
}' \
  "$BASE_URL/api/v1/groups/5"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 5,
    "key": "api_urednici_api_editors",
    "name": "API suradnici",
    "is_system": false,
    "is_enabled": true,
    "sort_order": 50
  },
  "meta": {
    "request_id": "79038fa2daf0fba9720e54dbde347e84"
  },
  "links": {
    "self": "/api/v1/groups/5"
  }
}
```

**Errores verificados para esta operación:** 400, 401, 403, 412, 422, 428. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `GET /api/v1/users`

**Propósito:** Devuelve una lista paginada por el cursor de usuarios locales.

**Permiso:** `users:read`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users?page[limit]=1"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "login_identifier": "Admin",
      "display_name": "Administrator",
      "email": "admin@example.test",
      "email_aliases": [
        "admin@example.test"
      ],
      "attributes": {
        "display_name": "Administrator",
        "email": "admin@example.test",
        "first_name": "Admin",
        "last_name": "Simbioza"
      },
      "is_admin": true,
      "is_active": true,
      "auth_source": "local",
      "provider_access": {
        "local": true,
        "saml": false,
        "cas": false,
        "oidc": false,
        "oauth2": false
      },
      "groups": [
        {
          "id": 1,
          "key": "administrator",
          "name": "Administrator",
          "is_system": true,
          "is_enabled": true,
          "sort_order": 10
        }
      ],
      "last_login_at": "2026-11-06 22:35:53",
      "must_change_password": "[REDACTED]"
    }
  ],
  "meta": {
    "request_id": "eb06b96c165d9a118b0e0973f3654f96",
    "page": {
      "limit": 1,
      "has_more": true,
      "next_cursor": "aWQ6MQ"
    }
  },
  "links": {
    "self": "/api/v1/users?page%5Blimit%5D=1",
    "next": "/api/v1/users?page[limit]=1&page[after]=aWQ6MQ"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/users`

**Propósito:** Crea un usuario local.

**Permiso:** `users:create`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "login_identifier": "api.example@example.invalid",
  "password": "[REDACTED]",
  "is_active": true,
  "is_admin": false,
  "provider_access": {
    "local": true
  },
  "attributes": {
    "display_name": "Ogledni API korisnik",
    "email": "api.example@example.invalid"
  }
}' \
  "$BASE_URL/api/v1/users"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": 4,
    "login_identifier": "api.example@example.invalid",
    "display_name": "Ogledni API korisnik",
    "email": "api.example@example.invalid",
    "email_aliases": [
      "api.example@example.invalid"
    ],
    "attributes": {
      "email": "api.example@example.invalid",
      "display_name": "Ogledni API korisnik"
    },
    "is_admin": false,
    "is_active": true,
    "auth_source": "local",
    "provider_access": {
      "local": true,
      "saml": false,
      "cas": false,
      "oidc": false,
      "oauth2": false
    },
    "groups": [],
    "last_login_at": null,
    "must_change_password": "[REDACTED]"
  },
  "meta": {
    "request_id": "ee8bf7773217b84d00d9b262e56d9f9b"
  },
  "links": {
    "self": "/api/v1/users/4"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/users/{userId}`

**Propósito:** Desactiva un usuario local y revoca sus claves API.

**Permiso:** `users:delete`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `userId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/users/4"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/users/{userId}`

**Propósito:** Devuelve un usuario local.

**Permiso:** `users:read`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `userId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users/4"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "login_identifier": "api.example@example.invalid",
    "display_name": "Ogledni API korisnik",
    "email": "api.example@example.invalid",
    "email_aliases": [
      "api.example@example.invalid"
    ],
    "attributes": {
      "display_name": "Ogledni API korisnik",
      "email": "api.example@example.invalid"
    },
    "is_admin": false,
    "is_active": true,
    "auth_source": "local",
    "provider_access": {
      "local": true,
      "saml": false,
      "cas": false,
      "oidc": false,
      "oauth2": false
    },
    "groups": [],
    "last_login_at": null,
    "must_change_password": "[REDACTED]"
  },
  "meta": {
    "request_id": "ac38613b06ac5362c257dda6d691fad6"
  },
  "links": {
    "self": "/api/v1/users/4"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/users/{userId}`

**Propósito:** Actualiza parcialmente un usuario local.

**Permiso:** `users:update`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `userId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "attributes": {
    "display_name": "Ažurirani API korisnik"
  }
}' \
  "$BASE_URL/api/v1/users/4"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "login_identifier": "api.example@example.invalid",
    "display_name": "Ažurirani API korisnik",
    "email": "api.example@example.invalid",
    "email_aliases": [
      "api.example@example.invalid"
    ],
    "attributes": {
      "display_name": "Ažurirani API korisnik",
      "email": "api.example@example.invalid"
    },
    "is_admin": false,
    "is_active": true,
    "auth_source": "local",
    "provider_access": {
      "local": true,
      "saml": false,
      "cas": false,
      "oidc": false,
      "oauth2": false
    },
    "groups": [
      {
        "id": 5,
        "key": "api_urednici_api_editors",
        "name": "API urednici / API editors",
        "is_system": false,
        "is_enabled": true,
        "sort_order": 50
      }
    ],
    "last_login_at": null,
    "must_change_password": "[REDACTED]"
  },
  "meta": {
    "request_id": "92b0f70bd59be10cf076a4de0ffd1589"
  },
  "links": {
    "self": "/api/v1/users/4"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/users/{userId}/groups`

**Propósito:** Reemplaza las membresías del manual de usuario local.

**Permiso:** `groups:manage`. Propietario de la clave de administrador; el alcance por sí solo es insuficiente.

**Ruta:** `userId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `group_ids`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "group_ids": [
    5
  ]
}' \
  "$BASE_URL/api/v1/users/4/groups"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "login_identifier": "api.example@example.invalid",
    "display_name": "Ogledni API korisnik",
    "email": "api.example@example.invalid",
    "email_aliases": [
      "api.example@example.invalid"
    ],
    "attributes": {
      "display_name": "Ogledni API korisnik",
      "email": "api.example@example.invalid"
    },
    "is_admin": false,
    "is_active": true,
    "auth_source": "local",
    "provider_access": {
      "local": true,
      "saml": false,
      "cas": false,
      "oidc": false,
      "oauth2": false
    },
    "groups": [
      {
        "id": 5,
        "key": "api_urednici_api_editors",
        "name": "API urednici / API editors",
        "is_system": false,
        "is_enabled": true,
        "sort_order": 50
      }
    ],
    "last_login_at": null,
    "must_change_password": "[REDACTED]"
  },
  "meta": {
    "request_id": "bde44da0c5418fd4da77d60d991c2288"
  },
  "links": {
    "self": "/api/v1/users/4/groups"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.
