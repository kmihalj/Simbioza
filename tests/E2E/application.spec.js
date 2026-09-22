import { expect, test } from '@playwright/test';
import { execFile } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { pathToFileURL } from 'node:url';
import { promisify } from 'node:util';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  expectUsableModal,
  idempotencyKey,
  login,
} from './helpers.js';

const execFileAsync = promisify(execFile);

const {
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
  adminApiToken: apiToken,
} = e2eEnvironment();

/**
 * HR: Klikom predaje stvarni HTML obrazac, čeka navigaciju i potvrđuje odgovor
 * očekivane POST rute bez utrke između novog dokumenta i sljedeće radnje.
 * EN: Submits the real HTML form by clicking, waits for navigation, and verifies
 * the expected POST response without racing the next action against the new document.
 */
async function submitFormAndExpectPost(page, button, expectedPath) {
  const responsePromise = page.waitForResponse((response) => response.request().method() === 'POST'
    && new URL(response.url()).pathname === expectedPath);

  const [response] = await Promise.all([
    responsePromise,
    button.click(),
  ]);
  expect([200, 302, 303]).toContain(response.status());
  await page.waitForLoadState('load');
}

/**
 * HR: Zatvara modal njegovom eksplicitnom kontrolom i čeka dovršetak fade
 *     tranzicije prije sljedećeg otvaranja.
 * EN: Closes a modal through its explicit control and waits for the fade
 *     transition to finish before the next modal opens.
 */
async function closeModal(dialog) {
  await dialog.locator('[data-bs-dismiss="modal"]').last().click();
  await expect(dialog).toBeHidden();
}

