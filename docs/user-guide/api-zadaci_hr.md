# API — zadaci u sadržaju

[← API — sadržaj](api-koristenje_hr.md)

Zadaci su dio sadržaja objavljene stranice. API ne stvara njihovu definiciju: autor je dodaje u HTML Editoru ili kroz strukturirani sadržaj [API-ja stranica](api-html-stranice_hr.md). `task:read` traži pravo čitanja objavljene stranice; `task:write` dodatno provjerava dopušta li pravilo liste (`toggle_scope`) i stvarna ovlast korisnika promjenu statusa.

PUT prima obvezni boolean `completed`, uz ETag aktualnog GET-a zadatka. Promjena ne stvara novu verziju stranice; povijest bilježi tko je promijenio stanje. Povijest koristi cursor paginaciju i vraća ograničen dostupni prozor zapisa. Jezična inačica se bira `lang` parametrom.

## Operacije i provjereni primjeri

### `GET /api/v1/pages/{documentId}/tasks`

**Svrha:** Vraća sve taskove iz aktualne objave stranice.

**Ovlast:** `task:read`. Čitanje stranice s definicijom zadatka i domenske ovlasti izmjene stanja. API ne kreira definicije zadataka.

**Putanja:** `documentId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks?lang=hr&page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "text": "Pregledati upute",
      "toggle_scope": "viewers",
      "state": {
        "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
        "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
        "completed": false,
        "updated_by_user_id": null,
        "updated_by_display_name": "",
        "updated_at": "",
        "version": 0
      }
    }
  ],
  "meta": {
    "request_id": "8520a08a8fe81a37a03f5a9e0644ba2d",
    "page": {
      "limit": 1,
      "total": 2,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1",
    "next": "/api/v1/pages/api-plan-radionice/tasks?lang=hr&page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}`

**Svrha:** Vraća jedan task i njegovo aktualno stanje.

**Ovlast:** `task:read`. Čitanje stranice s definicijom zadatka i domenske ovlasti izmjene stanja. API ne kreira definicije zadataka.

**Putanja:** `documentId`, `taskUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "text": "Pregledati upute",
    "toggle_scope": "viewers",
    "state": {
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": false,
      "updated_by_user_id": null,
      "updated_by_display_name": "",
      "updated_at": "",
      "version": 0
    }
  },
  "meta": {
    "request_id": "8e191e76e361b0b48ab9c57852a654a2"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}/history`

**Svrha:** Vraća audit promjena statusa jednog taska.

**Ovlast:** `task:read`. Čitanje stranice s definicijom zadatka i domenske ovlasti izmjene stanja. API ne kreira definicije zadataka.

**Putanja:** `documentId`, `taskUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`, `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "uuid": "e8222ff1-2de8-4e43-b7ea-3632fdc5e9fb",
      "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
      "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
      "completed": true,
      "changed_by_user_id": 1,
      "changed_by_display_name": "Administrator",
      "changed_at": "2026-11-06 22:49:03"
    }
  ],
  "meta": {
    "request_id": "fb30976da54e1510df5467626307a71b",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page%5Blimit%5D=1",
    "next": null
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PUT /api/v1/pages/{documentId}/tasks/{taskUuid}/state`

**Svrha:** Postavlja status taska bez stvaranja nove verzije stranice.

**Ovlast:** `task:write`. Čitanje stranice s definicijom zadatka i domenske ovlasti izmjene stanja. API ne kreira definicije zadataka.

**Putanja:** `documentId`, `taskUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `lang`.

**Tijelo / podržana polja:** `completed`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "completed": true
}' \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "task_uuid": "a7586a8f-d1e7-4bb4-bcb7-2273eb82e923",
    "list_uuid": "076e4a13-3797-4bdd-9fa8-618e832bffd0",
    "completed": true,
    "updated_by_user_id": 1,
    "updated_by_display_name": "Administrator",
    "updated_at": "2026-11-06 22:49:03",
    "version": 1
  },
  "meta": {
    "request_id": "32fc68d59ba7a6ba20b12cc7736128f5"
  },
  "links": {
    "self": "/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/state?lang=hr"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 400, 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).
