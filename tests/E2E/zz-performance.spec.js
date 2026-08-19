import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  getDataWithEtag,
  idempotencyKey,
} from './helpers.js';

const { adminApiToken } = e2eEnvironment();
const queryLogPath = process.env.HPH_E2E_QUERY_LOG;
const requestLogPath = process.env.HPH_E2E_REQUEST_LOG;

if (!queryLogPath || !requestLogPath) {
  throw new Error('HPH_E2E_QUERY_LOG and HPH_E2E_REQUEST_LOG are required. Run the suite through composer e2e.');
}

/**
 * HR: Vraća SQL događaje označenog HTTP zahtjeva bez bind vrijednosti.
 * EN: Returns SQL events for one marked HTTP request without binding values.
 */
function queryEvents(marker) {
  return readFileSync(queryLogPath, 'utf8')
    .split('\n')
    .filter(Boolean)
    .map((line) => JSON.parse(line))
    .filter((event) => event.request_id === marker);
}

/**
 * HR: Vraća HTTP metriku označenog zahtjeva.
 * EN: Returns the HTTP metric for one marked request.
 */
function requestEvent(marker) {
  const events = readFileSync(requestLogPath, 'utf8')
    .split('\n')
    .filter(Boolean)
    .map((line) => JSON.parse(line))
    .filter((event) => event.request_id === marker);

  expect(events, `${marker} did not produce exactly one HTTP metric`).toHaveLength(1);
  return events[0];
}

/**
 * HR: Provjerava zajednički SQL budžet i zabrane regresija označenog zahtjeva.
 * EN: Verifies the shared SQL budget and regression guards for a marked request.
 */
function expectRecordedBudget(name, marker, budget) {
  const events = queryEvents(marker);
  const sql = events.map((event) => event.sql.replaceAll('`', '"'));
  const lowerSql = sql.map((statement) => statement.toLowerCase());
  expect(events.length, `${name} exceeded its SQL query budget`).toBeLessThanOrEqual(budget);
    expect(
      lowerSql.filter((statement) => statement.includes('information_schema.tables')
        || statement.includes('sqlite_master where type = ?')).length,
      `${name} repeated schema-table discovery`,
    ).toBeLessThanOrEqual(2);
    expect(
      lowerSql.filter((statement) => statement.includes('from "auth_provider_settings"')).length,
      `${name} repeated Auth provider-settings reads`,
    ).toBeLessThanOrEqual(2);
  expect(
    lowerSql.some((statement) => statement.startsWith('update "auth_groups"')),
    `${name} triggered an unexpected Auth group repair write`,
  ).toBe(false);
  expect(
    lowerSql.some((statement) => statement.startsWith('update "auth_api_keys" set')),
    `${name} triggered an unexpected API-key usage write`,
  ).toBe(false);
    expect(
      lowerSql.filter((statement) => statement.includes('from "workspace_acl"')).length,
      `${name} repeated Workspace ACL reads`,
    ).toBeLessThanOrEqual(2);
  expect(
    lowerSql.filter((statement) => statement.startsWith('insert into "auth_user_provider_access"')
      || statement.startsWith('update "auth_user_provider_access"')).length,
    `${name} repeated Auth provider-access writes`,
  ).toBeLessThanOrEqual(1);
}

/**
 * HR: Provjerava trajanje, vršnu memoriju i veličinu tijela odgovora.
 * EN: Verifies duration, peak memory, and response-body size budgets.
 */
function expectRequestBudget(name, marker, budget) {
  const event = requestEvent(marker);
  expect(event.duration_ms, `${name} exceeded its request-duration budget`)
    .toBeLessThanOrEqual(budget.durationMs);
  expect(event.peak_memory_bytes, `${name} exceeded its peak-memory budget`)
    .toBeLessThanOrEqual(budget.peakMemoryBytes);
  expect(event.response_bytes, `${name} exceeded its response-size budget`)
    .toBeLessThanOrEqual(budget.responseBytes);
}

