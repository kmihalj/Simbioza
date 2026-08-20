import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  expectUsableModal,
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

async function openProfileSection(page, selector) {
  const section = page.locator(selector);
  if ((await section.count()) === 0) {
    return;
  }

  const sectionId = await section.getAttribute('id');
  const triggerSelectors = sectionId
    ? [
      `button[data-bs-target="#${sectionId}"]`,
      `button[aria-controls="${sectionId}"]`,
      `a[data-bs-target="#${sectionId}"]`,
      `[href="#${sectionId}"]`,
      `#${sectionId}-heading [data-bs-toggle="collapse"]`,
    ].join(', ')
    : 'summary, [data-bs-toggle="collapse"], .accordion-button';

  const trigger = page.locator(triggerSelectors).first();
  if (await trigger.count() > 0) {
    const alreadyExpanded = await trigger.getAttribute('aria-expanded');
    if (alreadyExpanded === 'false') {
      await trigger.scrollIntoViewIfNeeded();
      await trigger.click({ force: true }).catch(() => {});
    }
  }

  await section.evaluate((node) => {
    if (node instanceof HTMLDetailsElement) {
      node.open = true;
      return;
    }
    if (window.bootstrap && window.bootstrap.Collapse) {
      window.bootstrap.Collapse.getOrCreateInstance(node, { toggle: false }).show();
    } else {
      node.classList.add('show');
    }
  });

  await section.waitFor({ state: 'attached' });
  await section.scrollIntoViewIfNeeded();
  await expect
    .poll(async () => {
      const state = await section.evaluate((node) => {
        if (node instanceof HTMLDetailsElement) {
          return node.open ? 'open' : 'closed';
        }

        return node.classList.contains('show') ? 'open' : 'closed';
      });
      return state;
    }, { timeout: 5_000 })
    .toBe('open');
}

