import { expect, test } from '@playwright/test';
import { apiHeaders, e2eEnvironment, expectData, expectUsableModal, idempotencyKey, login } from './helpers.js';

const { adminLogin, adminPassword, adminApiToken } = e2eEnvironment();

async function editInstructions(page, form, html) {
  await form.locator('.editor-html-field-open:visible').click();
  const dialog = page.locator('.editor-html-field-dialog[open]');
  const frame = dialog.frameLocator('iframe');
  await expect(dialog.getByRole('button', {name:'Apply text',exact:true})).toBeEnabled();
  await frame.locator('[data-editor-html-source-toggle]').click();
  await frame.locator('[data-editor-html-source]').fill(html);
  await frame.locator('[data-editor-html-source-toggle]').click();
  await dialog.getByRole('button', {name:'Apply text',exact:true}).click();
  await expect(dialog).toHaveCount(0);
}

async function addScript(page, name, code, instructions, approve) {
  await page.goto('/settings/editor-html');
  await page.locator('#external-script-new > summary').click();
  await page.locator('#external-script-name').fill(name);
  await page.locator('#external-script-value').fill(`<script>${code}</script>`);
  await editInstructions(page, page.locator('#external-script-name').locator('xpath=ancestor::form'), instructions);
  await Promise.all([
    page.waitForURL((url) => url.searchParams.get('saved') === '1'),
    page.locator('#external-script-name').locator('xpath=ancestor::form').getByRole('button', { name: 'Add as pending' }).click(),
  ]);
  const entry = page.locator('[data-external-script-entry]').filter({ has: page.locator('summary').filter({ hasText: name }) });
  const id = await entry.getAttribute('data-external-script-entry');
  if (approve) {
    await entry.locator(':scope > summary').click();
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === 'POST'),
      entry.locator('button[name="action"][value="approve"]').click(),
    ]);
    await page.waitForLoadState('load');
  }
  return id;
}

test('approved script help opens a passive accessible modal without changing script selection', async ({ page, request }) => {
  test.setTimeout(120_000);
  await login(page, adminLogin, adminPassword);
  const suffix = Date.now();
  const helpId = await addScript(page, `Help ${suffix}`, 'window.scriptHelpApproved=true;',
    '<h3>Usage steps</h3><p><strong>Mark a table:</strong></p><pre><code>&lt;table id="example"&gt;</code></pre>'
      + '<script>window.helpExecuted=true;</script><img src="x" onerror="window.helpExecuted=true">', true);
  const emptyId = await addScript(page, `Empty ${suffix}`, 'window.emptyScript=true;', '<p><br></p>', true);
  const pendingId = await addScript(page, `Pending ${suffix}`, 'window.pendingScript=true;', '<p>Not approved</p>', false);
  const slug = `script-help-${suffix}`;
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(slug) }),
    data: { title: 'Script instructions', slug, language: 'en', html: '<p>Ordinary content</p>' },
  }), 201);
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  const check = page.locator(`[data-editor-external-script="${helpId}"]`);
  const help = page.locator(`[data-editor-script-help="${helpId}"]`);
  await expect(check).not.toBeChecked();
  await expect(help).toBeVisible();
  await expect(page.locator(`[data-editor-external-script="${emptyId}"]`)).toBeVisible();
  await expect(page.locator(`[data-editor-script-help="${emptyId}"]`)).toHaveCount(0);
  await expect(page.locator(`[data-editor-external-script="${pendingId}"]`)).toHaveCount(0);
  const original = await page.locator('[data-editor-html-input]').inputValue();
  await help.focus();
  await page.keyboard.press('Enter');
  const modal = page.locator('#editor-html-script-help-modal');
  await expectUsableModal(modal);
  await expect(modal.getByRole('heading', { name: 'Usage steps', exact: true })).toBeVisible();
  await expect(modal.locator('pre code')).toHaveText('<table id="example">');
  await expect(modal.locator('script,img')).toHaveCount(0);
  expect(await page.evaluate(() => window.helpExecuted)).toBeUndefined();
  await page.keyboard.press('Escape');
  await expect(modal).toBeHidden();
  await expect(page.locator('[data-editor-html-input]')).toHaveValue(original);
  await expect(check).not.toBeChecked();
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  await check.check();
  await page.keyboard.press('Escape');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForURL((url) => url.searchParams.get('saved') === '1');
  await page.goto(`/editor-html/view?document=${slug}&lang=en`);
  expect(await page.evaluate(() => window.scriptHelpApproved)).toBe(true);
  expect(await page.evaluate(() => window.helpExecuted)).toBeUndefined();

  await page.goto('/settings/editor-html');
  const entry = page.locator(`[data-external-script-entry="${helpId}"]`);
  await entry.locator(':scope > summary').click();
  const textarea = entry.locator('textarea[name="instructions_translations[en]"]');
  await editInstructions(page, textarea.locator('xpath=ancestor::form'), '<p>&nbsp;</p>');
  await textarea.locator('xpath=ancestor::form').getByRole('button', { name: 'Save instructions', exact: true }).click();
  await page.waitForURL((url) => url.searchParams.get('saved') === '1');
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  await expect(check).toBeChecked();
  await expect(help).toHaveCount(0);
  await page.screenshot({ path: test.info().outputPath('scripts-without-help.png'), fullPage: true });
});

