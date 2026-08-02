import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { apiHeaders, e2eEnvironment } from './helpers.js';

const { adminApiToken } = e2eEnvironment();
const queryLogPath = process.env.HPH_E2E_QUERY_LOG;

if (!queryLogPath) {
  throw new Error('HPH_E2E_QUERY_LOG is required. Run the suite through composer e2e.');
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
 * HR: Izvršava jedan GET i provjerava njegov trajni SQL budžet.
 * EN: Executes one GET request and verifies its durable SQL budget.
 */
async function expectQueryBudget(request, name, path, budget, authenticated = true) {
  const marker = `budget-${name}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const headers = authenticated
    ? apiHeaders(adminApiToken, { 'X-HPH-Performance-Run': marker })
    : { 'X-HPH-Performance-Run': marker };
  const response = await request.get(path, { headers });

  expect(response.status(), `${name} returned an unexpected status`).toBe(200);

  const events = queryEvents(marker);
  const sql = events.map((event) => event.sql);
  expect(events.length, `${name} exceeded its SQL query budget`).toBeLessThanOrEqual(budget);
  expect(
    sql.filter((statement) => statement.includes('information_schema.tables')
      || statement.includes('sqlite_master WHERE type = ?')).length,
    `${name} repeated schema-table discovery`,
  ).toBeLessThanOrEqual(1);
  expect(
    sql.filter((statement) => statement.includes('FROM "auth_provider_settings"')).length,
    `${name} repeated Auth provider-settings reads`,
  ).toBeLessThanOrEqual(1);
  expect(
    sql.some((statement) => statement.startsWith('UPDATE "auth_groups"')),
    `${name} wrote Auth groups during a read request`,
  ).toBe(false);
}

test('representative read paths remain inside measured SQL budgets', async ({ request }) => {
  await expectQueryBudget(request, 'home', '/', 12, false);
  await expectQueryBudget(request, 'current-user', '/api/v1/me', 16);
  await expectQueryBudget(request, 'users', '/api/v1/users?page[limit]=20', 30);
  await expectQueryBudget(request, 'workspaces', '/api/v1/workspaces?page[limit]=20', 24);
  await expectQueryBudget(request, 'calendars', '/api/v1/calendars?page[limit]=20', 24);
  await expectQueryBudget(request, 'notifications', '/api/v1/notifications?page[limit]=20', 20);
});
