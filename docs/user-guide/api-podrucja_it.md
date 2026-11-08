# API: aree di lavoro, alberi, autorizzazioni e temi

<a id="api--workspaces-trees-permissions-and-themes"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

`workspace:read` legge le aree di lavoro e gli alberi accessibili. `workspace:manage` li gestisce solo quando il proprietario dispone delle autorizzazioni correnti richieste. Le impostazioni globali della home page, gli elenchi delle aree di lavoro eliminate e il ripristino richiedono un amministratore. L'eliminazione dell'area di lavoro è recuperabile.

Crea un'area di lavoro con `name`; fornire `slug` o lasciare che sia il sistema a ricavarlo. `visibility` è `public`, `authenticated` o `restricted`. Le mappe di traduzione sono `name_translations`/`description_translations`; `primary_language` e `supported_languages` devono corrispondere alle lingue installate. `tree_visibility` e `contents_visibility` controllano la presentazione dell'albero/del contenuto, senza ignorare gli ACL.

ACL PUT **sostituisce tutte le assegnazioni dirette**. `subjects` è un elenco di oggetti `{type,id,permissions}`. L'area di lavoro `type` supporta `user` o `group`; le autorizzazioni sono `can_view`, `can_add`, `can_edit`, `can_publish`, `can_delete`, `can_manage`. I diritti ereditati/di sistema non vengono rimossi semplicemente omettendoli. La limitazione del singolo nodo API attualmente accetta solo gli utenti già assegnati all'area di lavoro; non è un API completo per ogni eccezione di accesso supportata dall'interfaccia web.

POST `nodes` crea solo nodi `internal_link` o `external_link`. Crea documenti HTML con la [pagina API](api-html-stranice_it.md). Il riordino richiede l'elenco completo `placements`, ciascuno con `id`, `parent_id` e `sort_order`. La modifica dei metadati del nodo non modifica il relativo documento HTML.

`shorts` utilizza `page`, `limit`, `depth`, `order`; `limit=all` è disponibile solo sotto le 100 voci visibili. Non utilizza `per_page`. Gli endpoint del tema gestiscono un **tema privato dell'area di lavoro**, non il tema globale del sito. Carica un'immagine in più parti `asset` con `role`; l'importazione del tema ZIP utilizza `theme`. Il caricamento dell'immagine restituisce l'intero modello del tema e il relativo elenco `assets`, non un singolo oggetto immagine. Trova lì l'`file` aggiunto. È possibile eliminare solo le immagini non utilizzate. L'esportazione del tema richiede un amministratore.

L'esportazione HTML restituisce un ZIP di pagine pubblicate accessibili, facoltativamente ristrette da `node_ids`. Non ricrea sondaggi funzionanti. Non è un backup di risposte, cronologia e impostazioni.

Le impostazioni della home page utilizzano `public_target`/`authenticated_target`: `default`, `page:<nodeId>` o `shorts:<workspaceId>`, oltre ai flag di presentazione. Personale PUT accetta `selection` come ID di pagina (0 ripristina il valore predefinito) o oggetto di destinazione, solo quando l'amministratore abilita la selezione personale.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/workspaces`

