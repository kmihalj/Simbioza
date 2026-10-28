import { expect, test } from '@playwright/test';
import {
  apiHeaders, e2eEnvironment, expectData, getDataWithEtag, idempotencyKey, login,
} from './helpers.js';

const { adminLogin, adminPassword, adminApiToken } = e2eEnvironment();

test('full Simbioza shows each standalone catalogue only while that content exists', async ({ page, request }) => {
  const pages = page.locator('.hph-primary-navigation a[href$="/pages"]');
  const surveys = page.locator('.hph-primary-navigation a[href$="/questionnaires"]');
  await page.goto('/about');
  await expect(pages).toHaveCount(0);
  await expect(surveys).toHaveCount(0);

  const key = 'e2e-standalone-navigation';
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('navigation-page') }),
    data: { title: 'Standalone navigation', slug: key, language: 'en', html: '<p>Standalone content</p>' },
  }), 201);
  await page.reload();
  await expect(pages).toBeVisible();
  await expect(surveys).toHaveCount(0);
  await login(page, adminLogin, adminPassword);
  await expect(pages).toBeVisible();

  const editable = await getDataWithEtag(request, `/api/v1/pages/${key}?lang=en`, apiHeaders(adminApiToken));
  expect((await request.delete(`/api/v1/pages/${key}?lang=en`, {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('navigation-page-delete'), 'If-Match': editable.etag,
    }),
  })).status()).toBe(204);
  await page.goto('/about');
  await expect(pages).toHaveCount(0);

  const survey = await expectData(await request.post('/api/v1/surveys', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('navigation-survey') }),
    data: { title: 'Standalone survey navigation', primary_language: 'en' },
  }), 201);
  await page.reload();
  await expect(surveys).toBeVisible();
  await expect(pages).toHaveCount(0);
  const snapshot = await expectData(await request.get(`/api/v1/surveys/${survey.uuid}`, {
    headers: apiHeaders(adminApiToken),
  }));
  await expectData(await request.post(`/api/v1/surveys/${survey.uuid}/archive`, {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('navigation-survey-archive') }),
    data: {
      version: Number(snapshot.survey.lock_version), revision: snapshot.revision.uuid, confirmed: true,
    },
  }));
  await page.reload();
  await expect(surveys).toHaveCount(0);
});
