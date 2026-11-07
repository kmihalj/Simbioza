# API — limiti, CalDAV e ICS

<a id="api--boundaries-caldav-and-ics"></a>


> Le schermate mostrano l’interfaccia in inglese.

[← API — indice](api-koristenje_it.md)

Questo capitolo distingue il REST pubblico API da altri meccanismi di integrazione. OpenAPI elenca le rotte `/api/v1` registrate; non è un inventario di ogni pagina Web o protocollo Simbioza.

<a id="features-without-a-separate-rest-endpoint"></a>

## Funzionalità senza un endpoint REST separato

- Menu e Theme non hanno API REST per le impostazioni globali; esistono invece route per il tema privato dell'area di lavoro.
- Backup non ha endpoint REST per il backup o il ripristino di siti e aree di lavoro. L'esportazione HTML ZIP è un formato diverso, non una copia di sicurezza.
- ORM, e-mail e accessibilità non espongono risorse REST pubbliche separate.
- Comment non ha estensione REST per creare/eliminare commenti.
- Le API Task modificano lo stato delle attività esistenti; le definizioni appartengono al contenuto dell'editor.
- Le API Survey non accettano l'invio di risposte da parte dei partecipanti e non sostituiscono l'accesso esterno.
- Il REST del calendario non espone inviti/risposte dei partecipanti o approvazioni delle prenotazioni.
- I percorsi web amministrativi POST non sono un sostituto REST: utilizzano sessioni, CSRF e le proprie autorizzazioni.

## CalDAV

CalDAV è un protocollo separato in `/caldav`, che utilizza HTTP Basic con un identificatore di accesso locale e una password locale. Una chiave Bearer API non è una credenziale CalDAV. Utilizza HTTPS. Un utente solo SSO necessita di una password locale per CalDAV; l'impostazione di per sé non abilita l'accesso Web locale. Vedere [connessione dei client di calendario](kalendari-povezivanje_it.md).

La sincronizzazione di base supporta i probe OPTIONS/HEAD, PROPFIND per root/principal/collection, REPORT per un calendario e GET/PUT/DELETE per `/{uid}.ics`. PUT/DELETE richiedono l'accesso in scrittura al calendario corrente. Il nome file dell'oggetto segue il relativo UID e deve essere codificato in URL. La pianificazione CalDAV Scheduling/iTIP completa non è implementata; gli inviti alle riunioni ricevono risposta tramite l'interfaccia web.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Risultato effettivo: HTTP 207, `application/xml; charset=utf-8`; l'elenco XML include solo i calendari accessibili. Senza l'autenticazione Basic, o con una normale chiave Bearer, lo stesso PROPFIND restituisce 401. Questi metodi sono stati inoltre verificati con richieste di curl reali:

| Metodo | Sentiero | Risultato |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

<a id="ics-links-and-export"></a>

## Collegamenti ICS ed esportazione

REST `/api/v1/calendars/{calendarUuid}/export.ics` richiede una chiave Bearer e `calendar:read`. I collegamenti ICS di abbonamento pubblico/privato dall'interfaccia web costituiscono un contratto separato con le proprie regole o token di accesso; non aggiungere una chiave API a tali URL. Una sottoscrizione ICS pubblica è di sola lettura. Ottieni collegamenti di abbonamento dal tuo calendario invece di crearli dagli URL di prova di un'altra installazione.