**Scopo:** Restituisce le aree di lavoro visibili al proprietario della chiave API.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "uuid": "4cd08aa0-7377-400e-a330-469da66b60f6",
      "slug": "editing-guide",
      "name": "Editing guide",
      "name_translations": {
        "en": "Editing guide"
      },
      "description": "Example pages for collaborative work.",
      "description_translations": {
        "en": "Example pages for collaborative work."
      },
      "visibility": "public",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": false,
      "created_at": "2026-11-06 22:35:54",
      "updated_at": "2026-11-06 22:35:54",
      "deleted_at": "",
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "613cd338ec6a74ef447a39df54e39cea",
    "page": {
      "limit": 1,
      "total": 4,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/workspaces?page%5Blimit%5D=1",
    "next": "/api/v1/workspaces?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces`

**Scopo:** Crea una nuova area di lavoro soggetta alla politica di creazione dell'applicazione.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "Ogledno API područje",
  "slug": "api-ogledno-podrucje",
  "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
  "visibility": "restricted"
}' \
  "$BASE_URL/api/v1/workspaces"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "40109227f06c007cdfd19c562fd53308"
  },
  "links": {
    "self": "/api/v1/workspaces"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/deleted`

**Scopo:** Restituisce l'elenco riservato agli amministratori delle aree di lavoro eliminate temporaneamente.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/deleted?page[limit]=100"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 4,
      "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
      "slug": "api-ogledno-podrucje",
      "name": "Ogledno API područje",
      "name_translations": {
        "en": "Ogledno API područje"
      },
      "description": "Ažurirano preko API-ja.",
      "description_translations": {
        "en": "Ažurirano preko API-ja."
      },
      "visibility": "restricted",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": true,
      "created_at": "2026-11-06 22:44:20",
      "updated_at": "2026-11-06 23:20:32",
      "deleted_at": "2026-11-06 23:20:32",
      "permissions": []
    }
  ],
  "meta": {
    "request_id": "337679c1372f56ec4cc2e5510beb8f0b",
    "page": {
      "limit": 100,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/workspaces/deleted?page%5Blimit%5D=100",
    "next": null
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces/deleted/{workspaceId}/restore`

**Scopo:** Ripristina un'area di lavoro eliminata temporaneamente con uno slug disponibile.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `slug`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "slug": "api-ogledno-podrucje"
}' \
  "$BASE_URL/api/v1/workspaces/deleted/4/restore"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 23:20:32",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "22962d6ee7bb686754b06152ff10ba62"
  },
  "links": {
    "self": "/api/v1/workspaces/deleted/4/restore"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/homepage/preference`

**Scopo:** Restituisce la home page personale del proprietario della chiave API quando la selezione è abilitata.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "efe455081ae8c6a2599cf235290fa053"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/homepage/preference`

**Scopo:** Memorizza la home page personale del proprietario della chiave API dopo un nuovo controllo ACL.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `selection`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "selection": 0
}' \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "c0575c57cbeb8fb18fdd07daa34ad587"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/homepage/settings`

**Scopo:** Restituisce i criteri della home page pubblica e autenticata riservati esclusivamente all'amministratore.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "0bb93172f325cd23a0fd269bbf59e7f5"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/homepage/settings`

**Scopo:** Memorizza i criteri della home page pubblica e autenticata riservati esclusivamente all'amministratore.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `allow_user_selection`, `authenticated_show_display_options`, `authenticated_show_tree`, `authenticated_target`, `public_show_display_options`, `public_show_tree`, `public_target`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "public_target": "default",
  "authenticated_target": "default",
  "public_show_tree": true,
  "public_show_display_options": true,
  "authenticated_show_tree": true,
  "authenticated_show_display_options": true,
  "allow_user_selection": true
}' \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "4e069414636ec0f7a9ae9b197dd191e1"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/workspaces/{workspaceSlug}`

**Scopo:** Elimina temporaneamente un'area di lavoro con l'autorizzazione effettiva can_manage.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}`

**Scopo:** Restituisce un'area di lavoro quando il proprietario della chiave può visualizzarla.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "e0e85fd92c7a001921444f4175228ec0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errori verificati per questa operazione:** 401, 403, 404. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/workspaces/{workspaceSlug}`

**Scopo:** Aggiorna parzialmente un'area di lavoro con l'autorizzazione effettiva can_manage.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano preko API-ja."
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "135a815aac013227e2c0dad8cb3a7e86"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errori verificati per questa operazione:** 400, 401, 403, 412, 428. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/acl`

**Scopo:** restituisce l'ACL diretto dell'area di lavoro a un utente autorizzato a gestirla.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 1,
      "label": "Administrator",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "b6aa7159a0807a86e4ecec28837632c8"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/{workspaceSlug}/acl`