/**
 * HR: Izvršava jedan GET i provjerava njegov trajni SQL budžet.
 * EN: Executes one GET request and verifies its durable SQL budget.
 */
async function expectQueryBudget(
  request,
  name,
  path,
  queryBudget,
  requestBudget,
  authenticated = true,
) {
  const marker = `budget-${name}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const headers = authenticated
    ? apiHeaders(adminApiToken, { 'X-HPH-Performance-Run': marker })
    : { 'X-HPH-Performance-Run': marker };
  const response = await request.get(path, { headers });

  expect(response.status(), `${name} returned an unexpected status`).toBe(200);
  expectRecordedBudget(name, marker, queryBudget);
  expectRequestBudget(name, marker, requestBudget);
}

test('representative read paths remain inside measured SQL budgets', async ({ request }) => {
  const apiReadBudget = { durationMs: 500, peakMemoryBytes: 32 * 1024 * 1024, responseBytes: 32 * 1024 };
  /*
   * HR: Prethodni backup/restore scenarij namjerno vraća i audit vremena
   *     uporabe API ključa. Jedan neoznačeni zahtjev osvježava taj audit kako
   *     bi mjerenje provjeravalo stabilno stanje, a ne jednokratno auditno
   *     pisanje nakon stvarnog restorea.
   * EN: The preceding backup/restore scenario intentionally restores the API
   *     key usage audit timestamp. One unmarked request refreshes that audit
   *     so the measurement checks steady state rather than the one-time audit
   *     write following a real restore.
   */
  const warmup = await request.get('/api/v1/me', { headers: apiHeaders(adminApiToken) });
  expect(warmup.status()).toBe(200);
    await expectQueryBudget(
      request,
      'home',
      '/',
      70,
      { durationMs: 500, peakMemoryBytes: 32 * 1024 * 1024, responseBytes: 35 * 1024 },
      false,
    );
  await expectQueryBudget(request, 'current-user', '/api/v1/me', 14, apiReadBudget);
  await expectQueryBudget(request, 'users', '/api/v1/users?page[limit]=20', 20, apiReadBudget);
  await expectQueryBudget(request, 'workspaces', '/api/v1/workspaces?page[limit]=20', 16, apiReadBudget);
  await expectQueryBudget(request, 'calendars', '/api/v1/calendars?page[limit]=20', 16, apiReadBudget);
  await expectQueryBudget(request, 'notifications', '/api/v1/notifications?page[limit]=20', 24, apiReadBudget);
});

test('Auth create and update mutations remain inside measured SQL budgets', async ({ request }) => {
  const suffix = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const loginIdentifier = `performance-${suffix}@example.invalid`;
  const createMarker = `budget-auth-create-${suffix}`;
  const createdResponse = await request.post('/api/v1/users', {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('performance-auth-create'),
      'X-HPH-Performance-Run': createMarker,
    }),
    data: {
      login_identifier: loginIdentifier,
      password: 'PerformanceUser!2026',
      is_active: true,
      is_admin: false,
      provider_access: { local: true },
      attributes: {
        display_name: 'Performance User',
        email: loginIdentifier,
      },
    },
  });
  const created = await expectData(createdResponse, 201);
  expectRecordedBudget('auth-create', createMarker, 45);
  expectRequestBudget('auth-create', createMarker, {
    durationMs: 2_000,
    peakMemoryBytes: 32 * 1024 * 1024,
    responseBytes: 16 * 1024,
  });

  const current = await getDataWithEtag(request, `/api/v1/users/${created.id}`, apiHeaders(adminApiToken));
  const updateMarker = `budget-auth-update-${suffix}`;
  const updatedResponse = await request.patch(`/api/v1/users/${created.id}`, {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('performance-auth-update'),
      'If-Match': current.etag,
      'X-HPH-Performance-Run': updateMarker,
    }),
    data: { attributes: { display_name: 'Updated Performance User' } },
  });
  expect((await expectData(updatedResponse)).display_name).toBe('Updated Performance User');
  expectRecordedBudget('auth-update', updateMarker, 40);
  expect(
    queryEvents(updateMarker)
      .map((event) => event.sql.replaceAll('`', '"').toLowerCase())
      .filter((statement) => statement.startsWith('insert into "auth_user_provider_access"')
        || statement.startsWith('update "auth_user_provider_access"')),
    'auth-update rewrote an unchanged provider-access matrix',
  ).toHaveLength(0);
  expectRequestBudget('auth-update', updateMarker, {
    durationMs: 750,
    peakMemoryBytes: 32 * 1024 * 1024,
    responseBytes: 16 * 1024,
  });
});

