import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { pathToFileURL } from 'node:url';
import { apiHeaders, createEditorSurface, e2eEnvironment, expectData, getDataWithEtag, idempotencyKey, login } from './helpers.js';

const { adminLogin, adminPassword, adminApiToken } = e2eEnvironment();

// EN: Select actual text, then exercise the public toolbar rather than execCommand in the test.
// HR: Označi stvarni tekst pa koristi javnu alatnu traku, ne execCommand iz testa.
async function selectText(page, id) {
  await page.locator(`[data-editor-html-surface] #${id}`).evaluate((element) => {
    element.focus();
    const range = document.createRange();
    range.selectNodeContents(element);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  });
}

async function end(page) {
  await page.locator('[data-editor-html-surface]').evaluate((element) => {
    element.focus();
    const range = document.createRange();
    range.selectNodeContents(element);
    range.collapse(false);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  });
}

async function menu(page, name, selector) {
  await page.getByRole('button', { name, exact: true }).click();
  await page.locator(`.editor-html-toolbar ${selector}`).click();
}

async function source(page, html) {
  await page.locator('[data-editor-html-source-toggle]').click();
  await page.locator('[data-editor-html-source]').fill(html);
  await page.locator('[data-editor-html-source-toggle]').click();
}

// EN: Compare persisted DOM, not indentation or attribute-order differences in standalone HTML.
// HR: Usporedi spremljeni DOM, ne razlike uvlačenja ili redoslijeda atributa standalone HTML-a.
async function shape(page, html) {
  return page.evaluate((value) => {
    const document = new DOMParser().parseFromString(value, 'text/html');
    function walk(node) {
      if (node.nodeType === Node.TEXT_NODE) {
        const text = node.textContent.replace(/\s+/g, ' ').trim();
        return text ? text : null;
      }
      if (!(node instanceof Element)) return null;
      // EN: HTML parsers repair illegal p/list nesting with empty anonymous paragraphs.
      // HR: HTML parseri popravljaju nedopušteno p/list gniježđenje praznim anonimnim odlomcima.
      if (node.tagName === 'P' && node.attributes.length === 0 && node.childNodes.length === 0) return null;
      const attributes = Array.from(node.attributes).map(({ name, value }) => {
        if (name === 'style') {
          value = Array.from(node.style).sort().map((key) => `${key}:${node.style.getPropertyValue(key).trim()}`).join(';');
        }
        return [name, value];
      }).sort(([a], [b]) => a.localeCompare(b));
      return [node.tagName, attributes, Array.from(node.childNodes).map(walk).filter((v) => v !== null)];
    }
    return Array.from(document.body.childNodes).map(walk).filter((v) => v !== null);
  }, html);
}

async function persist(page, editorPath) {
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === '/editor-html/save');
  await page.getByRole('button', { name: 'Save and publish', exact: true }).click();
  const response = await responsePromise;
  expect([200, 302, 303]).toContain(response.status());
  const sent = new URLSearchParams(response.request().postData()).get('html');
  expect(sent).toBeTruthy();
  await expect(page.locator('.editor-html-view-content')).toBeVisible();
  const publishedPath = new URL(page.url()).pathname;
  await page.goto(editorPath);
  const stored = await page.locator('[data-editor-html-input]').inputValue();
  expect(await shape(page, stored)).toEqual(await shape(page, sent));
  await test.info().attach('persisted-canonical-html', { body: stored, contentType: 'text/html' });
  await page.goto(publishedPath);
  return { stored, publishedPath };
}

async function editor(page, request, prefix) {
  await login(page, adminLogin, adminPassword);
  const path = await createEditorSurface(request, adminApiToken, prefix);
  await page.goto(path);
  return path;
}

// EN: Exercise the pencil, populated edit form, update, and removal of every dynamic block instance.
// HR: Provjeri olovku, popunjeni obrazac izmjene, ažuriranje i uklanjanje svake dinamičke instance.
async function editAndRemove(page, type, marker = type) {
  const blocks = page.locator(`[data-editor-html-surface] [data-editor-html-${marker}="1"]`);
  while (await blocks.count()) {
    const count = await blocks.count();
    await blocks.first().locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
    const modal = page.locator(`#editor-html-${type}-modal`);
    await expect(modal).toBeVisible();
    const title = modal.locator(`[data-editor-html-${type}-title]`);
    if (await title.count()) {
      await title.fill(`Updated ${type}`);
      const save = modal.locator(`[data-editor-html-${type}-save], [data-editor-html-${type}-insert]`);
      await save.click();
      await expect(modal).toBeHidden();
      await blocks.first().locator(':scope > [data-editor-html-dynamic-action="edit"]').click();
      await expect(title).toHaveValue(`Updated ${type}`);
    }
    await modal.locator(`[data-editor-html-${type}-remove]`).click();
    await expect(modal).toBeHidden();
    await expect(blocks).toHaveCount(count - 1);
  }
}

