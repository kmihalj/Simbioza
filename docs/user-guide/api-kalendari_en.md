# API — calendars and events

[← API — contents](api-koristenje_en.md)

`calendar:read` reads calendars accessible to the key owner. `calendar:write` supplements current creation, write and management permissions. A calendar needs `name`; `type`/`calendar_type` can be `personal`, `team` or `resource`, subject to ownership and management rules.

Calendar PATCH and DELETE require an ETag. ACL PUT accepts the complete `rules` list: `subject_type` (`user`/`group`), numeric `subject_id`, `can_read`, `can_write`. It replaces direct rules, so read current state before writing.

An event needs `title` and valid start/end values (`starts_at`, `ends_at`), with `timezone`. `is_all_day` marks all-day events. Date/time variants and aliases are listed per operation. Recurrence uses `recurrence_rule` or separate frequency/end fields. Event-range GET requires `from` and `to`; `expand_recurring=false` reads definitions, while expansion returns occurrences. Event IDs are numeric, while calendar paths use a UUID.

ICS import sends the text in `ics`, optional numeric `calendar_id`, and `merge_mode=skip` or `update`. Creating a calendar instead of importing into an existing one is subject to current permissions and settings. ICS export downloads the calendar; that route has no `from/to` filters. Use the event list for date-range filtering.

This REST API has no separate invitation, attendee-response or reservation-approval operations. Adding such fields to a normal event POST does not invoke the meeting workflow. [CalDAV and ICS protocols](api-granice-protokola_en.md) are separate from the Bearer REST API.

## Operations and verified examples

### `GET /api/v1/calendars`

**Purpose:** Returns calendars visible to the API-key owner.

**Permission:** `calendar:read`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Query parameters:** `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars?page[limit]=1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/calendars`

**Purpose:** Creates a new calendar with domain permission checks.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Query parameters:** none.

**Body / supported fields:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/calendars/import`

**Purpose:** Imports iCalendar text into an existing or new calendar.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Query parameters:** none.

**Body / supported fields:** `calendar_id`, `calendar_type`, `color`, `description`, `ics`, `is_authenticated_read`, `is_public_read`, `merge_mode`, `name`, `public_order`, `show_on_public_index`, `show_public_link`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/calendars/{calendarUuid}`

**Purpose:** Deletes one calendar when its domain ACL permits it.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/calendars/{calendarUuid}`

**Purpose:** Fetches one calendar by its public UUID.

**Permission:** `calendar:read`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 404. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/calendars/{calendarUuid}`

**Purpose:** Partially updates one calendar.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 400, 401, 403, 412, 428. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/calendars/{calendarUuid}/acl`

**Purpose:** Replaces all user/group ACL rules of a calendar.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `rules`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/calendars/{calendarUuid}/events`

**Purpose:** Returns calendar events inside the requested range.

**Permission:** `calendar:read`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `expand_recurring`, `from`, `page[after]`, `page[limit]`, `to`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/calendars/{calendarUuid}/events`

**Purpose:** Creates an event in a calendar.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Purpose:** Deletes one event from a calendar.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`, `eventId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Purpose:** Fetches one event from the calendar named in the URL.

**Permission:** `calendar:read`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`, `eventId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Purpose:** Partially updates one event.

**Permission:** `calendar:write`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`, `eventId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/calendars/{calendarUuid}/export.ics`

**Purpose:** Downloads a readable calendar as a standard ICS file.

**Permission:** `calendar:read`. Current calendar ownership, read/write/management permissions and user/group ACLs.

**Path:** `calendarUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.ics \
  "$BASE_URL/api/v1/calendars/e8eaf356-1af4-4a05-8943-263752ef910b/export.ics"
```

Actual response: **HTTP 200**.

```text
content-type: text/calendar; charset=utf-8
content-disposition: attachment; filename="administrator-personal-calendar.ics"
content-length: 189
```

The file is saved as `response.*`. The verified content has 189 bytes; do not decode it as JSON.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.