**Scopo:** sostituisce l'intero ACL dell'area di lavoro.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `subjects`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "c7bc6b2bc47f17e6da17a6f25bba9d5b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/acl/subjects`

**Scopo:** cerca utenti o gruppi per il selettore Workspace ACL.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `category`, `q`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "label": "Ana Author",
      "type": "user",
      "category": "user",
      "is_builtin": false,
      "is_read_only": false
    }
  ],
  "meta": {
    "request_id": "a00b8b79c556ffb217e854bb4df6781f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces/{workspaceSlug}/exports/html`

**Scopo:** scarica uno ZIP HTML consultabile offline di un'area di lavoro o delle pagine selezionate, filtrato secondo le ACL.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `node_ids`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_ids": [
    36
  ]
}' \
  --dump-header response-headers.txt \
  --output response.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/exports/html"
```

Risposta effettiva: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="simbioza-api-ogledno-podrucje-20261106-231026.zip"
content-length: 117723
```

Il file viene salvato come `response.*`. Il contenuto verificato ha 117723 byte; non decodificarlo come JSON.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces/{workspaceSlug}/nodes`

**Scopo:** Aggiunge un collegamento interno o esterno all'albero.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `contents_visibility`, `is_homepage`, `node_type`, `parent_id`, `primary_language`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_type": "internal_link",
  "title": "api-internal",
  "slug": "api-internal",
  "target_url": "/about",
  "sort_order": 200
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "api-internal",
    "title_translations": {
      "en": "api-internal"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "b75c597c0414ff8e3cf18d976e9f03a9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Scopo:** Elimina un nodo di collegamento; i documenti rimangono responsabilità dell'editor API.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`, `nodeId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Scopo:** Aggiorna i dati strutturali per un nodo dell'albero.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`, `nodeId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `contents_visibility`, `is_homepage`, `labels`, `node_type`, `parent_id`, `primary_language`, `properties`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API poveznica"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "API poveznica",
    "title_translations": {
      "en": "API poveznica"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "89fef7d6bff70c6a19e5dc398783d33f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Scopo:** Restituisce le restrizioni dirette ACL per un nodo.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`, `nodeId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [],
  "meta": {
    "request_id": "a376426ba7d7e2cdbd47d084edcd9e9e"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Scopo:** Sostituisce le restrizioni dirette ACL per un nodo.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`, `nodeId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `subjects`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": false,
        "can_edit": false,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "3991706d28cfe0d2ce50650e27891ff0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/shorts`