test('every basic toolbar format, insertion variant and editing command survives save and reopen', async ({ page, request }) => {
  test.setTimeout(180_000);
  const path = await editor(page, request, 'roundtrip-basic');
  const formats = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'blockquote', 'pre'];
  const commands = ['bold', 'italic', 'underline', 'removeFormat', 'unlink', 'undo', 'redo'];
  const aligns = ['left', 'center', 'right', 'justify'];
  await source(page, formats.map((tag) => `<p id="format-${tag}">Format ${tag}</p>`).join('')
    + ['bold', 'italic', 'underline', 'combined', 'remove', 'link', 'unlink', 'undo', 'ul', 'ol']
      .map((id) => `<p id="case-${id}">${id === 'remove' ? '<b><i>remove</i></b>' : id}</p>`).join('')
    + aligns.map((align) => `<p id="align-${align}">Alignment ${align}</p>`).join(''));
  expect(await page.locator('[data-editor-html-format-select] option').evaluateAll((nodes) => nodes.map((n) => n.value)))
    .toEqual(formats);
  for (const tag of formats) {
    await selectText(page, `format-${tag}`);
    await page.locator('[data-editor-html-format-select]').selectOption(tag);
    await expect(page.locator(`[data-editor-html-surface] ${tag}`).filter({ hasText: new RegExp(`^Format ${tag}$`) })).toHaveCount(1);
  }
  for (const command of ['bold', 'italic', 'underline']) {
    await selectText(page, `case-${command}`);
    await page.locator(`[data-editor-html-command="${command}"]`).click();
  }
  await selectText(page, 'case-combined');
  for (const command of ['bold', 'italic', 'underline']) await page.locator(`[data-editor-html-command="${command}"]`).click();
  await selectText(page, 'case-remove');
  await page.locator('[data-editor-html-command="removeFormat"]').click();
  await expect(page.locator('#case-remove b, #case-remove i')).toHaveCount(0);
  for (const align of aligns) {
    await selectText(page, `align-${align}`);
    await page.locator(`[data-editor-html-align="${align}"]`).click();
  }
  for (const [id, command] of [['ul', 'insertUnorderedList'], ['ol', 'insertOrderedList']]) {
    await selectText(page, `case-${id}`);
    await page.locator(`[data-editor-html-command="${command}"]`).click();
  }
  for (const id of ['link', 'unlink']) {
    await selectText(page, `case-${id}`);
    await page.locator('[data-editor-html-link]').click();
    await page.locator('[data-editor-html-link-url]').fill(`https://example.com/${id}`);
    await page.locator('[data-editor-html-link-url-insert]').click();
    await expect(page.locator('#editor-html-link-modal')).toBeHidden();
  }
  await selectText(page, 'case-unlink');
  await page.locator('[data-editor-html-command="unlink"]').click();
  await expect(page.locator('#case-unlink a')).toHaveCount(0);
  await selectText(page, 'case-undo');
  await page.locator('[data-editor-html-command="bold"]').click();
  await page.locator('[data-editor-html-command="undo"]').click();
  await expect(page.locator('#case-undo b, #case-undo strong')).toHaveCount(0);
  await page.locator('[data-editor-html-command="redo"]').click();
  await expect(page.locator('#case-undo b, #case-undo strong')).toHaveCount(1);
  for (const snippet of ['columns2', 'columns3', 'hr']) {
    await end(page); await menu(page, 'Insert', `[data-editor-html-snippet="${snippet}"]`);
  }
  const alerts = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
  for (const variant of alerts) {
    await end(page); await menu(page, 'Bootstrap alerts', `[data-editor-html-alert="${variant}"]`);
  }
  for (const card of ['with-title', 'plain']) {
    await end(page); await menu(page, 'Cards', `[data-editor-html-card="${card}"]`);
  }
  await page.locator('[data-editor-html-check]').click();
  await expect(page.locator('.editor-html-check-toast').last()).toBeVisible();
  await page.locator('.editor-html-check-toast').last().getByRole('button', { name: 'Close', exact: true }).click();
  await page.locator('[data-editor-html-fullscreen-toggle]').click();
  await expect(page.locator('[data-editor-html-editing-frame]')).toHaveClass(/editor-html-fullscreen/);
  await page.locator('[data-editor-html-fullscreen-toggle]').click();
  const { stored } = await persist(page, path);
  expect(stored).toMatch(/<b>bold<\/b>|<strong>bold<\/strong>/);
  const view = page.locator('.editor-html-view-content');
  for (const [id, tag] of [['bold', 'b'], ['italic', 'i'], ['underline', 'u']]) {
    await expect(view.locator(`#case-${id} ${tag}`)).toHaveText(id);
  }
  await expect(view.locator('#case-combined b i u')).toHaveText('combined');
  await expect(view.locator('ul li')).toContainText(['ul']);
  await expect(view.locator('ol li')).toContainText(['ol']);
  await expect(view.locator('a[href="https://example.com/link"]')).toHaveText('link');
  for (const variant of alerts) await expect(view.locator(`.alert-${variant}`)).toHaveCount(1);
  await expect(view.locator('.card')).toHaveCount(2);
  await expect(view.locator('.card-header')).toHaveCount(1);
  await expect(view.locator('.col-md-6')).toHaveCount(2);
  await expect(view.locator('.col-md-4')).toHaveCount(3);
  await expect(view.locator('hr')).toHaveCount(1);
  expect(commands.length).toBe(7);
});

