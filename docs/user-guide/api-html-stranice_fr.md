# API — pages HTML, brouillons et pièces jointes

<a id="api--html-pages-drafts-and-attachments"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

L’API prend en charge les pages HTML autonomes et les pages placées dans des espaces de travail. `page:read`, `page:write`, `page:publish`, `attachment:read` et `attachment:write` sont des étendues distinctes ; Les droits actuels sur les documents et l’espace de travail s’appliquent également.

POST accepte `title`, `language` et soit `html`, soit `content` structuré. `workspace_slug` et `parent_id` en option placent la nouvelle page dans un espace de travail. Sans placement, il crée une page autonome lorsque cela est autorisé. Le nouveau contenu de l’espace de travail commence sous la forme d’un brouillon. L'éditeur autonome n'a pas de flux de travail de révision/publication séparé ; ces opérations y renvoient un conflit.

Le flux normal de l'espace de travail est **créer → GET brouillon → PATCH brouillon → réviser → publier**. La mise à jour d'un brouillon existant nécessite ses `draft_revision` et ETag. `html` est canonique HTML ; `content` contient des blocs structurés. GET avec `rendered=1` ajoute une vue de lecture ; ne stockez pas cette sortie rendue à la place de la source canonique. Les lecteurs continuent de voir la version publiée pendant qu'un brouillon distinct est édité.

La version lit l'historique d'exposition ; La restauration crée une version de travail et ne la publie pas. La copie de traduction prend `source_language` et `target_language` ; la modification et la publication de la cible sont des étapes distinctes. Les deux langues doivent être installées et activées.

`attachment` est le champ du fichier en plusieurs parties. Les téléchargements fragmentés envoient `upload_id`, `original_name`, `mime_type`, `file_size`, `chunk_index` (base zéro), `chunk_count` et `attachment`. Les morceaux intermédiaires renvoient 202 et le morceau final 201. `DELETE .../chunks/{uploadId}` annule un téléchargement inachevé. Téléchargez les pièces jointes via `/content`, et non via un chemin de stockage privé. `download=1` demande la disposition du téléchargement. Les champs de métadonnées sont `display_name`, `alt_text`, `caption`, `description`.

PUT `attachment-visibility` contrôle l'affichage de la liste des pièces jointes et modifie `none`, `public` ou `authenticated` en fonction du contrat et des autorisations pris en charge par le document. La suppression d'une pièce jointe peut mettre à jour le brouillon du contenu, utilisez donc le ETag actuel. La suppression d'un brouillon de page jamais publié peut supprimer définitivement ce document ; la suppression d'un brouillon d'une page précédemment publiée conserve sa version publiée. Ne confondez pas cela avec la suppression d'une page publiée.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/pages`

**Objectif :** Renvoie les pages publiées visibles par le propriétaire de la clé API.

**Autorisation :** `page:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Paramètres de requête :** `lang`, `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages?page[limit]=1&lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages`

**Objectif :** Crée un document autonome ou une page d'espace de travail non publiée.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Paramètres de requête :** aucun.

**Corps/champs pris en charge :** `content`, `contents_visibility`, `html`, `language`, `node_slug`, `parent_id`, `slug`, `title`, `workspace_slug`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/pages/{documentId}`

**Objectif :** Supprime un document autonome ou une page d'espace de travail en fonction des droits réels.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}`

**Objectif :** Renvoie uniquement la version de la page publiée.

**Autorisation :** `page:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `rendered`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice?lang=hr&rendered=1"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 404. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/pages/{documentId}`

**Objectif :** Enregistre le nouveau contenu ou le brouillon de l'espace de travail partagé avec un verrouillage optimiste.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `content`, `contents_visibility`, `draft_revision`, `html`, `title`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 409. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PUT /api/v1/pages/{documentId}/attachment-visibility`

**Objectif :** Modifie la visibilité de la liste des pièces jointes dans l'affichage de la page.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `visibility`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/attachments`

**Objectif :** Renvoie les pièces jointes actives pour une page lisible.

