# API: espacios de trabajo, árboles, permisos y temas

<a id="api--workspaces-trees-permissions-and-themes"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

`workspace:read` lee árboles y espacios de trabajo accesibles. `workspace:manage` solo los administra cuando el propietario tiene los permisos actuales requeridos. La configuración global de la página de inicio, las listas de espacios de trabajo eliminados y la restauración requieren un administrador. La eliminación del espacio de trabajo es recuperable.

Cree un espacio de trabajo con `name`; suministre `slug` o deje que el sistema lo derive. `visibility` es `public`, `authenticated` o `restricted`. Los mapas de traducción son `name_translations`/`description_translations`; `primary_language` y `supported_languages` deben coincidir con los idiomas instalados. `tree_visibility` y `contents_visibility` controlan la presentación del contenido/árbol, sin omitir las ACL.

ACL PUT **reemplaza todas las asignaciones directas**. `subjects` es una lista de objetos `{type,id,permissions}`. El espacio de trabajo `type` admite `user` o `group`; los permisos son `can_view`, `can_add`, `can_edit`, `can_publish`, `can_delete`, `can_manage`. Los derechos heredados/del sistema no se eliminan simplemente omitiéndolos. La restricción de nodo individual API actualmente acepta solo usuarios ya asignados al espacio de trabajo; no es un API completo para cada excepción de acceso admitida por la interfaz web.

POST `nodes` crea solo nodos `internal_link` o `external_link`. Cree documentos HTML con la [página API](api-html-stranice_es.md). Para reordenar se necesita la lista completa `placements`, cada una con `id`, `parent_id` y `sort_order`. Cambiar los metadatos del nodo no edita su documento HTML.

`shorts` utiliza `page`, `limit`, `depth`, `order`; `limit=all` está disponible solo por debajo de 100 entradas visibles. No utiliza `per_page`. Los puntos finales del tema administran un **tema privado del espacio de trabajo**, no el tema global del sitio. Sube una imagen en multiparte `asset` con `role`; tema ZIP importación utiliza `theme`. La carga de imágenes devuelve el modelo de tema completo y su lista `assets`, ni un solo objeto de imagen. Busque el `file` agregado allí. Sólo se pueden eliminar las imágenes no utilizadas. La exportación de temas requiere un administrador.

La exportación HTML devuelve un ZIP de páginas publicadas accesibles, opcionalmente reducido por `node_ids`. No recrea encuestas funcionales. No es una copia de seguridad de las respuestas, el historial y la configuración.

La configuración de la página de inicio usa `public_target`/`authenticated_target`: `default`, `page:<nodeId>` o `shorts:<workspaceId>`, además de indicadores de presentación. Personal PUT acepta `selection` como ID de página (0 restaura el valor predeterminado) o como objeto de destino, solo cuando el administrador habilita la selección personal.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/workspaces`

**Propósito:** Devuelve espacios de trabajo visibles para el propietario de la clave de API.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces?page[limit]=1"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "uuid": "4cd08aa0-7377-400e-a330-469da66b60f6",
      "slug": "editing-guide",
      "name": "Editing guide",
      "name_translations": {
        "en": "Editing guide"
      },
      "description": "Example pages for collaborative work.",
      "description_translations": {
        "en": "Example pages for collaborative work."
      },
      "visibility": "public",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": false,
      "created_at": "2026-11-06 22:35:54",
      "updated_at": "2026-11-06 22:35:54",
      "deleted_at": "",
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "613cd338ec6a74ef447a39df54e39cea",
    "page": {
      "limit": 1,
      "total": 4,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/workspaces?page%5Blimit%5D=1",
    "next": "/api/v1/workspaces?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces`

