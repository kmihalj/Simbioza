# API — granice, CalDAV i ICS

[← API — sadržaj](api-koristenje_hr.md)

Ovo poglavlje razdvaja javni REST API od ostalih načina povezivanja. OpenAPI prikazuje samo registrirane `/api/v1` rute; nije popis svih web stranica i protokola Simbioze.

## Što nema zasebne REST rute

- Menu i Theme nemaju globalni REST API postavki; postoje rute privatne teme određenog područja.
- Backup nema REST izvoz/povrat cijelog sitea ili područja. HTML ZIP izvoz je drugi format i nije backup.
- ORM, Email i Accessibility ne izlažu zasebne javne REST resurse.
- Comment nema REST proširenje za stvaranje/brisanje komentara.
- Task API mijenja stanje postojećih zadataka, a definicije su dio sadržaja Editor modula.
- Survey API ne prima predaje ispitanika i ne zamjenjuje vanjsku prijavu.
- Calendar REST nema pozive/odgovore sudionika i odobravanje rezervacija.
- Administratorske web POST rute nisu alternativa REST API-ju: koriste sesiju, CSRF i svoje ovlasti.

## CalDAV

CalDAV je odvojeni protokol pod `/caldav`, s HTTP Basic prijavom lokalnim identifikatorom i lokalnom lozinkom. API Bearer ključ nije CalDAV vjerodajnica. Koristite HTTPS. Račun koji se prijavljuje samo SSO-om mora imati postavljenu lokalnu lozinku za CalDAV; to samo po sebi ne uključuje lokalnu web prijavu. Opis povezivanja klijenata: [kalendari — povezivanje](kalendari-povezivanje_hr.md).

Podržana je osnovna sinkronizacija: OPTIONS/HEAD probe, PROPFIND za korijen, principal i kolekciju, REPORT za kalendar te GET/PUT/DELETE za `/{uid}.ics`. PUT/DELETE zahtijevaju aktualno pravo pisanja u taj kalendar. URL objektne datoteke prati UID i mora biti ispravno URL-kodiran. Cijeli CalDAV Scheduling/iTIP tijek nije implementiran; na pozive sastanaka odgovara se u web sučelju.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Stvarni rezultat: HTTP 207, `application/xml; charset=utf-8`; XML popis sadrži samo dostupne kalendare. Bez Basic prijave i s običnim Bearer ključem isti PROPFIND vraća 401. Sljedeće metode dodatno su provjerene stvarnim curl zahtjevima:

| Metoda | Putanja | Rezultat |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

## ICS poveznice i izvoz

REST `/api/v1/calendars/{calendarUuid}/export.ics` zahtijeva Bearer ključ i `calendar:read`. Javna/privatna pretplatna ICS poveznica iz web sučelja zaseban je ugovor s vlastitim pravima ili tokenom; nemojte u nju dodavati API ključ. Javna ICS pretplata je za čitanje, ne za slanje izmjena. Poveznice za pretplatu uzmite iz vlastitog kalendara; ne sastavljajte ih iz tuđih testnih URL-ova.
