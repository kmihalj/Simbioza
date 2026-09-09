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
  adminApiToken,
  adminLogin,
  adminPassword,
  userApiToken,
  userLogin,
  userPassword,
} = e2eEnvironment();
const adminHeaders = apiHeaders(adminApiToken);
const userHeaders = apiHeaders(userApiToken);

test.describe.serial('Workspace Search web and API ACL boundary', () => {
  const publicWorkspace = 'e2e-search-public';
  const restrictedWorkspace = 'e2e-search-restricted';
  const publicPage = 'e2e-search-public-page';
  const restrictedPage = 'e2e-search-restricted-page';
  const exactPhrasePage = 'e2e-search-exact-phrase-page';
  const splitPhrasePage = 'e2e-search-split-phrase-page';
  const sharedTerm = `simbioza-search-boundary-${Date.now()}`;
  const phraseStem = `simbioza-search-phrase-${Date.now()}`;
  const replacementTerm = `simbioza-search-republished-${Date.now()}`;
  let ordinaryUserId;

  /**
   * HR: Stvara i objavljuje stvarnu Editor stranicu unutar zadanog područja.
   * EN: Creates and publishes a real Editor page inside the supplied Workspace.
   */
  async function createAndPublish(request, workspaceSlug, documentId, title, body) {
    await expectData(await request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey(`search-create-${documentId}`) }),
      data: {
        title,
        slug: documentId,
        workspace_slug: workspaceSlug,
        language: 'hr',
        contents_visibility: 'inherit',
        content: [{ type: 'html', html: `<p>${body}</p>` }],
      },
    }), 201);
    const draft = await getDataWithEtag(request, `/api/v1/pages/${documentId}/draft?lang=hr`, adminHeaders);
    await expectData(await request.post(`/api/v1/pages/${documentId}/publish?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey(`search-publish-${documentId}`),
        'If-Match': draft.etag,
      }),
      data: {},
    }));
  }

  test('setup creates one public and one restricted published page', async ({ request }) => {
    const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: adminHeaders,
    }));
    ordinaryUserId = users.find((user) => user.login_identifier === userLogin)?.id;
    expect(ordinaryUserId).toBeTruthy();

    await expectData(await request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('search-public-workspace') }),
      data: {
        name: 'E2E Search Public',
        slug: publicWorkspace,
        description: 'Public search ACL fixture.',
        visibility: 'public',
      },
    }), 201);
    await expectData(await request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('search-restricted-workspace') }),
      data: {
        name: 'E2E Search Restricted',
        slug: restrictedWorkspace,
        description: 'Restricted search ACL fixture.',
        visibility: 'restricted',
      },
    }), 201);
    await expectData(await request.put(`/api/v1/workspaces/${restrictedWorkspace}/acl`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('search-restricted-acl') }),
      data: {
        subjects: [{
          type: 'user',
          id: ordinaryUserId,
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

    await createAndPublish(
      request,
      publicWorkspace,
      publicPage,
      'Javni rezultat pretrage',
      `${sharedTerm} sadržaj dostupan svakom gostu.`,
    );
    await createAndPublish(
      request,
      restrictedWorkspace,
      restrictedPage,
      'Ograničeni rezultat pretrage',
      `${sharedTerm} sadržaj dostupan samo ovlaštenim korisnicima.`,
    );
    await createAndPublish(
      request,
      publicWorkspace,
      exactPhrasePage,
      'Točna fraza pretrage',
      `${phraseStem} Dio 1 i Dio 2 nalaze se zajedno.`,
    );
    await createAndPublish(
      request,
      publicWorkspace,
      splitPhrasePage,
      'Razdvojeni pojmovi pretrage',
      `${phraseStem} Dio je odvojen od 1, ali Dio 2 ostaje fraza.`,
    );
  });

  test('API key owner sees exactly the pages allowed by the owner ACL', async ({ request }) => {
    const response = await request.get(`/api/v1/workspace-search?q=${encodeURIComponent(sharedTerm)}&lang=hr`, {
      headers: userHeaders,
    });
    const results = await expectData(response);
    expect(results.map((item) => item.title).sort()).toEqual([
      'Javni rezultat pretrage',
      'Ograničeni rezultat pretrage',
    ]);

    const payload = await response.json();
    expect(payload.meta.total).toBe(2);
    expect(payload.meta.filters).toMatchObject({ workspace: '', author: '', from: '', to: '' });

    const filtered = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(sharedTerm)}&workspace=${restrictedWorkspace}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(filtered)).map((item) => item.title)).toEqual([
      'Ograničeni rezultat pretrage',
    ]);
  });

  test('guest result page exposes only public content and no restricted total or snippet', async ({ request }) => {
    const response = await request.get(`/search?q=${encodeURIComponent(sharedTerm)}&lang=hr`);
    expect(response.status()).toBe(200);
    const html = await response.text();

    expect(html).toContain('Javni rezultat pretrage');
    expect(html).not.toContain('Ograničeni rezultat pretrage');
    expect(html).not.toContain('samo ovlaštenim korisnicima');
    expect(html).toMatch(/Pronađeno rezultata:\s*1|Results found:\s*1/);
  });

  test('page title and visible Workspace name are independently searchable', async ({ request }) => {
    const titleResponse = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent('Javni rezultat pretrage')}&lang=hr`,
      { headers: userHeaders },
    );
    const titleResults = await expectData(titleResponse);
    expect(titleResults).toHaveLength(1);
    expect(titleResults[0]).toMatchObject({
      result_type: 'page',
      title: 'Javni rezultat pretrage',
    });

    const workspaceResponse = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent('E2E Search Restricted')}&lang=hr`,
      { headers: userHeaders },
    );
    const workspaceResults = await expectData(workspaceResponse);
    expect(workspaceResults).toHaveLength(1);
    expect(workspaceResults[0]).toMatchObject({
      result_type: 'workspace',
      title: 'E2E Search Restricted',
      workspace_slug: restrictedWorkspace,
    });

    const concealed = await request.get(
      `/search?q=${encodeURIComponent('E2E Search Restricted')}&lang=hr`,
    );
    expect(concealed.status()).toBe(200);
    expect(await concealed.text()).toMatch(/Pronađeno rezultata:\s*0|Results found:\s*0/);
  });

  test('plain input is an exact phrase while plus and quotes require advanced terms', async ({ request }) => {
    const exactResponse = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(`${phraseStem} Dio 1`)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(exactResponse)).map((item) => item.title)).toEqual([
      'Točna fraza pretrage',
    ]);

    const advancedQuery = `+${phraseStem} +Dio +1 +"Dio 2"`;
    const advancedResponse = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(advancedQuery)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(advancedResponse)).map((item) => item.title).sort()).toEqual([
      'Razdvojeni pojmovi pretrage',
      'Točna fraza pretrage',
    ]);
  });

  test('global picker searches any selected combination of visible Workspaces', async ({ page }) => {
    await login(page, userLogin, userPassword);
    await page.goto('/search?lang=hr');

    await page.locator('#workspace-search-workspace-button').click();
    await page.locator(`[data-workspace-search-scope][value="${restrictedWorkspace}"]`).check();
    await page.locator('#workspace-search-q').fill(sharedTerm);
    await page.getByRole('button', { name: /^Pretraži$|^Search$/ }).click();
    await expect(page.getByRole('link', { name: 'Ograničeni rezultat pretrage' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Javni rezultat pretrage' })).toHaveCount(0);

    await page.locator('#workspace-search-workspace-button').click();
    await page.locator(`[data-workspace-search-scope][value="${publicWorkspace}"]`).check();
    await page.getByRole('button', { name: /^Pretraži$|^Search$/ }).click();
    await expect(page.getByRole('link', { name: 'Ograničeni rezultat pretrage' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Javni rezultat pretrage' })).toBeVisible();

    const searchUrl = page.url();
    const query = page.locator('#workspace-search-q');
    await query.fill('');
    await page.getByRole('button', { name: /^Pretraži$|^Search$/ }).click();
    await expect(page).toHaveURL(searchUrl);
    expect(await query.evaluate((input) => input.validationMessage)).toMatch(
      /dva ili više područja|two or more Workspaces/i,
    );
  });

  test('empty term browses a selected Workspace and author picker stays bounded', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.goto(
      `/search?workspaces%5B%5D=${encodeURIComponent(publicWorkspace)}&per_page=1&lang=hr`,
    );

    await expect(page.locator('[data-workspace-search-scope][value="__personal__"]')).toHaveCount(1);
    await expect(page.locator('.hph-workspace-search__help')).toContainText(
      /kriterij.*kombinirati|combine.*criter/i,
    );
    const table = page.locator('.hph-workspace-search__table');
    await expect(table).toBeVisible();
    for (const heading of [
      /Ime stranice|Page name/i,
      /Autor|Author/i,
      /Objavljeno|Published/i,
      /Zadnja izmjena|Last modified/i,
      /Izmijenio|Modified by/i,
    ]) {
      await expect(table.getByRole('columnheader', { name: heading })).toBeVisible();
    }
    await expect(table.locator('tbody tr')).toHaveCount(1);
    await expect(page.locator('.pagination')).toBeVisible();

    const authorPicker = page.locator('[data-workspace-search-author-picker]');
    const initialAuthors = page.waitForResponse((response) => (
      response.url().includes('/search/lookups/authors') && response.status() === 200
    ));
    await authorPicker.locator('[data-author-toggle]').click();
    const initialPayload = await (await initialAuthors).json();
    expect(initialPayload.ok).toBe(true);
    expect(initialPayload.items.length).toBeLessThanOrEqual(25);

    const searchedAuthors = page.waitForResponse((response) => (
      response.url().includes('/search/lookups/authors')
      && response.url().includes(encodeURIComponent(adminLogin))
      && response.status() === 200
    ));
    await authorPicker.locator('[data-author-search]').fill(adminLogin);
    await searchedAuthors;
    const authorChoice = authorPicker.locator('[data-author-choice]:not([data-author-choice=""])').first();
    await expect(authorChoice).toBeVisible();
    await authorChoice.click();
    await expect(authorPicker.locator('[data-author-value]')).not.toHaveValue('');

    await page.locator('input[name="from"]').fill('2020-01-01');
    await page.getByRole('button', { name: /^Pretraži$|^Search$/ }).click();
    await expect(page.locator('input[name="to"]')).not.toHaveValue('');
    await expect(table).toBeVisible();

    await table.getByRole('columnheader', { name: /Ime stranice|Page name/i }).getByRole('link').click();
    await expect(page).toHaveURL(/(?:\?|&)sort=title(?:&|$)/);
    await expect(page).toHaveURL(/(?:\?|&)direction=desc(?:&|$)/);
  });

  test('embedded result page preselects multiple visible editable Workspace scopes', async ({ page }) => {
    await login(page, userLogin, userPassword);
    await page.goto(
      `/search?q=${encodeURIComponent(sharedTerm)}`
      + `&workspaces%5B%5D=${encodeURIComponent(publicWorkspace)}`
      + `&workspaces%5B%5D=${encodeURIComponent(restrictedWorkspace)}&embedded=1&lang=hr`,
    );

    const scope = page.locator('#workspace-search-workspace-button');
    await expect(scope).toHaveJSProperty('tagName', 'BUTTON');
    await expect(scope).toContainText(/Odabrana područja: 2|Selected Workspaces: 2/);
    await scope.click();
    const publicScope = page.locator(`[data-workspace-search-scope][value="${publicWorkspace}"]`);
    const restrictedScope = page.locator(`[data-workspace-search-scope][value="${restrictedWorkspace}"]`);
    await expect(publicScope).toBeChecked();
    await expect(restrictedScope).toBeChecked();
    await expect(page.locator('input[name="workspaces[]"]:checked')).toHaveCount(2);
    await expect(page.locator('input[name="embedded"]')).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Ograničeni rezultat pretrage' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Javni rezultat pretrage' })).toBeVisible();
    await expect(page.getByText(/Bez operatora unesene se riječi|Without operators, the entered words/)).toBeVisible();

    await publicScope.uncheck();
    await page.getByRole('button', { name: /^Pretraži$|^Search$/ }).click();
    await expect(page).not.toHaveURL(/(?:\?|&)embedded=1(?:&|$)/);
    await expect(page.getByRole('link', { name: 'Ograničeni rezultat pretrage' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Javni rezultat pretrage' })).toHaveCount(0);
  });

  test('draft keeps the published index while publishing immediately replaces it', async ({ request }) => {
    const versions = await expectData(await request.get(
      `/api/v1/pages/${publicPage}/versions?lang=hr`,
      { headers: adminHeaders },
    ));
    const publishedVersion = versions[0]?.version_number;
    expect(publishedVersion).toBeTruthy();

    await expectData(await request.post(
      `/api/v1/pages/${publicPage}/versions/${publishedVersion}/restore?lang=hr`,
      {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey('search-public-restore'),
        }),
        data: {},
      },
    ));
    const restoredDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${publicPage}/draft?lang=hr`,
      adminHeaders,
    );
    await expectData(await request.patch(`/api/v1/pages/${publicPage}?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('search-public-update'),
        'If-Match': restoredDraft.etag,
      }),
      data: {
        title: 'Ažurirani javni rezultat pretrage',
        draft_revision: restoredDraft.data.draft_revision,
        contents_visibility: 'inherit',
        content: [{
          type: 'html',
          html: `<h1>Ažurirani javni rezultat pretrage</h1><p>${replacementTerm}</p>`,
        }],
      },
    }));

    /*
     * HR: Spremanje nacrta ne smije iz pretrage ukloniti zadnju objavljenu verziju.
     * EN: Saving a draft must not remove the last published version from search.
     */
    const whileDraft = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(sharedTerm)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(whileDraft))).toHaveLength(2);
    const unpublishedTerm = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(replacementTerm)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(unpublishedTerm))).toHaveLength(0);

    const updatedDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${publicPage}/draft?lang=hr`,
      adminHeaders,
    );
    await expectData(await request.post(`/api/v1/pages/${publicPage}/publish?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('search-public-republish'),
        'If-Match': updatedDraft.etag,
      }),
      data: {},
    }));

    const afterPublish = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(replacementTerm)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(afterPublish)).map((item) => item.title)).toEqual([
      'Ažurirani javni rezultat pretrage',
    ]);
    const oldAfterPublish = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(sharedTerm)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(oldAfterPublish)).map((item) => item.title)).toEqual([
      'Ograničeni rezultat pretrage',
    ]);
  });

  test('deleting a page immediately removes its language rows from search', async ({ request }) => {
    const editable = await getDataWithEtag(
      request,
      `/api/v1/pages/${publicPage}?lang=hr`,
      adminHeaders,
    );
    const deleted = await request.delete(`/api/v1/pages/${publicPage}?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('search-public-delete'),
        'If-Match': editable.etag,
      }),
    });
    expect(deleted.status()).toBe(204);

    const apiResult = await request.get(
      `/api/v1/workspace-search?q=${encodeURIComponent(replacementTerm)}&lang=hr`,
      { headers: userHeaders },
    );
    expect((await expectData(apiResult))).toHaveLength(0);

    const guestResult = await request.get(`/search?q=${encodeURIComponent(replacementTerm)}&lang=hr`);
    expect(guestResult.status()).toBe(200);
    expect(await guestResult.text()).toMatch(/Pronađeno rezultata:\s*0|Results found:\s*0/);
  });

  test('administrator can rebuild one workspace or the whole site from settings', async ({ page }) => {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/workspace-search');
    const workspacePicker = page.locator('[data-workspace-lookup-picker="workspace"]');
    const workspaceValue = workspacePicker.locator('[data-workspace-lookup-value]');
    await expect(workspacePicker).toHaveAttribute('data-workspace-lookup-ready', '1');
    await workspacePicker.locator('[data-workspace-lookup-toggle]').click();
    await workspacePicker.locator('[data-workspace-lookup-search]').fill(restrictedWorkspace);
    await workspacePicker
      .locator('[data-workspace-lookup-list]')
      .getByRole('button', { name: 'E2E Search Restricted', exact: true })
      .click();
    await expect(workspaceValue).not.toHaveValue('0');
    await Promise.all([
      page.waitForResponse((response) => (
        response.request().method() === 'POST'
        && new URL(response.url()).pathname.endsWith('/settings/workspace-search/reindex')
      )),
      page.getByRole('button', { name: /Ponovno izgradi indeks|Rebuild index/ }).click(),
    ]);
    await expect(page.getByRole('alert')).toContainText(/Indeks je obnovljen|Index rebuilt/);

    await expect(workspacePicker).toHaveAttribute('data-workspace-lookup-ready', '1');
    await workspacePicker.locator('[data-workspace-lookup-toggle]').click();
    await workspacePicker
      .locator('[data-workspace-lookup-list]')
      .getByRole('button', { name: /All workspaces|Sva područja/i, exact: true })
      .click();
    await expect(workspaceValue).toHaveValue('0');
    await Promise.all([
      page.waitForResponse((response) => (
        response.request().method() === 'POST'
        && new URL(response.url()).pathname.endsWith('/settings/workspace-search/reindex')
      )),
      page.getByRole('button', { name: /Ponovno izgradi indeks|Rebuild index/ }).click(),
    ]);
    await expect(page.getByRole('alert')).toContainText(/Indeks je obnovljen|Index rebuilt/);
  });
});
