# Implemented API v1 contract

This is the current HTTP API of Simbioza, not an implementation proposal.
The API is supplied by `heartphrame-module-api`; enabled domain modules register
their own routes and enforce their own business rules. Framework code is an
upstream dependency and is not modified by this integration.

## Start with the actual installation

The base path is `/api/v1` relative to the application. For an installation at
`https://example.test/simbioza`, set:

```bash
export HPH_API_URL='https://example.test/simbioza/api/v1'
read -r -s HPH_API_TOKEN
export HPH_API_TOKEN
curl --fail-with-body --silent --show-error \
  --header "Authorization: Bearer $HPH_API_TOKEN" "$HPH_API_URL/me"
```

Create a key under **Settings → Users → API keys**, copy it once and keep it
outside source code and logs. Auth owns issued keys, one-way secret hashes,
current user/group identity, expiry, revocation and optional IP restrictions.
API owns the administration/request UI and HTTP boundary. The actual token
format is `hfp_live_<public-id>_<secret>`; clients should treat the whole value
as an opaque secret. Rotate or revoke a lost key instead of trying to recover it.

The current owner must exist and remain active. Each operation needs both the
key's scope and the owner's real domain permissions. Administrator status does
not bypass scope checks. A browser session, a survey-only external login or a
user ID in submitted JSON cannot impersonate another API owner.

## Discovery and current identity

These core routes all require a valid Bearer key:

```text
GET /api/v1
GET /api/v1/me
GET /api/v1/openapi.json
```

Discovery returns the resources and scope groups of **currently enabled**
modules. `/me` returns the safe profile of the key owner and the current key's
public identity/scopes, never its secret. `/openapi.json` generates an OpenAPI
3.1 inventory from the active router, with the installation URL in `servers`.
It returns the raw document as
`application/vnd.oai.openapi+json;version=3.1.0`, with `Cache-Control: no-store`.
It is not wrapped in `data`/`meta`/`links`.

The generated document describes active routes and shared envelopes. Its
payload/response schemas are generic: consult the domain reference for exact
fields, multipart uploads, CSV/ICS/binary downloads, status codes and workflow
rules. Importing it into a client does not grant any scopes or permissions.
The unauthenticated CORS `OPTIONS` preflight is separate and validates the
configured origin, method and header allowlists.

## Implemented resources and ownership

| Owner module | API family and scopes | Current behavior |
| --- | --- | --- |
| Auth | `/users`, `/groups`; `users:read/create/update/delete`, `groups:read/manage` | Active administrator owner required. Local accounts/groups only; no administration of remote identity-provider accounts. User DELETE deactivates the account and revokes its keys. |
| Workspace | `/workspaces`; `workspace:read/manage` | ACL-filtered workspaces/tree, metadata, node/permission management, summaries, HTML export and workspace-specific presentation. |
| HTML Editor | `/pages`; `page:read/write/publish`, `attachment:read/write` | Page content, languages, published versions, shared drafts/review/publication and attachments. Standalone writes publish immediately; Workspace writes follow its draft workflow. |
| Calendar | `/calendars`; `calendar:read/write` | Calendar/event CRUD, ACL, recurrence, ICS import/export. Calendar DELETE is permanent. |
| Task | `/pages/{documentId}/tasks`; `task:read/write` | Definitions from the published page, current state and history; page ACL and task-list scope apply. |
| Notification | `/notifications`; `notifications:read/write` | Only the key owner's visible inbox; no arbitrary-notification creation endpoint. |
| Workspace Search | `/workspace-search`; `workspace-search:read` | ACL-filtered titles, published text/authors, snippets, counts and filters. |
| Survey | `/surveys`; `surveys:read/write/publish/results/export` | Definitions, draft/review/publication/recovery, aggregate results, manager-only individual responses and private exports. |
| Audit | `/audit`; `audit:read` | Read-only, redacted activity events for active administrators; no technical-log download API. |
| Simbioza User | `/me/follows`, `/me/follow-preferences`; `follows:read/write` | Current key owner’s followed targets and notification/appearance preferences; target ACL remains in force. |
| API | `/webhooks`; `webhooks:read/manage` | Key-owned subscriptions, delivery history, secret rotation and retry; asynchronous signed delivery. |

A disabled domain module contributes neither routes nor scopes. Menu, Theme
and Email have no separate public HTTP resource family. Email remains an
internal transport. Theme operations related to a Workspace belong to that
Workspace integration, not a global Theme API. Backup archives use the
separate authorized Backup workflow; they are not result CSV exports.

## Personal Workspace status

`GET /me/personal-workspace` is registered by Simbioza User and requires the
advertised `workspace:read` scope. It returns only the API-key owner's existing
personal-Workspace mapping and never creates an area. Missing mappings return
`exists: false` and `workspace: null`. Personal follows and follow-preference
routes use their advertised `follows:*` scopes.

