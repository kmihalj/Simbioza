# API — workspaces, trees, permissions and themes

[← API — contents](api-koristenje_en.md)

`workspace:read` reads accessible workspaces and trees. `workspace:manage` only manages them when the owner has the required current permissions. Global homepage settings, deleted-workspace lists and restore require an administrator. Workspace deletion is recoverable.

Create a workspace with `name`; supply `slug` or let the system derive it. `visibility` is `public`, `authenticated` or `restricted`. Translation maps are `name_translations`/`description_translations`; `primary_language` and `supported_languages` must match installed languages. `tree_visibility` and `contents_visibility` control tree/content presentation, without bypassing ACLs.

ACL PUT **replaces all direct assignments**. `subjects` is a list of `{type,id,permissions}` objects. Workspace `type` supports `user` or `group`; permissions are `can_view`, `can_add`, `can_edit`, `can_publish`, `can_delete`, `can_manage`. Inherited/system rights are not removed simply by omitting them. The individual node-restriction API currently accepts only users already assigned to the workspace; it is not a complete API for every access exception supported by the web interface.

POST `nodes` creates only `internal_link` or `external_link` nodes. Create HTML documents with the [page API](api-html-stranice_en.md). Reordering needs the complete `placements` list, each with `id`, `parent_id` and `sort_order`. Changing node metadata does not edit its HTML document.

`shorts` uses `page`, `limit`, `depth`, `order`; `limit=all` is available only below100 visible entries. It does not use `per_page`. Theme endpoints manage a **workspace private theme**, not the global site theme. Upload an image in multipart `asset` with `role`; theme ZIP import uses `theme`. Image upload returns the entire theme model and its `assets` list, not a single image object. Find the added `file` there. Only unused images can be deleted. Theme export requires an administrator.

HTML export returns a ZIP of accessible published pages, optionally narrowed by `node_ids`. It does not recreate functional surveys. It is not a backup of responses, history and settings.

Homepage settings use `public_target`/`authenticated_target`: `default`, `page:<nodeId>` or `shorts:<workspaceId>`, plus presentation flags. Personal PUT accepts `selection` as a page ID (0 restores default) or a target object, only when the administrator enables personal selection.

## Operations and verified examples

### `GET /api/v1/workspaces`

**Purpose:** Returns workspaces visible to the API key owner.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces?page[limit]=1"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "uuid": "4cd08aa0-7377-400e-a330-469da66b60f6",
      "slug": "editing-guide",
      "name": "Editing guide",
      "name_translations": {
        "en": "Editing guide"
      },
      "description": "Example pages for collaborative work.",
      "description_translations": {
        "en": "Example pages for collaborative work."
      },
      "visibility": "public",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": false,
      "created_at": "2026-11-06 22:35:54",
      "updated_at": "2026-11-06 22:35:54",
      "deleted_at": "",
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "613cd338ec6a74ef447a39df54e39cea",
    "page": {
      "limit": 1,
      "total": 4,
      "has_more": true,
      "next_cursor": "eyJvZmZzZXQiOjF9"
    }
  },
  "links": {
    "self": "/api/v1/workspaces?page%5Blimit%5D=1",
    "next": "/api/v1/workspaces?page%5Blimit%5D=1&page%5Bafter%5D=eyJvZmZzZXQiOjF9"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces`

**Purpose:** Creates a new Workspace subject to the application's creation policy.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** none.

**Body / supported fields:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "Ogledno API područje",
  "slug": "api-ogledno-podrucje",
  "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
  "visibility": "restricted"
}' \
  "$BASE_URL/api/v1/workspaces"
```

Actual response: **HTTP 201**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "40109227f06c007cdfd19c562fd53308"
  },
  "links": {
    "self": "/api/v1/workspaces"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/deleted`

**Purpose:** Returns the administrator-only list of soft-deleted workspaces.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** `page[after]`, `page[limit]`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/deleted?page[limit]=100"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 4,
      "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
      "slug": "api-ogledno-podrucje",
      "name": "Ogledno API područje",
      "name_translations": {
        "en": "Ogledno API područje"
      },
      "description": "Ažurirano preko API-ja.",
      "description_translations": {
        "en": "Ažurirano preko API-ja."
      },
      "visibility": "restricted",
      "tree_visibility": "inherit",
      "contents_visibility": "inherit",
      "is_archived": false,
      "is_deleted": true,
      "created_at": "2026-11-06 22:44:20",
      "updated_at": "2026-11-06 23:20:32",
      "deleted_at": "2026-11-06 23:20:32",
      "permissions": []
    }
  ],
  "meta": {
    "request_id": "337679c1372f56ec4cc2e5510beb8f0b",
    "page": {
      "limit": 100,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/workspaces/deleted?page%5Blimit%5D=100",
    "next": null
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces/deleted/{workspaceId}/restore`

**Purpose:** Restores a soft-deleted Workspace under an available slug.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `slug`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "slug": "api-ogledno-podrucje"
}' \
  "$BASE_URL/api/v1/workspaces/deleted/4/restore"