test('page creation, publication, and public rendering stay inside measured SQL budgets', async ({ request }) => {
  const suffix = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const workspaceSlug = `performance-${suffix}`;
  const pageSlug = `performance-page-${suffix}`;
  await expectData(await request.post('/api/v1/workspaces', {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('performance-workspace-create'),
    }),
    data: {
      name: 'Performance Workspace',
      slug: workspaceSlug,
      description: 'Ephemeral SQL performance scenario.',
      visibility: 'public',
    },
  }), 201);

  const createMarker = `budget-page-create-${suffix}`;
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('performance-page-create'),
      'X-HPH-Performance-Run': createMarker,
    }),
    data: {
      title: 'Performance Page',
      slug: pageSlug,
      workspace_slug: workspaceSlug,
      language: 'en',
      content: [{ type: 'html', html: '<h1>Performance Page</h1><p>Measured content.</p>' }],
    },
  }), 201);
  /*
   * HR: Dva odvojena audit INSERT-a trajno bilježe nastanak HTML dokumenta i
   *     njegovo vezanje u stablo područja; generički HTTP duplikat se preskače.
   * EN: Two separate audit INSERTs durably record creation of the HTML document
   *     and its Workspace-tree binding; the generic HTTP duplicate is skipped.
   */
  expectRecordedBudget('page-create', createMarker, 70);
  expectRequestBudget('page-create', createMarker, {
    durationMs: 1_000,
    peakMemoryBytes: 32 * 1024 * 1024,
    responseBytes: 16 * 1024,
  });

  const draft = await getDataWithEtag(
    request,
    `/api/v1/pages/${pageSlug}/draft?lang=en`,
    apiHeaders(adminApiToken),
  );
  const publishMarker = `budget-page-publish-${suffix}`;
  await expectData(await request.post(`/api/v1/pages/${pageSlug}/publish?lang=en`, {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('performance-page-publish'),
      'If-Match': draft.etag,
      'X-HPH-Performance-Run': publishMarker,
    }),
    data: {},
  }));
  /*
   * HR: Dodatnih upita sinkronizira objavljeni čvor i jezik u Workspace Search
   *     bez skeniranja cijelog područja.
   * EN: Additional bounded queries synchronize the published node and language
   *     into Workspace Search without scanning the complete Workspace.
   */
  expectRecordedBudget('page-publish', publishMarker, 72);
  expectRequestBudget('page-publish', publishMarker, {
    durationMs: 1_000,
    peakMemoryBytes: 32 * 1024 * 1024,
    responseBytes: 16 * 1024,
  });

  const publicMarker = `budget-page-public-${suffix}`;
  const publicResponse = await request.get(`/workspace/${workspaceSlug}/${pageSlug}`, {
    headers: { 'X-HPH-Performance-Run': publicMarker },
  });
  expect(publicResponse.status()).toBe(200);
  expectRecordedBudget('page-public', publicMarker, 35);
  expectRequestBudget('page-public', publicMarker, {
    durationMs: 1_000,
    peakMemoryBytes: 32 * 1024 * 1024,
    responseBytes: 64 * 1024,
  });
});
