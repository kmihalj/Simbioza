# API — webhookovi i isporuke

[← API — sadržaj](api-koristenje_hr.md)

Webhook obavještava drugi sustav o događaju bez stalnog provjeravanja popisa. Administrator mora dopustiti webhookove i odredišne hostove. Potreban je `webhooks:read` ili `webhooks:manage`; običan korisnik upravlja pretplatama stvorenima istim API ključem, a administrator s odgovarajućim scopeom vidi i upravlja svima.

POST traži `name`, dopušteni HTTPS `target_url`, listu `events` (npr. `['*']`) i opcionalni boolean `active`. Tajna za HMAC potpis vraća se samo pri stvaranju i rotaciji; spremite je na odredišnom sustavu. Ostali GET odgovori je ne vraćaju. PATCH/DELETE/rotate-secret i ponavljanje isporuke koriste ETag.

Promjena sadržaja dodaje isporuku u red čekanja. GET `deliveries` prikazuje pokušaje, status i odgovor odredišta. `retry` ponovno raspoređuje postojeću isporuku; ne znači da ju je udaljeni sustav već primio. Zasebni zakazani worker obavlja slanje; njegovo pokretanje mora biti podešeno na poslužitelju. REST primjer ovdje provjeren je s redom čekanja, bez slanja na vanjsku adresu.

Potpis i format webhook poruke opisani su u tehničkoj dokumentaciji modula API. Odredište mora provjeriti HMAC-SHA256 potpis nad izvornim bajtovima tijela i zaštititi se od ponavljanja; API ključ kojim upravljate pretplatom nije potpisna tajna webhooka.

## Operacije i provjereni primjeri

### `GET /api/v1/webhooks`

**Svrha:** Vraća paginirani popis pretplata dostupnih aktualnom ključu.

**Ovlast:** `webhooks:read`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Query parametri:** `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks?page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "b621cf9d278e6902b10f96a7c2f6d79e",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/webhooks?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/webhooks`

**Svrha:** Kreira pretplatu i samo u ovom odgovoru vraća novu potpisnu tajnu.

**Ovlast:** `webhooks:manage`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Query parametri:** nema.

**Tijelo / podržana polja:** `active`, `events`, `name`, `target_url`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API observer",
  "target_url": "https://example.com/hooks/simbioza",
  "events": [
    "*"
  ],
  "active": true
}' \
  "$BASE_URL/api/v1/webhooks"
```

Stvarni odgovor: **HTTP 201**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "673b6de22148feaba0dc6b194655cda1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/webhooks/{uuid}`

**Svrha:** Trajno uklanja pretplatu uz optimističku zaštitu.

**Ovlast:** `webhooks:manage`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/webhooks/{uuid}`

**Svrha:** Vraća jednu dopuštenu pretplatu bez potpisne tajne.

**Ovlast:** `webhooks:read`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "ce22c9cc6d5493b57b2b1026ddc69a27"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 404. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/webhooks/{uuid}`

**Svrha:** Mijenja pretplatu samo ako klijent pošalje aktualni If-Match.

**Ovlast:** `webhooks:manage`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `active`, `events`, `name`, `target_url`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "name": "API observer updated"
}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
    "owner_api_key_id": 1,
    "owner_user_id": 1,
    "name": "API observer updated",
    "target_url": "https://example.com/hooks/simbioza",
    "events": [
      "*"
    ],
    "active": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a50a566b4cb87453a1d4835a86d8ab96"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 422. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/webhooks/{uuid}/deliveries`

**Svrha:** Vraća paginiranu povijest isporuka jedne pretplate.

