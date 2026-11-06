# Implementirani ugovor API-ja v1

Ovo je aktualni HTTP API Simbioze, a ne plan implementacije. Zajednički HTTP sloj
pruža `heartphrame-module-api`; uključeni domenski moduli registriraju vlastite
rute i provode svoja poslovna pravila. Framework je uzvodna ovisnost i ova
integracija ne mijenja njegov kod.

## Počnite od stvarne instalacije

Bazna putanja je `/api/v1` u odnosu na aplikaciju. Za instalaciju na
`https://example.test/simbioza` postavite:

```bash
export HPH_API_URL='https://example.test/simbioza/api/v1'
read -r -s HPH_API_TOKEN
export HPH_API_TOKEN
curl --fail-with-body --silent --show-error \
  --header "Authorization: Bearer $HPH_API_TOKEN" "$HPH_API_URL/me"
```

Ključ kreirajte u **Postavke → Korisnici → API ključevi**, kopirajte ga jednom i
držite izvan izvornog koda i logova. Auth posjeduje izdane ključeve, jednosmjerne
hashove tajni, aktualni identitet korisnika/grupa, istek, opoziv i opcionalna IP
ograničenja. API posjeduje administraciju/zahtjeve i HTTP sloj. Stvarni oblik
tokena je `hfp_live_<public-id>_<secret>`; klijent cijelu vrijednost treba
smatrati neprozirnom tajnom. Izgubljeni ključ rotirajte ili opozovite umjesto
pokušaja ponovnog čitanja tajne.

Aktualni vlasnik mora postojati i ostati aktivan. Svaka radnja traži i scope
ključa i stvarne domenske ovlasti vlasnika. Administratorski status ne zaobilazi
scope provjeru. Browser sesija, vanjska prijava samo za anketu ni ID korisnika u
poslanom JSON-u ne mogu preuzeti identitet drugog vlasnika API ključa.

## Otkrivanje API-ja i trenutačni identitet

Sve tri rute jezgre zahtijevaju valjan Bearer ključ:

```text
GET /api/v1
GET /api/v1/me
GET /api/v1/openapi.json
```

Discovery vraća resurse i grupe scopeova **trenutačno uključenih** modula.
`/me` vraća siguran profil vlasnika te javni identitet/scopeove trenutačnog
ključa, nikada njegovu tajnu. `/openapi.json` generira OpenAPI 3.1 pregled iz
aktivnog registra ruta, s adresom instalacije u `servers`. Vraća sam dokument
vrste `application/vnd.oai.openapi+json;version=3.1.0`, uz
`Cache-Control: no-store`, bez omotača `data`/`meta`/`links`.

Generirani dokument opisuje aktivne rute i zajedničke omotače. Sheme tijela
zahtjeva/odgovora su općenite: točna polja, multipart upload, CSV/ICS/binarna
preuzimanja, statusne kodove i pravila tijeka rada pročitajte u domenskoj
referenci. Uvoz dokumenta u klijent ne dodjeljuje scopeove ni prava.
CORS `OPTIONS` preflight bez autentikacije zaseban je postupak koji provjerava
podešene dopuštene izvore, metode i zaglavlja.

## Implementirani resursi i vlasništvo

