# API — tâches dans le contenu

<a id="api--tasks-in-content"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

Les tâches font partie du contenu de la page publiée. L’API ne crée pas leur définition ; les auteurs l'ajoutent dans l'éditeur HTML ou via un contenu structuré dans la [page API](api-html-stranice_fr.md). `task:read` nécessite l'accès à la page publiée. `task:write` vérifie en outre les `toggle_scope` de la liste et les autorisations utilisateur actuelles.

PUT nécessite un booléen `completed` et la tâche actuelle GET ETag. Changer d'état ne crée pas une autre version de la page ; l’histoire enregistre l’acteur. L'historique utilise la pagination du curseur dans une fenêtre d'enregistrement disponible limitée. Sélectionnez la langue avec `lang`.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/pages/{documentId}/tasks`

**Objectif :** Renvoie toutes les tâches de la publication actuelle de la page.

**Autorisation :** `task:read`. Accès à la page publiée et autorisation en vertu de la règle de changement d'état de la liste de tâches.

**Chemin :** `documentId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks?lang=hr&page[limit]=1"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "text": "Pregledati upute",
      "toggle_scope": "viewers",
      "state": {
        "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
        "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "completed": false,
        "updated_by_user_id": null,
        "updated_by_display_name": "",
        "updated_at": "",
        "version": 0
      }
    }
  ],
  "meta": {
    "request_id": "8520a08a8fe81a37a03f5a9e0644ba2d",
    "page": {
      "limit": 1,
      "total": 2,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1",
    "next": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}`

**Objectif :** Renvoie une tâche et son état actuel.

**Autorisation :** `task:read`. Accès à la page publiée et autorisation en vertu de la règle de changement d'état de la liste de tâches.

**Chemin :** `documentId`, `taskUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "text": "Pregledati upute",
    "toggle_scope": "viewers",
    "state": {
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": false,
      "updated_by_user_id": null,
      "updated_by_display_name": "",
      "updated_at": "",
      "version": 0
    }
  },
  "meta": {
    "request_id": "8e191e76e361b0b48ab9c57852a654a2"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}/history`

**Objectif :** Renvoie l'audit de changement d'état d'une tâche.

**Autorisation :** `task:read`. Accès à la page publiée et autorisation en vertu de la règle de changement d'état de la liste de tâches.

**Chemin :** `documentId`, `taskUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`, `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page[limit]=1"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "e8222ff1-2de8-4e43-b7ea-3632fdc5e9fb",
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": true,
      "changed_by_user_id": 1,
      "changed_by_display_name": "Administrator",
      "changed_at": "2026-11-06 22:49:03"
    }
  ],
  "meta": {
    "request_id": "fb30976da54e1510df5467626307a71b",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page%5Blimit%5D=1",
    "next": null
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PUT /api/v1/pages/{documentId}/tasks/{taskUuid}/state`

**Objectif :** Définit l'état de la tâche sans créer de nouvelle version de page.

**Autorisation :** `task:write`. Accès à la page publiée et autorisation en vertu de la règle de changement d'état de la liste de tâches.

**Chemin :** `documentId`, `taskUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `lang`.

**Corps/Champs pris en charge :** `completed`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "completed": true
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "completed": true,
    "updated_by_user_id": 1,
    "updated_by_display_name": "Administrator",
    "updated_at": "2026-11-06 22:49:03",
    "version": 1
  },
  "meta": {
    "request_id": "32fc68d59ba7a6ba20b12cc7736128f5"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
  }
}
```

**Erreurs vérifiées pour cette opération :** 400, 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.
