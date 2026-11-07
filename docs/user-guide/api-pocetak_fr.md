# API — premiers pas et règles communes

<a id="api--getting-started-and-shared-rules"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

L’API connecte Simbioza à d'autres systèmes. Un administrateur installe et active d'abord le modul’API, puis délivre une clé à un utilisateur actif réel. Une clé a des étendues sélectionnées, une expiration facultative et des restrictions IP. Les autorisations utilisateur actuelles s'appliquent toujours : une portée administrative ne transforme pas un utilisateur ordinaire en administrateur.

Le [guide de l'administrateur](admin-api_fr.md) couvre l'émission, l'approbation, la rotation et la révocation des clés. Ce chapitre couvre l'intégration des applications. Chaque commande et réponse affichée a été vérifiée avec de vraies requêtes HTTP et des données synthétiques isolées. Les identifiants, horodatages et ETags différeront selon votre installation ; utilisez les valeurs renvoyées par vos propres requêtes. Les secrets sont remplacés par `[REDACTED]`.

<a id="preparation"></a>

## Préparation

Définissez votre adresse d'installation, y compris son sous-répertoire, sans barre oblique finale. Gardez la clé hors du code source et des référentiels publics.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

Les commandes utilisent `curl --globoff` afin que les crochets dans les paramètres tels que `page[limit]` ne soient pas interprétés comme des modèles d'URL. `jq` sélectionne uniquement des extraits lisibles de réponses volumineuses ; Simbioza ne l'exige pas. Envoyez la clé dans `Authorization: Bearer`, jamais dans l'URL ou un cookie du navigateur.

<a id="request-bodies-and-responses"></a>

## Corps de requête et réponses

Les écritures utilisent `Content-Type: application/json` et un objet de niveau supérieur avec des champs nommés. `{}` est un objet valide, bien qu'une opération puisse nécessiter des champs spécifiques. `[]`, les scalaires et les noms de champs numériques de niveau supérieur ne sont pas valides ; les listes imbriquées restent prises en charge. Un exemple peut omettre un corps lorsque l'opération ne le lit pas. Les mutations du sondage nécessitent JSON et leurs informations de version.

Les réponses JSON réussies utilisent `data`, `meta` et `links`. `meta.request_id` identifie une demande dans les journaux. Une réponse 204 n’a pas de corps JSON. OpenAPI renvoie le document directement ; pièces jointes, CSV, ICS et ZIP exportent les fichiers de retour. Vérifiez `Content-Type` avant de décoder JSON.

## Pagination

De nombreuses listes utilisent `page[limit]` (par défaut 50, maximum 100) et un `page[after]` opaque. Suivez `links.next` jusqu'à ce qu'il soit `null` ; ne construisez pas et ne modifiez pas de curseurs. Un curseur invalide peut renvoyer la première page. La pagination est spécifique à la ressource : le sondage utilise `page/per_page`, la recherche utilise également `page/per_page` et les résumés d'espace de travail utilisent `page/limit`. Les suivis personnels renvoient une liste limitée sans le contrat du curseur.

<a id="concurrent-updates"></a>

## Mises à jour simultanées

Pour les routes marqués `If-Match`, commencez par GET la ressource ou le brouillon actuel et lisez son en-tête HTTP `ETag`. Envoyez la valeur exacte, y compris les guillemets, dans l'en-tête `If-Match` de la prochaine écriture. Les exemples appellent cette variable `ETAG`. N'utilisez pas le ETag d'une autre langue ou le ETag d'une page publiée lors de la mise à jour d'un brouillon. Par défaut, une précondition manquante renvoie 428 et une précondition obsolète 412. Les brouillons HTML utilisent également `draft_revision`. Survey utilise ses propres `version` et `revision` au lieu de ETags.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

Cela démontre la sauvegarde des en-têtes de réponse. Obtenez `ETAG` auprès de la ressource appropriée GET, et non auprès de `/me`.

<a id="repeated-writes-and-limits"></a>

## Écritures répétées et limites

Les écritures JSON peuvent utiliser un `Idempotency-Key` unique. La même clé et demande de rejouer la réponse précédente ; la réutilisation de la clé avec un corps différent renvoie 409. Les téléchargements en plusieurs parties et binaires ne prennent pas en charge cet en-tête et renvoient 422. La valeur par défaut est de 120 requêtes par minute et par clé, avec les en-têtes `X-RateLimit-*` et `Retry-After` sur 429 ; les administrateurs peuvent configurer une autre limite. Le corps JSON maximum par défaut est de 2 Mo, et les modules individuels appliquent des limites supplémentaires. CORS est une stratégie d'administrateur distincte et ne remplace pas l'authentification du porteur.

Lisez OpenAPI avec une clé valide. Il répertorie les routes et les étendues enregistrées à partir des modules actifs, mais ses schémas génériques ne décrivent pas entièrement les paramètres métier, les champs en plusieurs parties ou les réponses binaires. Utilisez également les chapitres de ressources. CalDAV et les liens ICS publics sont des protocoles distincts en dehors de ce document.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1`

**Objectif :** Renvoie la version, les ressources disponibles et les étendues du module activé.

**Autorisation :** une clé API valide. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Réponse réelle : **HTTP 200**. Il s'agit de la sortie réelle du filtre `jq` affiché.

```json
{
  "data": {
    "name": "HeartPhrame API",
    "version": "v1",
    "resources": [
      "users",
      "groups",
      "audit",
      "webhooks",
      "notifications",
      "page",
      "attachment",
      "task",
      "workspace",
      "workspace-search",
      "surveys",
      "calendar",
      "follows"
    ],
    "scopes": [
      "users:read",
      "users:create",
      "users:update",
      "users:delete",
      "groups:read",
      "groups:manage",
      "audit:read",
      "webhooks:read",
      "webhooks:manage",
      "notifications:read",
      "notifications:write",
      "page:read",
      "page:write",
      "page:publish",
      "attachment:read",
      "attachment:write",
      "task:read",
      "task:write",
      "workspace:read",
      "workspace:manage",
      "workspace-search:read",
      "surveys:read",
      "surveys:write",
      "surveys:publish",
      "surveys:results",
      "surveys:export",
      "calendar:read",
      "calendar:write",
      "follows:read",
      "follows:write"
    ],
    "security": {
      "authentication": "bearer",
      "rate_limit_headers": true,
      "idempotency_key_for_writes": true,
      "problem_format": "RFC 9457",
      "webhooks": {
        "enabled": true,
        "delivery": "asynchronous",
        "signature": "hmac-sha256"
      }
    }
  },
  "meta": {
    "request_id": "cc6b890db5532c15134ac71575045fb5"
  },
  "links": {
    "self": "/api/v1"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/me`

**Objectif :** Renvoie l'identité, les étendues et les données utilisateur publiques sans champs de mot de passe.

**Autorisation :** une clé API valide. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Réponse réelle : **HTTP 200**.

```json
{
  "data": {
    "key": {
      "id": 1,
      "public_id": "72ea299cf26253dc485c6a95",
      "scopes": [
        "users:read",
        "users:create",
        "users:update",
        "users:delete",
        "groups:read",
        "groups:manage",
        "audit:read",
        "webhooks:read",
        "webhooks:manage",
        "notifications:read",
        "notifications:write",
        "page:read",
        "page:write",
        "page:publish",
        "attachment:read",
        "attachment:write",
        "task:read",
        "task:write",
        "workspace:read",
        "workspace:manage",
        "workspace-search:read",
        "surveys:read",
        "surveys:write",
        "surveys:publish",
        "surveys:results",
        "surveys:export",
        "calendar:read",
        "calendar:write",
        "follows:read",
        "follows:write"
      ]
    },
    "user": {
      "id": 1,
      "login_identifier": "Admin",
      "display_name": "Administrator",
      "email": "admin@example.test",
      "first_name": "Admin",
      "last_name": "Simbioza",
      "group_ids": [
        1
      ],
      "group_keys": [
        "administrator"
      ],
      "is_admin": true,
      "is_active": true,
      "auth_source": "local"
    }
  },
  "meta": {
    "request_id": "bd11f1fa32846abeb0f47349982394fb"
  },
  "links": {
    "self": "/api/v1/me"
  }
}
```

**Erreurs vérifiées pour cette opération :** 401, 429. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/openapi.json`

**Objectif :** Renvoie OpenAPI 3.1 JSON pour les modules actuellement activés.

**Autorisation :** une clé API valide. Clé API active et stratégie de destination autorisée ; les non-administrateurs sont limités aux abonnements créés par cette clé, tandis que les administrateurs peuvent tous gérer.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Réponse réelle : **HTTP 200**. Il s'agit de la sortie réelle du filtre `jq` affiché.

```json
{
  "openapi": "3.1.0",
  "info": {
    "title": "HeartPhrame API",
    "version": "1.0.0",
    "description": "Versioned modular API. Domain ACL is always evaluated in addition to key scopes."
  },
  "path_count": 77,
  "security": [
    {
      "bearerAuth": []
    }
  ],
  "components": {
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "HeartPhrame API key"
      }
    }
  }
}
```

**Erreurs vérifiées pour cette opération :** 401. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

<a id="errors-and-diagnostics"></a>

## Erreurs et diagnostics

Vérifiez l’état HTTP avant de lire `data`. Les demandes rejetées renvoient `application/problem+json`, avec `status`, `code`, `detail` et `request_id`. A404 pour le contenu privé ne confirme pas si la ressource existe. Ne désactivez pas les contrôles d’autorisation ou la protection du formulaire Web CSRF pour contourner une erreur API. L'authentification du porteur est distincte de la session Web.

| HTTP | Signification |
|---|---|
| 400 | JSON ou demande invalide |
| 401 | clé manquante, invalide ou non autorisée |
| 403 | Portée manquante ou autorisation actuelle |
| 404 | la ressource n'est pas disponible pour cet utilisateur ou est manquante |
| 409 | conflit de version, d’état ou d’idempotence |
| 412 | périmé If-Match |
| 415 | type de contenu de corps non vide non pris en charge |
| 422 | Champs professionnels non valides ou téléchargement non pris en charge |
| 428 | requis If-Match manquant |
| 429 | limite de taux atteinte ; suivre Réessayer-Après |

<a id="without-authentication"></a>

### Sans authentification

Réponse réelle HTTP 401 :

```json
{
  "type": "about:blank",
  "title": "Authentication failed",
  "status": 401,
  "detail": "Send a valid active API key as a Bearer token.",
  "code": "invalid_api_key",
  "instance": "/api/v1",
  "request_id": "1022fef5ddfad19a8c78dd5c87f8bf70"
}
```

<a id="missing-update-precondition"></a>

### Condition préalable de mise à jour manquante

Réponse réelle HTTP 428 :

```json
{
  "type": "about:blank",
  "title": "The change precondition was not met",
  "status": 428,
  "detail": "Read the resource before changing it and send its ETag as If-Match.",
  "code": "if_match_required",
  "instance": "/api/v1/workspaces/api-ogledno-podrucje",
  "request_id": "a0153b5a77ba484de02d0c9e35974996"
}
```

<a id="same-key-different-body"></a>

### Même clé, corps différent

Réponse réelle HTTP 409 :

```json
{
  "type": "about:blank",
  "title": "The Idempotency-Key has already been used",
  "status": 409,
  "detail": "The same key must not be used for different requests.",
  "code": "idempotency_key_reused",
  "instance": "/api/v1/groups",
  "request_id": "622b2c534cf92ddc3f402f0de733122a"
}
```

<a id="rate-limit-reached"></a>

### Limite de débit atteinte

Réponse réelle HTTP 429 :

```json
{
  "type": "about:blank",
  "title": "Too many API requests",
  "status": 429,
  "detail": "Wait before the next request.",
  "code": "rate_limit_exceeded",
  "instance": "/api/v1/me",
  "request_id": "a43267398714c9a8ac74f166a959cc41"
}
```

<a id="php--a-complete-small-client"></a>

## PHP — un petit client complet

Il s'agit d'un exemple PHP distinct ; vous n'avez pas besoin de traduire les commandes curl en code. Enregistrez la fonction sous `api-client.php`. L'extension cURL PHP est requise. Les URL de production doivent utiliser HTTPS ; HTTP est autorisé uniquement pour le développement local de bouclage. La fonction prend en charge JSON, les réponses multiparts et binaires, ne suit pas les redirections et génère des erreurs HTTP. Lisez les secrets de votre magasin secret ou de votre environnement.

```php
<?php

declare(strict_types=1);

/**
 * EN: Calls the REST API; redirects are disabled so credentials stay on the configured host.
 * HR: Poziva REST API; preusmjeravanja su isključena kako vjerodajnice ne bi otišle drugom hostu.
 *
 * @param array<string,mixed>|stdClass|null $body
 * @param list<string> $extraHeaders
 * @param array<string,string|CURLFile>|null $multipart
 * @return array{status:int,headers:array<string,string>,json:mixed,body:string}
 */
function simbiozaApi(
    string $baseUrl,
    #[SensitiveParameter] string $key,
    string $method,
    string $path,
    array|stdClass|null $body = null,
    array $extraHeaders = [],
    ?array $multipart = null,
): array {
    if (!extension_loaded('curl')) {
        throw new RuntimeException('PHP cURL extension is required.');
    }
    $url = parse_url($baseUrl);
    $local = in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true);
    if (!is_array($url) || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
        || (($url['scheme'] ?? '') !== 'https' && !$local)
        || !isset($url['host']) || isset($url['user']) || isset($url['pass'])
        || isset($url['query']) || isset($url['fragment']) || trim($key) === '') {
        throw new InvalidArgumentException('Set a valid HTTPS installation URL and API key.');
    }
    if (($path !== '/api/v1' && !str_starts_with($path, '/api/v1/'))
        || str_contains($path, "\r") || str_contains($path, "\n")
        || ($body !== null && $multipart !== null)) {
        throw new InvalidArgumentException('Use an /api/v1 path and one request-body format.');
    }
    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $key, ...$extraHeaders];
    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ];
    if ($multipart !== null) {
        // EN: cURL supplies the multipart boundary. HR: cURL sam postavlja multipart razdjelnik.
        $options[CURLOPT_POSTFIELDS] = $multipart;
    } elseif ($body !== null) {
        // EN: An empty named-field object must be {}, not []. HR: Prazan objekt polja mora biti {}, ne [].
        $options[CURLOPT_POSTFIELDS] = json_encode($body === [] ? new stdClass() : $body, JSON_THROW_ON_ERROR);
        $headers[] = 'Content-Type: application/json';
    }
    $responseHeaders = [];
    $options[CURLOPT_HTTPHEADER] = $headers;
    $options[CURLOPT_HEADERFUNCTION] = static function ($handle, string $line) use (&$responseHeaders): int {
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[strtolower(trim($name))] = trim($value);
        }
        return strlen($line);
    };
    $handle = curl_init(rtrim($baseUrl, '/') . $path);
    curl_setopt_array($handle, $options);
    $raw = curl_exec($handle);
    if ($raw === false) {
        $message = curl_error($handle);
        unset($handle);
        throw new RuntimeException('HTTP transport failed: ' . $message);
    }
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    unset($handle);
    $json = str_contains($responseHeaders['content-type'] ?? '', 'json') && $raw !== ''
        ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
    if ($status >= 400) {
        $detail = is_array($json) ? (string)($json['detail'] ?? 'Request rejected') : 'Request rejected';
        $requestId = $responseHeaders['x-request-id'] ?? '';
        throw new RuntimeException("HTTP $status: $detail (request_id=$requestId)");
    }
    return ['status' => $status, 'headers' => $responseHeaders, 'json' => $json, 'body' => $raw];
}
```

Utilisation (définissez d'abord les variables d'environnement depuis la préparation) :

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Sortie réelle sur le compte administrateur synthétique :

```text
Administrator
```

Transmettez les champs nommés comme cinquième argument pour une écriture JSON ; utilisez `[]` pour un objet vide (l'assistant le code comme `{}`). Ajoutez `['If-Match: ' . $etag]` comme sixième argument pour ETags. Multipart est le septième argument, par exemple `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, avec le cinquième argument `null`. Ne définissez pas manuellement le type de contenu en plusieurs parties. Enregistrez les fichiers avec `file_put_contents()` en utilisant `body` ; JSON décodé est dans `json`. Les chemins de ressources, les charges utiles et les autorisations sont les mêmes que ceux des exemples curl dans les autres chapitres.