## Route examples

All paths below are relative to the same API base:

```text
GET    /users?page[limit]=10
PUT    /users/{userId}/groups
GET    /workspaces/{workspaceId}/tree
POST   /pages
PATCH  /pages/{documentId}
POST   /pages/{documentId}/review
POST   /pages/{documentId}/publish
POST   /pages/{documentId}/attachments
PATCH  /calendars/{calendarUuid}/events/{eventId}
PUT    /pages/{documentId}/tasks/{taskUuid}/state
GET    /workspace-search?q=guide&lang=en&per_page=10
POST   /surveys
POST   /surveys/{survey}/submit-review
POST   /surveys/{survey}/publish
GET    /surveys/{survey}/results
POST   /surveys/{survey}/exports
```

Page creation uses `/pages` with the appropriate placement fields, not an
invented `/workspaces/{id}/pages` endpoint. Task routes are scoped to their
published document, not a global `/tasks` collection. Draft review requires
`page:write`; publication additionally requires `page:publish` and actual
publisher rights. For Survey, `delete` preserves accepted responses in trash,
`discard-draft` removes only a newer working draft, and administrator-only
`restore` returns a former publication as closed.

There is no token-only respondent-submission API and no API route which uses
someone else's browser SAML/OIDC Survey session. Respondents use the normal
Survey admission/answer flow with eligibility, CSRF and repeat checks.

## Responses and domain-specific pagination

Ordinary JSON success responses use:

```json
{
  "data": {},
  "meta": {"request_id": "req_example"},
  "links": {"self": "/simbioza/api/v1/me"}
}
```

Errors use RFC 9457 `application/problem+json`:

```json
{
  "type": "about:blank",
  "title": "Forbidden",
  "status": 403,
  "detail": "The API key does not grant the required scope.",
  "code": "insufficient_scope",
  "request_id": "req_example"
}
```

Keep `request_id` for diagnosis, not the token. Statuses, DTO identifiers and
timestamp formats follow each owner module's reference; there is no promise
that every identifier is a string or every timestamp is ISO 8601. A successful
create may return `201`, deletion `204`, and downloads their actual media type.

Pagination is not uniform across all resources. Users/Groups and main Calendar/
Workspace lists use opaque `page[after]` cursors and bounded `page[limit]`
(default 50, maximum 100). Audit and Workspace Search use `page`/`per_page`.
Survey list/result page metadata is inside `data`, including `items` where
applicable. Follow the returned `links` and metadata for that domain. Do not
invent global `filter[...]` or `sort` parameters unsupported by an endpoint.

## Writes, conflicts and transport

- Send JSON as `Content-Type: application/json`; payload size is bounded.
- Use a fresh 8–190 character `Idempotency-Key` per logical operation. Exact
  retries with that key can replay a saved safe response; a different request
  using it returns `409`. Multipart/streamed uploads reject that header and
  follow their own upload protocol.
- Where a resource returns an `ETag` and its route requires conditional writes,
  send it as `If-Match`. With the default policy, a missing condition returns
  `428` and a stale condition `412`. Calendar/Workspace/webhook contracts specify
  where this applies. Page drafts and Survey workflows use their own
  `draft_revision` or `version`/`revision` and return `409` for stale edits.
- Rate limiting defaults to 120 requests per minute per key, with limit headers
  and `429`/`Retry-After` on exhaustion. Security storage failures do not bypass
  write protection.
- CORS is disabled by default. Explicitly allow only intended browser origins.
  CORS does not replace key authentication or ACL.
- Webhooks use HTTPS/public-target checks, encrypted signing secrets,
  HMAC-SHA256 and asynchronous CLI delivery with bounded retry. Domain changes
  do not wait for the receiver's network request. See the detailed worker and
  signature reference before deploying a consumer.

The ordinary PHP session login and browser CSRF forms are not the API login
mechanism. Use HTTPS and the installed API module rather than exposing web
controllers or private tables as a shortcut.

## Detailed references and runnable examples

- [API quick start: cURL, PHP and expected responses](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/quickstart_en.md)
- [Discovery and OpenAPI](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/discovery_en.md)
- [Security, conditional writes and pagination](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/security_en.md)
- [HTML Editor API](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-editor-html/-/blob/main/docs/api_en.md)
- [Survey API and examples](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-survey/-/blob/main/docs/api_en.md)
- [Webhook operations](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/webhooks_en.md)

For a new client, verify `/me`, discover active resources, read an authorized
object, test a controlled write and its stale/conflict path in an isolated
installation, then restrict the key to the final required scopes and IPs.
