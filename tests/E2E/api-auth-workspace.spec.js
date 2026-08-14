import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  expectProblem,
  getDataWithEtag,
  idempotencyKey,
} from './helpers.js';

const { adminApiToken, userApiToken, userLogin } = e2eEnvironment();
const adminHeaders = apiHeaders(adminApiToken);
const userHeaders = apiHeaders(userApiToken);

test.describe.serial('Auth and API administration lifecycle', () => {
  let createdUserId;
  let createdGroupId;

  test('API discovery, OpenAPI, CORS, pagination, and authorization boundaries are active', async ({ request }) => {
    const discovery = await request.get('/api/v1', { headers: adminHeaders });
    const discoveryData = await expectData(discovery);
    expect(discoveryData.resources).toEqual(expect.arrayContaining([
      'users', 'groups', 'audit', 'workspace', 'page', 'attachment', 'calendar',
      'task', 'notifications', 'webhooks', 'workspace-search',
    ]));
    expect(discoveryData.scope_groups.map((group) => group.module)).toEqual(expect.arrayContaining([
      'auth', 'workspace', 'editor-html', 'calendar', 'task', 'notification', 'api',
      'workspace-search',
    ]));
    expect(discoveryData.security).toMatchObject({
      authentication: 'bearer',
      idempotency_key_for_writes: true,
      problem_format: 'RFC 9457',
    });

    const openApi = await request.get('/api/v1/openapi.json', { headers: adminHeaders });
    expect(openApi.status()).toBe(200);
    expect(openApi.headers()['content-type']).toContain('application/vnd.oai.openapi+json');
    const openApiData = await openApi.json();
    expect(openApiData.openapi).toBe('3.1.0');
    expect(Object.keys(openApiData.paths)).toEqual(expect.arrayContaining([
      '/api/v1/users',
      '/api/v1/workspaces/{workspaceSlug}',
      '/api/v1/pages/{documentId}/attachments/{assetUuid}',
      '/api/v1/calendars/{calendarUuid}/events/{eventId}',
      '/api/v1/workspace-search',
      '/api/v1/notifications/{uuid}',
      '/api/v1/webhooks/{uuid}/deliveries/{deliveryUuid}',
    ]));

    const cors = await request.fetch('/api/v1/users', {
      method: 'OPTIONS',
      headers: {
        Origin: 'https://client.example',
        'Access-Control-Request-Method': 'GET',
        'Access-Control-Request-Headers': 'Authorization',
      },
    });
    expect([200, 204]).toContain(cors.status());
    expect(cors.headers()['access-control-allow-methods']).toContain('GET');

    const ordinaryUser = await request.get('/api/v1/users?page[limit]=1', { headers: userHeaders });
    await expectProblem(ordinaryUser, 403, 'administrator_required');

    const firstPage = await request.get('/api/v1/users?page[limit]=1', { headers: adminHeaders });
    const firstPagePayload = await firstPage.json();
    expect(firstPage.status()).toBe(200);
    expect(firstPagePayload.data).toHaveLength(1);
    expect(firstPagePayload.meta.page.limit).toBe(1);
    expect(firstPagePayload.meta.page.has_more).toBe(true);
    expect(firstPagePayload.meta.page.next_cursor).toBeTruthy();
  });

  test('group and user CRUD honors ETags, memberships, idempotency, and safe output', async ({ request }) => {
    const groupCreated = await request.post('/api/v1/groups', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('auth-group-create') }),
      data: { name: 'E2E Documentation Editors', is_enabled: true },
    });
    const group = await expectData(groupCreated, 201);
    createdGroupId = group.id;
    expect(group.name).toBe('E2E Documentation Editors');

    const createKey = idempotencyKey('auth-user-create');
    const userPayload = {
      login_identifier: 'e2e-api-user@example.invalid',
      password: 'E2eApiUser!2026',
      is_active: true,
      is_admin: false,
      provider_access: { local: true },
      attributes: {
        display_name: 'E2E API User',
        email: 'e2e-api-user@example.invalid',
      },
    };
    const created = await request.post('/api/v1/users', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': createKey }),
      data: userPayload,
    });
    const createdUser = await expectData(created, 201);
    createdUserId = createdUser.id;
    expect(created.headers().location).toContain(`/api/v1/users/${createdUserId}`);
    expect(createdUser).not.toHaveProperty('password');
    expect(createdUser).not.toHaveProperty('password_hash');

    const replay = await request.post('/api/v1/users', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': createKey }),
      data: userPayload,
    });
    expect(replay.status()).toBe(201);
    expect(replay.headers()['idempotency-replayed']).toBe('true');
    expect((await replay.json()).data.id).toBe(createdUserId);

    const userResource = await getDataWithEtag(request, `/api/v1/users/${createdUserId}`, adminHeaders);
    expect(userResource.data.login_identifier).toBe(userPayload.login_identifier);

    const missingPrecondition = await request.patch(`/api/v1/users/${createdUserId}`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('auth-user-no-etag') }),
      data: { attributes: { display_name: 'Must not be saved' } },
    });
    await expectProblem(missingPrecondition, 428, 'if_match_required');

    const membership = await request.put(`/api/v1/users/${createdUserId}/groups`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('auth-user-groups'),
        'If-Match': userResource.etag,
      }),
      data: { group_ids: [createdGroupId] },
    });
    const membershipData = await expectData(membership);
    expect(membershipData.groups.map((groupItem) => groupItem.id)).toContain(createdGroupId);

    const groupResource = await getDataWithEtag(request, `/api/v1/groups/${createdGroupId}`, adminHeaders);
    const updatedGroup = await request.patch(`/api/v1/groups/${createdGroupId}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('auth-group-update'),
        'If-Match': groupResource.etag,
      }),
      data: { name: 'E2E Documentation Reviewers' },
    });
    expect((await expectData(updatedGroup)).name).toBe('E2E Documentation Reviewers');

    const currentUser = await getDataWithEtag(request, `/api/v1/users/${createdUserId}`, adminHeaders);
    const updatedUser = await request.patch(`/api/v1/users/${createdUserId}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('auth-user-update'),
        'If-Match': currentUser.etag,
      }),
      data: { attributes: { display_name: 'E2E Updated API User' } },
    });
    expect((await expectData(updatedUser)).display_name).toBe('E2E Updated API User');

    const users = await request.get('/api/v1/users?page[limit]=100', { headers: adminHeaders });
    expect((await expectData(users)).map((item) => item.id)).toContain(createdUserId);
    const groups = await request.get('/api/v1/groups', { headers: adminHeaders });
    expect((await expectData(groups)).map((item) => item.id)).toContain(createdGroupId);
  });

  test('audit reports mutations and temporary Auth records can be removed safely', async ({ request }) => {
    const audit = await request.get('/api/v1/audit?page[limit]=100', { headers: adminHeaders });
    const auditData = await expectData(audit);
    expect(auditData.map((event) => event.event_key)).toEqual(expect.arrayContaining([
      'auth.api_group_created', 'auth.api_user_created', 'auth.api_user_groups_replaced',
      'auth.api_group_updated', 'auth.api_user_updated',
    ]));

    const currentUser = await getDataWithEtag(request, `/api/v1/users/${createdUserId}`, adminHeaders);
    const deletedUser = await request.delete(`/api/v1/users/${createdUserId}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('auth-user-delete'),
        'If-Match': currentUser.etag,
      }),
    });
    expect(deletedUser.status()).toBe(204);

    const currentGroup = await getDataWithEtag(request, `/api/v1/groups/${createdGroupId}`, adminHeaders);
    const deletedGroup = await request.delete(`/api/v1/groups/${createdGroupId}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('auth-group-delete'),
        'If-Match': currentGroup.etag,
      }),
    });
    expect(deletedGroup.status()).toBe(204);

    const ordinaryMe = await request.get('/api/v1/me', { headers: userHeaders });
    const ordinaryMeData = await expectData(ordinaryMe);
    expect(ordinaryMeData.user.login_identifier).toBe(userLogin);
    expect(ordinaryMeData.user.is_admin).toBe(false);
  });
});

