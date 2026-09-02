import { expect, test } from '@playwright/test';
import { e2eEnvironment, login } from './helpers.js';

const { adminLogin, adminPassword } = e2eEnvironment();

async function submitFormAndExpectPost(page, button, expectedPath) {
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === expectedPath);
  const [response] = await Promise.all([responsePromise, button.click()]);
  expect([200, 302, 303]).toContain(response.status());
}

test('task pencil edits list and table rows without losing additional cells', async ({ page }) => {
  test.setTimeout(60_000);
  const suffix = Date.now();
  let workspaceSlug = `e2e-task-table-${suffix}`;
  const pageSlug = `task-table-${suffix}`;

  await login(page, adminLogin, adminPassword);
  await page.goto('/workspaces/manage');
  await page.getByRole('textbox', { name: 'Name', exact: true }).fill('E2E Task Table');
  await page.getByRole('textbox', { name: 'Slug', exact: true }).fill(workspaceSlug);
  await page.getByRole('textbox', { name: 'Description' }).fill('Task table editor verification.');
  await submitFormAndExpectPost(
    page,
    page.getByRole('button', { name: 'Save', exact: true }),
    '/workspaces/save',
  );
  workspaceSlug = new URL(page.url()).searchParams.get('workspace') ?? workspaceSlug;

  await page.getByRole('link', { name: 'Open Workspace' }).click();
  await page.locator(
    'button.btn-outline-primary.workspace-tree-card-action[data-bs-target="#workspace-create-page"]',
  ).click();
  const createPanel = page.locator('#workspace-create-page');
  await createPanel.evaluate((node) => window.bootstrap?.Collapse
    ? window.bootstrap.Collapse.getOrCreateInstance(node, { toggle: false }).show()
    : node.classList.add('show'));
  await page.locator('[id^="workspace-page-title-"]:visible').fill('Task Table');
  await page.locator('#workspace-page-slug').fill(pageSlug);
  await submitFormAndExpectPost(
    page,
    page.getByRole('button', { name: /Create and edit|Kreiraj i uredi/i }),
    '/workspaces/page/create',
  );

  await page.getByRole('button', { name: 'Dynamic elements' }).click();
  await page.locator('[data-editor-html-task-open]').click();
  const modal = page.locator('#editor-html-task-modal');
  await expect(modal).toBeVisible();
  await modal.locator('[data-editor-html-task-title]').fill('Plan');
  await modal.locator('[data-editor-html-task-view]').selectOption('table');
  await modal.locator('[data-editor-html-task-items]').fill(
    'Review https://example.test/docs\nPrepare release',
  );
  await modal.locator('[data-editor-html-task-save]').click();
  await expect(modal).toBeHidden();

  const taskBlock = page.locator('.editor-html-task-list');
  const table = taskBlock.locator('.editor-html-task-table');
  await expect(table.locator('tbody tr')).toHaveCount(2);
  await expect(taskBlock.locator(':scope > [data-editor-html-dynamic-action="edit"]')).toBeVisible();

  await table.locator('thead th').first().click();
  await page.locator('button[title="Table"]').click();
  await page.locator('[data-editor-html-table-action="column-after"]').click();
  await table.locator('thead th').nth(1).fill('Owner');
  await table.locator('tbody tr').nth(0).locator('td').nth(1).fill('Ana');
  await table.locator('tbody tr').nth(1).locator('td').nth(1).fill('Boris');

  await taskBlock.locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
  await expect(modal).toBeVisible();
  await modal.locator('[data-editor-html-task-items]').fill(
    'Prepare release\nNew task\nReview https://example.test/docs',
  );
  await modal.locator('[data-editor-html-task-save]').click();
  await expect(modal).toBeHidden();

  await expect(table.locator('tbody tr')).toHaveCount(3);
  await expect(table.locator('tbody tr').nth(0).locator('td').nth(0)).toContainText('Prepare release');
  await expect(table.locator('tbody tr').nth(0).locator('td').nth(1)).toHaveText('Boris');
  await expect(table.locator('tbody tr').nth(1).locator('td').nth(0)).toContainText('New task');
  await expect(table.locator('tbody tr').nth(1).locator('td').nth(1)).toBeEmpty();
  await expect(table.locator('tbody tr').nth(2).locator('td').nth(1)).toHaveText('Ana');
  await expect(taskBlock.locator(':scope > [data-editor-html-dynamic-action="edit"]')).toBeVisible();

  await taskBlock.locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
  await modal.locator('[data-editor-html-task-items]').fill(
    'Prepare release\nReview https://example.test/docs',
  );
  await modal.locator('[data-editor-html-task-save]').click();
  await expect(table.locator('tbody tr')).toHaveCount(2);
  await expect(table.locator('tbody tr').nth(0).locator('td').nth(1)).toHaveText('Boris');
  await expect(table.locator('tbody tr').nth(1).locator('td').nth(1)).toHaveText('Ana');

  await submitFormAndExpectPost(
    page,
    page.getByRole('button', { name: 'Save and publish' }),
    '/editor-html/save',
  );
  await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`);
  await expect(page.locator('.editor-task-list .editor-task-checkbox')).toHaveCount(2);
  await expect(page.getByRole('link', { name: 'https://example.test/docs' })).toHaveAttribute(
    'href',
    'https://example.test/docs',
  );
  await expect(page.locator('.editor-html-task-table tbody tr').nth(0).locator('td').nth(1)).toHaveText('Boris');
  await expect(page.locator('.editor-html-task-table tbody tr').nth(1).locator('td').nth(1)).toHaveText('Ana');
});
