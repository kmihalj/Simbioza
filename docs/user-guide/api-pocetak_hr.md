# API — početak i zajednička pravila

[← API — sadržaj](api-koristenje_hr.md)

API služi povezivanju Simbioze s drugim sustavima. Administrator prvo instalira i aktivira modul API te izda ključ stvarnom aktivnom korisniku. Ključ ima izabrane scopeove, opcionalni rok valjanosti i IP ograničenja. Korisnik zadržava svoja stvarna prava: administratorski scope ne pretvara običnog korisnika u administratora.

U [administratorskim uputama](admin-api_hr.md) opisano je izdavanje, odobravanje, rotacija i opoziv ključa. Ovdje su zahtjevi za razvoj aplikacije. Sve naredbe i prikazani odgovori provjereni su stvarnim HTTP zahtjevima na zasebnim sintetičkim podacima. Identifikatori, vremena i ETag vrijednosti razlikovat će se na vašoj instalaciji; koristite vrijednosti iz vlastitih odgovora. Tajne su zamijenjene oznakom `[REDACTED]`.

## Priprema

Postavite adresu svoje instalacije, uključujući poddirektorij, bez završne kose crte. Ključ ne zapisujte u izvorni kod ni javni repozitorij.

```sh
export BASE_URL='https://example.org/simbioza'
read -r -s SIMBIOZA_API_KEY
export SIMBIOZA_API_KEY
```

Sljedeće naredbe koriste `curl --globoff` kako zagrade u parametrima `page[limit]` ne bi bile protumačene kao uzorak. `jq` se koristi samo za pregledan odabir dijela velikih odgovora; nije potreban samoj Simbiozi. Ključ se šalje zaglavljem `Authorization: Bearer`, ne URL-om ili kolačićem preglednika.

## Tijelo zahtjeva i odgovori

Upisi koriste `Content-Type: application/json` i vršni JSON objekt s imenovanim poljima. `{}` je valjan objekt, ali radnja može zahtijevati obvezna polja. `[]`, skalarne vrijednosti i vršni numerički nazivi polja nisu valjani; ugniježđene liste ostaju dopuštene. Kod radnji koje ne čitaju tijelo, primjer ga može izostaviti. Survey radnje zahtijevaju JSON i svoje podatke o verziji.

Uspješan JSON odgovor koristi `data`, `meta` i `links`. `meta.request_id` služi povezivanju s dnevnikom. Prazan odgovor `204` nema JSON. OpenAPI vraća izravni dokument, a privitci, CSV, ICS i ZIP vraćaju datoteku: provjerite `Content-Type` prije JSON dekodiranja.

## Paginacija

Mnogi popisi koriste `page[limit]` (zadano 50, najviše 100) i neprozirni `page[after]`. Slijedite `links.next` dok nije `null`; cursor nemojte izrađivati ili mijenjati sami. Nevaljan cursor može vratiti prvu stranicu. Nemaju svi resursi isti ugovor: Survey koristi `page/per_page`, pretraga također `page/per_page`, a sažetci područja `page/limit`. Osobna praćenja vraćaju ograničen popis bez cursor ugovora.

## Zaštita od istodobnih izmjena

Za rute označene `If-Match` najprije dohvatite aktualni resurs ili nacrt i pročitajte HTTP zaglavlje `ETag`. U sljedeći upis pošaljite tu vrijednost kao `If-Match`, uključujući navodnike. U primjerima je to varijabla `ETAG`. Nemojte koristiti ETag druge jezične inačice ili objavljene stranice kada mijenjate nacrt. Zadano se nedostajuće zaglavlje odbija statusom 428, a zastarjelo statusom 412. HTML nacrt dodatno ima `draft_revision`; Survey koristi zasebni `version` i `revision`, bez ETag-a.

```sh
curl --globoff --silent --show-error \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  "$BASE_URL/api/v1/me"
```

Ovaj primjer pokazuje spremanje zaglavlja; `ETAG` uzmite iz odgovarajućeg GET odgovora resursa, ne iz `/me`.

## Ponovljeni upisi i ograničenja

Za JSON upise možete poslati jedinstveni `Idempotency-Key`. Isti ključ i isti zahtjev vraćaju prethodni rezultat; isti ključ uz drugo tijelo vraća 409. Za multipart i binarna slanja to zaglavlje nije podržano i vraća 422. Zadano ograničenje je 120 zahtjeva u minuti po ključu, uz `X-RateLimit-*` i `Retry-After` kod429; administrator ga može prilagoditi. Zadana najveća veličina JSON zahtjeva je 2MiB, a pojedini moduli dodatno ograničavaju sadržaj. CORS je zasebna administratorska postavka i ne zamjenjuje Bearer prijavu.