**Scopo:** restituisce i riepiloghi delle pagine pubblicate dell'area di lavoro, filtrati secondo le ACL.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri query:** `lang`, `page`, `limit`, `depth`, `order`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "tree": [
      {
        "id": 40,
        "uuid": "f85dfce8-f123-4dd9-969a-f29b068dae06",
        "workspace_id": 4,
        "parent_id": null,
        "node_type": "document",
        "slug": "api-vodic-za-integracije",
        "title": "Vodič za integracije",
        "title_translations": "{\"en\":\"Vodič za integracije\",\"hr\":\"Vodič za integracije\"}",
        "document_key": "api-vodic-za-integracije",
        "route_name": null,
        "target_url": null,
        "sort_order": 100,
        "is_homepage": 1,
        "is_tree_hidden": 0,
        "is_enabled": 1,
        "contents_visibility": "inherit",
        "created_by_user_id": 1,
        "updated_by_user_id": 1,
        "created_at": "2026-11-06 23:24:07",
        "updated_at": "2026-11-06 23:24:07",
        "is_tree_hidden_effective": false,
        "is_tree_temporarily_visible": false,
        "permissions": {
          "can_view": true,
          "can_add": true,
          "can_edit": true,
          "can_publish": true,
          "can_delete": true,
          "can_manage": true
        },
        "children": [],
        "title_translations_map": {
          "en": "Vodič za integracije",
          "hr": "Vodič za integracije"
        },
        "href": "/workspace/api-ogledno-podrucje/api-vodic-za-integracije?lang=hr"
      }
    ],
    "articles": [],
    "depth": 2,
    "limit": "10",
    "order": "newest",
    "total": 1,
    "all_available": true,
    "pagination": {
      "page": 1,
      "pages": 1,
      "total": 1,
      "from": 1,
      "to": 1,
      "page_numbers": [
        1
      ]
    },
    "shorts_path": "/workspace/api-ogledno-podrucje/shorts?lang=hr",
    "workspace_path": "/workspace/api-ogledno-podrucje?lang=hr"
  },
  "meta": {
    "request_id": "59be13951d906db537a449e60e0c472c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/theme`

**Scopo:** Restituisce le impostazioni del tema dell'area di lavoro, le scelte di sistema e i file privati.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Risposta effettiva: **HTTP 200**. Questo è l'output effettivo del filtro `jq` mostrato.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "__default__",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "simbioza",
      "label": {
        "hr": "Simbioza",
        "en": "Simbioza"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-botanical-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.svg",
        "role": "icon",
        "used": false
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "d2a84727c1eea4b947872cc8fb0452d9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/workspaces/{workspaceSlug}/theme`

**Scopo:** Memorizza una configurazione di tema privato solo per l'area di lavoro fornita.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `mode_policy`, `theme`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

Gli esempi seguenti utilizzano il tema pubblico Simbioza e presuppongono che lo spazio di lavoro erediti quel tema. Le risposte illustrano il contratto API; l’elenco dei file e l’identificativo della richiesta dipendono dall’installazione.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme": {
    "id": "simbioza",
    "label": {
      "hr": "Simbioza",
      "en": "Simbioza"
    },
    "font_family": "Aptos, \"Segoe UI\", system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    "menu_font_family": "Aptos, \"Segoe UI\", system-ui, -apple-system, BlinkMacSystemFont, sans-serif",
    "menu_font_size": "1rem",
    "menu_font_weight": "600",
    "menu_font_style": "normal",
    "system": true,
    "components": {
      "skip_link": true,
      "header": {
        "enabled": true,
        "sticky": true,
        "container": "fluid",
        "height_px": 72,
        "items": [
          {
            "id": "header-1",
            "type": "logo",
            "position": "start",
            "text": [],
            "href": "@app",
            "src_light": "@theme-assets/simbioza/icon-adriatic-light.png",
            "src_dark": "@theme-assets/simbioza/icon-adriatic-dark.png",
            "alt": {
              "hr": "Simbioza znak",
              "en": "Simbioza mark"
            },
            "height": 48,
            "new_tab": false
          },
          {
            "id": "header-2",
            "type": "text",
            "position": "start",
            "text": {
              "hr": "Simbioza",
              "en": "Simbioza"
            },
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "header-3",
            "type": "language",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "header-4",
            "type": "account",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          }
        ]
      },
      "navigation": {
        "placement": "standalone",
        "container": "fluid",
        "height_px": 56,
        "breakpoint": "lg",
        "tabs_style": "standard",
        "tabs_accent_enabled": true,
        "tabs_radius": "small",
        "tabs_active_border_width_px": 2,
        "show_brand": false,
        "brand_display": "none",
        "show_mobile_label": false,
        "mobile_label": {
          "hr": "Izbornik",
          "en": "Menu"
        },
        "sticky": true,
        "show_icons": false,
        "mark_external_links": true
      },
      "hero": {
        "home_enabled": true,
        "inner_enabled": true,
        "container": "fluid",
        "home_size": "medium",
        "inner_size": "medium",
        "alignment": "start",
        "content_min_height_px": 0,
        "content_padding_top_px": 0,
        "content_padding_bottom_px": 0,
        "visual_enabled": true,
        "visual_src_light": "@theme-assets/simbioza/hero-adriatic-light.svg",
        "visual_src_dark": "@theme-assets/simbioza/hero-adriatic-dark.svg",
        "visual_alt": [],
        "visual_decorative": true,
        "visual_allow_overflow": false,
        "visual_width_px": 560,
        "visual_max_height_px": 0,
        "visual_top_px": -48,
        "visual_right_px": 24,
        "visual_rotation_deg": 0,
        "wave": true
      },
      "content": {
        "container": "fluid",
        "inner_title_placement": "hero",
        "application_title_placement": "hero",
        "surface": "plain",
        "inner_hero_overlap": "large",
        "density": "comfortable",
        "title_accent": "none",
        "title_shadow": false,
        "content_accent": "top",
        "content_shadow": false,
        "sidebar_accent": "top",
        "sidebar_shadow": false
      },
      "cards": {
        "style": "quiet",
        "density": "comfortable",
        "radius": "medium"
      }
    },
    "light": {
      "colors": {
        "body_bg": "#FFF8EE",
        "body_text": "#123F4B",
        "muted_text": "#547078",
        "surface_bg": "#FFFFFF",
        "surface_text": "#123F4B",
        "border": "#D9E3DF",
        "link": "#A83F2E",
        "primary": "#0E6268",
        "secondary": "#C7543A",
        "success": "#287A68",
        "warning": "#D39B2F",
        "danger": "#B93636",
        "info": "#27979A",
        "document_action_bg": "#FFFFFF",
        "document_action_text": "#C7543A",
        "document_action_border": "#D9E3DF",
        "document_action_hover_bg": "#C7543A",
        "document_action_hover_text": "#FFF8EE",
        "document_action_active_bg": "#0E6268",
        "document_action_active_text": "#FFF8EE",
        "card_bg": "#FFFFFF",
        "card_text": "#123F4B",
        "card_border": "#D9E3DF",
        "card_accent": "#FF8064",
        "card_shadow": "#071F27",
        "title_card_bg": "#FFFFFF",
        "title_card_text": "#123F4B",
        "title_card_border": "#D9E3DF",
        "title_card_accent": "#FF8064",
        "content_card_bg": "#FFFFFF",
        "content_card_text": "#123F4B",
        "content_card_border": "#D9E3DF",
        "content_card_accent": "#FF8064",
        "sidebar_card_bg": "#FFFFFF",
        "sidebar_card_text": "#123F4B",
        "sidebar_card_border": "#D9E3DF",
        "sidebar_card_accent": "#E9B84A",
        "title_supporting_text": "#276E82",
        "hero_gradient_1": "#25408F",
        "hero_gradient_2": "#2872A0",
        "hero_gradient_3": "#2F9EAA",
        "hero_gradient_4": "#3CB4AD",
        "hero_gradient_5": "#4CC0AD",
        "hero_text": "#FFF8EE",
        "hero_title": "#FFF8EE",
        "hero_subtitle": "#D9F2EE",
        "hero_muted": "#B7D8D5",
        "table_header_bg": "#EAF3F0",
        "table_header_text": "#123F4B",
        "table_row_bg": "#FFFFFF",
        "table_row_text": "#123F4B",
        "table_row_alt_bg": "#F5F7F4",
        "table_border": "#D9E3DF",
        "alert_primary_bg": "#CFE2FF",
        "alert_primary_text": "#084298",
        "alert_primary_border": "#B6D4FE",
        "alert_success_bg": "#D1E7DD",
        "alert_success_text": "#0F5132",
        "alert_success_border": "#BADBCC",
        "alert_warning_bg": "#FFF3CD",
        "alert_warning_text": "#664D03",
        "alert_warning_border": "#FFECB5",
        "alert_danger_bg": "#F8D7DA",
        "alert_danger_text": "#842029",
        "alert_danger_border": "#F5C2C7",
        "alert_info_bg": "#CFF4FC",
        "alert_info_text": "#055160",
        "alert_info_border": "#B6EFFB",
        "badge_bg": "#123F4B",
        "badge_text": "#FFF8EE",
        "tag_bg": "#EAF3F0",
        "tag_text": "#123F4B",
        "nav_default_bg": "#071F27",
        "nav_default_text": "#FFF8EE",
        "nav_border": "#72D4C8",
        "nav_active_bg": "#071F27",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#E9B84A",
        "nav_hover_bg": "#0D424B",
        "left_menu_hover_bg": "#EAF3F0",
        "left_menu_hover_text": "#123F4B",
        "left_menu_active_bg": "#0E6268",
        "left_menu_active_text": "#FFFFFF",
        "special_left_menu_accent": "#FF8064",
        "pagination_bg": "#FFFFFF",
        "pagination_text": "#0E6268",
        "pagination_active_bg": "#0E6268",
        "pagination_active_text": "#FFFFFF",
        "breadcrumb_text": "#547078",
        "breadcrumb_link": "#0E6268",
        "editor_tab_bg": "#F8F9FA",
        "editor_tab_text": "#495057",
        "editor_tab_border": "#DEE2E6",
        "editor_tab_hover_bg": "#E9ECEF",
        "editor_tab_active_bg": "#FFFFFF",
        "editor_tab_active_text": "#212529",
        "editor_tab_active_accent": "#0D6EFD",
        "editor_tab_panel_bg": "#FFFFFF",
        "editor_tab_panel_text": "#212529",
        "editor_accordion_header_bg": "#F8F9FA",
        "editor_accordion_header_text": "#212529",
        "editor_accordion_body_bg": "#FFFFFF",
        "editor_accordion_body_text": "#212529",
        "focus_ring": "#FF8064",
        "disabled_bg": "#E7ECE9",
        "disabled_text": "#667C81",
        "valid": "#287A68",
        "invalid": "#B93636",
        "code_bg": "#EEF4F1",
        "code_text": "#8B3653",
        "blockquote_border": "#72D4C8",
        "callout_bg": "#EAF3F0",
        "callout_text": "#123F4B",
        "callout_border": "#72D4C8",
        "navbar_gradient_1": "#25408F",
        "navbar_gradient_2": "#2872A0",
        "navbar_gradient_3": "#2F9EAA",
        "navbar_gradient_4": "#3CB4AD",
        "navbar_gradient_5": "#4CC0AD",
        "navbar_text": "#FFF8EE",
        "navbar_link": "#FFF8EE",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#0D424B",
        "primary_text": "auto",
        "secondary_text": "#FFFFFF",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto"
      }
    },
    "dark": {
      "colors": {
        "body_bg": "#071F27",
        "body_text": "#E7F3F0",
        "muted_text": "#A9C5C3",
        "surface_bg": "#0B2A32",
        "surface_text": "#E7F3F0",
        "border": "#31515A",
        "link": "#8AE1D7",
        "primary": "#45AFA6",
        "secondary": "#FF8064",
        "success": "#62C6A8",
        "warning": "#E9B84A",
        "danger": "#F07979",
        "info": "#72D4C8",
        "document_action_bg": "#0B2A32",
        "document_action_text": "#FF8064",
        "document_action_border": "#31515A",
        "document_action_hover_bg": "#FF8064",
        "document_action_hover_text": "#071F27",
        "document_action_active_bg": "#45AFA6",
        "document_action_active_text": "#071F27",
        "card_bg": "#0B2A32",
        "card_text": "#E7F3F0",
        "card_border": "#31515A",
        "card_accent": "#FF8064",
        "card_shadow": "#000000",
        "title_card_bg": "#0B2A32",
        "title_card_text": "#E7F3F0",
        "title_card_border": "#31515A",
        "title_card_accent": "#FF8064",
        "content_card_bg": "#0B2A32",
        "content_card_text": "#E7F3F0",
        "content_card_border": "#31515A",
        "content_card_accent": "#FF8064",
        "sidebar_card_bg": "#0B2A32",
        "sidebar_card_text": "#E7F3F0",
        "sidebar_card_border": "#31515A",
        "sidebar_card_accent": "#E9B84A",
        "title_supporting_text": "#A9C5C3",
        "hero_gradient_1": "#25408F",
        "hero_gradient_2": "#2872A0",
        "hero_gradient_3": "#2F9EAA",
        "hero_gradient_4": "#3CB4AD",
        "hero_gradient_5": "#4CC0AD",
        "hero_text": "#FFF8EE",
        "hero_title": "#FFF8EE",
        "hero_subtitle": "#D9F2EE",
        "hero_muted": "#B7D8D5",
        "table_header_bg": "#113A43",
        "table_header_text": "#F7FBFA",
        "table_row_bg": "#0B2A32",
        "table_row_text": "#E7F3F0",
        "table_row_alt_bg": "#102F37",
        "table_border": "#31515A",
        "alert_primary_bg": "#031633",
        "alert_primary_text": "#6EA8FE",
        "alert_primary_border": "#084298",
        "alert_success_bg": "#051B11",
        "alert_success_text": "#75B798",
        "alert_success_border": "#0F5132",
        "alert_warning_bg": "#332701",
        "alert_warning_text": "#FFDA6A",
        "alert_warning_border": "#664D03",
        "alert_danger_bg": "#2C0B0E",
        "alert_danger_text": "#EA868F",
        "alert_danger_border": "#842029",
        "alert_info_bg": "#032830",
        "alert_info_text": "#6EDFF6",
        "alert_info_border": "#055160",
        "badge_bg": "#E9B84A",
        "badge_text": "#071F27",
        "tag_bg": "#153A42",
        "tag_text": "#E7F3F0",
        "nav_default_bg": "#071F27",
        "nav_default_text": "#FFF8EE",
        "nav_border": "#31515A",
        "nav_active_bg": "#0D424B",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#E9B84A",
        "nav_hover_bg": "#0D424B",
        "left_menu_hover_bg": "#153A42",
        "left_menu_hover_text": "#FFFFFF",
        "left_menu_active_bg": "#45AFA6",
        "left_menu_active_text": "#071F27",
        "special_left_menu_accent": "#FF8064",
        "pagination_bg": "#0B2A32",
        "pagination_text": "#8AE1D7",
        "pagination_active_bg": "#45AFA6",
        "pagination_active_text": "#071F27",
        "breadcrumb_text": "#A9C5C3",
        "breadcrumb_link": "#8AE1D7",
        "editor_tab_bg": "#212529",
        "editor_tab_text": "#ADB5BD",
        "editor_tab_border": "#495057",
        "editor_tab_hover_bg": "#343A40",
        "editor_tab_active_bg": "#2B3035",
        "editor_tab_active_text": "#FFFFFF",
        "editor_tab_active_accent": "#6EA8FE",
        "editor_tab_panel_bg": "#2B3035",
        "editor_tab_panel_text": "#DEE2E6",
        "editor_accordion_header_bg": "#212529",
        "editor_accordion_header_text": "#DEE2E6",
        "editor_accordion_body_bg": "#2B3035",
        "editor_accordion_body_text": "#DEE2E6",
        "focus_ring": "#FF8064",
        "disabled_bg": "#183941",
        "disabled_text": "#8DA4A5",
        "valid": "#62C6A8",
        "invalid": "#F07979",
        "code_bg": "#102F37",
        "code_text": "#FF9F89",
        "blockquote_border": "#72D4C8",
        "callout_bg": "#102F37",
        "callout_text": "#E7F3F0",
        "callout_border": "#31515A",
        "navbar_gradient_1": "#25408F",
        "navbar_gradient_2": "#2872A0",
        "navbar_gradient_3": "#2F9EAA",
        "navbar_gradient_4": "#3CB4AD",
        "navbar_gradient_5": "#4CC0AD",
        "navbar_text": "#FFF8EE",
        "navbar_link": "#FFF8EE",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#0D424B",
        "primary_text": "auto",
        "secondary_text": "auto",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto"
      }
    }
  },
  "mode_policy": "light"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Esempio di risposta: **HTTP 200**.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "Simbioza – Ogledno API područje",
        "en": "Simbioza – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "other",
        "used": true
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "fa8a2e1360c17a2b204df8cebd36ea8c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/assets`

**Scopo:** Memorizza un'immagine dal campo multiparte `asset` nella libreria dei temi privata.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `asset`, `role`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'asset=@./api-circle.svg;type=image/svg+xml' \
  --form role=other \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Esempio di risposta: **HTTP 200**.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "Simbioza – Ogledno API područje",
        "en": "Simbioza – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "api-circle.svg",
        "role": "other",
        "used": false
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "other",
        "used": true
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "b47707737f33e9b0db3941383cbaa65b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/workspaces/{workspaceSlug}/theme/assets/{file}`

**Scopo:** Elimina un file del tema privato di Workspace inutilizzato.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`, `file`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Esempio di risposta: **HTTP 200**.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "Simbioza – Ogledno API područje",
        "en": "Simbioza – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "other",
        "used": true
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "97a72aa130fa5380e38c2f24cdf948ba"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/theme/export`

**Scopo:** Scarica un tema privato di Workspace ZIP per un amministratore.

**Autorizzazione:** una chiave API valida. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output api-workspace-theme.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/export"
```

Esempio di risposta: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="workspace-theme-api-ogledno-podrucje.zip"
```

Il file ZIP viene salvato come `api-workspace-theme.zip` e può essere utilizzato nel successivo esempio di importazione. La sua dimensione dipende dal contenuto; non decodificarlo come JSON.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/import`

**Scopo:** Importa un tema ZIP dal campo multiparte `theme` in un tema Workspace privato.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `mode_policy`, `theme`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'theme=@./api-workspace-theme.zip;type=application/zip' \
  --form mode_policy=auto \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/import" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Esempio di risposta: **HTTP 200**.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "Simbioza – Ogledno API područje",
        "en": "Simbioza – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "other",
        "used": true
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "c8eee8b7fe118a2c8e40873aa94ac9c9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/import"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/{workspaceSlug}/theme/selection`

**Scopo:** seleziona l'ereditarietà o un tema di sistema invariato per un'area di lavoro.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `mode_policy`, `theme_id`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme_id": "simbioza",
  "mode_policy": "auto"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/selection" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Esempio di risposta: **HTTP 200**.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "simbioza",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "simbioza",
      "label": {
        "hr": "Simbioza",
        "en": "Simbioza"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-botanical-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.svg",
        "role": "icon",
        "used": false
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "3a8b382e6a363076a026c05f7c899268"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/selection"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/workspaces/{workspaceSlug}/tree`

**Scopo:** Restituisce l'albero dell'area di lavoro filtrato e le autorizzazioni effettive del nodo.

**Autorizzazione:** `workspace:read`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "78d6b87d8ee7c962567b443c82af5111"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/workspaces/{workspaceSlug}/tree/order`

**Scopo:** Memorizza l'ordine completo dell'albero e la gerarchia.

**Autorizzazione:** `workspace:manage`. Autorizzazioni correnti dell'area di lavoro/nodo; l'amministrazione globale e il ripristino richiedono diritti di amministratore.

**Percorso:** `workspaceSlug`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** `lang`.

**Corpo/campi supportati:** `placements`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "placements": [
    {
      "id": 36,
      "parent_id": null,
      "sort_order": 100
    },
    {
      "id": 37,
      "parent_id": null,
      "sort_order": 200
    },
    {
      "id": 38,
      "parent_id": null,
      "sort_order": 201
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree/order"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 37,
      "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
      "parent_id": null,
      "node_type": "internal_link",
      "slug": "api-internal",
      "title": "API poveznica",
      "title_translations": {
        "en": "API poveznica"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "/about",
      "sort_order": 200,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 38,
      "uuid": "5947a07a-179f-433e-9030-7abbbe9eaa6d",
      "parent_id": null,
      "node_type": "external_link",
      "slug": "api-external",
      "title": "api-external",
      "title_translations": {
        "en": "api-external"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "https://example.com/",
      "sort_order": 201,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "98e3352a89089722cf08b382db4fc6d2"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree/order"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
