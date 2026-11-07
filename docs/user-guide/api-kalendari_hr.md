# API — kalendari i događaji

[← API — sadržaj](api-koristenje_hr.md)

`calendar:read` čita kalendare dostupne vlasniku ključa. `calendar:write` dopunjuje stvarne ovlasti stvaranja, pisanja i upravljanja kalendarom. Definicija kalendara traži `name`; `type`/`calendar_type` su `personal`, `team` ili `resource`, uz pravila o vlasniku i upravljanju.

PATCH i DELETE kalendara zahtijevaju ETag. PUT ACL prima cijelu listu `rules`: `subject_type` (`user`/`group`), brojčani `subject_id`, `can_read`, `can_write`. Zamjenjuje izravna pravila, zato prije upisa pročitajte trenutačno stanje.

Događaj traži `title` i valjane početak/završetak (`starts_at`, `ends_at`), uz `timezone`. `is_all_day` označava cjelodnevni događaj. Podržane su i datumske/vremenske varijante te aliasi navedeni uz operaciju. Ponavljanja koriste `recurrence_rule` ili zasebna polja učestalosti/završetka. GET događaja za raspon traži `from` i `to`; `expand_recurring=false` dohvaća definicije, a prošireni prikaz vraća pojavljivanja. ID događaja je broj, dok je identifikator kalendara u putanji UUID.

ICS uvoz šalje tekst u `ics`, opcionalni brojčani `calendar_id` i `merge_mode=skip` ili `update`. Bez postojećeg kalendara stvaranje podliježe stvarnim ovlastima i postavkama. ICS izvoz preuzima kalendar; ova ruta nema filtre `from/to`. Datumsku filtraciju koristite na popisu događaja.

Ovaj REST API ne izlaže zasebne radnje poziva sudionicima, odgovora na poziv i odobravanja rezervacije. Dodavanje takvih polja običnom POST-u događaja ne zamjenjuje tijek sastanka. [CalDAV i ICS protokoli](api-granice-protokola_hr.md) odvojeni su od Bearer REST API-ja.

## Operacije i provjereni primjeri

### `GET /api/v1/calendars`

**Svrha:** Vraća kalendare vidljive vlasniku API ključa.

**Ovlast:** `calendar:read`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Query parametri:** `page[after]`, `page[limit]`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars?page[limit]=1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Sintetički raspored",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "e1fc33cb335d26e476aea1056e00b0a2",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/calendars`

**Svrha:** Kreira novi kalendar uz domensku provjeru prava.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Query parametri:** nema.

**Tijelo / podržana polja:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API radionice",
  "type": "team",
  "description": "Sintetički raspored",
  "color": "#1677ff",
  "is_enabled": true,
  "is_public_read": false,
  "is_authenticated_read": false
}' \
  "$BASE_URL/api/v1/calendars"
```

Stvarni odgovor: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "070d314efe35cc19720e469157dac2f9"
  },
  "links": {
    "self": "/api/v1/calendars"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/calendars/import`

**Svrha:** Uvozi iCalendar tekst u postojeći ili novi kalendar.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Query parametri:** nema.