test('all table operations and media layout menu values survive persistence', async ({ page, request }) => {
  test.setTimeout(180_000);
  const path = await editor(page, request, 'roundtrip-table-media');
  await source(page, '<p>Table and media round trip</p>');
  await end(page); await menu(page, 'Table', '[data-editor-html-snippet="table"]');
  const table = page.locator('[data-editor-html-surface] table');
  const action = async (name, cell) => {
    await cell.click(); await menu(page, 'Table', `[data-editor-html-table-action="${name}"]`);
  };
  await action('row-before', table.locator('tbody td').first());
  await action('row-after', table.locator('tbody td').first());
  await action('row-delete', table.locator('tbody tr').last().locator('td').first());
  await action('column-before', table.locator('tbody td').first());
  await action('column-after', table.locator('tbody td').first());
  await action('column-delete', table.locator('tbody tr').first().locator('td').last());
  await action('merge-right', table.locator('tbody td').first());
  await expect(table.locator('tbody td').first()).toHaveAttribute('colspan', '2');
  await action('split', table.locator('tbody td').first());
  await action('merge-down', table.locator('tbody td').first());
  await expect(table.locator('tbody td').first()).toHaveAttribute('rowspan', '2');
  await action('split', table.locator('tbody td').first());
  const image = '/theme/assets/library/simbioza/icon-adriatic-light.png';
  expect((await page.request.get(image)).ok()).toBe(true);
  await page.locator('[data-editor-html-source-toggle]').click();
  const current = await page.locator('[data-editor-html-source]').inputValue();
  await page.locator('[data-editor-html-source]').fill(current
    + [25, 50, 75, 100].map((size) => `<p><img id="media-${size}" src="${image}" alt="Size ${size}"></p>`).join('')
    + '<p><img id="media-reset" src="' + image + '" alt="Reset"></p>');
  await page.locator('[data-editor-html-source-toggle]').click();
  for (const [index, size] of [25, 50, 75, 100].entries()) {
    const media = page.locator(`[data-editor-html-surface] #media-${size}`);
    for (const value of [`width-${size}`, `spacing-${index}`, `align-${['left', 'center', 'right', 'left'][index]}`]) {
      await media.click(); await menu(page, 'Media', `[data-editor-html-media-action="${value}"]`);
    }
  }
  const reset = page.locator('[data-editor-html-surface] #media-reset');
  await reset.click(); await menu(page, 'Media', '[data-editor-html-media-action="width-25"]');
  await reset.click(); await menu(page, 'Media', '[data-editor-html-media-action="reset"]');
  await reset.click();
  page.once('dialog', (dialog) => dialog.accept('Changed alternative text'));
  await menu(page, 'Media', '[data-editor-html-media-action="image-alt"]');
  await reset.click(); await menu(page, 'Media', '[data-editor-html-media-action="image-decorative"]');
  const { stored } = await persist(page, path);
  expect(stored).not.toContain('contenteditable="true"');
  const view = page.locator('.editor-html-view-content');
  await expect(view.locator('table')).toHaveCount(1);
  await expect(view.locator('table tbody tr')).toHaveCount(2);
  for (const size of [25, 50, 75, 100]) {
    await expect(view.locator(`#media-${size}`)).toHaveClass(new RegExp(`w-${size}`));
  }
  await expect(view.locator('#media-reset')).toHaveAttribute('alt', '');
  await expect(view.locator('#media-reset')).toHaveAttribute('data-editor-html-decorative', '1');
});

