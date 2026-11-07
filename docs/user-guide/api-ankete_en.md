# API — surveys and results

[← API — contents](api-koristenje_en.md)

The API requires both a scope and current survey/workspace permissions. `surveys:read` reads definitions, `surveys:write` edits and submits review, `surveys:publish` reviews/publishes and changes final states, `surveys:results` reads permitted results, and `surveys:export` creates private CSV exports. External participant sign-in is not an API key; REST exposes no response-submission endpoint or participant impersonation.

POST creates a draft from `title` and `primary_language`; optional `placement` places it in a workspace subject to host permissions. `primary_language` must be installed. PATCH sends the whole `definition`, not a partial question fragment. It contains `settings`, `pages`, `texts` and `rules`; UUIDs connect questions, choices and translations. `languages`/`enabled_languages` use installed languages, and the installation enforces its timezone.

GET the survey before every update. Send `survey.lock_version` as `version`, and `revision.uuid` as `revision` for transitions. Survey results/exports have no ETag; authoring uses its own optimistic lock. A stale `version` returns 409. `meaning_unchanged=true` does not bypass protection of already collected responses.

The separate actions below are `submit-review`, `return-review`, `publish`, `close`, `archive`, `withdraw`, `copy`, `delete`, `discard-draft`, `restore`. Send `confirmed=true` where confirmation is required. Submission waits for a publisher; only a published, open survey accepts responses. `copy` returns a new draft UUID without responses. Delete/discard of a new draft is recoverable; administrator-only `restore` restores a previously published survey closed. Administrators can obtain a deleted survey's version from the `state=deleted` list.

Lists use `q`, `state`, `page`, `per_page` (default 25, maximum 100). Results use `lang`, `from/to` as `YYYY-MM-DD`, `page` and `per_page` (default 10, maximum 25); date filters require management rights. `results` paginates questions, while `responses` paginates submissions. Raw submissions require management permission, not merely access to public results.

CSV uses `mode=aggregate` or `responses`, language and optional dates. `include_identity=true` applies only to identified responses and an authorized manager. The response returns a private expiring `token` and `expires`. Store the token privately and download as the same authorized user. All Survey responses use `Cache-Control: private, no-store`. The PATCH example includes a complete valid definition; replace its survey UUID translation key with your own UUID.

## Operations and verified examples

### `GET /api/v1/surveys`

**Purpose:** Returns bounded authorized definitions only.

**Permission:** `surveys:read`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Query parameters:** `page`, `per_page`, `q`, `state`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys?q=API&page=1&per_page=10"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys`

**Purpose:** Supports standalone or explicitly authorized Workspace placement.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Query parameters:** none.

**Body / supported fields:** `placement`, `primary_language`, `title`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/surveys/{survey}`

**Purpose:** Returns bounded authorized definitions only.

**Permission:** `surveys:read`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 404. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/surveys/{survey}`

**Purpose:** Requires the optimistic version and preserves published/collected invariants.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `definition`, `meaning_unchanged`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 409. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/exports`

**Purpose:** Creates a bounded private manager export.

**Permission:** `surveys:export`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `from`, `include_identity`, `lang`, `mode`, `to`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/surveys/{survey}/exports/{token}`

**Purpose:** The download rechecks key scope, current owner and current management rights.

**Permission:** `surveys:export`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`, `token`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.csv \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports/[REDACTED]"
```

Actual response: **HTTP 200**.

```text
content-type: text/csv; charset=UTF-8
content-disposition: attachment; filename="survey-results.csv"
cache-control: private, no-store
```

The file is saved as `response.*`. The verified content has 564 bytes; do not decode it as JSON.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/surveys/{survey}/responses`

**Purpose:** A results scope alone cannot read raw answers.

**Permission:** `surveys:results`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `from`, `to`, `lang`, `page`, `per_page`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/responses?lang=hr&page=1&per_page=10"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/surveys/{survey}/results`

**Purpose:** API keys cannot borrow a browser's external participant session.

**Permission:** `surveys:results`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `from`, `to`, `lang`, `page`, `per_page`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/results?lang=hr&page=1&per_page=10"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/submit-review`

**Purpose:** Submits the current draft for review.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/return-review`

**Purpose:** A publisher returns the draft for changes.

**Permission:** `surveys:publish`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/publish`

**Purpose:** Publishes the reviewed revision.

**Permission:** `surveys:publish`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 409. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/close`

**Purpose:** Closes response collection.

**Permission:** `surveys:publish`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/archive`

**Purpose:** Archives the survey.

**Permission:** `surveys:publish`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/withdraw`

**Purpose:** Withdraws the draft from review for editing.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/copy`

**Purpose:** Creates a new draft without previous responses.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/delete`

**Purpose:** Recoverably deletes the survey and preserves responses.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/discard-draft`

**Purpose:** Discards the working draft under the documented workflow rules.

**Permission:** `surveys:write`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/surveys/{survey}/restore`

**Purpose:** An administrator restores a deleted survey; previously published collection remains closed.

**Permission:** `surveys:publish`. Current survey/workspace authoring, publication and results permissions; no participant impersonation.

**Path:** `survey`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `confirmed`, `revision`, `title`, `version`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.
