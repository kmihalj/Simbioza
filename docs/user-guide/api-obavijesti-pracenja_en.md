# API — notifications and personal follows

[← API — contents](api-koristenje_en.md)

All operations apply to the key owner. A JSON body cannot select another user's inbox or preferences. `notifications:read` reads the inbox; `notifications:write` changes read state and deletes read notifications. Lists use `state=all`, `read` or `unread`, with cursor parameters. PATCH sends boolean `read` with an ETag; `read-all` marks the owner's whole inbox read. A foreign/missing UUID discloses no content; the current GET returns 422 `notification_validation_failed` for that case.

`follows:read`/`follows:write` manage personal follows. Types are `workspace`, `page`, `calendar`, `task_list`. A page's `target_id` is its **numeric workspace node ID**, not the HTML document key. Task lists also require `document_id`. Current access to the target is rechecked. Following a calendar and subscribing to it are different: removing a follow disables notifications without removing the calendar subscription.

Personal preferences include `email_enabled`, `email_mode` (`immediate`, `daily`, `important`, `off`), `notify_own_changes`, `theme_mode` (`auto`, `light`, `dark`, `system`). Theme choice applies only when global policy allows a personal mode. A follow may have `email_mode_override`; `null` inherits preferences. `GET personal-workspace` requires `workspace:read` and returns status; **it does not create a workspace**.

## Operations and verified examples

### `GET /api/v1/notifications`

**Purpose:** Returns a paginated inbox with an optional `state=all|read|unread` filter.

**Permission:** `notifications:read`. Only the key owner’s notifications.

**Query parameters:** `page[after]`, `page[limit]`, `state`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications?page[limit]=1&state=all"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/notifications/read-all`

**Purpose:** Marks all notifications owned by the key owner as read.

**Permission:** `notifications:write`. Only the key owner’s notifications.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{}' \
  "$BASE_URL/api/v1/notifications/read-all"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/notifications/{uuid}`

**Purpose:** Permanently removes one owned read notification.

**Permission:** `notifications:write`. Only the key owner’s notifications.

**Path:** `uuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/notifications/{uuid}`

**Purpose:** Returns one notification owned by the key owner.

**Permission:** `notifications:read`. Only the key owner’s notifications.

**Path:** `uuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/notifications/0f2851e0-660d-4f78-b8bc-c356d3ff1d9c"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/notifications/{uuid}`

**Purpose:** Sets the read/unread state of one owned notification.

**Permission:** `notifications:write`. Only the key owner’s notifications.

**Path:** `uuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `read`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/me/follow-preferences`

**Purpose:** Returns combined personal channel and cadence preferences.

**Permission:** `follows:read`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follow-preferences"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/me/follow-preferences`

**Purpose:** Partially updates personal preferences without changing another user's rows.

**Permission:** `follows:write`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Query parameters:** none.

**Body / supported fields:** `email_enabled`, `email_mode`, `notify_own_changes`, `theme_mode`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/me/follows`

**Purpose:** Returns ACL-filtered personal follows.

**Permission:** `follows:read`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Query parameters:** `search`, `type`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows?type=page&search=API"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/me/follows`

**Purpose:** Adds an owned follow after the domain ACL check.

**Permission:** `follows:write`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Query parameters:** none.

**Body / supported fields:** `document_id`, `email_mode_override`, `label`, `target_id`, `target_type`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

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

Actual response: **HTTP 201**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/me/follows/{type}/{targetId}`

**Purpose:** Removes only a follow owned by the key owner.

**Permission:** `follows:write`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Path:** `type`, `targetId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/follows/page/36"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/me/personal-workspace`

**Purpose:** Returns the API-key owner's personal Workspace without other users' data.

**Permission:** `workspace:read`. Only the key owner’s follows/preferences; current access to followed resources is rechecked.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me/personal-workspace"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.
