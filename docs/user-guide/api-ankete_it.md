# API: sondaggi e risultati

<a id="api--surveys-and-results"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

API richiede sia un ambito che le autorizzazioni correnti del sondaggio/area di lavoro. `surveys:read` legge le definizioni, `surveys:write` modifica e invia la revisione, `surveys:publish` rivede/pubblica e modifica gli stati finali, `surveys:results` legge i risultati consentiti e `surveys:export` crea esportazioni private CSV. L'accesso del partecipante esterno non è una chiave API; REST non espone alcun endpoint di invio della risposta o rappresentazione del partecipante.

POST crea una bozza da `title` e `primary_language`; facoltativo `placement` lo inserisce in un'area di lavoro soggetta alle autorizzazioni dell'host. `primary_language` deve essere installato. PATCH invia l'intero `definition`, non un frammento parziale della domanda. Contiene `settings`, `pages`, `texts` e `rules`; Gli UUID collegano domande, scelte e traduzioni. `languages`/`enabled_languages` utilizza le lingue installate e l'installazione impone il proprio fuso orario.

Leggi il sondaggio con GET prima di ogni aggiornamento. Invia `survey.lock_version` come `version` e `revision.uuid` come `revision` per le transizioni. I risultati/esportazioni del sondaggio non hanno ETag; l'authoring utilizza il proprio blocco ottimistico. Un `version` obsoleto restituisce 409. `meaning_unchanged=true` non ignora la protezione delle risposte già raccolte.

Le azioni separate riportate di seguito sono `submit-review`, `return-review`, `publish`, `close`, `archive`, `withdraw`, `copy`, `delete`, `discard-draft`, `restore`. Invia `confirmed=true` dove è richiesta la conferma. L'invio attende un editore; solo un sondaggio pubblicato e aperto accetta risposte. `copy` restituisce una nuova bozza UUID senza risposte. L'eliminazione/eliminazione di una nuova bozza è recuperabile; solo amministratore `restore` ripristina un sondaggio precedentemente pubblicato e chiuso. Gli amministratori possono ottenere la versione di un sondaggio eliminato dall'elenco `state=deleted`.

Gli elenchi utilizzano `q`, `state`, `page`, `per_page` (predefinito 25, massimo 100). I risultati utilizzano `lang`, `from/to` come `YYYY-MM-DD`, `page` e `per_page` (predefinito 10, massimo 25); i filtri della data richiedono diritti di gestione. `results` impagina le domande, mentre `responses` impagina gli invii. Gli invii non elaborati richiedono l'autorizzazione della direzione, non semplicemente l'accesso ai risultati pubblici.

CSV utilizza `mode=aggregate` o `responses`, lingua e date opzionali. `include_identity=true` si applica solo alle risposte identificate e a un responsabile autorizzato. La risposta restituisce `token` e `expires` privati e con scadenza. Archivia il token in privato e scaricalo come lo stesso utente autorizzato. Tutte le risposte al sondaggio utilizzano `Cache-Control: private, no-store`. L'esempio PATCH include una definizione valida completa; sostituisci la chiave di traduzione del sondaggio UUID con la tua UUID.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/surveys`

**Scopo:** Restituisce solo definizioni autorizzate e limitate.

