# API — notifiche ed elementi seguiti personali

<a id="api--notifications-and-personal-follows"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

Tutte le operazioni si applicano al proprietario della chiave. Un corpo JSON non può selezionare la posta in arrivo o le preferenze di un altro utente. `notifications:read` legge la posta in arrivo; `notifications:write` modifica lo stato di lettura ed elimina le notifiche già lette. Gli elenchi utilizzano `state=all`, `read` o `unread`, con i parametri del cursore. PATCH invia il booleano `read` con un ETag; `read-all` contrassegna l'intera casella di posta del proprietario letta. Un UUID di un altro utente o mancante non rivela alcun contenuto; l'attuale GET restituisce 422 `notification_validation_failed` per quel caso.

`follows:read`/`follows:write` gestiscono gli elementi seguiti personali. I tipi sono `workspace`, `page`, `calendar`, `task_list`. `target_id` di una pagina è il suo **ID numerico del nodo dell'area di lavoro**, non la chiave del documento HTML. Gli elenchi attività richiedono anche `document_id`. L'accesso corrente alla destinazione viene ricontrollato. Seguire un calendario e iscriversi ad esso sono diversi: la rimozione di un follow disabilita le notifiche senza rimuovere l'iscrizione al calendario.

Le preferenze personali includono `email_enabled`, `email_mode` (`immediate`, `daily`, `important`, `off`), `notify_own_changes`, `theme_mode` (`auto`, `light`, `dark`, `system`). La scelta del tema si applica solo quando la politica globale consente una modalità personale. Un elemento seguito può avere `email_mode_override`; `null` eredita le preferenze. `GET personal-workspace` richiede `workspace:read` e restituisce lo stato; **non crea un'area di lavoro**.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/notifications`

**Scopo:** Restituisce una casella di posta impaginata con un filtro `state=all|read|unread` opzionale.

**Autorizzazione:** `notifications:read`. Solo le notifiche del proprietario della chiave.

**Parametri di query:** `page[after]`, `page[limit]`, `state`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications?page[limit]=1&state=all"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/notifications/read-all`

**Scopo:** contrassegna tutte le notifiche di proprietà del proprietario della chiave come lette.

**Autorizzazione:** `notifications:write`. Solo le notifiche del proprietario della chiave.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/notifications/read-all"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/notifications/{uuid}`

**Scopo:** rimuove definitivamente una notifica già letta del proprietario della chiave.

**Autorizzazione:** `notifications:write`. Solo le notifiche del proprietario della chiave.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/notifications/{uuid}`

**Scopo:** Restituisce una notifica di proprietà del proprietario della chiave.

**Autorizzazione:** `notifications:read`. Solo le notifiche del proprietario della chiave.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/notifications/{uuid}`

**Scopo:** Imposta lo stato letto/non letto di una notifica di proprietà.

**Autorizzazione:** `notifications:write`. Solo le notifiche del proprietario della chiave.

**Percorso:** `uuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `read`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/me/follow-preferences`

**Scopo:** restituisce le preferenze combinate di canale personale e cadenza.

**Autorizzazione:** `follows:read`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/me/follow-preferences`

**Scopo:** Aggiorna parzialmente le preferenze personali senza modificare le righe di un altro utente.

**Autorizzazione:** `follows:write`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `email_enabled`, `email_mode`, `notify_own_changes`, `theme_mode`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/me/follows`

**Scopo:** restituisce gli elementi seguiti personali, filtrati secondo le ACL.

**Autorizzazione:** `follows:read`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Parametri di query:** `search`, `type`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows?type=page&search=API"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/me/follows`

**Scopo:** aggiunge un elemento seguito del proprietario dopo il controllo di dominio ACL.

**Autorizzazione:** `follows:write`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `document_id`, `email_mode_override`, `label`, `target_id`, `target_type`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

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

Risposta effettiva: **HTTP 201**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/me/follows/{type}/{targetId}`

**Scopo:** rimuove soltanto un elemento seguito appartenente al proprietario della chiave.

**Autorizzazione:** `follows:write`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Percorso:** `type`, `targetId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows/page/36"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/me/personal-workspace`

**Scopo:** Restituisce l'area di lavoro personale del proprietario della chiave API senza i dati degli altri utenti.

**Autorizzazione:** `workspace:read`. Soltanto elementi seguiti e preferenze del proprietario della chiave; l'accesso corrente alle risorse seguite viene ricontrollato.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/personal-workspace"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