**Propósito:** Crea un nuevo Espacio de Trabajo sujeto a la política de creación de la aplicación.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "Ogledno API područje",
  "slug": "api-ogledno-podrucje",
  "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
  "visibility": "restricted"
}' \
  "$BASE_URL/api/v1/workspaces"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "40109227f06c007cdfd19c562fd53308"
  },
  "links": {
    "self": "/api/v1/workspaces"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/deleted`

**Propósito:** Devuelve la lista solo para administradores de espacios de trabajo eliminados temporalmente.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/deleted?page[limit]=100"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 4,
      "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
      "slug": "api-ogledno-podrucje",
      "name": "Ogledno API područje",
      "name_translations": {
        "en": "Ogledno API područje"
      },
      "description": "Ažurirano preko API-ja.",
      "description_translations": {
        "en": "Ažurirano preko API-ja."
      },
      "visibility": "restricted",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": true,
      "created_at": "2026-11-06 22:44:20",
      "updated_at": "2026-11-06 23:20:32",
      "deleted_at": "2026-11-06 23:20:32",
      "permissions": []
    }
  ],
  "meta": {
    "request_id": "337679c1372f56ec4cc2e5510beb8f0b",
    "page": {
      "limit": 100,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/workspaces/deleted?page%5Blimit%5D=100",
    "next": null
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces/deleted/{workspaceId}/restore`

**Propósito:** Restaura un espacio de trabajo eliminado temporalmente bajo un slug disponible.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `slug`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "slug": "api-ogledno-podrucje"
}' \
  "$BASE_URL/api/v1/workspaces/deleted/4/restore"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 23:20:32",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "22962d6ee7bb686754b06152ff10ba62"
  },
  "links": {
    "self": "/api/v1/workspaces/deleted/4/restore"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/homepage/preference`

**Propósito:** Devuelve la página de inicio personal del propietario de la clave de API cuando la selección está habilitada.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "efe455081ae8c6a2599cf235290fa053"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/homepage/preference`

**Propósito:** Almacena la página de inicio personal del propietario de la clave de API después de una nueva verificación ACL.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `selection`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "selection": 0
}' \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "c0575c57cbeb8fb18fdd07daa34ad587"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/homepage/settings`

**Propósito:** Devuelve la política de página de inicio pública y autenticada solo para administradores.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "0bb93172f325cd23a0fd269bbf59e7f5"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/homepage/settings`

**Propósito:** Almacena la política de página de inicio pública y autenticada solo para administradores.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `allow_user_selection`, `authenticated_show_display_options`, `authenticated_show_tree`, `authenticated_target`, `public_show_display_options`, `public_show_tree`, `public_target`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "public_target": "default",
  "authenticated_target": "default",
  "public_show_tree": true,
  "public_show_display_options": true,
  "authenticated_show_tree": true,
  "authenticated_show_display_options": true,
  "allow_user_selection": true
}' \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "4e069414636ec0f7a9ae9b197dd191e1"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/workspaces/{workspaceSlug}`

**Propósito:** Elimina temporalmente un espacio de trabajo con el permiso efectivo can_manage.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}`

**Propósito:** Devuelve un espacio de trabajo cuando el propietario de la clave puede verlo.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "e0e85fd92c7a001921444f4175228ec0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errores verificados para esta operación:** 401, 403, 404. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PATCH /api/v1/workspaces/{workspaceSlug}`

**Propósito:** Actualiza parcialmente un espacio de trabajo con el permiso can_manage efectivo.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano preko API-ja."
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "135a815aac013227e2c0dad8cb3a7e86"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errores verificados para esta operación:** 400, 401, 403, 412, 428. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/acl`

**Propósito:** Devuelve el espacio de trabajo directo ACL a un usuario que puede administrarlo.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 1,
      "label": "Administrator",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "b6aa7159a0807a86e4ecec28837632c8"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/{workspaceSlug}/acl`

**Propósito:** Reemplaza el espacio de trabajo completo ACL.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `subjects`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "c7bc6b2bc47f17e6da17a6f25bba9d5b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/acl/subjects`

**Propósito:** Busca usuarios o grupos para el selector del espacio de trabajo ACL.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `category`, `q`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "label": "Ana Author",
      "type": "user",
      "category": "user",
      "is_builtin": false,
      "is_read_only": false
    }
  ],
  "meta": {
    "request_id": "a00b8b79c556ffb217e854bb4df6781f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces/{workspaceSlug}/exports/html`

**Propósito:** Descarga un ACL sin conexión filtrado HTML ZIP de un espacio de trabajo o páginas seleccionadas.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `node_ids`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_ids": [
    36
  ]
}' \
  --dump-header response-headers.txt \
  --output response.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/exports/html"
```

Respuesta real: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="simbioza-api-ogledno-podrucje-20261106-231026.zip"
content-length: 117723
```

