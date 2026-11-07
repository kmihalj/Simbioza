# API: attività nel contenuto

<a id="api--tasks-in-content"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

Le attività fanno parte del contenuto della pagina pubblicata. API non crea la loro definizione; gli autori lo aggiungono nell'editor HTML o tramite contenuto strutturato nella [pagina API](api-html-stranice_it.md). `task:read` richiede l'accesso alla pagina pubblicata. `task:write` controlla inoltre `toggle_scope` dell'elenco e le autorizzazioni dell'utente corrente.

PUT richiede un valore booleano `completed` e l'attività corrente GET ETag. La modifica dello stato non crea un'altra versione della pagina; la storia registra l'attore. La cronologia utilizza l'impaginazione del cursore all'interno di una finestra di record disponibile limitata. Selezionare la lingua con `lang`.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/pages/{documentId}/tasks`

**Scopo:** Restituisce tutte le attività dalla pubblicazione corrente della pagina.

**Autorizzazione:** `task:read`. Accesso alla pagina pubblicata e autorizzazione in base alla regola di modifica dello stato dell'elenco attività.

**Percorso:** `documentId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`, `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks?lang=hr&page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}`

**Scopo:** Restituisce un'attività e il suo stato corrente.

**Autorizzazione:** `task:read`. Accesso alla pagina pubblicata e autorizzazione in base alla regola di modifica dello stato dell'elenco attività.

**Percorso:** `documentId`, `taskUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}/history`

**Scopo:** restituisce il registro dei cambiamenti di stato di un'attività.

**Autorizzazione:** `task:read`. Accesso alla pagina pubblicata e autorizzazione in base alla regola di modifica dello stato dell'elenco attività.

**Percorso:** `documentId`, `taskUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`, `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/pages/{documentId}/tasks/{taskUuid}/state`

**Scopo:** imposta lo stato dell'attività senza creare una nuova versione della pagina.

**Autorizzazione:** `task:write`. Accesso alla pagina pubblicata e autorizzazione in base alla regola di modifica dello stato dell'elenco attività.

**Percorso:** `documentId`, `taskUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`.

**Corpo/campi supportati:** `completed`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 400, 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
