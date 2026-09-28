import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  getDataWithEtag,
  idempotencyKey,
  login,
} from './helpers.js';

const { adminApiToken, adminLogin, adminPassword } = e2eEnvironment();
const adminHeaders = apiHeaders(adminApiToken);

test('permanent Workspace deletion shows persistent progress and resumes after interruption', async ({ page, request }) => {
  test.setTimeout(120_000);
  const slug = `e2e-purge-${Date.now()}`;

  // HR: Više od jedne serije dokumenata omogućuje provjeru prekida i nastavka.
  // EN: More than one document batch verifies interruption and resumption.
  await expectData(await request.post('/api/v1/workspaces', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('purge-workspace') }),
    data: { name: 'E2E Purge', slug, description: 'Disposable purge fixture.', visibility: 'public' },
  }), 201);
  for (let index = 0; index < 21; index += 1) {
    await expectData(await request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(`purge-page-${index}`) }),
      data: {
        title: `Purge page ${index}`,
        slug: `${slug}-page-${index}`,
        workspace_slug: slug,
        language: 'en',
        contents_visibility: 'inherit',
        content: [{ type: 'html', html: `<p>Disposable page ${index}</p>` }],
      },
    }), 201);
  }
  const current = await getDataWithEtag(request, `/api/v1/workspaces/${slug}`, adminHeaders);
  const deleted = await request.delete(`/api/v1/workspaces/${slug}`, {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('purge-soft-delete'),
      'If-Match': current.etag,
    }),
  });
  expect(deleted.status()).toBe(204);

  await login(page, adminLogin, adminPassword);
  await page.goto('/settings/workspaces/deleted?lang=en');
  const row = page.getByRole('row').filter({ hasText: slug });
  const form = row.locator('[data-workspace-purge-form]');
  await expect(form).toBeVisible();
  await form.locator('input[name="confirm_slug"]').fill(slug);

  let requests = 0;
  await page.route('**/settings/workspaces/purge', async (route) => {
    if (route.request().method() !== 'POST') {
      await route.continue();
      return;
    }
    requests += 1;
    if (requests === 2) {
      await route.abort();
      return;
    }
    await route.continue();
  });
  await form.getByRole('button', { name: 'Delete permanently' }).click();
  await expect.poll(() => requests).toBeGreaterThanOrEqual(2);
  await expect(row.locator('[data-workspace-purge-count]')).toContainText(' / ');
  await expect(form.getByRole('button', { name: 'Delete permanently' })).toBeEnabled();
  await page.unroute('**/settings/workspaces/purge');

  // HR: Ponovno učitavanje mora zadržati brojač i spriječiti vraćanje djelomično uklonjenog područja.
  // EN: Reload must retain progress and prevent restoration of a partially purged Workspace.
  await page.reload();
  const resumedRow = page.getByRole('row').filter({ hasText: slug });
  const resumedForm = resumedRow.locator('[data-workspace-purge-form]');
  await expect(resumedRow.locator('[data-workspace-purge-progress-wrap]')).toBeVisible();
  await expect(resumedRow.getByRole('button', { name: 'Restore' })).toBeDisabled();
  await resumedForm.locator('input[name="confirm_slug"]').fill(slug);
  await resumedForm.getByRole('button', { name: 'Delete permanently' }).click();
  await expect(resumedRow).toHaveCount(0);
});