El archivo se guarda como `response.*`. El contenido verificado tiene 117723 bytes; no lo decodifique como JSON.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces/{workspaceSlug}/nodes`

**Propósito:** Agrega un enlace interno o externo al árbol.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `contents_visibility`, `is_homepage`, `node_type`, `parent_id`, `primary_language`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_type": "internal_link",
  "title": "api-internal",
  "slug": "api-internal",
  "target_url": "/about",
  "sort_order": 200
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "api-internal",
    "title_translations": {
      "en": "api-internal"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "b75c597c0414ff8e3cf18d976e9f03a9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Propósito:** Elimina un nodo de enlace; Los documentos siguen siendo responsabilidad del editor API.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`, `nodeId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Propósito:** Actualiza los datos estructurales de un nodo del árbol.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`, `nodeId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `contents_visibility`, `is_homepage`, `labels`, `node_type`, `parent_id`, `primary_language`, `properties`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API poveznica"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "API poveznica",
    "title_translations": {
      "en": "API poveznica"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "89fef7d6bff70c6a19e5dc398783d33f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Propósito:** Devuelve restricciones directas ACL para un nodo.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`, `nodeId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [],
  "meta": {
    "request_id": "a376426ba7d7e2cdbd47d084edcd9e9e"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Propósito:** Reemplaza las restricciones directas ACL para un nodo.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`, `nodeId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `subjects`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": false,
        "can_edit": false,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "3991706d28cfe0d2ce50650e27891ff0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/shorts`

**Propósito:** Devuelve resúmenes filtrados por ACL de las páginas del espacio de trabajo publicadas.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`, `page`, `limit`, `depth`, `order`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "tree": [
      {
        "id": 40,
        "uuid": "f85dfce8-f123-4dd9-969a-f29b068dae06",
        "workspace_id": 4,
        "parent_id": null,
        "node_type": "document",
        "slug": "api-vodic-za-integracije",
        "title": "Vodič za integracije",
        "title_translations": "{\"en\":\"Vodič za integracije\",\"hr\":\"Vodič za integracije\"}",
        "document_key": "api-vodic-za-integracije",
        "route_name": null,
        "target_url": null,
        "sort_order": 100,
        "is_homepage": 1,
        "is_tree_hidden": 0,
        "is_enabled": 1,
        "contents_visibility": "inherit",
        "created_by_user_id": 1,
        "updated_by_user_id": 1,
        "created_at": "2026-11-06 23:24:07",
        "updated_at": "2026-11-06 23:24:07",
        "is_tree_hidden_effective": false,
        "is_tree_temporarily_visible": false,
        "permissions": {
          "can_view": true,
          "can_add": true,
          "can_edit": true,
          "can_publish": true,
          "can_delete": true,
          "can_manage": true
        },
        "children": [],
        "title_translations_map": {
          "en": "Vodič za integracije",
          "hr": "Vodič za integracije"
        },
        "href": "/workspace/api-ogledno-podrucje/api-vodic-za-integracije?lang=hr"
      }
    ],
    "articles": [],
    "depth": 2,
    "limit": "10",
    "order": "newest",
    "total": 1,
    "all_available": true,
    "pagination": {
      "page": 1,
      "pages": 1,
      "total": 1,
      "from": 1,
      "to": 1,
      "page_numbers": [
        1
      ]
    },
    "shorts_path": "/workspace/api-ogledno-podrucje/shorts?lang=hr",
    "workspace_path": "/workspace/api-ogledno-podrucje?lang=hr"
  },
  "meta": {
    "request_id": "59be13951d906db537a449e60e0c472c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/theme`

