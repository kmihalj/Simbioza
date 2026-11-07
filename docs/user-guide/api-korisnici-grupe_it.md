# API: utenti e gruppi

<a id="api--users-and-groups"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

Queste operazioni richiedono un proprietario della chiave amministratore e l'ambito `users:*` o `groups:*` appropriato. Gestiscono account locali e gruppi locali. Un utente normale non può leggere la directory amministrativa semplicemente avendo questi ambiti.

Un nuovo utente necessita di `login_identifier` e di una password valida; un gruppo ha bisogno di `name`. `attributes` contiene gli attributi del profilo. `display_name`, `first_name`, `last_name` e `email` supportano anche l'input di livello superiore. `provider_access` associa i metodi di accesso consentiti (`local`, `saml`, `cas`, `oidc`, `oauth2`). `group_ids` è un elenco di ID numerici di gruppo locale. L'appartenenza PUT sostituisce l'intero elenco anziché aggiungere un gruppo.

PATCH richiede almeno un campo modificato. Gli aggiornamenti di utenti/gruppi, la sostituzione e l'eliminazione dell'appartenenza richiedono l'attuale ETag. L'utente DELETE disattiva l'account e revoca le sue chiavi API; non cancella tutti i dati aziendali correlati. Gli account/gruppi di sistema e gestiti esternamente presentano restrizioni aggiuntive. Sostituisci `[REDACTED]` nell'esempio con la tua password complessa.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/groups`

**Scopo:** Restituisce l'elenco dei gruppi locali.

**Autorizzazione:** `groups:read`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/groups`

**Scopo:** Crea un gruppo locale non di sistema.

**Autorizzazione:** `groups:manage`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `is_enabled`, `name`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

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

Risposta effettiva: **HTTP 201**.

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

**Errori verificati per questa operazione:** 400, 401, 403, 409, 415. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/groups/{groupId}`

**Scopo:** elimina un gruppo locale non di sistema e le relative appartenenze.

**Autorizzazione:** `groups:manage`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `groupId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/groups/5"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/groups/{groupId}`

**Scopo:** Restituisce un gruppo locale.

**Autorizzazione:** `groups:read`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `groupId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups/5"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/groups/{groupId}`

**Scopo:** Aggiorna un gruppo locale non di sistema.

**Autorizzazione:** `groups:manage`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `groupId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `is_enabled`, `name`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 400, 401, 403, 412, 422, 428. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/users`

**Scopo:** Restituisce un elenco di utenti locali paginato con il cursore.

**Autorizzazione:** `users:read`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/users`

**Scopo:** Crea un utente locale.

**Autorizzazione:** `users:create`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

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

Risposta effettiva: **HTTP 201**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/users/{userId}`

**Scopo:** Disattiva un utente locale e revoca le sue chiavi API.

**Autorizzazione:** `users:delete`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `userId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/users/4"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/users/{userId}`

**Scopo:** Restituisce un utente locale.

**Autorizzazione:** `users:read`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `userId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users/4"
```

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/users/{userId}`

**Scopo:** Aggiorna parzialmente un utente locale.

**Autorizzazione:** `users:update`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `userId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/users/{userId}/groups`

**Scopo:** Sostituisce le iscrizioni manuali dell'utente locale.

**Autorizzazione:** `groups:manage`. Il proprietario della chiave deve essere amministratore; il solo ambito non basta.

**Percorso:** `userId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `group_ids`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

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

Risposta effettiva: **HTTP 200**.

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

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
