# API v1 ugovor

Ovaj dokument definira minimalni stabilni ugovor koji prethodi implementaciji
javnog HeartPhrame API-ja. Ne opisuje postojeće web-kontrolere kao API. Web-rute
ostaju namijenjene HTML sučelju, a API dobiva zasebne verzionirane rute,
autentikaciju, DTO objekte i pravila kompatibilnosti.

## 1. Cilj i granice

- API korijen je `/api/v1`.
- Prva verzija podržava JSON i UTF-8.
- Svaki domenski modul ostaje vlasnik svojih podataka, validacije i ACL odluka.
- `module-api` pruža zajednički HTTP sloj, ali ne čita izravno tablice drugih
  modula.
- Postojeće web-rute i session prijava nisu javni API ugovor.
- API se može isključiti uklanjanjem `module-api`; ostali moduli nastavljaju
  raditi.
- API ključ ne može korisniku dati pravo koje nema u aplikaciji.

## 2. Vlasništvo modula

| Modul | API odgovornost |
|---|---|
| `module-api` | Verzije ruta, JSON odgovori, problem odgovori, paginacija, OpenAPI, CORS, rate limiting, request ID i registar idempotencije |
| `module-auth` | API ključevi, autentikacija ključa, scopes, korisnički identitet, lokalni user/group CRUD i auth audit |
| `module-workspace` | Područja, stabla, čvorovi, workflow i Workspace ACL |
| `module-editor-html` | Dokumenti, jezici, verzije, HTML, privici i objavljeni prikaz |
| `module-calendar` | Kalendari, događaji, ponavljanja, calendar ACL i ICS |
| `module-task` | Definicije zadataka, aktualna stanja i povijest promjena |
| `module-notification` | Obavijesti trenutačnog korisnika |
| `module-email` | Nema javnu krajnju točku za proizvoljno slanje pošte; ostaje interni servis |
| `module-theme` | Opcionalni read-only opis aktivne javne teme |
| `module-menu` | Opcionalni read-only izbornik filtriran za trenutačni API identitet |

Domenski modul prijavljuje svoje API rute samo kada je `module-api` instaliran.
Opcionalna integracija ne smije postati njegova obavezna Composer ovisnost.

## 3. API identitet i ključevi

Ključ je vezan uz postojećeg aktivnog korisnika. Autorizacija se provodi ovim
redoslijedom:

1. ključ postoji, nije opozvan i nije istekao
2. vlasnički korisnik postoji i aktivan je
3. ključ ima potreban scope
4. korisnik ima potrebno domensko pravo kroz postojeći ACL

Predloženi format ključa je:

```text
hfp_live_<public-id>_<secret>
```

`public-id` služi za pronalazak zapisa. Tajni dio generira se kriptografski
sigurnim generatorom, prikazuje samo jednom i u bazi se čuva isključivo njegov
hash. Nikada se ne zapisuje u audit, iznimku, access log ili HTML.

Svaki ključ sadrži:

- naziv i opcionalni opis
- `user_id`
- skup scopes
- vrijeme isteka ili `null`
- `created_at`, `last_used_at` i `revoked_at`
- opcionalni popis dopuštenih IP adresa
- javni prefiks za sigurno prepoznavanje ključa u administraciji

## 4. Scopes

Početni registar koristi stabilne ASCII nazive:

```text
workspace:read
workspace:manage
page:read
page:write
page:publish
attachment:read
attachment:write
calendar:read
calendar:write
task:read
task:write
users:read
users:create
users:update
users:delete
groups:read
groups:manage
notifications:read
notifications:write
webhooks:read
webhooks:manage
```

`page:write` obuhvaća izradu stranice i njezina prvog nacrta, izmjenu ili
odbacivanje nacrta, prijevod, vraćanje verzije i brisanje. `page:publish` je
zasebno pravo pregleda i objave. `groups:manage` obuhvaća izradu, izmjenu i
brisanje grupa te dodavanje i uklanjanje članova.

Administratorski status sam po sebi nije dovoljan za API operaciju. Ključ
aktivnog administratora mora imati i odgovarajući scope. `users:*` i
`groups:*` krajnje točke rade samo s lokalnim Auth zapisima; ne upravljaju
korisnicima u SAML, CAS, OIDC ili OAuth provideru.

`DELETE /users/{id}` deaktivira lokalni račun i opoziva njegove API ključeve.
Fizičko brisanje nije API operacija jer bi prekinulo audit, vlasništvo i
povijesne reference.

## 5. Zajednički HTTP ugovor

### Zahtjev

- `Authorization: Bearer <api-key>`
- `Accept: application/json`
- `Content-Type: application/json` za JSON tijelo
- `Accept-Language` određuje zadani jezik odgovora
- eksplicitni parametar `lang` ima prednost za višejezični sadržaj
- klijent može poslati `X-Request-Id`, a poslužitelj ga uvijek vraća

### Uspješan odgovor

```json
{
  "data": {},
  "meta": {
    "request_id": "opaque-request-id"
  },
  "links": {}
}
```

Kolekcije koriste cursor paginaciju:

```text
page[limit]=50
page[after]=opaque-cursor
filter[...]=...
sort=field,-other_field
```

Zadani limit je `50`, najveći je `100`, a cursor je neproziran i klijent ga ne
smije rastavljati.

### Pogreška