test.describe('browser flows', () => {
  test('mobile navigation, hero artwork, equal hero sizes, and edge-to-edge layout work', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const homeResponse = await page.goto('/');

    expect(homeResponse?.status()).toBe(200);
    await expect(page.locator('.hph-hero')).toBeVisible();

    const visual = page.locator('.hph-hero__visual img:visible');
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

  test('sticky language selector stays above overlapping hero content', async ({ page }) => {
    // HR: Vidljivost nije dovoljna: hit-test potvrđuje da ni navigacija ni hero
    //     ne presreću klik na jezičnu stavku otvorenu iz ljepljivog zaglavlja.
    // EN: Visibility is insufficient: hit testing proves navigation and hero
    //     do not intercept a click on a language item opened from the sticky header.
    await page.setViewportSize({ width: 990, height: 506 });
    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    const stickyHeader = page.locator('.hph-site-header--sticky');
    const languageControl = stickyHeader.locator('.hph-site-header__control--language');
    const languageToggle = languageControl.locator('[data-bs-toggle="dropdown"]');
    const languageMenu = languageControl.locator('.dropdown-menu');
    const alternateLanguage = languageMenu.locator('.dropdown-item').last();

    await expect(stickyHeader).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 300));
    await expect.poll(() => stickyHeader.evaluate(
      (element) => Math.round(element.getBoundingClientRect().top),
    )).toBe(0);

    await languageToggle.click();
    await expect(languageMenu).toBeVisible();
    await expect(alternateLanguage).toBeVisible();

    const stacking = await alternateLanguage.evaluate((element) => {
      const rectangle = element.getBoundingClientRect();
      const target = document.elementFromPoint(
        rectangle.left + (rectangle.width / 2),
        rectangle.top + (rectangle.height / 2),
      );

      return {
        insideViewport: rectangle.top >= 0 && rectangle.bottom <= window.innerHeight,
        receivesPointer: target instanceof Element && element.contains(target),
      };
    });
    expect(stacking.insideViewport).toBe(true);
    expect(stacking.receivesPointer).toBe(true);
  });

  test('Setup checks releases and presents module versions in responsive rows', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.route('**/settings/check-updates/status', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          ok: true,
          checked_at: 1789552800,
          checked_at_iso: '2026-09-16T12:00:00+02:00',
          failures: 0,
          updates: 2,
          components: [
            {
              package: 'aaieduhr/simbioza',
              kind: 'application',
              installed_version: '0.1.71',
              latest_version: '9.9.9',
              status: 'update_available',
            },
            {
              package: 'aaieduhr/heartphrame-module-menu',
              kind: 'module',
              installed_version: '0.1.12',
              latest_version: '0.1.13',
              status: 'update_available',
            },
          ],
        }),
      });
    });
    const response = await page.goto('/settings/setup');

    expect(response?.status()).toBe(200);
    const updateStatus = await page.evaluate(async () => {
      const statusResponse = await fetch('/settings/setup/application-update-status', {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });

      return { status: statusResponse.status, payload: await statusResponse.json() };
    });
    expect(updateStatus.status).toBe(200);
    expect(updateStatus.payload.ok).toBe(true);
    expect(updateStatus.payload.update).toEqual(expect.objectContaining({
      state: expect.any(String),
      stage: expect.any(String),
      current_version: expect.any(String),
    }));
    await expect(page.locator('[data-setup-update-overlay]')).toBeHidden();
    await expect(page.locator('[data-setup-update-progress]')).toHaveAttribute('role', 'progressbar');
    const moduleTable = page.locator('.setup-modules-grid[role="table"]');
    await expect(moduleTable).toBeVisible();
    await expect(moduleTable.getByRole('columnheader', { name: /^(Module|Modul)$/i })).toBeVisible();
    await expect(moduleTable.getByRole('columnheader', { name: /^(Version|Verzija)$/i })).toBeVisible();
    await expect(moduleTable.getByRole('columnheader', { name: /^(Actions|Radnje)$/i })).toBeVisible();
    await expect.poll(() => moduleTable.locator('.setup-module-row').count()).toBeGreaterThan(5);
    await expect(page.locator('[data-setup-application-update]')).toContainText('9.9.9');
    const menuModule = moduleTable.locator('[data-component-package="aaieduhr/heartphrame-module-menu"]');
    await expect(menuModule.locator('[data-component-installed-version]')).toHaveText('0.1.12');
    await expect(menuModule.locator('[data-component-update-version]')).toContainText('0.1.13');

    const stateButtons = moduleTable.getByRole('button', {
      name: /^(Enable|Disable|Uključi|Isključi)$/i,
    });
    await expect.poll(() => stateButtons.count()).toBeGreaterThan(0);
    await expect(stateButtons.first()).toHaveClass(/btn-sm/);

    await page.setViewportSize({ width: 390, height: 844 });
    await expect(moduleTable).toBeVisible();
    const mobileModule = moduleTable.locator('.setup-module-row').filter({
      has: page.getByRole('button', { name: /^(Enable|Disable|Uključi|Isključi)$/i }),
    }).first();
    await expect(mobileModule).toBeVisible();
    const moduleLayout = await moduleTable.evaluate((grid) => {
      const rectangle = grid.getBoundingClientRect();
      const overflowingChildren = Array.from(grid.querySelectorAll('*')).filter((element) => {
        const childRectangle = element.getBoundingClientRect();

        return childRectangle.width > 0
          && (childRectangle.left < rectangle.left - 1 || childRectangle.right > rectangle.right + 1);
      });

      return {
        left: rectangle.left,
        right: rectangle.right,
        clientWidth: grid.clientWidth,
        scrollWidth: grid.scrollWidth,
        overflowingChildren: overflowingChildren.length,
        viewportWidth: window.innerWidth,
      };
    });
    expect(moduleLayout.left).toBeGreaterThanOrEqual(0);
    expect(moduleLayout.right).toBeLessThanOrEqual(moduleLayout.viewportWidth);
    expect(moduleLayout.scrollWidth).toBeLessThanOrEqual(moduleLayout.clientWidth);
    expect(moduleLayout.overflowingChildren).toBe(0);
    const mobileLayout = await mobileModule.evaluate((row) => {
      const rectangle = row.getBoundingClientRect();
      const actionCell = row.querySelector('.setup-module-actions')?.getBoundingClientRect();

      return {
        display: getComputedStyle(row).display,
        left: rectangle.left,
        right: rectangle.right,
        actionRight: actionCell?.right ?? Number.POSITIVE_INFINITY,
        viewportWidth: window.innerWidth,
      };
    });
    expect(mobileLayout.display).toBe('block');
    expect(mobileLayout.left).toBeGreaterThanOrEqual(0);
    expect(mobileLayout.right).toBeLessThanOrEqual(mobileLayout.viewportWidth);
    expect(mobileLayout.actionRight).toBeLessThanOrEqual(mobileLayout.viewportWidth);

    /*
     * HR: Aktivna GUI nadogradnja mora nakon navigacije obnoviti trajni prikaz
     *     napretka iz privatnog statusa pozadinskog procesa.
     * EN: An active GUI update must restore its persistent progress screen
     *     from the background process's private status after navigation.
     */
    const e2eProject = process.env.HPH_E2E_PROJECT;
    expect(e2eProject).toBeTruthy();
    const updateStatusPath = join(e2eProject, 'data/application-update-status.json');
    await writeFile(updateStatusPath, JSON.stringify({
      state: 'running',
      stage: 'configuration',
      progress: 42,
      started_at: new Date().toISOString(),
      finished_at: null,
      pid: null,
      message: '',
    }));
    try {
      const progressResponse = await page.goto('/settings/setup');
      expect(progressResponse?.status()).toBe(200);
      const updateOverlay = page.locator('[data-setup-update-overlay]');
      await expect(updateOverlay).toBeVisible();
      await expect(updateOverlay.locator('[data-setup-update-progress]')).toHaveAttribute('aria-valuenow', '42');
      await expect(updateOverlay.locator('[data-setup-update-stage]')).toContainText(
        /existing theme configuration|konfiguraciju postojećih tema/i,
      );
    } finally {
      await rm(updateStatusPath, { force: true });
    }
  });

  test('local administrator login immediately activates rights and they can be disabled', async ({ page }) => {
    await page.goto('/settings');
    await expect(page).toHaveURL(/\/auth\/login\?next=%2Fsettings/);

    await login(page, adminLogin, adminPassword);
    await expect(page).toHaveURL(/\/settings$/);
    await expect(page.locator('body')).not.toContainText(/Access denied|Pristup nije dozvoljen/i);

    const accountDropdown = page.locator('.hph-site-header__control--account li.nav-item.dropdown');
    await accountDropdown.locator(':scope > [data-bs-toggle="dropdown"]').click();
    const activeSwitch = accountDropdown.getByRole('switch', { name: /Administrator/i });
    await expect(activeSwitch).toHaveAttribute('aria-checked', 'true');
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/'),
      activeSwitch.click(),
    ]);

    await accountDropdown.locator(':scope > [data-bs-toggle="dropdown"]').click();
    await expect(accountDropdown.getByRole('switch', { name: /Administrator/i }))
      .toHaveAttribute('aria-checked', 'false');
    await expect(accountDropdown.getByText(/Administration|Administracija/i)).toHaveCount(0);

    await page.goto('/settings');
    await expect(page).toHaveURL(/\/account\/administrator\?next=%2Fsettings/);

    await page.goto('/settings/calendar');
    await expect(page).toHaveURL(/\/account\/administrator\?next=%2Fsettings%2Fcalendar/);
    await page.goto('/auth/logout');
    await expect(page).toHaveURL(/\/auth\/login$/);
    await expect(page.locator('#local_override_login')).toBeVisible();
  });

  test('authenticated non-administrator receives a real 403 response', async ({ page }) => {
    await login(page, userLogin, userPassword);
    const response = await page.goto('/settings');

    expect(response?.status()).toBe(403);
    await expect(page.locator('body')).toContainText(/Access denied|Pristup nije dozvoljen/i);
  });

  test('Workspace manager organizes the complete tree without opening a denied page', async ({
    page,
    request,
  }) => {
    const suffix = Date.now();
    const workspaceSlug = `e2e-manager-tree-${suffix}`;
    const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: apiHeaders(apiToken),
    }));
    const ordinaryUser = users.find((user) => user.login_identifier === userLogin);
    expect(ordinaryUser?.id).toBeTruthy();

    const workspace = await expectData(await request.post('/api/v1/workspaces', {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('manager-tree-workspace'),
      }),
      data: {
        name: 'E2E Manager Tree',
        slug: workspaceSlug,
        visibility: 'restricted',
      },
    }), 201);
    expect(workspace.slug).toBe(workspaceSlug);

    const firstNode = await expectData(await request.post(
      `/api/v1/workspaces/${workspaceSlug}/nodes`,
      {
        headers: apiHeaders(apiToken, {
          'Idempotency-Key': idempotencyKey('manager-tree-first'),
        }),
        data: {
          node_type: 'internal_link',
          title: 'Manager Tree First',
          slug: `manager-tree-first-${suffix}`,
          target_url: '/about',
          sort_order: 10,
        },
      },
    ), 201);
    const secondNode = await expectData(await request.post(
      `/api/v1/workspaces/${workspaceSlug}/nodes`,
      {
        headers: apiHeaders(apiToken, {
          'Idempotency-Key': idempotencyKey('manager-tree-second'),
        }),
        data: {
          node_type: 'internal_link',
          title: 'Manager Tree Second',
          slug: `manager-tree-second-${suffix}`,
          target_url: '/about',
          sort_order: 20,
        },
      },
    ), 201);

    await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('manager-tree-acl'),
      }),
      data: {
        subjects: [{
          type: 'user',
          id: ordinaryUser.id,
          permissions: {
            can_view: false,
            can_add: false,
            can_edit: false,
            can_publish: false,
            can_delete: false,
            can_manage: true,
          },
        }],
      },
    }));
    await expectData(await request.put(
      `/api/v1/workspaces/${workspaceSlug}/nodes/${firstNode.id}/acl`,
      {
        headers: apiHeaders(apiToken, {
          'Idempotency-Key': idempotencyKey('manager-tree-node-acl'),
        }),
        data: {
          subjects: [{
            type: 'user',
            id: ordinaryUser.id,
            permissions: {
              can_view: false,
              can_add: false,
              can_edit: false,
              can_publish: false,
              can_delete: false,
              can_manage: false,
            },
          }],
        },
      },
    ));

    await login(page, userLogin, userPassword);
    const workspaceResponse = await page.goto(`/workspace/${workspaceSlug}`);
    expect(workspaceResponse?.status()).toBe(200);
    await expect(page.getByRole('button', { name: /Edit tree|Uredi stablo/i })).toBeVisible();
    await expect(page.locator(`a[href^="/workspaces/manage?workspace=${workspaceSlug}"]`)).toHaveCount(1);
    await expect(page.getByText('Manager Tree First', { exact: true })).toHaveCount(0);

    await page.getByRole('button', { name: /Edit tree|Uredi stablo/i }).click();
    const organizer = page.locator('[data-workspace-tree-editor]');
    await expect(organizer).toBeVisible();
    const rows = organizer.locator('[data-workspace-tree-order-row]');
    await expect(rows).toHaveCount(2);
    await expect(rows.nth(0)).toContainText(/Unavailable page|Nedostupna stranica/i);
    await expect(rows.nth(0)).not.toContainText('Manager Tree First');
    await expect(rows.nth(1)).toContainText('Manager Tree Second');
    await expect(organizer.getByRole('button', {
      name: /Add item|Dodaj stavku/i,
      exact: true,
    })).toHaveCount(1);
    await expect(rows.nth(0).locator('.workspace-tree-node-edit')).toHaveCount(0);
    await expect(rows.nth(1).locator('.workspace-tree-node-edit')).toHaveCount(1);

    await rows.nth(0).getByRole('button', { name: /Move down|Pomakni dolje/i }).click();
    await submitFormAndExpectPost(
      page,
      organizer.locator('button[type="submit"]'),
      '/workspaces/tree/order',
    );

    const reorderedTree = await expectData(await request.get(
      `/api/v1/workspaces/${workspaceSlug}/tree?lang=en`,
      { headers: apiHeaders(apiToken) },
    ));
    expect(reorderedTree.map((node) => node.id)).toEqual([secondNode.id, firstNode.id]);

    const manageResponse = await page.goto(`/workspaces/manage?workspace=${workspaceSlug}`);
    expect(manageResponse?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: /Workspace data|Podaci područja/i })).toBeVisible();
    await expect(page.getByRole('heading', { name: /Members and permissions|Članovi i prava/i })).toBeVisible();
  });

  test('administrator publishes content while drafts and immutable versions remain separated', async ({
    browser,
    page,
    request,
  }) => {
    test.setTimeout(180_000);

    let workspaceSlug = `e2e-content-workspace-${Date.now()}`;
    const pageSlug = `e2e-published-page-${Date.now()}`;
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
    await expect(page).toHaveURL((url) => url.pathname === '/workspaces/manage');
    workspaceSlug = new URL(page.url()).searchParams.get('workspace') ?? workspaceSlug;
    await expect(page.getByRole('link', { name: 'Open Workspace' })).toBeVisible();

    await page.getByRole('link', { name: 'Open Workspace' }).click();
    const workspaceCreateToggle = page.locator(
      'button.btn-outline-primary.workspace-tree-card-action[data-bs-target="#workspace-create-page"]',
    );
    await workspaceCreateToggle.click();
    const createPanel = page.locator('#workspace-create-page');
    await createPanel.evaluate((node) => {
      if (window.bootstrap?.Collapse) {
        window.bootstrap.Collapse.getOrCreateInstance(node, { toggle: false }).show();
      } else {
        node.classList.add('show');
      }
    });
    await expect(createPanel).toBeVisible();
    const localizedPageTitle = page.locator('[id^="workspace-page-title-"]:visible');
    await expect(localizedPageTitle).toBeVisible({ timeout: 5_000 });
    await localizedPageTitle.fill('E2E Published Page');
    await page.locator('#workspace-page-slug').fill(pageSlug);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: /Create and edit|Kreiraj i uredi/i }),
      '/workspaces/page/create',
    );
    await expect(page).toHaveURL((url) => url.pathname === '/editor-html'
      && url.searchParams.get('document') === pageSlug);

    /*
     * HR: Kada je Workspace modul instaliran, dokument se otvara i stvara
     *     isključivo kroz stablo područja. Samostalne akcije Otvori i Kreiraj
     *     zato ne smiju biti prikazane, dok preostale akcije koriste pune
     *     tematske stilove.
     * EN: With the Workspace module installed, documents are opened and
     *     created exclusively through the workspace tree. The standalone Open
     *     and Create actions must therefore be absent, while the remaining
     *     actions keep their filled theme styles.
    */
    await expect(page.getByRole('button', { name: 'Open', exact: true })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Create', exact: true })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Save and submit for review' })).toBeVisible();
    for (const actionName of ['Translations', 'History']) {
      const action = page.getByRole('button', { name: actionName, exact: true });
      if (await action.count() > 0) {
        await expect(action).toHaveClass(/btn-secondary/);
        await expect(action).not.toHaveClass(/btn-outline-secondary/);
      }
    }
    const viewAction = page.getByRole('link', { name: 'View', exact: true });
    await expect(viewAction).toHaveClass(/btn-secondary/);
    await expect(viewAction).not.toHaveClass(/btn-outline-secondary/);

    /*
     * HR: Provjerava svaki modal dostupan u zaglavlju uređivača, ne samo
     *     prijavljeni dijalog za povijest verzija.
     * EN: Verifies every modal exposed by the editor header, not only the
     *     reported version-history dialog.
     */
    for (const modalAction of [
      { button: 'Translations', dialog: 'Copy translation' },
      { button: 'History', dialog: 'Version history' },
    ]) {
      const trigger = page.getByRole('button', { name: modalAction.button, exact: true });
      if (await trigger.count() === 0) {
        continue;
      }

      await trigger.click();
      const dialog = page.getByRole('dialog', { name: modalAction.dialog });
      await expectUsableModal(dialog);
      await closeModal(dialog);
    }

    const editorSurface = page.locator('[data-editor-html-surface]');
    await expect(editorSurface).toBeVisible();

    for (const toolbarModal of [
      { button: 'Insert calendar', dialog: 'Insert calendar' },
      { button: 'Insert task list', dialog: 'Insert task list' },
    ]) {
      const trigger = page.getByRole('button', { name: toolbarModal.button, exact: true });
      if (await trigger.count() === 0) {
        continue;
      }

      await trigger.click();
      const dialog = page.getByRole('dialog', { name: toolbarModal.dialog });
      await expectUsableModal(dialog);
      await closeModal(dialog);
    }

    // HR: Novi kompaktni prikaz privitka mora ostati poravnat na desktopu i
    //     bez vodoravnog preljeva na mobilnom prikazu. Isti privitak zatim
    //     provjeravamo u tabličnom pregledu objavljene stranice.
    // EN: The compact attachment editor must stay aligned on desktop and free
    //     of horizontal overflow on mobile. The same attachment is then checked
    //     in the published page's tabular attachment view.
    const attachmentName = 'e2e-attachment-layout.txt';
    await page.locator('#editor-html-attachment').setInputFiles({
      name: attachmentName,
      mimeType: 'text/plain',
      buffer: Buffer.from('Attachment layout fixture.\n', 'utf8'),
    });
    await page.locator('[data-editor-html-upload-form] button[type="submit"]').click();
    const attachmentItem = page.locator('[data-editor-html-asset-item]').filter({
      hasText: attachmentName,
    });
    await expect(attachmentItem).toBeVisible();
    const desktopAttachmentLayout = await attachmentItem.evaluate((item) => {
      const rectangle = item.getBoundingClientRect();
      const metadata = item.querySelector('[data-editor-html-asset-metadata-form]')?.getBoundingClientRect();
      const actions = item.querySelector('.editor-html-asset-actions')?.getBoundingClientRect();
      const history = item.querySelector('[data-editor-html-asset-history]')?.getBoundingClientRect();

      return {
        height: rectangle.height,
        metadataBottom: metadata?.bottom ?? Number.NEGATIVE_INFINITY,
        actionsBottom: actions?.bottom ?? Number.POSITIVE_INFINITY,
        historyRight: history?.right ?? Number.POSITIVE_INFINITY,
        itemRight: rectangle.right,
      };
    });
    expect(desktopAttachmentLayout.height).toBeLessThan(190);
    expect(Math.abs(
      desktopAttachmentLayout.metadataBottom - desktopAttachmentLayout.actionsBottom,
    )).toBeLessThanOrEqual(3);
    expect(desktopAttachmentLayout.historyRight).toBeLessThanOrEqual(desktopAttachmentLayout.itemRight);

    const darkAttachmentInfo = await attachmentItem.evaluate((item) => {
      document.documentElement.dataset.hphTheme = 'dark';
      document.documentElement.dataset.bsTheme = 'dark';
      const info = item.querySelector('.editor-html-asset-info');
      const styles = info ? window.getComputedStyle(info) : null;
      const channelValues = (color) => (String(color).match(/[\d.]+/g) || [])
        .slice(0, 3)
        .map(Number);
      const luminance = (color) => {
        const channels = channelValues(color).map((channel) => {
          const normalized = channel / 255;
          return normalized <= 0.03928
            ? normalized / 12.92
            : ((normalized + 0.055) / 1.055) ** 2.4;
        });
        return (0.2126 * (channels[0] ?? 0))
          + (0.7152 * (channels[1] ?? 0))
          + (0.0722 * (channels[2] ?? 0));
      };
      const background = styles?.backgroundColor || '';
      const text = styles?.color || '';
      const lighter = Math.max(luminance(background), luminance(text));
      const darker = Math.min(luminance(background), luminance(text));

      return {
        background,
        contrast: (lighter + 0.05) / (darker + 0.05),
      };
    });
    expect(darkAttachmentInfo.background).not.toBe('rgb(248, 249, 250)');
    expect(darkAttachmentInfo.contrast).toBeGreaterThanOrEqual(4.5);
    await page.evaluate(() => {
      document.documentElement.dataset.hphTheme = 'light';
      document.documentElement.dataset.bsTheme = 'light';
    });

    await page.setViewportSize({ width: 390, height: 844 });
    const mobileAttachmentLayout = await attachmentItem.evaluate((item) => {
      const rectangle = item.getBoundingClientRect();

      return {
        left: rectangle.left,
        right: rectangle.right,
        clientWidth: item.clientWidth,
        scrollWidth: item.scrollWidth,
        viewportWidth: window.innerWidth,
      };
    });
    expect(mobileAttachmentLayout.left).toBeGreaterThanOrEqual(0);
    expect(mobileAttachmentLayout.right).toBeLessThanOrEqual(mobileAttachmentLayout.viewportWidth);
    expect(mobileAttachmentLayout.scrollWidth).toBeLessThanOrEqual(mobileAttachmentLayout.clientWidth);
    await page.setViewportSize({ width: 1280, height: 720 });

    await page.locator('#editor-html-attachment-visibility').selectOption('public');
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save visibility', exact: true }),
      '/editor-html/attachment-visibility',
    );

    await editorSurface.fill(firstPublishedBody);
    await submitFormAndExpectPost(
      page,
      page.getByRole('button', { name: 'Save and publish' }),
      '/editor-html/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
      && url.searchParams.get('saved') === '1');
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();

    await page.goto(`/workspace/${workspaceSlug}/${pageSlug}?attachments=on`);
    const attachmentTable = page.locator('.editor-html-view-attachment-table');
    await expect(attachmentTable).toBeVisible();
    for (const heading of ['File', 'Version', 'File type', 'Size', 'Uploaded by']) {
      await expect(attachmentTable.getByRole('columnheader', { name: heading, exact: true })).toBeVisible();
    }
    await expect(attachmentTable.getByRole('link', { name: attachmentName })).toBeVisible();

    await page.getByRole('link', { name: 'Edit', exact: true }).click();
    await expect(editorSurface).toBeVisible();

    // HR: Odustajanje mora ukloniti samo lokalni nacrt i ponovno otvoriti
    //     objavljeni dokument bez promjene zajedničkog nacrta na poslužitelju.
    // EN: Cancel must remove only the local draft and reopen the published
    //     document without changing the shared server-side draft.
    const localOnlyBody = 'Local browser draft that must be discarded by Cancel.';
    await editorSurface.fill(localOnlyBody);
    await expect.poll(() => page.evaluate(() => Object.keys(window.localStorage)
      .filter((key) => key.startsWith('hfc-editor-html-draft-v1:')).length)).toBe(1);
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`);
    await expect(page.getByRole('heading', { name: firstPublishedBody, exact: true })).toBeVisible();
    expect(await page.evaluate(() => Object.keys(window.localStorage)
      .filter((key) => key.startsWith('hfc-editor-html-draft-v1:')).length)).toBe(0);

    await page.getByRole('link', { name: 'Edit', exact: true }).click();
    await expect(editorSurface).toBeVisible();
    await expect(editorSurface).not.toContainText(localOnlyBody);
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
      page.getByRole('button', { name: 'Save and submit for review' }),
      '/editor-html/save',
    );
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/${pageSlug}`
      && url.searchParams.get('submitted') === '1');
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

    const browserUsers = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: apiHeaders(apiToken),
    }));
    const ordinaryWorkspaceUser = browserUsers.find(
      (user) => user.login_identifier === userLogin,
    );
    expect(ordinaryWorkspaceUser?.id).toBeTruthy();

    await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('browser-workspace-public-acl'),
      }),
      data: {
        subjects: [
          {
            type: 'public',
            permissions: {
              can_view: true,
              can_add: false,
              can_edit: false,
              can_publish: false,
              can_delete: false,
              can_manage: false,
            },
          },
          {
            type: 'user',
            id: ordinaryWorkspaceUser.id,
            permissions: {
              can_view: true,
              can_add: false,
              can_edit: true,
              can_publish: false,
              can_delete: false,
              can_manage: false,
            },
          },
        ],
      },
    }));
    const tree = await expectData(await request.get(
      `/api/v1/workspaces/${workspaceSlug}/tree?lang=en`,
      { headers: apiHeaders(apiToken) },
    ));
    const publishedNode = tree.find((node) => node.slug === pageSlug);
    expect(publishedNode?.id).toBeTruthy();

    /*
     * HR: Aktivna stranica na bilo kojoj dubini automatski pokazuje svoju
     *     prvu razinu podstranica, čak i kada spremljeno stanje nema tu granu.
     * EN: An active page at any depth automatically shows its first child
     *     level, even when the stored state does not contain that branch.
     */
    const activeBranchParentSlug = `e2e-active-branch-parent-${Date.now()}`;
    await expectData(await request.post('/api/v1/pages', {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('browser-active-branch-parent'),
      }),
      data: {
        title: 'E2E Active Branch Parent',
        slug: activeBranchParentSlug,
        workspace_slug: workspaceSlug,
        language: 'en',
        html: '<p>Temporary parent page for the active branch test.</p>',
      },
    }), 201);
    const branchTree = await expectData(await request.get(
      `/api/v1/workspaces/${workspaceSlug}/tree?lang=en`,
      { headers: apiHeaders(apiToken) },
    ));
    const activeBranchParent = branchTree.find(
      (node) => node.slug === activeBranchParentSlug,
    );
    expect(activeBranchParent?.id).toBeTruthy();
    const activeBranchChild = await expectData(await request.post(
      `/api/v1/workspaces/${workspaceSlug}/nodes`,
      {
        headers: apiHeaders(apiToken, {
          'Idempotency-Key': idempotencyKey('browser-active-branch-child'),
        }),
        data: {
          node_type: 'internal_link',
          title: 'E2E Active Branch Child',
          slug: 'e2e-active-branch-child',
          target_url: '/about',
          parent_id: publishedNode.id,
          sort_order: 10,
        },
      },
    ), 201);
    await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/tree/order`, {
      headers: apiHeaders(apiToken, {
        'Idempotency-Key': idempotencyKey('browser-active-branch-order'),
      }),
      data: {
        placements: [
          { id: activeBranchParent.id, parent_id: null, sort_order: 10 },
          { id: publishedNode.id, parent_id: activeBranchParent.id, sort_order: 10 },
          { id: activeBranchChild.id, parent_id: publishedNode.id, sort_order: 10 },
        ],
      },
    }));

    await page.goto(`/workspace/${workspaceSlug}/${pageSlug}?lang=en`);
    const readableTree = page.locator('[data-workspace-tree-view]');
    const readableTreeKey = await readableTree.getAttribute('data-workspace-tree-key');
    expect(readableTreeKey).toBeTruthy();
    await page.evaluate((treeKey) => {
      window.sessionStorage.setItem(`heartphrame.workspace.tree.v1.${treeKey}`, '[]');
    }, readableTreeKey);
    await page.reload();

    const activeTreeNode = readableTree.locator(
      `[data-workspace-tree-node="${publishedNode.id}"]`,
    );
    await expect(activeTreeNode.locator(
      ':scope > .workspace-tree-row > [aria-current="page"]',
    )).toBeVisible();
    await expect(activeTreeNode.locator(
      ':scope > .workspace-tree-row [data-workspace-tree-branch-toggle]',
    )).toHaveAttribute('aria-expanded', 'true');
    const activePageChildren = activeTreeNode.locator(':scope > .workspace-tree-branch');
    await expect(activePageChildren).toBeVisible();
    await expect(activePageChildren.getByRole('link', {
      name: 'E2E Active Branch Child',
      exact: true,
    })).toBeVisible();

    await page.getByRole('link', { name: /Shorts|Summaries|Sažetci/i }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/shorts`);
    await expect(page.getByRole('heading', {
      name: /^(Shorts|Summaries|Sažetci) · /i,
    })).toBeVisible();
    await page.getByRole('link', { name: /Close summaries|Zatvori sažetke/i }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}`);
    await page.getByRole('link', { name: /Shorts|Summaries|Sažetci/i }).click();
    await expect(page).toHaveURL((url) => url.pathname === `/workspace/${workspaceSlug}/shorts`);
    await expect(page.locator('.workspace-short-card')).toContainText(secondDraftBody);
    await expect(page.getByRole('link', { name: /Read more|Pročitaj više/i })).toBeVisible();
    await expect(page.locator('#workspace-shorts-depth')).toHaveValue('2');
    await expect(page.locator('#workspace-shorts-limit')).toHaveValue('10');
    await expect(page.locator('#workspace-shorts-order')).toHaveValue('newest');
    await expect(page.locator('#workspace-shorts-depth option[value="all"]')).toHaveText(/All levels|Sve razine/i);
    await expect(page.locator('#workspace-shorts-order option[value="title_asc"]')).toHaveText(
      /By title, ascending|Po naslovu uzlazno/i,
    );
    await expect(page.locator('#workspace-shorts-order option[value="title_desc"]')).toHaveText(
      /By title, descending|Po naslovu silazno/i,
    );
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
    const displayOptionsButton = page.getByRole('button', {
      name: /^(View options|Display options|Opcije prikaza)$/i,
    });
    await expect(displayOptionsButton).toBeVisible();
    await expect(page.getByRole('button', { name: 'Page tree', exact: true })).toHaveText('');
    await expect(displayOptionsButton).toHaveText('');

    await page.goto(`/workspace/${workspaceSlug}/shorts?lang=en&tree=0&options=0`);
    await expect(page.getByRole('heading', { name: /^Summaries · / })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Page tree', exact: true })).toBeVisible();
    const hiddenDisplayOptionsButton = page.getByRole('button', {
      name: /^(View options|Display options|Opcije prikaza)$/i,
    });
    await expect(hiddenDisplayOptionsButton).toBeVisible();
    await expect(page.locator('#workspace-page-tree')).not.toHaveClass(/\bshow\b/);
    await expect(page.locator('#workspace-shorts-display-options')).not.toHaveClass(/\bshow\b/);
    await hiddenDisplayOptionsButton.click();
    await expect(page.locator('#workspace-shorts-display-options')).toHaveClass(/\bshow\b/);
    await expect(page.getByLabel(/Displayed levels|Prikazane razine/i)).toHaveValue('2');
    await expect(page.getByLabel(/Number of articles|Broj članaka/i)).toHaveValue('10');
    await expect(page.getByLabel(/Order|Redoslijed/i)).toHaveValue('newest');
    await page.getByLabel(/Displayed levels|Prikazane razine/i).selectOption('all');
    await page.getByLabel(/Number of articles|Broj članaka/i).selectOption('5');
    await page.getByLabel(/Order|Redoslijed/i).selectOption('title_asc');
    await page.getByRole('button', { name: /Apply|Show|Prikaži/i }).click();
    await expect(page).toHaveURL((url) => url.searchParams.get('depth') === 'all'
      && url.searchParams.get('limit') === '5'
      && url.searchParams.get('order') === 'title_asc');
    await expect(page.getByLabel(/Displayed levels|Prikazane razine/i)).toHaveValue('all');
    await expect(page.getByLabel(/Number of articles|Broj članaka/i)).toHaveValue('5');
    await expect(page.getByLabel(/Order|Redoslijed/i)).toHaveValue('title_asc');

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
    const treeEditor = page.locator('[data-workspace-tree-editor]');
    const openTreeOrganizer = async () => {
      await page.waitForLoadState('load');
      await page.getByRole('button', { name: /Edit tree|Uredi stablo/i }).click();
      await expect(treeEditor).toBeVisible();
      const addButton = treeEditor.getByRole('button', {
        name: /Add item|Dodaj stavku/i,
        exact: true,
      });
      await expect(addButton).toBeVisible();
      return addButton;
    };

    let addTreeItemButton = await openTreeOrganizer();
    const nodeDialog = page.locator('#workspace-node-editor-modal');
    await addTreeItemButton.click();
    await expectUsableModal(nodeDialog);
    await nodeDialog.locator('select[name="node_type"]').selectOption('separator');
    await expect(nodeDialog).toContainText(/system separator|sistemski separator/i);
    await submitFormAndExpectPost(
      page,
      nodeDialog.getByRole('button', { name: /Add item|Dodaj stavku/i, exact: true }),
      '/workspaces/node/save',
    );
    await expect(page.getByRole('heading', { name: /^(Links|Linkovi)$/ })).toBeVisible();

    addTreeItemButton = await openTreeOrganizer();
    await addTreeItemButton.click();
    await expectUsableModal(nodeDialog);
    await nodeDialog.locator('select[name="node_type"]').selectOption('external_link');
    await nodeDialog.locator('input[name^="title_translations["]:visible').fill('SRCE');
    const parentPicker = nodeDialog.locator('input[name="parent_id"]').locator('xpath=..');
    await parentPicker.locator('[data-workspace-lookup-toggle]').click();
    await parentPicker.locator('[data-workspace-lookup-search]').fill('Links');
    await parentPicker
      .locator('[data-workspace-lookup-list]')
      .getByRole('button', { name: 'Links', exact: true })
      .click();
    await nodeDialog.locator('input[name="target_url"]').fill('https://www.srce.unizg.hr/');
    await submitFormAndExpectPost(
      page,
      nodeDialog.getByRole('button', { name: /Add item|Dodaj stavku/i, exact: true }),
      '/workspaces/node/save',
    );
    await expect(page.getByRole('link', { name: 'SRCE', exact: true })).toBeVisible();

    await openTreeOrganizer();

    const editTreeItem = page.getByRole('button', {
      name: /Edit item: E2E Published Page|Uredi stavku: E2E Published Page/i,
    });
    await expect(editTreeItem).toBeVisible();
    await editTreeItem.click();

    await expect(nodeDialog).toBeVisible();
    await expectUsableModal(nodeDialog);

    const restrictionSearch = nodeDialog.locator('#workspace-restriction-user-search');
    await restrictionSearch.fill(userLogin);
    const restrictionResult = nodeDialog.locator(
      '#workspace-restriction-user-results [role="option"]',
    ).first();
    await expect(restrictionResult).toBeVisible();
    await restrictionResult.click();

    const restrictionRow = nodeDialog.locator(
      `[data-workspace-restriction-row="${ordinaryWorkspaceUser.id}"]`,
    );
    const inheritedView = restrictionRow.locator(
      '[data-workspace-restriction-permission="can_view"]',
    );
    const inheritedEdit = restrictionRow.locator(
      '[data-workspace-restriction-permission="can_edit"]',
    );
    await expect(inheritedView).toBeChecked();
    await expect(inheritedEdit).toBeChecked();
    await restrictionRow.locator(
      '[data-workspace-restriction-permission="can_edit"] + span',
    ).click();
    await expect(inheritedEdit).not.toBeChecked();
    await expect.poll(() => restrictionRow.evaluate((row) => {
      const inherited = row.querySelector(
        '[data-workspace-restriction-permission="can_view"] + span',
      );
      const denied = row.querySelector(
        '[data-workspace-restriction-permission="can_edit"] + span',
      );
      if (!(inherited instanceof HTMLElement) || !(denied instanceof HTMLElement)) {
        return false;
      }

      return getComputedStyle(inherited).backgroundColor
        !== getComputedStyle(denied).backgroundColor;
    })).toBe(true);

    const aclColors = await restrictionRow.evaluate((row) => {
      const inherited = row.querySelector(
        '[data-workspace-restriction-permission="can_view"] + span',
      );
      const denied = row.querySelector(
        '[data-workspace-restriction-permission="can_edit"] + span',
      );
      const unavailable = row.querySelector('.workspace-node-restriction-unavailable');
      if (
        !(inherited instanceof HTMLElement)
        || !(denied instanceof HTMLElement)
        || !(unavailable instanceof HTMLElement)
      ) {
        throw new Error('Restriction color controls are missing.');
      }

      return {
        inherited: getComputedStyle(inherited).backgroundColor,
        denied: getComputedStyle(denied).backgroundColor,
        unavailable: getComputedStyle(unavailable).backgroundColor,
        body: getComputedStyle(document.body).backgroundColor,
      };
    });
    expect(aclColors.inherited).not.toBe(aclColors.denied);
    expect(aclColors.inherited).not.toBe(aclColors.body);
    expect(aclColors.denied).not.toBe(aclColors.body);
    expect(aclColors.unavailable).toBe(aclColors.body);

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
    const persistedRestrictionRow = nodeDialog.locator(
      `[data-workspace-restriction-row="${ordinaryWorkspaceUser.id}"]`,
    );
    await expect(persistedRestrictionRow.locator(
      '[data-workspace-restriction-permission="can_view"]',
    )).toBeChecked();
    await expect(persistedRestrictionRow.locator(
      '[data-workspace-restriction-permission="can_edit"]',
    )).not.toBeChecked();
    await expect(nodeDialog.getByRole('heading', {
      name: /Direct user permissions|Izravna dopuštenja korisnicima/i,
    })).toBeVisible();

    const titleInput = nodeDialog.locator('input[name^="title_translations["]:visible');
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
    await expectUsableModal(historyDialog);
    await expect(historyDialog.getByRole('row')).toHaveCount(4);
    await expect(historyDialog).toContainText('#3');
    await expect(historyDialog).toContainText('#2');
    await expect(historyDialog).toContainText('#1');

    /*
     * HR: Zatvaranje i ponovno otvaranje istog modala mora ostati ispravno;
     *     tako pokrivamo kvar koji se ranije pojavljivao tek nakon nekoliko klikova.
     * EN: Closing and reopening the same modal must remain correct; this covers
     *     the former failure that sometimes appeared only after several clicks.
     */
    await closeModal(historyDialog);
    await page.getByRole('button', { name: 'History' }).click();
    await expectUsableModal(historyDialog);

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

    await page.goto(`/workspaces/manage?workspace=${workspaceSlug}`);
    await page.getByRole('link', { name: /Export Workspace to HTML|Izvezi područje u HTML/i }).click();
    await expect(page.getByRole('heading', {
      name: /Export Workspace to HTML|Izvezi područje u HTML/i,
    })).toBeVisible();
    await expect(page.getByRole('radio', { name: /Complete Workspace|Cijelo područje/i })).toBeChecked();
    await page.getByRole('radio', { name: /Selected pages|Odabrane stranice/i }).check();
    await expect(page.getByLabel('E2E Published Page Renamed')).toBeEnabled();
    await page.getByRole('radio', { name: /Complete Workspace|Cijelo područje/i }).check();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', {
      name: /Export Workspace to HTML|Izvezi područje u HTML/i,
    }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(new RegExp(`^simbioza-${workspaceSlug}-\\d{8}-\\d{6}\\.zip$`));
    const downloadPath = await download.path();
    expect(downloadPath).not.toBeNull();
    const archive = await readFile(downloadPath);
    expect([...archive.subarray(0, 4)]).toEqual([0x50, 0x4B, 0x03, 0x04]);
    expect(archive.length).toBeGreaterThan(1_000);

    /*
     * HR: ZIP se otvara stvarnom PHP ZipArchive ekstenzijom, provjerava da ne
     *     umnaža nepostojeći prijevod i zatim se pregledava izravno kroz file://.
     * EN: The ZIP is opened by the real PHP ZipArchive extension, checked for
     *     nonexistent-translation duplication, and then viewed directly via file://.
     */
    const extractedDirectory = await mkdtemp(join(tmpdir(), 'simbioza-workspace-export-'));
    try {
      const inspectionScript = String.raw`
        $zip = new ZipArchive();
        if ($zip->open($argv[1]) !== true) { fwrite(STDERR, "Unable to open ZIP\n"); exit(2); }
        $entries = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
          $entries[] = $zip->getNameIndex($index);
        }
        $page = 'index.html';
        $html = $zip->getFromName($page);
        if (!is_string($html)) { fwrite(STDERR, "Offline shell missing\n"); exit(3); }
        preg_match_all('#(?:src|href)="(assets/theme/[^"]+)"#', $html, $matches);
        $directPages = array_values(array_filter(
          $entries,
          fn($entry) => preg_match('#^(hr|en)/[^/]+\.html$#', $entry) === 1,
        ));
        $themeAssetFiles = array_values(array_filter(
          $entries,
          fn($entry) => str_starts_with($entry, 'assets/theme/'),
        ));
        $missing = [];
        foreach (array_unique($matches[1] ?? []) as $asset) {
          if ($zip->locateName($asset) === false) { $missing[] = $asset; }
        }
        if (!$zip->extractTo($argv[2])) { fwrite(STDERR, "Unable to extract ZIP\n"); exit(4); }
        $zip->close();
        echo json_encode([
          'page' => $page,
          'has_hr_pages' => count(array_filter($entries, fn($entry) => str_starts_with($entry, 'hr/'))) > 0,
          'has_en_pages' => count(array_filter($entries, fn($entry) => str_starts_with($entry, 'en/'))) > 0,
          'direct_pages' => $directPages,
          'theme_reference_count' => count(array_unique($matches[1] ?? [])),
          'embedded_theme_image_count' => preg_match_all('#src="data:image/[^;]+;base64,#', $html),
          'theme_asset_files' => $themeAssetFiles,
          'missing_theme_assets' => $missing,
          'has_export_note' => str_contains($html, 'E2E Content Workspace'),
          'has_all_languages' => str_contains($html, 'value="hr"') && str_contains($html, 'value="en"'),
          'has_duplicate_shell_title' => preg_match(
            '#hph-hero__title[^>]*>\s*Simbioza\s*-\s*E2E Content Workspace#',
            $html,
          ) === 1,
        ], JSON_THROW_ON_ERROR);
      `;
      const { stdout } = await execFileAsync('php', [
        '-r',
        inspectionScript,
        downloadPath,
        extractedDirectory,
      ]);
      const inspection = JSON.parse(stdout);

    expect(inspection.has_en_pages).toBe(true);
    expect(inspection.has_hr_pages).toBe(false);
    expect(inspection.direct_pages.some((entry) => entry.startsWith('en/e2e-published-page'))).toBe(true);
      expect(inspection.has_export_note).toBe(true);
      expect(inspection.has_all_languages).toBe(true);
      expect(inspection.has_duplicate_shell_title).toBe(false);
      expect(inspection.theme_reference_count).toBe(0);
      expect(inspection.embedded_theme_image_count).toBeGreaterThanOrEqual(2);
      expect(inspection.theme_asset_files.length).toBeGreaterThanOrEqual(2);
      expect(inspection.missing_theme_assets).toEqual([]);

      const offlineContext = await browser.newContext();
      const offlinePage = await offlineContext.newPage();
      await offlinePage.setViewportSize({ width: 1600, height: 1000 });
      await offlinePage.goto(pathToFileURL(join(extractedDirectory, inspection.page)).href);
      await expect(offlinePage.getByRole('heading', {
        name: 'E2E Published Page',
        exact: true,
      })).toBeVisible();
      await expect(offlinePage.getByRole('link', {
        name: 'E2E Published Page Renamed',
        exact: true,
      })).toBeVisible();
      /*
       * HR: Čista instalacija uključuje samo hrvatski i engleski. Njemački
       *     paket ostaje primjer koji administrator naknadno instalira.
       * EN: A clean installation enables Croatian and English only. The
       *     German pack remains an example installed later by an administrator.
       */
      await expect(offlinePage.locator('[data-export-language] option')).toHaveCount(2);
      await expect(offlinePage.locator('[data-export-language] option[value="de"]')).toHaveCount(0);
      const offlineLogo = offlinePage.locator('.hph-site-header__logo:visible');
      const offlineHeroVisual = offlinePage.locator('.hph-hero__visual img:visible');
      await expect(offlineLogo).toBeVisible();
      await expect(offlineHeroVisual).toBeVisible();
      await expect(offlineLogo).toHaveAttribute('src', /^data:image\//);
      await expect(offlineHeroVisual).toHaveAttribute('src', /^data:image\//);
      await expect.poll(() => offlineLogo.evaluate((image) => image.naturalWidth)).toBeGreaterThan(0);
      await expect.poll(() => offlineHeroVisual.evaluate((image) => image.naturalWidth)).toBeGreaterThan(0);

      /*
       * HR: Mišem odabrana stavka stabla smije ostati semantički trenutačna,
       *     ali ne smije zadržati Bootstrapovu `.active` pozadinu niti fokus
       *     namijenjen tipkovnici.
       * EN: A tree item selected by mouse may remain semantically current, but
       *     must not retain Bootstrap's `.active` background or keyboard-only
       *     focus treatment.
       */
      const offlineTreeLink = offlinePage.getByRole('link', {
        name: 'E2E Published Page Renamed',
        exact: true,
      });
      await offlineTreeLink.click();
      await expect(offlineTreeLink).toHaveAttribute('aria-current', 'page');
      await expect(offlineTreeLink).not.toHaveClass(/\bactive\b/);

      const outlineToggle = offlinePage.getByRole('button', { name: /Content|Sadržaj/i });
      if (await outlineToggle.count() > 0 && await outlineToggle.getAttribute('aria-expanded') === 'true') {
        await outlineToggle.click();
      }
      const offlineGeometry = await offlinePage.locator('[data-workspace-export-layout]').evaluate((layout) => {
        const main = layout.querySelector('.workspace-export-main');
        const layoutRect = layout.getBoundingClientRect();
        const mainRect = main?.getBoundingClientRect();
        const layoutStyle = getComputedStyle(layout);

        return {
          contentRight: layoutRect.right - Number.parseFloat(layoutStyle.paddingRight),
          mainRight: mainRect?.right ?? 0,
        };
      });
      expect(Math.abs(offlineGeometry.contentRight - offlineGeometry.mainRight)).toBeLessThanOrEqual(1);

      const overlapGeometry = await offlinePage.locator('.hph-page-stage').evaluate((stage) => {
        const hero = stage.querySelector('.hph-hero')?.getBoundingClientRect();
        const main = stage.querySelector('.hph-main-content')?.getBoundingClientRect();

        return {
          overlap: (hero?.bottom ?? 0) - (main?.top ?? 0),
        };
      });
      expect(overlapGeometry.overlap).toBeGreaterThan(0);

      const standalonePage = await offlineContext.newPage();
      await standalonePage.goto(pathToFileURL(join(
        extractedDirectory,
        inspection.direct_pages[0],
      )).href);
      await expect(standalonePage.locator('#editor-html-standalone-content')).toBeVisible();
      await expect(standalonePage.locator('.hph-site-header')).toHaveCount(0);
      await expect(standalonePage.locator('link[href="../assets/css/theme.css"]')).toHaveCount(0);
      expect(await standalonePage.locator('body').evaluate(
        (body) => getComputedStyle(body).backgroundColor,
      )).toBe('rgb(255, 255, 255)');
      await offlineContext.close();
    } finally {
      await rm(extractedDirectory, { recursive: true, force: true });
    }

    /*
     * HR: Neprivilegirani korisnik ne smije ni otvoriti export formu, iako
     *     smije vidjeti javno područje i njegovu objavljenu stranicu.
     * EN: An unprivileged user must not open the export form even when the
     *     public Workspace and its published page remain visible.
     */
    const userContext = await browser.newContext({ baseURL: new URL(page.url()).origin });
    const userPage = await userContext.newPage();
    await login(userPage, userLogin, userPassword);
    const deniedExport = await userPage.goto(`/workspaces/export?workspace=${workspaceSlug}`);
    expect(deniedExport?.status()).toBe(403);
    await expect(userPage.locator('body')).toContainText(/Access denied|Nedozvoljen pristup/i);
    await userContext.close();
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