**Autorizzazione:** `surveys:read`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Parametri di query:** `page`, `per_page`, `q`, `state`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys?q=API&page=1&per_page=10"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "items": [],
    "total": 0,
    "page": 1,
    "pages": 1,
    "per_page": 10,
    "can_create": true,
    "can_restore": true
  },
  "meta": {
    "request_id": "d0b8ee7dfb5cb00730d6e9c3bb6281d7"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys`

**Scopo:** Supporta il posizionamento autonomo o esplicitamente autorizzato di Workspace.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `placement`, `primary_language`, `title`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API povratne informacije",
  "primary_language": "hr"
}' \
  "$BASE_URL/api/v1/surveys"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f"
  },
  "meta": {
    "request_id": "e25c296217ac34f4d26cfc3085244246"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403, 422. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/surveys/{survey}`

**Scopo:** Restituisce solo definizioni autorizzate e limitate.

**Autorizzazione:** `surveys:read`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "survey": {
      "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
      "owner_user_id": 1,
      "primary_language": "hr",
      "timezone": "UTC",
      "state": "draft",
      "audience_mode": "application",
      "privacy_mode": "anonymous",
      "results_visibility": "private",
      "draft_revision_uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
      "published_revision_uuid": null,
      "lock_version": 0,
      "structure_locked": 0,
      "accepted_count": 0,
      "opens_at": null,
      "closes_at": null,
      "created_at": "2026-11-06 22:11:23",
      "updated_at": "2026-11-06 22:11:23"
    },
    "revision": {
      "uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
      "survey_uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
      "revision_number": 1,
      "state": "draft",
      "title": "API povratne informacije",
      "structure_fingerprint": "643d5437104296e21d906ecb15b2c96ad278f20cfc4af53b12bb6069bd853726",
      "content_fingerprint": "500f09259ca330d0833105171bd86f93ede50f7e0b0f8d72352b55a249386a85",
      "created_by_user_id": 1,
      "reviewed_by_user_id": null,
      "created_at": "2026-11-06 22:11:23"
    },
    "definition": {
      "settings": {
        "languages": [
          "hr"
        ],
        "enabled_languages": [
          "hr"
        ],
        "timezone": "Europe/Zagreb",
        "opens_at": null,
        "closes_at": null,
        "audience_mode": "application",
        "privacy_mode": "anonymous",
        "results_visibility": "private",
        "public_cookie_days": 30,
        "results_timing": "during",
        "results_audience": "managers",
        "admission": [],
        "invitees_required": false,
        "show_question_codes": true,
        "show_choice_codes": true
      },
      "pages": [],
      "texts": {
        "hr": {
          "eaf6b9bc-2459-4849-8102-7343e047a09f": {
            "title": "API povratne informacije"
          }
        }
      },
      "rules": []
    },
    "rights": {
      "manage": true,
      "publish": true,
      "preview": true,
      "delete": true
    },
    "versions": [
      {
        "uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
        "survey_uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
        "revision_number": 1,
        "state": "draft",
        "title": "API povratne informacije",
        "structure_fingerprint": "643d5437104296e21d906ecb15b2c96ad278f20cfc4af53b12bb6069bd853726",
        "content_fingerprint": "500f09259ca330d0833105171bd86f93ede50f7e0b0f8d72352b55a249386a85",
        "created_by_user_id": 1,
        "reviewed_by_user_id": null,
        "created_at": "2026-11-06 22:11:23"
      }
    ]
  },
  "meta": {
    "request_id": "db784bd3b231d1f5865b767b33c2e884"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403, 404. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/surveys/{survey}`

**Scopo:** Richiede la versione ottimistica e preserva le invarianti pubblicate/raccolte.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `definition`, `meaning_unchanged`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 0,
  "definition": {
    "settings": {
      "languages": [
        "hr",
        "en"
      ],
      "enabled_languages": [
        "hr",
        "en"
      ],
      "timezone": "Europe/Zagreb",
      "opens_at": null,
      "closes_at": null,
      "audience_mode": "public",
      "privacy_mode": "anonymous",
      "results_visibility": "private",
      "public_cookie_days": 30,
      "results_timing": "during",
      "results_audience": "public",
      "admission": [],
      "invitees_required": false,
      "show_question_codes": false,
      "show_choice_codes": false
    },
    "pages": [
      {
        "uuid": "10000000-0000-4000-8000-000000000001",
        "require_complete": true,
        "blocks": [
          {
            "uuid": "10000000-0000-4000-8000-000000000002",
            "kind": "html"
          },
          {
            "uuid": "10000000-0000-4000-8000-000000000003",
            "kind": "question",
            "question": {
              "uuid": "10000000-0000-4000-8000-000000000004",
              "type": "radio",
              "alias": "EASE",
              "required": true,
              "settings": [],
              "choices": [
                {
                  "uuid": "10000000-0000-4000-8000-000000000005",
                  "code": "1",
                  "kind": "regular"
                },
                {
                  "uuid": "10000000-0000-4000-8000-000000000006",
                  "code": "2",
                  "kind": "regular"
                },
                {
                  "uuid": "10000000-0000-4000-8000-000000000007",
                  "code": "3",
                  "kind": "regular"
                }
              ]
            }
          },
          {
            "uuid": "10000000-0000-4000-8000-000000000008",
            "kind": "question",
            "question": {
              "uuid": "10000000-0000-4000-8000-000000000009",
              "type": "rating",
              "alias": "RATING",
              "required": true,
              "settings": {
                "min": 1,
                "max": 5
              },
              "choices": []
            }
          },
          {
            "uuid": "10000000-0000-4000-8000-000000000010",
            "kind": "question",
            "question": {
              "uuid": "10000000-0000-4000-8000-000000000011",
              "type": "textarea",
              "alias": "COMMENT",
              "required": false,
              "settings": {
                "max_length": 1000
              },
              "choices": []
            }
          }
        ]
      }
    ],
    "texts": {
      "en": {
        "10000000-0000-4000-8000-000000000001": {
          "title": "Your experience"
        },
        "10000000-0000-4000-8000-000000000002": {
          "html": "<h2>Your feedback matters</h2><p>This example survey was created through the API and published through the normal review workflow. It is public and anonymous. Please enter no personal information.</p>"
        },
        "10000000-0000-4000-8000-000000000005": {
          "label": "Easy"
        },
        "10000000-0000-4000-8000-000000000006": {
          "label": "Mostly easy"
        },
        "10000000-0000-4000-8000-000000000007": {
          "label": "Needs improvement"
        },
        "10000000-0000-4000-8000-000000000004": {
          "text": "How easy is it to use surveys?",
          "help_html": ""
        },
        "10000000-0000-4000-8000-000000000009": {
          "text": "Your overall rating",
          "help_html": ""
        },
        "10000000-0000-4000-8000-000000000011": {
          "text": "What would you improve?",
          "help_html": ""
        },
        "eaf6b9bc-2459-4849-8102-7343e047a09f": {
          "title": "Workshop feedback"
        }
      },
      "hr": {
        "10000000-0000-4000-8000-000000000001": {
          "title": "Vaše iskustvo"
        },
        "10000000-0000-4000-8000-000000000002": {
          "html": "<h2>Vaše mišljenje nam je važno</h2><p>Ova je ogledna anketa izrađena putem API-ja i objavljena kroz redovni tijek pregleda. Javna je i anonimna. Ne unosite osobne podatke.</p>"
        },
        "10000000-0000-4000-8000-000000000005": {
          "label": "Jednostavno"
        },
        "10000000-0000-4000-8000-000000000006": {
          "label": "Uglavnom jednostavno"
        },
        "10000000-0000-4000-8000-000000000007": {
          "label": "Treba poboljšati"
        },
        "10000000-0000-4000-8000-000000000004": {
          "text": "Koliko vam je jednostavno koristiti ankete?",
          "help_html": ""
        },
        "10000000-0000-4000-8000-000000000009": {
          "text": "Vaša ukupna ocjena",
          "help_html": ""
        },
        "10000000-0000-4000-8000-000000000011": {
          "text": "Što biste poboljšali?",
          "help_html": ""
        },
        "eaf6b9bc-2459-4849-8102-7343e047a09f": {
          "title": "Povratna informacija o radionici"
        }
      }
    },
    "rules": []
  }
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "survey": {
      "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
      "owner_user_id": 1,
      "primary_language": "hr",
      "timezone": "UTC",
      "state": "draft",
      "audience_mode": "application",
      "privacy_mode": "anonymous",
      "results_visibility": "private",
      "draft_revision_uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
      "published_revision_uuid": null,
      "lock_version": 1,
      "structure_locked": 0,
      "accepted_count": 0,
      "opens_at": null,
      "closes_at": null,
      "created_at": "2026-11-06 22:11:23",
      "updated_at": "2026-11-06 22:11:43"
    },
    "revision": {
      "uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
      "survey_uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
      "revision_number": 1,
      "state": "draft",
      "title": "Povratna informacija o radionici",
      "structure_fingerprint": "416e9b4b28f5f95ec65ba960d5a43f223fa6c228c66ed0a6c4c8f599402ea9fa",
      "content_fingerprint": "6bd8bf2556e986fde5847f5f4ca6fc272a1e2cb5b253080fd69e9ce4ff6e8e0f",
      "created_by_user_id": 1,
      "reviewed_by_user_id": null,
      "created_at": "2026-11-06 22:11:23"
    },
    "definition": {
      "settings": {
        "languages": [
          "hr",
          "en"
        ],
        "enabled_languages": [
          "hr",
          "en"
        ],
        "timezone": "Europe/Zagreb",
        "opens_at": null,
        "closes_at": null,
        "audience_mode": "public",
        "privacy_mode": "anonymous",
        "results_visibility": "private",
        "public_cookie_days": 30,
        "results_timing": "during",
        "results_audience": "public",
        "admission": [],
        "invitees_required": false,
        "show_question_codes": false,
        "show_choice_codes": false
      },
      "pages": [
        {
          "uuid": "10000000-0000-4000-8000-000000000001",
          "require_complete": true,
          "blocks": [
            {
              "uuid": "10000000-0000-4000-8000-000000000002",
              "kind": "html"
            },
            {
              "uuid": "10000000-0000-4000-8000-000000000003",
              "kind": "question",
              "question": {
                "uuid": "10000000-0000-4000-8000-000000000004",
                "type": "radio",
                "alias": "EASE",
                "required": true,
                "settings": [],
                "choices": [
                  {
                    "uuid": "10000000-0000-4000-8000-000000000005",
                    "code": "1",
                    "kind": "regular"
                  },
                  {
                    "uuid": "10000000-0000-4000-8000-000000000006",
                    "code": "2",
                    "kind": "regular"
                  },
                  {
                    "uuid": "10000000-0000-4000-8000-000000000007",
                    "code": "3",
                    "kind": "regular"
                  }
                ]
              }
            },
            {
              "uuid": "10000000-0000-4000-8000-000000000008",
              "kind": "question",
              "question": {
                "uuid": "10000000-0000-4000-8000-000000000009",
                "type": "rating",
                "alias": "RATING",
                "required": true,
                "settings": {
                  "min": 1,
                  "max": 5
                },
                "choices": []
              }
            },
            {
              "uuid": "10000000-0000-4000-8000-000000000010",
              "kind": "question",
              "question": {
                "uuid": "10000000-0000-4000-8000-000000000011",
                "type": "textarea",
                "alias": "COMMENT",
                "required": false,
                "settings": {
                  "max_length": 1000
                },
                "choices": []
              }
            }
          ]
        }
      ],
      "texts": {
        "hr": {
          "eaf6b9bc-2459-4849-8102-7343e047a09f": {
            "title": "Povratna informacija o radionici"
          },
          "10000000-0000-4000-8000-000000000001": {
            "title": "Vaše iskustvo"
          },
          "10000000-0000-4000-8000-000000000002": {
            "html": "<h2>Vaše mišljenje nam je važno</h2><p>Ova je ogledna anketa izrađena putem API-ja i objavljena kroz redovni tijek pregleda. Javna je i anonimna. Ne unosite osobne podatke.</p>"
          },
          "10000000-0000-4000-8000-000000000003": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000004": {
            "text": "Koliko vam je jednostavno koristiti ankete?",
            "help_html": ""
          },
          "10000000-0000-4000-8000-000000000005": {
            "label": "Jednostavno"
          },
          "10000000-0000-4000-8000-000000000006": {
            "label": "Uglavnom jednostavno"
          },
          "10000000-0000-4000-8000-000000000007": {
            "label": "Treba poboljšati"
          },
          "10000000-0000-4000-8000-000000000008": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000009": {
            "text": "Vaša ukupna ocjena",
            "help_html": ""
          },
          "10000000-0000-4000-8000-000000000010": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000011": {
            "text": "Što biste poboljšali?",
            "help_html": ""
          }
        },
        "en": {
          "eaf6b9bc-2459-4849-8102-7343e047a09f": {
            "title": "Workshop feedback"
          },
          "10000000-0000-4000-8000-000000000001": {
            "title": "Your experience"
          },
          "10000000-0000-4000-8000-000000000002": {
            "html": "<h2>Your feedback matters</h2><p>This example survey was created through the API and published through the normal review workflow. It is public and anonymous. Please enter no personal information.</p>"
          },
          "10000000-0000-4000-8000-000000000003": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000004": {
            "text": "How easy is it to use surveys?",
            "help_html": ""
          },
          "10000000-0000-4000-8000-000000000005": {
            "label": "Easy"
          },
          "10000000-0000-4000-8000-000000000006": {
            "label": "Mostly easy"
          },
          "10000000-0000-4000-8000-000000000007": {
            "label": "Needs improvement"
          },
          "10000000-0000-4000-8000-000000000008": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000009": {
            "text": "Your overall rating",
            "help_html": ""
          },
          "10000000-0000-4000-8000-000000000010": {
            "html": ""
          },
          "10000000-0000-4000-8000-000000000011": {
            "text": "What would you improve?",
            "help_html": ""
          }
        }
      },
      "rules": []
    },
    "rights": {
      "manage": true,
      "publish": true,
      "preview": true,
      "delete": true
    },
    "versions": [
      {
        "uuid": "f7134760-fb72-46be-8753-37e4fc658f0f",
        "survey_uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
        "revision_number": 1,
        "state": "draft",
        "title": "Povratna informacija o radionici",
        "structure_fingerprint": "416e9b4b28f5f95ec65ba960d5a43f223fa6c228c66ed0a6c4c8f599402ea9fa",
        "content_fingerprint": "6bd8bf2556e986fde5847f5f4ca6fc272a1e2cb5b253080fd69e9ce4ff6e8e0f",
        "created_by_user_id": 1,
        "reviewed_by_user_id": null,
        "created_at": "2026-11-06 22:11:23"
      }
    ]
  },
  "meta": {
    "request_id": "f8c16b4a617b7954567f8043c5fc66d9"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403, 409. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/exports`

**Scopo:** Crea un'esportazione limitata del gestore privato.

**Autorizzazione:** `surveys:export`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `from`, `include_identity`, `lang`, `mode`, `to`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "mode": "aggregate",
  "lang": "hr"
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "token": "[REDACTED]",
    "expires": 1794089656
  },
  "meta": {
    "request_id": "87f7f878891a1a9eeb6030c016dcfdb1"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/surveys/{survey}/exports/{token}`

**Scopo:** il download ricontrolla l'ambito della chiave, il proprietario attuale e i diritti di gestione attuali.

**Autorizzazione:** `surveys:export`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`, `token`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.csv \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports/[REDACTED]"
```

Risposta effettiva: **HTTP 200**.

```text
content-type: text/csv; charset=UTF-8
content-disposition: attachment; filename="survey-results.csv"
cache-control: private, no-store
```

Il file viene salvato come `response.*`. Il contenuto verificato ha 564 byte; non decodificarlo come JSON.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/surveys/{survey}/responses`

**Scopo:** l'ambito dei risultati da solo non è in grado di leggere risposte non elaborate.

**Autorizzazione:** `surveys:results`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri query:** `from`, `to`, `lang`, `page`, `per_page`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/responses?lang=hr&page=1&per_page=10"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "items": [],
    "total": 0,
    "page": 1,
    "pages": 1,
    "per_page": 10,
    "language": "hr",
    "filters": {
      "from": "",
      "to": ""
    }
  },
  "meta": {
    "request_id": "e6f818a6c72e45d14e81cc9bc24fc6ed"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/surveys/{survey}/results`

**Scopo:** le chiavi API non possono prendere in prestito la sessione del partecipante esterno del browser.

**Autorizzazione:** `surveys:results`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri query:** `from`, `to`, `lang`, `page`, `per_page`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/results?lang=hr&page=1&per_page=10"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "title": "Povratna informacija o radionici",
    "language": "hr",
    "languages": [
      "hr",
      "en"
    ],
    "manager": true,
    "privacy": "anonymous",
    "total": 0,
    "questions": [
      {
        "question": "Koliko vam je jednostavno koristiti ankete?",
        "type": "radio",
        "code": "",
        "answered": 0,
        "unanswered": 0,
        "suppressed": false,
        "series": [
          {
            "label": "",
            "base": 0,
            "cells": [
              {
                "label": "Jednostavno",
                "count": 0,
                "percent": 0
              },
              {
                "label": "Uglavnom jednostavno",
                "count": 0,
                "percent": 0
              },
              {
                "label": "Treba poboljšati",
                "count": 0,
                "percent": 0
              }
            ]
          }
        ],
        "statistics": []
      },
      {
        "question": "Vaša ukupna ocjena",
        "type": "rating",
        "code": "",
        "answered": 0,
        "unanswered": 0,
        "suppressed": false,
        "series": [
          {
            "label": "",
            "base": 0,
            "cells": [
              {
                "label": "1",
                "count": 0,
                "percent": 0
              },
              {
                "label": "2",
                "count": 0,
                "percent": 0
              },
              {
                "label": "3",
                "count": 0,
                "percent": 0
              },
              {
                "label": "4",
                "count": 0,
                "percent": 0
              },
              {
                "label": "5",
                "count": 0,
                "percent": 0
              }
            ]
          }
        ],
        "statistics": {
          "average": null,
          "minimum": null,
          "maximum": null
        }
      },
      {
        "question": "Što biste poboljšali?",
        "type": "textarea",
        "code": "",
        "answered": 0,
        "unanswered": 0,
        "suppressed": false,
        "series": [],
        "statistics": []
      }
    ],
    "question_total": 3,
    "page": 1,
    "pages": 1,
    "per_page": 10,
    "filters": {
      "from": "",
      "to": ""
    },
    "threshold": 5
  },
  "meta": {
    "request_id": "8e39299f410e693376022ae9c92e2a70"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/submit-review`

**Scopo:** invia la bozza corrente per la revisione.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 1,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/submit-review"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "submit-review"
  },
  "meta": {
    "request_id": "41d72f79366e63c5610b2486892f4891"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/return-review`

**Scopo:** un editore restituisce la bozza per modifiche.

**Autorizzazione:** `surveys:publish`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 2,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/return-review"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "return-review"
  },
  "meta": {
    "request_id": "b3fb60f7050f1cb8f9472bf68a0a9f0e"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/publish`

**Scopo:** Pubblica la revisione rivista.

**Autorizzazione:** `surveys:publish`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 6,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/publish"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "publish"
  },
  "meta": {
    "request_id": "46280486ab83f18775aae1fdc1213711"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403, 409. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/close`

**Scopo:** chiude la raccolta delle risposte.

**Autorizzazione:** `surveys:publish`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 7,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/close"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "close"
  },
  "meta": {
    "request_id": "4ab5027fe4df4091de6037b5d13f44f4"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/archive`

**Scopo:** Archivia il sondaggio.

**Autorizzazione:** `surveys:publish`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 8,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/archive"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "archive"
  },
  "meta": {
    "request_id": "935368dcefbb1bbcdc6dfd73963ccddf"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/withdraw`

**Scopo:** Ritira la bozza dalla revisione per la modifica.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 4,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/withdraw"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "withdraw"
  },
  "meta": {
    "request_id": "82758373ec56db91127f56291ff31322"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/copy`

**Scopo:** Crea una nuova bozza senza risposte precedenti.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 1,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true,
  "title": "API kopija za provjeru nacrta"
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/copy"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "26664ec4-13ce-45eb-82d1-fdac825f219b"
  },
  "meta": {
    "request_id": "37630bd30d1bff33f103c3397f8e3126"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/delete`

**Scopo:** Elimina in modo recuperabile il sondaggio e conserva le risposte.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 9,
  "revision": "f7134760-fb72-46be-8753-37e4fc658f0f",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/delete"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "eaf6b9bc-2459-4849-8102-7343e047a09f",
    "operation": "delete",
    "deleted": true
  },
  "meta": {
    "request_id": "f79755796c0cedf2a3ada17e594dbce7"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/discard-draft`

**Scopo:** Elimina la bozza di lavoro in base alle regole del flusso di lavoro documentate.

**Autorizzazione:** `surveys:write`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 0,
  "revision": "225cc989-f91a-4043-bea7-5d5f89eaf786",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/26664ec4-13ce-45eb-82d1-fdac825f219b/discard-draft"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "26664ec4-13ce-45eb-82d1-fdac825f219b",
    "operation": "discard-draft",
    "deleted": true
  },
  "meta": {
    "request_id": "ce5fdb5a30f5cc49eecfbc0879e37c1c"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/surveys/{survey}/restore`

**Scopo:** Un amministratore ripristina un sondaggio eliminato; la raccolta precedentemente pubblicata rimane chiusa.

**Autorizzazione:** `surveys:publish`. Autorizzazioni per la creazione, la pubblicazione e i risultati del sondaggio/area di lavoro corrente; nessuna imitazione del partecipante.

**Percorso:** `survey`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `confirmed`, `revision`, `title`, `version`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "version": 1,
  "revision": "225cc989-f91a-4043-bea7-5d5f89eaf786",
  "confirmed": true
}' \
  "$BASE_URL/api/v1/surveys/26664ec4-13ce-45eb-82d1-fdac825f219b/restore"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "uuid": "26664ec4-13ce-45eb-82d1-fdac825f219b",
    "operation": "restore",
    "deleted": false
  },
  "meta": {
    "request_id": "09b6e5dc6d714041f074fde62f7183dd"
  },
  "links": []
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
