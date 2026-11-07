# API: calendari ed eventi

<a id="api--calendars-and-events"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

`calendar:read` legge i calendari accessibili al proprietario della chiave. `calendar:write` integra le attuali autorizzazioni di creazione, scrittura e gestione. Un calendario necessita di `name`; `type`/`calendar_type` può essere `personal`, `team` o `resource`, soggetto a regole di proprietà e gestione.

I calendari PATCH e DELETE richiedono un ETag. ACL PUT accetta l'elenco completo `rules`: `subject_type` (`user`/`group`), numerico `subject_id`, `can_read`, `can_write`. Sostituisce le regole dirette, quindi leggi lo stato attuale prima di scrivere.

Un evento richiede `title` e valori di inizio/fine validi (`starts_at`, `ends_at`), con `timezone`. `is_all_day` contrassegna gli eventi dell'intera giornata. Le varianti di data/ora e gli alias sono elencati per operazione. La ricorrenza utilizza `recurrence_rule` o campi frequenza/fine separati. L'intervallo di eventi GET richiede `from` e `to`; `expand_recurring=false` legge le definizioni, mentre l'espansione restituisce le occorrenze. Gli ID evento sono numerici, mentre i percorsi del calendario utilizzano UUID.

L'importazione ICS invia il testo in `ics`, numerico opzionale `calendar_id` e `merge_mode=skip` o `update`. La creazione di un calendario anziché l'importazione in uno esistente è soggetta alle autorizzazioni e alle impostazioni correnti. L'esportazione ICS scarica il calendario; quel percorso non ha filtri `from/to`. Utilizza l'elenco eventi per filtrare l'intervallo di date.

Questo REST API non prevede operazioni separate di invito, risposta dei partecipanti o approvazione della prenotazione. L'aggiunta di tali campi a un evento normale POST non richiama il flusso di lavoro della riunione. [CalDAV e i protocolli ICS](api-granice-protokola_it.md) sono separati dal Bearer REST API.

<a id="operations-and-verified-examples"></a>

## Operazioni ed esempi verificati

### `GET /api/v1/calendars`

**Scopo:** Restituisce i calendari visibili al proprietario della chiave API.