test('intrinsic wide images stay inside content in the editor, published page and offline export', async ({ page, request }) => {
  test.setTimeout(120_000);
  const path = await editor(page, request, 'roundtrip-image-bounds');
  const documentId = new URL(path, 'http://localhost').searchParams.get('document');
  expect(documentId).toBeTruthy();

  // EN: Generate a real large image in memory; no remote image or checked-in binary fixture is needed.
  // HR: Stvori stvarnu veliku sliku u memoriji, bez udaljene slike ili binarne datoteke u repozitoriju.
  const png = Buffer.from(await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 2000;
    canvas.height = 1000;
    const context = canvas.getContext('2d');
    context.fillStyle = '#2255aa';
    context.fillRect(0, 0, 2000, 1000);
    context.fillStyle = '#ffdd33';
    context.fillRect(0, 0, 100, 1000);
    context.fillRect(1900, 0, 100, 1000);
    return canvas.toDataURL('image/png').split(',')[1];
  }), 'base64');
  expect(png.readUInt32BE(16)).toBe(2000);
  expect(png.readUInt32BE(20)).toBe(1000);
  const upload = await request.post(`/api/v1/pages/${documentId}/attachments?lang=en`, {
    headers: apiHeaders(adminApiToken),
    multipart: { attachment: { name: 'overflow-2000x1000.png', mimeType: 'image/png', buffer: png } },
  });
  expect(upload.status(), await upload.text()).toBe(201);
  const asset = await expectData(upload, 201);
  const imageSource = `/editor-html/asset/${asset.uuid}`;
  const image = (id, style = 'width:100%', classes = '') => `<img id="${id}" src="${imageSource}"`
    + ` alt="Overflow ${id}" width="2000" height="1000"`
    + (style ? ` style="${style}"` : '') + (classes ? ` class="${classes}"` : '') + '>';
  // EN: Use the native two-tab contract; the sanitizer deliberately rejects one-tab collections.
  // HR: Koristi nativni ugovor s dva taba; sanitizer namjerno odbacuje kolekcije sa samo jednim tabom.
  const tabs = (id, html) => `<section class="editor-html-tabs" data-editor-html-tabs="1"`
    + ` data-editor-html-tabs-id="${id}" contenteditable="false">`
    + '<div class="editor-html-tabs__list" role="tablist" aria-label="Image tabs">'
    + [0, 1].map((index) => `<button type="button" role="tab" id="${id}-tab-${index + 1}"`
      + ` class="editor-html-tabs__tab${index === 0 ? ' editor-html-tabs__tab--active' : ''}"`
      + ` aria-controls="${id}-panel-${index + 1}" aria-selected="${index === 0}"`
      + ` tabindex="${index === 0 ? 0 : -1}" data-editor-html-tab="${index}">`
      + `${index === 0 ? 'Image tab' : 'Second tab'}</button>`).join('')
    + '</div><div class="editor-html-tabs__panels">'
    + [html, '<p>Second tab text</p>'].map((body, index) => `<div role="tabpanel"`
      + ` class="editor-html-tabs__panel${index === 0 ? ' editor-html-tabs__panel--active' : ''}"`
      + ` id="${id}-panel-${index + 1}" aria-labelledby="${id}-tab-${index + 1}" tabindex="0"`
      + ` data-editor-html-tab-panel="${index}"${index === 0 ? '' : ' hidden="hidden"'}>${body}</div>`).join('')
    + '</div></section>';
  const accordion = (id, html) => `<section class="editor-html-accordion" data-editor-html-accordion="1"`
    + ` data-editor-html-accordion-id="${id}" contenteditable="false">`
    + '<details class="editor-html-accordion__item"><summary class="editor-html-accordion__title">Image accordion</summary>'
    + `<div class="editor-html-accordion__body">${html}</div></details></section>`;
  const card = (html) => `<div class="card"><div class="card-body">${html}</div></div>`;
  await source(page, '<h2>Image containment regression</h2>'
    + `<p>${image('overflow-percent')}</p><p>${image('overflow-pixels', 'width:2000px')}</p>`
    + `<p>${image('overflow-inline-height', 'width:2000px;height:1000px')}</p>`
    + `<figure class="figure w-100"><a href="${imageSource}">${image('overflow-figure')}</a>`
    + '<figcaption class="figure-caption">Semantic figure caption</figcaption></figure>'
    + '<span class="figure editor-html-media-figure editor-html-media-image w-100">'
    + `<a href="${imageSource}">${image('overflow-linked', 'width:2000px')}</a>`
    + '<span class="figure-caption">Linked editor figure caption</span></span>'
    + '<div class="text-end"><span class="figure editor-html-media-figure editor-html-media-image w-100 d-inline-block me-5">'
    + image('overflow-spaced-right') + '</span></div>'
    + '<div><span class="figure editor-html-media-figure editor-html-media-image w-100 d-inline-block mx-3">'
    + image('overflow-spaced-horizontal') + '</span></div>'
    + '<div><span class="figure editor-html-media-figure editor-html-media-image w-100 d-inline-block m-2">'
    + image('overflow-spaced-all') + '</span></div>'
    + card(`<p>${image('overflow-card')}</p>`)
    + tabs('overflow-tabs', `<p>${image('overflow-tab')}</p>`)
    + accordion('overflow-accordion', `<p>${image('overflow-accordion-image')}</p>`)
    + card(tabs('overflow-nested-tabs', accordion('overflow-nested-accordion', `<p>${image('overflow-nested')}</p>`)))
    + [25, 50, 75].map((size) => `<p>${image(`overflow-palette-${size}`, '', `w-${size}`)}</p>`).join(''));

  const checkGeometry = async (targetPage, selector, stage) => {
    for (const viewport of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
      await targetPage.setViewportSize(viewport);
      const content = targetPage.locator(selector);
      await expect(content).toBeVisible();
      // EN: Published/exported accordions start closed; open them as a reader before measuring every image.
      // HR: Objavljeni/izvezeni accordioni počinju zatvoreni; otvori ih kao čitatelj prije mjerenja svih slika.
      for (const details of await content.locator('details').all()) {
        if (!await details.evaluate((node) => node.open)) await details.locator(':scope > summary').click();
      }
      await expect(content.locator('img[alt^="Overflow "]')).toHaveCount(15);
      await expect.poll(() => content.locator('img[alt^="Overflow "]').evaluateAll((images) => images.every(
        (node) => node.complete && node.naturalWidth > 0,
      ))).toBe(true);
      const geometry = await content.evaluate((root) => {
        const box = (node) => {
          const rect = node.getBoundingClientRect();
          const style = getComputedStyle(node);
          const left = rect.left + parseFloat(style.borderLeftWidth) + parseFloat(style.paddingLeft);
          return { left, right: left + node.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight) };
        };
        const overflow = [];
        const images = Array.from(root.querySelectorAll('img[alt^="Overflow "]')).map((node) => {
          const rect = node.getBoundingClientRect();
          let child = node;
          while (child !== root && child.parentElement) {
            const parent = child.parentElement;
            const boundary = box(parent);
            const childRect = child.getBoundingClientRect();
            const childStyle = getComputedStyle(child);
            // EN: A figure's selected spacing must fit too, not only its border box.
            // HR: Mora stati i odabrani razmak figure, ne samo njezin rubni okvir.
            const figure = child.matches('figure, span.figure');
            const left = childRect.left - (figure ? parseFloat(childStyle.marginLeft) || 0 : 0);
            const right = childRect.right + (figure ? parseFloat(childStyle.marginRight) || 0 : 0);
            if (parent.clientWidth > 0 && (left < boundary.left - 2 || right > boundary.right + 2)) {
              overflow.push({ image: node.alt, wrapper: child.tagName, left, right, boundary });
            }
            child = parent;
          }
          const paragraph = node.closest('p');
          const paragraphWidth = paragraph ? box(paragraph).right - box(paragraph).left : 0;
          return { alt: node.alt, width: rect.width, height: rect.height, paragraphWidth };
        });
        const rect = root.getBoundingClientRect();
        return { images, overflow, clientWidth: root.clientWidth, scrollWidth: root.scrollWidth,
          left: rect.left, right: rect.right, viewport: innerWidth };
      });
      await test.info().attach(`${stage}-${viewport.width}-image-geometry`, {
        body: JSON.stringify(geometry, null, 2), contentType: 'application/json',
      });
      expect(geometry.overflow).toEqual([]);
      expect(geometry.scrollWidth).toBeLessThanOrEqual(geometry.clientWidth + 2);
      expect(geometry.left).toBeGreaterThanOrEqual(-2);
      expect(geometry.right).toBeLessThanOrEqual(geometry.viewport + 2);
      for (const measured of geometry.images) {
        expect(measured.width, measured.alt).toBeGreaterThan(0);
        // EN: The web variant may be smaller than the original, but must retain its 2:1 aspect ratio.
        // HR: Web izvedenica može biti manja od izvornika, ali mora zadržati njegov omjer 2 : 1.
        expect(Math.abs(measured.height - measured.width / 2), measured.alt).toBeLessThanOrEqual(2);
        if (viewport.width === 1440 && measured.alt.includes('overflow-palette-')) {
          const percentage = Number(measured.alt.split('-').at(-1));
          expect(Math.abs(measured.width - measured.paragraphWidth * percentage / 100), measured.alt).toBeLessThanOrEqual(2);
        }
      }
    }
  };

  await checkGeometry(page, '[data-editor-html-surface]', 'editor');
  await page.setViewportSize({ width: 1440, height: 900 });
  const { stored } = await persist(page, path);
  for (const size of [25, 50, 75]) expect(stored).toContain(`w-${size}`);
  await expect(page.locator('.editor-html-view-content')).toContainText('Linked editor figure caption');
  await checkGeometry(page, '.editor-html-view-content', 'published');

  await page.setViewportSize({ width: 1440, height: 900 });
  const downloadPromise = page.waitForEvent('download');
  await page.locator('a.editor-html-view-action[href*="/editor-html/export"]').click();
  const download = await downloadPromise;
  expect(download.suggestedFilename()).toMatch(/\.zip$/);
  const archivePath = await download.path();
  expect(archivePath).toBeTruthy();
  const directory = await mkdtemp(join(tmpdir(), 'simbioza-image-containment-'));
  const offline = await page.context().newPage();
  try {
    execFileSync('php', ['-r', '$zip = new ZipArchive(); if ($zip->open($argv[1]) !== true'
      + ' || !$zip->extractTo($argv[2])) { exit(2); } $zip->close();', archivePath, directory], { timeout: 15_000 });
    await offline.goto(pathToFileURL(join(directory, 'index.html')).href);
    await checkGeometry(offline, '#editor-html-standalone-content', 'offline-export');
    await expect(offline.locator('#editor-html-standalone-content')).toContainText('Semantic figure caption');
  } finally {
    await offline.close();
    await rm(directory, { recursive: true, force: true });
  }
});

