# API: plantilla y ejemplos

<a id="api--template-and-examples"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Para desarrolladores: API](ankete-developeri_es.md)

Estos son ejemplos ilustrativos sin claves reales ni enlaces a encuestas existentes. Copie solo el contenido del bloque relevante en el archivo nombrado.

<a id="template"></a>

## api-feedback-template.json

La plantilla utiliza croata e inglés, los cuales deben instalarse en el objetivo. Ajuste los idiomas y el texto antes de enviar.

    {
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
            "10000000-0000-4000-8000-000000000012": {
              "title": "Workshop feedback"
            },
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
            }
          },
          "hr": {
            "10000000-0000-4000-8000-000000000012": {
              "title": "Povratna informacija o radionici"
            },
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
            }
          }
        },
        "rules": []
      }
    }

<a id="helper"></a>

## prepare_api_survey.py

El asistente lee la plantilla de la misma carpeta y reasigna los identificadores de la nueva encuesta y versión.

    #!/usr/bin/env python3
    """EN: Give the example fresh entity IDs for a newly created survey.
    HR: Primjeru dodjeljuje nove identifikatore za novokreiranu anketu.
    """
    import argparse
    import json
    import pathlib
    import re
    import uuid

    parser = argparse.ArgumentParser()
    parser.add_argument('--survey', required=True, type=uuid.UUID)
    parser.add_argument('--version', required=True, type=int)
    args = parser.parse_args()
    if args.version < 0:
        parser.error('version must be non-negative')
    template = json.loads(pathlib.Path(__file__).with_name('api-feedback-template.json').read_text(encoding='utf-8'))
    pattern = re.compile(r'^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$', re.I)
    original = next(iter(template['definition']['texts']['en']))
    identifiers = {original: str(args.survey)}

    def remap(value):
        if isinstance(value, str) and pattern.fullmatch(value):
            return identifiers.setdefault(value, str(uuid.uuid4()))
        if isinstance(value, dict):
            return {remap(key): remap(item) for key, item in value.items()}
        if isinstance(value, list):
            return [remap(item) for item in value]
        return value

    result = remap(template)
    result['version'] = args.version
    print(json.dumps(result, ensure_ascii=False, indent=2))

<a id="example-http-201-response"></a>

## Ejemplo HTTP 201 respuesta

    {
      "data": {
        "uuid": "10000000-0000-4000-8000-000000000001"
      },
      "meta": {
        "request_id": "example-request"
      },
      "links": []
    }

En uso real, lea siempre el identificador de su propia respuesta POST. No utilice el identificador que se muestra en este ejemplo.
