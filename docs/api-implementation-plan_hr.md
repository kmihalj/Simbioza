# Plan implementacije API-ja

Ovaj dokument je nastavna točka za rad u više odvojenih Codex sesija. Svaka
etapa mora završiti zelenim testovima i upotrebljivim stanjem prije početka
sljedeće.

## Status

| Etapa | Status | Rezultat |
|---|---|---|
| 1. API v1 ugovor | Dovršeno | `api-v1-contract_hr.md` definira vlasništvo, ACL, scopes, DTO/HTTP pravila i resurse |
| 2. Auth API ključevi | Dovršeno | Hashirani ključevi u Authu; API modul pruža administraciju, grupirane scopeove, istek, IP ograničenja, rotaciju i opoziv; Auth vodi audit |
| 3. Temelj `module-api` | Dovršeno | `/api/v1`, `/me`, cursor paginacija, OpenAPI 3.1, CORS, rate limiting, request ID i problem odgovori |
| 4. Resursi samo za čitanje | Dovršeno | Auth, Workspace, Editor, Calendar, Task i Notification rute registriraju se samo uz uključeni domenski modul |
| 5. Integracijski testovi | Dovršeno | Pokriveni su samostalni API/Editor rad, kombinacije opcionalnih modula, prijenosna početna shema i stvarne HTTPS ACL provjere |
| 6. Write API | Dovršeno | Domenske write operacije imaju ACL, cursor kolekcije, ETag/If-Match i trajni `Idempotency-Key` replay |
| 7. Admin API | Dovršeno | Lokalni user/group CRUD, audit i inbox operacije dovršeni su bez izlaganja provider ili SMTP tajni |
| 8. Webhookovi i puna stabilizacija | Dovršeno | Trajne pretplate, šifrirane tajne, HMAC potpisi, SSRF zaštita, asinkroni worker, retry, testovi i zasebne hrvatske/engleske operativne upute |

## Dovršena etapa 2: Auth API ključevi

Dovršeni opseg:

1. Proširiti jedinu inicijalnu Auth migraciju tablicama ključeva i, po potrebi,
   zapisima potrošnje ili rate limita.
2. Dodati `AuthApiKeyService` koji generira, provjerava, opoziva i rotira
   ključeve.
3. Dodati nepromjenjivi objekt API identiteta za kasniji middleware.
4. Dodati središnji popis dopuštenih scopes bez slobodnog tekstualnog unosa.
5. Omogućiti da `module-api` doda administratorsko sučelje u Auth postavke samo
   kada je API modul uključen.
6. Tajnu prikazati samo nakon izrade ili rotacije.
7. Auditirati izradu, rotaciju, opoziv, uspješnu i neuspješnu uporabu.
8. Dodati testove za hash, istek, opoziv, neaktivnog korisnika i administratorske
   scopes.
9. Dokumentirati shemu, sigurnost i uporabu na hrvatskom i engleskom.

## Pravilo nastavka

Na početku sljedeće sesije prvo pročitajte:

1. `docs/api-v1-contract_hr.md`
2. ovaj dokument
3. `docs/module-dependencies_hr.md`
4. Auth migraciju, `ModuleAuth`, `AuthUserService` i `AuthAuditLogService`

Ne mijenjajte HeartPhrame Framework radi API-ja. Ne izlažite postojeće HTML
kontrolere kao API. Nikada ne spremajte čiste API ključeve.
