# API — obavijesti i osobna praćenja

[← API — sadržaj](api-koristenje_hr.md)

Sve radnje odnose se na vlasnika ključa. Ne možete JSON-om odabrati tuđi pretinac ili osobne postavke. `notifications:read` čita pretinac; `notifications:write` mijenja pročitano stanje i briše pročitane obavijesti. Popis koristi `state=all`, `read` ili `unread` i cursor parametre. PATCH šalje boolean `read` uz ETag; `read-all` označava cijeli vlastiti pretinac. Tuđi/nepostojeći UUID ne otkriva sadržaj; trenutačni dohvat za taj slučaj vraća 422 `notification_validation_failed`.

`follows:read`/`follows:write` upravljaju osobnim praćenjima. Vrste su `workspace`, `page`, `calendar`, `task_list`. `target_id` za stranicu je **brojčani ID čvora područja**, ne ključ HTML dokumenta. Za listu zadataka treba i `document_id`. Pristup cilju ponovno se provjerava. Praćenje kalendara i pretplata na kalendar nisu ista radnja: uklanjanje praćenja gasi obavijesti, a ne uklanja pretplatu.

Osobne postavke imaju `email_enabled`, `email_mode` (`immediate`, `daily`, `important`, `off`), `notify_own_changes`, `theme_mode` (`auto`, `light`, `dark`, `system`). Izbor teme vrijedi samo kad globalno pravilo dopušta osobni način. Pojedinačno praćenje može imati `email_mode_override`; `null` nasljeđuje osobne postavke. `GET personal-workspace` traži `workspace:read` i vraća status, **ne stvara područje**.

## Operacije i provjereni primjeri

### `GET /api/v1/notifications`

**Svrha:** Vraća paginirani inbox uz opcionalni `state=all|read|unread` filtar.

**Ovlast:** `notifications:read`. Samo pretinac vlasnika ključa.