**Autorizzazione:** `calendar:read`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Parametri di query:** `page[after]`, `page[limit]`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars?page[limit]=1"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Sintetički raspored",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": true,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    }
  ],
  "meta": {
    "request_id": "e1fc33cb335d26e476aea1056e00b0a2",
    "page": {
      "limit": 1,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars?page%5Blimit%5D=1",
    "next": null
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/calendars`

**Scopo:** Crea un nuovo calendario con controlli delle autorizzazioni del dominio.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "name": "API radionice",
  "type": "team",
  "description": "Sintetički raspored",
  "color": "#1677ff",
  "is_enabled": true,
  "is_public_read": false,
  "is_authenticated_read": false
}' \
  "$BASE_URL/api/v1/calendars"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "070d314efe35cc19720e469157dac2f9"
  },
  "links": {
    "self": "/api/v1/calendars"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/calendars/import`

**Scopo:** Importa il testo di iCalendar in un calendario esistente o nuovo.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `calendar_id`, `calendar_type`, `color`, `description`, `ics`, `is_authenticated_read`, `is_public_read`, `merge_mode`, `name`, `public_order`, `show_on_public_index`, `show_public_link`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "calendar_id": 1,
  "merge_mode": "skip",
  "ics": "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Simbioza API manual//EN\r\nBEGIN:VEVENT\r\nUID:api-manual-import@example.invalid\r\nDTSTAMP:20261106T100000Z\r\nDTSTART:20261112T090000Z\r\nDTEND:20261112T100000Z\r\nSUMMARY:Imported API event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
}' \
  "$BASE_URL/api/v1/calendars/import"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "calendar": {
      "id": 1,
      "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
      "slug": "api-radionice",
      "name": "API radionice",
      "description": "Ažurirano API rasporedom.",
      "type": "team",
      "owner_user_id": null,
      "color": "#1677FF",
      "is_enabled": true,
      "is_public_read": false,
      "is_authenticated_read": false,
      "can_write": false,
      "created_at": "2026-11-06 23:08:57",
      "updated_at": "2026-11-06 23:08:57"
    },
    "created": 1,
    "updated": 0,
    "skipped": 0,
    "total": 1
  },
  "meta": {
    "request_id": "5e6f724385a764a7bbb5089da3bc8f01"
  },
  "links": {
    "self": "/api/v1/calendars/import"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/calendars/{calendarUuid}`

**Scopo:** Elimina un calendario quando il suo dominio ACL lo consente.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "If-Match: $ETAG" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/calendars/{calendarUuid}`

**Scopo:** recupera un calendario tramite il suo UUID pubblico.

**Autorizzazione:** `calendar:read`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Sintetički raspored",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": true,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "1386cd671b7e38072e364bc34947d19c"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Errori verificati per questa operazione:** 401, 403, 404. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/calendars/{calendarUuid}`

**Scopo:** Aggiorna parzialmente un calendario.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `access_rules`, `acl`, `acl_group_id`, `acl_group_read`, `acl_group_write`, `acl_user_id`, `acl_user_read`, `acl_user_write`, `calendar_type`, `color`, `description`, `is_authenticated_read`, `is_enabled`, `is_public_read`, `name`, `owner_user_id`, `personal_preset`, `personal_suffix`, `public_order`, `show_on_public_index`, `show_public_link`, `type`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

**Prima di scrivere:** ripeti la richiesta GET e imposta `ETAG` dalla relativa intestazione `ETag`.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --header "If-Match: $ETAG" \
  --data-raw '{
  "description": "Ažurirano API rasporedom."
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "48783b78a64fa19566ee89854bffdb99"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753"
  }
}
```

**Errori verificati per questa operazione:** 400, 401, 403, 412, 428. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PUT /api/v1/calendars/{calendarUuid}/acl`

**Scopo:** Sostituisce tutte le regole ACL utente/gruppo di un calendario.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `rules`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PUT \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "rules": [
    {
      "subject_type": "user",
      "subject_id": 2,
      "can_read": true,
      "can_write": true
    }
  ]
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uuid": "e1273d61-ad5e-4edf-90d5-b7d1193bc753",
    "slug": "api-radionice",
    "name": "API radionice",
    "description": "Ažurirano API rasporedom.",
    "type": "team",
    "owner_user_id": null,
    "color": "#1677FF",
    "is_enabled": true,
    "is_public_read": false,
    "is_authenticated_read": false,
    "can_write": false,
    "created_at": "2026-11-06 23:08:57",
    "updated_at": "2026-11-06 23:08:57"
  },
  "meta": {
    "request_id": "dc07257329738af268ffc971c7195390"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/acl"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/calendars/{calendarUuid}/events`

**Scopo:** Restituisce gli eventi del calendario all'interno dell'intervallo richiesto.

**Autorizzazione:** `calendar:read`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri query:** `expand_recurring`, `from`, `page[after]`, `page[limit]`, `to`.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": [
    {
      "id": 1,
      "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
      "calendar_id": 1,
      "title": "API radionica",
      "description": "Provjera stvarnih događaja",
      "location": "Dvorana A",
      "starts_at": "2026-11-10 10:00:00",
      "ends_at": "2026-11-10 11:00:00",
      "is_all_day": false,
      "timezone": "Europe/Zagreb",
      "recurrence_rule": "",
      "instance_key": "1",
      "is_recurring": false,
      "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
    }
  ],
  "meta": {
    "request_id": "5095e41c647508f3f3c0dbd83e2a9e60",
    "page": {
      "limit": 50,
      "total": 1,
      "has_more": false,
      "next_cursor": null
    }
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events?from=2026-11-01&to=2026-12-01&expand_recurring=false",
    "next": null
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `POST /api/v1/calendars/{calendarUuid}/events`

**Scopo:** Crea un evento in un calendario.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request POST \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica",
  "description": "Provjera stvarnih događaja",
  "location": "Dvorana A",
  "starts_at": "2026-11-10T10:00:00",
  "ends_at": "2026-11-10T11:00:00",
  "timezone": "Europe/Zagreb"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
```

Risposta effettiva: **HTTP 201**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "f4ea6360950cf37c84849ba4b9425892"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `DELETE /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Scopo:** Elimina un evento da un calendario.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`, `eventId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request DELETE \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Risposta effettiva: **HTTP 204**. Il corpo è vuoto.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Scopo:** Recupera un evento dal calendario indicato nell'URL.

**Autorizzazione:** `calendar:read`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`, `eventId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "0bb588ef723efad8ed331552cf1a48477eaeebc5"
  },
  "meta": {
    "request_id": "fb8d07b66a9f22a0af23e3ec01c284d7"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `PATCH /api/v1/calendars/{calendarUuid}/events/{eventId}`

**Scopo:** Aggiorna parzialmente un evento.

**Autorizzazione:** `calendar:write`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`, `eventId`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo/campi supportati:** `all_day`, `description`, `end`, `ends`, `ends_at`, `ends_on`, `ends_time`, `event_type`, `event_type_id`, `icalendar`, `is_all_day`, `location`, `new_event_type_icon`, `new_event_type_name`, `recurrence_count`, `recurrence_end`, `recurrence_frequency`, `recurrence_rule`, `recurrence_until`, `start`, `starts`, `starts_at`, `starts_on`, `starts_time`, `timezone`, `title`, `type_id`, `uid`. L'esempio seguente utilizza valori verificati; le condizioni e le regole di sostituzione delle liste sono descritte all'inizio del capitolo.

```sh
curl --globoff --silent --show-error \
  --request PATCH \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --header "Content-Type: application/json" \
  --data-raw '{
  "title": "API radionica — ažurirano"
}' \
  "$BASE_URL/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