test('all native dynamic element types and chart/timeline variants survive save and render', async ({ page, request }) => {
  test.setTimeout(240_000);
  const path = await editor(page, request, 'roundtrip-dynamic');
  await source(page, '<h2>All dynamic elements</h2>');
  for (const type of ['tabs', 'accordion', 'dropdown']) {
    await end(page); await menu(page, 'Dynamic elements', `[data-editor-html-${type}-open]`);
    const modal = page.locator(`#editor-html-${type}-modal`);
    if (type === 'dropdown') {
      await modal.locator('[data-editor-html-dropdown-title]').fill('Dropdown title');
      await modal.locator('[data-editor-html-dropdown-initially-open]').check();
    } else {
      await modal.locator(`[data-editor-html-${type}-items]`).fill('First\nSecond');
    }
    await modal.locator(`[data-editor-html-${type}-save]`).click();
    await expect(modal).toBeHidden();
    const block = page.locator(`[data-editor-html-surface] [data-editor-html-${type}="1"]`).last();
    if (type === 'tabs') {
      await block.locator('[role="tabpanel"]').nth(0).fill('Tab first body');
      await block.locator('[role="tabpanel"]').nth(1).fill('Tab second body');
    } else {
      await block.locator('details > div').first().fill(`${type} body`);
    }
  }
  const types = ['bar', 'line', 'area', 'pie', 'doughnut'];
  for (const [index, type] of types.entries()) {
    await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-chart-open]');
    const modal = page.locator('#editor-html-chart-modal');
    await modal.locator('[data-editor-html-chart-type]').selectOption(type);
    await modal.locator('[data-editor-html-chart-title]').fill(`Chart ${type}`);
    await modal.locator('[data-editor-html-chart-3d]').setChecked(index % 2 === 0);
    await modal.locator('[data-editor-html-chart-legend-position]').selectOption(['top', 'bottom', 'left', 'right', 'top'][index]);
    await modal.locator('[data-editor-html-chart-width]').selectOption(['25', '50', '75', '100', '100'][index]);
    await modal.locator('[data-editor-html-chart-alignment]').selectOption(['left', 'center', 'right', 'left', 'center'][index]);
    await modal.locator('[data-editor-html-chart-save]').click();
    await expect(modal).toBeHidden();
  }
  for (const display of ['day', 'week', 'month', 'quarter']) {
    await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-roadmap-open]');
    const modal = page.locator('#editor-html-roadmap-modal');
    await modal.locator('[data-editor-html-roadmap-title]').fill(`Roadmap ${display}`);
    await modal.locator('[data-editor-html-roadmap-display]').selectOption(display);
    await modal.locator('[data-editor-html-roadmap-save]').click();
    await expect(modal).toBeHidden();
  }
  for (const mode of ['list', 'table']) {
    await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-task-open]');
    const modal = page.locator('#editor-html-task-modal');
    await modal.locator('[data-editor-html-task-title]').fill(`Tasks ${mode}`);
    await modal.locator('[data-editor-html-task-view]').selectOption(mode);
    await modal.locator('[data-editor-html-task-items]').fill('First task\nSecond task');
    await modal.locator('[data-editor-html-task-save]').click();
    await expect(modal).toBeHidden();
  }
  for (const kind of ['page-report', 'attachment-gallery', 'workspace-search', 'recent-changes']) {
    await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-workspace-block-open]');
    const modal = page.locator('#editor-html-workspace-block-modal');
    await modal.locator('[data-editor-html-workspace-block-kind]').selectOption(kind);
    await modal.locator('[data-editor-html-workspace-block-title]').fill(`Workspace ${kind}`);
    await modal.locator('[data-editor-html-workspace-block-insert]').click();
    await expect(modal).toBeHidden();
  }
  await end(page); await menu(page, 'Insert', '[data-editor-html-iframe-open]');
  const frameModal = page.locator('#editor-html-iframe-modal');
  await frameModal.locator('[data-editor-html-iframe-code]').fill('https://example.com/embed');
  await frameModal.locator('[data-editor-html-iframe-title]').fill('Safe HTTPS iframe');
  await frameModal.locator('[data-editor-html-iframe-save]').click();
  await expect(frameModal).toBeHidden();
  const { stored } = await persist(page, path);
  for (const name of ['tabs', 'accordion', 'dropdown', 'chart', 'roadmap', 'task-list', 'workspace-block', 'iframe']) {
    expect(stored).toContain(`data-editor-html-${name}="1"`);
  }
  const view = page.locator('.editor-html-view-content');
  await expect(view.locator('.editor-html-chart-svg')).toHaveCount(5);
  await expect(view.locator('.editor-html-roadmap-view')).toHaveCount(4);
  await expect(view.locator('.editor-task-checkbox')).toHaveCount(4);
  await expect(view.locator('[data-editor-html-tabs="1"] [role="tab"]')).toHaveCount(2);
  const tabs = view.locator('[data-editor-html-tabs="1"]');
  await tabs.getByRole('tab', { name: 'Second', exact: true }).click();
  await expect(tabs.locator('[role="tabpanel"]').nth(1)).toBeVisible();
  await expect(tabs.locator('[role="tabpanel"]').nth(1)).toHaveText('Tab second body');
  const accordion = view.locator('[data-editor-html-accordion="1"]');
  await accordion.locator('summary').first().click();
  await expect(accordion.locator('details > div').first()).toBeVisible();
  await expect(accordion.locator('details > div').first()).toHaveText('accordion body');
  await expect(view.locator('[data-editor-html-dropdown="1"] details')).toHaveAttribute('open', '');
  await expect(view.locator('iframe')).toHaveAttribute('sandbox', 'allow-scripts allow-presentation');
  await expect(view.locator('iframe')).toHaveAttribute('src', 'https://example.com/embed');
  await page.goto(path);
  for (const [type, marker] of [['tabs', 'tabs'], ['accordion', 'accordion'], ['dropdown', 'dropdown'],
    ['chart', 'chart'], ['roadmap', 'roadmap'], ['task', 'task-list'], ['workspace-block', 'workspace-block'],
    ['iframe', 'iframe']]) await editAndRemove(page, type, marker);
  const removed = await persist(page, path);
  for (const marker of ['tabs', 'accordion', 'dropdown', 'chart', 'roadmap', 'task-list', 'workspace-block', 'iframe']) {
    expect(removed.stored).not.toContain(`data-editor-html-${marker}="1"`);
  }
});

