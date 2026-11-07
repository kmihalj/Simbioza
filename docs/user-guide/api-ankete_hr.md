# API — ankete i rezultati

[← API — sadržaj](api-koristenje_hr.md)

API zahtijeva scope i stvarne ovlasti ankete/područja. `surveys:read` pregledava definicije, `surveys:write` uređuje i šalje na pregled, `surveys:publish` pregledava/objavljuje i mijenja završna stanja, `surveys:results` čita dopuštene rezultate, a `surveys:export` stvara privatni CSV. Vanjska prijava ispitanika nije API ključ; REST ne izlaže predaju odgovora niti dopušta oponašanje ispitanika.

POST stvara nacrt s `title` i `primary_language`; opcionalni `placement` smješta ga u područje uz ovlast domaćina. `primary_language` mora biti instaliran. PATCH šalje cijelu `definition`, ne parcijalni komadić pitanja. Ona sadrži `settings`, `pages`, `texts` i `rules`; UUID-ovi povezuju pitanja, izbore i prijevode. `languages`/`enabled_languages` koriste instalirane jezike, a vremensku zonu potvrđuje instalacija.

Prije svake izmjene pročitajte GET ankete. `survey.lock_version` pošaljite kao `version`, a `revision.uuid` kao `revision` za prijelaze. Izvoz i rezultati nemaju ETag; upisi ankete koriste vlastitu optimističku kontrolu. Stari `version` vraća 409. `meaning_unchanged=true` nije način za zaobilaženje zaštite već prikupljenih odgovora.

Radnje su odvojeno prikazane dolje: `submit-review`, `return-review`, `publish`, `close`, `archive`, `withdraw`, `copy`, `delete`, `discard-draft`, `restore`. Za potvrđene radnje pošaljite `confirmed=true`. Poslana anketa čeka objavljivača; tek objavljena i otvorena prima odgovore. `copy` vraća UUID novog nacrta bez odgovora. Brisanje/odbacivanje novog nacrta je oporavljivo; `restore` je administratorski i vraća prethodno objavljenu anketu zatvorenu. Verziju obrisane ankete administrator može pročitati na popisu `state=deleted`.

Popis koristi `q`, `state`, `page`, `per_page` (zadano 25, najviše 100). Rezultati koriste `lang`, `from/to` u obliku `YYYY-MM-DD`, `page` i `per_page` (zadano 10, najviše 25); datumski filtri dopušteni su upravitelju. `results` paginira pitanja, a `responses` predaje. Izvorne predaje traže pravo upravljanja, ne samo javne rezultate.

CSV šalje `mode=aggregate` ili `responses`, jezik i opcionalne datumske filtre; `include_identity=true` vrijedi samo za identificirane odgovore i ovlaštenog upravitelja. Odgovor vraća vremenski ograničeni `token` i `expires`. Sačuvajte token privatno i preuzmite ga istim ovlaštenim korisnikom. Svi Survey odgovori imaju `Cache-Control: private, no-store`. Predložak s cijelom valjanom definicijom nalazi se u PATCH primjeru; zamijenite UUID ankete u prijevodima svojim UUID-om.

## Operacije i provjereni primjeri

### `GET /api/v1/surveys`

**Svrha:** Vraća samo ograničene ovlaštene definicije.

**Ovlast:** `surveys:read`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Query parametri:** `page`, `per_page`, `q`, `state`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys?q=API&page=1&per_page=10"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys`

**Svrha:** Podržava samostalni ili izričito ovlašteni smještaj u području.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Query parametri:** nema.

**Tijelo / podržana polja:** `placement`, `primary_language`, `title`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/surveys/{survey}`

**Svrha:** Vraća samo ograničene ovlaštene definicije.

**Ovlast:** `surveys:read`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 404. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/surveys/{survey}`

**Svrha:** Traži optimističku verziju i čuva invarijante objave/prikupljanja.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `definition`, `meaning_unchanged`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 409. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/exports`

**Svrha:** Izrađuje ograničeni privatni upravljački izvoz.

**Ovlast:** `surveys:export`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `from`, `include_identity`, `lang`, `mode`, `to`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/surveys/{survey}/exports/{token}`

**Svrha:** Preuzimanje provjerava scope ključa, trenutačnog vlasnika i važeća prava upravljanja.

**Ovlast:** `surveys:export`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`, `token`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.csv \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports/[REDACTED]"
```

Stvarni odgovor: **HTTP 200**.

```text
content-type: text/csv; charset=UTF-8
content-disposition: attachment; filename="survey-results.csv"
cache-control: private, no-store
```

Datoteka je zapisana u `response.*`. Provjereni sadržaj ima 564 bajtova; ne pokušavajte ga dekodirati kao JSON.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/surveys/{survey}/responses`

**Svrha:** Sam scope rezultata ne otvara izvorne odgovore.

**Ovlast:** `surveys:results`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `from`, `to`, `lang`, `page`, `per_page`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/responses?lang=hr&page=1&per_page=10"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/surveys/{survey}/results`

**Svrha:** API ključevi ne mogu preuzeti vanjsku sesiju ispitanika iz preglednika.

**Ovlast:** `surveys:results`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `from`, `to`, `lang`, `page`, `per_page`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/results?lang=hr&page=1&per_page=10"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/submit-review`

**Svrha:** Pošle trenutni nacrt na pregled.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/return-review`

**Svrha:** Objavljivač vraća nacrt na doradu.

**Ovlast:** `surveys:publish`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/publish`

**Svrha:** Objavljuje pregledanu reviziju.

**Ovlast:** `surveys:publish`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403, 409. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/close`

**Svrha:** Zatvara prikupljanje odgovora.

**Ovlast:** `surveys:publish`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/archive`

**Svrha:** Arhivira anketu.

**Ovlast:** `surveys:publish`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/withdraw`

**Svrha:** Povlači nacrt s pregleda radi uređivanja.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/copy`

**Svrha:** Stvara novi nacrt bez prethodnih odgovora.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/delete`

**Svrha:** Oporavljivo briše anketu i čuva odgovore.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/discard-draft`

**Svrha:** Odbacuje radni nacrt uz pravila opisnog tijeka.

**Ovlast:** `surveys:write`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/surveys/{survey}/restore`

**Svrha:** Administrator vraća obrisanu anketu; objavljena ostaje zatvorena.

**Ovlast:** `surveys:publish`. Aktualna prava autora/upravitelja/objavljivača i pravila rezultata; API ne koristi vanjsku sesiju ispitanika i ne prima njegove predaje.

**Putanja:** `survey`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `confirmed`, `revision`, `title`, `version`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).