**Autorisation :** `attachment:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr&page[limit]=1"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/attachments`

**Objectif :** Stocke une pièce jointe standard en plusieurs parties.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `attachment`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'attachment=@./workshop-notes.txt;type=text/plain' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments?lang=hr"
```

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/attachments/chunks`

**Objectif :** Stocke un morceau de pièces jointes volumineuses ; le dernier morceau renvoie la ressource terminée.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/champs pris en charge :** `attachment`, `chunk_count`, `chunk_index`, `file_size`, `mime_type`, `original_name`, `upload_id`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 202**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/pages/{documentId}/attachments/chunks/{uploadId}`

**Objectif :** Annule un téléchargement volumineux et supprime les morceaux déjà transférés.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `uploadId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/chunks/manual-f64d9f4c2b284d2e?lang=hr"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Objectif :** Supprime une pièce jointe et supprime ses références de tout le contenu des paramètres régionaux.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `assetUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `html`, `title`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Objectif :** Renvoie les métadonnées d'une pièce jointe.

**Autorisation :** `attachment:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `assetUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/pages/{documentId}/attachments/{assetUuid}`

**Objectif :** Met à jour partiellement les métadonnées d'affichage des pièces jointes.

**Autorisation :** `attachment:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `assetUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `alt_text`, `caption`, `description`, `display_name`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 400, 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/attachments/{assetUuid}/content`

**Objectif :** Diffuse une pièce jointe à partir d'un système de fichiers ou d'une base de données sans encapsulation JSON/base64.

**Autorisation :** `attachment:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `assetUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `download`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.bin \
  "$BASE_URL/api/v1/pages/api-plan-radionice/attachments/a458c2aa-62e3-46b3-a262-c4e9376bbc84/content?lang=hr"
```

Réponse réelle : **HTTP 200**.

```text
content-type: text/plain;charset=UTF-8
```

Le fichier est enregistré sous `response.*`. Le contenu vérifié comporte 19 octets ; ne le décodez pas comme JSON.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/pages/{documentId}/draft`

**Objectif :** Supprime un brouillon d'espace de travail ; une nouvelle page non publiée est complètement supprimée.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/draft`

**Objectif :** Renvoie le brouillon de l'espace de travail partagé uniquement à un éditeur autorisé.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/draft?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/publish`

**Objectif :** publie un brouillon d'espace de travail existant avec une portée et une autorisation distinctes.

**Autorisation :** `page:publish`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/publish?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/review`

**Objectif :** Soumet un brouillon d'espace de travail pour révision et déclenche les mêmes notifications partagées que l'action Web.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/review?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/translations`

**Objectif :** Copie le contenu d'un paramètre régional vers un brouillon de paramètres régionaux nouveau ou existant.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `source_language`, `target_language`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/versions`

**Objectif :** Renvoie uniquement l'historique publié pour les paramètres régionaux demandés.

**Autorisation :** `page:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/versions/{versionNumber}`

**Objectif :** Renvoie une version publiée exacte.

**Autorisation :** `page:read`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `versionNumber`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/pages/{documentId}/versions/{versionNumber}/restore`

**Objectif :** Restaure une ancienne version en tant que nouvelle version de travail ou brouillon de l'espace de travail.

**Autorisation :** `page:write`. Autorisations actuelles des documents et de l'espace de travail ; L'accès en lecture, en modification, en publication et en pièce jointe est vérifié séparément.

**Chemin :** `documentId`, `versionNumber`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/versions/1/restore?lang=hr"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

<a id="complete-two-part-upload-example"></a>

## Exemple complet de téléchargement en deux parties

Les deux morceaux utilisent le même nouveau `upload_id`, le même nom de fichier et la même taille totale. Utilisez un autre identifiant pour un autre téléchargement. Chaque morceau de cet exemple vérifié comporte 6 octets, 12 au total. Créez d'abord les fichiers dans votre répertoire actuel :

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

Réponse réelle : **HTTP 202**.

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

Réponse réelle : **HTTP 201**.

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
