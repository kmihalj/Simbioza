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

test('approved script help opens an accessible modal without changing script selection', async ({ page, request }) => {
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
  await expect(modal.locator('script')).toHaveCount(0);
  await expect(modal.locator('img')).toHaveCount(1);
  await expect(modal.locator('img')).not.toHaveAttribute('onerror', /.+/);
  expect(await modal.locator('*').evaluateAll(nodes => nodes.flatMap(node =>
    Array.from(node.attributes).filter(attribute => /^on/i.test(attribute.name)).map(attribute => attribute.name),
  ))).toEqual([]);
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

// EN: Real form persistence and the full Editor iframe must preserve the same rich canonical content.
// HR: Stvarno spremanje obrasca i iframe punog Editora moraju čuvati isti bogati kanonski sadržaj.
test('rich instruction fields preserve cards, tabs, tables and media spacing after save and reopen', async ({ page, request }) => {
  test.setTimeout(180_000);
  await login(page, adminLogin, adminPassword);
  await page.route('https://editor-fixture.invalid/pixel.png', route => route.fulfill({
    contentType: 'image/png',
    body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'base64'),
  }));
  const suffix = Date.now();
  const rows = Array.from({length: 30}, (_, index) => `<tr><td>${30-index}</td></tr>`).join('');
  const media = Array.from({length: 4}, (_, gap) => `<span id="instruction-media-${gap}"`
    + ` class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top w-25 mb-${gap} me-${gap}">`
    + `<img class="figure-img img-fluid" src="https://editor-fixture.invalid/pixel.png" alt="Spacing ${gap}"></span>`).join('');
  const richHtml = '<div class="card"><div class="card-header">Rich card</div>'
    + '<div class="card-body"><p><strong>Preserved formatting</strong> and <em>emphasis</em></p></div></div>'
    + '<section class="editor-html-tabs" data-editor-html-tabs="1" data-editor-html-tabs-id="tabs-instructions">'
    + '<div class="editor-html-tabs__list" role="tablist" aria-label="Rich instruction examples">'
    + '<button type="button" role="tab" data-editor-html-tab="0">Table example</button>'
    + '<button type="button" role="tab" data-editor-html-tab="1">Details example</button></div>'
    + '<div class="editor-html-tabs__panels"><div role="tabpanel" data-editor-html-tab-panel="0">'
    + '<table class="table table-bordered" data-editor-html-table="1" data-editor-html-table-page-size="all">'
    + '<thead class="table-dark"><tr><th scope="col">Value</th></tr></thead><tbody>' + rows + '</tbody></table>'
    + '</div><div role="tabpanel" data-editor-html-tab-panel="1"><p>Second rich panel</p></div></div></section>' + media;
  const id = await addScript(page, `Rich field ${suffix}`, 'window.richInstructionsApproved=true;', richHtml, true);
  await page.goto('/settings/editor-html');
  const entry = page.locator(`[data-external-script-entry="${id}"]`);
  await entry.locator(':scope > summary').click();
  const form = entry.locator('form').filter({has: page.locator('input[name="action"][value="instructions"]')});
  await form.locator('[data-script-instructions-language]').selectOption('en');
  const textarea = form.locator('textarea[name="instructions_translations[en]"]');
  const stored = await textarea.inputValue();
  const contract = html => {
    const document = new DOMParser().parseFromString(html, 'text/html');
    return {
      card: document.querySelector('.card-header')?.textContent,
      strong: document.querySelector('.card-body strong')?.textContent,
      tableClasses: document.querySelector('table')?.className,
      headerClasses: document.querySelector('thead')?.className,
      pageSize: document.querySelector('table')?.getAttribute('data-editor-html-table-page-size'),
      rows: document.querySelectorAll('tbody tr').length,
      tabCount: document.querySelectorAll('[role="tab"]').length,
      gaps: Array.from(document.querySelectorAll('.figure')).map(node =>
        Array.from(node.classList).filter(name => /^(?:mb|me)-/.test(name)).sort()),
    };
  };
  expect(await page.evaluate(contract, stored)).toEqual({
    card: 'Rich card', strong: 'Preserved formatting', tableClasses: 'table table-bordered',
    headerClasses: 'table-dark', pageSize: 'all', rows: 30, tabCount: 2,
    gaps: [['mb-0','me-0'],['mb-1','me-1'],['mb-2','me-2'],['mb-3','me-3']],
  });
  await form.locator('.editor-html-field-open:visible').click();
  const dialog = page.locator('.editor-html-field-dialog[open]');
  const frame = dialog.frameLocator('iframe');
  await expect(dialog.getByRole('button', {name:'Apply text',exact:true})).toBeEnabled();
  await expect(frame.locator('[data-editor-html-surface] .card-header')).toHaveText('Rich card');
  await expect(frame.locator('[data-editor-html-surface] table')).toHaveClass('table table-bordered');
  await expect(frame.locator('[data-editor-html-surface] thead')).toHaveClass('table-dark');
  for (let gap = 0; gap < 4; gap++) {
    const mediaClasses = await frame.locator(`img[alt="Spacing ${gap}"]`).evaluate(node =>
      Array.from(node.closest('.figure').classList).filter(name => /^(?:mb|me)-/.test(name)).sort());
    expect(mediaClasses).toEqual([`mb-${gap}`,`me-${gap}`]);
  }
  await dialog.getByRole('button', {name:'Apply text',exact:true}).click();
  await expect(dialog).toHaveCount(0);
  await expect(textarea).toHaveValue(stored);
  await form.getByRole('button', {name:'Save instructions',exact:true}).click();
  await page.waitForURL(url => url.searchParams.get('saved') === '1');
  await page.reload();
  await entry.locator(':scope > summary').click();
  await form.locator('[data-script-instructions-language]').selectOption('en');
  await expect(textarea).toHaveValue(stored);
  const preview = form.locator('.editor-html-field-preview:visible');
  await expect(preview.locator('.card-header')).toHaveText('Rich card');
  await preview.getByRole('tab', {name:'Details example',exact:true}).click();
  await expect(preview.getByRole('tabpanel').filter({hasText:'Second rich panel'})).toBeVisible();
  await preview.getByRole('tab', {name:'Table example',exact:true}).click();
  await expect(preview.locator('tbody tr:visible')).toHaveCount(30);
  await preview.locator('th button').click();
  await expect(preview.locator('tbody tr').first().locator('td')).toHaveText('1');
  await preview.getByRole('combobox', {name:'Rows per page',exact:true}).selectOption('10');
  await expect(preview.locator('tbody tr:visible')).toHaveCount(10);
  await expect(textarea).toHaveValue(stored);

  const slug = `rich-instructions-${suffix}`;
  await expectData(await request.post('/api/v1/pages', {
    headers: apiHeaders(adminApiToken, {'Idempotency-Key':idempotencyKey(slug)}),
    data: {title:'Rich help reader',slug,language:'en',html:'<p>Editor content stays unchanged</p>'},
  }),201);
  await page.goto(`/editor-html?document=${slug}&lang=en`);
  const editorBefore = await page.locator('[data-editor-html-surface]').innerHTML();
  const htmlBefore = await page.locator('[data-editor-html-input]').inputValue();
  await page.getByRole('button', {name:'External scripts',exact:true}).click();
  await page.locator(`[data-editor-script-help="${id}"]`).click();
  const help = page.locator('#editor-html-script-help-modal');
  await expectUsableModal(help);
  await expect(help.locator('.card-header')).toHaveText('Rich card');
  await help.getByRole('tab', {name:'Details example',exact:true}).click();
  await expect(help.getByRole('tabpanel').filter({hasText:'Second rich panel'})).toBeVisible();
  await help.getByRole('tab', {name:'Table example',exact:true}).click();
  await expect(help.locator('tbody tr:visible')).toHaveCount(30);
  await help.locator('th button').click();
  await expect(help.locator('tbody tr').first().locator('td')).toHaveText('1');
  await help.getByRole('combobox', {name:'Rows per page',exact:true}).selectOption('10');
  await expect(help.locator('tbody tr:visible')).toHaveCount(10);
  await help.getByRole('combobox', {name:'Rows per page',exact:true}).selectOption('all');
  await expect(help.locator('tbody tr:visible')).toHaveCount(30);
  await expect(page.locator('[data-editor-html-input]')).toHaveValue(htmlBefore);
  expect(await page.locator('[data-editor-html-surface]').innerHTML()).toBe(editorBefore);
  await page.keyboard.press('Escape');
  await expect(help).toBeHidden();
  await expect(page.locator(`[data-editor-external-script="${id}"]`)).not.toBeChecked();
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
