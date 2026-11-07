# API — notifications et suivis personnels

<a id="api--notifications-and-personal-follows"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

Toutes les opérations s'appliquent au propriétaire de la clé. Un corps JSON ne peut pas sélectionner la boîte de réception ou les préférences d'un autre utilisateur. `notifications:read` lit la boîte de réception ; `notifications:write` modifie l'état de lecture et supprime les notifications de lecture. Les listes utilisent `state=all`, `read` ou `unread`, avec des paramètres de curseur. PATCH envoie un booléen `read` avec un ETag ; `read-all` marque la lecture de l'intégralité de la boîte de réception du propriétaire. Un UUID étranger/manquant ne divulgue aucun contenu ; le GET actuel renvoie 422 `notification_validation_failed` pour ce cas.

`follows:read`/`follows:write` gère les suivis personnels. Les types sont `workspace`, `page`, `calendar`, `task_list`. Le `target_id` d'une page est son **ID numérique de nœud d'espace de travail**, et non la clé de document HTML. Les listes de tâches nécessitent également `document_id`. L'accès actuel à la cible est revérifié. Suivre un calendrier et s'y abonner sont différents : supprimer un abonnement désactive les notifications sans supprimer l'abonnement au calendrier.

Les préférences personnelles incluent `email_enabled`, `email_mode` (`immediate`, `daily`, `important`, `off`), `notify_own_changes`, `theme_mode` (`auto`, `light`, `dark`, `system`). Le choix du thème s'applique uniquement lorsque la politique globale autorise un mode personnel. Un suivi peut avoir `email_mode_override` ; `null` hérite des préférences. `GET personal-workspace` nécessite `workspace:read` et renvoie le statut ; **cela ne crée pas d'espace de travail**.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/notifications`

**Objectif :** Renvoie une boîte de réception paginée avec un filtre `state=all|read|unread` facultatif.

**Autorisation :** `notifications:read`. Uniquement les notifications du propriétaire de la clé.

**Paramètres de requête :** `page[after]`, `page[limit]`, `state`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications?page[limit]=1&state=all"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
      "user_id": 1,
      "notification_key": "api.manual.example",
      "title": "API workshop reminder",
      "message": "Review the API example before the workshop.",
      "link_url": "/about",
      "source_module": "application",
      "source_reference": "api-manual",
      "dedup_key": "api-manual-notification",
      "data_json": null,
      "read_at": null,
      "created_at": "2026-11-06 22:48:09",
      "updated_at": "2026-11-06 22:48:09",
      "is_read": false,
      "data": []
    }
  ],
  "meta": {
    "request_id": "4e774695d4bd72dc9ac089f709b259bb",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/notifications?page%5Blimit%5D=1&state=all",
    "next": null
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/notifications/read-all`

**Objectif :** Marque toutes les notifications appartenant au propriétaire de la clé comme lues.

