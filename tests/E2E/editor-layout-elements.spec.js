import { expect, test } from '@playwright/test';
import { e2eEnvironment, login } from './helpers.js';

const { adminLogin, adminPassword } = e2eEnvironment();

async function submitFormAndExpectPost(page, button, expectedPath) {
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === expectedPath);
  const [response] = await Promise.all([responsePromise, button.click()]);
  expect([200, 302, 303]).toContain(response.status());
}

async function placeCaretAtEnd(page) {
  await page.locator('[data-editor-html-surface]').evaluate((surface) => {
    surface.focus();
    const range = document.createRange();
    range.selectNodeContents(surface);
    range.collapse(false);
    const selection = window.getSelection();
    selection?.removeAllRanges();
    selection?.addRange(range);
  });
}

async function openToolbarMenu(page, name) {
  await page.getByRole('button', { name, exact: true }).click();
}

test('cards, tabs, accordion and chart 3D remain directly editable and render canonically', async ({ page }) => {
  test.setTimeout(90_000);
  const suffix = Date.now();
  let workspaceSlug = `e2e-editor-layout-${suffix}`;
  const pageSlug = `layout-${suffix}`;

  await login(page, adminLogin, adminPassword);
  await page.goto('/workspaces/manage');
  await page.getByRole('textbox', { name: 'Name', exact: true }).fill('E2E Editor Layout');
  await page.getByRole('textbox', { name: 'Slug', exact: true }).fill(workspaceSlug);
  await page.getByRole('textbox', { name: 'Description' }).fill('Editor layout verification.');
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
  await page.locator('[id^="workspace-page-title-"]:visible').fill('Editor Layout');
  await page.locator('#workspace-page-slug').fill(pageSlug);
  await submitFormAndExpectPost(
    page,
    page.getByRole('button', { name: /Create and edit|Kreiraj i uredi/i }),
    '/workspaces/page/create',
  );

  await placeCaretAtEnd(page);
  await openToolbarMenu(page, 'Cards');
  await page.locator('[data-editor-html-card="with-title"]').click();
  const card = page.locator('[data-editor-html-surface] .card').last();
  await card.locator(':scope > .card-header').fill('Editable card header');
  await card.locator(':scope > .card-body').fill('Editable card body');

  await placeCaretAtEnd(page);
  await openToolbarMenu(page, 'Dynamic elements');
  await page.locator('[data-editor-html-tabs-open]').click();
  const tabsModal = page.locator('#editor-html-tabs-modal');
  await tabsModal.locator('[data-editor-html-tabs-items]').fill('Overview\n  \t\nDetails');
  await tabsModal.locator('[data-editor-html-tabs-save]').click();
  await expect(tabsModal).toBeHidden();
  const tabs = page.locator('[data-editor-html-tabs="1"]').last();
  await expect(tabs.locator('[role="tab"]')).toHaveCount(2);
  await expect(tabs.locator('[role="tabpanel"]').nth(0)).toHaveAttribute(
    'data-editor-html-tab-panel-label',
    /Tab content: Overview|Sadržaj taba: Pregled/,
  );
  await tabs.locator('[role="tab"]').nth(0).fill('Summary');
  await expect(tabs.locator('[role="tabpanel"]').nth(0)).toHaveAttribute(
    'data-editor-html-tab-panel-label',
    /Tab content: Summary|Sadržaj taba: Summary/,
  );
  await tabs.locator('[role="tabpanel"]').nth(0).fill('First tab body');
  await tabs.locator('[role="tabpanel"]').nth(1).fill('Second tab body');
  await tabs.locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
  await expect(tabsModal.locator('[data-editor-html-tabs-items]')).toHaveValue('Summary\nDetails');
  await tabsModal.locator('[data-editor-html-tabs-items]').fill('Summary\nDetails\nResources');
  await tabsModal.locator('[data-editor-html-tabs-save]').click();
  await expect(tabs.locator('[role="tab"]')).toHaveCount(3);
  await expect(tabs.locator('[role="tabpanel"]').nth(0)).toHaveText('First tab body');

  await placeCaretAtEnd(page);
  await openToolbarMenu(page, 'Dynamic elements');
  await page.locator('[data-editor-html-accordion-open]').click();
  const accordionModal = page.locator('#editor-html-accordion-modal');
  await accordionModal.locator('[data-editor-html-accordion-items]').fill('First item\n \t \nSecond item');
  await accordionModal.locator('[data-editor-html-accordion-save]').click();
  await expect(accordionModal).toBeHidden();
  const accordion = page.locator('[data-editor-html-accordion="1"]').last();
  await expect(accordion.locator(':scope > details')).toHaveCount(2);
  await expect(accordion.locator(':scope > details').nth(0)).toHaveAttribute('open', '');
  await expect(accordion.locator(':scope > details').nth(1)).toHaveAttribute('open', '');
  await expect(accordion.locator(':scope > details').nth(0).locator(':scope > div')).toBeVisible();
  await accordion.locator(':scope > details').nth(0).locator(':scope > summary').fill('Changed item');
  await accordion.locator(':scope > details').nth(0).locator(':scope > div').fill('Accordion body');
  await accordion.locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
  await expect(accordionModal.locator('[data-editor-html-accordion-items]')).toHaveValue(
    'Changed item\nSecond item',
  );
  await accordionModal.locator('[data-editor-html-accordion-save]').click();

  await placeCaretAtEnd(page);
  await openToolbarMenu(page, 'Dynamic elements');
  await page.locator('[data-editor-html-chart-open]').click();
  const chartModal = page.locator('#editor-html-chart-modal');
  await expect(chartModal).toBeVisible();
  await chartModal.locator('[data-editor-html-chart-3d]').check();
  await expect(chartModal.locator('[data-editor-html-chart-preview]')).toContainText('');
  await expect.poll(async () => chartModal.locator('[data-editor-html-chart-preview]').innerHTML())
    .toContain('feDropShadow');
  await chartModal.locator('[data-editor-html-chart-save]').click();
  await expect(chartModal).toBeHidden();

  await submitFormAndExpectPost(
    page,
    page.getByRole('button', { name: 'Save and publish' }),
    '/editor-html/save',
  );
  await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`);
  const renderedCard = page.locator('.editor-html-view-content > .card').filter({
    has: page.getByText('Editable card header', { exact: true }),
  });
  await expect(renderedCard.locator(':scope > .card-header')).toHaveText('Editable card header');
  await expect(renderedCard.locator(':scope > .card-body')).toHaveText('Editable card body');

  const renderedTabs = page.locator('[data-editor-html-tabs="1"]');
  await expect(renderedTabs.locator('[role="tabpanel"]').nth(0)).toBeVisible();
  await renderedTabs.locator('[role="tab"]').nth(1).click();
  await expect(renderedTabs.locator('[role="tabpanel"]').nth(1)).toBeVisible();
  await expect(renderedTabs.locator('[role="tabpanel"]').nth(1)).toHaveText('Second tab body');

  const renderedAccordion = page.locator('[data-editor-html-accordion="1"]');
  await expect(renderedAccordion.locator(':scope > details').nth(0)).not.toHaveAttribute('open', '');
  await renderedAccordion.locator(':scope > details').nth(0).locator('summary').click();
  await expect(renderedAccordion.locator(':scope > details').nth(0)).toHaveAttribute('open', '');
  await renderedAccordion.locator(':scope > details').nth(1).locator('summary').click();
  await expect(renderedAccordion.locator(':scope > details').nth(0)).toHaveAttribute('open', '');
  await expect(renderedAccordion.locator(':scope > details').nth(1)).toHaveAttribute('open', '');
  await expect(renderedAccordion.locator(':scope > details').nth(0).locator(':scope > div')).toHaveText(
    'Accordion body',
  );
  await expect(page.locator('.editor-html-chart-svg')).toHaveCount(1);
  await expect(page.locator('.editor-html-chart-svg filter')).toHaveCount(1);
});
