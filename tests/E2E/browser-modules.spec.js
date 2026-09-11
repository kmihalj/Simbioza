import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  createEditorSurface,
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

/**
 * HR: Otvara dropdown u pomičnom modalu i provjerava da su mu gornji i
 *     donji dio stvarno iznad tijela/podnožja modala, a ne samo CSS-om vidljivi.
 * EN: Opens a dropdown in a scrollable modal and verifies its top and bottom
 *     are actually above the modal body/footer, rather than merely CSS-visible.
 */
async function expectUnclippedModalDropdown(toggle) {
  const menu = toggle.locator('xpath=following-sibling::*[contains(concat(" ", normalize-space(@class), " "), " dropdown-menu ")][1]');
  const dialog = toggle.locator('xpath=ancestor::*[contains(concat(" ", normalize-space(@class), " "), " modal-dialog-scrollable ")][1]');

  await toggle.click();
  await expect(menu).toBeVisible();
  await expect(dialog).toHaveClass(/hph-modal-dropdown-open/);
  await expect.poll(() => menu.evaluate((element) => {
    const rectangle = element.getBoundingClientRect();
    const inset = Math.max(4, Math.min(12, rectangle.height / 4));
    const x = rectangle.left + (rectangle.width / 2);
    const topTarget = document.elementFromPoint(x, rectangle.top + inset);
    const bottomTarget = document.elementFromPoint(x, rectangle.bottom - inset);

    return rectangle.width > 0
      && rectangle.height > 0
      && topTarget instanceof Element
      && bottomTarget instanceof Element
      && element.contains(topTarget)
      && element.contains(bottomTarget);
  })).toBe(true);

  await toggle.click();
  await expect(menu).toBeHidden();
  await expect.poll(() => dialog.evaluate((element) => (
    element.classList.contains('hph-modal-dropdown-open')
  ))).toBe(false);
}

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
  test('Editor settings normalize and deduplicate allowed MIME rows when saved', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    const settingsResponse = await page.goto('/settings/editor-html');
    expect(settingsResponse?.status()).toBe(200);

    const mimeTypes = page.locator('#editor-html-uploads-mime-types');
    const originalRows = (await mimeTypes.inputValue())
      .split(/\r?\n/)
      .map((row) => row.trim())
      .filter((row) => row !== '');
    expect(originalRows.length).toBeGreaterThan(0);
    const expectedRows = [...new Set(originalRows.map((row) => row.toLowerCase()))];
    await mimeTypes.fill([
      ...originalRows,
      '',
      originalRows[0].toUpperCase(),
      `  ${originalRows[0]}  `,
    ].join('\n'));

    const saveResponse = page.waitForResponse((candidate) => (
      candidate.request().method() === 'POST'
      && new URL(candidate.url()).pathname === '/settings/editor-html'
    ));
    await mimeTypes.locator('xpath=ancestor::form').getByRole('button', {
      name: /Save settings|Spremi postavke/i,
    }).click();
    expect((await saveResponse).status()).toBeLessThan(400);
    await expect(mimeTypes).toHaveValue(expectedRows.join('\n'));
  });

  test('image optimization resumes after a temporary step connection failure', async ({ page }) => {
    const optimization = (status, processed, percent) => ({
      status,
      total: 1,
      processed,
      percent,
      generated: processed,
      skipped: 0,
      documents_total: 1,
      documents_processed: processed,
      worker_busy: false,
      message: '',
    });
    let statusCalls = 0;
    let stepCalls = 0;

    await page.route(/\/settings\/workspaces\/maintenance\/images$/, async (route) => {
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({ ok: true, optimization: optimization('queued', 0, 0) }),
      });
    });
    await page.route(/\/settings\/workspaces\/maintenance\/images\/status$/, async (route) => {
      statusCalls += 1;
      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({ ok: true, optimization: optimization('running', 0, 0) }),
      });
    });
    await page.route(/\/settings\/workspaces\/maintenance\/images\/step$/, async (route) => {
      stepCalls += 1;
      if (stepCalls === 1) {
        await route.abort('connectionfailed');
        return;
      }

      await route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({ ok: true, optimization: optimization('done', 1, 100) }),
      });
    });

    await login(page, adminLogin, adminPassword);
    const response = await page.goto('/settings/workspaces/maintenance');
    expect(response?.status()).toBe(200);
    await page.locator('[data-image-optimization-start]').click();

    await expect.poll(() => statusCalls).toBeGreaterThan(0);
    await expect.poll(() => stepCalls).toBeGreaterThan(1);
    await expect(page.locator('[data-image-optimization-panel]')).toBeHidden();
    await expect(page.locator('[data-image-optimization-start]')).toBeEnabled();
  });

  test('every rendered Bootstrap modal remains interactive after repeated opening', async ({ page, request }) => {
    test.setTimeout(90_000);
    const editorPath = await createEditorSurface(request, adminApiToken, 'modal-editor');
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
      editorPath,
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

  test('Editor lookup dropdowns remain fully usable inside scrollable modals', async ({ page, request }) => {
    await page.setViewportSize({ width: 1800, height: 1000 });
    await createEditorSurface(request, adminApiToken, 'modal-dropdown-target');
    const editorPath = await createEditorSurface(request, adminApiToken, 'modal-dropdown-editor');
    await login(page, adminLogin, adminPassword);
    const response = await page.goto(editorPath);
    expect(response?.status()).toBe(200);

    await page.getByRole('button', { name: /^(Link|Poveznica)$/i }).click();
    const linkModal = page.locator('#editor-html-link-modal');
    await expectUsableModal(linkModal);
    await expectUnclippedModalDropdown(
      linkModal.locator('[data-editor-html-link-page-button]'),
    );
    await linkModal.locator('[data-bs-dismiss="modal"]').last().click();
    await expect(linkModal).toBeHidden();

    await page.getByRole('button', {
      name: /Dynamic elements|Dinamički elementi/i,
    }).click();
    await page.getByRole('button', {
      name: /Include page content|Uključi sadržaj stranice/i,
      exact: true,
    }).click();
    const includeModal = page.locator('#editor-html-document-include-modal');
    await expectUsableModal(includeModal);
    const includePageList = includeModal.locator('[data-editor-html-document-include-page-list]');
    await expect.poll(() => includePageList.locator('button').count()).toBeGreaterThan(0);
    await expectUnclippedModalDropdown(
      includeModal.locator('[data-editor-html-document-include-page-button]'),
    );
    await includeModal.locator('[data-bs-dismiss="modal"]').last().click();
    await expect(includeModal).toBeHidden();
  });

  test('Editor forwards exhausted wheel scrolling and removes selected images from the keyboard', async ({ page, request }) => {
    await page.setViewportSize({ width: 1440, height: 700 });
    const editorPath = await createEditorSurface(request, adminApiToken, 'editor-media-keyboard');
    await login(page, adminLogin, adminPassword);
    const response = await page.goto(editorPath);
    expect(response?.status()).toBe(200);

    const surface = page.locator('[data-editor-html-surface]');
    await expect(surface).toBeVisible();
    await expect.poll(() => surface.evaluate((element) => (
      getComputedStyle(element).overscrollBehaviorY
    ))).toBe('auto');

    await page.evaluate(() => {
      const spacer = document.createElement('div');
      spacer.dataset.e2eEditorScrollSpacer = '1';
      spacer.style.height = '1200px';
      document.body.append(spacer);
      window.scrollTo(0, 0);
    });
    await surface.evaluate((element) => {
      element.scrollTop = element.scrollHeight;
    });
    await surface.hover();
    await page.mouse.wheel(0, 700);
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBeGreaterThan(0);

    const insertSelectedImage = async (marker) => {
      await surface.evaluate((element, value) => {
        const paragraph = document.createElement('p');
        paragraph.dataset.e2eMediaMarker = value;
        paragraph.innerHTML = '<span class="figure editor-html-media-figure" contenteditable="false">'
          + '<img alt="Keyboard deletion fixture" '
          + 'src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==">'
          + '</span>';
        element.append(paragraph);
        element.dispatchEvent(new InputEvent('input', {
          bubbles: true,
          inputType: 'insertHTML',
        }));
      }, marker);
      const figure = surface.locator(`[data-e2e-media-marker="${marker}"] .figure`);
      await figure.click();
      await expect(figure).toHaveClass(/editor-html-media-selected/);
      return figure;
    };

    const deleteFigure = await insertSelectedImage('delete');
    await page.keyboard.press('Delete');
    await expect(deleteFigure).toHaveCount(0);

    const backspaceFigure = await insertSelectedImage('backspace');
    await page.keyboard.press('Backspace');
    await expect(backspaceFigure).toHaveCount(0);
  });

  test('shared calendar managers get a standalone list of every shared calendar', async ({ page, request }) => {
    const calendarName = `E2E shared calendar manager ${Date.now()}`;
    const created = await expectData(await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('shared-calendar-manager-create'),
      }),
      data: {
        name: calendarName,
        description: 'Standalone shared-calendar management coverage.',
        type: 'resource',
        color: '#1677ff',
        is_enabled: true,
        is_public_read: false,
        is_authenticated_read: true,
      },
    }), 201);

    await login(page, adminLogin, adminPassword);
    await page.goto('/calendars');
    const manageAction = page.getByRole('link', {
      name: /Manage shared calendars|Administriraj zajedničke kalendare/i,
    });
    await expect(manageAction).toBeVisible();
    await manageAction.click();
    await expect(page).toHaveURL((url) => url.pathname === '/calendars/manage');
    await expect(page.getByRole('heading', {
      name: /Calendar administration|Administracija kalendara/i,
    })).toBeVisible();
    await expect(page.locator('aside').filter({ hasText: /Settings|Postavke/i })).toHaveCount(0);

    const sharedRow = page.locator('tr').filter({ hasText: calendarName });
    await expect(sharedRow).toBeVisible();
    await sharedRow.getByRole('link', {
      name: /Open calendar|Otvori kalendar/i,
    }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/calendars/view/${created.uuid}`);
  });

  test('all module settings, application screens, JSON helpers, and public assets respond', async ({ page, request }) => {
    test.setTimeout(90_000);
    const editorPath = await createEditorSurface(request, adminApiToken, 'surface-editor');
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
      '/settings/auth?section=provider-oidc',
      '/settings/auth?section=provider-oauth2',
      '/settings/auth/impersonation',
      '/settings/auth/api-keys',
      '/workspaces',
      '/workspaces/manage',
      '/settings/workspaces',
      '/settings/workspaces/homepage',
      '/settings/workspaces/all',
      '/settings/workspaces/deleted',
      '/settings/personal-workspaces',
      '/settings/confluence-import',
      editorPath,
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

    /*
     * HR: Confluence import mora koristiti isti 3/9 raspored i karticu kao
     *     ostale postavke; inače bočni izbornik ulazi u sadržaj, a tekst
     *     naslova nasljeđuje boje hero elementa.
     * EN: Confluence import must use the same 3/9 layout and card as the
     *     remaining settings pages; otherwise the sidebar overlaps content
     *     and heading copy inherits hero colours.
     */
    await page.goto('/settings/confluence-import');
    const confluenceSettings = page.locator('.confluence-import-shell');
    const confluenceSidebar = confluenceSettings.locator('xpath=preceding-sibling::aside[1]');
    await expect(confluenceSettings).toHaveClass(/col-lg-9/);
    await expect(confluenceSidebar).toHaveClass(/col-lg-3/);
    await expect(confluenceSettings.locator(':scope > .card').first()).toBeVisible();
    await expect(confluenceSettings.locator('.card h1').first()).toContainText(/Confluence/i);

    const settingsGeometry = await page.locator('.confluence-import-shell').evaluate((main) => {
      const aside = main.previousElementSibling;
      const mainRect = main.getBoundingClientRect();
      const asideRect = aside?.getBoundingClientRect();

      return {
        asideRight: asideRect?.right ?? 0,
        mainLeft: mainRect.left,
        mainWidth: mainRect.width,
        viewportWidth: window.innerWidth,
      };
    });
    expect(settingsGeometry.mainLeft).toBeGreaterThanOrEqual(settingsGeometry.asideRight);
    expect(settingsGeometry.mainWidth).toBeLessThan(settingsGeometry.viewportWidth * 0.8);

    const descriptionColours = await confluenceSettings
      .locator('.card header .text-body-secondary')
      .first()
      .evaluate((description) => {
        const muted = getComputedStyle(document.documentElement)
          .getPropertyValue('--hph-muted-text')
          .trim();
        const probe = document.createElement('span');
        probe.style.color = muted;
        document.body.append(probe);
        const expected = getComputedStyle(probe).color;
        probe.remove();

        return { actual: getComputedStyle(description).color, expected };
      });
    expect(descriptionColours.actual).toBe(descriptionColours.expected);

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
      ['/confluence-import/assets.css', 'text/css'],
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

  test('single calendar settings and direct subscription use the displayed calendar', async ({ page, request }) => {
    test.setTimeout(60_000);
    const calendarName = `E2E single calendar ${Date.now()}`;
    const created = await expectData(await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('single-calendar-create'),
      }),
      data: {
        name: calendarName,
        description: 'Single-calendar settings and subscription coverage.',
        type: 'team',
        color: '#1677ff',
        is_enabled: true,
        is_public_read: false,
        is_authenticated_read: true,
      },
    }), 201);
    const calendarPath = `/calendars/view/${created.uuid}`;

    await login(page, adminLogin, adminPassword);
    await page.goto(calendarPath);
    const verifyCalendarActionIcons = async () => {
      const iconActions = page.locator('.calendar-actions .calendar-action-icon-button');
      await expect(iconActions).toHaveCount(7);
      const geometry = await iconActions.evaluateAll((actions) => actions.map((action, index) => {
        const box = action.getBoundingClientRect();
        const icon = action.querySelector('svg.calendar-action-icon');
        const overlapsAnotherAction = actions.some((other, otherIndex) => {
          if (otherIndex === index) {
            return false;
          }
          const otherBox = other.getBoundingClientRect();
          return box.left < otherBox.right - 0.5
            && box.right > otherBox.left + 0.5
            && box.top < otherBox.bottom - 0.5
            && box.bottom > otherBox.top + 0.5;
        });

        return {
          ariaLabel: action.getAttribute('aria-label') ?? '',
          buttonColor: getComputedStyle(action).color,
          height: box.height,
          iconStroke: icon instanceof SVGElement ? getComputedStyle(icon).stroke : '',
          overlapsAnotherAction,
          title: action.getAttribute('title') ?? '',
          width: box.width,
        };
      }));

      for (const action of geometry) {
        expect(action.title).not.toBe('');
        expect(action.ariaLabel).toBe(action.title);
        expect(action.width).toBeLessThanOrEqual(48);
        expect(action.height).toBeLessThanOrEqual(48);
        expect(action.iconStroke).toBe(action.buttonColor);
        expect(action.overlapsAnotherAction).toBe(false);
      }
      expect(await page.evaluate(() => document.documentElement.scrollWidth))
        .toBeLessThanOrEqual(await page.evaluate(() => window.innerWidth));
    };
    await verifyCalendarActionIcons();

    await page.getByRole('button', {
      name: /Edit this calendar's settings and access rights|Uredi postavke i prava pristupa ovog kalendara/i,
    }).click();

    const settingsModal = page.locator('#calendarCalendarModal');
    await expectUsableModal(settingsModal);
    const protectedManagerAcl = settingsModal.locator('tr[data-calendar-acl-protected]');
    await expect(protectedManagerAcl).toHaveCount(1);
    await expect(protectedManagerAcl.locator('select')).toBeDisabled();
    await expect(protectedManagerAcl.locator('select option:checked')).toHaveText('Kalendari');
    await expect(protectedManagerAcl.locator('input[type="checkbox"]')).toHaveCount(2);
    await expect(protectedManagerAcl.locator('input[type="checkbox"]').first()).toBeChecked();
    await expect(protectedManagerAcl.locator('input[type="checkbox"]').last()).toBeChecked();
    await expect(protectedManagerAcl.locator('[data-calendar-remove-acl-row]')).toBeHidden();
    await settingsModal.locator('[data-bs-dismiss="modal"]').last().click();
    await expect(settingsModal).toBeHidden();

    await page.goto('/settings/theme');
    const originalTheme = await page.locator('#active_theme').inputValue();
    const originalModePolicy = await page.locator('#mode_policy').inputValue();
    const themeIds = await page.locator('#active_theme option').evaluateAll(
      (options) => options.map((option) => option.value).filter((value) => value !== ''),
    );
    expect(themeIds).toEqual(expect.arrayContaining(['aai', 'dabar', 'simbioza', 'srce-sup', 'standard']));
    try {
      for (const themeId of themeIds) {
        for (const modePolicy of ['light', 'dark']) {
          await page.locator('#active_theme').selectOption(themeId);
          await page.locator('#mode_policy').selectOption(modePolicy);
          await Promise.all([
            page.waitForLoadState('networkidle'),
            page.getByRole('button', { name: /Save site theme|Spremi temu site-a/i }).click(),
          ]);
          await page.goto(calendarPath);
          await verifyCalendarActionIcons();
          await page.goto('/settings/theme');
        }
      }
    } finally {
      await page.locator('#active_theme').selectOption(originalTheme);
      await page.locator('#mode_policy').selectOption(originalModePolicy);
      await Promise.all([
        page.waitForLoadState('networkidle'),
        page.getByRole('button', { name: /Save site theme|Spremi temu site-a/i }).click(),
      ]);
    }

    await page.goto('/auth/logout');
    await login(page, userLogin, userPassword);
    await page.goto(calendarPath);
    await page.getByRole('button', {
      name: /Subscribe me to this calendar|Pretplati me na ovaj kalendar/i,
    }).click();
    await expect(page.locator('.calendar-sidebar .badge').filter({
      hasText: /^(Subscribed|Pretplaćeni ste)$/i,
    })).toBeVisible();

    const current = await getDataWithEtag(
      request,
      `/api/v1/calendars/${created.uuid}`,
      apiHeaders(adminApiToken),
    );
    const deleted = await request.delete(`/api/v1/calendars/${created.uuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('single-calendar-delete'),
        'If-Match': current.etag,
      }),
    });
    expect(deleted.status()).toBe(204);
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
    const firstMenuRow = page.locator('#menu-settings-table tbody tr').first();
    const menuColumnGeometry = await firstMenuRow.evaluate((row) => {
      const label = row.querySelector('.menu-label-cell');
      const labelInput = row.querySelector('.menu-label-visible');
      const target = row.querySelector('.menu-target-cell');
      const route = row.querySelector('.menu-target-select');
      const workspace = row.querySelector('[data-menu-target-workspace-button]');
      const page = row.querySelector('[data-menu-target-page-button]');
      const labelBox = label?.getBoundingClientRect();
      const targetBox = target?.getBoundingClientRect();
      const routeBox = route?.getBoundingClientRect();
      const workspaceBox = workspace?.getBoundingClientRect();
      const pageBox = page?.getBoundingClientRect();

      return {
        rowHeight: row.getBoundingClientRect().height,
        labelWidth: labelBox?.width ?? 0,
        labelInputWidth: labelInput?.getBoundingClientRect().width ?? 0,
        targetWidth: targetBox?.width ?? 0,
        routeInsideTarget: Boolean(targetBox && routeBox
          && routeBox.left >= targetBox.left
          && routeBox.right <= targetBox.right),
        pickersShareRow: Boolean(workspaceBox && pageBox
          && Math.abs(workspaceBox.top - pageBox.top) < 1
          && workspaceBox.right <= pageBox.left),
      };
    });
    expect(menuColumnGeometry.rowHeight).toBeLessThanOrEqual(120);
    expect(menuColumnGeometry.labelWidth).toBeLessThan(menuColumnGeometry.targetWidth);
    expect(menuColumnGeometry.labelInputWidth).toBeGreaterThanOrEqual(160);
    expect(menuColumnGeometry.routeInsideTarget).toBe(true);
    expect(menuColumnGeometry.pickersShareRow).toBe(true);

    const labelsBefore = await page.getByRole('textbox', { name: 'Label' })
      .evaluateAll((inputs) => inputs.map((input) => input.value));
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/menu'),
      page.getByRole('button', { name: 'Save menu configuration' }).click(),
    ]);
    await page.goto('/settings/menu?section=top');
    expect(await page.getByRole('textbox', { name: 'Label' })
      .evaluateAll((inputs) => inputs.map((input) => input.value))).toEqual(labelsBefore);

    await page.goto('/settings/auth?section=overview');
    await expect(page.getByRole('link', { name: 'OIDC', exact: true })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'OAuth2', exact: true })).toHaveCount(0);
    await page.locator('#oidc_enabled').check();
    await page.locator('#oauth2_enabled').check();
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/auth'
        && url.searchParams.get('setup_status') === 'success'),
      page.getByRole('button', { name: 'Save provider settings' }).click(),
    ]);
    await expect(page.getByRole('link', { name: 'OIDC', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'OAuth2', exact: true })).toBeVisible();

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

  test('AAI hero artwork remains complete below sticky navigation', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.setViewportSize({ width: 1600, height: 1000 });
    await page.goto('/settings/theme?theme=aai');

    const activeTheme = page.locator('#active_theme');
    const originalTheme = await activeTheme.inputValue();
    if (originalTheme !== 'aai') {
      await activeTheme.selectOption('aai');
      await Promise.all([
        page.waitForLoadState('networkidle'),
        page.getByRole('button', { name: /Save site theme|Spremi temu site-a/i }).click(),
      ]);
    }

    await page.goto('/about');
    const geometry = await page.evaluate(() => {
      const navigation = document.querySelector('.hph-primary-navigation');
      const image = document.querySelector('.hph-hero__visual img');
      if (!(navigation instanceof HTMLElement) || !(image instanceof HTMLImageElement)) {
        return null;
      }

      const navigationRect = navigation.getBoundingClientRect();
      const imageRect = image.getBoundingClientRect();

      return {
        navigationBottom: navigationRect.bottom,
        navigationPosition: getComputedStyle(navigation).position,
        imageTop: imageRect.top,
        imageNaturalWidth: image.naturalWidth,
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: window.innerWidth,
      };
    });

    expect(geometry).not.toBeNull();
    expect(geometry.navigationPosition).toBe('sticky');
    expect(geometry.imageNaturalWidth).toBeGreaterThan(0);
    expect(geometry.imageTop).toBeGreaterThanOrEqual(geometry.navigationBottom - 1);
    expect(geometry.documentWidth).toBeLessThanOrEqual(geometry.viewportWidth);

    if (originalTheme !== 'aai') {
      await page.goto('/settings/theme?theme=aai');
      await page.locator('#active_theme').selectOption(originalTheme);
      await Promise.all([
        page.waitForLoadState('networkidle'),
        page.getByRole('button', { name: /Save site theme|Spremi temu site-a/i }).click(),
      ]);
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

    const clonedEditor = page.locator('#theme-editor-form');
    await clonedEditor.locator('[data-theme-section-id="hero"] > summary').click();
    await clonedEditor.locator('[data-theme-hero-visual-width]').fill('677');
    await clonedEditor.locator('[data-theme-hero-visual-max-height]').fill('333');
    await clonedEditor.locator('[data-theme-hero-visual-top]').fill('-77');
    await clonedEditor.locator('[data-theme-hero-visual-right]').fill('-33');
    await clonedEditor.locator('[data-theme-hero-visual-allow-overflow]').check();
    await Promise.all([
      page.waitForLoadState('networkidle'),
      page.getByRole('button', { name: 'Save theme' }).click(),
    ]);

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
    const importedEditor = page.locator('#theme-editor-form');
    await importedEditor.locator('[data-theme-section-id="hero"] > summary').click();
    await expect(importedEditor.locator('[data-theme-hero-visual-width]')).toHaveValue('677');
    await expect(importedEditor.locator('[data-theme-hero-visual-max-height]')).toHaveValue('333');
    await expect(importedEditor.locator('[data-theme-hero-visual-top]')).toHaveValue('-77');
    await expect(importedEditor.locator('[data-theme-hero-visual-right]')).toHaveValue('-33');
    await expect(importedEditor.locator('[data-theme-hero-visual-allow-overflow]')).toBeChecked();

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

      return created;
    };

    const alphaDocument = await createWorkspacePage(alpha, `Alpha Theme Page ${suffix}`);
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

    await page.goto(`/editor-html?document=${encodeURIComponent(alphaDocument.id)}&lang=en`);
    await expect(page.locator('[data-editor-html-surface]')).toBeVisible();
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
    const personalLink = page.locator(
      '[data-simbioza-personal-workspace-card] a[href*="/workspace/osobno-"]',
    );
    await expect(personalLink).toBeVisible();
    const personalPath = await personalLink.getAttribute('href');
    expect(personalPath).toMatch(/^\/workspace\/osobno-/);

    await personalLink.click();
    await expect(page).toHaveURL(new RegExp(`${personalPath}$`));
    const englishTitle = page.getByRole('heading', { name: /^Workspace of:/i }).first();
    await expect(englishTitle).toBeVisible();
    await expect(page.getByText(/Personal workspace of /i).first()).toBeVisible();
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
    await expect(page.getByText(/^Personal workspace of /i)).toHaveCount(0);

    await page.goto('/workspaces');
    await expect(page.locator('main').locator(`a[href="${personalPath}"]`)).toHaveCount(1);

    // HR: Opća pretraga odmah nudi obična vidljiva područja, ali sva osobna
    //     područja sažima u jednu mogućnost umjesto popisa svakog vlasnika.
    // EN: Global search immediately offers ordinary visible Workspaces while
    //     aggregating every personal Workspace into one option.
    await page.goto('/search');
    const workspaceFilter = page.locator('#workspace-search-workspace-button');
    await expect(workspaceFilter).toBeVisible();
    await workspaceFilter.click();
    await expect(page.locator('#workspace-search-scope-personal')).toHaveCount(1);
    await expect(page.locator(`input[name="workspaces[]"][value="${personalPath.split('/').at(-1)}"]`))
      .toHaveCount(0);
    await expect.poll(async () => page.locator('input[name="workspaces[]"]').count()).toBeGreaterThan(2);

    await page.goto('/auth/logout');
    const guestResponse = await page.goto(personalPath);
    expect(guestResponse?.status()).toBe(403);

    await login(page, userLogin, userPassword);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');
    await expect(page.locator(
      `[data-simbioza-personal-workspace-card] a[href="${personalPath}"]`,
    )).toHaveCount(1);

    await page.goto('/auth/logout');
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/personal-workspaces');
    await expect(page.locator('#personal-workspaces-auto-create')).toBeChecked();
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(1);

    await page.goto('/workspaces');
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(0);
    const personalWorkspaceIndex = page.getByRole('link', { name: /Personal Workspaces|Osobna područja/i });
    await expect(personalWorkspaceIndex).toBeVisible();
    await personalWorkspaceIndex.click();
    await expect(page).toHaveURL(/\/workspaces\?personal=1$/);
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(1);
  });

  test('users can create their personal Workspace only when enabled and keep it afterwards', async ({ page, request }) => {
    test.setTimeout(90_000);
    const suffix = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    const password = 'E2ePersonalWorkspace!2026';
    const creator = {
      login: `personal-creator-${suffix}@example.invalid`,
      name: `E2E Personal Creator ${suffix}`,
    };
    const disabled = {
      login: `personal-disabled-${suffix}@example.invalid`,
      name: `E2E Personal Disabled ${suffix}`,
    };
    const createUser = async ({ login: loginIdentifier, name }) => expectData(
      await request.post('/api/v1/users', {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey('personal-workspace-user'),
        }),
        data: {
          login_identifier: loginIdentifier,
          password,
          is_active: true,
          is_admin: false,
          provider_access: { local: true },
          attributes: {
            display_name: name,
            email: loginIdentifier,
          },
        },
      }),
      201,
    );
    const settingsForm = () => page.locator('[data-personal-workspace-settings]');
    const saveSettings = async () => {
      const response = page.waitForResponse((candidate) => (
        candidate.request().method() === 'POST'
        && new URL(candidate.url()).pathname === '/settings/personal-workspaces'
      ));
      await settingsForm().getByRole('button', { name: /Save|Spremi/i }).click();
      expect((await response).status()).toBeLessThan(400);
      await expect(page).toHaveURL(/\/settings\/personal-workspaces$/);
    };

    await createUser(creator);
    await createUser(disabled);

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/personal-workspaces');
    const automaticCreation = page.locator('#personal-workspaces-auto-create');
    const selfCreation = page.locator('#personal-workspaces-self-create');
    const personalWorkspaceTable = page.locator('table');
    await expect(personalWorkspaceTable.getByRole('row', { name: new RegExp(creator.name) }))
      .toHaveCount(0);
    await expect(personalWorkspaceTable.getByRole('row', { name: new RegExp(disabled.name) }))
      .toHaveCount(0);
    await expect(automaticCreation).toBeChecked();
    await expect(selfCreation).toBeDisabled();
    await automaticCreation.uncheck();
    await expect(selfCreation).toBeEnabled();
    await selfCreation.check();
    await saveSettings();
    await expect(automaticCreation).not.toBeChecked();
    await expect(selfCreation).toBeChecked();
    await expect(selfCreation).toBeEnabled();

    await page.goto('/auth/logout');
    await login(page, creator.login, password);
    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');

    const personalCard = page.locator('[data-simbioza-personal-workspace-card]');
    const appearanceCard = page.locator('[data-simbioza-appearance-card]');
    const followingCard = page.locator('[data-simbioza-following-card]');
    await expect(personalCard.getByRole('heading', {
      name: /Create my personal Workspace|Izradi moje osobno područje/i,
    })).toBeVisible();
    await expect(followingCard.locator('[data-simbioza-personal-workspace-card]')).toHaveCount(0);
    await expect(followingCard.locator('[data-simbioza-appearance-card]')).toHaveCount(0);
    if (await appearanceCard.count() > 0) {
      await expect(appearanceCard).toBeVisible();
    }
    await expect(page.locator('a.dropdown-item').filter({
      hasText: /My Workspace|Moje područje/i,
    })).toHaveCount(0);

    await Promise.all([
      page.waitForURL((url) => (
        url.pathname === '/auth/account/profile'
        && url.hash === '#simbioza-user-personal-workspace'
      )),
      personalCard.getByRole('button', {
        name: /Create my personal Workspace|Izradi moje osobno područje/i,
      }).click(),
    ]);
    await expect(personalCard.getByRole('heading', {
      name: /My personal Workspace|Moje osobno područje/i,
    })).toBeVisible();
    const personalLink = personalCard.locator('a[href*="/workspace/osobno-"]');
    await expect(personalLink).toBeVisible();
    const personalPath = await personalLink.getAttribute('href');
    expect(personalPath).toMatch(/^\/workspace\/osobno-/);

    const userDropdown = page.locator('li.nav-item.dropdown').filter({ hasText: creator.name });
    await userDropdown.locator(':scope > [data-bs-toggle="dropdown"]').click();
    const profileMenuItem = userDropdown.getByRole('link', { name: /My profile|Moj profil/i });
    const workspaceMenuItem = userDropdown.getByRole('link', { name: /My Workspace|Moje područje/i });
    await expect(profileMenuItem).toBeVisible();
    await expect(workspaceMenuItem).toBeVisible();
    await expect(workspaceMenuItem).toHaveAttribute('href', personalPath);
    const personalMenuOrder = await userDropdown.locator('a.dropdown-item').allTextContents();
    expect(personalMenuOrder.indexOf((await profileMenuItem.textContent()).trim()) + 1)
      .toBe(personalMenuOrder.indexOf((await workspaceMenuItem.textContent()).trim()));

    await page.goto('/auth/logout');
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/all');
    await expect(page.locator(`a[href="${personalPath}"]`)).toHaveCount(0);
    await expect(page.getByText(creator.name, { exact: false })).toHaveCount(0);
    await page.goto('/settings/workspaces/maintenance');
    await expect(page.getByRole('row', { name: /Personal Workspaces|Osobna područja/i })).toBeVisible();
    await expect(page.getByText(creator.name, { exact: false })).toHaveCount(0);

    await page.goto('/settings/personal-workspaces');
    const createdWorkspaceRow = personalWorkspaceTable.getByRole('row', {
      name: new RegExp(creator.name),
    });
    await expect(createdWorkspaceRow).toHaveCount(1);
    await expect(createdWorkspaceRow.locator('input, button')).toHaveCount(0);
    await expect(personalWorkspaceTable.getByRole('row', { name: new RegExp(disabled.name) }))
      .toHaveCount(0);
    await expect(selfCreation).toBeChecked();
    await selfCreation.uncheck();
    await saveSettings();
    await expect(automaticCreation).not.toBeChecked();
    await expect(selfCreation).not.toBeChecked();

    await page.goto('/auth/logout');
    await login(page, creator.login, password);
    await page.goto('/auth/account/profile');
    await expect(page.locator(
      `[data-simbioza-personal-workspace-card] a[href="${personalPath}"]`,
    )).toHaveCount(1);
    const existingUserDropdown = page.locator('li.nav-item.dropdown').filter({ hasText: creator.name });
    await existingUserDropdown.locator(':scope > [data-bs-toggle="dropdown"]').click();
    await expect(existingUserDropdown.getByRole('link', { name: /My Workspace|Moje područje/i }))
      .toHaveAttribute('href', personalPath);

    await page.goto('/auth/logout');
    await login(page, disabled.login, password);
    await page.goto('/auth/account/profile');
    await expect(page.locator('[data-simbioza-personal-workspace-card]')).toHaveCount(0);
    await expect(page.locator('a.dropdown-item').filter({
      hasText: /My Workspace|Moje područje/i,
    })).toHaveCount(0);

    // HR: Vraća početnu politiku zbog sljedećih testova u istoj razvojnoj bazi.
    // EN: Restores the default policy for subsequent tests in the same development database.
    await page.goto('/auth/logout');
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/personal-workspaces');
    await automaticCreation.check();
    await expect(selfCreation).toBeDisabled();
    await saveSettings();
    await expect(automaticCreation).toBeChecked();
    await expect(selfCreation).toBeDisabled();
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
      return created;
    };

    const publicPage = await publishPage(publicTitle, publicSlug);
    const signedInPage = await publishPage(signedInTitle, signedInSlug);
    const homepagePagePicker = (audience) => page
      .locator(`[data-workspace-homepage-target="${audience}"]`)
      .locator('xpath=..');
    const homepageWorkspacePicker = (audience) => page.locator(
      audience === 'personal'
        ? '#workspace-personal-homepage-workspace [data-workspace-lookup-picker]'
        : `#workspace-${audience}-workspace [data-workspace-lookup-picker]`,
    );

    const chooseHomepageWorkspace = async (audience, label, query = label) => {
      const picker = homepageWorkspacePicker(audience);
      await expect(picker).toHaveAttribute('data-workspace-lookup-ready', '1');
      await picker.locator('[data-workspace-lookup-toggle]').click();
      await picker.locator('[data-workspace-lookup-search]').fill(query);
      await picker
        .locator('[data-workspace-lookup-list]')
        .getByRole('button', { name: label, exact: true })
        .click();
    };
    const chooseHomepagePage = async (audience, label, query) => {
      const picker = homepagePagePicker(audience);
      await expect(picker).toHaveAttribute('data-workspace-lookup-ready', '1');
      await picker.locator('[data-workspace-lookup-toggle]').click();
      await picker.locator('[data-workspace-lookup-search]').fill(query);
      await picker
        .locator('[data-workspace-lookup-list]')
        .getByRole('button', { name: label, exact: true })
        .click();
    };

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/homepage');
    await expect(page.getByRole('heading', { name: 'Application homepage' })).toBeVisible();
    await chooseHomepageWorkspace('public', homepageWorkspace.name, String(suffix));
    await expect(homepagePagePicker('public').locator('[data-workspace-lookup-toggle]'))
      .toHaveText('Select a page');
    await chooseHomepagePage('public', publicTitle, 'Public Homepage');
    await expect(page.locator('[data-workspace-homepage-target="public"]'))
      .toHaveValue(`page:${publicPage.workspace_node.id}`);

    await chooseHomepageWorkspace('authenticated', 'All workspaces', 'All');
    await chooseHomepagePage(
      'authenticated',
      `${homepageWorkspace.name} / ${signedInTitle}`,
      signedInTitle,
    );
    await expect(page.locator('[data-workspace-homepage-target="authenticated"]'))
      .toHaveValue(`page:${signedInPage.workspace_node.id}`);
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
    await expect(homepagePagePicker('personal')).toBeVisible();
    await chooseHomepageWorkspace('personal', homepageWorkspace.name, String(suffix));
    await chooseHomepagePage('personal', publicTitle, 'Public Homepage');
    await expect(page.locator('[data-workspace-homepage-target="personal"]'))
      .toHaveValue(`page:${publicPage.workspace_node.id}`);
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save personal homepage|Spremi osobnu naslovnicu/i }).click(),
    ]);
    await page.goto('/');
    await expect(page).toHaveURL(new RegExp(`/workspace/${workspaceSlug}/${publicSlug}\\?lang=en$`));

    await page.goto('/auth/account/profile');
    await openProfileSection(page, '#auth-account-personal');
    await chooseHomepageWorkspace('personal', 'All workspaces', 'All');
    await expect(page.locator('[data-workspace-homepage-target="personal"]')).toHaveValue('default');
    await Promise.all([
      page.waitForURL('/auth/account/profile'),
      page.getByRole('button', { name: /Save personal homepage|Spremi osobnu naslovnicu/i }).click(),
    ]);
    await page.goto('/auth/logout');

    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspaces/homepage');
    await chooseHomepageWorkspace('public', homepageWorkspace.name, String(suffix));
    await chooseHomepagePage('public', 'Summaries', 'Summ');
    await chooseHomepageWorkspace('authenticated', 'All workspaces', 'All');
    await expect(homepagePagePicker('authenticated').locator('[data-workspace-lookup-toggle]'))
      .toHaveText('Use the public homepage');
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
    await chooseHomepageWorkspace('public', 'All workspaces', 'All');
    await expect(homepagePagePicker('public').locator('[data-workspace-lookup-toggle]'))
      .toHaveText('Built-in application page');
    await chooseHomepageWorkspace('authenticated', 'All workspaces', 'All');
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
    await expect(page.getByRole('textbox', { name: 'SMTP host' })).toHaveValue('127.0.0.1');
    await expect(page.getByRole('spinbutton', { name: 'Port' })).toHaveValue('9');
    await expect(page.getByRole('combobox', { name: 'Encryption' })).toHaveValue('none');
    await expect(page.getByRole('textbox', { name: 'Username' })).toHaveValue('');

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
