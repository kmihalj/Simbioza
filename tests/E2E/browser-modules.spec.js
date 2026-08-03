import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  getDataWithEtag,
  idempotencyKey,
  login,
} from './helpers.js';

const {
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
  adminApiToken,
} = e2eEnvironment();

test.describe('module browser surfaces', () => {
  test('all module settings, application screens, JSON helpers, and public assets respond', async ({ page }) => {
    test.setTimeout(90_000);
    await login(page, adminLogin, adminPassword);

    const htmlRoutes = [
      '/settings',
      '/settings/menu?section=top',
      '/settings/menu?section=settings',
      '/settings/menu?section=contexts_top',
      '/settings/menu?section=contexts_left',
      '/settings/theme',
      '/settings/auth?section=overview',
      '/settings/auth?section=attributes',
      '/settings/auth?section=groups',
      '/settings/auth?section=users',
      '/settings/auth?section=local',
      '/settings/auth/impersonation',
      '/settings/auth/api-keys',
      '/workspaces',
      '/workspaces/manage',
      '/settings/workspaces',
      '/settings/workspaces/homepage',
      '/settings/workspaces/all',
      '/settings/workspaces/deleted',
      '/editor-html',
      '/settings/editor-html',
      '/settings/editor-html/documents/deleted',
      '/notifications',
      '/settings/email',
      '/calendars',
      '/calendar/profile',
      '/settings/calendar',
    ];
    for (const route of htmlRoutes) {
      const response = await page.goto(route);
      expect(response?.status(), route).toBe(200);
      await expect(page.locator('body'), route).not.toContainText(/Internal Server Error|Fatal error/i);
    }

    const sessionRequests = [
      ['/settings/auth/users/data?page=1&limit=10', 'application/json'],
      ['/settings/auth/users/export.csv', 'text/csv'],
      ['/settings/auth/api-keys/users?q=e2e', 'application/json'],
      ['/settings/editor-html/storage-migration/status', 'application/json'],
      ['/settings/calendar/list', 'application/json'],
      ['/calendars/data?from=2026-08-01&to=2026-08-31', 'application/json'],
    ];
    for (const [route, contentType] of sessionRequests) {
      const response = await page.request.get(route);
      expect(response.status(), route).toBe(200);
      expect(response.headers()['content-type'], route).toContain(contentType);
    }

    const assets = [
      ['/theme.css', 'text/css'],
      ['/workspaces/assets.css', 'text/css'],
      ['/workspaces/assets.js', 'javascript'],
      ['/comments/assets.css', 'text/css'],
      ['/comments/assets.js', 'javascript'],
      ['/tasks/assets.css', 'text/css'],
      ['/tasks/assets.js', 'javascript'],
      ['/calendar/assets/calendar.css', 'text/css'],
      ['/calendar/assets/calendar.js', 'javascript'],
      ['/menu/assets/flags/hr.svg', 'image/svg+xml'],
      ['/theme/assets/flags/hr.svg', 'image/svg+xml'],
    ];
    for (const [route, contentType] of assets) {
      const response = await page.request.get(route);
      expect(response.status(), route).toBe(200);
      expect(response.headers()['content-type'], route).toContain(contentType);
      expect((await response.body()).byteLength, route).toBeGreaterThan(0);
    }

    const commentCsrf = await page.request.get('/comments/csrf-token');
    expect(commentCsrf.status()).toBe(200);
    expect((await commentCsrf.json()).csrf.token).toBeTruthy();
    const taskCsrf = await page.request.get('/tasks/csrf-token');
    expect(taskCsrf.status()).toBe(200);
    expect((await taskCsrf.json()).csrf.token).toBeTruthy();
  });

  test('Menu configuration saves without changing its hierarchy and locale switching persists', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/menu?section=top');
    await expect(page.getByRole('heading', { name: 'Menu settings' })).toBeVisible();
    const labelsBefore = await page.getByRole('textbox', { name: 'Label' })
      .evaluateAll((inputs) => inputs.map((input) => input.value));
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/menu'),
      page.getByRole('button', { name: 'Save menu configuration' }).click(),
    ]);
    await page.goto('/settings/menu?section=top');
    expect(await page.getByRole('textbox', { name: 'Label' })
      .evaluateAll((inputs) => inputs.map((input) => input.value))).toEqual(labelsBefore);

    await page.goto('/locale/hr');
    await expect(page.locator('html')).toHaveAttribute('lang', 'hr');
    await expect(page.getByRole('link', { name: 'Početna' })).toBeVisible();
    await page.goto('/locale/en');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByRole('link', { name: 'Home' })).toBeVisible();
  });

  test('Theme live preview remains readable in narrow containers', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/theme');

    for (const width of [390, 1100]) {
      await page.setViewportSize({ width, height: 844 });
      const preview = page.locator('[data-theme-preview]').first();
      await expect(preview).toBeVisible();

      const geometry = await preview.evaluate((element) => {
        const subtitle = element.querySelector('.theme-preview-hero__subtitle')?.getBoundingClientRect();
        const label = element.querySelector('[data-theme-preview-content] > p')?.getBoundingClientRect();

        return {
          subtitleBottom: subtitle?.bottom ?? 0,
          labelTop: label?.top ?? 0,
        };
      });
      expect(geometry.labelTop).toBeGreaterThanOrEqual(geometry.subtitleBottom);

      const pageWidth = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: window.innerWidth,
      }));
      expect(pageWidth.documentWidth).toBeLessThanOrEqual(pageWidth.viewportWidth);
    }
  });

  test('Theme clone, package export, portable backup, deletion, and backup import round-trip', async ({ page }) => {
    test.setTimeout(90_000);
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/theme');

    const cloneName = `E2E Portable Theme ${Date.now()}`;
    await page.getByRole('textbox', { name: 'Theme name' }).first().fill(cloneName);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Create copy' }).click(),
    ]);
    await expect(page.getByRole('heading', { name: new RegExp(`Edit theme: ${cloneName}`) })).toBeVisible();

    const packageDownloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Export theme package' }).click();
    const packageDownload = await packageDownloadPromise;
    expect(packageDownload.suggestedFilename()).toMatch(/\.zip$/);
    expect(await packageDownload.path()).toBeTruthy();

    const backupDownloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Export theme backup' }).click();
    const backupDownload = await backupDownloadPromise;
    const backupPath = await backupDownload.path();
    expect(backupDownload.suggestedFilename()).toMatch(/\.zip$/);
    expect(backupPath).toBeTruthy();

    page.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Delete theme' }).click(),
    ]);
    await expect(page.getByRole('heading', { name: new RegExp(`Edit theme: ${cloneName}`) })).toHaveCount(0);

    await page.locator('input[type="file"]').setInputFiles(backupPath);
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Import theme', exact: true }).click(),
    ]);
    await expect(page.getByRole('heading', { name: new RegExp(`Edit theme: ${cloneName}`) })).toBeVisible();

    page.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Delete theme' }).click(),
    ]);
  });

  test('Auth self-service profile, notification preference, and reversible password change work', async ({ page }) => {
    test.setTimeout(60_000);
    const temporaryPassword = 'E2eTemporary!2026';
    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');

    await page.getByRole('textbox', { name: 'First name' }).fill('Updated E2E');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Save profile' }).click(),
    ]);
    await expect(page.getByRole('textbox', { name: 'First name' })).toHaveValue('Updated E2E');

    const preference = page.getByRole('switch', { name: /e-mail copies|e-mail kopije/i });
    const originalPreference = await preference.isChecked();
    await preference.setChecked(!originalPreference);
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save notification settings|Spremi postavke obavijesti/i }).click(),
    ]);
    await expect(preference).toBeChecked({ checked: !originalPreference });
    await preference.setChecked(originalPreference);
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save notification settings|Spremi postavke obavijesti/i }).click(),
    ]);

    await page.goto('/auth/password/change');
    await page.getByRole('textbox', { name: 'Current password' }).fill(userPassword);
    await page.getByRole('textbox', { name: 'New password', exact: true }).fill(temporaryPassword);
    await page.getByRole('textbox', { name: 'Confirm new password' }).fill(temporaryPassword);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/'),
      page.getByRole('button', { name: 'Save password' }).click(),
    ]);
    await page.goto('/auth/logout');

    await page.goto('/auth/login');
    await page.locator('#auth_login').fill(userLogin);
    await page.locator('#auth_password').fill(userPassword);
    await page.locator('#local_override_login button[type="submit"]').click();
    await expect(page).toHaveURL(/\/auth\/login/);

    await login(page, userLogin, temporaryPassword);
    await page.goto('/auth/password/change');
    await page.getByRole('textbox', { name: 'Current password' }).fill(temporaryPassword);
    await page.getByRole('textbox', { name: 'New password', exact: true }).fill(userPassword);
    await page.getByRole('textbox', { name: 'Confirm new password' }).fill(userPassword);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/'),
      page.getByRole('button', { name: 'Save password' }).click(),
    ]);

    await page.goto('/auth/account/profile');
    await page.getByRole('textbox', { name: 'First name' }).fill('E2E');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Save profile' }).click(),
    ]);
  });

  test('Workspace application homepage follows public, signed-in, and personal precedence', async ({ page }) => {
    test.setTimeout(90_000);

    const suffix = Date.now();
    const workspaceSlug = `e2e-homepage-${suffix}`;
    const publicSlug = `public-homepage-${suffix}`;
    const signedInSlug = `signed-in-homepage-${suffix}`;
    const publicTitle = `E2E Public Homepage ${suffix}`;
    const signedInTitle = `E2E Signed-in Homepage ${suffix}`;
    const headers = apiHeaders(adminApiToken);

    const homepageWorkspace = await expectData(await page.request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('homepage-workspace'),
      }),
      data: {
        name: `E2E Homepage ${suffix}`,
        slug: workspaceSlug,
        visibility: 'public',
      },
    }), 201);

    const publishPage = async (title, slug) => {
      const created = await expectData(await page.request.post('/api/v1/pages', {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey(`homepage-page-${slug}`),
        }),
        data: {
          title,
          slug,
          workspace_slug: workspaceSlug,
          language: 'en',
          html: `<h1>${title}</h1><p>Homepage precedence E2E fixture.</p>`,
        },
      }), 201);
      const draft = await getDataWithEtag(
        page.request,
        `/api/v1/pages/${created.id}/draft?lang=en`,
        headers,
      );
      await expectData(await page.request.post(`/api/v1/pages/${created.id}/publish?lang=en`, {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey(`homepage-publish-${slug}`),
          'If-Match': draft.etag,
        }),
        data: {},
      }));
    };

    await publishPage(publicTitle, publicSlug);
    await publishPage(signedInTitle, signedInSlug);

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/homepage');
    await expect(page.getByRole('heading', { name: 'Application homepage' })).toBeVisible();
    await page.locator('#workspace-public-homepage').selectOption({ label: publicTitle });
    await page.locator('#workspace-authenticated-homepage').selectOption({ label: signedInTitle });
    await page.locator('#workspace-allow-user-homepage').check();
    await Promise.all([
      page.waitForURL('/settings/workspaces/homepage'),
      page.getByRole('button', { name: 'Save homepage settings' }).click(),
    ]);

    await page.goto('/auth/logout');
    await page.goto('/');
    await expect(page).toHaveURL(new RegExp(`/workspace/${workspaceSlug}/${publicSlug}\\?lang=en$`));

    await login(page, userLogin, userPassword);
    await expect(page).toHaveURL(
      new RegExp(`/workspace/${workspaceSlug}/${signedInSlug}\\?lang=en$`),
    );

    await page.goto('/auth/account/profile');
    await expect(page.getByRole('heading', { name: 'Personal homepage' })).toBeVisible();
    await page.locator('#workspace-personal-homepage').selectOption({ label: publicTitle });
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Save personal homepage' }).click(),
    ]);
    await page.goto('/');
    await expect(page).toHaveURL(new RegExp(`/workspace/${workspaceSlug}/${publicSlug}\\?lang=en$`));

    await page.goto('/auth/account/profile');
    await page.locator('#workspace-personal-homepage').selectOption('default');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Save personal homepage' }).click(),
    ]);
    await page.goto('/auth/logout');

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/homepage');
    await page.locator('#workspace-public-homepage').selectOption(`shorts:${homepageWorkspace.id}`);
    await page.locator('#workspace-authenticated-homepage').selectOption('default');
    await page.locator('#workspace-public-show-tree').uncheck();
    await page.locator('#workspace-public-show-options').uncheck();
    await Promise.all([
      page.waitForURL('/settings/workspaces/homepage'),
      page.getByRole('button', { name: 'Save homepage settings' }).click(),
    ]);
    await page.goto('/auth/logout');
    await page.goto('/');
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/shorts`
      && url.searchParams.get('lang') === 'en'
      && url.searchParams.get('tree') === '0'
      && url.searchParams.get('options') === '0');
    await expect(page.locator('#workspace-page-tree')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#workspace-shorts-display-options')).not.toHaveClass(/\bshow\b/);

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/homepage');
    await page.locator('#workspace-public-homepage').selectOption('default');
    await page.locator('#workspace-authenticated-homepage').selectOption('default');
    await Promise.all([
      page.waitForURL('/settings/workspaces/homepage'),
      page.getByRole('button', { name: 'Save homepage settings' }).click(),
    ]);
    await page.goto('/auth/logout');
  });

  test('personal API-key request, administrator approval, and one-time reveal work', async ({ page }) => {
    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await page.getByText('Request an API key', { exact: true }).click();
    await page.getByRole('textbox', { name: 'Name', exact: true }).fill('E2E personal read key');
    await page.getByRole('textbox', { name: 'Purpose' }).fill(
      'Validates the user request and administrator approval lifecycle.',
    );
    await page.locator('input[name="scopes[]"][value="workspace:read"]').check();
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Submit request' }).click(),
    ]);
    await expect(page.getByText('E2E personal read key', { exact: true })).toBeVisible();
    await page.goto('/auth/logout');

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/auth/api-keys#api-key-requests');
    const requestItem = page.locator('article.api-request-item').filter({ hasText: 'E2E personal read key' });
    await expect(requestItem).toBeVisible();
    await Promise.all([
      page.waitForURL('/settings/auth/api-keys'),
      requestItem.getByRole('button', { name: 'Approve request' }).click(),
    ]);
    await page.goto('/auth/logout');

    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    const reveal = page.getByRole('link', { name: 'Reveal key once' });
    await expect(reveal).toBeVisible();
    await reveal.click();
    await expect(page.locator('[data-api-key-token]')).toContainText(/^hfp_live_/);
    await page.goto('/auth/account/profile');
    await expect(page.getByText('The secret has already been shown.')).toBeVisible();
  });

  test('Comment create, reaction, report, moderation, and Notification UI work on a real page', async ({ page }) => {
    test.setTimeout(90_000);
    const workspaceSlug = `e2e-comments-${Date.now()}`;
    const documentId = 'discussion';
    const headers = apiHeaders(adminApiToken);
    await expectData(await page.request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('comment-workspace') }),
      data: { name: 'E2E Comments', slug: workspaceSlug, visibility: 'public' },
    }), 201);
    const created = await expectData(await page.request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('comment-page') }),
      data: {
        title: 'E2E Discussion',
        slug: documentId,
        workspace_slug: workspaceSlug,
        language: 'en',
        html: '<h1>E2E Discussion</h1><p>Comment integration surface.</p>',
      },
    }), 201);
    const draft = await getDataWithEtag(
      page.request,
      `/api/v1/pages/${created.id}/draft?lang=en`,
      headers,
    );
    await expectData(await page.request.post(`/api/v1/pages/${created.id}/publish?lang=en`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('comment-publish'),
        'If-Match': draft.etag,
      }),
      data: {},
    }));

    await login(page, adminLogin, adminPassword);
    const publicPath = `/workspace/${workspaceSlug}/${documentId}`;
    await page.goto(publicPath);
    await page.getByRole('textbox', { name: 'New comment' }).fill('E2E moderated comment');
    await page.getByRole('button', { name: 'Post comment' }).click();
    await expect(page.getByText('E2E moderated comment', { exact: true })).toBeVisible();
    await page.goto('/auth/logout');

    await login(page, userLogin, userPassword);
    await page.goto(publicPath);
    const comment = page.locator('article').filter({ hasText: 'E2E moderated comment' });
    await comment.getByRole('button', { name: 'Like', exact: true }).click();
    await expect(comment.getByRole('button', { name: 'Like', exact: true })).toContainText('1');
    await comment.getByRole('button', { name: 'Report inappropriate comment' }).click();
    await expect(page.locator('[role="status"]')).toContainText(/reported|prijavljen/i);
    await page.goto('/auth/logout');

    await login(page, adminLogin, adminPassword);
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.goto('/notifications');
    await expect(page.locator('body')).toContainText(/comment|komentar/i);
    const notificationColors = await page.locator('.card').evaluate((card) => {
      const bodyText = card.querySelector('.text-body');
      const secondaryText = card.querySelector('.text-body-secondary');
      if (!(bodyText instanceof HTMLElement) || !(secondaryText instanceof HTMLElement)) {
        throw new Error('Notification text elements are missing.');
      }

      // HR: Razrješava varijablu teme u isti izračunati RGB oblik koji vraća preglednik.
      // EN: Resolves a theme variable to the same computed RGB form returned by the browser.
      const colorFromVariable = (name) => {
        const probe = document.createElement('span');
        probe.style.color = `var(${name})`;
        card.append(probe);
        const color = getComputedStyle(probe).color;
        probe.remove();
        return color;
      };

      return {
        body: getComputedStyle(bodyText).color,
        expectedBody: colorFromVariable('--hph-body-text'),
        secondary: getComputedStyle(secondaryText).color,
        expectedSecondary: colorFromVariable('--hph-muted-text'),
      };
    });
    expect(notificationColors.body).toBe(notificationColors.expectedBody);
    expect(notificationColors.secondary).toBe(notificationColors.expectedSecondary);
    await page.goto(publicPath);
    const moderated = page.locator('article').filter({ hasText: 'E2E moderated comment' });
    page.once('dialog', (dialog) => dialog.accept());
    await moderated.getByRole('button', { name: 'Delete comment' }).click();
    await expect(page.getByText('E2E moderated comment', { exact: true })).toHaveCount(0);
  });

  test('E-mail settings persist and a failed local SMTP test remains observable in the outbox', async ({ page }) => {
    test.setTimeout(60_000);
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/email');
    await page.getByRole('checkbox', { name: 'E-mail delivery is enabled' }).check();
    await page.getByRole('textbox', { name: 'SMTP host' }).fill('127.0.0.1');
    await page.getByRole('spinbutton', { name: 'Port' }).fill('9');
    await page.getByRole('combobox', { name: 'Encryption' }).selectOption({ label: 'No encryption' });
    await page.getByRole('textbox', { name: 'Sender address' }).fill('e2e@example.invalid');
    await page.getByRole('textbox', { name: 'Public application URL' }).fill('http://127.0.0.1');
    await Promise.all([
      page.waitForURL('/settings/email'),
      page.getByRole('button', { name: 'Save settings' }).click(),
    ]);
    await expect(page.getByRole('checkbox', { name: 'E-mail delivery is enabled' })).toBeChecked();

    await page.getByRole('textbox', { name: 'Recipient address' }).fill('recipient@example.invalid');
    await Promise.all([
      page.waitForURL('/settings/email'),
      page.getByRole('button', { name: 'Send test' }).click(),
    ]);
    await expect(page.locator('[role="alert"]')).toContainText(/failed|nije uspjelo/i);
    await expect(page.locator('body')).toContainText(/Failed:\s*1|Neuspjelo:\s*1/i);

    await page.getByRole('checkbox', { name: 'E-mail delivery is enabled' }).uncheck();
    await Promise.all([
      page.waitForURL('/settings/email'),
      page.getByRole('button', { name: 'Save settings' }).click(),
    ]);
  });
});
