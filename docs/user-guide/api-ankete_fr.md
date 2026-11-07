# API — sondages et résultats

<a id="api--surveys-and-results"></a>


> Les captures d’écran présentent l’interface en anglais.

[← API — contenu](api-koristenje_fr.md)

L’API nécessite à la fois une étendue et des autorisations d'interrogation/d'espace de travail actuelles. `surveys:read` lit les définitions, `surveys:write` modifie et soumet la révision, `surveys:publish` examine/publie et modifie les états finaux, `surveys:results` lit les résultats autorisés et `surveys:export` crée des exportations privées CSV. La connexion des participants externes n’est pas une clé API ; REST n’expose aucun point de terminaison de soumission de réponse ni d’usurpation d’identité de participant.

POST crée un brouillon à partir de `title` et `primary_language` ; facultatif `placement` le place dans un espace de travail soumis aux autorisations de l'hôte. `primary_language` doit être installé. PATCH envoie l'intégralité de `definition`, et non un fragment de question partiel. Il contient `settings`, `pages`, `texts` et `rules` ; Les UUID connectent les questions, les choix et les traductions. `languages`/`enabled_languages` utilisent les langues installées et l'installation applique son fuseau horaire.

GET le sondage avant chaque mise à jour. Envoyez `survey.lock_version` sous la forme `version` et `revision.uuid` sous la forme `revision` pour les transitions. Les résultats/exportations du sondage n’ont pas de ETag ; la création utilise son propre verrou optimiste. Un `version` obsolète renvoie 409. `meaning_unchanged=true` ne contourne pas la protection des réponses déjà collectées.

Les actions distinctes ci-dessous sont `submit-review`, `return-review`, `publish`, `close`, `archive`, `withdraw`, `copy`, `delete`, `discard-draft`, `restore`. Envoyez `confirmed=true` lorsqu'une confirmation est requise. La soumission attend un responsable de publication ; seul un sondage publié et ouvert accepte les réponses. `copy` renvoie un nouveau brouillon UUID sans réponses. La suppression/rejet d'un nouveau brouillon est récupérable ; `restore` réservé aux administrateurs restaure un sondage précédemment publié et fermé. Les administrateurs peuvent obtenir la version d'un sondage supprimé à partir de la liste `state=deleted`.

Les listes utilisent `q`, `state`, `page`, `per_page` (par défaut 25, maximum 100). Les résultats utilisent `lang`, `from/to` comme `YYYY-MM-DD`, `page` et `per_page` (par défaut 10, maximum 25) ; les filtres de date nécessitent des droits de gestion. `results` pagine les questions, tandis que `responses` pagine les soumissions. Les soumissions brutes nécessitent une autorisation de gestion, et pas seulement un accès aux résultats publics.

CSV utilise `mode=aggregate` ou `responses`, la langue et les dates facultatives. `include_identity=true` s'applique uniquement aux réponses identifiées et à un responsable autorisé. La réponse renvoie un `token` et un `expires` privés expirant. Stockez le jeton en privé et téléchargez-le en tant que même utilisateur autorisé. Toutes les réponses au sondage utilisent `Cache-Control: private, no-store`. L'exemple PATCH inclut une définition valide complète ; remplacez sa clé de traduction de sondage UUID par votre propre UUID.

<a id="operations-and-verified-examples"></a>

## Opérations et exemples vérifiés

### `GET /api/v1/surveys`

**Objectif :** Renvoie uniquement les définitions autorisées limitées.

**Autorisation :** `surveys:read`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Paramètres de requête :** `page`, `per_page`, `q`, `state`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys?q=API&page=1&per_page=10"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys`

**Objectif :** Prend en charge le placement d'espace de travail autonome ou explicitement autorisé.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `placement`, `primary_language`, `title`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 422. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/surveys/{survey}`

**Objectif :** Renvoie uniquement les définitions autorisées limitées.

**Autorisation :** `surveys:read`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 404. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `PATCH /api/v1/surveys/{survey}`

**Objectif :** Nécessite la version optimiste et préserve les invariants publiés/collectés.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `definition`, `meaning_unchanged`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 409. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/exports`

**Objectif :** Crée une exportation de gestionnaire privé limitée.

**Autorisation :** `surveys:export`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `from`, `include_identity`, `lang`, `mode`, `to`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 201**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/surveys/{survey}/exports/{token}`

**Objectif :** Le téléchargement revérifie la portée de la clé, le propriétaire actuel et les droits de gestion actuels.

**Autorisation :** `surveys:export`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`, `token`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.csv \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/exports/[REDACTED]"
```

Réponse réelle : **HTTP 200**.

```text
content-type: text/csv; charset=UTF-8
content-disposition: attachment; filename="survey-results.csv"
cache-control: private, no-store
```

Le fichier est enregistré sous `response.*`. Le contenu vérifié comporte 564 octets ; ne le décodez pas comme JSON.

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/surveys/{survey}/responses`

**Objectif :** Un périmètre de résultats ne peut pas lire à lui seul les réponses brutes.

**Autorisation :** `surveys:results`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `from`, `to`, `lang`, `page`, `per_page`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/responses?lang=hr&page=1&per_page=10"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `GET /api/v1/surveys/{survey}/results`

**Objectif :** Les clés API ne peuvent pas emprunter la session de participant externe d'un navigateur.

**Autorisation :** `surveys:results`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** `from`, `to`, `lang`, `page`, `per_page`.

**Corps :** aucun champ de saisie supplémentaire ; suivez l'exemple.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/surveys/eaf6b9bc-2459-4849-8102-7343e047a09f/results?lang=hr&page=1&per_page=10"
```

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/submit-review`

**Objectif :** Soumet le projet actuel pour examen.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/return-review`

**Objectif :** Un responsable de publication renvoie le brouillon pour modification.

**Autorisation :** `surveys:publish`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/publish`

**Objectif :** Publie la révision révisée.

**Autorisation :** `surveys:publish`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403, 409. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/close`

**Objectif :** Ferme la collecte de réponses.

**Autorisation :** `surveys:publish`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/archive`

**Objectif :** Archive le sondage.

**Autorisation :** `surveys:publish`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/withdraw`

**Objectif :** Retire le brouillon de la révision pour modification.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/copy`

**Objectif :** Crée un nouveau brouillon sans réponses précédentes.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/delete`

**Objectif :** Supprime le sondage de manière récupérable et conserve les réponses.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/discard-draft`

**Objectif :** Supprime le brouillon de travail selon les règles de flux de travail documentées.

**Autorisation :** `surveys:write`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.

### `POST /api/v1/surveys/{survey}/restore`

**Objectif :** Un administrateur restaure un sondage supprimé ; la collection précédemment publiée reste fermée.

**Autorisation :** `surveys:publish`. Autorisations actuelles de création, de publication et de résultats de sondages/espaces de travail ; aucune usurpation d’identité de participant.

**Chemin :** `survey`. Prenez les valeurs de la réponse précédente. Un identifiant de page est sa clé de document ; nodeId est numérique et un UUID n'est pas un slug.

**Paramètres de requête :** aucun.

**Corps/Champs pris en charge :** `confirmed`, `revision`, `title`, `version`. L'exemple ci-dessous utilise des valeurs vérifiées ; les conditions et les règles de remplacement de liste sont décrites au début du chapitre.

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

Réponse réelle : **HTTP 200**.

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

**Erreurs vérifiées pour cette opération :** 401, 403. Voir [règles partagées](api-pocetak_fr.md) pour connaître la signification des états et les réponses aux problèmes.
