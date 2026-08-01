# Plan implementacije API-ja / API implementation plan

Ovaj dokument je nastavna točka za rad u više odvojenih Codex sesija. Svaka
etapa mora završiti zelenim testovima i upotrebljivim stanjem prije početka
sljedeće.

This document is the continuation point for work across separate Codex
sessions. Every milestone must end with green tests and a usable state before
the next milestone begins.

## Status

| Etapa / Milestone | Status | Rezultat / Outcome |
|---|---|---|
| 1. API v1 ugovor | Dovršeno / Complete | `api-v1-contract.md` definira ownership, ACL, scopes, DTO/HTTP pravila i resurse |
| 2. Auth API ključevi | Dovršeno / Complete | Hashirani ključevi u Authu; API modul daje administraciju, grupirane scopeove, istek, IP ograničenja, rotaciju i opoziv; Auth vodi audit / Hashed keys in Auth; the API module provides administration, grouped scopes, expiry, IP restrictions, rotation, and revocation; Auth owns the audit |
| 3. `module-api` temelj | Dovršeno / Complete | `/api/v1`, `/me`, cursor paginacija, OpenAPI 3.1, CORS, rate limiting, request ID i problem odgovori / `/api/v1`, `/me`, cursor pagination, OpenAPI 3.1, CORS, rate limiting, request IDs, and problem responses |
| 4. Read-only resursi | Dovršeno / Complete | Auth, Workspace, Editor, Calendar, Task i Notification rute registriraju se samo uz uključen domenski modul / Auth, Workspace, Editor, Calendar, Task, and Notification routes register only with their enabled domain module |
| 5. Integracijski testovi | Dovršeno / Complete | Pokriveni su samostalni API/Editor rad, kombinacije opcionalnih modula, prijenosna početna shema i stvarne HTTPS ACL provjere / Standalone API/Editor operation, optional-module combinations, the portable initial schema, and real HTTPS ACL checks are covered |
| 6. Write API | Dovršeno / Complete | Domenske write operacije imaju ACL, cursor kolekcije, ETag/If-Match i trajni Idempotency-Key replay / Domain writes have ACL, cursor collections, ETag/If-Match, and durable Idempotency-Key replay |
| 7. Admin API | Dovršeno / Complete | Lokalni user/group CRUD, audit i inbox operacije dovršeni su bez izlaganja provider ili SMTP tajni / Local user/group CRUD, audit, and inbox operations are complete without exposing provider or SMTP secrets |
| 8. Webhooks i puna stabilizacija | Dovršeno / Complete | Trajne pretplate, šifrirane tajne, HMAC potpisi, SSRF zaštita, asinkroni worker, retry, testovi i dvojezične operativne upute / Durable subscriptions, encrypted secrets, HMAC signatures, SSRF protection, async worker, retry, tests, and bilingual operations guidance |

## Dovršena etapa 2: Auth API ključevi / Completed milestone 2: Auth API keys

Dovršeni opseg:

1. Proširiti jedinu inicijalnu Auth migraciju tablicama ključeva i, po potrebi,
   zapisima potrošnje/rate limita.
2. Dodati `AuthApiKeyService` koji generira, validira, opoziva i rotira ključeve.
3. Dodati nepromjenjivi API identity objekt za kasniji middleware.
4. Dodati centralni registar dopuštenih scopes bez slobodnog tekstualnog unosa.
5. Neka `module-api` doda administratorsko sučelje u Auth postavke samo kada je API modul uključen.
6. Tajnu prikazati samo nakon kreiranja ili rotacije.
7. Auditirati kreiranje, rotaciju, opoziv, uspješnu i neuspješnu uporabu.
8. Dodati testove za hash, istek, opoziv, neaktivnog korisnika i admin scopes.
9. Dokumentirati shemu, sigurnost i način uporabe na hrvatskom i engleskom.

Completed scope:

1. Extend the single initial Auth migration with key tables and, if required,
   usage/rate-limit records.
2. Add `AuthApiKeyService` to generate, validate, revoke, and rotate keys.
3. Add an immutable API identity object for the later middleware.
4. Add a central allowlist of supported scopes without free-form scope input.
5. Let `module-api` add an administration screen under Auth settings when the API module is enabled.
6. Display the secret only after creation or rotation.
7. Audit creation, rotation, revocation, successful use, and failed use.
8. Test hashing, expiration, revocation, inactive users, and administrator scopes.
9. Document schema, security, and usage in Croatian and English.

## Pravilo nastavka / Continuation rule

Na početku sljedeće sesije prvo pročitati:

1. `docs/api-v1-contract.md`
2. ovaj dokument
3. `docs/module-dependencies.md`
4. Auth migraciju, `ModuleAuth`, `AuthUserService` i `AuthAuditLogService`

At the start of the next session, first read:

1. `docs/api-v1-contract.md`
2. this document
3. `docs/module-dependencies.md`
4. the Auth migration, `ModuleAuth`, `AuthUserService`, and `AuthAuditLogService`

Ne mijenjati HeartPhrame Framework radi API-ja. Ne izlagati postojeće HTML
controllere kao API. Ne spremati čiste API ključeve.

Do not change HeartPhrame Framework for the API. Do not expose existing HTML
controllers as the API. Never store plaintext API keys.