**Propósito:** Devuelve la configuración del tema del espacio de trabajo, las opciones del sistema y los archivos privados.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "__default__",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "simbioza",
      "label": {
        "hr": "Simbioza",
        "en": "Simbioza"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-botanical-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.svg",
        "role": "icon",
        "used": false
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "d2a84727c1eea4b947872cc8fb0452d9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/workspaces/{workspaceSlug}/theme`

**Propósito:** Almacena una configuración de tema privada solo para el espacio de trabajo proporcionado.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `mode_policy`, `theme`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme": {
    "id": "aai",
    "label": {
      "hr": "AAI",
      "en": "AAI"
    },
    "font_family": "system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif",
    "menu_font_family": "system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif",
    "menu_font_size": "1rem",
    "menu_font_weight": "500",
    "menu_font_style": "normal",
    "system": false,
    "components": {
      "skip_link": true,
      "header": {
        "enabled": true,
        "sticky": true,
        "container": "fluid",
        "height_px": 72,
        "items": [
          {
            "id": "aai-logo",
            "type": "logo",
            "position": "start",
            "text": [],
            "href": "https://www.aaiedu.hr/",
            "src_light": "@theme-assets/aai/logo-aaieduhr-light.svg",
            "src_dark": "@theme-assets/aai/logo-aaieduhr-dark.svg",
            "alt": {
              "hr": "AAI@EduHr",
              "en": "AAI@EduHr"
            },
            "height": 50,
            "new_tab": true
          },
          {
            "id": "language",
            "type": "language",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "account",
            "type": "account",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "srce-logo",
            "type": "logo",
            "position": "end",
            "text": [],
            "href": "https://www.srce.unizg.hr/",
            "src_light": "@theme-assets/aai/logo-srce-light.svg",
            "src_dark": "@theme-assets/aai/logo-srce-dark.svg",
            "alt": {
              "hr": "Sveučilišni računski centar Srce",
              "en": "University Computing Centre Srce"
            },
            "height": 50,
            "new_tab": true
          }
        ]
      },
      "navigation": {
        "text_shadow": "none",
        "placement": "standalone",
        "container": "fluid",
        "height_px": 56,
        "breakpoint": "lg",
        "tabs_style": "standard",
        "tabs_accent_enabled": true,
        "tabs_radius": "small",
        "tabs_active_border_width_px": 2,
        "show_brand": false,
        "brand_display": "none",
        "show_mobile_label": false,
        "mobile_label": {
          "hr": "Izbornik",
          "en": "Menu"
        },
        "sticky": true,
        "show_icons": false,
        "mark_external_links": true
      },
      "hero": {
        "title_text_shadow": "none",
        "supporting_text_shadow": "none",
        "home_enabled": true,
        "inner_enabled": true,
        "container": "fluid",
        "home_size": "medium",
        "inner_size": "medium",
        "alignment": "start",
        "content_min_height_px": 0,
        "content_padding_top_px": 0,
        "content_padding_bottom_px": 0,
        "visual_enabled": true,
        "visual_src_light": "@theme-assets/aai/hero-aai-banner.svg",
        "visual_src_dark": "@theme-assets/aai/hero-aai-banner.svg",
        "visual_alt": [],
        "visual_decorative": true,
        "visual_allow_overflow": true,
        "visual_width_px": 650,
        "visual_max_height_px": 320,
        "visual_top_px": -48,
        "visual_right_px": 24,
        "visual_rotation_deg": 0,
        "wave": true
      },
      "content": {
        "container": "fluid",
        "inner_title_placement": "hero",
        "application_title_placement": "hero",
        "surface": "plain",
        "inner_hero_overlap": "large",
        "density": "comfortable",
        "title_accent": "none",
        "title_shadow": false,
        "content_accent": "top",
        "content_shadow": false,
        "sidebar_accent": "top",
        "sidebar_shadow": false
      },
      "cards": {
        "style": "quiet",
        "density": "comfortable",
        "radius": "medium"
      }
    },
    "light": {
      "colors": {
        "body_bg": "#F3F4F7",
        "body_text": "#212529",
        "muted_text": "#66737D",
        "surface_bg": "#FFFFFF",
        "surface_text": "#212529",
        "border": "#D8DCE3",
        "link": "#1094E3",
        "primary": "#0095DA",
        "secondary": "#003567",
        "success": "#198754",
        "warning": "#E39717",
        "danger": "#D51F36",
        "info": "#4EC0AD",
        "document_action_bg": "#FFFFFF",
        "document_action_text": "#006FA8",
        "document_action_border": "#DEE2E6",
        "document_action_hover_bg": "#0095DA",
        "document_action_hover_text": "#FFFFFF",
        "document_action_active_bg": "#003567",
        "document_action_active_text": "#FFFFFF",
        "primary_text": "auto",
        "secondary_text": "auto",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto",
        "card_bg": "#FFFFFF",
        "card_text": "#212529",
        "card_border": "#DEE2E6",
        "card_accent": "#0095DA",
        "card_shadow": "#000000",
        "title_card_bg": "#FFFFFF",
        "title_card_text": "#212529",
        "title_card_border": "#DEE2E6",
        "title_card_accent": "#0095DA",
        "content_card_bg": "#FFFFFF",
        "content_card_text": "#212529",
        "content_card_border": "#DEE2E6",
        "content_card_accent": "#0095DA",
        "sidebar_card_bg": "#FFFFFF",
        "sidebar_card_text": "#212529",
        "sidebar_card_border": "#DEE2E6",
        "sidebar_card_accent": "#4EC0AD",
        "title_supporting_text": "#6C757D",
        "hero_gradient_1": "#003567",
        "hero_gradient_2": "#084678",
        "hero_gradient_3": "#13588B",
        "hero_gradient_4": "#1F5EA0",
        "hero_gradient_5": "#1F8CA0",
        "hero_text": "#FFFFFF",
        "hero_title": "#FFFFFF",
        "hero_subtitle": "#E5F6FD",
        "hero_muted": "#BDEAFF",
        "table_header_bg": "#F8F9FA",
        "table_header_text": "#000000",
        "table_row_bg": "#FFFFFF",
        "table_row_text": "#212529",
        "table_row_alt_bg": "#F2F2F2",
        "table_border": "#DEE2E6",
        "alert_primary_bg": "#CFE2FF",
        "alert_primary_text": "#084298",
        "alert_primary_border": "#B6D4FE",
        "alert_success_bg": "#D1E7DD",
        "alert_success_text": "#0F5132",
        "alert_success_border": "#BADBCC",
        "alert_warning_bg": "#FFF3CD",
        "alert_warning_text": "#664D03",
        "alert_warning_border": "#FFECB5",
        "alert_danger_bg": "#F8D7DA",
        "alert_danger_text": "#842029",
        "alert_danger_border": "#F5C2C7",
        "alert_info_bg": "#CFF4FC",
        "alert_info_text": "#055160",
        "alert_info_border": "#B6EFFB",
        "badge_bg": "#0095DA",
        "badge_text": "#FFFFFF",
        "tag_bg": "#E5F6FD",
        "tag_text": "#003567",
        "nav_default_bg": "#003567",
        "nav_default_text": "#7AD4FD",
        "nav_border": "#4EC0AD",
        "nav_active_bg": "#1F5EA0",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#7AD4FD",
        "nav_hover_bg": "#174E7E",
        "left_menu_hover_bg": "#E5F6FD",
        "left_menu_hover_text": "#003567",
        "left_menu_active_bg": "#0095DA",
        "left_menu_active_text": "#FFFFFF",
        "special_left_menu_accent": "#D51F36",
        "pagination_bg": "#FFFFFF",
        "pagination_text": "#0079B8",
        "pagination_active_bg": "#0095DA",
        "pagination_active_text": "#FFFFFF",
        "breadcrumb_text": "#66737D",
        "breadcrumb_link": "#0079B8",
        "editor_tab_bg": "#F8F9FA",
        "editor_tab_text": "#495057",
        "editor_tab_border": "#DEE2E6",
        "editor_tab_hover_bg": "#E9ECEF",
        "editor_tab_active_bg": "#FFFFFF",
        "editor_tab_active_text": "#212529",
        "editor_tab_active_accent": "#0D6EFD",
        "editor_tab_panel_bg": "#FFFFFF",
        "editor_tab_panel_text": "#212529",
        "editor_accordion_header_bg": "#F8F9FA",
        "editor_accordion_header_text": "#212529",
        "editor_accordion_body_bg": "#FFFFFF",
        "editor_accordion_body_text": "#212529",
        "focus_ring": "#0095DA",
        "disabled_bg": "#E9ECEF",
        "disabled_text": "#6C757D",
        "valid": "#198754",
        "invalid": "#DC3545",
        "code_bg": "#F8F9FA",
        "code_text": "#D63384",
        "blockquote_border": "#0095DA",
        "callout_bg": "#EAF8FC",
        "callout_text": "#003567",
        "callout_border": "#8DD6E8",
        "navbar_gradient_1": "#003567",
        "navbar_gradient_2": "#084678",
        "navbar_gradient_3": "#13588B",
        "navbar_gradient_4": "#1F5EA0",
        "navbar_gradient_5": "#1F8CA0",
        "navbar_text": "#7AD4FD",
        "navbar_link": "#7AD4FD",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#003567"
      }
    },
    "dark": {
      "colors": {
        "body_bg": "#0C141C",
        "body_text": "#E8F2F7",
        "muted_text": "#A 8BBC6",
        "surface_bg": "#14212C",
        "surface_text": "#E8F2F7",
        "border": "#385064",
        "link": "#65C7F0",
        "primary": "#22A9E0",
        "secondary": "#4E8FC7",
        "success": "#198754",
        "warning": "#F0AD32",
        "danger": "#EF667A",
        "info": "#64D3C3",
        "document_action_bg": "#14212C",
        "document_action_text": "#65C7F0",
        "document_action_border": "#385064",
        "document_action_hover_bg": "#22A9E0",
        "document_action_hover_text": "#0C141C",
        "document_action_active_bg": "#64D3C3",
        "document_action_active_text": "#0C141C",
        "primary_text": "auto",
        "secondary_text": "auto",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto",
        "card_bg": "#182835",
        "card_text": "#E8F2F7",
        "card_border": "#385064",
        "card_accent": "#22A9E0",
        "card_shadow": "#000000",
        "title_card_bg": "#14212C",
        "title_card_text": "#E8F2F7",
        "title_card_border": "#385064",
        "title_card_accent": "#22A9E0",
        "content_card_bg": "#14212C",
        "content_card_text": "#E8F2F7",
        "content_card_border": "#385064",
        "content_card_accent": "#22A9E0",
        "sidebar_card_bg": "#182835",
        "sidebar_card_text": "#E8F2F7",
        "sidebar_card_border": "#385064",
        "sidebar_card_accent": "#64D3C3",
        "title_supporting_text": "#6C757D",
        "hero_gradient_1": "#001D38",
        "hero_gradient_2": "#062B4E",
        "hero_gradient_3": "#0D3A5F",
        "hero_gradient_4": "#15496F",
        "hero_gradient_5": "#155F70",
        "hero_text": "#FFFFFF",
        "hero_title": "#FFFFFF",
        "hero_subtitle": "#D9F4FF",
        "hero_muted": "#AED7E7",
        "table_header_bg": "#1D3444",
        "table_header_text": "#F5FBFE",
        "table_row_bg": "#14212C",
        "table_row_text": "#E8F2F7",
        "table_row_alt_bg": "#192B39",
        "table_border": "#385064",
        "alert_primary_bg": "#031633",
        "alert_primary_text": "#6EA8FE",
        "alert_primary_border": "#084298",
        "alert_success_bg": "#051B11",
        "alert_success_text": "#75B798",
        "alert_success_border": "#0F5132",
        "alert_warning_bg": "#332701",
        "alert_warning_text": "#FFDA6A",
        "alert_warning_border": "#664D03",
        "alert_danger_bg": "#2C0B0E",
        "alert_danger_text": "#EA868F",
        "alert_danger_border": "#842029",
        "alert_info_bg": "#032830",
        "alert_info_text": "#6EDFF6",
        "alert_info_border": "#055160",
        "badge_bg": "#22A9E0",
        "badge_text": "#08131B",
        "tag_bg": "#1D3444",
        "tag_text": "#D9F4FF",
        "nav_default_bg": "#001D38",
        "nav_default_text": "#92DAF7",
        "nav_border": "#2E708B",
        "nav_active_bg": "#15496F",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#65C7F0",
        "nav_hover_bg": "#0D3A5F",
        "left_menu_hover_bg": "#1D3444",
        "left_menu_hover_text": "#FFFFFF",
        "left_menu_active_bg": "#22A9E0",
        "left_menu_active_text": "#08131B",
        "special_left_menu_accent": "#EF667A",
        "pagination_bg": "#14212C",
        "pagination_text": "#65C7F0",
        "pagination_active_bg": "#22A9E0",
        "pagination_active_text": "#08131B",
        "breadcrumb_text": "#A 8BBC6",
        "breadcrumb_link": "#65C7F0",
        "editor_tab_bg": "#212529",
        "editor_tab_text": "#ADB5BD",
        "editor_tab_border": "#495057",
        "editor_tab_hover_bg": "#343A40",
        "editor_tab_active_bg": "#2B3035",
        "editor_tab_active_text": "#FFFFFF",
        "editor_tab_active_accent": "#6EA8FE",
        "editor_tab_panel_bg": "#2B3035",
        "editor_tab_panel_text": "#DEE2E6",
        "editor_accordion_header_bg": "#212529",
        "editor_accordion_header_text": "#DEE2E6",
        "editor_accordion_body_bg": "#2B3035",
        "editor_accordion_body_text": "#DEE2E6",
        "focus_ring": "#65C7F0",
        "disabled_bg": "#22323E",
        "disabled_text": "#879AA5",
        "valid": "#75B798",
        "invalid": "#EA868F",
        "code_bg": "#1A2C3A",
        "code_text": "#8AD8F5",
        "blockquote_border": "#22A9E0",
        "callout_bg": "#1A2C3A",
        "callout_text": "#E8F2F7",
        "callout_border": "#385064",
        "navbar_gradient_1": "#001D38",
        "navbar_gradient_2": "#062B4E",
        "navbar_gradient_3": "#0D3A5F",
        "navbar_gradient_4": "#15496F",
        "navbar_gradient_5": "#155F70",
        "navbar_text": "#92DAF7",
        "navbar_link": "#92DAF7",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#0D3A5F"
      }
    }
  },
  "mode_policy": "light"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "fa8a2e1360c17a2b204df8cebd36ea8c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/assets`