OpenAPI se čita uz valjan ključ. Prikazuje registrirane rute i scopeove aktivnih modula, ali njegove opće sheme ne opisuju sve poslovne parametre, multipart polja i binarne odgovore; zato koristite i pojedinačna poglavlja ovog priručnika. CalDAV i javne ICS poveznice pripadaju drugim protokolima i nisu dio tog dokumenta.

## Operacije i provjereni primjeri

### `GET /api/v1`

**Svrha:** Vraća verziju, dostupne resurse i scopeove uključenih modula.

**Ovlast:** valjan API ključ. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1" | jq '{data: (.data | {name,version,resources,scopes,security}), meta, links}'
```

Stvarni odgovor: **HTTP 200**. Prikazan je stvarni izlaz navedenog `jq` filtra.

```json
{
  "data": {
    "name": "HeartPhrame API",
    "version": "v1",
    "resources": [
      "users",
      "groups",
      "audit",
      "webhooks",
      "notifications",
      "page",
      "attachment",
      "task",
      "workspace",
      "workspace-search",
      "surveys",
      "calendar",
      "follows"
    ],
    "scopes": [
      "users:read",
      "users:create",
      "users:update",
      "users:delete",
      "groups:read",
      "groups:manage",
      "audit:read",
      "webhooks:read",
      "webhooks:manage",
      "notifications:read",
      "notifications:write",
      "page:read",
      "page:write",
      "page:publish",
      "attachment:read",
      "attachment:write",
      "task:read",
      "task:write",
      "workspace:read",
      "workspace:manage",
      "workspace-search:read",
      "surveys:read",
      "surveys:write",
      "surveys:publish",
      "surveys:results",
      "surveys:export",
      "calendar:read",
      "calendar:write",
      "follows:read",
      "follows:write"
    ],
    "security": {
      "authentication": "bearer",
      "rate_limit_headers": true,
      "idempotency_key_for_writes": true,
      "problem_format": "RFC 9457",
      "webhooks": {
        "enabled": true,
        "delivery": "asynchronous",
        "signature": "hmac-sha256"
      }
    }
  },
  "meta": {
    "request_id": "cc6b890db5532c15134ac71575045fb5"
  },
  "links": {
    "self": "/api/v1"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/me`

**Svrha:** Vraća identitet, scopeove i javne korisničke podatke bez lozinke.

**Ovlast:** valjan API ključ. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/me"
```

Stvarni odgovor: **HTTP 200**.

```json
{
  "data": {
    "key": {
      "id": 1,
      "public_id": "72ea299cf26253dc485c6a95",
      "scopes": [
        "users:read",
        "users:create",
        "users:update",
        "users:delete",
        "groups:read",
        "groups:manage",
        "audit:read",
        "webhooks:read",
        "webhooks:manage",
        "notifications:read",
        "notifications:write",
        "page:read",
        "page:write",
        "page:publish",
        "attachment:read",
        "attachment:write",
        "task:read",
        "task:write",
        "workspace:read",
        "workspace:manage",
        "workspace-search:read",
        "surveys:read",
        "surveys:write",
        "surveys:publish",
        "surveys:results",
        "surveys:export",
        "calendar:read",
        "calendar:write",
        "follows:read",
        "follows:write"
      ]
    },
    "user": {
      "id": 1,
      "login_identifier": "Admin",
      "display_name": "Administrator",
      "email": "admin@example.test",
      "first_name": "Admin",
      "last_name": "Simbioza",
      "group_ids": [
        1
      ],
      "group_keys": [
        "administrator"
      ],
      "is_admin": true,
      "is_active": true,
      "auth_source": "local"
    }
  },
  "meta": {
    "request_id": "bd11f1fa32846abeb0f47349982394fb"
  },
  "links": {
    "self": "/api/v1/me"
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401, 429. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

### `GET /api/v1/openapi.json`

**Svrha:** Vraća OpenAPI 3.1 JSON za trenutačno uključene module.

**Ovlast:** valjan API ključ. Za webhookove samo vlastite pretplate; webhook konfiguracija mora ih dopuštati. Discovery/me/OpenAPI traže valjan Bearer ključ.

**Query parametri:** nema.

**Tijelo:** radnja nema dodatna ulazna polja; pratite primjer.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/openapi.json" | jq '{openapi, info, path_count: (.paths | length), security, components: {securitySchemes: .components.securitySchemes}}'
```

Stvarni odgovor: **HTTP 200**. Prikazan je stvarni izlaz navedenog `jq` filtra.

```json
{
  "openapi": "3.1.0",
  "info": {
    "title": "HeartPhrame API",
    "version": "1.0.0",
    "description": "Versioned modular API. Domain ACL is always evaluated in addition to key scopes."
  },
  "path_count": 77,
  "security": [
    {
      "bearerAuth": []
    }
  ],
  "components": {
    "securitySchemes": {
      "bearerAuth": {
        "type": "http",
        "scheme": "bearer",
        "bearerFormat": "HeartPhrame API key"
      }
    }
  }
}
```

**Pogreške provjerene za ovu operaciju:** 401. Značenje statusa i struktura pogreške: [zajednička pravila](api-pocetak_hr.md).

## Pogreške i dijagnostika

Provjerite HTTP status prije čitanja `data`. Odbijeni zahtjev vraća `application/problem+json`, s poljima `status`, `code`, `detail` i `request_id`. Za privatni sadržaj404 ne potvrđuje postoji li resurs. Ne isključujte provjere ovlasti ili CSRF zaštitu web obrazaca radi otklanjanja API pogreške. API Bearer prijava odvojena je od web sesije.

| HTTP | Značenje |
|---|---|
| 400 | neispravan JSON ili zahtjev |
| 401 | ključ nedostaje, nije valjan ili nije dopušten |
| 403 | nedostaje scope ili stvarna ovlast |
| 404 | resurs nije dostupan tom korisniku ili ne postoji |
| 409 | konflikt verzije, stanja ili ponovljenog zahtjeva |
| 412 | zastarjeli If-Match |
| 415 | nepodržan tip nepraznog tijela |
| 422 | nevaljana poslovna polja ili nepodržano slanje |
| 428 | nedostaje obvezni If-Match |
| 429 | dosegnuto ograničenje prometa; pratite Retry-After |

### Bez prijave

Stvarni odgovor HTTP 401:

```json
{
  "type": "about:blank",
  "title": "Authentication failed",
  "status": 401,
  "detail": "Send a valid active API key as a Bearer token.",
  "code": "invalid_api_key",
  "instance": "/api/v1",
  "request_id": "1022fef5ddfad19a8c78dd5c87f8bf70"
}
```

### Nedostaje zaštita izmjene

Stvarni odgovor HTTP 428:

```json
{
  "type": "about:blank",
  "title": "The change precondition was not met",
  "status": 428,
  "detail": "Read the resource before changing it and send its ETag as If-Match.",
  "code": "if_match_required",
  "instance": "/api/v1/workspaces/api-ogledno-podrucje",
  "request_id": "a0153b5a77ba484de02d0c9e35974996"
}
```

### Isti ključ, drugo tijelo

Stvarni odgovor HTTP 409:

```json
{
  "type": "about:blank",
  "title": "The Idempotency-Key has already been used",
  "status": 409,
  "detail": "The same key must not be used for different requests.",
  "code": "idempotency_key_reused",
  "instance": "/api/v1/groups",
  "request_id": "622b2c534cf92ddc3f402f0de733122a"
}
```

### Dosegnuto ograničenje

Stvarni odgovor HTTP 429:

```json
{
  "type": "about:blank",
  "title": "Too many API requests",
  "status": 429,
  "detail": "Wait before the next request.",
  "code": "rate_limit_exceeded",
  "instance": "/api/v1/me",
  "request_id": "a43267398714c9a8ac74f166a959cc41"
}
```

## PHP — potpuni mali klijent

Ovo je zaseban PHP primjer; nije potrebno prepisivati curl naredbe u kod. Spremite funkciju kao `api-client.php`. Potrebno je PHP proširenje cURL. Produkcijska adresa mora biti HTTPS; HTTP je dopušten samo za lokalni razvoj na loopback adresi. Funkcija podržava JSON, multipart i binarne odgovore, ne slijedi preusmjeravanja i baca iznimku za HTTP pogreške. Tajne čitajte iz svojeg sigurnog spremišta ili varijabli okoline.

```php
<?php

declare(strict_types=1);

/**
 * EN: Calls the REST API; redirects are disabled so credentials stay on the configured host.
 * HR: Poziva REST API; preusmjeravanja su isključena kako vjerodajnice ne bi otišle drugom hostu.
 *
 * @param array<string,mixed>|stdClass|null $body
 * @param list<string> $extraHeaders
 * @param array<string,string|CURLFile>|null $multipart
 * @return array{status:int,headers:array<string,string>,json:mixed,body:string}
 */
function simbiozaApi(
    string $baseUrl,
    #[SensitiveParameter] string $key,
    string $method,
    string $path,
    array|stdClass|null $body = null,
    array $extraHeaders = [],
    ?array $multipart = null,
): array {
    if (!extension_loaded('curl')) {
        throw new RuntimeException('PHP cURL extension is required.');
    }
    $url = parse_url($baseUrl);
    $local = in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true);
    if (!is_array($url) || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
        || (($url['scheme'] ?? '') !== 'https' && !$local)
        || !isset($url['host']) || isset($url['user']) || isset($url['pass'])
        || isset($url['query']) || isset($url['fragment']) || trim($key) === '') {
        throw new InvalidArgumentException('Set a valid HTTPS installation URL and API key.');
    }
    if (($path !== '/api/v1' && !str_starts_with($path, '/api/v1/'))
        || str_contains($path, "\r") || str_contains($path, "\n")
        || ($body !== null && $multipart !== null)) {
        throw new InvalidArgumentException('Use an /api/v1 path and one request-body format.');
    }
    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $key, ...$extraHeaders];
    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ];
    if ($multipart !== null) {
        // EN: cURL supplies the multipart boundary. HR: cURL sam postavlja multipart razdjelnik.
        $options[CURLOPT_POSTFIELDS] = $multipart;
    } elseif ($body !== null) {
        // EN: An empty named-field object must be {}, not []. HR: Prazan objekt polja mora biti {}, ne [].
        $options[CURLOPT_POSTFIELDS] = json_encode($body === [] ? new stdClass() : $body, JSON_THROW_ON_ERROR);
        $headers[] = 'Content-Type: application/json';
    }
    $responseHeaders = [];
    $options[CURLOPT_HTTPHEADER] = $headers;
    $options[CURLOPT_HEADERFUNCTION] = static function ($handle, string $line) use (&$responseHeaders): int {
        if (str_contains($line, ':')) {
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[strtolower(trim($name))] = trim($value);
        }
        return strlen($line);
    };
    $handle = curl_init(rtrim($baseUrl, '/') . $path);
    curl_setopt_array($handle, $options);
    $raw = curl_exec($handle);
    if ($raw === false) {
        $message = curl_error($handle);
        unset($handle);
        throw new RuntimeException('HTTP transport failed: ' . $message);
    }
    $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    unset($handle);
    $json = str_contains($responseHeaders['content-type'] ?? '', 'json') && $raw !== ''
        ? json_decode($raw, true, 512, JSON_THROW_ON_ERROR) : null;
    if ($status >= 400) {
        $detail = is_array($json) ? (string)($json['detail'] ?? 'Request rejected') : 'Request rejected';
        $requestId = $responseHeaders['x-request-id'] ?? '';
        throw new RuntimeException("HTTP $status: $detail (request_id=$requestId)");
    }
    return ['status' => $status, 'headers' => $responseHeaders, 'json' => $json, 'body' => $raw];
}
```

Primjer korištenja (prvo postavite varijable okoline iz odjeljka Priprema):

```php
<?php
require __DIR__ . '/api-client.php';
$base = (string)getenv('BASE_URL');
$key = (string)getenv('SIMBIOZA_API_KEY');
$reply = simbiozaApi($base, $key, 'GET', '/api/v1/me');
echo $reply['json']['data']['user']['display_name'], PHP_EOL;
```

Stvarni rezultat ovog primjera na sintetičkom administratorskom računu:

```text
Administrator
```

Za JSON upis proslijedite imenovana polja kao peti argument; za prazan objekt koristite `[]` (funkcija ga kodira u `{}`). Za ETag dodajte `['If-Match: ' . $etag]` kao šesti argument. Multipart se šalje sedmim argumentom, primjerice `['attachment' => new CURLFile(__DIR__ . '/notes.txt')]`, uz peti argument `null`. Ne postavljajte ručno multipart Content-Type. Za datoteku koristite `file_put_contents()` nad `body`; JSON se nalazi u `json`. Pojedinačne putanje, tijela i ovlasti identični su curl primjerima u ostalim poglavljima.
