# API: páginas HTML, borradores y archivos adjuntos

<a id="api--html-pages-drafts-and-attachments"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

El API admite páginas HTML independientes y páginas ubicadas en espacios de trabajo. `page:read`, `page:write`, `page:publish`, `attachment:read` y `attachment:write` son ámbitos separados; También se aplican los derechos actuales sobre documentos y espacios de trabajo.

POST acepta `title`, `language` y `html` o `content` estructurado. `workspace_slug` y opcional `parent_id` colocan la nueva página en un espacio de trabajo. Sin ubicación, crea una página independiente cuando está permitido. El nuevo contenido del espacio de trabajo comienza como un borrador. El editor independiente no tiene un flujo de trabajo de revisión/publicación independiente; esas operaciones devuelven un conflicto allí.

El flujo normal del espacio de trabajo es **crear → GET borrador → PATCH borrador → revisar → publicar**. La actualización de un borrador existente requiere su `draft_revision` y ETag. `html` es canónico HTML; `content` contiene bloques estructurados. GET con `rendered=1` agrega una vista de lectura; no almacene esa salida renderizada en lugar de la fuente canónica. Los lectores siguen viendo la versión publicada mientras se edita un borrador por separado.

La versión dice exponer el historial; restaurar crea una versión funcional y no la publica. La copia de traducción toma `source_language` y `target_language`; editar y publicar el objetivo son pasos separados. Ambos idiomas deben estar instalados y habilitados.

`attachment` es el campo del archivo de varias partes. Las cargas fragmentadas envían `upload_id`, `original_name`, `mime_type`, `file_size`, `chunk_index` (base cero), `chunk_count` y `attachment`. Los fragmentos intermedios regresan202 y el fragmento final201. `DELETE .../chunks/{uploadId}` cancela una carga incompleta. Descargue archivos adjuntos a través de `/content`, no a través de una ruta de almacenamiento privada. `download=1` solicita disposición de descarga. Los campos de metadatos son `display_name`, `alt_text`, `caption`, `description`.

PUT `attachment-visibility` controla la visualización de la lista de archivos adjuntos y cambia `none`, `public` o `authenticated` según el contrato y los permisos admitidos del documento. Eliminar un archivo adjunto puede actualizar el contenido del borrador, así que use el ETag actual. Descartar un borrador de página nunca publicado puede eliminar permanentemente ese documento; al descartar un borrador de una página publicada anteriormente se conserva su versión publicada. No confunda esto con eliminar una página publicada.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/pages`

**Propósito:** Devuelve páginas publicadas visibles para el propietario de la clave de API.

**Permiso:** `page:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Parámetros de consulta:** `lang`, `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages?page[limit]=1&lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "id": "korisnicke-upute-ankete-izrada-objava--3634a42afb",
      "title": "1. Kreirajte nacrt",
      "language": "hr",
      "default_language": "hr",
      "available_languages": [
        "en",
        "hr"
      ],
      "version_number": 5,
      "is_draft": false,
      "draft_revision": "",
      "attachment_visibility": "none",
      "workspace_managed": true,
      "contents_visibility": "hidden",
      "created_at": "2026-10-27 09:43:15",
      "updated_at": "2026-10-27 11:11:07",
      "created_by_user_id": 1,
      "updated_by_user_id": 1,
      "created_by_display_name": "Administrator",
      "updated_by_display_name": "Administrator"
    }
  ],
  "meta": {
    "request_id": "adcdc319e4f744728ad7892844707887",
    "page": {
      "limit": 1,
      "total": 31,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/pages?page%5Blimit%5D=1&lang=hr",
    "next": "/api/v1/pages?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9&lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages`