Pogreške koriste `application/problem+json` i stabilni strojno čitljiv `code`:

```json
{
  "type": "https://heartphrame.example/problems/forbidden",
  "title": "Forbidden",
  "status": 403,
  "detail": "Trenutačni identitet ne smije čitati ovaj resurs.",
  "code": "resource_forbidden",
  "request_id": "opaque-request-id"
}
```

Produkcijski odgovor ne sadrži SQL, stack trace, datotečne putanje ni tajne.

### Vrijeme i identifikatori

- Datumi u JSON-u koriste ISO 8601 i UTC, primjerice
  `2026-07-28T12:00:00Z`.
- Identifikatori su JSON nizovi znakova i smatraju se neprozirnima čak i kada je
  interni ključ broj.
- Prikazni naziv ili slug nikada nije zamjena za stabilni identifikator.

## 6. Konkurentne izmjene

Promjenjivi resursi vraćaju `ETag`, a izmjena traži odgovarajući `If-Match`.
Editor dodatno zadržava eksplicitni `draft_revision` za domensku zaštitu nacrta.
Zastarjeli ETag vraća `412 Precondition Failed`, a sukob draft revizije
`409 Conflict`.

POST operacije koje klijent može ponoviti podržavaju `Idempotency-Key`. Isti
korisnik, krajnja točka i ključ zahtjeva moraju vratiti prethodni rezultat bez
ponavljanja poslovne operacije.

## 7. Početni resursi

### Read-only v1 jezgra

```text
GET /api/v1/me
GET /api/v1/workspaces
GET /api/v1/workspaces/{workspaceId}
GET /api/v1/workspaces/{workspaceId}/tree
GET /api/v1/pages/{pageId}
GET /api/v1/pages/{pageId}/attachments
GET /api/v1/calendars
GET /api/v1/calendars/{calendarId}
GET /api/v1/calendars/{calendarId}/events
GET /api/v1/tasks
GET /api/v1/tasks/{taskId}
GET /api/v1/notifications
```

Kolekcije sadrže samo resurse koje vlasnik ključa smije vidjeti. Pojedinačni
nedostupan resurs vraća `404` kada bi `403` otkrio postojanje privatnog resursa.

### Administracija lokalnih korisnika

```text
GET    /api/v1/users
POST   /api/v1/users
GET    /api/v1/users/{userId}
PATCH  /api/v1/users/{userId}
DELETE /api/v1/users/{userId}
GET    /api/v1/groups
POST   /api/v1/groups
PATCH  /api/v1/groups/{groupId}
DELETE /api/v1/groups/{groupId}
PUT    /api/v1/users/{userId}/groups
```

Ove krajnje točke zahtijevaju aktivnog lokalnog administratora i odgovarajuće
`users:*` ili `groups:*` scopes. API ne vraća `password_hash`, provider tajne,
reset tokene ni osjetljivu konfiguraciju. Lozinka se prima samo pri izradi
korisnika ili izričitoj promjeni i nikada se ne vraća.

## 8. Write operacije

Nacrti, objava, privici, događaji i zadaci koriste domenske rute:

```text
POST  /api/v1/workspaces/{workspaceId}/pages
PATCH /api/v1/pages/{pageId}/draft
POST  /api/v1/pages/{pageId}/review
POST  /api/v1/pages/{pageId}/publish
POST  /api/v1/pages/{pageId}/attachments
POST  /api/v1/calendars/{calendarId}/events
PATCH /api/v1/calendar-events/{eventId}
PATCH /api/v1/tasks/{taskId}/state
```

### Webhook pretplate

```text
GET    /api/v1/webhooks
POST   /api/v1/webhooks
GET    /api/v1/webhooks/{uuid}
PATCH  /api/v1/webhooks/{uuid}
DELETE /api/v1/webhooks/{uuid}
POST   /api/v1/webhooks/{uuid}/rotate-secret
GET    /api/v1/webhooks/{uuid}/deliveries
GET    /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}
POST   /api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}/retry
```

Mutacija se dovršava prije mrežne isporuke. API sprema sanitizirani događaj u
outbox, a CLI worker ga potpisuje HMAC-SHA256 potpisom i šalje uz ograničeni
retry. Tajna je šifrirana u bazi i vraća se samo pri izradi ili rotaciji.

## 9. Kompatibilnost

- Nova polja u objektu odgovora dopuštena su unutar `v1`.
- Postojeća polja ne mijenjaju tip ni značenje unutar `v1`.
- Uklanjanje ili promjena značenja zahtijeva `/api/v2`.
- Nova opcionalna krajnja točka ne zahtijeva novu glavnu verziju API-ja.
- OpenAPI opis generira se iz istog registra koji registrira runtime rute.
- Interni PHP potpis servisa nije automatski dio javnog HTTP ugovora.

## 10. Sigurnosni minimum

Prije prvog javnog write endpointa implementacija mora osigurati:

- hashirane ključeve i prikaz tajne samo jednom
- opoziv i istek ključa
- provjeru scopea i domenskog ACL-a
- rate limiting po ključu i IP adresi
- audit write operacija i neuspjelih autentikacija
- ograničenje veličine zahtjeva
- sigurnu validaciju MIME tipa za upload
- CORS isključen prema zadanim postavkama
- zaštitu osjetljivih podataka u zapisima i problem odgovorima