| Vlasnički modul | API obitelj i scopeovi | Aktualno ponašanje |
| --- | --- | --- |
| Auth | `/users`, `/groups`; `users:read/create/update/delete`, `groups:read/manage` | Traži aktivnog administratorskog vlasnika. Samo lokalni računi/grupe; ne upravlja računima vanjskog providera. DELETE korisnika deaktivira račun i opoziva njegove ključeve. |
| Workspace | `/workspaces`; `workspace:read/manage` | ACL-filtrirana područja/stablo, metapodaci, čvorovi/ovlasti, sažeci, HTML izvoz i posebni izgled područja. |
| HTML Editor | `/pages`; `page:read/write/publish`, `attachment:read/write` | Sadržaj stranica, jezici, objavljene verzije, zajednički nacrt/pregled/objava i privitci. Samostalni zapis odmah je objavljen, a Workspace zapis slijedi njegov nacrt. |
| Calendar | `/calendars`; `calendar:read/write` | CRUD kalendara/događaja, ACL, ponavljanja i ICS uvoz/izvoz. DELETE kalendara je nepovratan. |
| Task | `/pages/{documentId}/tasks`; `task:read/write` | Definicije objavljene stranice, trenutačno stanje i povijest; primjenjuju se ACL stranice i opseg liste zadataka. |
| Notification | `/notifications`; `notifications:read/write` | Samo vidljivi inbox vlasnika ključa; nema rute za proizvoljno kreiranje obavijesti. |
| Workspace Search | `/workspace-search`; `workspace-search:read` | ACL-filtrirani naslovi, objavljeni tekst/autori, isječci, zbrojevi i filtri. |
| Survey | `/surveys`; `surveys:read/write/publish/results/export` | Definicije, nacrt/pregled/objava/oporavak, zbirni rezultati, pojedinačni odgovori samo za upravitelje i privatni izvoz. |
| Audit | `/audit`; `audit:read` | Redigirani dnevnik aktivnosti samo za čitanje aktivnom administratoru; nema API preuzimanja tehničkih logova. |
| Simbioza User | `/me/follows`, `/me/follow-preferences`; `follows:read/write` | Praćenja i postavke obavijesti/izgleda samo vlasnika ključa; vrijede ovlasti nad ciljem. |
| API | `/webhooks`; `webhooks:read/manage` | Pretplate vlasničkog ključa, povijest dostave, rotacija tajne i ponavljanje; asinkrona potpisana dostava. |

Isključeni domenski modul ne doprinosi ni rute ni scopeove. Menu, Theme i Email
nemaju zasebnu javnu HTTP obitelj resursa. Email ostaje interni transport.
Radnje teme vezane uz područje pripadaju integraciji Workspacea, a ne globalnom
Theme API-ju. Sigurnosne kopije koriste zaseban ovlašteni Backup postupak;
nisu CSV izvoz rezultata.

## Status osobnog područja

`GET /me/personal-workspace` registrira Simbioza User i zahtijeva oglašeni scope
`workspace:read`. Vraća samo postojeću vezu vlasnika API ključa s njegovim
osobnim područjem i nikada ne stvara područje. Ako veza ne postoji, vraća
`exists: false` i `workspace: null`. Praćenja i njihove postavke koriste
oglašene scopeove `follows:*`.

## Primjeri ruta

