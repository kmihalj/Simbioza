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
} = e2eEnvironment();
const adminHeaders = apiHeaders(adminApiToken);
const userHeaders = apiHeaders(userApiToken);

test.describe.serial('Workspace Search web and API ACL boundary', () => {
  const publicWorkspace = 'e2e-search-public';
  const restrictedWorkspace = 'e2e-search-restricted';
  const publicPage = 'e2e-search-public-page';
  const restrictedPage = 'e2e-search-restricted-page';
  const sharedTerm = `simbioza-search-boundary-${Date.now()}`;
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
    await expect(page.locator('#workspace-search-reindex-scope')).toBeVisible();
    await page.locator('#workspace-search-reindex-scope').selectOption({
      label: 'E2E Search Restricted',
    });
    await Promise.all([
      page.waitForResponse((response) => (
        response.request().method() === 'POST'
        && new URL(response.url()).pathname.endsWith('/settings/workspace-search/reindex')
      )),
      page.getByRole('button', { name: /Ponovno izgradi indeks|Rebuild index/ }).click(),
    ]);
    await expect(page.getByRole('alert')).toContainText(/Indeks je obnovljen|Index rebuilt/);

    await page.locator('#workspace-search-reindex-scope').selectOption('0');
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