```

Actual response: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 23:20:32",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "22962d6ee7bb686754b06152ff10ba62"
  },
  "links": {
    "self": "/api/v1/workspaces/deleted/4/restore"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/homepage/preference`

**Purpose:** Returns the API-key owner's personal homepage when selection is enabled.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "efe455081ae8c6a2599cf235290fa053"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/homepage/preference`

**Purpose:** Stores the API-key owner's personal homepage after a fresh ACL check.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** none.

**Body / supported fields:** `selection`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "selection": 0
}' \
  "$BASE_URL/api/v1/workspaces/homepage/preference" | jq '{data: (.data | {selectedNodeId,selectedTarget,selectedTargetValue,selectionUnavailable,viewOptionsReady}), meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "selectedNodeId": 0,
    "selectedTarget": {
      "type": "default",
      "node_id": 0,
      "workspace_id": 0,
      "show_tree": true,
      "show_display_options": true
    },
    "selectedTargetValue": "default",
    "selectionUnavailable": false,
    "viewOptionsReady": true
  },
  "meta": {
    "request_id": "c0575c57cbeb8fb18fdd07daa34ad587"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/preference"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/homepage/settings`

**Purpose:** Returns administrator-only public and authenticated homepage policy.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "0bb93172f325cd23a0fd269bbf59e7f5"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/homepage/settings`

**Purpose:** Stores administrator-only public and authenticated homepage policy.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Query parameters:** none.

**Body / supported fields:** `allow_user_selection`, `authenticated_show_display_options`, `authenticated_show_tree`, `authenticated_target`, `public_show_display_options`, `public_show_tree`, `public_target`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "public_target": "default",
  "authenticated_target": "default",
  "public_show_tree": true,
  "public_show_display_options": true,
  "authenticated_show_tree": true,
  "authenticated_show_display_options": true,
  "allow_user_selection": true
}' \
  "$BASE_URL/api/v1/workspaces/homepage/settings" | jq '{data: (.data | {settings,view_options_ready}), meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "settings": {
      "public_node_id": 0,
      "authenticated_node_id": 0,
      "public_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "authenticated_target": {
        "type": "default",
        "node_id": 0,
        "workspace_id": 0,
        "show_tree": true,
        "show_display_options": true
      },
      "allow_user_selection": true
    },
    "view_options_ready": true
  },
  "meta": {
    "request_id": "4e069414636ec0f7a9ae9b197dd191e1"
  },
  "links": {
    "self": "/api/v1/workspaces/homepage/settings"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/workspaces/{workspaceSlug}`

**Purpose:** Soft-deletes a Workspace with the effective can_manage permission.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}`

**Purpose:** Returns one Workspace when the key owner may view it.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Actual response: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Sintetički sadržaj za provjeru cjelokupnog API-ja.",
    "description_translations": {
      "en": "Sintetički sadržaj za provjeru cjelokupnog API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "e0e85fd92c7a001921444f4175228ec0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errors verified for this operation:** 401, 403, 404. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/workspaces/{workspaceSlug}`

**Purpose:** Partially updates a Workspace with the effective can_manage permission.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `contents_visibility`, `description`, `description_translations`, `is_archived`, `name`, `name_translations`, `primary_language`, `slug`, `supported_languages`, `tree_visibility`, `visibility`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

**Before writing:** refresh the GET and set `ETAG` from its `ETag` header.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano preko API-ja."
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje"
```

Actual response: **HTTP 200**.

```json
{
  "data": {
    "id": 4,
    "uuid": "25fcd84a-f5cd-4361-ad82-aa56ad37eae5",
    "slug": "api-ogledno-podrucje",
    "name": "Ogledno API područje",
    "name_translations": {
      "en": "Ogledno API područje"
    },
    "description": "Ažurirano preko API-ja.",
    "description_translations": {
      "en": "Ažurirano preko API-ja."
    },
    "visibility": "restricted",
    "tree_visibility": "inherit",
    "contents_visibility": "inherit",
    "is_archived": false,
    "is_deleted": false,
    "created_at": "2026-11-06 22:44:20",
    "updated_at": "2026-11-06 22:44:20",
    "deleted_at": "",
    "permissions": {
      "can_view": true,
      "can_add": true,
      "can_edit": true,
      "can_publish": true,
      "can_delete": true,
      "can_manage": true
    }
  },
  "meta": {
    "request_id": "135a815aac013227e2c0dad8cb3a7e86"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje"
  }
}
```

**Errors verified for this operation:** 400, 401, 403, 412, 428. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/acl`

