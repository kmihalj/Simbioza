# API – Benutzer und Gruppen

<a id="api--users-and-groups"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[← API — Inhalt](api-koristenje_de.md)

Für diese Vorgänge sind ein Schlüsselbesitzer mit Administratorrechten und der entsprechende Scope `users:*` oder `groups:*` erforderlich. Sie verwalten lokale Konten und lokale Gruppen. Ein normaler Benutzer kann das Verwaltungsverzeichnis nicht allein aufgrund dieser Scopes lesen.

Ein neuer Benutzer benötigt `login_identifier` und ein gültiges Passwort; eine Gruppe benötigt `name`. `attributes` enthält Profilattribute. `display_name`, `first_name`, `last_name` und `email` unterstützen auch die Eingabe auf oberster Ebene. `provider_access` bildet zulässige Anmeldemethoden ab (`local`, `saml`, `cas`, `oidc`, `oauth2`). `group_ids` ist eine Liste numerischer lokaler Gruppen-IDs. Die Mitgliedschaft PUT ersetzt die gesamte Liste, anstatt eine Gruppe hinzuzufügen.

PATCH erfordert mindestens ein geändertes Feld. Für Benutzer-/Gruppenaktualisierungen, das Ersetzen und Löschen von Mitgliedschaften ist die aktuelle Version ETag erforderlich. DELETE-Benutzer deaktiviert das Konto und widerruft seine API-Schlüssel; es werden nicht alle zugehörigen Geschäftsdaten gelöscht. Für vom System und extern verwaltete Konten/Gruppen gelten zusätzliche Einschränkungen. Ersetzen Sie `[REDACTED]` im Beispiel durch Ihr eigenes sicheres Passwort.

<a id="operations-and-verified-examples"></a>

## Operationen und verifizierte Beispiele

### `GET /api/v1/groups`

**Zweck:** Gibt die lokale Gruppenliste zurück.

**Berechtigung:** `groups:read`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Abfrageparameter:** `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups?page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/groups`

**Zweck:** Erstellt eine lokale Nicht-Systemgruppe.

**Berechtigung:** `groups:manage`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `is_enabled`, `name`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

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

Tatsächliche Antwort: **HTTP 201**.

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

**Für diesen Vorgang überprüfte Fehler:** 400, 401, 403, 409, 415. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `DELETE /api/v1/groups/{groupId}`

**Zweck:** Löscht eine lokale Nicht-Systemgruppe und ihre Mitgliedschaften.

**Berechtigung:** `groups:manage`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `groupId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/groups/5"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/groups/{groupId}`

**Zweck:** Gibt eine lokale Gruppe zurück.

**Berechtigung:** `groups:read`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `groupId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups/5"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PATCH /api/v1/groups/{groupId}`

**Zweck:** Aktualisiert eine lokale Nicht-Systemgruppe.

**Berechtigung:** `groups:manage`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `groupId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `is_enabled`, `name`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 400, 401, 403, 412, 422, 428. Statusbedeutungen und Problemreaktionen finden Sie unter [gemeinsame Regeln](api-pocetak_de.md).

### `GET /api/v1/users`

**Zweck:** Gibt eine mit dem Cursor paginierte Liste lokaler Benutzer zurück.

**Berechtigung:** `users:read`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Abfrageparameter:** `page[after]`, `page[limit]`.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users?page[limit]=1"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `POST /api/v1/users`

**Zweck:** Erstellt einen lokalen Benutzer.

**Berechtigung:** `users:create`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

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

Tatsächliche Antwort: **HTTP 201**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `DELETE /api/v1/users/{userId}`

**Zweck:** Deaktiviert einen lokalen Benutzer und widerruft seine API-Schlüssel.

**Berechtigung:** `users:delete`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `userId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/users/4"
```

Tatsächliche Antwort: **HTTP 204**. Der Körper ist leer.

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `GET /api/v1/users/{userId}`

**Zweck:** Gibt einen lokalen Benutzer zurück.

**Berechtigung:** `users:read`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `userId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Körper:** keine zusätzlichen Eingabefelder; Folgen Sie dem Beispiel.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users/4"
```

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PATCH /api/v1/users/{userId}`

**Zweck:** Aktualisiert teilweise einen lokalen Benutzer.

**Berechtigung:** `users:update`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `userId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.

### `PUT /api/v1/users/{userId}/groups`

**Zweck:** Ersetzt die manuellen Mitgliedschaften eines lokalen Benutzers.

**Berechtigung:** `groups:manage`. Schlüsselbesitzer mit Administratorrechten; der Umfang allein reicht nicht aus.

**Pfad:** `userId`. Übernehmen Sie die Werte aus der vorherigen Antwort. Eine Seiten-ID ist ihr Dokumentschlüssel; nodeId ist numerisch und ein UUID ist kein Slug.

**Abfrageparameter:** keine.

**Textkörper / unterstützte Felder:** `group_ids`. Das folgende Beispiel verwendet verifizierte Werte; Bedingungen und Listenersetzungsregeln werden am Anfang des Kapitels beschrieben.

**Vor dem Schreiben:** Aktualisieren Sie GET und legen Sie `ETAG` aus dem Header `ETag` fest.

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

Tatsächliche Antwort: **HTTP 200**.

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

**Für diesen Vorgang überprüfte Fehler:** 401, 403. Siehe [gemeinsame Regeln](api-pocetak_de.md) für Statusbedeutungen und Problemreaktionen.