test('internal links, included page content, every calendar view and iframe code survive persistence', async ({ page, request }) => {
  test.setTimeout(180_000);
  const targetPath = await createEditorSurface(request, adminApiToken, 'roundtrip-include-target');
  const target = new URL(targetPath, 'http://localhost').searchParams.get('document');
  const draft = await getDataWithEtag(request, `/api/v1/pages/${target}/draft?lang=en`, apiHeaders(adminApiToken));
  await expectData(await request.post(`/api/v1/pages/${target}/publish?lang=en`, {
    headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('roundtrip-target'), 'If-Match': draft.etag }),
    data: {},
  }));
  const calendars = [];
  for (const name of ['First', 'Second']) {
    calendars.push(await expectData(await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('roundtrip-calendar') }),
      data: { name: `Roundtrip ${name}`, type: 'team', color: '#1677ff', is_enabled: true },
    }), 201));
  }
  const path = await editor(page, request, 'roundtrip-integrations');
  await source(page, '<h2>Integrated elements</h2>');
  await end(page); await page.locator('[data-editor-html-link]').click();
  const link = page.locator('#editor-html-link-modal');
  await link.locator('[data-editor-html-link-page-button]').click();
  const pageButtons = link.locator('[data-editor-html-link-page-list] button');
  await expect.poll(() => pageButtons.count()).toBeGreaterThan(0);
  await link.locator(`[data-link-document-key="${target}"]`).click();
  await link.locator('[data-editor-html-link-internal-insert]').click();
  await expect(link).toBeHidden();
  await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-document-include-open]');
  const include = page.locator('#editor-html-document-include-modal');
  await include.locator('[data-editor-html-document-include-page-button]').click();
  const includedPages = include.locator('[data-editor-html-document-include-page-list] button');
  await expect.poll(() => includedPages.count()).toBeGreaterThan(0);
  await include.locator(`[data-include-document-key="${target}"]`).click();
  await include.locator('[data-editor-html-document-include-insert]').click();
  await expect(include).toBeHidden();
  for (const [index, view] of ['month', 'week', 'day'].entries()) {
    await end(page); await menu(page, 'Dynamic elements', '[data-editor-html-calendar-open]');
    const modal = page.locator('#editor-html-calendar-modal');
    await modal.locator('[data-editor-html-calendar-dropdown] > button').click();
    for (const calendar of calendars) await modal.locator(`[data-editor-html-calendar-uuid][value="${calendar.uuid}"]`).check();
    await modal.locator('[data-editor-html-calendar-title]').fill(`Calendar ${view}`);
    await modal.locator('[data-editor-html-calendar-view]').selectOption(view);
    await modal.locator('[data-editor-html-calendar-date-mode]').selectOption(index === 0 ? 'today' : 'fixed');
    if (index > 0) await modal.locator('[data-editor-html-calendar-date]').fill('2026-10-23');
    await modal.locator('[data-editor-html-calendar-insert]').click();
    await expect(modal).toBeHidden();
  }
  await end(page); await menu(page, 'Insert', '[data-editor-html-iframe-open]');
  const iframe = page.locator('#editor-html-iframe-modal');
  await iframe.locator('[data-editor-html-iframe-code]').fill('<iframe src="https://example.com/frame" title="Pasted frame" height="600" allowfullscreen></iframe>');
  await iframe.locator('[data-editor-html-iframe-title]').fill('Pasted iframe');
  await iframe.locator('[data-editor-html-iframe-save]').click();
  await expect(iframe).toBeHidden();
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  await expect(page.locator('[data-editor-external-scripts]')).toBeVisible();
  await page.getByRole('button', { name: 'External scripts', exact: true }).click();
  const { stored } = await persist(page, path);
  expect(stored).toContain('data-editor-html-include="1"');
  expect(stored).toContain('data-include-target=');
  for (const view of ['month', 'week', 'day']) expect(stored).toContain(`data-calendar-view="${view}"`);
  for (const calendar of calendars) expect(stored).toContain(calendar.uuid);
  const view = page.locator('.editor-html-view-content');
  await expect(view.locator('iframe')).toHaveAttribute('src', 'https://example.com/frame');
  await expect(view.locator('iframe')).toHaveAttribute('sandbox', 'allow-scripts allow-presentation');
  await expect(view).toContainText('Temporary browser test document.');
  await expect(view.locator('[data-calendar-embed-rendered="1"]')).toHaveCount(3);
  await page.goto(path);
  await editAndRemove(page, 'document-include', 'include');
  await editAndRemove(page, 'calendar');
  await editAndRemove(page, 'iframe');
  const removed = await persist(page, path);
  for (const marker of ['include', 'calendar', 'iframe']) expect(removed.stored).not.toContain(`data-editor-html-${marker}="1"`);
});

