import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  idempotencyKey,
  login,
} from './helpers.js';

const {
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
  adminApiToken: apiToken,
} = e2eEnvironment();

/**
 * HR: Predaje stvarni HTML obrazac i potvrđuje odgovor očekivane POST rute bez
 * oslanjanja na utrku događaja učitavanja stranice.
 * EN: Submits the real HTML form and verifies the expected POST route response
 * without relying on a page-load event race.
 */
async function submitFormAndExpectPost(page, button, expectedPath) {
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === expectedPath);

  await button.evaluate((control) => {
    if (!(control instanceof HTMLButtonElement) || !(control.form instanceof HTMLFormElement)) {
      throw new Error('The selected submit control is not attached to an HTML form.');
    }

    control.form.requestSubmit(control);
  });

  const response = await responsePromise;
  expect([200, 302, 303]).toContain(response.status());
}

test.describe('browser flows', () => {
  test('mobile navigation, hero artwork, equal hero sizes, and edge-to-edge layout work', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const homeResponse = await page.goto('/');

    expect(homeResponse?.status()).toBe(200);
    await expect(page.locator('.hph-hero')).toBeVisible();

    const visual = page.locator('.hph-hero__visual img');
    await expect(visual).toBeVisible();
    await expect.poll(() => visual.evaluate((image) => image.naturalWidth)).toBeGreaterThan(0);

    const menuButton = page.getByRole('button', { name: /Menu|Izbornik/i });
    const drawer = page.locator('.hph-primary-navigation__drawer');
    await expect(menuButton).toBeVisible();
    await expect(drawer).toBeHidden();
    await menuButton.click();
    await expect(drawer).toBeVisible();
    await expect(drawer.getByRole('link', { name: /Workspaces|Područja/i })).toBeVisible();
    await drawer.getByRole('button', { name: /Close|Zatvori/i }).click();
    await expect(drawer).toBeHidden();

    const homeHeroHeight = await page.locator('.hph-hero').evaluate(
      (element) => element.getBoundingClientRect().height,
    );
    const homeHeroStageHeight = await page.locator('.hph-hero__stage').evaluate(
      (element) => element.getBoundingClientRect().height,
    );
    expect(homeHeroStageHeight).toBeGreaterThanOrEqual(256);
    await page.goto('/about');
    const innerHeroHeight = await page.locator('.hph-hero').evaluate(
      (element) => element.getBoundingClientRect().height,
    );
    expect(Math.abs(homeHeroHeight - innerHeroHeight)).toBeLessThanOrEqual(1);

    const layout = await page.locator('.hph-hero').evaluate((element) => ({
      right: element.getBoundingClientRect().right,
      viewportWidth: window.innerWidth,
      documentWidth: document.documentElement.scrollWidth,
    }));
    expect(Math.abs(layout.right - layout.viewportWidth)).toBeLessThanOrEqual(1);
    expect(layout.documentWidth).toBeLessThanOrEqual(layout.viewportWidth);

    await page.goto('/workspaces');
    const mobileContentGeometry = await page.locator('.hph-page-stage').evaluate((stage) => {
      const hero = stage.querySelector('.hph-hero')?.getBoundingClientRect();
      const heroContent = stage.querySelector('.hph-hero__content')?.getBoundingClientRect();
      const main = stage.querySelector('.hph-main-content')?.getBoundingClientRect();

      return {
        contentClearance: (main?.top ?? 0) - (heroContent?.bottom ?? 0),
        contentOverlap: (hero?.bottom ?? 0) - (main?.top ?? 0),
      };
    });
    expect(mobileContentGeometry.contentOverlap).toBeGreaterThanOrEqual(120);
    expect(mobileContentGeometry.contentClearance).toBeGreaterThanOrEqual(16);
  });

  test('guest is redirected, administrator is authorized, and logout clears the session', async ({ page }) => {
    await page.goto('/settings/auth');
    await expect(page).toHaveURL(/\/auth\/login\?next=%2Fsettings%2Fauth/);

    await login(page, adminLogin, adminPassword);
    const settingsResponse = await page.goto('/settings/auth');
    expect(settingsResponse?.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText(/Access denied|Pristup nije dozvoljen/i);

    await page.goto('/auth/logout');
    await expect(page).toHaveURL(/\/auth\/login$/);
    await expect(page.locator('#local_override_login')).toBeVisible();
  });

  test('authenticated non-administrator receives a real 403 response', async ({ page }) => {
    await login(page, userLogin, userPassword);
    const response = await page.goto('/settings/auth');

    expect(response?.status()).toBe(403);
    await expect(page.locator('body')).toContainText(/Access denied|Pristup nije dozvoljen/i);
  });

  test('administrator publishes content while drafts and immutable versions remain separated', async ({ page, request }) => {
    test.setTimeout(90_000);

    const workspaceSlug = 'e2e-content-workspace';
    const pageSlug = 'e2e-published-page';
    const firstPublishedBody = 'First published body from the isolated E2E test.';
    const secondDraftBody = 'Second draft body that must stay private until publication.';

    await login(page, adminLogin, adminPassword);
    await page.goto('/workspaces/manage');
    await page.getByRole('textbox', { name: 'Name', exact: true }).fill('E2E Content Workspace');
    await page.getByRole('textbox', { name: 'Slug', exact: true }).fill(workspaceSlug);
    await page.getByRole('textbox', { name: 'Description' }).fill(
      'Temporary workspace for content lifecycle automation.',
    );
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save', exact: true }),
      '/workspaces/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === '/workspaces/manage'
      && url.searchParams.get('workspace') === workspaceSlug);
    await expect(page.getByRole('link', { name: 'Open Workspace' })).toBeVisible();

    await page.getByRole('link', { name: 'Open Workspace' }).click();
    await page.getByRole('button', { name: 'New page' }).click();
    await page.getByRole('textbox', { name: 'Page title' }).fill('E2E Published Page');
    await page.getByRole('textbox', { name: 'Slug', exact: true }).fill(pageSlug);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Create and edit' }),
      '/workspaces/page/create',
    );
    await expect(page).toHaveURL((url) => url.pathname === '/editor-html'
      && url.searchParams.get('document') === pageSlug);

    const editorSurface = page.locator('[data-editor-html-surface]');
    await expect(editorSurface).toBeVisible();
    await editorSurface.fill(firstPublishedBody);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save and publish' }),
      '/editor-html/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
      && url.searchParams.get('saved') === '1');
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();

    await page.getByRole('link', { name: 'Edit', exact: true }).click();
    await expect(editorSurface).toBeVisible();
    await editorSurface.fill(secondDraftBody);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save', exact: true }),
      '/editor-html/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === '/editor-html'
      && url.searchParams.get('saved') === '1');
    await expect(page.getByText('Shared draft', { exact: true })).toBeVisible();

    await page.getByRole('link', { name: 'View', exact: true }).click();
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();
    await expect(page.getByText(secondDraftBody, { exact: true })).toHaveCount(0);

    await page.getByRole('link', { name: 'Edit draft', exact: true }).click();
    await expect(editorSurface).toContainText(secondDraftBody);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save and publish' }),
      '/editor-html/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
      && url.searchParams.get('saved') === '1');
    await expect(page.getByRole('heading', { name: secondDraftBody, exact: true })).toBeVisible();
    await expect(page.getByText(firstPublishedBody, { exact: true })).toHaveCount(0);

    await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('browser-workspace-public-acl'),
      }),
      data: {
        subjects: [{
          type: 'public',
          permissions: {
            can_view: true,
            can_add: false,
            can_edit: false,
            can_publish: false,
            can_delete: false,
            can_manage: false,
          },
        }],
      },
    }));
    const tree = await expectData(await request.get(
      `/api/v1/workspaces/${workspaceSlug}/tree?lang=en`,
      { headers: apiHeaders(apiToken) },
    ));
    const publishedNode = tree.find((node) => node.slug === pageSlug);
    expect(publishedNode?.id).toBeTruthy();

    await page.getByRole('link', { name: /Shorts|Summaries|Sažetci/i }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/shorts`);
    await expect(page.getByRole('heading', {
      name: /^(Shorts|Summaries|Sažetci) · /i,
    })).toBeVisible();
    await expect(page.locator('.workspace-short-card')).toContainText(secondDraftBody);
    await expect(page.getByRole('link', { name: /Read more|Pročitaj više/i })).toBeVisible();
    await expect(page.locator('#workspace-shorts-depth')).toHaveValue('2');
    await expect(page.locator('#workspace-shorts-limit')).toHaveValue('10');
    await expect(page.locator('#workspace-shorts-order')).toHaveValue('newest');
    await expect(page.locator('#workspace-shorts-limit option[value="all"]')).toBeEnabled();
    const excerptGeometry = await page.locator('.workspace-short-excerpt').evaluate((excerpt) => {
      const style = getComputedStyle(excerpt);
      const fade = getComputedStyle(excerpt, '::after');

      return {
        maxHeight: Number.parseFloat(style.maxHeight),
        lineHeight: Number.parseFloat(style.lineHeight),
        overflow: style.overflow,
        fadeBackground: fade.backgroundImage,
      };
    });
    expect(excerptGeometry.maxHeight / excerptGeometry.lineHeight).toBeCloseTo(12, 1);
    expect(excerptGeometry.overflow).toBe('hidden');
    expect(excerptGeometry.fadeBackground).toContain('linear-gradient');

    await expect(page.locator('#workspace-page-tree')).toHaveClass(/\bshow\b/);
    await expect(page.locator('#workspace-shorts-display-options')).not.toHaveClass(/\bshow\b/);
    await expect(page.getByRole('button', { name: 'Page tree', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Display options', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Page tree', exact: true })).toHaveText('');
    await expect(page.getByRole('button', { name: 'Display options', exact: true })).toHaveText('');

    await page.goto(`/workspace/${workspaceSlug}/shorts?lang=en&tree=0&options=0`);
    await expect(page.getByRole('heading', { name: /^Summaries · / })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Page tree', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Display options', exact: true })).toBeVisible();
    await expect(page.locator('#workspace-page-tree')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#workspace-shorts-display-options')).not.toHaveClass(/\bshow\b/);
    await page.getByRole('button', { name: 'Display options', exact: true }).click();
    await expect(page.locator('#workspace-shorts-display-options')).toHaveClass(/\bshow\b/);
    await expect(page.getByLabel('Displayed levels')).toHaveValue('2');
    await expect(page.getByLabel('Number of articles')).toHaveValue('10');
    await expect(page.getByLabel('Order')).toHaveValue('newest');

    const croatianShorts = `/workspace/${workspaceSlug}/shorts?lang=en&tree=0&options=0`;
    await page.goto(`/locale/hr?next=${encodeURIComponent(croatianShorts)}`);
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/shorts`
      && url.searchParams.get('lang') === 'hr'
      && url.searchParams.get('tree') === '0'
      && url.searchParams.get('options') === '0');
    await expect(page.getByRole('heading', { name: /^Sažetci · / })).toBeVisible();
    await expect(page.locator('.workspace-short-card')).toContainText(secondDraftBody);

    const currentShortsUrl = new URL(page.url());
    const currentShortsPath = `${currentShortsUrl.pathname}${currentShortsUrl.search}`;
    await page.goto(`/locale/en?next=${encodeURIComponent(currentShortsPath)}`);
    await expect(page.getByRole('heading', { name: /^Summaries · / })).toBeVisible();

    await page.getByRole('link', { name: /Read more|Pročitaj više/i }).click();
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.reload();
    await page.getByRole('button', { name: /Edit tree|Uredi stablo/i }).click();
    const editTreeItem = page.getByRole('button', {
      name: /Edit item: E2E Published Page|Uredi stavku: E2E Published Page/i,
    });
    await expect(editTreeItem).toBeVisible();
    await editTreeItem.click();

    const nodeDialog = page.locator('#workspace-node-editor-modal');
    await expect(nodeDialog).toBeVisible();
    const stacking = await nodeDialog.evaluate((modal) => {
      const backdrop = document.querySelector('.modal-backdrop');

      return {
        directBodyChild: modal.parentElement === document.body,
        modalZIndex: Number.parseInt(getComputedStyle(modal).zIndex, 10),
        backdropZIndex: backdrop instanceof HTMLElement
          ? Number.parseInt(getComputedStyle(backdrop).zIndex, 10)
          : 0,
      };
    });
    expect(stacking.directBodyChild).toBe(true);
    expect(stacking.modalZIndex).toBeGreaterThan(stacking.backdropZIndex);

    const publicAclRow = nodeDialog.getByRole('row').filter({ hasText: /Public|Javno/i });
    const inheritedView = publicAclRow.locator('.workspace-acl-checkbox-inherited').first();
    const directView = publicAclRow.locator('.workspace-acl-checkbox-direct').first();
    await expect(inheritedView).toBeChecked();
    await expect(inheritedView).toBeDisabled();
    await expect(directView).toBeEnabled();
    await expect(directView).not.toBeChecked();
    await directView.check();

    const aclColors = await publicAclRow.evaluate((row) => {
      const inherited = row.querySelector('.workspace-acl-checkbox-inherited');
      const direct = row.querySelector('.workspace-acl-checkbox-direct');
      if (!(inherited instanceof HTMLElement) || !(direct instanceof HTMLElement)) {
        throw new Error('ACL color controls are missing.');
      }

      return {
        inherited: getComputedStyle(inherited).backgroundColor,
        direct: getComputedStyle(direct).backgroundColor,
        body: getComputedStyle(document.body).backgroundColor,
      };
    });
    expect(aclColors.inherited).not.toBe(aclColors.direct);
    expect(aclColors.inherited).not.toBe(aclColors.body);
    expect(aclColors.direct).not.toBe(aclColors.body);

    await submitFormAndExpectPost(
      page,
      nodeDialog.getByRole('button', { name: /Save restrictions|Spremi ograničenja/i }),
      '/workspaces/node/acl',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`);
    await page.getByRole('button', { name: /Edit tree|Uredi stablo/i }).click();
    await expect(editTreeItem).toBeVisible();
    await editTreeItem.click();
    await expect(nodeDialog).toBeVisible();
    await expect(
      nodeDialog.getByRole('row').filter({ hasText: /Public|Javno/i })
        .locator('.workspace-acl-checkbox-direct').first(),
    ).toBeChecked();

    const titleInput = nodeDialog.locator('input[name="title"]');
    await expect(titleInput).toBeEnabled();
    await titleInput.fill('E2E Published Page Renamed');
    await submitFormAndExpectPost(
      page,
      nodeDialog.getByRole('button', { name: /Save item|Spremi stavku/i }),
      '/workspaces/node/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`);
    await expect(page.getByRole('link', {
      name: 'E2E Published Page Renamed',
      exact: true,
    })).toBeVisible();

    await page.getByRole('button', { name: 'History' }).click();
    const historyDialog = page.getByRole('dialog', { name: 'Version history' });
    await expect(historyDialog).toBeVisible();
    await expect(historyDialog.getByRole('row')).toHaveCount(4);
    await expect(historyDialog).toContainText('#3');
    await expect(historyDialog).toContainText('#2');
    await expect(historyDialog).toContainText('#1');

    const firstPublicationLink = historyDialog.locator('a[href*="version=2"]');
    await expect(firstPublicationLink).toHaveCount(1);
    const firstPublicationUrl = await firstPublicationLink.getAttribute('href');
    expect(firstPublicationUrl).not.toBeNull();
    const firstPublicationResponse = await page.goto(firstPublicationUrl);
    expect(firstPublicationResponse?.status()).toBe(200);
    await expect(page).toHaveURL((url) => url.pathname === '/editor-html/version'
      && url.searchParams.get('version') === '2');
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();
    await expect(page.getByText(secondDraftBody, { exact: true })).toHaveCount(0);
  });
});