**Autorisation :** `notifications:write`. Uniquement les notifications du propriétaire de la clé.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/notifications/read-all"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "updated": 0
  },
  "meta": {
    "request_id": "85f72e80bda9476062b33acf913e71e0"
  },
  "links": {
    "self": "/api/v1/notifications/read-all"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/notifications/{uuid}`

**Objectif :** Supprime définitivement une notification de lecture détenue.

**Autorisation :** `notifications:write`. Uniquement les notifications du propriétaire de la clé.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/notifications/{uuid}`

**Objectif :** Renvoie une notification appartenant au propriétaire de la clé.

**Autorisation :** `notifications:read`. Uniquement les notifications du propriétaire de la clé.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": null,
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": false,
    "data": []
  },
  "meta": {
    "request_id": "c7fcdc0d24f01935cd578d7f201b921b"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/notifications/{uuid}`

**Objectif :** Définit l'état lu/non lu d'une notification détenue.

**Autorisation :** `notifications:write`. Uniquement les notifications du propriétaire de la clé.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `read`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "read": true
}' \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": "2026-11-06 22:49:03",
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": true,
    "data": []
  },
  "meta": {
    "request_id": "6a1d831097fc07de5556f61468f959b6"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/me/follow-preferences`

**Objectif :** Renvoie les préférences combinées de canal personnel et de cadence.

**Autorisation :** `follows:read`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": null,
    "email_enabled": false
  },
  "meta": {
    "request_id": "98b2df0845176673b3e4ccdf749b2a12"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/me/follow-preferences`

**Objectif :** Met à jour partiellement les préférences personnelles sans modifier les lignes d'un autre utilisateur.

**Autorisation :** `follows:write`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `email_enabled`, `email_mode`, `notify_own_changes`, `theme_mode`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "email_enabled": false,
  "email_mode": "off",
  "notify_own_changes": false
}' \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": "2026-11-06 22:49:04",
    "email_enabled": false
  },
  "meta": {
    "request_id": "0073fb95a0562c45f0f28bdebd4dcd34"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/me/follows`

**Objectif :** Renvoie les suivis personnels filtrés ACL.

**Autorisation :** `follows:read`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Paramètres de requête :** `search`, `type`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows?type=page&search=API"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
      "user_id": 1,
      "target_type": "page",
      "target_id": "36",
      "workspace_id": 4,
      "page_id": 36,
      "document_id": "api-plan-radionice",
      "label_snapshot": "Plan API radionice",
      "email_mode_override": null,
      "created_at": "2026-11-06 22:50:09",
      "updated_at": "2026-11-06 22:50:09",
      "accessible": true,
      "label": "Plan API radionice",
      "url": "/workspace/api-ogledno-podrucje/api-plan-radionice",
      "following": true,
      "automatic_excluded": false
    }
  ],
  "meta": {
    "request_id": "50ba4b674bc6f0ab007022025f0f4ecd"
  },
  "links": {
    "self": "/api/v1/me/follows?type=page&search=API"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/me/follows`

**Objectif :** Ajoute un suivi détenu après la vérification du domaine ACL.

**Autorisation :** `follows:write`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `document_id`, `email_mode_override`, `label`, `target_id`, `target_type`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "target_type": "page",
  "target_id": "36",
  "label": "API workshop plan"
}' \
  "$BASE_URL/api/v1/me/follows"
```

Réponse réelle : **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
    "user_id": 1,
    "target_type": "page",
    "target_id": "36",
    "workspace_id": 4,
    "page_id": 36,
    "document_id": "api-plan-radionice",
    "label_snapshot": "Plan API radionice",
    "email_mode_override": null,
    "created_at": "2026-11-06 22:50:09",
    "updated_at": "2026-11-06 22:50:09",
    "accessible": true,
    "label": "Plan API radionice",
    "url": "/workspace/api-ogledno-podrucje/api-plan-radionice"
  },
  "meta": {
    "request_id": "21c4c68788859bf941d61d7d43fc8e8b"
  },
  "links": {
    "self": "/api/v1/me/follows"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/me/follows/{type}/{targetId}`

**Objectif :** Supprime uniquement un suivi appartenant au propriétaire de la clé.

**Autorisation :** `follows:write`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Chemin :** `type`, `targetId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows/page/36"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/me/personal-workspace`

**Objectif :** Renvoie l'espace de travail personnel du propriétaire de la clé API sans les données des autres utilisateurs.

**Autorisation :** `workspace:read`. Uniquement les suivis/préférences du propriétaire de la clé ; l'accès actuel aux ressources suivies est revérifié.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/personal-workspace"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "exists": false,
    "deleted": false,
    "automatic_creation_enabled": true,
    "self_creation_enabled": false,
    "can_create": false,
    "workspace": null
  },
  "meta": {
    "request_id": "2d5ab5b5583445fea4b560c4645137cd"
  },
  "links": {
    "self": "/api/v1/me/personal-workspace"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.