**Propósito:** Almacena una imagen del campo multiparte `asset` en la biblioteca de temas privada.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `asset`, `role`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'asset=@./api-circle.svg;type=image/svg+xml' \
  --form role=other \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "api-circle.svg",
        "role": "other",
        "used": false
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "b47707737f33e9b0db3941383cbaa65b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/workspaces/{workspaceSlug}/theme/assets/{file}`

**Propósito:** Elimina un archivo de tema privado de Workspace no utilizado.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`, `file`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "97a72aa130fa5380e38c2f24cdf948ba"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/theme/export`

**Propósito:** Descarga un tema privado de Workspace ZIP para un administrador.

**Permiso:** una clave de API válida. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/export"
```

Respuesta real: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="workspace-theme-api-ogledno-podrucje.zip"
content-length: 27051
```

El archivo se guarda como `response.*`. El contenido verificado tiene 27051 bytes; no lo decodifique como JSON.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/import`

**Propósito:** Importa un tema ZIP del campo multiparte `theme` a un tema de espacio de trabajo privado.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `mode_policy`, `theme`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'theme=@./api-workspace-theme.zip;type=application/zip' \
  --form mode_policy=auto \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/import" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "api-circle.svg",
        "role": "other",
        "used": false
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "c8eee8b7fe118a2c8e40873aa94ac9c9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/import"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/{workspaceSlug}/theme/selection`