test.describe.serial('Workspace API and ACL lifecycle', () => {
  const workspaceSlug = 'e2e-complete-workspace';
  let workspaceId;
  let ordinaryUserId;
  let firstNodeId;
  let secondNodeId;

  test('workspace creation, concealed reads, ACL, and subject search work', async ({ request }) => {
    const adminIdentity = await expectData(await request.get('/api/v1/me', {
      headers: adminHeaders,
    }));
    const adminUserId = Number(adminIdentity.user.id);
    expect(adminUserId).toBeGreaterThan(0);

    const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: adminHeaders,
    }));
    ordinaryUserId = users.find((user) => user.login_identifier === userLogin)?.id;
    expect(ordinaryUserId).toBeTruthy();

    const created = await request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-create') }),
      data: {
        name: 'E2E Complete Workspace',
        slug: workspaceSlug,
        description: 'Exercises the complete Workspace HTTP API.',
        visibility: 'restricted',
      },
    });
    const workspace = await expectData(created, 201);
    workspaceId = workspace.id;
    expect(workspace.permissions.can_manage).toBe(true);

    const adminVisible = await request.get(`/api/v1/workspaces/${workspaceSlug}`, {
      headers: adminHeaders,
    });
    expect((await expectData(adminVisible)).permissions.can_manage).toBe(true);

    const concealed = await request.get(`/api/v1/workspaces/${workspaceSlug}`, { headers: userHeaders });
    await expectProblem(concealed, 404, 'workspace_not_found');

    const aclBefore = await request.get(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: adminHeaders,
    });
    await expectData(aclBefore);

    const subjectSearch = await request.get(
      `/api/v1/workspaces/${workspaceSlug}/acl/subjects?category=user&q=e2e-user`,
      { headers: adminHeaders },
    );
    expect((await expectData(subjectSearch)).some((subject) => subject.id === ordinaryUserId)).toBe(true);

    const acl = await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-acl') }),
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
    });
    const aclData = await expectData(acl);
    expect(aclData.find((subject) => subject.id === ordinaryUserId)?.permissions.can_view).toBe(true);

    const visible = await request.get(`/api/v1/workspaces/${workspaceSlug}`, { headers: userHeaders });
    expect((await expectData(visible)).permissions.can_view).toBe(true);

    /*
     * HR: I GET pristupi kroz stateless API moraju nositi stvarnog izvršitelja,
     *     ne oznaku gosta. Provjeravamo administratorsko i obično korisničko
     *     čitanje istoga područja.
     * EN: Stateless API GET access must also carry the real actor rather than
     *     a guest label. Verify both administrator and regular-user reads of
     *     the same workspace.
     */
    const readAudit = await expectData(await request.get(
      `/api/v1/audit?page[limit]=100&event_key=workspace.view&channel=api&target=${encodeURIComponent(workspaceSlug)}`,
      { headers: adminHeaders },
    ));
    expect(readAudit.length).toBeGreaterThanOrEqual(2);
    expect(readAudit.every((event) => Number(event.actor_user_id) > 0)).toBe(true);
    expect(readAudit.map((event) => Number(event.actor_user_id))).toEqual(expect.arrayContaining([
      adminUserId, Number(ordinaryUserId),
    ]));
    expect(readAudit.every((event) => !/^guest$|^gost$/i.test(String(event.actor_label ?? '')))).toBe(true);

    const stillCannotManage = await request.get(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: userHeaders,
    });
    await expectProblem(stillCannotManage, 403, 'workspace_access_denied');

    const theme = await expectData(await request.get(`/api/v1/workspaces/${workspaceSlug}/theme`, {
      headers: adminHeaders,
    }));
    expect(theme.settings.active_theme).toBeTruthy();
    expect(theme.themes.map((item) => item.id)).toEqual(expect.arrayContaining(['standard']));

    const ordinaryTheme = await request.get(`/api/v1/workspaces/${workspaceSlug}/theme`, {
      headers: userHeaders,
    });
    await expectProblem(ordinaryTheme, 403, 'workspace_access_denied');

    const selectedTheme = await expectData(await request.put(
      `/api/v1/workspaces/${workspaceSlug}/theme/selection`,
      {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey('workspace-theme-selection'),
        }),
        data: { theme_id: 'standard', mode_policy: 'auto' },
      },
    ));
    expect(selectedTheme.settings.active_theme).toBe('standard');

    const uploadedTheme = await expectData(await request.post(
      `/api/v1/workspaces/${workspaceSlug}/theme/assets`,
      {
        headers: adminHeaders,
        multipart: {
          role: 'other',
          asset: {
            name: 'e2e-workspace-mark.svg',
            mimeType: 'image/svg+xml',
            buffer: Buffer.from(
              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" fill="#1677ff"/></svg>',
              'utf8',
            ),
          },
        },
      },
    ));
    const uploadedAsset = uploadedTheme.assets.find((asset) => asset.file.includes('e2e-workspace-mark'));
    expect(uploadedAsset?.used).toBe(false);

    const deletedThemeAsset = await expectData(await request.delete(
      `/api/v1/workspaces/${workspaceSlug}/theme/assets/${encodeURIComponent(uploadedAsset.file)}`,
      { headers: adminHeaders },
    ));
    expect(deletedThemeAsset.assets.map((asset) => asset.file)).not.toContain(uploadedAsset.file);

    const savedTheme = await expectData(await request.patch(`/api/v1/workspaces/${workspaceSlug}/theme`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-theme-save'),
      }),
      data: { theme: deletedThemeAsset.selected_theme, mode_policy: 'dark' },
    }));
    expect(savedTheme.settings.mode_policy).toBe('dark');
    expect(savedTheme.assets_read_only).toBe(false);

    const exportedTheme = await request.get(`/api/v1/workspaces/${workspaceSlug}/theme/export`, {
      headers: adminHeaders,
    });
    expect(exportedTheme.status()).toBe(200);
    expect(exportedTheme.headers()['content-type']).toContain('application/zip');
    const themeArchive = await exportedTheme.body();
    expect(themeArchive.byteLength).toBeGreaterThan(100);

    const importedTheme = await expectData(await request.post(
      `/api/v1/workspaces/${workspaceSlug}/theme/import`,
      {
        headers: adminHeaders,
        multipart: {
          mode_policy: 'auto',
          theme: {
            name: 'e2e-workspace-theme.zip',
            mimeType: 'application/zip',
            buffer: themeArchive,
          },
        },
      },
    ));
    expect(importedTheme.settings.mode_policy).toBe('auto');
    expect(importedTheme.assets_read_only).toBe(false);

    const homepageSettings = await expectData(await request.get('/api/v1/workspaces/homepage/settings', {
      headers: adminHeaders,
    }));
    expect(homepageSettings).toHaveProperty('settings');
    const ordinaryHomepageSettings = await request.get('/api/v1/workspaces/homepage/settings', {
      headers: userHeaders,
    });
    await expectProblem(ordinaryHomepageSettings, 403, 'workspace_access_denied');
  });

  test('tree links, complete ordering, node ACL, updates, and deletion work', async ({ request }) => {
    const firstNode = await request.post(`/api/v1/workspaces/${workspaceSlug}/nodes`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-one') }),
      data: {
        node_type: 'internal_link',
        title: 'E2E Internal Link',
        slug: 'e2e-internal-link',
        target_url: '/about',
        sort_order: 20,
      },
    });
    firstNodeId = (await expectData(firstNode, 201)).id;

    const secondNode = await request.post(`/api/v1/workspaces/${workspaceSlug}/nodes`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-two') }),
      data: {
        node_type: 'external_link',
        title: 'E2E External Link',
        slug: 'e2e-external-link',
        target_url: 'https://example.com/',
        sort_order: 10,
      },
    });
    secondNodeId = (await expectData(secondNode, 201)).id;

    const tree = await expectData(await request.get(`/api/v1/workspaces/${workspaceSlug}/tree?lang=en`, {
      headers: adminHeaders,
    }));
    expect(tree.flatMap((node) => [node.id])).toEqual(expect.arrayContaining([firstNodeId, secondNodeId]));

    const shorts = await expectData(await request.get(
      `/api/v1/workspaces/${workspaceSlug}/shorts?lang=en&depth=2&limit=10&order=newest`,
      { headers: adminHeaders },
    ));
    expect(shorts).toMatchObject({ depth: 2, limit: '10', order: 'newest', total: 0 });

    const exportedWorkspace = await request.post(`/api/v1/workspaces/${workspaceSlug}/exports/html`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-html-export'),
      }),
      data: { node_ids: [firstNodeId, secondNodeId] },
    });
    await expectProblem(exportedWorkspace, 422, 'workspace_validation_failed');

    const reordered = await request.put(`/api/v1/workspaces/${workspaceSlug}/tree/order`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-order') }),
      data: {
        placements: [
          { id: firstNodeId, parent_id: null, sort_order: 10 },
          { id: secondNodeId, parent_id: null, sort_order: 20 },
        ],
      },
    });
    await expectData(reordered);

    const nodeAclBefore = await request.get(
      `/api/v1/workspaces/${workspaceSlug}/nodes/${firstNodeId}/acl`,
      { headers: adminHeaders },
    );
    await expectData(nodeAclBefore);
    const nodeAcl = await request.put(
      `/api/v1/workspaces/${workspaceSlug}/nodes/${firstNodeId}/acl`,
      {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-acl') }),
        data: {
          subjects: [{
            type: 'user',
            id: ordinaryUserId,
            permissions: {
              can_view: true,
              can_add: false,
              can_edit: true,
              can_publish: false,
              can_delete: false,
              can_manage: false,
            },
          }],
        },
      },
    );
    const nodeAclData = await expectData(nodeAcl);
    expect(nodeAclData[0].permissions).toMatchObject({ can_view: true, can_edit: true });

    const clearedNodeAcl = await request.put(
      `/api/v1/workspaces/${workspaceSlug}/nodes/${firstNodeId}/acl`,
      {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-acl-clear') }),
        data: { subjects: [] },
      },
    );
    expect(await expectData(clearedNodeAcl)).toEqual([]);

    const updated = await request.patch(`/api/v1/workspaces/${workspaceSlug}/nodes/${secondNodeId}`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-update') }),
      data: { title: 'E2E Updated External Link', target_url: 'https://example.org/' },
    });
    expect((await expectData(updated)).title).toBe('E2E Updated External Link');

    const deleted = await request.delete(`/api/v1/workspaces/${workspaceSlug}/nodes/${secondNodeId}`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-node-delete') }),
    });
    expect(deleted.status()).toBe(204);
  });

  test('workspace ETag update, soft deletion, administrator restore, and list endpoints work', async ({ request }) => {
    const list = await request.get('/api/v1/workspaces?page[limit]=100', { headers: adminHeaders });
    expect((await expectData(list)).map((workspace) => workspace.slug)).toContain(workspaceSlug);

    const current = await getDataWithEtag(request, `/api/v1/workspaces/${workspaceSlug}`, adminHeaders);
    const stale = await request.patch(`/api/v1/workspaces/${workspaceSlug}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-stale'),
        'If-Match': '"stale-workspace-etag"',
      }),
      data: { description: 'Must not be saved.' },
    });
    await expectProblem(stale, 412, 'resource_changed');

    const updated = await request.patch(`/api/v1/workspaces/${workspaceSlug}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-update'),
        'If-Match': current.etag,
      }),
      data: { description: 'Updated through an ETag protected request.' },
    });
    expect((await expectData(updated)).description).toContain('ETag protected');

    const afterUpdate = await getDataWithEtag(request, `/api/v1/workspaces/${workspaceSlug}`, adminHeaders);
    const deleted = await request.delete(`/api/v1/workspaces/${workspaceSlug}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('workspace-delete'),
        'If-Match': afterUpdate.etag,
      }),
    });
    expect(deleted.status()).toBe(204);

    const deletedList = await request.get('/api/v1/workspaces/deleted?page[limit]=100', {
      headers: adminHeaders,
    });
    expect((await expectData(deletedList)).map((workspace) => workspace.id)).toContain(workspaceId);
    const ordinaryDeletedList = await request.get('/api/v1/workspaces/deleted', { headers: userHeaders });
    await expectProblem(ordinaryDeletedList, 403, 'workspace_access_denied');

    const restored = await request.post(`/api/v1/workspaces/deleted/${workspaceId}/restore`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('workspace-restore') }),
      data: { slug: `${workspaceSlug}-restored` },
    });
    expect((await expectData(restored)).slug).toBe(`${workspaceSlug}-restored`);
  });
});
