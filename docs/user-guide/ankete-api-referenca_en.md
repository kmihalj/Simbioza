# API — routes, scopes and errors

[For developers — API](ankete-developeri_en.md)

The base path is `/api/v1/surveys`, without a trailing slash. Prefix it with the installation subdirectory where applicable. Every request needs a valid key, its required scope and the owner's current domain permission.

<div class="table-responsive">

| Method and path suffix                                | Scope           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Transitions use `version`, `revision` and explicit `confirmed`. Copying accepts a new title. Do not change the structure in place after responses exist: copy the survey.

Listing supports `q`, `state`, `page` and `per_page`. Results accept language and pagination; managers may also filter inclusive `from`/`to` reporting days. Individual submissions require management; chart-reader permission is insufficient.

Exports accept `mode` (aggregate/responses), `lang` and explicit `include_identity`. The returned token expires and remains restricted to the same authorized creator. It is not a public link or a backup.

<div class="table-responsive">

| Status | Action |
|----|----|
| 401 / 403 | Check the key, scope, active account and current permissions. |
| 404 | The resource is unavailable or a required module is disabled; do not bypass access controls. |
| 409 | Reread the current version and reconcile the change. |
| 422 | Correct the definition, language or filters using the response message. |

</div>

Success responses use `data`, `meta` and `links`. Request identifiers help diagnosis. Do not log answers or keys publicly. There is no token-only submission API acting as a participant; normal sign-in, validation and repeat protection still apply.

## Removal and recovery through the API

`delete` moves the whole survey to trash and preserves responses. `discard-draft` discards a working draft; an existing publication remains unchanged. Both require the current `version`, `revision` and Boolean `confirmed: true`. `restore` requires `version`, `confirmed: true` and an administrator, in addition to the key scope above. A previous publication returns closed. The response includes the UUID, operation and `deleted`, indicating whether the survey is now in trash. HTTP 409 means reread the current state before deciding again.