**Propósito:** Crea un documento independiente o una página de espacio de trabajo no publicada.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `content`, `contents_visibility`, `html`, `language`, `node_slug`, `parent_id`, `slug`, `title`, `workspace_slug`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "Plan API radionice",
  "slug": "api-plan-radionice",
  "workspace_slug": "api-ogledno-podrucje",
  "language": "hr",
  "content": [
    {
      "type": "html",
      "html": "<h1>Plan radionice</h1><p>Objasnimo cjelokupni API.</p>"
    },
    {
      "type": "task_list",
      "title": "Priprema radionice",
      "toggle_scope": "viewers",
      "items": [
        {
          "text": "Pregledati upute"
        },
        {
          "text": "Provjeriti primjer"
        }
      ]
    }
  ]
}' \
  "$BASE_URL/api/v1/pages"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": true,
    "draft_revision": "4665715ff5846b0194bae853acbf4c741e22c8c0f217f133",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "workspace_node": {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "workspace_id": 4,
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": "{\"en\":\"Plan API radionice\",\"hr\":\"Plan API radionice\"}",
      "document_key": "api-plan-radionice",
      "route_name": null,
      "target_url": null,
      "sort_order": 100,
      "is_homepage": 1,
      "is_tree_hidden": 0,
      "is_enabled": 1,
      "contents_visibility": "inherit",
      "created_by_user_id": 1,
      "updated_by_user_id": 1,
      "created_at": "2026-11-06 22:44:21",
      "updated_at": "2026-11-06 22:44:21"
    }
  },
  "meta": {
    "request_id": "e79f9286b26d48ee17b44ecd47d01359"
  },
  "links": {
    "self": "/api/v1/pages"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/pages/{documentId}`

**Propósito:** Elimina un documento independiente o una página del espacio de trabajo según los derechos reales.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}`

**Propósito:** Devuelve solo la versión de la página publicada.

**Permiso:** `page:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`, `rendered`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": false,
    "draft_revision": "",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "rendered_html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\" data-task-state-url=\"/tasks/state\" data-task-csrf-url=\"/tasks/csrf-token\" data-task-language=\"hr\" data-task-last-changed-label=\"Last changed\" data-task-close-label=\"Close\" data-task-csrf-error=\"CSRF token is unavailable.\" data-task-save-error=\"Task state could not be saved.\" data-task-empty-label=\"No tasks to display.\" data-task-document=\"api-plan-radionice\" data-task-list-label=\"Priprema radionice\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3><div class=\"editor-task-list-meta\"><small class=\"editor-task-list-audit\" hidden=\"hidden\"></small></div>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><label class=\"editor-task-control\"><input type=\"checkbox\" class=\"form-check-input editor-task-checkbox\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-document=\"api-plan-radionice\" data-task-language=\"hr\" disabled><span class=\"editor-task-text\">Pregledati upute</span></label></li>\n<li class=\"editor-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><label class=\"editor-task-control\"><input type=\"checkbox\" class=\"form-check-input editor-task-checkbox\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-document=\"api-plan-radionice\" data-task-language=\"hr\" disabled><span class=\"editor-task-text\">Provjeriti primjer</span></label></li>\n</ul></section>"
  },
  "meta": {
    "request_id": "36c727b5e10aa0addfff223a23e62ae6"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
  }
}
```

**Errores verificados para esta operación:** 401, 403, 404. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PATCH /api/v1/pages/{documentId}`

**Propósito:** Guarda contenido nuevo o el borrador del espacio de trabajo compartido con bloqueo optimista.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `content`, `contents_visibility`, `draft_revision`, `html`, `title`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "title": "Plan API radionice",
  "draft_revision": "4665715ff5846b0194bae853acbf4c741e22c8c0f217f133",
  "content": [
    {
      "type": "html",
      "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
    },
    {
      "type": "task_list",
      "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "title": "Priprema radionice",
      "toggle_scope": "viewers",
      "view": "list",
      "headers": [],
      "items": [
        {
          "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
          "text": "Pregledati upute",
          "initial_completed": false,
          "cells": []
        },
        {
          "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
          "text": "Provjeriti primjer",
          "initial_completed": false,
          "cells": []
        }
      ]
    }
  ]
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": true,
    "draft_revision": "c432b67012b68f6e9ceac28a2ab2d32233fae4f41bc6fa8c",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:21",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "78d3a47eb5d697696d041c0eb8270773"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403, 409. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PUT /api/v1/pages/{documentId}/attachment-visibility`

