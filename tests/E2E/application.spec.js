import { expect, test } from '@playwright/test';

const adminLogin = process.env.HPH_E2E_ADMIN_LOGIN;
const adminPassword = process.env.HPH_E2E_ADMIN_PASSWORD;
const userLogin = process.env.HPH_E2E_USER_LOGIN;
const userPassword = process.env.HPH_E2E_USER_PASSWORD;
const apiToken = process.env.HPH_E2E_API_TOKEN;

for (const [name, value] of Object.entries({
  HPH_E2E_ADMIN_LOGIN: adminLogin,
  HPH_E2E_ADMIN_PASSWORD: adminPassword,
  HPH_E2E_USER_LOGIN: userLogin,
  HPH_E2E_USER_PASSWORD: userPassword,
  HPH_E2E_API_TOKEN: apiToken,
})) {
  if (!value) {
    throw new Error(`${name} is required. Run the suite through composer e2e.`);
  }
}

async function login(page, loginIdentifier, password) {
  await page.goto('/auth/login');
  await page.locator('#auth_login').fill(loginIdentifier);
  await page.locator('#auth_password').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.startsWith('/auth/login')),
    page.locator('#local_override_login button[type="submit"]').click(),
  ]);
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

  test('administrator publishes content while drafts and immutable versions remain separated', async ({ page }) => {
    test.setTimeout(60_000);

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
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/workspaces/manage'
        && url.searchParams.get('workspace') === workspaceSlug),
      page.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);

    await page.getByRole('link', { name: 'Open Workspace' }).click();
    await page.getByRole('button', { name: 'New page' }).click();
    await page.getByRole('textbox', { name: 'Page title' }).fill('E2E Published Page');
    await page.getByRole('textbox', { name: 'Slug', exact: true }).fill(pageSlug);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/editor-html'
        && url.searchParams.get('document') === pageSlug),
      page.getByRole('button', { name: 'Create and edit' }).click(),
    ]);

    const editorSurface = page.locator('[data-editor-html-surface]');
    await expect(editorSurface).toBeVisible();
    await editorSurface.fill(firstPublishedBody);
    await Promise.all([
      page.waitForURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
        && url.searchParams.get('saved') === '1'),
      page.getByRole('button', { name: 'Save and publish' }).click(),
    ]);
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();

    await page.getByRole('link', { name: 'Edit', exact: true }).click();
    await expect(editorSurface).toBeVisible();
    await editorSurface.fill(secondDraftBody);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/editor-html'
        && url.searchParams.get('saved') === '1'),
      page.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await expect(page.getByText('Shared draft', { exact: true })).toBeVisible();

    await page.getByRole('link', { name: 'View', exact: true }).click();
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();
    await expect(page.getByText(secondDraftBody, { exact: true })).toHaveCount(0);

    await page.getByRole('link', { name: 'Edit draft', exact: true }).click();
    await expect(editorSurface).toContainText(secondDraftBody);
    await Promise.all([
      page.waitForURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
        && url.searchParams.get('saved') === '1'),
      page.getByRole('button', { name: 'Save and publish' }).click(),
    ]);
    await expect(page.getByRole('heading', { name: secondDraftBody, exact: true })).toBeVisible();
    await expect(page.getByText(firstPublishedBody, { exact: true })).toHaveCount(0);

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