test('DataTables examples are pending by default and become usable only after approval and page selection', async ({ page, request }) => {
  test.setTimeout(120_000);
  await login(page, adminLogin, adminPassword);
  await page.goto('/settings/editor-html');
  const entries = page.locator('[data-external-script-entry]').filter({ hasText: 'DataTables 3.1.4' });
  await expect(entries).toHaveCount(1);
  const ids = [];
  for (let index = 0; index < 1; index++) {
    const entry = entries.nth(index);
    await expect(entry.locator(':scope > summary')).toContainText(/Pending approval|Awaiting approval/);
    ids.push(await entry.getAttribute('data-external-script-entry'));
    await entry.locator(':scope > summary').click();
    const loading = entry.locator('[data-script-loading-code]');
    await expect(loading).toContainText('datatables.min.css');
    await expect(loading).toContainText('datatables.min.js');
    await expect(loading).not.toContainText('new window.DataTable');
    expect((await loading.textContent()).trim().split('\n')).toHaveLength(2);
    expect(await loading.evaluate(node => getComputedStyle(node).whiteSpace)).toBe('pre');
    const startup = entry.locator('[data-script-startup-code]');
    await expect(startup.locator('pre')).toBeHidden();
    await startup.locator(':scope > summary').click();
    await expect(startup.locator('pre')).toContainText('// EN:');
    await expect(startup.locator('pre')).toContainText('// HR:');
    await expect(startup.locator('pre')).toContainText('new window.DataTable');
    await entry.locator(':scope > summary').click();
  }
  const slug = `datatable-example-${Date.now()}`;
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(slug) }),
    data: { title: 'DataTables example', slug, language: 'en', html: '<table id="dt-tablica-1">'
      + '<thead><tr><th scope="col">Number</th></tr></thead><tbody><tr><td>2</td></tr><tr><td>1</td></tr></tbody></table>' },
  }), 201);
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  for (const id of ids) await expect(page.locator(`[data-editor-external-script="${id}"]`)).toHaveCount(0);
  await page.goto(`/editor-html/view?document=${slug}&lang=en`);
  await expect(page.locator('link[href*="cdn.datatables.net"]')).toHaveCount(0);
  expect(await page.evaluate(() => typeof window.DataTable)).toBe('undefined');
  for (const id of ids) {
    await page.goto('/settings/editor-html');
    const entry = page.locator(`[data-external-script-entry="${id}"]`);
    await entry.locator(':scope > summary').click();
    await entry.locator('button[name="action"][value="approve"]').click();
    await page.waitForURL((url) => url.searchParams.get('saved') === '1');
  }
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  for (const id of ids) {
    await expect(page.locator(`[data-editor-script-help="${id}"]`)).toBeVisible();
    await page.locator(`[data-editor-external-script="${id}"]`).check();
  }
  await page.keyboard.press('Escape');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForURL((url) => url.searchParams.get('saved') === '1');
  await page.goto(`/editor-html/view?document=${slug}&lang=en`);
  await expect(page.locator('.dt-container')).toHaveCount(1);
  await expect(page.locator('link[href="https://cdn.datatables.net/v/bs5/dt-3.1.4/datatables.min.css"]')).toHaveCount(1);
  const header = page.locator('#dt-tablica-1').getByRole('columnheader');
  await header.getByRole('button').focus();
  await page.keyboard.press('Enter');
  await expect(header).toHaveAttribute('aria-sort', 'ascending');
  await expect(page.locator('#dt-tablica-1 tbody tr').first().locator('td')).toHaveText('1');
});


