# API — HTML stranice, nacrti i privitci

[← API — sadržaj](api-koristenje_hr.md)

API radi sa samostalnim HTML stranicama i stranicama u područjima. `page:read`/`page:write`/`page:publish` i `attachment:read`/`attachment:write` odvojeni su scopeovi; uz njih vrijede ovlasti dokumenta i područja.

POST prima `title`, `language` te `html` ili strukturirani `content`. `workspace_slug` i opcionalni `parent_id` smještaju novu stranicu u područje. Bez toga nastaje samostalna stranica uz odgovarajuću ovlast. U području je novi sadržaj nacrt. Samostalni Editor nema poseban review/publish tijek: te radnje za njega vraćaju konflikt.

Uobičajeni tijek za područje je **stvaranje → GET nacrta → PATCH nacrta → review → publish**. PATCH postojećeg nacrta treba njegov `draft_revision` i ETag. `html` je kanonski HTML; `content` su strukturirani blokovi. Dohvat s `rendered=1` dodaje prikaz za čitanje; taj prikaz ne spremajte kao zamjenu kanonskog izvora. Objavljena inačica ostaje čitateljima dostupna dok se zasebno mijenja nacrt.

GET verzija prikazuje povijest; restore stvara radnu inačicu i nije sam po sebi objava. Kopiranje prijevoda šalje `source_language` i `target_language`; uređivanje/objava novog prijevoda zasebne su radnje. Oba jezika moraju biti instalirana i omogućena.

`attachment` je naziv multipart polja za datoteku. Za slanje u dijelovima šalju se `upload_id`, `original_name`, `mime_type`, `file_size`, `chunk_index` (od 0), `chunk_count` i `attachment`; djelomično slanje vraća 202, posljednji dio201. `DELETE .../chunks/{uploadId}` otkazuje nedovršeno slanje. Prilog se preuzima kroz `/content`, ne izravnom putanjom privatne pohrane. `download=1` traži preuzimanje. Metapodaci su `display_name`, `alt_text`, `caption`, `description`.

PUT `attachment-visibility` podešava prikaz popisa privitaka i postavlja `none`, `public` ili `authenticated` prema dopuštenom ugovoru i ovlastima dokumenta. Brisanje privitka može ažurirati sadržaj nacrta, pa koristite aktualni ETag. Odbacivanje nacrta nikada objavljene stranice može trajno ukloniti taj dokument; na prethodno objavljenoj stranici čuva objavljenu inačicu. Nemojte ga zamijeniti s brisanjem objavljene stranice.

## Operacije i provjereni primjeri

### `GET /api/v1/pages`

**Svrha:** Vraća objavljene stranice vidljive vlasniku API ključa.

**Ovlast:** `page:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Query parametri:** `lang`, `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages?page[limit]=1&lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages`

**Svrha:** Kreira samostalni dokument ili neobjavljenu Workspace stranicu.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Query parametri:** nema.

**Tijelo / podržana polja:** `content`, `contents_visibility`, `html`, `language`, `node_slug`, `parent_id`, `slug`, `title`, `workspace_slug`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/pages/{documentId}`

**Svrha:** Briše samostalni dokument ili Workspace stranicu prema stvarnim pravima.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}`

**Svrha:** Vraća isključivo objavljenu verziju stranice.

**Ovlast:** `page:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `rendered`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 404. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/pages/{documentId}`

**Svrha:** Sprema novi sadržaj ili zajednički Workspace nacrt uz optimistic lock.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `content`, `contents_visibility`, `draft_revision`, `html`, `title`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 409. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PUT /api/v1/pages/{documentId}/attachment-visibility`

**Svrha:** Mijenja vidljivost popisa privitaka na pregledu.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `visibility`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/attachments`

**Svrha:** Vraća aktivne privitke čitljive stranice.

**Ovlast:** `attachment:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr&page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/attachments`

**Svrha:** Sprema jedan standardni multipart privitak.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `attachment`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'attachment=@./workshop-notes.txt;type=text/plain' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr"
```

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/attachments/chunks`

**Svrha:** Sprema jedan dio velikog privitka; zadnji dio vraća završni resurs.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `attachment`, `chunk_count`, `chunk_index`, `file_size`, `mime_type`, `original_name`, `upload_id`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 202**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/pages/{documentId}/attachments/chunks/{uploadId}`

**Svrha:** Prekida veliki upload i briše već prenesene dijelove.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `uploadId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks/manual-f64d9f4c2b284d2e?lang=hr"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Svrha:** Briše privitak i uklanja njegove reference iz sadržaja svih jezika.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `assetUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `html`, `title`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Svrha:** Vraća metapodatke jednog privitka.

**Ovlast:** `attachment:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `assetUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Svrha:** Djelomično mijenja prikazne metapodatke privitka.

**Ovlast:** `attachment:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `assetUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `alt_text`, `caption`, `description`, `display_name`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 400, 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}/content`

**Svrha:** Streama privitak iz datotečnog sustava ili baze bez JSON/base64 sloja.

**Ovlast:** `attachment:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `assetUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `download`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.bin \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84/content?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

```text
content-type: text/plain;charset=UTF-8
```

Datoteka je zapisana u `response.*`. Provjereni sadržaj ima 19 bajtova; ne pokušavajte ga dekodirati kao JSON.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/pages/{documentId}/draft`

**Svrha:** Odbacuje Workspace nacrt; novu neobjavljenu stranicu potpuno uklanja.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/draft`

**Svrha:** Vraća zajednički Workspace nacrt samo ovlaštenom uredniku.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/publish`

**Svrha:** Objavljuje postojeći Workspace nacrt uz zasebni publish scope i pravo.

**Ovlast:** `page:publish`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/publish?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/review`

**Svrha:** Šalje Workspace nacrt na pregled i pokreće zajedničke obavijesti jednako kao radnja iz web sučelja.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/review?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/translations`

**Svrha:** Kopira sadržaj iz jednog jezika u novi ili postojeći jezični nacrt.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `source_language`, `target_language`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/versions`

**Svrha:** Vraća samo objavljenu povijest tražene jezične verzije.

**Ovlast:** `page:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/versions/{versionNumber}`

**Svrha:** Vraća jednu točno određenu objavljenu verziju.

**Ovlast:** `page:read`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `versionNumber`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/pages/{documentId}/versions/{versionNumber}/restore`

**Svrha:** Vraća staru verziju kao novu radnu verziju ili Workspace nacrt.

**Ovlast:** `page:write`. Trenutačna prava stranice i područja; uređivanje, objava, nacrt i privitci provjeravaju se odvojeno.

**Putanja:** `documentId`, `versionNumber`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

## Potpuni primjer slanja u dva dijela

Oba dijela koriste isti novi `upload_id`, isti naziv i ukupnu veličinu. Novi upload treba drugi identifikator. U ovom provjerenom primjeru svaki dio ima 6 bajtova, ukupno 12. Prvo pripremite datoteke u tekućem direktoriju:

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

Stvarni odgovor: **HTTP 202**.

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

Stvarni odgovor: **HTTP 201**.

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
