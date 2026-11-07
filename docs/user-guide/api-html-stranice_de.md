# API – HTML-Seiten, Entwürfe und Anhänge

<a id="api--html-pages-drafts-and-attachments"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Die API unterstützt eigenständige HTML-Seiten und in Arbeitsbereichen platzierte Seiten. `page:read`, `page:write`, `page:publish`, `attachment:read` und `attachment:write` sind separate Scopes; es gelten auch die aktuellen Dokument- und Arbeitsbereichsrechte.

POST akzeptiert `title`, `language` und entweder `html` oder strukturiertes `content`. `workspace_slug` und optional `parent_id` platzieren die neue Seite in einem Arbeitsbereich. Ohne Platzierung wird eine eigenständige Seite erstellt, wenn dies zulässig ist. Neue Arbeitsbereichsinhalte beginnen als Entwurf. Der eigenständige Editor verfügt über keinen separaten Überprüfungs-/Veröffentlichungs-Workflow. Diese Vorgänge geben dort einen Konflikt zurück.

Der normale Arbeitsbereichsablauf ist **Erstellen → GET Entwurf → PATCH Entwurf → Überprüfen → Veröffentlichen**. Für die Aktualisierung eines vorhandenen Entwurfs sind dessen `draft_revision` und ETag erforderlich. `html` ist kanonisch HTML; `content` enthält strukturierte Blöcke. GET mit `rendered=1` fügt eine Leseansicht hinzu; Speichern Sie die gerenderte Ausgabe nicht anstelle der kanonischen Quelle. Leser sehen weiterhin die veröffentlichte Version, während ein separater Entwurf bearbeitet wird.

Version liest den Exposé-Verlauf; Restore erstellt eine funktionierende Version und veröffentlicht sie nicht. Die Übersetzungskopie benötigt `source_language` und `target_language`; Bearbeiten und Veröffentlichen des Ziels sind separate Schritte. Beide Sprachen müssen installiert und aktiviert sein.

`attachment` ist das mehrteilige Dateifeld. Geblockte Uploads senden `upload_id`, `original_name`, `mime_type`, `file_size`, `chunk_index` (nullbasiert), `chunk_count` und `attachment`. Zwischenblöcke geben 202 und den letzten Block 201 zurück. `DELETE .../chunks/{uploadId}` bricht einen nicht abgeschlossenen Upload ab. Laden Sie Anhänge über `/content` herunter, nicht über einen privaten Speicherpfad. `download=1` fordert Download-Disposition an. Metadatenfelder sind `display_name`, `alt_text`, `caption`, `description`.

PUT `attachment-visibility` steuert die Anzeige der Anhangsliste und ändert `none`, `public` oder `authenticated` entsprechend dem unterstützten Vertrag und den Berechtigungen des Dokuments. Durch das Löschen eines Anhangs werden möglicherweise Entwurfsinhalte aktualisiert. Verwenden Sie daher den aktuellen ETag. Das Verwerfen eines nie veröffentlichten Seitenentwurfs kann dazu führen, dass dieses Dokument dauerhaft entfernt wird. Beim Verwerfen eines Entwurfs einer zuvor veröffentlichten Seite bleibt die veröffentlichte Version erhalten. Verwechseln Sie dies nicht mit dem Löschen einer veröffentlichten Seite.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/pages`

**Zweck:** Gibt veröffentlichte Seiten zurück, die für den API-Schlüsseleigentümer sichtbar sind.

**Berechtigung:** `page:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Abfrageparameter:** `lang`, `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages?page[limit]=1&lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages`

**Zweck:** Erstellt ein eigenständiges Dokument oder eine unveröffentlichte Arbeitsbereichs-Seite.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `content`, `contents_visibility`, `html`, `language`, `node_slug`, `parent_id`, `slug`, `title`, `workspace_slug`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

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

Tatsächliche Antwort: **HTTP 201**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/pages/{documentId}`

**Zweck:** Löscht ein eigenständiges Dokument oder eine Arbeitsbereichsseite entsprechend den tatsächlichen Rechten.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}`

**Zweck:** Gibt nur die veröffentlichte Seitenversion zurück.

**Berechtigung:** `page:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `rendered`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 404. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PATCH /api/v1/pages/{documentId}`

**Zweck:** Speichert neue Inhalte oder den freigegebenen Arbeitsbereichs-Entwurf mit optimistischer Sperrung.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `content`, `contents_visibility`, `draft_revision`, `html`, `title`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 409. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `PUT /api/v1/pages/{documentId}/attachment-visibility`

