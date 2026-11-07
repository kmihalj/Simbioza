# API: guida introduttiva e regole condivise

<a id="api--getting-started-and-shared-rules"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

API collega Simbioza ad altri sistemi. Un amministratore innanzitutto installa e abilita il modulo API, quindi rilascia una chiave a un utente attivo reale. Una chiave ha ambiti selezionati, scadenza opzionale e restrizioni IP. Si applicano ancora le attuali autorizzazioni utente: un ambito amministrativo non trasforma un utente normale in un amministratore.

La [guida dell'amministratore](admin-api_it.md) tratta il rilascio, l'approvazione, la rotazione e la revoca delle chiavi. Questo capitolo riguarda l'integrazione delle applicazioni. Ogni comando e risposta visualizzata è stata verificata con richieste HTTP reali e dati sintetici isolati. ID, timestamp e ETag differiranno a seconda dell'installazione; utilizzare i valori restituiti dalle proprie richieste. I segreti vengono sostituiti con `[REDACTED]`.

<a id="preparation"></a>

## Preparazione

Imposta l'indirizzo di installazione, inclusa la sua sottodirectory, senza una barra finale. Mantenere la chiave fuori dal codice sorgente e dai repository pubblici.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

I comandi utilizzano `curl --globoff` quindi le parentesi nei parametri come `page[limit]` non vengono interpretate come modelli URL. `jq` seleziona solo estratti leggibili da risposte di grandi dimensioni; Simbioza non lo richiede. Invia la chiave in `Authorization: Bearer`, mai nell'URL o in un cookie del browser.

<a id="request-bodies-and-responses"></a>

## Corpi delle richieste e risposte

Le scritture usano `Content-Type: application/json` e un oggetto di primo livello con campi denominati. `{}` è un oggetto valido, anche se un'operazione può richiedere campi specifici. `[]`, i valori scalari e i nomi numerici dei campi di primo livello non sono validi; le liste annidate restano supportate. Un esempio può omettere il corpo quando l'operazione non lo legge. Le modifiche ai sondaggi richiedono JSON e le relative informazioni di versione.

Le risposte JSON riuscite usano `data`, `meta` e `links`. `meta.request_id` identifica una richiesta nei registri. Una risposta 204 non ha un corpo JSON. OpenAPI restituisce direttamente il documento; allegati ed esportazioni CSV, ICS e ZIP restituiscono file. Controlla `Content-Type` prima di decodificare JSON.

<a id="pagination"></a>

## Impaginazione

Molti elenchi utilizzano `page[limit]` (predefinito 50, massimo 100) e un opaco `page[after]`. Segui `links.next` finché non diventa `null`; non costruire o modificare i cursori. Un cursore non valido potrebbe restituire la prima pagina. La paginazione è specifica per risorsa: il sondaggio utilizza `page/per_page`, la ricerca utilizza anche `page/per_page` e i riepiloghi dell'area di lavoro utilizzano `page/limit`. Gli elementi seguiti personali restituiscono un elenco limitato senza il contratto del cursore.

<a id="concurrent-updates"></a>

## Aggiornamenti simultanei

Per i percorsi contrassegnati con `If-Match`, prima GET la risorsa o bozza corrente e leggere la sua intestazione HTTP `ETag`. Invia il valore esatto, comprese le virgolette, nell'intestazione `If-Match` della scrittura successiva. Gli esempi chiamano quella variabile `ETAG`. Non utilizzare ETag di un'altra lingua o ETag di una pagina pubblicata quando si aggiorna una bozza. Per impostazione predefinita, una precondizione mancante restituisce 428 e una obsoleta 412. Le bozze HTML utilizzano inoltre `draft_revision`. Survey utilizza i propri `version` e `revision` invece degli ETag.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

Ciò dimostra il salvataggio delle intestazioni di risposta. Ottieni `ETAG` dalla risorsa appropriata GET, non da `/me`.

<a id="repeated-writes-and-limits"></a>

## Scritture e limiti ripetuti

Le scritture JSON possono utilizzare un `Idempotency-Key` univoco. La stessa chiave e richiesta riproducono la risposta precedente; riutilizzare la chiave con un corpo diverso restituisce 409. I caricamenti in più parti e binari non supportano questa intestazione e restituiscono 422. Il valore predefinito è 120 richieste al minuto per chiave, con intestazioni `X-RateLimit-*` e `Retry-After` su 429; gli amministratori possono configurare un altro limite. Il corpo massimo predefinito di JSON è 2MiB e i singoli moduli applicano limiti aggiuntivi. CORS è un criterio amministrativo separato e non sostituisce l'autenticazione Bearer.

Leggi OpenAPI con una chiave valida. Elenca i percorsi e gli ambiti registrati dai moduli attivi, ma i suoi schemi generici non descrivono completamente i parametri aziendali, i campi multiparte o le risposte binarie. Utilizza anche i capitoli delle risorse. CalDAV e i collegamenti ICS pubblici sono protocolli separati esterni a tale documento.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1`

**Scopo:** restituisce la versione, le risorse disponibili e gli ambiti del modulo abilitato.

**Autorizzazione:** una chiave API valida. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

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

**Errori verificati per questa operazione:** 401. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/me`

**Scopo:** Restituisce identità, ambiti e dati utente pubblici senza campi password.

**Autorizzazione:** una chiave API valida. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 429. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/openapi.json`

**Scopo:** Restituisce OpenAPI 3.1 JSON per i moduli attualmente abilitati.

**Autorizzazione:** una chiave API valida. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

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

**Errori verificati per questa operazione:** 401. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

<a id="errors-and-diagnostics"></a>

## Errori e diagnostica

Controllare lo stato HTTP prima di leggere `data`. Le richieste rifiutate restituiscono `application/problem+json`, con `status`, `code`, `detail` e `request_id`. Una risposta 404 per il contenuto privato non conferma se la risorsa esiste. Non disabilitare i controlli delle autorizzazioni o la protezione del modulo Web CSRF per aggirare un errore API. L'autenticazione Bearer è separata dalla sessione web.

| HTTP | Significato |
|---|---|
| 400 | JSON o richiesta non valida |
| 401 | chiave mancante, non valida o non consentita |
| 403 | ambito mancante o autorizzazione corrente |
| 404 | la risorsa non è disponibile per questo utente o è mancante |
| 409 | conflitto di versione, stato o idempotenza |
| 412 | If-Match obsoleto |
| 415 | tipo di contenuto del corpo non vuoto non supportato |
| 422 | campi aziendali non validi o caricamento non supportato |
| 428 | If-Match obbligatorio mancante |
| 429 | limite di richieste raggiunto; rispettare Retry-After |

<a id="without-authentication"></a>

### Senza autenticazione

Risposta effettiva HTTP 401:

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

### Precondizione dell'aggiornamento mancante

Risposta effettiva HTTP 428:

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

### Stessa chiave, corpo diverso

Risposta effettiva HTTP 409:

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

### Limite di richieste raggiunto

Risposta effettiva HTTP 429:

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

## PHP — un piccolo client completo

Questo è un esempio PHP separato; non è necessario tradurre i comandi curl in codice. Salva la funzione come `api-client.php`. È richiesta l'estensione PHP cURL. Gli URL di produzione devono utilizzare HTTPS; HTTP è consentito solo per lo sviluppo locale di loopback. La funzione supporta JSON, risposte in più parti e binarie, non segue i reindirizzamenti e genera errori HTTP. Leggi i segreti dal tuo archivio sicuro dei segreti o dalle variabili d'ambiente.

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

Utilizzo (impostare prima le variabili di ambiente da Preparazione):

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Output effettivo sull'account amministratore sintetico:

```text
Administrator
```

Passare i campi denominati come quinto argomento per una scrittura JSON; utilizzare `[]` per un oggetto vuoto (l'helper lo codifica come `{}`). Aggiungi `['If-Match: ' . $etag]` come sesto argomento per ETag. Multiparte è il settimo argomento, ad esempio `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, con il quinto argomento `null`. Non impostare manualmente il tipo di contenuto multiparte. Salva file con `file_put_contents()` utilizzando `body`; decodificato JSON è in `json`. Percorsi delle risorse, payload e autorizzazioni sono gli stessi degli esempi di curl negli altri capitoli.
