# API implementation plan

This document is the continuation point for work across separate Codex
sessions. Every milestone must end with green tests and a usable state before
the next milestone begins.

## Status

| Milestone | Status | Outcome |
|---|---|---|
| 1. API v1 contract | Complete | `api-v1-contract_en.md` defines ownership, ACL, scopes, DTO/HTTP rules, and resources |
| 2. Auth API keys | Complete | Hashed keys in Auth; the API module provides administration, grouped scopes, expiry, IP restrictions, rotation, and revocation; Auth owns the audit |
| 3. `module-api` foundation | Complete | `/api/v1`, `/me`, cursor pagination, OpenAPI 3.1, CORS, rate limiting, request IDs, and problem responses |
| 4. Read-only resources | Complete | Auth, Workspace, Editor, Calendar, Task, and Notification routes register only with their enabled domain module |
| 5. Integration tests | Complete | Standalone API/Editor operation, optional-module combinations, the portable initial schema, and real HTTPS ACL checks are covered |
| 6. Write API | Complete | Domain writes have ACL, cursor collections, ETag/If-Match, and durable `Idempotency-Key` replay |
| 7. Admin API | Complete | Local user/group CRUD, audit, and inbox operations are complete without exposing provider or SMTP secrets |
| 8. Webhooks and full stabilization | Complete | Durable subscriptions, encrypted secrets, HMAC signatures, SSRF protection, async worker, retry, tests, and separate English/Croatian operational guidance |

## Completed milestone 2: Auth API keys

Completed scope:

1. Extend the single initial Auth migration with key tables and, if required,
   usage/rate-limit records.
2. Add `AuthApiKeyService` to generate, validate, revoke, and rotate keys.
3. Add an immutable API identity object for later middleware.
4. Add a central allowlist of supported scopes without free-form scope input.
5. Let `module-api` add an administration screen under Auth settings only when
   the API module is enabled.
6. Display the secret only after creation or rotation.
7. Audit creation, rotation, revocation, successful use, and failed use.
8. Test hashing, expiration, revocation, inactive users, and administrator
   scopes.
9. Document schema, security, and usage in English and Croatian.

## Continuation rule

At the start of the next session, first read:

1. `docs/api-v1-contract_en.md`
2. this document
3. `docs/module-dependencies_en.md`
4. the Auth migration, `ModuleAuth`, `AuthUserService`, and
   `AuthAuditLogService`

Do not change HeartPhrame Framework for the API. Do not expose existing HTML
controllers as the API. Never store plaintext API keys.