test('modal insertions keep the selection and structured HTML inside editable dynamic bodies', async ({ page, request }) => {
  test.setTimeout(120_000);
  const path = await editor(page, request, 'roundtrip-nested');
  await source(page, '<h2>Nested dynamic content</h2>');
  for (const type of ['tabs', 'accordion', 'dropdown']) {
    await end(page); await menu(page, 'Dynamic elements', `[data-editor-html-${type}-open]`);
    const modal = page.locator(`#editor-html-${type}-modal`);
    if (type === 'dropdown') await modal.locator('[data-editor-html-dropdown-title]').fill('Nested dropdown');
    await modal.locator(`[data-editor-html-${type}-save]`).click();
    await expect(modal).toBeHidden();
    const block = page.locator(`[data-editor-html-surface] [data-editor-html-${type}="1"]`).last();
    const body = type === 'tabs' ? block.locator('[role="tabpanel"]').first() : block.locator('details > div').first();
    await body.fill(`Nested ${type} body`);
    await body.evaluate((element) => {
      element.focus();
      const range = document.createRange();
      range.selectNodeContents(element); range.collapse(false);
      const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(range);
    });
    await menu(page, 'Dynamic elements', '[data-editor-html-chart-open]');
    const chart = page.locator('#editor-html-chart-modal');
    await chart.locator('[data-editor-html-chart-title]').fill(`Nested chart ${type}`);
    await chart.locator('[data-editor-html-chart-save]').click();
    await expect(chart).toBeHidden();
    await expect(body.locator('[data-editor-html-chart="1"]')).toHaveCount(1);
    if (type !== 'tabs') {
      await menu(page, 'Dynamic elements', '[data-editor-html-tabs-open]');
      const nestedTabs = page.locator('#editor-html-tabs-modal');
      await nestedTabs.locator('[data-editor-html-tabs-save]').click();
      await expect(nestedTabs).toBeHidden();
      await expect(body.locator('[data-editor-html-tabs="1"]')).toHaveCount(1);
    }
  }
  await persist(page, path);
  await expect(page.locator('.editor-html-view-content .editor-html-chart-svg')).toHaveCount(3);
});
