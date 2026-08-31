import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  createEditorSurface,
  e2eEnvironment,
  expectData,
  login,
} from './helpers.js';

const {
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
  adminApiToken,
} = e2eEnvironment();

test.describe('separated activity and technical logs', () => {
  test('administrator filters and exports the business audit without exposing the technical log', async ({ page, request }) => {
    /*
     * HR: Najprije stvaramo stvarni gostujući pregled, a zatim isti pregled
     *     prijavljenog administratora. Time regresijski pokrivamo razliku koja
     *     je prije sve opće HTTP zapise pogrešno označavala kao gostujuće.
     * EN: First create a real guest view, then the same view as the signed-in
     *     administrator. This covers the regression that previously marked all
     *     generic HTTP events as guest activity.
     */
    const guestView = await page.goto('/about');
    expect(guestView?.status()).toBe(200);

    await login(page, adminLogin, adminPassword);
    const editorPath = await createEditorSurface(request, adminApiToken, 'audit-editor');
    const editorDocument = new URL(editorPath, 'http://e2e.invalid').searchParams.get('document');
    expect(editorDocument).toBeTruthy();

    /*
     * HR: Običan HTML pregled stvara neutralni poslovni audit događaj kroz
     *     globalni middleware. Ne šaljemo tijela, query parametre ili tajne.
     * EN: A normal HTML view creates a neutral business audit event through
     *     the global middleware. Bodies, query parameters, and secrets are
     *     never submitted.
     */
    for (const path of [
      '/about',
      '/settings',
      '/calendars',
      '/notifications',
      editorPath,
      '/workspaces',
      '/search',
    ]) {
      const viewed = await page.goto(path);
      expect(viewed?.status(), `${path} did not render`).toBe(200);
    }

    /*
     * HR: Reprezentativni pristupi jezgrenim dijelovima moraju imati istoga
     *     prijavljenog izvršitelja, neovisno o modulu koji je renderirao HTML.
     * EN: Representative access to core areas must carry the same signed-in
     *     actor regardless of which module rendered the HTML response.
     */
    const expectedAccess = [
      ['application.view', 'about'],
      ['application.view', 'settings'],
      ['calendar.view', 'calendars'],
      ['notification.view', 'notifications'],
      ['editor-html.view', editorDocument],
      ['workspace.view', 'workspaces'],
      ['workspace-search.view', 'search'],
    ];
    const recentWebEvents = await expectData(await page.request.get(
      '/api/v1/audit?page[limit]=100&channel=web',
      { headers: apiHeaders(adminApiToken) },
    ));
    for (const [eventKey, target] of expectedAccess) {
      const event = recentWebEvents.find((candidate) => candidate.event_key === eventKey
        && (candidate.target_id === target || candidate.target_label === target));
      expect(event, `${eventKey} for ${target} was not audited`).toBeTruthy();
      expect(event.actor_label).toBe('E2E Administrator');
      expect(Number(event.actor_user_id)).toBeGreaterThan(0);
    }

    const audit = await page.goto('/settings/logs/audit');
    expect(audit?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: /Dnevnik aktivnosti|Activity audit/i })).toBeVisible();
    await expect(page.locator('.audit-table')).toContainText('application.view');
    await expect(page.locator('main').getByRole('link', { name: /Tehnički log|Technical log/i }).first()).toBeVisible();

    const applicationViews = page.locator('.audit-record').filter({ hasText: 'application.view' });
    await expect(applicationViews.nth(0)).toContainText('E2E Administrator');
    await expect(applicationViews.filter({ hasText: /Gost|Guest/i }).first()).toBeVisible();

    /*
     * HR: Mobilni audit koristi kartice bez horizontalnog rezanja, dva dovoljno
     *     široka datumska polja i jasno imenovane akcije izvoza.
     * EN: The mobile audit uses unclipped cards, two sufficiently wide date
     *     fields, and clearly named export actions.
     */
    await page.setViewportSize({ width: 390, height: 844 });
    await expect(page.getByRole('link', { name: /Izvezi CSV|Export CSV/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Izvezi NDJSON|Export NDJSON/i })).toBeVisible();
    const fromBox = await page.locator('input[name="from"]').boundingBox();
    const toBox = await page.locator('input[name="to"]').boundingBox();
    expect(fromBox?.width ?? 0).toBeGreaterThan(120);
    expect(toBox?.width ?? 0).toBeGreaterThan(120);
    const firstRecordBox = await page.locator('.audit-record').first().boundingBox();
    expect(firstRecordBox).not.toBeNull();
    expect((firstRecordBox?.x ?? -1) + (firstRecordBox?.width ?? 500)).toBeLessThanOrEqual(390);

    const csv = await page.request.get('/settings/logs/audit/export?format=csv&module=application');
    expect(csv.status()).toBe(200);
    expect(csv.headers()['content-type']).toContain('text/csv');
    expect(await csv.text()).toContain('event_key');

    const ndjson = await page.request.get('/settings/logs/audit/export?format=ndjson&module=application');
    expect(ndjson.status()).toBe(200);
    expect(ndjson.headers()['content-type']).toContain('application/x-ndjson');
    expect(await ndjson.text()).toContain('"event_key":"application.view"');

    const technical = await page.goto('/settings/logs/technical');
    expect(technical?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: /Tehnički log|Technical log/i })).toBeVisible();
    await expect(page.locator('main').getByRole('link', { name: /Dnevnik aktivnosti|Activity audit/i }).first()).toBeVisible();
  });

  test('ordinary user cannot inspect or export either administrator log', async ({ page }) => {
    await login(page, userLogin, userPassword);

    for (const path of [
      '/settings/logs/audit',
      '/settings/logs/audit/export?format=ndjson',
      '/settings/logs/technical',
      '/settings/logs/technical/export',
    ]) {
      const response = await page.goto(path);
      expect(response?.status()).toBe(403);
      await expect(page.getByRole('heading', { name: /Pristup nije dozvoljen|Access denied/i })).toBeVisible();
    }
  });
});
