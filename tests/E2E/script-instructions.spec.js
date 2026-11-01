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

test('native table controls are per table, include All, and need no external script approval', async ({ page, request }) => {
  test.setTimeout(120_000);
  await login(page, adminLogin, adminPassword);
  await page.goto('/settings/editor-html');
  await expect(page.locator('[data-external-script-entry]').filter({ hasText: 'DataTables 3.1.4' })).toHaveCount(0);
  const slug = `native-table-${Date.now()}`;
  const rows = Array.from({length: 23}, (_, index) => `<tr><td>${23-index}</td></tr>`).join('');
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(slug) }),
    data: { title: 'Native tables', slug, language: 'en', html: '<table id="native-test">'
      + '<thead><tr><th scope="col">Number</th></tr></thead><tbody>' + rows + '</tbody></table>'
      + '<table id="plain-test"><thead><tr><th>Plain</th></tr></thead><tbody><tr><td>Unchanged</td></tr></tbody></table>' },
  }), 201);
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  await page.locator('#native-test tbody td').first().click();
  await page.getByRole('button', {name:'Table',exact:true}).click();
  await page.getByRole('button', {name:'Table settings',exact:true}).click();
  const settings = page.getByRole('dialog', {name:'Table settings',exact:true});
  await expectUsableModal(settings);
  await settings.getByRole('checkbox', {name:'Sorting and pagination',exact:true}).check();
  await settings.getByRole('combobox', {name:'Initial rows per page',exact:true}).selectOption('all');
  await settings.getByRole('button', {name:'Apply',exact:true}).click();
  await expect(settings).toBeHidden();
  await expect(page.locator('[data-editor-html-surface] .editor-html-table-controls')).toHaveCount(0);
  await expect(page.locator('#native-test')).toHaveAttribute('data-editor-html-table-page-size', 'all');
  await page.getByRole('button', {name:'Save',exact:true}).click();
  await page.waitForURL((url) => url.searchParams.get('saved') === '1');
  await page.goto(`/editor-html/view?document=${slug}&lang=en`);
  await expect(page.getByRole('combobox', {name:'Rows per page',exact:true})).toHaveValue('all');
  await expect(page.locator('#native-test tbody tr:visible')).toHaveCount(23);
  await expect(page.locator('#plain-test button')).toHaveCount(0);
  await expect(page.locator('script[src*="datatables"], link[href*="cdn.datatables.net"]')).toHaveCount(0);
  const header = page.locator('#native-test th');
  await header.getByRole('button').press('Enter');
  await expect(header).toHaveAttribute('aria-sort', 'ascending');
  await expect(page.locator('#native-test tbody tr').first().locator('td')).toHaveText('1');
  await page.getByRole('combobox', {name:'Rows per page',exact:true}).selectOption('10');
  await expect(page.locator('#native-test tbody tr:visible')).toHaveCount(10);
  await page.getByRole('button', {name:'Last page',exact:true}).click();
  await expect(page.locator('#native-test tbody tr:visible')).toHaveCount(3);
  await page.getByRole('combobox', {name:'Rows per page',exact:true}).selectOption('all');
  await expect(page.locator('#native-test tbody tr:visible')).toHaveCount(23);
  await expect(page.getByRole('button', {name:'Last page',exact:true})).toBeDisabled();
  await page.emulateMedia({media:'print'});
  await expect(page.locator('.editor-html-table-controls:visible')).toHaveCount(0);
  await expect(page.locator('#native-test tbody tr:visible')).toHaveCount(23);
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
