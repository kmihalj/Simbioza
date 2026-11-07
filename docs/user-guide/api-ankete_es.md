# API: encuestas y resultados

<a id="api--surveys-and-results"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[← API — contenido](api-koristenje_es.md)

El API requiere un alcance y permisos actuales de encuesta/espacio de trabajo. `surveys:read` lee definiciones, `surveys:write` edita y envíe revisiones, `surveys:publish` revisa/publica y cambia los estados finales, `surveys:results` lee resultados permitidos y `surveys:export` crea exportaciones CSV privadas. El inicio de sesión de participantes externos no es una clave de API; REST no expone ningún punto final de envío de respuestas ni suplantación de participantes.

POST crea un borrador a partir de `title` y `primary_language`; opcional `placement` lo coloca en un espacio de trabajo sujeto a permisos de host. Debe instalarse `primary_language`. PATCH envíe el `definition` completo, no un fragmento parcial de la pregunta. Contiene `settings`, `pages`, `texts` y `rules`; Los UUID conectan preguntas, opciones y traducciones. `languages`/`enabled_languages` usan idiomas instalados y la instalación aplica su zona horaria.

GET la encuesta antes de cada actualización. Envíe `survey.lock_version` como `version` y `revision.uuid` como `revision` para las transiciones. Los resultados/exportaciones de la encuesta no tienen ETag; La creación utiliza su propio bloqueo optimista. Un `version` obsoleto devuelve 409. `meaning_unchanged=true` no elude la protección de las respuestas ya recopiladas.

Las acciones separadas a continuación son `submit-review`, `return-review`, `publish`, `close`, `archive`, `withdraw`, `copy`, `delete`, `discard-draft`, `restore`. Envíe `confirmed=true` donde se requiera confirmación. El envío espera a Una persona con permiso para publicar; Sólo una encuesta abierta y publicada acepta respuestas. `copy` devuelve un nuevo borrador UUID sin respuestas. Eliminar/descartar un nuevo borrador es recuperable; Solo administrador `restore` restaura una encuesta cerrada previamente publicada. Los administradores pueden obtener la versión de una encuesta eliminada de la lista `state=deleted`.

Las listas utilizan `q`, `state`, `page`, `per_page` (predeterminado 25, máximo 100). Los resultados utilizan `lang`, `from/to` como `YYYY-MM-DD`, `page` y `per_page` (predeterminado 10, máximo 25); Los filtros de fecha requieren derechos de administración. `results` pagina las preguntas, mientras que `responses` pagina los envíos. Las presentaciones sin procesar requieren permiso de la administración, no simplemente acceso a los resultados públicos.

CSV usa `mode=aggregate` o `responses`, idioma y fechas opcionales. `include_identity=true` se aplica únicamente a respuestas identificadas y a un gestor autorizado. La respuesta devuelve un `token` y `expires` privados que vencen. Almacene el token de forma privada y descárguelo como el mismo usuario autorizado. Todas las respuestas de la encuesta utilizan `Cache-Control: private, no-store`. El ejemplo PATCH incluye una definición válida completa; reemplace su clave de traducción de encuesta UUID con su propia UUID.

<a id="operations-and-verified-examples"></a>

## Operaciones y ejemplos verificados.

### `GET /api/v1/surveys`

**Propósito:** Devuelve únicamente definiciones autorizadas limitadas.

**Permiso:** `surveys:read`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Parámetros de consulta:** `page`, `per_page`, `q`, `state`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys?q=API&page=1&per_page=10"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys`

**Propósito:** Admite la ubicación de espacios de trabajo independientes o autorizados explícitamente.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `placement`, `primary_language`, `title`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 201**.

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

**Errores verificados para esta operación:** 401, 403, 422. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `GET /api/v1/surveys/{survey}`

**Propósito:** Devuelve únicamente definiciones autorizadas limitadas.

**Permiso:** `surveys:read`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 404. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `PATCH /api/v1/surveys/{survey}`

**Propósito:** Requiere la versión optimista y conserva las invariantes publicadas/recopiladas.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `definition`, `meaning_unchanged`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 409. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/exports`

**Propósito:** Crea una exportación de gestor privado limitada.

**Permiso:** `surveys:export`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `from`, `include_identity`, `lang`, `mode`, `to`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 201**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/surveys/{survey}/exports/{token}`

**Propósito:** La descarga vuelve a verificar el alcance de la clave, el propietario actual y los derechos de administración actuales.

**Permiso:** `surveys:export`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`, `token`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.csv \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports/[REDACTED]"
```

Respuesta real: **HTTP 200**.

```text
content-type: text/csv; charset=UTF-8
content-disposition: attachment; filename="survey-results.csv"
cache-control: private, no-store
```

El archivo se guarda como `response.*`. El contenido verificado tiene 564 bytes; no lo decodifique como JSON.

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/surveys/{survey}/responses`

**Propósito:** Un alcance de resultados por sí solo no puede leer respuestas sin procesar.

**Permiso:** `surveys:results`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `from`, `to`, `lang`, `page`, `per_page`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/responses?lang=hr&page=1&per_page=10"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `GET /api/v1/surveys/{survey}/results`

**Propósito:** Las claves API no pueden tomar prestada una sesión de participante externo de un navegador.

**Permiso:** `surveys:results`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** `from`, `to`, `lang`, `page`, `per_page`.

**Cuerpo:** sin campos de entrada adicionales; sigue el ejemplo.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/results?lang=hr&page=1&per_page=10"
```

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/submit-review`

**Propósito:** Presenta el borrador actual para su revisión.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/return-review`

**Propósito:** Una persona con permiso para publicar devuelve el borrador para realizar cambios.

**Permiso:** `surveys:publish`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/publish`

**Propósito:** Publica la revisión revisada.

**Permiso:** `surveys:publish`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403, 409. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado de los estados y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/close`

**Propósito:** Cierra la recopilación de respuestas.

**Permiso:** `surveys:publish`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/archive`

**Propósito:** Archiva la encuesta.

**Permiso:** `surveys:publish`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/withdraw`

**Propósito:** Retira el borrador de la revisión para editarlo.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/copy`

**Propósito:** Crea un nuevo borrador sin respuestas previas.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/delete`

**Propósito:** Elimina de forma recuperable la encuesta y conserva las respuestas.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/discard-draft`

**Propósito:** Descarta el borrador de trabajo según las reglas de flujo de trabajo documentadas.

**Permiso:** `surveys:write`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.

### `POST /api/v1/surveys/{survey}/restore`

**Propósito:** Un administrador restaura una encuesta eliminada; La colección publicada anteriormente permanece cerrada.

**Permiso:** `surveys:publish`. Permisos actuales de creación, publicación y resultados de encuestas/espacios de trabajo; sin suplantación de participante.

**Ruta:** `survey`. Tome los valores de la respuesta anterior. Una ID de página es su clave de documento; nodeId es numérico y UUID no es un slug.

**Parámetros de consulta:** ninguno.

**Cuerpo/campos admitidos:** `confirmed`, `revision`, `title`, `version`. El siguiente ejemplo utiliza valores verificados; Las condiciones y las reglas de reemplazo de listas se describen al comienzo del capítulo.

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

Respuesta real: **HTTP 200**.

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

**Errores verificados para esta operación:** 401, 403. Consulte [reglas compartidas](api-pocetak_es.md) para conocer el significado del estado y las respuestas a los problemas.
