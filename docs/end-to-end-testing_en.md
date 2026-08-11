# End-to-end testing

Simbioza has a browser and HTTP API end-to-end suite for the assembled
application. Unlike a module unit test, it installs the latest `dev-main`
Framework and module heads into a new temporary project, runs all official
migrations against a new SQLite database by default, starts a real HTTP server,
and drives Chromium through Playwright. The same runner accepts an explicitly
prepared empty PostgreSQL, MySQL, or MariaDB database for a complete
cross-driver run.

The suite never uses `config/database.php` from your working application. With
SQLite, test users, the Bearer key, Workspace records, sessions, logs, and
caches exist below the operating system's temporary
`heartphrame-clean-matrix` directory. For a network driver, credentials are
read only from `HPH_MATRIX_DB_*` and the operator must provide a dedicated empty
disposable database. The runner removes the temporary project but never creates
or drops a network database; explicit database cleanup remains with the
operator.

The Workspace, page, draft, and published versions created by the browser test
are synthetic fixtures, not starter or demonstration content. They are never
copied into Simbioza, a module package, or an administrator's installation.

## What is covered

All 42 scenarios cover every module shipped by Simbioza. They exercise public
behavior rather than private implementation details.

| Area | End-to-end coverage |
|---|---|
| Clean host and ORM | A new application, SQLite/PostgreSQL/MySQL database, all official migrations, real front controller, sessions, logs, cache directories, and teardown safety. |
| Theme and Menu | Desktop/mobile navigation, right-side mobile drawer, locale persistence, menu save, full-height responsive hero artwork, adaptive collision-free mobile content overlap, equal Home/Inner sizes, edge-to-edge layout, supporting copy beneath automatic hero titles using the Hero color settings, collision-free narrow live preview, theme clone, used-assets-only package export, complete theme export, deletion, and complete-theme import. |
| Auth | Guest redirect, administrator and regular-user authorization, local login/logout, profile and notification preference updates, reversible password change, group/user CRUD, memberships, ETags, safe output, audit records, and cleanup. |
| API | Bearer authentication, dynamic scopes, discovery, raw OpenAPI 3.1, CORS preflight, pagination, RFC 9457 problems, rate-limit headers, idempotent replay, `If-Match`, personal key request, administrator approval, one-time reveal, and scope/domain-permission separation. |
| Workspace | Creation, concealed unauthorized reads, subject search, workspace and node ACLs, tree links, complete ordering, updates, node deletion, soft deletion, deleted list, restore, twelve-line Summaries, language fallback, collapsible tree/display controls, and structured Summaries homepage targets. |
| HTML Editor | Structured draft creation, concurrent revision rejection, review, publisher boundary, publication, immutable versions, rendered output, translations, version restore, draft discard, page deletion, and public Workspace route removal. |
| Attachments | Standard multipart upload, rejection of unsupported upload idempotency, chunk upload and cancellation, visibility, listing, metadata update, byte-for-byte download, and deletion. |
| Task | Discovery from versioned document content, ETag-protected state change, idempotent replay, and one-entry state history. |
| Notification | Review and comment-report domain notifications, API inbox/read/read-all behavior, and the authenticated notification screen. |
| Comment | Comment creation on a real published page, reaction, inappropriate-content report, administrator notification, and moderator deletion. |
| Calendar and CalDAV | Calendar ACL, event CRUD, required ranges, ICS export, ETags, well-known discovery, `HEAD`, `OPTIONS`, principal/collection `PROPFIND`, `REPORT`, and calendar-object `PUT`/`GET`/`DELETE`. |
| Webhooks | Subscription ownership, one-time secret, ETag update, secret rotation, a delivery created by a real domain mutation, delivery inspection/retry, and protected deletion. |
| Email | Settings persistence, queueing through the real outbox, an immediate attempt against an intentionally unavailable local SMTP endpoint, and observable terminal failure without external delivery. |

Negative paths are intentional coverage: concealed `404` responses, `403`
permission failures, invalid keys, missing or stale preconditions, invalid
ranges, and unsupported upload idempotency must remain stable contracts.