**Purpose:** Returns the direct Workspace ACL to a user who may manage it.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 1,
      "label": "Administrator",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      }
    }
  ],
  "meta": {
    "request_id": "b6aa7159a0807a86e4ecec28837632c8"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/{workspaceSlug}/acl`

**Purpose:** Replaces the complete Workspace ACL.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `subjects`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "c7bc6b2bc47f17e6da17a6f25bba9d5b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/acl/subjects`

**Purpose:** Searches users or groups for the Workspace ACL picker.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `category`, `q`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 2,
      "label": "Ana Author",
      "type": "user",
      "category": "user",
      "is_builtin": false,
      "is_read_only": false
    }
  ],
  "meta": {
    "request_id": "a00b8b79c556ffb217e854bb4df6781f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/acl/subjects?category=user&q=Ana"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces/{workspaceSlug}/exports/html`

**Purpose:** Downloads an ACL-filtered offline HTML ZIP of a Workspace or selected pages.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `node_ids`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_ids": [
    36
  ]
}' \
  --dump-header response-headers.txt \
  --output response.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/exports/html"
```

Actual response: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="simbioza-api-ogledno-podrucje-20261106-231026.zip"
content-length: 117723
```

The file is saved as `response.*`. The verified content has 117723 bytes; do not decode it as JSON.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces/{workspaceSlug}/nodes`

**Purpose:** Adds an internal or external link to the tree.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `contents_visibility`, `is_homepage`, `node_type`, `parent_id`, `primary_language`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "node_type": "internal_link",
  "title": "api-internal",
  "slug": "api-internal",
  "target_url": "/about",
  "sort_order": 200
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes"
```

Actual response: **HTTP 201**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "api-internal",
    "title_translations": {
      "en": "api-internal"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "b75c597c0414ff8e3cf18d976e9f03a9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Purpose:** Deletes a link node; documents remain the Editor API's responsibility.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`, `nodeId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Actual response: **HTTP 204**. The body is empty.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}`

**Purpose:** Updates structural data for one tree node.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`, `nodeId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `contents_visibility`, `is_homepage`, `labels`, `node_type`, `parent_id`, `primary_language`, `properties`, `route_name`, `slug`, `sort_order`, `supported_languages`, `target_url`, `title`, `title_translations`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API poveznica"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
```

Actual response: **HTTP 200**.

```json
{
  "data": {
    "id": 37,
    "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
    "parent_id": null,
    "node_type": "internal_link",
    "slug": "api-internal",
    "title": "API poveznica",
    "title_translations": {
      "en": "API poveznica"
    },
    "document_key": "",
    "route_name": "",
    "target_url": "/about",
    "sort_order": 200,
    "is_homepage": false,
    "contents_visibility": "inherit",
    "labels": [],
    "properties": [],
    "permissions": []
  },
  "meta": {
    "request_id": "89fef7d6bff70c6a19e5dc398783d33f"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Purpose:** Returns direct ACL restrictions for one node.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`, `nodeId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Actual response: **HTTP 200**.

```json
{
  "data": [],
  "meta": {
    "request_id": "a376426ba7d7e2cdbd47d084edcd9e9e"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/{workspaceSlug}/nodes/{nodeId}/acl`

**Purpose:** Replaces direct ACL restrictions for one node.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`, `nodeId`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `subjects`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "subjects": [
    {
      "type": "user",
      "id": 2,
      "permissions": {
        "can_view": true
      }
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "type": "user",
      "id": 2,
      "label": "Ana Author",
      "is_builtin": false,
      "is_read_only": false,
      "permissions": {
        "can_view": true,
        "can_add": false,
        "can_edit": false,
        "can_publish": false,
        "can_delete": false,
        "can_manage": false
      }
    }
  ],
  "meta": {
    "request_id": "3991706d28cfe0d2ce50650e27891ff0"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/nodes/37/acl"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/shorts`

**Purpose:** Returns ACL-filtered summaries of published Workspace pages.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`, `page`, `limit`, `depth`, `order`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
```

Actual response: **HTTP 200**.

```json
{
  "data": {
    "tree": [
      {
        "id": 40,
        "uuid": "f85dfce8-f123-4dd9-969a-f29b068dae06",
        "workspace_id": 4,
        "parent_id": null,
        "node_type": "document",
        "slug": "api-vodic-za-integracije",
        "title": "Vodič za integracije",
        "title_translations": "{\"en\":\"Vodič za integracije\",\"hr\":\"Vodič za integracije\"}",
        "document_key": "api-vodic-za-integracije",
        "route_name": null,
        "target_url": null,
        "sort_order": 100,
        "is_homepage": 1,
        "is_tree_hidden": 0,
        "is_enabled": 1,
        "contents_visibility": "inherit",
        "created_by_user_id": 1,
        "updated_by_user_id": 1,
        "created_at": "2026-11-06 23:24:07",
        "updated_at": "2026-11-06 23:24:07",
        "is_tree_hidden_effective": false,
        "is_tree_temporarily_visible": false,
        "permissions": {
          "can_view": true,
          "can_add": true,
          "can_edit": true,
          "can_publish": true,
          "can_delete": true,
          "can_manage": true
        },
        "children": [],
        "title_translations_map": {
          "en": "Vodič za integracije",
          "hr": "Vodič za integracije"
        },
        "href": "/workspace/api-ogledno-podrucje/api-vodic-za-integracije?lang=hr"
      }
    ],
    "articles": [],
    "depth": 2,
    "limit": "10",
    "order": "newest",
    "total": 1,
    "all_available": true,
    "pagination": {
      "page": 1,
      "pages": 1,
      "total": 1,
      "from": 1,
      "to": 1,
      "page_numbers": [
        1
      ]
    },
    "shorts_path": "/workspace/api-ogledno-podrucje/shorts?lang=hr",
    "workspace_path": "/workspace/api-ogledno-podrucje?lang=hr"
  },
  "meta": {
    "request_id": "59be13951d906db537a449e60e0c472c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/shorts?lang=hr&page=1&limit=10"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/theme`

**Purpose:** Returns Workspace theme settings, system choices, and private files.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "__default__",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "simbioza",
      "label": {
        "hr": "Simbioza",
        "en": "Simbioza"
      }
    },
    "assets": [
      {
        "file": "hero-adriatic-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-dark.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-adriatic-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-adriatic-light.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "hero-botanical-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-botanical-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-dark.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.png",
        "role": "hero",
        "used": false
      },
      {
        "file": "hero-natural-light.svg",
        "role": "hero",
        "used": false
      },
      {
        "file": "icon-adriatic-dark.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-adriatic-light.png",
        "role": "icon",
        "used": true
      },
      {
        "file": "icon-adriatic-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-botanical-light.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-dark.svg",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.png",
        "role": "icon",
        "used": false
      },
      {
        "file": "icon-natural-light.svg",
        "role": "icon",
        "used": false
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "d2a84727c1eea4b947872cc8fb0452d9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PATCH /api/v1/workspaces/{workspaceSlug}/theme`

**Purpose:** Stores a private theme configuration only for the supplied Workspace.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `mode_policy`, `theme`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme": {
    "id": "aai",
    "label": {
      "hr": "AAI",
      "en": "AAI"
    },
    "font_family": "system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif",
    "menu_font_family": "system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif",
    "menu_font_size": "1rem",
    "menu_font_weight": "500",
    "menu_font_style": "normal",
    "system": false,
    "components": {
      "skip_link": true,
      "header": {
        "enabled": true,
        "sticky": true,
        "container": "fluid",
        "height_px": 72,
        "items": [
          {
            "id": "aai-logo",
            "type": "logo",
            "position": "start",
            "text": [],
            "href": "https://www.aaiedu.hr/",
            "src_light": "@theme-assets/aai/logo-aaieduhr-light.svg",
            "src_dark": "@theme-assets/aai/logo-aaieduhr-dark.svg",
            "alt": {
              "hr": "AAI@EduHr",
              "en": "AAI@EduHr"
            },
            "height": 50,
            "new_tab": true
          },
          {
            "id": "language",
            "type": "language",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "account",
            "type": "account",
            "position": "end",
            "text": [],
            "href": "",
            "src_light": "",
            "src_dark": "",
            "alt": [],
            "height": 44,
            "new_tab": false
          },
          {
            "id": "srce-logo",
            "type": "logo",
            "position": "end",
            "text": [],
            "href": "https://www.srce.unizg.hr/",
            "src_light": "@theme-assets/aai/logo-srce-light.svg",
            "src_dark": "@theme-assets/aai/logo-srce-dark.svg",
            "alt": {
              "hr": "Sveučilišni računski centar Srce",
              "en": "University Computing Centre Srce"
            },
            "height": 50,
            "new_tab": true
          }
        ]
      },
      "navigation": {
        "text_shadow": "none",
        "placement": "standalone",
        "container": "fluid",
        "height_px": 56,
        "breakpoint": "lg",
        "tabs_style": "standard",
        "tabs_accent_enabled": true,
        "tabs_radius": "small",
        "tabs_active_border_width_px": 2,
        "show_brand": false,
        "brand_display": "none",
        "show_mobile_label": false,
        "mobile_label": {
          "hr": "Izbornik",
          "en": "Menu"
        },
        "sticky": true,
        "show_icons": false,
        "mark_external_links": true
      },
      "hero": {
        "title_text_shadow": "none",
        "supporting_text_shadow": "none",
        "home_enabled": true,
        "inner_enabled": true,
        "container": "fluid",
        "home_size": "medium",
        "inner_size": "medium",
        "alignment": "start",
        "content_min_height_px": 0,
        "content_padding_top_px": 0,
        "content_padding_bottom_px": 0,
        "visual_enabled": true,
        "visual_src_light": "@theme-assets/aai/hero-aai-banner.svg",
        "visual_src_dark": "@theme-assets/aai/hero-aai-banner.svg",
        "visual_alt": [],
        "visual_decorative": true,
        "visual_allow_overflow": true,
        "visual_width_px": 650,
        "visual_max_height_px": 320,
        "visual_top_px": -48,
        "visual_right_px": 24,
        "visual_rotation_deg": 0,
        "wave": true
      },
      "content": {
        "container": "fluid",
        "inner_title_placement": "hero",
        "application_title_placement": "hero",
        "surface": "plain",
        "inner_hero_overlap": "large",
        "density": "comfortable",
        "title_accent": "none",
        "title_shadow": false,
        "content_accent": "top",
        "content_shadow": false,
        "sidebar_accent": "top",
        "sidebar_shadow": false
      },
      "cards": {
        "style": "quiet",
        "density": "comfortable",
        "radius": "medium"
      }
    },
    "light": {
      "colors": {
        "body_bg": "#F3F4F7",
        "body_text": "#212529",
        "muted_text": "#66737D",
        "surface_bg": "#FFFFFF",
        "surface_text": "#212529",
        "border": "#D8DCE3",
        "link": "#1094E3",
        "primary": "#0095DA",
        "secondary": "#003567",
        "success": "#198754",
        "warning": "#E39717",
        "danger": "#D51F36",
        "info": "#4EC0AD",
        "document_action_bg": "#FFFFFF",
        "document_action_text": "#006FA8",
        "document_action_border": "#DEE2E6",
        "document_action_hover_bg": "#0095DA",
        "document_action_hover_text": "#FFFFFF",
        "document_action_active_bg": "#003567",
        "document_action_active_text": "#FFFFFF",
        "primary_text": "auto",
        "secondary_text": "auto",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto",
        "card_bg": "#FFFFFF",
        "card_text": "#212529",
        "card_border": "#DEE2E6",
        "card_accent": "#0095DA",
        "card_shadow": "#000000",
        "title_card_bg": "#FFFFFF",
        "title_card_text": "#212529",
        "title_card_border": "#DEE2E6",
        "title_card_accent": "#0095DA",
        "content_card_bg": "#FFFFFF",
        "content_card_text": "#212529",
        "content_card_border": "#DEE2E6",
        "content_card_accent": "#0095DA",
        "sidebar_card_bg": "#FFFFFF",
        "sidebar_card_text": "#212529",
        "sidebar_card_border": "#DEE2E6",
        "sidebar_card_accent": "#4EC0AD",
        "title_supporting_text": "#6C757D",
        "hero_gradient_1": "#003567",
        "hero_gradient_2": "#084678",
        "hero_gradient_3": "#13588B",
        "hero_gradient_4": "#1F5EA0",
        "hero_gradient_5": "#1F8CA0",
        "hero_text": "#FFFFFF",
        "hero_title": "#FFFFFF",
        "hero_subtitle": "#E5F6FD",
        "hero_muted": "#BDEAFF",
        "table_header_bg": "#F8F9FA",
        "table_header_text": "#000000",
        "table_row_bg": "#FFFFFF",
        "table_row_text": "#212529",
        "table_row_alt_bg": "#F2F2F2",
        "table_border": "#DEE2E6",
        "alert_primary_bg": "#CFE2FF",
        "alert_primary_text": "#084298",
        "alert_primary_border": "#B6D4FE",
        "alert_success_bg": "#D1E7DD",
        "alert_success_text": "#0F5132",
        "alert_success_border": "#BADBCC",
        "alert_warning_bg": "#FFF3CD",
        "alert_warning_text": "#664D03",
        "alert_warning_border": "#FFECB5",
        "alert_danger_bg": "#F8D7DA",
        "alert_danger_text": "#842029",
        "alert_danger_border": "#F5C2C7",
        "alert_info_bg": "#CFF4FC",
        "alert_info_text": "#055160",
        "alert_info_border": "#B6EFFB",
        "badge_bg": "#0095DA",
        "badge_text": "#FFFFFF",
        "tag_bg": "#E5F6FD",
        "tag_text": "#003567",
        "nav_default_bg": "#003567",
        "nav_default_text": "#7AD4FD",
        "nav_border": "#4EC0AD",
        "nav_active_bg": "#1F5EA0",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#7AD4FD",
        "nav_hover_bg": "#174E7E",
        "left_menu_hover_bg": "#E5F6FD",
        "left_menu_hover_text": "#003567",
        "left_menu_active_bg": "#0095DA",
        "left_menu_active_text": "#FFFFFF",
        "special_left_menu_accent": "#D51F36",
        "pagination_bg": "#FFFFFF",
        "pagination_text": "#0079B8",
        "pagination_active_bg": "#0095DA",
        "pagination_active_text": "#FFFFFF",
        "breadcrumb_text": "#66737D",
        "breadcrumb_link": "#0079B8",
        "editor_tab_bg": "#F8F9FA",
        "editor_tab_text": "#495057",
        "editor_tab_border": "#DEE2E6",
        "editor_tab_hover_bg": "#E9ECEF",
        "editor_tab_active_bg": "#FFFFFF",
        "editor_tab_active_text": "#212529",
        "editor_tab_active_accent": "#0D6EFD",
        "editor_tab_panel_bg": "#FFFFFF",
        "editor_tab_panel_text": "#212529",
        "editor_accordion_header_bg": "#F8F9FA",
        "editor_accordion_header_text": "#212529",
        "editor_accordion_body_bg": "#FFFFFF",
        "editor_accordion_body_text": "#212529",
        "focus_ring": "#0095DA",
        "disabled_bg": "#E9ECEF",
        "disabled_text": "#6C757D",
        "valid": "#198754",
        "invalid": "#DC3545",
        "code_bg": "#F8F9FA",
        "code_text": "#D63384",
        "blockquote_border": "#0095DA",
        "callout_bg": "#EAF8FC",
        "callout_text": "#003567",
        "callout_border": "#8DD6E8",
        "navbar_gradient_1": "#003567",
        "navbar_gradient_2": "#084678",
        "navbar_gradient_3": "#13588B",
        "navbar_gradient_4": "#1F5EA0",
        "navbar_gradient_5": "#1F8CA0",
        "navbar_text": "#7AD4FD",
        "navbar_link": "#7AD4FD",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#003567"
      }
    },
    "dark": {
      "colors": {
        "body_bg": "#0C141C",
        "body_text": "#E8F2F7",
        "muted_text": "#A 8BBC6",
        "surface_bg": "#14212C",
        "surface_text": "#E8F2F7",
        "border": "#385064",
        "link": "#65C7F0",
        "primary": "#22A9E0",
        "secondary": "#4E8FC7",
        "success": "#198754",
        "warning": "#F0AD32",
        "danger": "#EF667A",
        "info": "#64D3C3",
        "document_action_bg": "#14212C",
        "document_action_text": "#65C7F0",
        "document_action_border": "#385064",
        "document_action_hover_bg": "#22A9E0",
        "document_action_hover_text": "#0C141C",
        "document_action_active_bg": "#64D3C3",
        "document_action_active_text": "#0C141C",
        "primary_text": "auto",
        "secondary_text": "auto",
        "success_text": "auto",
        "warning_text": "auto",
        "danger_text": "auto",
        "info_text": "auto",
        "card_bg": "#182835",
        "card_text": "#E8F2F7",
        "card_border": "#385064",
        "card_accent": "#22A9E0",
        "card_shadow": "#000000",
        "title_card_bg": "#14212C",
        "title_card_text": "#E8F2F7",
        "title_card_border": "#385064",
        "title_card_accent": "#22A9E0",
        "content_card_bg": "#14212C",
        "content_card_text": "#E8F2F7",
        "content_card_border": "#385064",
        "content_card_accent": "#22A9E0",
        "sidebar_card_bg": "#182835",
        "sidebar_card_text": "#E8F2F7",
        "sidebar_card_border": "#385064",
        "sidebar_card_accent": "#64D3C3",
        "title_supporting_text": "#6C757D",
        "hero_gradient_1": "#001D38",
        "hero_gradient_2": "#062B4E",
        "hero_gradient_3": "#0D3A5F",
        "hero_gradient_4": "#15496F",
        "hero_gradient_5": "#155F70",
        "hero_text": "#FFFFFF",
        "hero_title": "#FFFFFF",
        "hero_subtitle": "#D9F4FF",
        "hero_muted": "#AED7E7",
        "table_header_bg": "#1D3444",
        "table_header_text": "#F5FBFE",
        "table_row_bg": "#14212C",
        "table_row_text": "#E8F2F7",
        "table_row_alt_bg": "#192B39",
        "table_border": "#385064",
        "alert_primary_bg": "#031633",
        "alert_primary_text": "#6EA8FE",
        "alert_primary_border": "#084298",
        "alert_success_bg": "#051B11",
        "alert_success_text": "#75B798",
        "alert_success_border": "#0F5132",
        "alert_warning_bg": "#332701",
        "alert_warning_text": "#FFDA6A",
        "alert_warning_border": "#664D03",
        "alert_danger_bg": "#2C0B0E",
        "alert_danger_text": "#EA868F",
        "alert_danger_border": "#842029",
        "alert_info_bg": "#032830",
        "alert_info_text": "#6EDFF6",
        "alert_info_border": "#055160",
        "badge_bg": "#22A9E0",
        "badge_text": "#08131B",
        "tag_bg": "#1D3444",
        "tag_text": "#D9F4FF",
        "nav_default_bg": "#001D38",
        "nav_default_text": "#92DAF7",
        "nav_border": "#2E708B",
        "nav_active_bg": "#15496F",
        "nav_active_text": "#FFFFFF",
        "nav_active_accent": "#65C7F0",
        "nav_hover_bg": "#0D3A5F",
        "left_menu_hover_bg": "#1D3444",
        "left_menu_hover_text": "#FFFFFF",
        "left_menu_active_bg": "#22A9E0",
        "left_menu_active_text": "#08131B",
        "special_left_menu_accent": "#EF667A",
        "pagination_bg": "#14212C",
        "pagination_text": "#65C7F0",
        "pagination_active_bg": "#22A9E0",
        "pagination_active_text": "#08131B",
        "breadcrumb_text": "#A 8BBC6",
        "breadcrumb_link": "#65C7F0",
        "editor_tab_bg": "#212529",
        "editor_tab_text": "#ADB5BD",
        "editor_tab_border": "#495057",
        "editor_tab_hover_bg": "#343A40",
        "editor_tab_active_bg": "#2B3035",
        "editor_tab_active_text": "#FFFFFF",
        "editor_tab_active_accent": "#6EA8FE",
        "editor_tab_panel_bg": "#2B3035",
        "editor_tab_panel_text": "#DEE2E6",
        "editor_accordion_header_bg": "#212529",
        "editor_accordion_header_text": "#DEE2E6",
        "editor_accordion_body_bg": "#2B3035",
        "editor_accordion_body_text": "#DEE2E6",
        "focus_ring": "#65C7F0",
        "disabled_bg": "#22323E",
        "disabled_text": "#879AA5",
        "valid": "#75B798",
        "invalid": "#EA868F",
        "code_bg": "#1A2C3A",
        "code_text": "#8AD8F5",
        "blockquote_border": "#22A9E0",
        "callout_bg": "#1A2C3A",
        "callout_text": "#E8F2F7",
        "callout_border": "#385064",
        "navbar_gradient_1": "#001D38",
        "navbar_gradient_2": "#062B4E",
        "navbar_gradient_3": "#0D3A5F",
        "navbar_gradient_4": "#15496F",
        "navbar_gradient_5": "#155F70",
        "navbar_text": "#92DAF7",
        "navbar_link": "#92DAF7",
        "navbar_active": "#FFFFFF",
        "navbar_active_bg": "#0D3A5F"
      }
    }
  },
  "mode_policy": "light"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "fa8a2e1360c17a2b204df8cebd36ea8c"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/assets`

**Purpose:** Stores an image from the `asset` multipart field in the private theme library.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `asset`, `role`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'asset=@./api-circle.svg;type=image/svg+xml' \
  --form role=other \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "light"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "api-circle.svg",
        "role": "other",
        "used": false
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "b47707737f33e9b0db3941383cbaa65b"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `DELETE /api/v1/workspaces/{workspaceSlug}/theme/assets/{file}`

**Purpose:** Deletes an unused Workspace private-theme file.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`, `file`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "97a72aa130fa5380e38c2f24cdf948ba"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/assets/api-circle.svg"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/theme/export`

**Purpose:** Downloads a Workspace private-theme ZIP for an administrator.

**Permission:** a valid API key. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.zip \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/export"
```

Actual response: **HTTP 200**.

```text
cache-control: private, no-store
content-type: application/zip
content-disposition: attachment; filename="workspace-theme-api-ogledno-podrucje.zip"
content-length: 27051
```

The file is saved as `response.*`. The verified content has 27051 bytes; do not decode it as JSON.

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `POST /api/v1/workspaces/{workspaceSlug}/theme/import`

**Purpose:** Imports a theme ZIP from the `theme` multipart field into a private Workspace theme.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `mode_policy`, `theme`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --form 'theme=@./api-workspace-theme.zip;type=application/zip' \
  --form mode_policy=auto \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/import" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "workspace-4",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "workspace-4",
      "label": {
        "hr": "AAI – Ogledno API područje",
        "en": "AAI – Ogledno API područje"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "api-circle.svg",
        "role": "other",
        "used": false
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "other",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "other",
        "used": true
      }
    ],
    "can_export": true
  },
  "meta": {
    "request_id": "c8eee8b7fe118a2c8e40873aa94ac9c9"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/import"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/{workspaceSlug}/theme/selection`

**Purpose:** Selects inheritance or one unchanged system theme for a Workspace.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** none.

**Body / supported fields:** `mode_policy`, `theme_id`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "theme_id": "aai",
  "mode_policy": "auto"
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/theme/selection" | jq '{data: {workspace_id: .data.workspace_id, settings: .data.settings, selected_theme: (.data.selected_theme | {id,label}), assets: [.data.assets[] | {file,role,used}], can_export: .data.can_export}, meta, links}'
```

Actual response: **HTTP 200**.This is the actual output of the shown `jq` filter.

```json
{
  "data": {
    "workspace_id": 4,
    "settings": {
      "enabled": true,
      "active_theme": "aai",
      "mode_policy": "auto"
    },
    "selected_theme": {
      "id": "aai",
      "label": {
        "hr": "AAI",
        "en": "AAI"
      }
    },
    "assets": [
      {
        "file": "hero-aai-banner.svg",
        "role": "hero",
        "used": true
      },
      {
        "file": "logo-aaieduhr-dark.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-aaieduhr-light.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-srce-dark.svg",
        "role": "logo",
        "used": true
      },
      {
        "file": "logo-srce-light.svg",
        "role": "logo",
        "used": true
      }
    ],
    "can_export": false
  },
  "meta": {
    "request_id": "3a8b382e6a363076a026c05f7c899268"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/theme/selection"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `GET /api/v1/workspaces/{workspaceSlug}/tree`

**Purpose:** Returns the filtered Workspace tree and effective node permissions.

**Permission:** `workspace:read`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body:** no additional input fields; follow the example.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "78d6b87d8ee7c962567b443c82af5111"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.

### `PUT /api/v1/workspaces/{workspaceSlug}/tree/order`

**Purpose:** Stores the complete tree order and hierarchy.

**Permission:** `workspace:manage`. Current workspace/node permissions; global administration and restore require administrator rights.

**Path:** `workspaceSlug`. Take values from the preceding response. A page ID is its document key; nodeId is numeric, and a UUID is not a slug.

**Query parameters:** `lang`.

**Body / supported fields:** `placements`. The example below uses verified values; conditions and list-replacement rules are described at the start of the chapter.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "placements": [
    {
      "id": 36,
      "parent_id": null,
      "sort_order": 100
    },
    {
      "id": 37,
      "parent_id": null,
      "sort_order": 200
    },
    {
      "id": 38,
      "parent_id": null,
      "sort_order": 201
    }
  ]
}' \
  "$BASE_URL/api/v1/workspaces/api-ogledno-podrucje/tree/order"
```

Actual response: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 36,
      "uuid": "e810e7a1-ff77-42f2-ae4e-57bed7370354",
      "parent_id": null,
      "node_type": "document",
      "slug": "api-plan-radionice",
      "title": "Plan API radionice",
      "title_translations": {
        "en": "Plan API radionice",
        "hr": "Plan API radionice"
      },
      "document_key": "api-plan-radionice",
      "route_name": "",
      "target_url": "",
      "sort_order": 100,
      "is_homepage": true,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 37,
      "uuid": "e26c1075-3a3b-4daf-96c5-8984258964e9",
      "parent_id": null,
      "node_type": "internal_link",
      "slug": "api-internal",
      "title": "API poveznica",
      "title_translations": {
        "en": "API poveznica"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "/about",
      "sort_order": 200,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    },
    {
      "id": 38,
      "uuid": "5947a07a-179f-433e-9030-7abbbe9eaa6d",
      "parent_id": null,
      "node_type": "external_link",
      "slug": "api-external",
      "title": "api-external",
      "title_translations": {
        "en": "api-external"
      },
      "document_key": "",
      "route_name": "",
      "target_url": "https://example.com/",
      "sort_order": 201,
      "is_homepage": false,
      "contents_visibility": "inherit",
      "labels": [],
      "properties": [],
      "permissions": {
        "can_view": true,
        "can_add": true,
        "can_edit": true,
        "can_publish": true,
        "can_delete": true,
        "can_manage": true
      },
      "children": []
    }
  ],
  "meta": {
    "request_id": "98e3352a89089722cf08b382db4fc6d2"
  },
  "links": {
    "self": "/api/v1/workspaces/api-ogledno-podrucje/tree/order"
  }
}
```

**Errors verified for this operation:** 401, 403. See [shared rules](api-pocetak_en.md) for status meanings and problem responses.