```

Risposta effettiva: **HTTP 200**.

```json
{
  "data": {
    "id": 1,
    "uid": "d236344142cbf824faae2cdb83b48864@heartphrame-calendar",
    "calendar_id": 1,
    "title": "API radionica — ažurirano",
    "description": "Provjera stvarnih događaja",
    "location": "Dvorana A",
    "starts_at": "2026-11-10 10:00:00",
    "ends_at": "2026-11-10 11:00:00",
    "is_all_day": false,
    "timezone": "Europe/Zagreb",
    "recurrence_rule": "",
    "instance_key": "",
    "is_recurring": false,
    "etag": "e4b119585f634b8b131a717ab7fdbaefcc129dcf"
  },
  "meta": {
    "request_id": "4aa9f46489ec917f0e3db3fcb19d348f"
  },
  "links": {
    "self": "/api/v1/calendars/e1273d61-ad5e-4edf-90d5-b7d1193bc753/events/1"
  }
}
```

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.

### `GET /api/v1/calendars/{calendarUuid}/export.ics`

**Scopo:** Scarica un calendario leggibile come file ICS standard.

**Autorizzazione:** `calendar:read`. Proprietà corrente del calendario, autorizzazioni di lettura/scrittura/gestione e ACL di utenti/gruppi.

**Percorso:** `calendarUuid`. Prendi i valori dalla risposta precedente. Un ID di pagina è la chiave del documento; nodeId è numerico e UUID non è uno slug.

**Parametri di query:** nessuno.

**Corpo:** nessun campo di input aggiuntivo; segui l'esempio.

```sh
curl --globoff --silent --show-error \
  --request GET \
  --header "Authorization: Bearer $SIMBIOZA_API_KEY" \
  --dump-header response-headers.txt \
  --output response.ics \
  "$BASE_URL/api/v1/calendars/e8eaf356-1af4-4a05-8943-263752ef910b/export.ics"
```

Risposta effettiva: **HTTP 200**.

```text
content-type: text/calendar; charset=utf-8
content-disposition: attachment; filename="administrator-personal-calendar.ics"
content-length: 189
```

Il file viene salvato come `response.*`. Il contenuto verificato ha 189 byte; non decodificarlo come JSON.

**Errori verificati per questa operazione:** 401, 403. Consulta [regole condivise](api-pocetak_it.md) per i significati dello stato e le risposte ai problemi.