## Performance budgets

The E2E server registers the ORM query observer only for the isolated test run.
It writes `build/e2e-query-log.jsonl` for SQLite or a driver-suffixed file such
as `build/e2e-query-log-mysql.jsonl`, and never records SQL bindings, API
tokens, request query strings, or response bodies. Normal application requests
do not enable this observer.

The same isolated run writes `build/e2e-request-log.jsonl`, or its corresponding
driver-suffixed file, with the method, path without its query string, status,
duration, memory use, response-body byte count, and content type. It does not
record headers, cookies, request bodies, or response bodies. Query and request
records are buffered and flushed once per request so the profiler does not
distort the measured hot path with per-query file writes.

The final scenario marks representative Home, current-user, user-list,
Workspace-list, Calendar-list, and Notification-list requests. It enforces a
query-count, request-duration, peak-memory, and response-size budgets for each
request. It also rejects repeated schema discovery, repeated Auth
provider-setting reads, Auth-group repair writes, and unexpected API-key usage
writes. This turns measured optimizations into regression contracts instead of
relying on a one-time benchmark.

To inspect the most expensive requests after a run:

```bash
jq -s 'group_by(.request_id) | map({path:.[0].path, queries:length}) | sort_by(.queries) | reverse | .[:20]' \
  build/e2e-query-log.jsonl

jq -s 'sort_by(.duration_ms) | reverse | .[:20] | map({path,duration_ms,peak_memory_bytes,response_bytes})' \
  build/e2e-request-log.jsonl
```

## First run

Install the latest Playwright test package without creating a lock file, then
install Chromium:

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

For a real MySQL or PostgreSQL run, create a dedicated empty test database and
provide its connection only through the process environment:

```bash
HPH_MATRIX_DB_HOST=127.0.0.1 \
HPH_MATRIX_DB_PORT=3306 \
HPH_MATRIX_DB_NAME=heartphrame_e2e \
HPH_MATRIX_DB_USER=heartphrame_e2e \
HPH_MATRIX_DB_PASSWORD='local-test-secret' \
php scripts/run_e2e.php --local --database=mysql
```

Use `--database=pgsql` and port `5432` for PostgreSQL. The database must be
empty before every run. Do not use a production schema or production account.
Avoid `--keep` with a network driver unless retaining its temporary connection
configuration for diagnostics is intentional.

The application and internal packages intentionally follow moving `dev-main`
heads. Therefore `package-lock.json` and `composer.lock` are not committed.

## Test local module edits

The default command resolves remote `dev-main` package heads, matching CI. To
test the allowed sibling module checkouts before pushing them:

```bash
composer e2e -- --local
```

The local mode uses path repositories only for the modules that belong to this
project. It never substitutes the upstream Framework or Demo module.

The local run is the required check whenever two or more sibling modules are
changed together. The default run must then pass as well after those module
commits are available on their remote `dev-main` branches.

## Debug a failure

Run a visible browser and retain the isolated application:

```bash
composer e2e -- --local --headed --keep
```

The runner prints the retained project path. Playwright traces, screenshots,
videos, and the HTML report are written below `build/`; the PHP server output is
in `build/e2e-server.log`, and non-sensitive query measurements are in
`build/e2e-query-log.jsonl`. Network-database runs append the selected driver,
for example `build/e2e-server-mysql.log`, `build/e2e-query-log-mysql.jsonl`, and
`build/e2e-request-log-mysql.jsonl`. All these paths are ignored by Git. Remove
a retained project after inspection or rerun without `--keep`.

## CI

GitHub Actions installs the latest Node.js, resolves the latest npm package,
installs Chromium with its Linux dependencies, and runs `composer e2e`. A test
failure retains the Playwright report, traces, screenshots, videos, and server
and performance logs as a downloadable CI artifact. A separate job runs the
same complete suite on clean PostgreSQL and MySQL databases.

The browser suite is deliberately a separate job from PHP unit and static
analysis jobs. This makes it clear whether a failure belongs to an isolated
module, clean installation, network database, or assembled browser/API flow.