Sve sljedeće putanje relativne su istoj baznoj adresi API-ja:

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
GET    /workspace-search?q=upute&lang=hr&per_page=10
POST   /surveys
POST   /surveys/{survey}/submit-review
POST   /surveys/{survey}/publish
GET    /surveys/{survey}/results
POST   /surveys/{survey}/exports
```

Stranica se kreira kroz `/pages` s odgovarajućim poljima smještaja, ne preko
izmišljene rute `/workspaces/{id}/pages`. Task rute vezane su uz objavljeni
dokument, a ne uz globalnu kolekciju `/tasks`. Slanje nacrta stranice na pregled
traži `page:write`; objava dodatno traži `page:publish` i stvarne ovlasti
objavitelja. U anketama `delete` čuva prihvaćene odgovore u košu,
`discard-draft` uklanja samo noviji radni nacrt, a administratorski `restore`
vraća nekadašnju objavu kao zatvorenu.

Nema API-ja predaje samo pomoću tokena niti rute koja bi preuzela nečiju browser
SAML/OIDC prijavu za anketu. Ispitanici koriste redovni postupak pristupa i
predaje ankete s pravom sudjelovanja, CSRF-om i zaštitom od ponavljanja.

## Odgovori i paginacija prema domeni

Uobičajeni uspješni JSON odgovor koristi:

```json
{
  "data": {},
  "meta": {"request_id": "req_example"},
  "links": {"self": "/simbioza/api/v1/me"}
}
```

Greške koriste RFC 9457 `application/problem+json`:

```json
{
  "type": "about:blank",
  "title": "Forbidden",
  "status": 403,
  "detail": "API ključ nema potreban scope.",
  "code": "insufficient_scope",
  "request_id": "req_example"
}
```

Za dijagnostiku zadržite `request_id`, ne token. Statusi, vrste identifikatora
DTO-a i formati vremena slijede referencu pojedinog vlasničkog modula; nema
općeg jamstva da je svaki ID string ili svako vrijeme ISO 8601. Kreiranje može
vratiti `201`, brisanje `204`, a preuzimanja svoju stvarnu medijsku vrstu.

Paginacija nije ista za sve resurse. Users/Groups te glavni popisi Calendara i
Workspacea koriste neprozirni cursor `page[after]` i ograničeni `page[limit]`
(zadano 50, najviše 100). Audit i Workspace Search koriste `page`/`per_page`.
Survey metapodatke popisa/rezultata vraća unutar `data`, uključujući `items`
ondje gdje je primjenjivo. Slijedite vraćene `links` i metapodatke te domene.
Ne pretpostavljajte globalne parametre `filter[...]` ili `sort` koje ruta ne
podržava.

## Pisanje, sukobi i prijenos

- JSON šaljite kao `Content-Type: application/json`; veličina tijela je ograničena.
- Za svaku logičku radnju koristite novi `Idempotency-Key` duljine 8–190 znakova.
  Točno ponovljen zahtjev može dobiti spremljeni sigurni odgovor; drugačiji
  zahtjev s istim ključem vraća `409`. Multipart/streamani upload odbija to
  zaglavlje i koristi vlastiti protokol prijenosa.
- Ondje gdje resurs vraća `ETag` i ruta traži uvjetnu izmjenu, pošaljite ga kao
  `If-Match`. Zadano izostavljeni uvjet vraća `428`, a zastarjeli `412`.
  Ugovori Calendara/Workspacea/webhookova navode gdje se to primjenjuje. Nacrti
  stranica i Survey koriste vlastiti `draft_revision` ili `version`/`revision`
  i vraćaju `409` za zastarjelu izmjenu.
- Zadano ograničenje je 120 zahtjeva u minuti po ključu, uz zaglavlja limita i
  `429`/`Retry-After` pri iscrpljenju. Greška sigurnosne pohrane ne zaobilazi
  zaštitu pisanja.
- CORS je zadano isključen. Izričito dopustite samo namijenjene browser izvore.
  CORS ne zamjenjuje autentikaciju ključa ni ACL.
- Webhookovi koriste HTTPS/provjeru javnog cilja, šifrirane potpisne tajne,
  HMAC-SHA256 i asinkroni CLI worker s ograničenim ponavljanjem. Domenska
  radnja ne čeka mrežni zahtjev prema primatelju. Prije implementacije potrošača
  pročitajte detaljnu referencu workera i potpisa.

Uobičajena PHP sesijska prijava i browser CSRF forme nisu mehanizam prijave u
API. Koristite HTTPS i instalirani API modul umjesto izlaganja web kontrolera
ili privatnih tablica kao prečaca.

## Detaljne reference i primjeri

- [API brzi početak: cURL, PHP i očekivani odgovori](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/quickstart_hr.md)
- [Discovery i OpenAPI](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/discovery_hr.md)
- [Sigurnost, uvjetne izmjene i paginacija](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/security_hr.md)
- [API HTML Editora](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-editor-html/-/blob/main/docs/api_hr.md)
- [API anketa i primjeri](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-survey/-/blob/main/docs/api_hr.md)
- [Webhook operativa](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-api/-/blob/main/docs/webhooks_hr.md)

Za novi klijent provjerite `/me`, otkrijte uključene resurse, pročitajte ovlašteni
objekt, provjerite kontroliranu izmjenu i njezin sukob/zastarjeli zahtjev u
izoliranoj instalaciji pa ograničite ključ na konačne potrebne scopeove i IP-eve.
