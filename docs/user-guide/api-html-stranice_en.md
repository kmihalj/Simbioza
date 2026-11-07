# API — HTML pages, drafts and attachments

[← API — contents](api-koristenje_en.md)

The API supports standalone HTML pages and pages placed in workspaces. `page:read`, `page:write`, `page:publish`, `attachment:read` and `attachment:write` are separate scopes; current document and workspace rights also apply.

POST accepts `title`, `language` and either `html` or structured `content`. `workspace_slug` and optional `parent_id` place the new page in a workspace. Without placement it creates a standalone page when permitted. New workspace content starts as a draft. Standalone Editor has no separate review/publish workflow; those operations return a conflict there.

The normal workspace flow is **create → GET draft → PATCH draft → review → publish**. Updating an existing draft requires its `draft_revision` and ETag. `html` is canonical HTML; `content` contains structured blocks. GET with `rendered=1` adds a reading view; do not store that rendered output in place of the canonical source. Readers keep seeing the published version while a separate draft is edited.

Version reads expose history; restore creates a working version and does not publish it. Translation copy takes `source_language` and `target_language`; editing and publishing the target are separate steps. Both languages must be installed and enabled.

`attachment` is the multipart file field. Chunked uploads send `upload_id`, `original_name`, `mime_type`, `file_size`, `chunk_index` (zero-based), `chunk_count` and `attachment`. Intermediate chunks return202 and the final chunk201. `DELETE .../chunks/{uploadId}` cancels an unfinished upload. Download attachments through `/content`, not a private storage path. `download=1` requests download disposition. Metadata fields are `display_name`, `alt_text`, `caption`, `description`.

PUT `attachment-visibility` controls the attachment-list display and changes `none`, `public` or `authenticated` according to the document's supported contract and permissions. Deleting an attachment may update draft content, so use the current ETag. Discarding a never-published page draft may permanently remove that document; discarding a draft of a previously published page retains its published version. Do not confuse this with deleting a published page.

## Operations and verified examples

### `GET /api/v1/pages`

**Purpose:** Returns published pages visible to the API key owner.

**Permission:** `page:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Query parameters:** `lang`, `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages?page[limit]=1&lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages`

**Purpose:** Creates a standalone document or an unpublished Workspace page.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Query parameters:** none.

**Body / supported fields:** `content`, `contents_visibility`, `html`, `language`, `node_slug`, `parent_id`, `slug`, `title`, `workspace_slug`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/pages/{documentId}`

**Purpose:** Deletes a standalone document or Workspace page according to actual rights.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}`

**Purpose:** Returns only the published page version.

**Permission:** `page:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `rendered`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 404. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/pages/{documentId}`

**Purpose:** Saves new content or the shared Workspace draft with optimistic locking.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `content`, `contents_visibility`, `draft_revision`, `html`, `title`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 409. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/pages/{documentId}/attachment-visibility`

**Purpose:** Changes attachment-list visibility on the page view.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `visibility`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/attachments`

**Purpose:** Returns active attachments for a readable page.

**Permission:** `attachment:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr&page[limit]=1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/attachments`

**Purpose:** Stores one standard multipart attachment.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `attachment`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'attachment=@./workshop-notes.txt;type=text/plain' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr"
```

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/attachments/chunks`

**Purpose:** Stores one large-attachment chunk; the final chunk returns the completed resource.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `attachment`, `chunk_count`, `chunk_index`, `file_size`, `mime_type`, `original_name`, `upload_id`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 202**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/pages/{documentId}/attachments/chunks/{uploadId}`

**Purpose:** Cancels a large upload and removes already transferred chunks.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `uploadId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks/manual-f64d9f4c2b284d2e?lang=hr"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Purpose:** Deletes an attachment and removes its references from all locale content.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `assetUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `html`, `title`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Purpose:** Returns metadata for one attachment.

**Permission:** `attachment:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `assetUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Purpose:** Partially updates attachment display metadata.

**Permission:** `attachment:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `assetUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `alt_text`, `caption`, `description`, `display_name`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 400, 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}/content`

**Purpose:** Streams an attachment from filesystem or database without JSON/base64 wrapping.

**Permission:** `attachment:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `assetUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `download`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.bin \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84/content?lang=hr"
```

Actual response: **HTTP 200**.

```text
content-type: text/plain;charset=UTF-8
```

The file is saved as `response.*`. The verified content has 19 bytes; do not decode it as JSON.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/pages/{documentId}/draft`

**Purpose:** Discards a Workspace draft; a new unpublished page is removed completely.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/draft`

**Purpose:** Returns the shared Workspace draft only to an authorized editor.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/publish`

**Purpose:** Publishes an existing Workspace draft with a separate scope and permission.

**Permission:** `page:publish`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/publish?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/review`

**Purpose:** Submits a Workspace draft for review and triggers the same shared notifications as the web action.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/review?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/translations`

**Purpose:** Copies content from one locale into a new or existing locale draft.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `source_language`, `target_language`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/versions`

**Purpose:** Returns only the published history for the requested locale.

**Permission:** `page:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/versions/{versionNumber}`

**Purpose:** Returns one exact published version.

**Permission:** `page:read`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `versionNumber`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/pages/{documentId}/versions/{versionNumber}/restore`

**Purpose:** Restores an old version as a new working version or Workspace draft.

**Permission:** `page:write`. Current document and workspace permissions; read, edit, publish and attachment access are checked separately.

**Path:** `documentId`, `versionNumber`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

## Complete two-part upload example

Both chunks use the same fresh `upload_id`, filename and total size. Use another identifier for another upload. Each chunk in this verified example has 6 bytes, 12 in total. First create the files in your current directory:

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

Actual response: **HTTP 202**.

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

Actual response: **HTTP 201**.

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
