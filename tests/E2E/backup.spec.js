import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  getDataWithEtag,
  idempotencyKey,
  login,
} from './helpers.js';

const { adminApiToken, adminLogin, adminPassword } = e2eEnvironment();

test.describe('complete Backup workflow', () => {
  test('administrator creates, uploads, preflights, and restores a full-site archive', async ({ page }, testInfo) => {
    test.setTimeout(180_000);
    const passphrase = 'Simbioza-E2E-backup-2026!';
    const project = process.env.HPH_E2E_PROJECT;
    expect(project).toBeTruthy();
    const applicationSource = await readFile(join(project, 'config/app.php'), 'utf8');

    await login(page, adminLogin, adminPassword);
    const response = await page.goto('/settings/backups');
    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: /Backup (i vraćanje|and restore)/i })).toBeVisible();

    /*
     * HR: Sučelje prikazuje potpune poslovne cjeline, a interne tehničke
     *     providere namjerno ne izlaže korisniku. Kod potpunog backupa cjeline
     *     su informativne i automatski uključene pa njihove kvačice moraju biti
     *     onemogućene.
     * EN: The UI exposes complete business components and intentionally hides
     *     internal technical providers. A full-site backup includes every
     *     component automatically, so their checkboxes are informational and
     *     must remain disabled.
     */
    const componentCheckboxes = page.locator('#backup-create-form input[name="components[]"]');
    await expect(componentCheckboxes).toHaveCount(6);
    expect(await componentCheckboxes.evaluateAll((checkboxes) => (
      checkboxes.map((checkbox) => checkbox.value)
    ))).toEqual(['users', 'workspaces', 'themes', 'settings', 'calendars', 'audit']);
    await expect(componentCheckboxes.first()).toBeDisabled();

    /*
     * HR: Izrada i vraćanje su jedan vertikalni accordion. Otvaranje vraćanja
     *     zatvara izradu, a ponovno otvaranje izrade vraća obrazac za nastavak.
     * EN: Create and restore form one vertical accordion. Opening restore
     *     closes create, and reopening create returns the form for this flow.
     */
    const createAccordion = page.locator('.backup-accordion').nth(0);
    const restoreAccordion = page.locator('.backup-accordion').nth(1);
    await expect(createAccordion).toHaveAttribute('open', '');
    await expect(restoreAccordion).not.toHaveAttribute('open', '');
    await restoreAccordion.locator(':scope > summary').click();
    await expect(createAccordion).not.toHaveAttribute('open', '');
    await expect(restoreAccordion).toHaveAttribute('open', '');
    // HR: Pogreška bez arhiva ne pokreće posao; poruka ne nestaje tijekom čitanja.
    // EN: Missing archives start no job; the error remains available while reading.
    await page.locator('#backup-upload').focus();
    await page.locator('#backup-upload').press('Enter');
    await expect(page.locator('#backup-toast')).toContainText(/Choose a backup archive|Odaberite backup arhiv/i);
    await expect(page.locator('#backup-upload')).toBeFocused();
    await page.waitForTimeout(6500);
    await expect(page.locator('#backup-toast')).toBeVisible();
    await page.locator('#backup-toast button').focus();
    await page.locator('#backup-toast button').press('Enter');
    await expect(page.locator('#backup-upload')).toBeFocused();
    await createAccordion.locator(':scope > summary').click();
    await expect(createAccordion).toHaveAttribute('open', '');
    await expect(restoreAccordion).not.toHaveAttribute('open', '');

    await page.locator('#backup-create-form input[name="label"]').fill('e2e-full-site');
    await page.locator('#backup-create-form input[name="passphrase"]').fill(passphrase);

    const downloadPromise = page.waitForEvent('download');
    await page.locator('#backup-create-form button[type="submit"]').click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/\.zip$/i);
    /*
     * HR: Interna Playwright putanja nema izvorni `.zip` sufiks, dok stvarni
     *     korisnički download ima. Spremamo ga pod predloženim nazivom kako bi
     *     test vjerno prošao istu validaciju naziva kao preglednik.
     * EN: Playwright's internal path lacks the original `.zip` suffix while a
     *     real user download has it. Save under the suggested name so the test
     *     exercises the same filename validation as the browser workflow.
     */
    const archivePath = testInfo.outputPath(download.suggestedFilename());
    await download.saveAs(archivePath);
    await expect(page.locator('#backup-toast')).toContainText(
      /Backup je izrađen i preuzet|Backup created and downloaded/i,
    );
    const latestJob = page.locator('#backup-jobs-body tr').first();
    await expect(latestJob).toBeVisible();
    const deleteJobButton = latestJob.locator('[data-backup-delete]');
    await deleteJobButton.focus();
    await page.waitForTimeout(5500);
    await expect(deleteJobButton).toBeFocused();
    await expect(latestJob.locator('[data-backup-download]')).toHaveCount(0);
    await expect(latestJob.locator('td').first()).toHaveText(
      /(?:[A-Z][a-z]{2} \d{1,2}, \d{4}, \d{1,2}:\d{2}:\d{2}\s?(?:AM|PM)|\d{1,2}\. \d{1,2}\. \d{4}\. \d{2}:\d{2}:\d{2}|\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/,
    );
    const jobsResponse = await page.request.get('/settings/backups/jobs', {
      headers: { Accept: 'application/json' },
    });
    expect(jobsResponse.status()).toBe(200);
    const jobsPayload = await jobsResponse.json();
    expect(jobsPayload.jobs[0].download_available).toBe(false);
    const secondDownload = await page.request.get(
      `/settings/backups/download?job=${encodeURIComponent(jobsPayload.jobs[0].uuid)}`,
    );
    expect(secondDownload.status()).toBe(404);
    /*
     * HR: Jezik sučelja mijenja se istom lokalizacijskom rutom koju koristi
     *     izbornik u zaglavlju. Parametar `lang` pripada jeziku sadržaja i ne
     *     smije utjecati na format datuma administracijskog sučelja.
     * EN: Switch the interface language through the same locale route used by
     *     the header selector. The `lang` parameter belongs to content language
     *     and must not control the administration UI date format.
     */
    await page.goto(`/locale/hr?next=${encodeURIComponent('/settings/backups')}`);
    const hrJobs = await (await page.request.get('/settings/backups/jobs')).json();
    expect(hrJobs.timezone).toBe('Europe/Zagreb');
    expect(hrJobs.jobs[0].created_at_display).toMatch(
      /^\d{1,2}\. [\p{L}.]+ \d{4}\. \d{2}:\d{2}:\d{2}$/u,
    );
    await page.goto(`/locale/en?next=${encodeURIComponent('/settings/backups')}`);
    const enJobs = await (await page.request.get('/settings/backups/jobs')).json();
    expect(enJobs.jobs[0].created_at_display).toMatch(
      /^[A-Z][a-z]{2} \d{1,2}, \d{4}, \d{1,2}:\d{2}:\d{2}\s?(?:AM|PM)$/,
    );

    /*
     * HR: Izrada je nakon preuzimanja i dalje otvorena, stoga prije rada s
     *     uploadom otvaramo accordion za vraćanje. Time test slijedi isti
     *     korisnički tijek kao stvarno sučelje.
     * EN: Create remains open after the download, so open the restore
     *     accordion before interacting with its upload controls. This keeps
     *     the test aligned with the real user flow.
     */
    await restoreAccordion.locator(':scope > summary').click();
    await expect(createAccordion).not.toHaveAttribute('open', '');
    await expect(restoreAccordion).toHaveAttribute('open', '');

    await page.locator('#backup-file').setInputFiles(archivePath);
    await page.locator('#backup-passphrase').fill(passphrase);
    await page.locator('#backup-upload').click();
    await expect(page.locator('#backup-preflight')).toBeEnabled({ timeout: 60_000 });
    await expect(page.locator('#backup-upload-progress').locator('..')).toHaveAttribute('aria-valuenow', '100');
    await expect(page.locator('#backup-result')).toContainText('providers');

    await page.locator('#backup-conflict').selectOption('replace');
    await page.locator('#backup-maintenance').check();
    await page.locator('#backup-preflight').click();
    await expect(page.locator('#backup-result')).toContainText('"errors": []', { timeout: 60_000 });
    await expect(page.locator('#backup-restore')).toBeEnabled();

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#backup-restore').click();
    await expect(page.locator('#backup-result')).toContainText('"restored": true', { timeout: 90_000 });
    await expect(page.locator('#backup-result')).toContainText('"uploaded_archive_deleted": true');
    await expect(page.locator('#backup-result')).toContainText('safety_snapshot');
    await expect(page.locator('#backup-toast')).toContainText(
      /Vraćanje je dovršeno|Restore completed/i,
    );

    /*
     * HR: Tema je datotečna backup cjelina. Nakon stvarnog vraćanja provjeravamo
     *     i nova svojstva hero ukrasa, a ne samo prisutnost teme.
     * EN: Themes are a filesystem-backed backup component. After a real restore,
     *     verify the new hero-artwork properties as well as the theme itself.
     */
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/theme?theme=dabar');
    const themeEditor = page.locator('#theme-editor-form');
    await themeEditor.locator('[data-theme-section-id="hero"] > summary').click();
    await expect(themeEditor.locator('[data-theme-hero-visual-width]')).toHaveValue('650');
    await expect(themeEditor.locator('[data-theme-hero-visual-max-height]')).toHaveValue('320');
    await expect(themeEditor.locator('[data-theme-hero-visual-top]')).toHaveValue('-64');
    await expect(themeEditor.locator('[data-theme-hero-visual-right]')).toHaveValue('24');
    await expect(themeEditor.locator('[data-theme-hero-visual-allow-overflow]')).toBeChecked();

    /*
     * HR: Povrat podataka ne smije pretvoriti dinamički bootstrap u statički.
     *     GUI i CLI moraju i dalje stvarno isključiti te ponovno učitati temu.
     * EN: Data restoration must not flatten the dynamic bootstrap. Both GUI
     *     and CLI must still genuinely unload and reload the Theme module.
     */
    expect(await readFile(join(project, 'config/app.php'), 'utf8')).toBe(applicationSource);
    const cliModule = (action) => execFileSync('php', ['vendor/bin/hph', 'modules', action, 'theme'], {
      cwd: project,
      env: { ...process.env, HPH_APP_PATH: project, HPH_CONFIG_PATH: join(project, 'config') },
    });
    try {
      await page.goto('/settings/setup');
      const themeForm = (action) => page.locator('form')
        .filter({ has: page.locator('input[name="module"][value="theme"]') })
        .filter({ has: page.locator(`input[name="action"][value="${action}"]`) });
      await Promise.all([
        page.waitForResponse((response) => response.request().method() === 'POST'
          && new URL(response.url()).pathname === '/settings/setup'),
        themeForm('disable').getByRole('button').click(),
      ]);
      await page.goto('/settings/setup');
      await expect(page.locator('.hph-site-header')).toHaveCount(0);
      await expect(page.locator('a[href$="/settings/theme"]')).toHaveCount(0);
      await expect(themeForm('enable').getByRole('button')).toBeVisible();
      await Promise.all([
        page.waitForResponse((response) => response.request().method() === 'POST'
          && new URL(response.url()).pathname === '/settings/setup'),
        themeForm('enable').getByRole('button').click(),
      ]);
      await page.goto('/settings/setup');
      await expect(page.locator('.hph-site-header')).toBeVisible();
      await expect(page.locator('a[href$="/settings/theme"]').first()).toBeVisible();
      cliModule('disable');
      await page.goto('/calendars');
      await expect(page.locator('.hph-site-header')).toHaveCount(0);
      const skip = page.getByRole('link', { name: /Skip to main content|Prijeđi na glavni sadržaj/i });
      await page.keyboard.press('Tab');
      await expect(skip).toBeFocused();
      await page.keyboard.press('Enter');
      await expect(page.locator('#main-content')).toBeFocused();
    } finally {
      cliModule('enable');
    }
    await page.goto('/calendars');
    await expect(page.locator('.hph-site-header')).toBeVisible();
  });

  test('administrator copies one complete Workspace and rebuilds its search index', async ({ page }, testInfo) => {
    test.setTimeout(180_000);
    const suffix = `${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
    const sourceWorkspace = `e2e-backup-source-${suffix}`;
    const targetWorkspace = `e2e-backup-copy-${suffix}`;
    const documentKey = `e2e-backup-page-${suffix}`;
    const title = `E2E Workspace Backup ${suffix}`;
    const searchTerm = `portable-workspace-${suffix}`;
    const passphrase = `Simbioza-workspace-${suffix}!`;
    const adminHeaders = apiHeaders(adminApiToken);

    /*
     * HR: Scenarij sam izrađuje izvorno područje i objavljenu stranicu kako
     *     ne bi ovisio o redoslijedu drugih E2E datoteka ili testnim seedovima.
     * EN: The scenario creates its own source Workspace and published page so
     *     it does not depend on other E2E file ordering or seeded fixtures.
     */
    await expectData(await page.request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-backup-source'),
      }),
      data: {
        name: title,
        slug: sourceWorkspace,
        description: `Portable backup source ${searchTerm}`,
        visibility: 'public',
      },
    }), 201);
    const createdPage = await expectData(await page.request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-backup-page'),
      }),
      data: {
        title,
        slug: documentKey,
        workspace_slug: sourceWorkspace,
        language: 'hr',
        contents_visibility: 'shown',
        content: [{
          type: 'html',
          html: `<h1>${title}</h1><p>${searchTerm} mora preživjeti prenosivi restore.</p>`,
        }],
      },
    }), 201);
    const draft = await getDataWithEtag(
      page.request,
      `/api/v1/pages/${createdPage.id}/draft?lang=hr`,
      adminHeaders,
    );
    await expectData(await page.request.post(`/api/v1/pages/${createdPage.id}/publish?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-backup-publish'),
        'If-Match': draft.etag,
      }),
      data: {},
    }));

    await login(page, adminLogin, adminPassword);
    const response = await page.goto(`/workspaces/backup?workspace=${sourceWorkspace}`);
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1.hph-hero__title')).toHaveText(
      /Backup područja|Workspace backup/i,
    );

    await page.locator('#workspace-backup-export-passphrase').fill(passphrase);
    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('button', { name: /Preuzmi šifrirani backup|Download encrypted backup/i }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/\.zip$/i);
    const archivePath = testInfo.outputPath(download.suggestedFilename());
    await download.saveAs(archivePath);

    await page.locator('#workspace-backup-file').setInputFiles(archivePath);
    await page.locator('#workspace-backup-passphrase').fill(passphrase);
    await page.locator('#workspace-backup-mode').selectOption('copy');
    await page.locator('#workspace-backup-target').fill(targetWorkspace);
    await page.locator('#workspace-backup-upload').click();
    await expect(page.locator('#workspace-backup-preflight')).toBeEnabled({ timeout: 60_000 });
    await expect(page.locator('#workspace-backup-result')).toContainText('workspace-scope');

    await page.locator('#workspace-backup-preflight').click();
    await expect(page.locator('#workspace-backup-result')).toContainText('"errors": []', { timeout: 60_000 });
    await expect(page.locator('#workspace-backup-restore')).toBeEnabled();

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#workspace-backup-restore').click();
    await expect(page.locator('#workspace-backup-result')).toContainText('"restored": true', { timeout: 90_000 });

    const copiedWorkspace = await expectData(await page.request.get(
      `/api/v1/workspaces/${targetWorkspace}`,
      { headers: adminHeaders },
    ));
    expect(copiedWorkspace.slug).toBe(targetWorkspace);
    expect(copiedWorkspace.name).toContain('(copy)');

    const copiedTree = await expectData(await page.request.get(
      `/api/v1/workspaces/${targetWorkspace}/tree?lang=hr`,
      { headers: adminHeaders },
    ));
    expect(copiedTree.some((node) => node.slug === documentKey && node.title === title)).toBe(true);

    const copiedView = await page.request.get(`/workspace/${targetWorkspace}/${documentKey}?lang=hr`);
    expect(copiedView.status()).toBe(200);
    expect(await copiedView.text()).toContain(searchTerm);

    /*
     * HR: Indeks se namjerno ne prenosi. Završni Search provider mora ga
     *     izgraditi za novi slug nakon uspješnog DB i datotečnog importa.
     * EN: The index is intentionally not transferred. The Search finalizer
     *     must rebuild it for the new slug after the DB and file import succeeds.
     */
    const search = await page.request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(searchTerm)}&workspace=${targetWorkspace}&lang=hr`,
      { headers: adminHeaders },
    );
    const copiedResults = await expectData(search);
    expect([...new Set(copiedResults.map((item) => item.workspace_slug))]).toEqual([targetWorkspace]);
    expect(copiedResults.map((item) => item.result_type).sort()).toEqual(['page', 'workspace']);
  });
});
