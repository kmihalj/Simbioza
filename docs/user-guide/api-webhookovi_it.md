# API: webhook e consegne

<a id="api--webhooks-and-deliveries"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

Un webhook informa un altro sistema di un evento senza interrogare ripetutamente gli elenchi. Un amministratore deve abilitare i webhook e consentire gli host di destinazione. `webhooks:read` o `webhooks:manage` è obbligatorio; un non amministratore gestisce le sottoscrizioni create dalla stessa chiave API, mentre un amministratore con l'ambito corrispondente può gestire tutte le sottoscrizioni.

POST richiede `name`, un `target_url` HTTPS consentito, una lista `events` (per esempio `['*']`) e il booleano facoltativo `active`. Il segreto di firma HMAC viene restituito soltanto alla creazione o alla rotazione; conservalo nel sistema ricevente. Le altre risposte GET non lo espongono. PATCH, DELETE, rotate-secret e il nuovo tentativo di consegna usano ETag.

Una mutazione del contenuto aggiunge una consegna in coda. GET `deliveries` mostra i tentativi, lo stato e le risposte di destinazione. `retry` riprogramma una consegna esistente; ciò non significa che il sistema remoto lo abbia ricevuto. Un operatore pianificato separatamente esegue la consegna e deve essere configurato sul server. Questi esempi REST sono stati verificati rispetto alla coda senza invio a un indirizzo esterno.

La documentazione tecnica del modulo API descrive le firme e i payload dei webhook. Il destinatario deve verificare la firma HMAC-SHA256 rispetto ai byte della richiesta originale e proteggersi dalla riproduzione. La chiave API che gestisce una sottoscrizione non è il segreto di firma del webhook.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/webhooks`

**Scopo:** Restituisce un elenco impaginato di abbonamenti disponibili per la chiave corrente.

**Autorizzazione:** `webhooks:read`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/webhooks`

**Scopo:** crea una sottoscrizione e restituisce il nuovo segreto di firma solo in questa risposta.

**Autorizzazione:** `webhooks:manage`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `active`, `events`, `name`, `target_url`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

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

Risposta effettiva: **HTTP 201**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/webhooks/{uuid}`

**Scopo:** rimuove definitivamente una sottoscrizione con protezione della concorrenza ottimistica.

**Autorizzazione:** `webhooks:manage`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/webhooks/{uuid}`

**Scopo:** Restituisce una sottoscrizione consentita senza il segreto della firma.

**Autorizzazione:** `webhooks:read`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403, 404. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/webhooks/{uuid}`

**Scopo:** Aggiorna un abbonamento solo quando il client invia l'If-Match corrente.

**Autorizzazione:** `webhooks:manage`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `active`, `events`, `name`, `target_url`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/webhooks/{uuid}/deliveries`

**Scopo:** Restituisce la cronologia di consegna impaginata per un abbonamento.

**Autorizzazione:** `webhooks:read`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}`

**Scopo:** Restituisce un tentativo di consegna.

**Autorizzazione:** `webhooks:read`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`, `deliveryUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry`

**Scopo:** Riaccoda una consegna esistente dopo averne controllato ETag.

**Autorizzazione:** `webhooks:manage`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`, `deliveryUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/webhooks/{uuid}/rotate-secret`

**Scopo:** Ruota il segreto con If-Match e restituisce il nuovo valore solo una volta.

**Autorizzazione:** `webhooks:manage`. Chiave API attiva e policy di destinazione consentita; i non amministratori sono limitati agli abbonamenti creati da questa chiave, mentre gli amministratori possono gestirli tutti.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

<a id="receiver--signatures-and-repeated-messages"></a>

## Destinatario: firme e messaggi ripetuti

Il server invia un POST con queste intestazioni. Verificare la firma prima della decodifica JSON; la modifica degli spazi bianchi o la ricodifica JSON modifica la firma.

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

La tolleranza di 300 secondi è la politica di questo esempio. Mantieni i valori `X-HeartPhrame-Webhook-Id` elaborati e non eseguire la stessa azione aziendale due volte. Una firma valida da sola non impedisce la riproduzione.

I selettori di eventi supportano un nome esatto, uno spazio dei nomi con `.*` (ad esempio `pages.*`) o `*`. Il corpo del webhook contiene l'identità dell'evento e i metadati di mutazione ripuliti; non invia la richiesta originale o l'intero contenuto del documento.

<a id="server-worker"></a>

## Processo di lavoro del server

Questi sono i comandi CLI dell'amministratore sul tuo server, separati dalle richieste REST:

```sh
vendor/bin/hph api webhooks:status
vendor/bin/hph api webhooks:worker --batch-size=20
vendor/bin/hph api webhooks:worker --watch --sleep=5
```

`--watch` funziona continuamente e deve essere supervisionato in produzione. Evita consegne di prova manuali non necessarie. Per un evento non riuscito, controlla la cronologia delle consegne e utilizza REST `retry` dopo aver risolto la causa.