**Propósito:** Cambia la visibilidad de la lista de archivos adjuntos en la vista de página.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `visibility`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "visibility": "authenticated"
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachment-visibility?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 2,
    "is_draft": true,
    "draft_revision": "630dbf45a41dbf230ff2010a88a5552309f73a82d6ead401",
    "attachment_visibility": "authenticated",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:48:21",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator"
  },
  "meta": {
    "request_id": "44dfd16005af547e2c7e9e1f05577e19"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachment-visibility?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/attachments`

**Propósito:** Devuelve archivos adjuntos activos para una página legible.

**Permiso:** `attachment:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`, `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr&page[limit]=1"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "a458c2aa-62e3-46b3-a262-c4e9376bbc84",
      "document_id": "api-plan-radionice",
      "original_name": "workshop-notes.txt",
      "display_name": "workshop-notes.txt",
      "mime_type": "text/plain",
      "kind": "file",
      "file_size": 19,
      "alt_text": "",
      "caption": "workshop-notes.txt",
      "description": "",
      "created_at": "2026-11-06 22:48:20",
      "created_by_user_id": 1,
      "created_by_display_name": "Administrator",
      "version_number": 1,
      "version_count": 1
    }
  ],
  "meta": {
    "request_id": "99676c040a6c38d63fa6f6f9793c9692",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments?lang=hr&page%5Blimit%5D=1",
    "next": null
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/attachments`

**Propósito:** Almacena un accesorio multiparte estándar.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `attachment`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'attachment=@./workshop-notes.txt;type=text/plain' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "uuid": "a458c2aa-62e3-46b3-a262-c4e9376bbc84",
    "document_id": "api-plan-radionice",
    "original_name": "workshop-notes.txt",
    "display_name": "workshop-notes.txt",
    "mime_type": "text/plain",
    "kind": "file",
    "file_size": 19,
    "alt_text": "",
    "caption": "workshop-notes.txt",
    "description": "",
    "created_at": "2026-11-06 22:48:20",
    "created_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "version_number": 1,
    "version_count": 1
  },
  "meta": {
    "request_id": "6329eb37cd6d687d577f7abdcb545bf1"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403, 422. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/attachments/chunks`

**Propósito:** Almacena un fragmento de archivo adjunto grande; el fragmento final devuelve el recurso completo.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `attachment`, `chunk_count`, `chunk_index`, `file_size`, `mime_type`, `original_name`, `upload_id`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form upload_id=manual-f64d9f4c2b284d2e \
  --form original_name=large-notes.txt \
  --form mime_type=text/plain \
  --form file_size=12 \
  --form chunk_index=0 \
  --form chunk_count=2 \
  --form 'attachment=@./chunk.part;type=application/octet-stream' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks?lang=hr"
```

Respuesta real: **HTTP 202**.

```json
{
  "data": null,
  "meta": {
    "request_id": "d706442ad663a28034a5dcc4a8a29c67"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments/chunks?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/pages/{documentId}/attachments/chunks/{uploadId}`

**Propósito:** Cancela una carga grande y elimina fragmentos ya transferidos.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `uploadId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks/manual-f64d9f4c2b284d2e?lang=hr"
```

Respuesta real: **HTTP 204**. El cuerpo está vacío.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Propósito:** Elimina un archivo adjunto y elimina sus referencias de todo el contenido local.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `assetUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `html`, `title`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 2,
    "is_draft": true,
    "draft_revision": "630dbf45a41dbf230ff2010a88a5552309f73a82d6ead401",
    "attachment_visibility": "authenticated",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:48:21",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "0c1fe4209a148857dd770085a1a05524"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Propósito:** Devuelve metadatos para un archivo adjunto.

**Permiso:** `attachment:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `assetUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "uuid": "a458c2aa-62e3-46b3-a262-c4e9376bbc84",
    "document_id": "api-plan-radionice",
    "original_name": "workshop-notes.txt",
    "display_name": "workshop-notes.txt",
    "mime_type": "text/plain",
    "kind": "file",
    "file_size": 19,
    "alt_text": "",
    "caption": "workshop-notes.txt",
    "description": "",
    "created_at": "2026-11-06 22:48:20",
    "created_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "version_number": 1,
    "version_count": 1
  },
  "meta": {
    "request_id": "996c39d94443a701c7f595c7f8fc4044"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `PATCH /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Propósito:** Actualiza parcialmente los metadatos de visualización de archivos adjuntos.

**Permiso:** `attachment:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `assetUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo/campos admitidos:** `alt_text`, `caption`, `description`, `display_name`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "display_name": "Workshop notes",
  "alt_text": "Notes for the API workshop",
  "caption": "Workshop notes",
  "description": "Synthetic text attachment."
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "uuid": "a458c2aa-62e3-46b3-a262-c4e9376bbc84",
    "document_id": "api-plan-radionice",
    "original_name": "workshop-notes.txt",
    "display_name": "Workshop notes",
    "mime_type": "text/plain",
    "kind": "file",
    "file_size": 19,
    "alt_text": "Notes for the API workshop",
    "caption": "Workshop notes",
    "description": "Synthetic text attachment.",
    "created_at": "2026-11-06 22:48:20",
    "created_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "version_number": 1,
    "version_count": 1
  },
  "meta": {
    "request_id": "c66139e7ae63f098f839e18ac4a41d5b"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 400, 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}/content`

**Propósito:** Transmite un archivo adjunto desde el sistema de archivos o la base de datos sin ajuste JSON/base64.

**Permiso:** `attachment:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `assetUuid`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`, `download`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.bin \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84/content?lang=hr"
```

Respuesta real: **HTTP 200**.

```text
content-type: text/plain;charset=UTF-8
```

El archivo se guarda como `response.*`. El contenido verificado tiene 19 bytes; no lo decodifique como JSON.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `DELETE /api/v1/pages/{documentId}/draft`

**Propósito:** Descarta un borrador del espacio de trabajo; una nueva página no publicada se elimina por completo.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": false,
    "draft_revision": "",
    "attachment_visibility": "authenticated",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "9770a689d7b973aa5573a5a5261ed09d"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/draft?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/draft`

**Propósito:** Devuelve el borrador del espacio de trabajo compartido solo a un editor autorizado.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": true,
    "draft_revision": "4665715ff5846b0194bae853acbf4c741e22c8c0f217f133",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "df48bc33363044e61d87f4e5ba3b8c01"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/draft?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/publish`

**Propósito:** Publica un borrador de espacio de trabajo existente con un alcance y permiso separados.

**Permiso:** `page:publish`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/publish?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": false,
    "draft_revision": "",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:21",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "7d23703f73364a276cc886ac29334f44"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/publish?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/review`

**Propósito:** envía un borrador del espacio de trabajo para su revisión y activa las mismas notificaciones compartidas que la acción web.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

**Antes de escribir:** actualice GET y configure `ETAG` desde su encabezado `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/review?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 1,
    "is_draft": true,
    "draft_revision": "c432b67012b68f6e9ceac28a2ab2d32233fae4f41bc6fa8c",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:21",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "6055daf88d087163b68b70f753b715c5"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/review?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/translations`

**Propósito:** Copia el contenido de una configuración regional en un borrador de configuración regional nuevo o existente.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `source_language`, `target_language`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "source_language": "hr",
  "target_language": "en"
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/translations"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "en",
    "default_language": "hr",
    "available_languages": [
      "en",
      "hr"
    ],
    "version_number": 2,
    "is_draft": true,
    "draft_revision": "c6a39528ef34103cce565e170d10405246f491b2f09dbf0e",
    "attachment_visibility": "authenticated",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:49:03",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "84a86dd54d71f443a53be6d642205db5"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/translations"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/versions`

**Propósito:** Devuelve solo el historial publicado para la configuración regional solicitada.

**Permiso:** `page:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`, `page[after]`, `page[limit]`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": [
    {
      "document_id": "api-plan-radionice",
      "language": "hr",
      "version_number": 1,
      "title": "Plan API radionice",
      "created_at": "2026-11-06 22:44:20",
      "created_by_user_id": 1,
      "created_by_display_name": "Administrator",
      "is_current": true
    }
  ],
  "meta": {
    "request_id": "0bc76919135a6179e8157de9435a1cf2",
    "page": {
      "limit": 50,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/versions?lang=hr",
    "next": null
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/pages/{documentId}/versions/{versionNumber}`

**Propósito:** Devuelve una versión publicada exacta.

**Permiso:** `page:read`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `versionNumber`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "document_id": "api-plan-radionice",
    "language": "hr",
    "version_number": 1,
    "title": "Plan API radionice",
    "created_at": "2026-11-06 22:44:20",
    "created_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "is_current": true,
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "f6945104df1b1d6d84aa0bed258ca2ea"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/pages/{documentId}/versions/{versionNumber}/restore`

**Propósito:** Restaura una versión anterior como una nueva versión de trabajo o borrador del espacio de trabajo.

**Permiso:** `page:write`. Permisos actuales de documentos y espacios de trabajo; leer, editar, publicar y acceder a archivos adjuntos se verifican por separado.

**Ruta:** `documentId`, `versionNumber`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `lang`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
```

Respuesta real: **HTTP 200**.

```json
{
  "data": {
    "id": "api-plan-radionice",
    "title": "Plan API radionice",
    "language": "hr",
    "default_language": "hr",
    "available_languages": [
      "hr"
    ],
    "version_number": 2,
    "is_draft": true,
    "draft_revision": "630dbf45a41dbf230ff2010a88a5552309f73a82d6ead401",
    "attachment_visibility": "none",
    "workspace_managed": true,
    "contents_visibility": "inherit",
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:48:20",
    "created_by_user_id": 1,
    "updated_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "updated_by_display_name": "Administrator",
    "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n<section class=\"editor-html-task-list\" data-editor-html-task-list=\"1\" data-task-list-uuid=\"076e4a13-3797-4bdd-9fa8-618e832bffd0\" data-task-toggle-scope=\"viewers\" data-task-list-view=\"list\"><h3 class=\"editor-html-task-list-title\">Priprema radionice</h3>\n<ul class=\"editor-html-task-items\">\n<li class=\"editor-html-task-item\" data-task-uuid=\"a7586a8f-d1e7-4bb4-bcb7-2273eb82e923\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Pregledati upute</span></li>\n<li class=\"editor-html-task-item\" data-task-uuid=\"fd3c4618-f8c2-4cb8-a4a4-fee47435e56b\" data-task-initial-completed=\"0\"><span class=\"editor-html-task-text\">Provjeriti primjer</span></li>\n</ul></section>",
    "content": [
      {
        "type": "html",
        "html": "<h1>Plan radionice</h1>\n<p>Objasnimo cjelokupni API.</p>\n"
      },
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ],
    "embeds": [
      {
        "type": "task_list",
        "uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "title": "Priprema radionice",
        "toggle_scope": "viewers",
        "view": "list",
        "headers": [],
        "items": [
          {
            "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
            "text": "Pregledati upute",
            "initial_completed": false,
            "cells": []
          },
          {
            "uuid": "fd3c4618-f8c2-4cb8-a4a4-fee47435e56b",
            "text": "Provjeriti primjer",
            "initial_completed": false,
            "cells": []
          }
        ]
      }
    ]
  },
  "meta": {
    "request_id": "bdcd34ef05619d5d30396f479ad63b15"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
  }
}
```

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

<a id="complete-two-part-upload-example"></a>

## Ejemplo de carga completo de dos partes

Ambos fragmentos utilizan el mismo `upload_id` nuevo, nombre de archivo y tamaño total. Utilice otro identificador para otra carga. Cada fragmento de este ejemplo verificado tiene 6 bytes, 12 en total. Primero cree los archivos en su directorio actual:

```sh
printf 'first-' > guide-chunk-0.part
printf 'second' > guide-chunk-1.part
```

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form upload_id=guide-5f4c81d11960 \
  --form original_name=guide-notes.txt \
  --form mime_type=text/plain \
  --form file_size=12 \
  --form chunk_index=0 \
  --form chunk_count=2 \
  --form 'attachment=@./guide-chunk-0.part;type=application/octet-stream' \
  "$BASE_URL/api/v1/pages/api-vodic-za-integracije/attachments/chunks?lang=hr"
```

Respuesta real: **HTTP 202**.

```json
{
  "data": null,
  "meta": {
    "request_id": "cce2fe88fd89a2f16ac13e4498955357"
  },
  "links": {
    "self": "/api/v1/pages/api-vodic-za-integracije/attachments/chunks?lang=hr"
  }
}
```

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form upload_id=guide-5f4c81d11960 \
  --form original_name=guide-notes.txt \
  --form mime_type=text/plain \
  --form file_size=12 \
  --form chunk_index=1 \
  --form chunk_count=2 \
  --form 'attachment=@./guide-chunk-1.part;type=application/octet-stream' \
  "$BASE_URL/api/v1/pages/api-vodic-za-integracije/attachments/chunks?lang=hr"
```

Respuesta real: **HTTP 201**.

```json
{
  "data": {
    "uuid": "f2f91dda-c922-45c9-b70a-1e7ad675399f",
    "document_id": "api-vodic-za-integracije",
    "original_name": "guide-notes.txt",
    "display_name": "guide-notes.txt",
    "mime_type": "text/plain",
    "kind": "file",
    "file_size": 12,
    "alt_text": "",
    "caption": "guide-notes.txt",
    "description": "",
    "created_at": "2026-11-06 23:56:28",
    "created_by_user_id": 1,
    "created_by_display_name": "Administrator",
    "version_number": 1,
    "version_count": 1
  },
  "meta": {
    "request_id": "3c6b285b281884e99cf54de1b44d0898"
  },
  "links": {
    "self": "/api/v1/pages/api-vodic-za-integracije/attachments/chunks?lang=hr"
  }
}
```
