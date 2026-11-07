# API — connecting other systems

This guide is for developers connecting their application to Simbioza. The API works with users, content, workspaces, calendars, surveys and personal data already permitted to the key owner. It does not replace installation administration or participant sign-in to surveys.

## Getting started

1. An administrator installs/enables API and issues a key with the required scopes.
2. Use [Getting started](api-pocetak_en.md) to set the address and read `/api/v1/me`.
3. Check your installation’s available resources and OpenAPI.
4. Open the resource chapter and follow its create, read, update or export example.
5. Handle errors as well: the key does not grant access to another user’s private content.

## Chapters

| Chapter | Verified operations |
|---|---|
| [API — getting started and shared rules](api-pocetak_en.md) | 3 |
| [API — users and groups](api-korisnici-grupe_en.md) | 11 |
| [API — workspaces, trees, permissions and themes](api-podrucja_en.md) | 30 |
| [API — HTML pages, drafts and attachments](api-html-stranice_en.md) | 22 |
| [API — calendars and events](api-kalendari_en.md) | 13 |
| [API — surveys and results](api-ankete_en.md) | 18 |
| [API — tasks in content](api-zadaci_en.md) | 4 |
| [API — notifications and personal follows](api-obavijesti-pracenja_en.md) | 11 |
| [API — webhooks and deliveries](api-webhookovi_en.md) | 9 |
| [API — audit and search](api-audit-pretraga_en.md) | 2 |
| [API boundaries, CalDAV and ICS](api-granice-protokola_en.md) | — |

This manual covers all **114 registered REST routes** in a complete installation. The named Survey action route supports 10 operations, giving **123 separately verified operations**. A modular installation exposes only resources whose modules are installed and active. CORS `OPTIONS` and the separate CalDAV protocol are outside this total.

Every example states the method, path, permissions, parameters, curl command, actual response and verified error codes. The address is replaced with your installation placeholder; keys and secrets are not published. Responses use synthetic data, so names, IDs and timestamps differ from yours. Some large responses use an explicit `jq` projection; the displayed JSON is then the actual output of that filter.

[Administrator API-key management](admin-api_en.md). [Shared rules, errors and a complete PHP client](api-pocetak_en.md).