test.describe.serial('versioned API flows', () => {
  test('missing and invalid keys return the same RFC problem response', async ({ request }) => {
    const missing = await request.get('/api/v1');
    const invalid = await request.get('/api/v1', {
      headers: { Authorization: 'Bearer definitely-not-a-valid-key' },
    });

    expect(missing.status()).toBe(401);
    expect(invalid.status()).toBe(401);
    expect(missing.headers()['content-type']).toContain('application/problem+json');
    expect(invalid.headers()['content-type']).toContain('application/problem+json');

    const missingBody = await missing.json();
    const invalidBody = await invalid.json();
    expect(missingBody.status).toBe(401);
    expect(invalidBody.status).toBe(401);
    expect(missingBody.code).toBe('invalid_api_key');
    expect(invalidBody.code).toBe('invalid_api_key');
    expect(JSON.stringify(invalidBody)).not.toContain('definitely-not-a-valid-key');
  });

  test('valid key exposes discovery and its safe owner profile', async ({ request }) => {
    const headers = { Authorization: `Bearer ${apiToken}` };
    const discovery = await request.get('/api/v1', { headers });
    const profile = await request.get('/api/v1/me', { headers });

    expect(discovery.status()).toBe(200);
    expect(profile.status()).toBe(200);

    const discoveryBody = await discovery.json();
    const profileBody = await profile.json();
    expect(discoveryBody.data.name).toBe('HeartPhrame API');
    expect(discoveryBody.data.version).toBe('v1');
    expect(discoveryBody.data.resources).toContain('workspace');
    expect(profileBody.data.user.login_identifier).toBe(adminLogin);
    expect(profileBody.data.user.is_admin).toBe(true);
    expect(JSON.stringify(profileBody)).not.toContain('password_hash');
  });

  test('administrator key completes a Workspace create and read cycle', async ({ request }) => {
    const headers = { Authorization: `Bearer ${apiToken}` };
    const created = await request.post('/api/v1/workspaces', {
      headers,
      data: {
        name: 'E2E Workspace',
        slug: 'e2e-workspace',
        description: 'Created by the isolated browser and API E2E suite.',
        visibility: 'restricted',
      },
    });

    expect(created.status()).toBe(201);
    expect(created.headers().location).toContain('/api/v1/workspaces/e2e-workspace');
    const createdBody = await created.json();
    expect(createdBody.data.slug).toBe('e2e-workspace');
    expect(createdBody.data.permissions.can_manage).toBe(true);

    const fetched = await request.get('/api/v1/workspaces/e2e-workspace', { headers });
    expect(fetched.status()).toBe(200);
    const fetchedBody = await fetched.json();
    expect(fetchedBody.data.name).toBe('E2E Workspace');
    expect(fetchedBody.data.visibility).toBe('restricted');
  });
});