test('instruction translations save independently and the help modal follows the interface locale', async ({ page, request }) => {
  test.setTimeout(120_000);
  await login(page, adminLogin, adminPassword);
  const suffix = Date.now();
  const id = await addScript(page, `Translated ${suffix}`, 'window.translatedHelp=true;', '<h3>English instructions</h3>', true);
  await page.goto('/settings/editor-html');
  const entry = page.locator(`[data-external-script-entry="${id}"]`);
  await entry.locator(':scope > summary').click();
  const form = entry.locator('form').filter({has:page.locator('input[name="action"][value="instructions"]')});
  const language = form.locator('[data-script-instructions-language]');
  expect(await language.locator('option').evaluateAll(options => options.map(option => option.value))).toEqual(['hr', 'en']);
  await language.selectOption('hr');
  await editInstructions(page, form, '<h3>Hrvatska uputa</h3>');
  await language.selectOption('en');
  await expect(form.locator('textarea[name="instructions_translations[en]"]')).toHaveValue('<h3>English instructions</h3>');
  await language.selectOption('hr');
  await expect(form.locator('textarea[name="instructions_translations[hr]"]')).toHaveValue('<h3>Hrvatska uputa</h3>');
  await form.getByRole('button', { name:'Save instructions', exact:true }).click();
  await page.waitForURL(url => url.searchParams.get('saved') === '1');
  const slug = `translated-help-${suffix}`;
  await expectData(await request.post('/api/v1/pages', {
    headers:apiHeaders(adminApiToken, {'Idempotency-Key':idempotencyKey(slug)}),
    data:{title:'Translated instructions',slug,language:'en',html:'<p>Content</p>'},
  }),201);
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  await page.getByRole('button', { name:'External scripts',exact:true }).click();
  await page.locator(`[data-editor-script-help="${id}"]`).click();
  const modal = page.locator('#editor-html-script-help-modal');
  await expectUsableModal(modal);
  await expect(modal.getByRole('heading',{name:'English instructions',exact:true})).toBeVisible();
  await expect(modal.locator('.modal-body')).toHaveAttribute('lang','en');
  await page.keyboard.press('Escape');
  await page.goto(`/locale/hr?next=${encodeURIComponent(`/editor-html?document=${slug}&lang=en`)}`);
  await page.getByRole('button', {name:'Vanjske skripte',exact:true}).click();
  await page.locator(`[data-editor-script-help="${id}"]`).click();
  await expectUsableModal(modal);
  await expect(modal.getByRole('heading',{name:'Hrvatska uputa',exact:true})).toBeVisible();
  await expect(modal.locator('.modal-body')).toHaveAttribute('lang','hr');
  await page.keyboard.press('Escape');
  await page.goto(`/locale/en?next=${encodeURIComponent('/settings/editor-html')}`);

  await page.goto('/settings/editor-html');
  await entry.locator(':scope > summary').click();
  await language.selectOption('hr');
  await expect(form.locator('textarea[name="instructions_translations[hr]"]')).toHaveValue('<h3>Hrvatska uputa</h3>');
  await page.screenshot({path:test.info().outputPath('translated-instructions-settings.png'), fullPage:true});
  await editInstructions(page, form, '<p>&nbsp;</p>');
  await form.getByRole('button', {name:'Save instructions',exact:true}).click();
  await page.waitForURL(url => url.searchParams.get('saved') === '1');
  await page.goto(`/locale/hr?next=${encodeURIComponent(`/editor-html?document=${slug}&lang=en`)}`);
  await page.getByRole('button', {name:'Vanjske skripte',exact:true}).click();
  await page.locator(`[data-editor-script-help="${id}"]`).click();
  await expectUsableModal(modal);
  await expect(modal.getByRole('heading',{name:'English instructions',exact:true})).toBeVisible();
  await expect(modal.locator('.modal-body')).toHaveAttribute('lang','en');

});