**Zweck:** Ändert die Sichtbarkeit der Anhangsliste in der Seitenansicht.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `visibility`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/attachments`

**Zweck:** Gibt aktive Anhänge für eine lesbare Seite zurück.

**Berechtigung:** `attachment:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr&page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages/{documentId}/attachments`

**Zweck:** Speichert einen standardmäßigen mehrteiligen Anhang.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `attachment`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'attachment=@./workshop-notes.txt;type=text/plain' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr"
```

Tatsächliche Antwort: **HTTP 201**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403, 422. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `POST /api/v1/pages/{documentId}/attachments/chunks`

**Zweck:** Speichert einen großen Anhangsblock; der letzte Block gibt die fertige Ressource zurück.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `attachment`, `chunk_count`, `chunk_index`, `file_size`, `mime_type`, `original_name`, `upload_id`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

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

Tatsächliche Antwort: **HTTP 202**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/pages/{documentId}/attachments/chunks/{uploadId}`

**Zweck:** Bricht einen großen Upload ab und entfernt bereits übertragene Blöcke.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `uploadId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks/manual-f64d9f4c2b284d2e?lang=hr"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Zweck:** Löscht einen Anhang und entfernt seine Verweise aus allen Gebietsschemainhalten.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `assetUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `html`, `title`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Zweck:** Gibt Metadaten für einen Anhang zurück.

**Berechtigung:** `attachment:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `assetUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PATCH /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Zweck:** Aktualisiert teilweise die Metadaten der Anhangsanzeige.

**Berechtigung:** `attachment:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `assetUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Textkörper / unterstützte Felder:** `alt_text`, `caption`, `description`, `display_name`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 400, 401, 403. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}/content`

**Zweck:** Streamt einen Anhang aus dem Dateisystem oder der Datenbank ohne JSON/base64-Umbruch.

**Berechtigung:** `attachment:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `assetUuid`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `download`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.bin \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84/content?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

```text
content-type: text/plain;charset=UTF-8
```

Die Datei wird als `response.*` gespeichert. Der verifizierte Inhalt hat 19 Bytes; Dekodieren Sie es nicht als JSON.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/pages/{documentId}/draft`

**Zweck:** Verwirft einen Arbeitsbereichs-Entwurf; eine neue unveröffentlichte Seite wird vollständig entfernt.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/draft`

**Zweck:** Gibt den freigegebenen Arbeitsbereichs-Entwurf nur an einen autorisierten Redakteur zurück.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages/{documentId}/publish`

**Zweck:** Veröffentlicht einen vorhandenen Arbeitsbereichsentwurf mit einem separaten Scope und einer separaten Berechtigung.

**Berechtigung:** `page:publish`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/publish?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages/{documentId}/review`

**Zweck:** Sendet einen Arbeitsbereichs-Entwurf zur Überprüfung und löst dieselben gemeinsamen Benachrichtigungen aus wie die Webaktion.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/review?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages/{documentId}/translations`

**Zweck:** Kopiert Inhalte aus einem Gebietsschema in einen neuen oder vorhandenen Gebietsschemaentwurf.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `source_language`, `target_language`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

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

Tatsächliche Antwort: **HTTP 201**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/versions`

**Zweck:** Gibt nur den veröffentlichten Verlauf für das angeforderte Gebietsschema zurück.

**Berechtigung:** `page:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`, `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/pages/{documentId}/versions/{versionNumber}`

**Zweck:** Gibt eine exakte veröffentlichte Version zurück.

**Berechtigung:** `page:read`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `versionNumber`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/pages/{documentId}/versions/{versionNumber}/restore`

**Zweck:** Stellt eine alte Version als neue Arbeitsversion oder Arbeitsbereichs-Entwurf wieder her.

**Berechtigung:** `page:write`. Aktuelle Dokument- und Arbeitsbereichsberechtigungen; Lese-, Bearbeitungs-, Veröffentlichungs- und Anhangzugriff werden separat geprüft.

**Pfad:** `documentId`, `versionNumber`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** `lang`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

<a id="complete-two-part-upload-example"></a>

## Vollständiges zweiteiliges Upload-Beispiel

Beide Chunks verwenden den gleichen neuen `upload_id`, Dateinamen und die gleiche Gesamtgröße. Verwenden Sie eine andere Kennung für einen anderen Upload. Jeder Block in diesem verifizierten Beispiel hat 6 Bytes, insgesamt 12. Erstellen Sie zunächst die Dateien in Ihrem aktuellen Verzeichnis:

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

Tatsächliche Antwort: **HTTP 202**.

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

Tatsächliche Antwort: **HTTP 201**.

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