**Ovlast:** `webhooks:read`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 7,
      "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
      "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
      "event": "calendar_events.deleted",
      "status": "pending",
      "attempts": 0,
      "available_at": "2026-11-06 23:08:58",
      "delivered_at": null,
      "response_status": null,
      "response_body": null,
      "last_error": null,
      "created_at": "2026-11-06 23:08:58",
      "updated_at": "2026-11-06 23:08:58"
    }
  ],
  "meta": {
    "request_id": "79ac9c3580e735f6146257343e05f331",
    "page": {
      "limit": 1,
      "total": 7,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1",
    "next": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}`

**Svrha:** Vraća jedan pokušaj isporuke.

**Ovlast:** `webhooks:read`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`, `deliveryUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "a1a068157f70f58e25aaca7e441ee20b"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry`

**Svrha:** Vraća postojeću isporuku u outbox uz provjeru njezina ETag-a.

**Ovlast:** `webhooks:manage`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`, `deliveryUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 7,
    "uuid": "f122cfca-2466-4280-b400-f5c9efa5b5ee",
    "event_id": "a1e4ee61-0e44-4bbe-b070-bb4f45446646",
    "event": "calendar_events.deleted",
    "status": "pending",
    "attempts": 0,
    "available_at": "2026-11-06 23:08:58",
    "delivered_at": null,
    "response_status": null,
    "response_body": null,
    "last_error": null,
    "created_at": "2026-11-06 23:08:58",
    "updated_at": "2026-11-06 23:08:58"
  },
  "meta": {
    "request_id": "186121f98aed702b812e57d49f3b06c8"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/deliveries/f122cfca-2466-4280-b400-f5c9efa5b5ee/retry"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/webhooks/{uuid}/rotate-secret`

**Svrha:** Rotira tajnu uz If-Match i novu vrijednost vraća samo jednom.

**Ovlast:** `webhooks:manage`. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Putanja:** `uuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "subscription": {
      "id": 1,
      "uuid": "c3219935-f00c-4ccd-aa2d-ed155ecc9831",
      "owner_api_key_id": 1,
      "owner_user_id": 1,
      "name": "API observer updated",
      "target_url": "https://example.com/hooks/simbioza",
      "events": [
        "*"
      ],
      "active": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:58"
    },
    "secret": "[REDACTED]"
  },
  "meta": {
    "request_id": "205b440690d3939f8600c5588c94c0b1"
  },
  "links": {
    "self": "/api/v1/webhooks/c3219935-f00c-4ccd-aa2d-ed155ecc9831/rotate-secret"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

## Odredište — potpis i ponovljene poruke

Poslužitelj šalje POST sa sljedećim zaglavljima. Potpis provjerite prije JSON dekodiranja; promjena razmaka ili ponovna JSON serializacija mijenja potpis.

```text
Content-Type: application/json
X-HeartPhrame-Webhook-Id: <event UUID>
X-HeartPhrame-Webhook-Event: <event name>
X-HeartPhrame-Webhook-Timestamp: <Unix seconds>
X-HeartPhrame-Webhook-Signature: v1=<hex HMAC-SHA256>
```

```php
<?php
/**
 * EN: Checks a webhook signature over the unmodified raw body.
 * HR: Provjerava potpis webhooka nad neizmijenjenim izvornim tijelom.
 */
function verifySimbiozaWebhook(
    string $rawBody,
    string $timestamp,
    string $signature,
    #[SensitiveParameter] string $secret,
    ?int $now = null,
): bool {
    if (!ctype_digit($timestamp) || abs(($now ?? time()) - (int)$timestamp) > 300) {
        return false;
    }
    $expected = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
    return hash_equals($expected, $signature);
}
```

Prag od 300 sekundi ovdje je politika primjera. Spremite i već obrađene `X-HeartPhrame-Webhook-Id` vrijednosti u trajno spremište i nemojte drugi put provoditi istu poslovnu radnju. Valjan potpis sam ne sprječava ponavljanje poruke.

Dopušteni odabiri događaja su točno ime, nazivni prostor s `.*` (npr. `pages.*`) ili `*`. Tijelo webhooka sadrži identitet događaja i pročišćene metapodatke mutacije; ne šalje izvorni zahtjev ni cijeli sadržaj dokumenta.

## Worker na poslužitelju

Ovo su administratorske CLI naredbe na vlastitom poslužitelju, odvojene od REST zahtjeva:

```sh
vendor/bin/hph api webhooks:status
vendor/bin/hph api webhooks:worker --batch-size=20
vendor/bin/hph api webhooks:worker --watch --sleep=5
```

`--watch` pokreće kontinuirani proces i zato ga na produkciji održava upravitelj procesa. Ne pokrećite više ručnih probnih isporuka bez potrebe. Za pojedinačni neuspješan događaj koristite provjeru povijesti i REST `retry` kada otklonite uzrok.
