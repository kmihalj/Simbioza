# API — korisnici i grupe

[← API — sadržaj](api-koristenje_hr.md)

Ove radnje zahtijevaju administratorskog vlasnika ključa i odgovarajući `users:*` ili `groups:*` scope. API upravlja lokalnim računima i lokalnim grupama. Običan korisnik ni s tim scopeovima ne može pregledavati imenik administratora.

Za novog korisnika obvezni su `login_identifier` i valjana lozinka; za grupu `name`. `attributes` sadrži profilne atribute. `display_name`, `first_name`, `last_name` i `email` mogu biti i izravna polja; `provider_access` je mapa dopuštenih prijava (`local`, `saml`, `cas`, `oidc`, `oauth2`). `group_ids` je lista brojčanih ID-ova lokalnih grupa. PUT članstva zamjenjuje cijeli popis, ne samo dodaje jednu grupu.

PATCH mijenja zadana polja i zahtijeva barem jedno polje. Za izmjenu korisnika/grupe, članstva i brisanje potreban je aktualni ETag. DELETE korisnika deaktivira račun i opoziva njegove API ključeve; nije brisanje svih povezanih poslovnih podataka. Sistemske i vanjski upravljane grupe/računi imaju dodatna ograničenja. U primjeru zamijenite `[REDACTED]` vlastitom jakom lozinkom.

## Operacije i provjereni primjeri

### `GET /api/v1/groups`

**Svrha:** Vraća popis lokalnih grupa.

**Ovlast:** `groups:read`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Query parametri:** `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups?page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/groups`

**Svrha:** Kreira lokalnu nesistemsku grupu.

**Ovlast:** `groups:manage`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Query parametri:** nema.

**Tijelo / podržana polja:** `is_enabled`, `name`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 400, 401, 403, 409, 415. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/groups/{groupId}`

**Svrha:** Briše lokalnu nesistemsku grupu i njezina članstva.

**Ovlast:** `groups:manage`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `groupId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/groups/5"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/groups/{groupId}`

**Svrha:** Vraća jednu lokalnu grupu.

**Ovlast:** `groups:read`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `groupId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/groups/5"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/groups/{groupId}`

**Svrha:** Mijenja lokalnu nesistemsku grupu.

**Ovlast:** `groups:manage`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `groupId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `is_enabled`, `name`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 400, 401, 403, 412, 422, 428. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/users`

**Svrha:** Vraća cursor-paginirani popis lokalnih korisnika.

**Ovlast:** `users:read`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Query parametri:** `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users?page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/users`

**Svrha:** Kreira lokalnog korisnika.

**Ovlast:** `users:create`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Query parametri:** nema.

**Tijelo / podržana polja:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

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

Stvarni odgovor: **HTTP 201**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/users/{userId}`

**Svrha:** Deaktivira lokalnog korisnika i opoziva njegove API ključeve.

**Ovlast:** `users:delete`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `userId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/users/4"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/users/{userId}`

**Svrha:** Vraća jednog lokalnog korisnika.

**Ovlast:** `users:read`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `userId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/users/4"
```

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/users/{userId}`

**Svrha:** Djelomično mijenja lokalnog korisnika.

**Ovlast:** `users:update`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `userId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `attributes`, `display_name`, `email`, `first_name`, `group_ids`, `is_active`, `is_admin`, `last_name`, `login_identifier`, `password`, `provider_access`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PUT /api/v1/users/{userId}/groups`

**Svrha:** Zamjenjuje ručna članstva lokalnog korisnika.

**Ovlast:** `groups:manage`. Administrator vlasnik ključa; scope sam nije dovoljan. DELETE korisnika deaktivira račun i opoziva njegove ključeve.

**Putanja:** `userId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `group_ids`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

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

Stvarni odgovor: **HTTP 200**.

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

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).
