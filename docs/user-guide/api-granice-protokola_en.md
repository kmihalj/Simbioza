# API — boundaries, CalDAV and ICS

[← API — contents](api-koristenje_en.md)

This chapter distinguishes the public REST API from other integration mechanisms. OpenAPI lists registered `/api/v1` routes; it is not an inventory of every Simbioza web page or protocol.

## Features without a separate REST endpoint

- Menu and Theme have no global settings REST API; private workspace-theme routes do exist.
- Backup has no REST site/workspace backup or restore endpoint. HTML ZIP export is a different format, not a backup.
- ORM, Email and Accessibility expose no separate public REST resources.
- Comment has no REST extension for creating/deleting comments.
- Task API changes existing task state; definitions belong to Editor content.
- Survey API does not accept participant submissions or replace external sign-in.
- Calendar REST exposes no attendee invitations/responses or reservation approvals.
- Administrative web POST routes are not a REST substitute: they use sessions, CSRF and their own permissions.

## CalDAV

CalDAV is a separate protocol under `/caldav`, using HTTP Basic with a local login identifier and local password. An API Bearer key is not a CalDAV credential. Use HTTPS. An SSO-only user needs a local password for CalDAV; setting it does not itself enable local web sign-in. See [connecting calendar clients](kalendari-povezivanje_en.md).

Basic synchronization supports OPTIONS/HEAD probes, PROPFIND for root/principal/collection, REPORT for a calendar, and GET/PUT/DELETE for `/{uid}.ics`. PUT/DELETE require current calendar write access. The object filename follows its UID and must be URL-encoded. Full CalDAV Scheduling/iTIP is not implemented; meeting invitations are answered through the web interface.

```sh
export CALDAV_LOGIN='your-local-login'
read -r -s CALDAV_PASSWORD
export CALDAV_PASSWORD
curl --silent --show-error --request PROPFIND \
  --user "$CALDAV_LOGIN:$CALDAV_PASSWORD" \
  --header "Depth: 1" \
  "$BASE_URL/caldav"
```

Actual result: HTTP 207, `application/xml; charset=utf-8`; the XML list includes only accessible calendars. Without Basic authentication, or with an ordinary Bearer key, the same PROPFIND returns 401. These methods were additionally verified with real curl requests:

| Method | Path | Result |
|---|---|---|
| OPTIONS | `/caldav` | 204 |
| GET | `/.well-known/caldav` | 301 |
| PROPFIND | `/caldav` | 207 |
| PUT | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 201 |
| GET | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 200 |
| REPORT | `/caldav/{calendarSlug}` | 207 |
| DELETE | `/caldav/{calendarSlug}/api-protocol%40example.invalid.ics` | 204 |

## ICS links and export

REST `/api/v1/calendars/{calendarUuid}/export.ics` requires a Bearer key and `calendar:read`. Public/private subscription ICS links from the web interface are a separate contract with their own access rules or tokens; do not add an API key to those URLs. A public ICS subscription is read-only. Obtain subscription links from your own calendar instead of constructing them from another installation’s test URLs.