test.describe('module browser surfaces', () => {
  test('every rendered Bootstrap modal remains interactive after repeated opening', async ({ page }) => {
    test.setTimeout(90_000);
    await login(page, adminLogin, adminPassword);

    /*
     * HR: Generički prolaz obuhvaća sve modalne okidače koje trenutna ruta
     *     stvarno renderira. Tako novi modul automatski ulazi u regresijsku
     *     zaštitu bez posebnog popisa ID-eva u testu.
     * EN: This generic pass covers every modal trigger actually rendered by
     *     the current route. New modules therefore join the regression guard
     *     without a separate hard-coded ID list.
     */
    for (const route of [
      '/calendars',
      '/settings/auth?section=users',
      '/editor-html',
      '/settings/editor-html',
    ]) {
      const response = await page.goto(route);
      expect(response?.status(), route).toBe(200);

      const targets = await page.locator('[data-bs-toggle="modal"][data-bs-target^="#"]')
        .evaluateAll((triggers) => [...new Set(triggers.map(
          (trigger) => trigger.getAttribute('data-bs-target'),
        ).filter((target) => typeof target === 'string' && target.length > 1))]);

      for (const target of targets) {
        const trigger = page.locator(`[data-bs-toggle="modal"][data-bs-target="${target}"]`).first();
        if (!await trigger.isVisible() || await trigger.isDisabled()) {
          continue;
        }

        for (let opening = 0; opening < 2; opening += 1) {
          await trigger.click();
          const dialog = page.locator(target);
          await expectUsableModal(dialog);
          await dialog.locator('[data-bs-dismiss="modal"]').last().click();
          await expect(dialog).toBeHidden();
        }
      }
    }
  });

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
      '/settings/personal-workspaces',
      '/editor-html',
      '/settings/editor-html',
      '/settings/editor-html/documents/deleted',
      '/notifications',
      '/settings/email',
      '/calendars',
      '/calendar/profile',
      '/settings/calendar',
      '/settings/backups',
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

  test('Workspace display defaults, page override, and login lifetime are configurable', async ({ page }) => {
    test.setTimeout(60_000);
    const suffix = Date.now();
    const workspace = {
      name: `E2E Display ${suffix}`,
      slug: `e2e-display-${suffix}`,
      page: `display-page-${suffix}`,
    };

    await expectData(await page.request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-display'),
      }),
      data: { name: workspace.name, slug: workspace.slug, visibility: 'public' },
    }), 201);
    const created = await expectData(await page.request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-display-page'),
      }),
      data: {
        title: `Display Page ${suffix}`,
        slug: workspace.page,
        workspace_slug: workspace.slug,
        language: 'en',
        html: '<h1>Display preference</h1><h2>Outline entry</h2><p>Visible document content.</p>',
      },
    }), 201);
    const draft = await getDataWithEtag(
      page.request,
      `/api/v1/pages/${created.id}/draft?lang=en`,
      apiHeaders(adminApiToken),
    );
    await expectData(await page.request.post(`/api/v1/pages/${created.id}/publish?lang=en`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-display-publish'),
        'If-Match': draft.etag,
      }),
      data: {},
    }));

    await login(page, adminLogin, adminPassword);
    const session = await page.context().cookies();
    const loginCookie = session.find((cookie) => cookie.name === 'HEARTPHRAME_E2E_SESSION');
    expect(loginCookie?.expires ?? -1).toBeGreaterThan(Math.floor(Date.now() / 1000) + 86_400);

    await page.goto(`/workspaces/manage?workspace=${workspace.slug}`);
    await page.locator('#workspace-tree-visibility').selectOption('hidden');
    await page.locator('#workspace-contents-visibility').selectOption('hidden');
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/workspaces/manage'
        && url.searchParams.get('workspace') === workspace.slug),
      page.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await expect(page.locator('#workspace-tree-visibility')).toHaveValue('hidden');
    await expect(page.locator('#workspace-contents-visibility')).toHaveValue('hidden');

    await page.goto(`/workspace/${workspace.slug}/${workspace.page}?lang=en`);
    await expect(page.locator('#workspace-page-tree')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#editor-html-toc-column')).toBeHidden();

    await page.goto(`/editor-html?document=${created.id}&lang=en`);
    await expect(page.locator('#editor-html-contents-visibility')).toHaveValue('inherit');
    await page.locator('#editor-html-contents-visibility').selectOption('shown');
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/editor-html'
        && url.searchParams.get('document') === created.id),
      page.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await expect(page.locator('#editor-html-contents-visibility')).toHaveValue('shown');

    await page.goto(`/workspace/${workspace.slug}/${workspace.page}?lang=en`);
    await expect(page.locator('#workspace-page-tree')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#editor-html-toc-column')).toBeVisible();

    await page.goto('/settings/auth?section=overview');
    await expect(page.locator('#login_duration_days')).toHaveValue('30');
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

  test('Supporting copy beneath automatic hero titles uses the hero subtitle color', async ({ page }) => {
    await login(page, adminLogin, adminPassword);

    for (const route of ['/calendars', '/workspaces']) {
      await page.goto(route);

      const supportingCopy = page.locator(
        '.hph-page-heading-support > p:first-of-type',
      ).first();
      await expect(supportingCopy, route).toBeVisible();

      const colors = await supportingCopy.evaluate((element) => {
        const rootStyle = getComputedStyle(document.documentElement);

        return {
          actual: getComputedStyle(element).color,
          hero: rootStyle.getPropertyValue('--hph-hero-subtitle').trim(),
          muted: rootStyle.getPropertyValue('--hph-muted-text').trim(),
        };
      });

      expect(colors.hero, route).not.toBe('');
      expect(colors.hero.toLowerCase(), route).not.toBe(colors.muted.toLowerCase());
      expect(colors.actual, route).toBe(
        await page.evaluate((heroColor) => {
          const probe = document.createElement('span');
          probe.style.color = heroColor;
          document.body.append(probe);
          const resolved = getComputedStyle(probe).color;
          probe.remove();

          return resolved;
        }, colors.hero),
      );
    }

    /*
     * HR: Izvorni tekst koji ostaje unutar kartice mora koristiti sadržajnu
     *     paletu, iako je njegov duplicirani naslov premješten u hero.
     * EN: Source copy that remains inside a card must use the content palette,
     *     even though its duplicate title was moved into the hero.
     */
    await page.goto('/settings/theme?theme=simbioza');
    const settingsCopy = page.getByText(
      /Site-wide theme configuration|Konfiguracija teme za cijeli site/i,
    ).first();
    await expect(settingsCopy).toBeVisible();
    const settingsColors = await settingsCopy.evaluate((element) => {
      const rootStyle = getComputedStyle(document.documentElement);

      return {
        actual: getComputedStyle(element).color,
        muted: rootStyle.getPropertyValue('--hph-muted-text').trim(),
      };
    });
    expect(settingsColors.actual).toBe(await page.evaluate((mutedColor) => {
      const probe = document.createElement('span');
      probe.style.color = mutedColor;
      document.body.append(probe);
      const resolved = getComputedStyle(probe).color;
      probe.remove();

      return resolved;
    }, settingsColors.muted));

  });

  test('Theme editor keeps page order and previews light and dark branding immediately', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/theme?theme=simbioza');

    const editor = page.locator('#theme-editor-form');
    const sectionIds = await editor.locator(
      '.theme-editor-sections > .theme-editor-section[data-theme-section-id]',
    ).evaluateAll((sections) => sections.map((section) => section.dataset.themeSectionId));
    expect(sectionIds).toEqual([
      'accessibility',
      'header',
      'navigation',
      'hero',
      'page-content',
      'card-presentation',
      'base',
      'buttons',
      'cards_tables',
      'feedback_badges',
      'forms_content',
      'assets',
    ]);
    await expect(editor.locator('[data-theme-section-id="assets"]')).toHaveCount(1);

    await editor.locator('[data-theme-section-id="hero"] > summary').click();
    const heroColorControls = editor.locator(
      '[data-theme-section-id="hero"] [data-theme-color-control]',
    );
    await expect(heroColorControls).toHaveCount(18);
    const allColorInputs = editor.locator('input[type="color"]');
    await expect(editor.locator('[data-theme-color-control]')).toHaveCount(await allColorInputs.count());

    const lightGradientPicker = editor.locator(
      '[data-theme-gradient-color][data-variant="light"]',
    ).first();
    const lightGradientHex = lightGradientPicker.locator(
      'xpath=ancestor::*[@data-theme-color-control]//*[@data-theme-color-text]',
    );
    await lightGradientHex.fill('#112233');
    await expect(lightGradientPicker).toHaveValue('#112233');
    await expect.poll(() => page.locator('[data-theme-preview="light"]').evaluate(
      (preview) => preview.style.getPropertyValue('--hph-hero-gradient-1'),
    )).toBe('#112233');
    await lightGradientPicker.evaluate((input) => {
      input.value = '#445566';
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await expect(lightGradientHex).toHaveValue('#445566');

    const lightHeroSelect = editor.locator(
      '[data-theme-hero-visual-source][data-variant="light"]',
    );
    const darkHeroSelect = editor.locator(
      '[data-theme-hero-visual-source][data-variant="dark"]',
    );
    await expect(lightHeroSelect).toHaveCount(1);
    await expect(darkHeroSelect).toHaveCount(1);
    expect(await lightHeroSelect.inputValue()).not.toBe(await darkHeroSelect.inputValue());

    const alternateHero = lightHeroSelect.locator('option[data-preview-src]:not([value=""])').nth(1);
    const alternateHeroValue = await alternateHero.getAttribute('value');
    const alternateHeroSource = await alternateHero.getAttribute('data-preview-src');
    expect(alternateHeroValue).toBeTruthy();
    expect(alternateHeroSource).toBeTruthy();
    await lightHeroSelect.selectOption(alternateHeroValue);
    await expect(page.locator('[data-theme-preview="light"] [data-theme-preview-hero-visual]'))
      .toHaveAttribute('src', alternateHeroSource);

    await editor.locator('[data-theme-section-id="header"] > summary').click();
    const lightLogoSelect = editor.locator(
      '[data-theme-header-logo-source][data-variant="light"]',
    ).first();
    const alternateLogo = lightLogoSelect.locator('option[data-preview-src]:not([value=""])').nth(1);
    const alternateLogoValue = await alternateLogo.getAttribute('value');
    const alternateLogoSource = await alternateLogo.getAttribute('data-preview-src');
    expect(alternateLogoValue).toBeTruthy();
    expect(alternateLogoSource).toBeTruthy();
    await lightLogoSelect.selectOption(alternateLogoValue);
    await expect(page.locator('[data-theme-preview="light"] [data-theme-preview-header] img'))
      .toHaveAttribute('src', alternateLogoSource);
  });

  test('Theme clone, package export, complete export, deletion, and import round-trip', async ({ page }) => {
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

    const completeDownloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Export complete theme' }).click();
    const completeDownload = await completeDownloadPromise;
    const completePath = await completeDownload.path();
    expect(completeDownload.suggestedFilename()).toMatch(/\.zip$/);
    expect(completePath).toBeTruthy();

    page.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Delete theme' }).click(),
    ]);
    await expect(page.getByRole('heading', { name: new RegExp(`Edit theme: ${cloneName}`) })).toHaveCount(0);

    await page.locator('input[name="complete_theme"]').setInputFiles(completePath);
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

  test('Workspace themes stay private and never mutate system themes or another workspace', async ({ page }) => {
    test.setTimeout(90_000);
    const suffix = Date.now();
    const headers = apiHeaders(adminApiToken);
    const alpha = {
      name: `E2E Theme Alpha ${suffix}`,
      slug: `e2e-theme-alpha-${suffix}`,
      page: `alpha-page-${suffix}`,
    };
    const beta = {
      name: `E2E Theme Beta ${suffix}`,
      slug: `e2e-theme-beta-${suffix}`,
      page: `beta-page-${suffix}`,
    };

    /*
     * HR: Svako područje dobiva jednu stvarnu objavljenu stranicu kako bi test
     *     provjerio request-scoped temu na javnoj ruti, a ne samo vrijednost forme.
     * EN: Each Workspace receives one real published page so the test verifies
     *     request-scoped theming on a public route, not only a form value.
     */
    const createWorkspacePage = async (workspace, title) => {
      await expectData(await page.request.post('/api/v1/workspaces', {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey(`workspace-theme-${workspace.slug}`),
        }),
        data: { name: workspace.name, slug: workspace.slug, visibility: 'public' },
      }), 201);
      const created = await expectData(await page.request.post('/api/v1/pages', {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey(`workspace-theme-page-${workspace.page}`),
        }),
        data: {
          title,
          slug: workspace.page,
          workspace_slug: workspace.slug,
          language: 'en',
          html: `<h1>${title}</h1><p>Private Workspace theme isolation fixture.</p>`,
        },
      }), 201);
      const draft = await getDataWithEtag(
        page.request,
        `/api/v1/pages/${created.id}/draft?lang=en`,
        headers,
      );
      await expectData(await page.request.post(`/api/v1/pages/${created.id}/publish?lang=en`, {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey(`workspace-theme-publish-${workspace.page}`),
          'If-Match': draft.etag,
        }),
        data: {},
      }));
    };

    await createWorkspacePage(alpha, `Alpha Theme Page ${suffix}`);
    await createWorkspacePage(beta, `Beta Theme Page ${suffix}`);
    await login(page, adminLogin, adminPassword);

    await page.goto(`/workspaces/manage?workspace=${alpha.slug}`);
    await page.getByRole('link', { name: 'Edit Workspace theme' }).click();
    await expect(page.locator('#active_theme')).toHaveValue('__default__');
    await page.locator('#active_theme').selectOption('standard');
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/workspaces/theme'
        && url.searchParams.get('workspace') === alpha.slug),
      page.getByRole('button', { name: 'Save workspace theme selection' }).click(),
    ]);
    await expect(page.locator('#active_theme')).toHaveValue('standard');
    await expect(page.getByRole('link', { name: 'Export complete theme' })).toHaveCount(0);

    /*
     * HR: Spremanje neizmijenjenog naziva sistemske teme mora stvoriti privatni
     *     naziv s područjem i tek tada administratoru ponuditi izvoz kopije.
     * EN: Saving an unchanged system-theme label must create a private label
     *     containing the Workspace and only then offer the copy export to an administrator.
     */
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/workspaces/theme'
        && url.searchParams.get('workspace') === alpha.slug),
      page.getByRole('button', { name: 'Save theme' }).click(),
    ]);
    await expect(page.getByRole('heading', {
      name: new RegExp(`Edit theme: Standard .* ${alpha.name}`),
    })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Export complete theme' })).toBeVisible();

    await page.goto(`/workspace/${alpha.slug}/${alpha.page}?lang=en`);
    await expect(page.locator('style[data-hph-runtime-theme]')).toHaveCount(1);

    await page.goto(`/workspace/${beta.slug}/${beta.page}?lang=en`);
    await expect(page.locator('style[data-hph-runtime-theme]')).toHaveCount(0);

    await page.goto(`/workspaces/manage?workspace=${beta.slug}`);
    await page.getByRole('link', { name: 'Edit Workspace theme' }).click();
    await expect(page.locator('#active_theme')).toHaveValue('__default__');

    await page.goto('/settings/theme');
    await expect(page.locator('#active_theme')).toHaveValue('simbioza');
    await expect(page.locator('#active_theme option', { hasText: alpha.name })).toHaveCount(0);
  });

  test('Auth self-service profile, notification preference, and reversible password change work', async ({ page }) => {
    test.setTimeout(60_000);
    const temporaryPassword = 'E2eTemporary!2026';
    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');

    const firstNameInput = page.locator('#profile_user_attribute_first_name')
      .or(page.getByRole('textbox', { name: /First name|Ime/i }));
    await firstNameInput.fill('Updated E2E');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save profile|Spremi profil/i }).click(),
    ]);
    await expect(firstNameInput).toHaveValue('Updated E2E');

    const preference = page.locator('#notification-email-enabled');
    if (await preference.count() > 0) {
      await expect(preference).toBeVisible();
      const originalPreference = await preference.evaluate((input) => input.checked);
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
    }

    await page.goto('/auth/password/change');
    await page.getByRole('textbox', { name: /Current password|Trenutna lozinka/i }).fill(userPassword);
    await page.getByRole('textbox', { name: 'New password', exact: true }).fill(temporaryPassword);
    await page.getByRole('textbox', { name: /Confirm new password|Potvrdi novu lozinku|Potvrdi lozinku/i }).fill(temporaryPassword);
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
    await page.getByRole('textbox', { name: /Current password|Trenutna lozinka/i }).fill(temporaryPassword);
    await page.getByRole('textbox', { name: 'New password', exact: true }).fill(userPassword);
    await page.getByRole('textbox', { name: /Confirm new password|Potvrdi novu lozinku|Potvrdi lozinku/i }).fill(userPassword);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/'),
      page.getByRole('button', { name: 'Save password' }).click(),
    ]);

    await page.goto('/auth/account/profile');
    await page.getByRole('textbox', { name: /First name|Ime/i }).fill('E2E');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: 'Save profile' }).click(),
    ]);
  });

  test('personal Workspace is created once, linked from profile, and concealed from guests', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');

    await expect(page.getByRole('heading', {
      name: /My personal Workspace|Moje osobno područje/i,
    })).toBeVisible();
    const personalLink = page.locator('a[href*="/workspace/osobno-"]').first();
    await expect(personalLink).toBeVisible();
    const personalPath = await personalLink.getAttribute('href');
    expect(personalPath).toMatch(/^\/workspace\/osobno-/);

    await personalLink.click();
    await expect(page).toHaveURL(new RegExp(`${personalPath}$`));
    const englishTitle = page.getByRole('heading', { name: /^Workspace of:/i }).first();
    await expect(englishTitle).toBeVisible();
    await expect(page.getByText(/Personal Workspace of user /i).first()).toBeVisible();
    await expect(page.getByText(/^Područje od:/i)).toHaveCount(0);
    await expect(page.getByText(/^Osobno područje korisnika /i)).toHaveCount(0);

    /*
     * HR: Naslov osobnog područja na desktopu mora ostati u jednom retku.
     * EN: The personal Workspace title must remain on one line on desktop.
     */
    await expect.poll(async () => englishTitle.evaluate((element) => {
      const range = document.createRange();
      range.selectNodeContents(element);

      return range.getClientRects().length;
    })).toBe(1);

    await page.goto('/locale/hr');
    await page.goto(personalPath);
    await expect(page.getByRole('heading', { name: /^Područje od:/i }).first()).toBeVisible();
    await expect(page.getByText(/Osobno područje korisnika /i).first()).toBeVisible();
    await expect(page.getByText(/^Workspace of:/i)).toHaveCount(0);
    await expect(page.getByText(/^Personal Workspace of user /i)).toHaveCount(0);

    await page.goto('/auth/logout');
    const guestResponse = await page.goto(personalPath);
    expect(guestResponse?.status()).toBe(403);

    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(1);

    await page.goto('/auth/logout');
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/personal-workspaces');
    await expect(page.locator('#personal-workspaces-auto-create')).toBeChecked();
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(1);
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
    await openProfileSection(page, '#auth-account-personal');
    await expect(page.locator('#workspace-personal-homepage')).toBeVisible();
    await expect(page.locator('#workspace-personal-homepage')).toBeVisible();
    await page.locator('#workspace-personal-homepage').selectOption({ label: publicTitle });
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save personal homepage|Spremi osobnu naslovnicu/i }).click(),
    ]);
    await page.goto('/');
    await expect(page).toHaveURL(new RegExp(`/workspace/${workspaceSlug}/${publicSlug}\\?lang=en$`));

    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');
    await page.locator('#workspace-personal-homepage').selectOption('default');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save personal homepage|Spremi osobnu naslovnicu/i }).click(),
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
    const requestName = 'E2E personal read key';
    const requestDescription = 'Validates the user request and administrator approval lifecycle.';

    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-security');
    const requestKeySummary = page
      .locator('#api-key-requests details summary')
      .filter({ hasText: /Zatraži API ključ|Request an API key/i });
    if (await requestKeySummary.count() > 0 && await requestKeySummary.first().isVisible()) {
      await requestKeySummary.first().click();
      await page.locator('#api-request-name').fill(requestName);
      await page.locator('#api-request-description').fill(requestDescription);
      await page.locator('input[name="scopes[]"][value="workspace:read"]').check();
      await Promise.all([
        page.waitForURL('/auth/account/profile'),
        page.getByRole('button', { name: /Submit request|Pošalji zahtjev/i }).click(),
      ]);
    }

    await openProfileSection(page, '#auth-account-security');
    const requestRow = page.locator('#api-key-requests tr', { hasText: requestName });
    await expect(requestRow).toHaveCount(1, { timeout: 10_000 });
    await page.goto('/auth/logout');

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/auth/api-keys#api-key-requests');
    const requestItem = page.locator('article.api-request-item').filter({ hasText: requestName });
    await expect(requestItem).toBeVisible();
    await Promise.all([
      page.waitForURL('/settings/auth/api-keys'),
      requestItem.getByRole('button', { name: /Approve request|Odobri zahtjev/i }).click(),
    ]);
    await page.goto('/auth/logout');

    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-security');
    const reveal = page.getByRole('link', { name: /Reveal key once|Prikaži ključ jednom/i });
    await expect(reveal).toBeVisible();
    await reveal.click();
    await expect(page.locator('[data-api-key-token]')).toContainText(/^hfp_live_/);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-security');
    await expect(page.getByText(/The secret has already been shown|Secret je već prikazan/i)).toBeVisible();
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
