# API — webhooks et envois

<a id="api--webhooks-and-deliveries"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

Un webhook informe un autre système d'un événement sans interroger les listes à plusieurs reprises. Un administrateur doit activer les webhooks et autoriser les hôtes de destination. `webhooks:read` ou `webhooks:manage` est requis ; un non-administrateur gère les abonnements créés par la même clé API, tandis qu'un administrateur ayant la portée correspondante peut gérer tous les abonnements.

POST nécessite `name`, un HTTPS `target_url` autorisé, une liste `events` (par exemple `['*']`) et un booléen facultatif `active`. Le secret de signature HMAC est renvoyé uniquement lors de sa création ou de sa rotation ; stockez-le sur le système de réception. Les autres réponses GET ne l'exposent pas. PATCH, DELETE, rotation-secret et nouvelle tentative de livraison utilisent des ETags.

Une mutation de contenu ajoute une livraison en file d'attente. GET `deliveries` affiche les tentatives, l'état et les réponses de destination. `retry` replanifie une livraison existante ; cela ne signifie pas que le système distant l'a reçu. Un travailleur planifié séparément effectue la livraison et doit être configuré sur le serveur. Ces exemples REST ont été vérifiés par rapport à la file d'attente sans envoi à une adresse externe.

La documentation technique du modul’API décrit les signatures et les charges utiles des webhooks. Le destinataire doit vérifier la signature HMAC-SHA256 par rapport aux octets de la demande d'origine et se protéger contre la relecture. La clé API gérant un abonnement n'est pas le secret de signature du webhook.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/webhooks`

**Objectif :** Renvoie une liste paginée des abonnements disponibles pour la clé actuelle.

**Autorisation :** `webhooks:read`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Paramètres de requête :** `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks?page[limit]=1"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "b621cf9d278e6902b10f96a7c2f6d79e",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/webhooks?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/webhooks`

**Objectif :** Crée un abonnement et renvoie son nouveau secret de signature uniquement dans cette réponse.

**Autorisation :** `webhooks:manage`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `active`, `events`, `name`, `target_url`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API observer",
  "target_url": "https://example.com/hooks/simbioza",
  "events": [
    "*"
  ],
  "active": true
}' \
  "$BASE_URL/api/v1/webhooks"
```

Réponse réelle : **HTTP 201**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "673b6de22148feaba0dc6b194655cda1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `DELETE /api/v1/webhooks/{uuid}`

**Objectif :** Supprime définitivement un abonnement avec une protection de concurrence optimiste.

**Autorisation :** `webhooks:manage`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Réponse réelle : **HTTP 204**. Le corps est vide.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/webhooks/{uuid}`

**Objectif :** Renvoie un abonnement autorisé sans son secret de signature.

**Autorisation :** `webhooks:read`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "ce22c9cc6d5493b57b2b1026ddc69a27"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403, 404. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/webhooks/{uuid}`

**Objectif :** Met à jour un abonnement uniquement lorsque le client envoie le If-Match actuel.

**Autorisation :** `webhooks:manage`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `active`, `events`, `name`, `target_url`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "name": "API observer updated"
}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer updated",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a50a566b4cb87453a1d4835a86d8ab96"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/webhooks/{uuid}/deliveries`

**Objectif :** Renvoie l'historique de livraison paginé pour un abonnement.

**Autorisation :** `webhooks:read`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `page[after]`, `page[limit]`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page[limit]=1"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": [
    {
      "id": 7,
      "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
      "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
      "event": "calendar_events.deleted",
      "status": "pending",
      "attempts": 0,
      "available_at": "2026-11-06 23:08:58",
      "delivered_at": null,
      "response_status": null,
      "response_body": null,
      "last_error": null,
      "created_at": "2026-11-06 23:08:58",
      "updated_at": "2026-11-06 23:08:58"
    }
  ],
  "meta": {
    "request_id": "79ac9c3580e735f6146257343e05f331",
    "page": {
      "limit": 1,
      "total": 7,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1",
    "next": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}`

**Objectif :** Renvoie une tentative de livraison.

**Autorisation :** `webhooks:read`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`, `deliveryUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a1a068157f70f58e25aaca7e441ee20b"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry`

**Objectif :** Remet en file d'attente une diffusion existante après avoir vérifié son ETag.

**Autorisation :** `webhooks:manage`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`, `deliveryUuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "186121f98aed702b812e57d49f3b06c8"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/webhooks/{uuid}/rotate-secret`

**Objectif :** Fait pivoter le secret avec If-Match et renvoie la nouvelle valeur une seule fois.

**Autorisation :** `webhooks:manage`. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Chemin :** `uuid`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

**Avant d'écrire :** actualisez le GET et définissez `ETAG` à partir de son en-tête `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer updated",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:58"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "205b440690d3939f8600c5588c94c0b1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

<a id="receiver--signatures-and-repeated-messages"></a>

## Destinataire – signatures et messages répétés

Le serveur envoie un POST avec ces en-têtes. Vérifiez la signature avant le décodage JSON ; la modification des espaces ou le réencodage JSON modifie la signature.

```text
Content-Type: application/json
X-HeartPhrame-Webhook-Id: <event UUID>
X-HeartPhrame-Webhook-Event: <event name>
X-HeartPhrame-Webhook-Timestamp: <Unix seconds>
X-HeartPhrame-Webhook-Signature: v1=<hex HMAC-SHA256>
```

```php
<?php
/**
 * EN: Checks a webhook signature over the unmodified raw body.
 * HR: Provjerava potpis webhooka nad neizmijenjenim izvornim tijelom.
 */
function verifySimbiozaWebhook(
    string $rawBody,
    string $timestamp,
    string $signature,
    #[SensitiveParameter] string $secret,
    ?int $now = null,
): bool {
    if (!ctype_digit($timestamp) || abs(($now ?? time()) - (int)$timestamp) > 300) {
        return false;
    }
    $expected = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    return hash_equals($expected, $signature);
}
```

La tolérance de 300 secondes est la politique de cet exemple. Conservez les valeurs `X-HeartPhrame-Webhook-Id` traitées et n’exécutez pas deux fois la même action commerciale. Une signature valide à elle seule n’empêche pas la relecture.

Les sélecteurs d'événements prennent en charge un nom exact, un espace de noms avec `.*` (par exemple `pages.*`) ou `*`. Le corps du webhook contient l'identité de l'événement et les métadonnées de mutation nettoyées ; il n'envoie pas la demande originale ni le contenu complet du document.

<a id="server-worker"></a>

## Employé de serveur

Il s'agit de commandes d'administrateur CLI sur votre serveur, distinctes des requêtes REST :

```sh
vendor/bin/hph api webhooks:status
vendor/bin/hph api webhooks:worker --batch-size=20
vendor/bin/hph api webhooks:worker --watch --sleep=5
```

`--watch` s'exécute en continu et doit être supervisé en production. Évitez les livraisons de tests manuels inutiles. Pour un événement ayant échoué, inspectez l’historique des livraisons et utilisez REST `retry` après avoir corrigé la cause.