**Tijelo / podržana polja:** `calendar_id`, `calendar_type`, `color`, `description`, `ics`, `is_authenticated_read`, `is_public_read`, `merge_mode`, `name`, `public_order`, `show_on_public_index`, `show_public_link`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "calendar_id": 1,
  "merge_mode": "skip",
  "ics": "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Simbioza API manual//EN\r\nBEGIN:VEVENT\r\nUID:api-manual-import@example.invalid\r\nDTSTAMP:20261106T100000Z\r\nDTSTART:20261112T090000Z\r\nDTEND:20261112T100000Z\r\nSUMMARY:Imported API event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
}' \
  "$BASE_URL/api/v1/calendars/import"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "calendar": {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Ažurirano API rasporedom.",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": false,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "created": 1,
    "updated": 0,
    "skipped": 0,
    "total": 1
  },
  "meta": {
    "request_id": "5e6f724385a764a7bbb5089da3bc8f01"
  },
  "links": {
    "self": "/api/v1/calendars/import"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/calendars/{calendarUuid}`

**Svrha:** Briše jedan kalendar kada domenski ACL to dopušta.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/calendars/{calendarUuid}`

**Svrha:** Dohvaća jedan kalendar po javnom UUID-u.

**Ovlast:** `calendar:read`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "1386cd671b7e38072e364bc34947d19c"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403, 404. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/calendars/{calendarUuid}`

**Svrha:** Djelomično mijenja jedan kalendar.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

**Prije upisa:** osvježite GET i postavite `ETAG` iz njegova zaglavlja `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano API rasporedom."
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "48783b78a64fa19566ee89854bffdb99"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 400, 401, 403, 412, 428. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PUT /api/v1/calendars/{calendarUuid}/acl`

**Svrha:** Zamjenjuje sva user/group ACL pravila kalendara.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `rules`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "rules": [
    {
      "subject_type": "user",
      "subject_id": 2,
      "can_read": true,
      "can_write": true
    }
  ]
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "dc07257329738af268ffc971c7195390"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/calendars/{calendarUuid}/events`

**Svrha:** Vraća događaje kalendara u zadanom rasponu.

**Ovlast:** `calendar:read`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** `expand_recurring`, `from`, `page[after]`, `page[limit]`, `to`.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
      "calendar_id": 1,
      "title": "API radionica",
      "description": "Provjera stvarnih događaja",
      "location": "Dvorana A",
      "starts_at": "2026-11-10 10:00:00",
      "ends_at": "2026-11-10 11:00:00",
      "is_all_day": false,
      "timezone": "Europe/Zagreb",
      "recurrence_rule": "",
      "instance_key": "1",
      "is_recurring": false,
      "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
    }
  ],
  "meta": {
    "request_id": "5095e41c647508f3f3c0dbd83e2a9e60",
    "page": {
      "limit": 50,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false",
    "next": null
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `POST /api/v1/calendars/{calendarUuid}/events`

**Svrha:** Kreira događaj u kalendaru.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica",
  "description": "Provjera stvarnih događaja",
  "location": "Dvorana A",
  "starts_at": "2026-11-10T10:00:00",
  "ends_at": "2026-11-10T11:00:00",
  "timezone": "Europe/Zagreb"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
```

Stvarni odgovor: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "f4ea6360950cf37c84849ba4b9425892"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `DELETE /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Svrha:** Briše jedan događaj iz kalendara.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`, `eventId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Stvarni odgovor: **HTTP 204**. Tijelo je prazno.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Svrha:** Dohvaća jedan događaj iz kalendara navedenog u URL-u.

**Ovlast:** `calendar:read`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`, `eventId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "fb8d07b66a9f22a0af23e3ec01c284d7"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `PATCH /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Svrha:** Djelomično mijenja jedan događaj.

**Ovlast:** `calendar:write`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`, `eventId`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo / podržana polja:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. Primjer ispod sadrži provjeren skup vrijednosti; uvjeti i zamjena lista opisani su na početku poglavlja.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica — ažurirano"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica — ažurirano",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "e4b119585f634b8b131a717ab7fdbaefcc129dcf"
  },
  "meta": {
    "request_id": "4aa9f46489ec917f0e3db3fcb19d348f"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/calendars/{calendarUuid}/export.ics`

**Svrha:** Preuzima čitljivi kalendar kao standardnu ICS datoteku.

**Ovlast:** `calendar:read`. Trenutačna Calendar prava vlasnika/administratora/ACL-a korisnika i grupa; pravo čitanja odvojeno je od pisanja i upravljanja.

**Putanja:** `calendarUuid`. Vrijednosti uzmite iz prethodnog odgovora. ID stranice je ključ dokumenta; nodeId je broj čvora, a UUID nije slug.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.ics \
  "$BASE_URL/api/v1/calendars/e8eaf356-1af4-4a05-8943-263752ef910b/export.ics"
```

Stvarni odgovor: **HTTP 200**.

```text
content-type: text/calendar; charset=utf-8
content-disposition: attachment; filename="administrator-personal-calendar.ics"
content-length: 189
```

Datoteka je zapisana u `response.*`. Provjereni sadržaj ima 189 bajtova; ne pokušavajte ga dekodirati kao JSON.

**Pogreške provjerene za ovu operaciju:** 401, 403. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).
