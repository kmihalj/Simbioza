# API — utilisateurs et groupes

<a id="api--users-and-groups"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

Ces opérations nécessitent un propriétaire de clé d'administrateur et la portée `users:*` ou `groups:*` appropriée. Ils gèrent les comptes locaux et les groupes locaux. Un utilisateur ordinaire ne peut pas lire le répertoire administratif simplement en disposant de ces étendues.

Un nouvel utilisateur a besoin de `login_identifier` et d'un mot de passe valide ; un groupe a besoin de `name`. `attributes` contient les attributs de profil. `display_name`, `first_name`, `last_name` et `email` prennent également en charge les entrées de niveau supérieur. `provider_access` mappe les méthodes de connexion autorisées (`local`, `saml`, `cas`, `oidc`, `oauth2`). `group_ids` est une liste d'ID de groupe local numériques. L'adhésion PUT remplace la liste entière plutôt que d'ajouter un groupe.

PATCH nécessite au moins un champ modifié. Les mises à jour d’utilisateurs/groupes, le remplacement et la suppression d’adhésions nécessitent le ETag actuel. L'utilisateur DELETE désactive le compte et révoque ses clés API ; il n'efface pas toutes les données commerciales associées. Les comptes/groupes système et gérés en externe ont des restrictions supplémentaires. Remplacez `[REDACTED]` dans l'exemple par votre propre mot de passe fort.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/groups`

**Objectif :** Renvoie la liste des groupes locaux.

**Autorisation :** `groups:read`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Paramètres de requête :** `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups?page[limit]=1"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/groups`

**Objectif :** Crée un groupe local non système.

**Autorisation :** `groups:manage`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `is_enabled`, `name`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 400, 401, 403, 409, 415. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/groups/{groupId}`

**Objectif :** Supprime un groupe local non système et ses adhésions.

**Autorisation :** `groups:manage`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `groupId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/groups/5"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/groups/{groupId}`

**Objectif :** Renvoie un groupe local.

**Autorisation :** `groups:read`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `groupId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups/5"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/groups/{groupId}`

**Objectif :** Met à jour un groupe local non système.

**Autorisation :** `groups:manage`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `groupId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `is_enabled`, `name`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 400, 401, 403, 412, 422, 428. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/users`

**Objectif :** Renvoie une liste d'utilisateurs locaux paginée par curseur.

**Autorisation :** `users:read`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Paramètres de requête :** `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users?page[limit]=1"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/users`

**Objectif :** Crée un utilisateur local.

**Autorisation :** `users:create`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/users/{userId}`

**Objectif :** Désactive un utilisateur local et révoque ses clés API.

**Autorisation :** `users:delete`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `userId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/users/4"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/users/{userId}`

**Objectif :** Renvoie un utilisateur local.

**Autorisation :** `users:read`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `userId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users/4"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/users/{userId}`

**Objectif :** Met à jour partiellement un utilisateur local.

**Autorisation :** `users:update`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `userId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PUT /api/v1/users/{userId}/groups`

**Objectif :** Remplace les adhésions manuelles d'un utilisateur local.

**Autorisation :** `groups:manage`. Propriétaire de la clé administrateur ; la portée seule est insuffisante.

**Chemin :** `userId`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `group_ids`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.