**Query parametri:** `page[after]`, `page[limit]`, `state`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications?page[limit]=1&state=all"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
      "user_id": 1,
      "notification_key": "api.manual.example",
      "title": "API workshop reminder",
      "message": "Review the API example before the workshop.",
      "link_url": "/about",
      "source_module": "application",
      "source_reference": "api-manual",
      "dedup_key": "api-manual-notification",
      "data_json": null,
      "read_at": null,
      "created_at": "2026-11-06 22:48:09",
      "updated_at": "2026-11-06 22:48:09",
      "is_read": false,
      "data": []
    }
  ],
  "meta": {
    "request_id": "4e774695d4bd72dc9ac089f709b259bb",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/notifications?page%5Blimit%5D=1&state=all",
    "next": null
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/notifications/read-all`

**Svrha:** Označava sve obavijesti vlasnika ključa pročitanima.

**Ovlast:** `notifications:write`. Samo pretinac vlasnika ključa.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/notifications/read-all"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "updated": 0
  },
  "meta": {
    "request_id": "85f72e80bda9476062b33acf913e71e0"
  },
  "links": {
    "self": "/api/v1/notifications/read-all"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/notifications/{uuid}`

**Svrha:** Trajno uklanja jednu vlastitu pročitanu obavijest.

**Ovlast:** `notifications:write`. Samo pretinac vlasnika ključa.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/notifications/{uuid}`

**Svrha:** Vraća jednu obavijest koja pripada vlasniku ključa.

**Ovlast:** `notifications:read`. Samo pretinac vlasnika ključa.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": null,
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": false,
    "data": []
  },
  "meta": {
    "request_id": "c7fcdc0d24f01935cd578d7f201b921b"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/notifications/{uuid}`

**Svrha:** Postavlja pročitano/nepročitano stanje jedne vlastite obavijesti.

**Ovlast:** `notifications:write`. Samo pretinac vlasnika ključa.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `read`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "read": true
}' \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "0f2851e0-660d-4f78-b8bc-c356d3ff1d9c",
    "user_id": 1,
    "notification_key": "api.manual.example",
    "title": "API workshop reminder",
    "message": "Review the API example before the workshop.",
    "link_url": "/about",
    "source_module": "application",
    "source_reference": "api-manual",
    "dedup_key": "api-manual-notification",
    "data_json": null,
    "read_at": "2026-11-06 22:49:03",
    "created_at": "2026-11-06 22:48:09",
    "updated_at": "2026-11-06 22:48:09",
    "is_read": true,
    "data": []
  },
  "meta": {
    "request_id": "6a1d831097fc07de5556f61468f959b6"
  },
  "links": {
    "self": "/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/me/follow-preferences`

**Svrha:** Vraća objedinjene osobne postavke kanala i ritma.

**Ovlast:** `follows:read`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": null,
    "email_enabled": false
  },
  "meta": {
    "request_id": "98b2df0845176673b3e4ccdf749b2a12"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/me/follow-preferences`

**Svrha:** Djelomično ažurira osobne postavke bez promjene tuđih zapisa.

**Ovlast:** `follows:write`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Query parametri:** nema.

**Tijelo / podržana polja:** `email_enabled`, `email_mode`, `notify_own_changes`, `theme_mode`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "email_enabled": false,
  "email_mode": "off",
  "notify_own_changes": false
}' \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "user_id": 1,
    "email_mode": "off",
    "notify_own_changes": false,
    "theme_mode": "auto",
    "updated_at": "2026-11-06 22:49:04",
    "email_enabled": false
  },
  "meta": {
    "request_id": "0073fb95a0562c45f0f28bdebd4dcd34"
  },
  "links": {
    "self": "/api/v1/me/follow-preferences"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/me/follows`

**Svrha:** Vraća ACL-filtrirana osobna praćenja.

**Ovlast:** `follows:read`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Query parametri:** `search`, `type`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows?type=page&search=API"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
      "user_id": 1,
      "target_type": "page",
      "target_id": "36",
      "workspace_id": 4,
      "page_id": 36,
      "document_id": "api-plan-radionice",
      "label_snapshot": "Plan API radionice",
      "email_mode_override": null,
      "created_at": "2026-11-06 22:50:09",
      "updated_at": "2026-11-06 22:50:09",
      "accessible": true,
      "label": "Plan API radionice",
      "url": "/workspace/api-ogledno-podrucje/api-plan-radionice",
      "following": true,
      "automatic_excluded": false
    }
  ],
  "meta": {
    "request_id": "50ba4b674bc6f0ab007022025f0f4ecd"
  },
  "links": {
    "self": "/api/v1/me/follows?type=page&search=API"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/me/follows`

**Svrha:** Dodaje vlastito praćenje nakon domenske ACL provjere.

**Ovlast:** `follows:write`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Query parametri:** nema.

**Tijelo / podržana polja:** `document_id`, `email_mode_override`, `label`, `target_id`, `target_type`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "target_type": "page",
  "target_id": "36",
  "label": "API workshop plan"
}' \
  "$BASE_URL/api/v1/me/follows"
```

Stvarni odgovor: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "209f707b-18be-4ead-b9fc-4b796eb7c83c",
    "user_id": 1,
    "target_type": "page",
    "target_id": "36",
    "workspace_id": 4,
    "page_id": 36,
    "document_id": "api-plan-radionice",
    "label_snapshot": "Plan API radionice",
    "email_mode_override": null,
    "created_at": "2026-11-06 22:50:09",
    "updated_at": "2026-11-06 22:50:09",
    "accessible": true,
    "label": "Plan API radionice",
    "url": "/workspace/api-ogledno-podrucje/api-plan-radionice"
  },
  "meta": {
    "request_id": "21c4c68788859bf941d61d7d43fc8e8b"
  },
  "links": {
    "self": "/api/v1/me/follows"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/me/follows/{type}/{targetId}`

**Svrha:** Uklanja samo praćenje vlasnika ključa.

**Ovlast:** `follows:write`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Putanja:** `type`, `targetId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows/page/36"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/me/personal-workspace`

**Svrha:** Vraća osobno područje vlasnika API ključa bez tuđih podataka.

**Ovlast:** `workspace:read`. Samo praćenja i postavke vlasnika ključa; pravo pristupa praćenoj stvari zasebno se provjerava.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/personal-workspace"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "exists": false,
    "deleted": false,
    "automatic_creation_enabled": true,
    "self_creation_enabled": false,
    "can_create": false,
    "workspace": null
  },
  "meta": {
    "request_id": "2d5ab5b5583445fea4b560c4645137cd"
  },
  "links": {
    "self": "/api/v1/me/personal-workspace"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).
