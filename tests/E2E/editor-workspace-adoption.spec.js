import { expect, test } from '@playwright/test';
import {
  apiHeaders, e2eEnvironment, expectData, idempotencyKey, login,
} from './helpers.js';

const { adminLogin, adminPassword, userLogin, userPassword, adminApiToken } = e2eEnvironment();

test('administrator moves standalone HTML pages into workspaces with links, translations, attachments and destination ACL', async ({
  browser, page, request,
}) => {
  test.setTimeout(180_000);
  await login(page, adminLogin, adminPassword);
  await page.goto('/settings/editor-html');
  await expect(page.getByRole('link', { name: 'Pages outside workspaces', exact: true }).first()).toBeVisible();
  const slugSwitch = page.locator('#editor-html-slug-enabled');
  const originalSlugEnabled = await slugSwitch.isChecked();
  await slugSwitch.check();
  const settingsSaved = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === '/settings/editor-html');
  await slugSwitch.locator('xpath=ancestor::form').getByRole('button', { name: /Save settings/i }).click();
  expect((await settingsSaved).status()).toBeLessThan(400);
  const visitorContext = await browser.newContext();
  const reader = await visitorContext.newPage();
  const userContext = await browser.newContext();
  const regular = await userContext.newPage();
  try {
    for (let index = 1; index <= 27; index++) {
      await expectData(await request.post('/api/v1/workspaces', {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(`move-list-${index}`) }),
        data: { name: `Move destination ${index}`, slug: `e2e-move-list-${index}`, visibility: 'restricted' },
      }), 201);
    }
    await login(regular, userLogin, userPassword);
    expect((await regular.goto('/settings/editor-html/standalone-pages')).status()).toBe(403);
    expect((await regular.request.get('/settings/editor-html/standalone-pages/lookup')).status()).toBe(403);

    for (const visibility of ['public', 'restricted']) {
      const key = `e2e-adopt-${visibility}`;
      const workspace = await expectData(await request.post('/api/v1/workspaces', {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(key) }),
        data: { name: `Adoption ${visibility}`, slug: key, visibility },
      }), 201);
      await expectData(await request.post('/api/v1/pages', {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(`${key}-page`) }),
        data: { title: `Original ${visibility}`, slug: key, language: 'en', html: `<p id="intro">${key} original body</p>` },
      }), 201);
      await expectData(await request.post(`/api/v1/pages/${key}/translations`, {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(`${key}-translation`) }),
        data: { source_language: 'en', target_language: 'hr' },
      }), 201);
      const bytes = Buffer.from(`Unchanged attachment ${key}\n`);
      const asset = await expectData(await request.post(`/api/v1/pages/${key}/attachments?lang=en`, {
        headers: apiHeaders(adminApiToken),
        multipart: { attachment: { name: 'instructions.txt', mimeType: 'text/plain', buffer: bytes } },
      }), 201);
      const historyPath = `/api/v1/pages/${key}/versions?lang=en`;
      const before = await expectData(await request.get(historyPath, { headers: apiHeaders(adminApiToken) }));
      const reference = `${key}-reference`;
      await expectData(await request.post('/api/v1/pages', {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(reference) }),
        data: {
          title: `Reference ${visibility}`, slug: reference, language: 'en',
          html: `<a href="/editor-html/view?document=${key}&amp;lang=hr#intro">Translated page</a>`,
        },
      }), 201);
      expect((await reader.goto(`/editor-html/view?document=${key}&lang=en`)).status()).toBe(200);

      await page.goto(`/settings/editor-html/standalone-pages?document=${key}`);
      await expect(page.locator('#standalone-page-move')).toBeVisible();
      const picker = page.locator('#move-workspace');
      await picker.click();
      await expect(page.locator('#move-workspace-options [role="option"]').first()).toBeVisible();
      const lookup = await page.request.get('/settings/editor-html/standalone-pages/lookup?page=1');
      expect(lookup.status()).toBe(200);
      const bounded = await lookup.json();
      expect(bounded.items.length).toBe(25);
      expect(bounded.more).toBe(true);
      await expect(page.locator('#move-workspace-more')).toBeVisible();
      await page.locator('#move-workspace-more').click();
      await expect.poll(() => page.locator('#move-workspace-options [role="option"]').count()).toBeGreaterThan(25);
      await picker.fill(`Adoption ${visibility}`);
      await expect(page.locator('#move-workspace-options [role="option"]')).toHaveCount(1);
      await picker.press('ArrowDown');
      await picker.press('Enter');
      await expect(page.locator('#move-workspace-id')).toHaveValue(String(workspace.id));
      await expect(picker).toHaveAttribute('aria-expanded', 'false');
      await page.screenshot({ path: test.info().outputPath(`move-${visibility}-desktop.png`), fullPage: true });
      await page.setViewportSize({ width: 390, height: 844 });
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
      await page.screenshot({ path: test.info().outputPath(`move-${visibility}-mobile.png`), fullPage: true });
      await page.setViewportSize({ width: 1280, height: 900 });
      await page.locator('#confirm-page-move').check();
      const rejectedForm = await page.locator('#standalone-page-move').evaluate((form) => Object.fromEntries(new FormData(form)));
      rejectedForm._move_token = 'invalid-session-token';
      expect((await page.request.post('/settings/editor-html/standalone-pages/move', { form: rejectedForm })).status()).toBe(400);
      await page.reload();
      await picker.fill(`Adoption ${visibility}`);
      await expect(page.locator('#move-workspace-options [role="option"]')).toHaveCount(1);
      await picker.press('ArrowDown');
      await picker.press('Enter');
      await page.locator('#confirm-page-move').check();
      await page.evaluate(() => scrollTo(0, 0));
      const posted = page.waitForResponse((response) => response.request().method() === 'POST'
        && new URL(response.url()).pathname === '/settings/editor-html/standalone-pages/move');
      await page.locator('#standalone-page-move button[type="submit"]').click();
      expect((await posted).status()).toBe(302);
      await expect(page.locator('.alert-success')).toContainText('moved');
      const destination = await page.locator('.alert-success a').getAttribute('href');
      expect(destination).toMatch(new RegExp(`/workspace/${key}/${key}`));
      expect((await page.goto(destination)).status()).toBe(200);
      await expect(page.locator('body')).toContainText(`${key} original body`);

      const after = await expectData(await request.get(historyPath, { headers: apiHeaders(adminApiToken) }));
      expect(after.map((version) => version.version_number)).toEqual(before.map((version) => version.version_number));
      const translated = await expectData(await request.get(`/api/v1/pages/${key}?lang=hr`, {
        headers: apiHeaders(adminApiToken),
      }));
      expect(translated.html).toContain(`${key} original body`);
      const repaired = await expectData(await request.get(`/api/v1/pages/${reference}?lang=en`, {
        headers: apiHeaders(adminApiToken),
      }));
      expect(repaired.html).toContain(`${destination}?lang=hr#intro`);
      const attachment = await request.get(`/api/v1/pages/${key}/attachments/${asset.uuid}/content?lang=en`, {
        headers: apiHeaders(adminApiToken),
      });
      expect(attachment.status()).toBe(200);
      expect(await attachment.body()).toEqual(bytes);

      for (const actor of [reader, regular]) {
        for (const url of [destination, `/editor-html/view?document=${key}&lang=en`, `/view/${key}?lang=en`]) {
          const result = await actor.goto(url);
          if (visibility === 'public') {
            expect(result.status()).toBe(200);
            await expect(actor.locator('body')).toContainText(`${key} original body`);
          } else {
            expect([403, 404]).toContain(result.status());
            await expect(actor.locator('body')).not.toContainText(`${key} original body`);
          }
        }
      }
      const assetResult = await reader.request.get(`/editor-html/asset/${asset.uuid}`);
      expect(visibility === 'public' ? assetResult.status() === 200 : [403, 404].includes(assetResult.status())).toBe(true);
    }
  } finally {
    await visitorContext.close();
    await userContext.close();
    await page.goto('/settings/editor-html');
    await page.locator('#editor-html-slug-enabled').setChecked(originalSlugEnabled);
    await page.locator('#editor-html-slug-enabled').locator('xpath=ancestor::form')
      .getByRole('button', { name: /Save settings/i }).click();
  }
});
