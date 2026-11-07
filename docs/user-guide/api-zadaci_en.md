# API — tasks in content

[← API — contents](api-koristenje_en.md)

Tasks are part of published page content. The API does not create their definition; authors add it in the HTML Editor or through structured content in the [page API](api-html-stranice_en.md). `task:read` requires access to the published page. `task:write` additionally checks the list's `toggle_scope` and current user permissions.

PUT requires a boolean `completed` and the current task GET's ETag. Changing state does not create another page version; history records the actor. History uses cursor pagination within a bounded available record window. Select the language with `lang`.

## Operations and verified examples

### `GET /api/v1/pages/{documentId}/tasks`

**Purpose:** Returns all tasks from the page's current publication.

**Permission:** `task:read`. Access to the published page and permission under the task-list state-change rule.

**Path:** `documentId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks?lang=hr&page[limit]=1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}`

**Purpose:** Returns one task and its current state.

**Permission:** `task:read`. Access to the published page and permission under the task-list state-change rule.

**Path:** `documentId`, `taskUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923?lang=hr"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/pages/{documentId}/tasks/{taskUuid}/history`

**Purpose:** Returns one task's state-change audit.

**Permission:** `task:read`. Access to the published page and permission under the task-list state-change rule.

**Path:** `documentId`, `taskUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/pages/api-plan-radionice/tasks/a7586a8f-d1e7-4bb4-bcb7-2273eb82e923/history?lang=hr&page[limit]=1"
```

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/pages/{documentId}/tasks/{taskUuid}/state`

**Purpose:** Sets task state without creating a new page version.

**Permission:** `task:write`. Access to the published page and permission under the task-list state-change rule.

**Path:** `documentId`, `taskUuid`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `completed`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

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

Actual response: **HTTP 200**.

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

**Errors verified for this operation:** 400, 401, 403, 422. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.