**Propósito:** Selecciona la herencia o un tema del sistema sin cambios para un espacio de trabajo.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `mode_policy`, `theme_id`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme_id": "aai",
  "mode_policy": "auto"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/selection" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Respuesta real: **HTTP 200**. Esta es la salida real del filtro `jq` mostrado.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "aai",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "aai",
      "label": {
        "hr": "AAI",
        "en": "AAI"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "logo",
        "used": true
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "3a8b382e6a363076a026c05f7c899268"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/selection"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/workspaces/{workspaceSlug}/tree`

**Propósito:** Devuelve el árbol del espacio de trabajo filtrado y los permisos de nodo efectivos.

**Permiso:** `workspace:read`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "78d6b87d8ee7c962567b443c82af5111"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PUT /api/v1/workspaces/{workspaceSlug}/tree/order`

**Propósito:** Almacena el orden y la jerarquía completos del árbol.

**Permiso:** `workspace:manage`. Permisos actuales del espacio de trabajo/nodo; la administración global y la restauración requieren derechos de administrador.

**Ruta:** `workspaceSlug`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `placements`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "placements": [
    {
      "id": 36,
      "parent_id": null,
      "sort_order": 100
    },
    {
      "id": 37,
      "parent_id": null,
      "sort_order": 200
    },
    {
      "id": 38,
      "parent_id": null,
      "sort_order": 201
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree/order"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 37,
      "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
      "parent_id": null,
      "node_type": "internal_link",
      "slug": "api-internal",
      "title": "API poveznica",
      "title_translations": {
        "en": "API poveznica"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "/about",
      "sort_order": 200,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 38,
      "uuid": "5947a07a-179f-433e-9030-7abbbe9eaa6d",
      "parent_id": null,
      "node_type": "external_link",
      "slug": "api-external",
      "title": "api-external",
      "title_translations": {
        "en": "api-external"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "https://example.com/",
      "sort_order": 201,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "98e3352a89089722cf08b382db4fc6d2"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree/order"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.
