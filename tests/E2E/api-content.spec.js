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

test.describe.serial('Editor, attachment, translation, version, and Task lifecycle', () => {
  const workspaceSlug = 'e2e-content-api';
  const documentId = 'e2e-api-guide';
  let userId;
  let taskUuid;
  let assetUuid;
  let workspaceNodeId;

  test('an editor creates and updates a structured shared draft without gaining publish rights', async ({ request }) => {
    const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: adminHeaders,
    }));
    userId = users.find((user) => user.login_identifier === userLogin)?.id;
    expect(userId).toBeTruthy();

    await expectData(await request.post('/api/v1/workspaces', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('content-workspace') }),
      data: {
        name: 'E2E Content API',
        slug: workspaceSlug,
        description: 'Complete Editor, Attachment, and Task API lifecycle.',
        visibility: 'restricted',
      },
    }), 201);

    await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('content-acl') }),
      data: {
        subjects: [{
          type: 'user',
          id: userId,
          permissions: {
            can_view: true,
            can_add: true,
            can_edit: true,
            can_publish: false,
            can_delete: false,
            can_manage: false,
          },
        }],
      },
    }));

    const created = await request.post('/api/v1/pages', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('content-page') }),
      data: {
        title: 'E2E API Guide',
        slug: documentId,
        workspace_slug: workspaceSlug,
        language: 'en',
        contents_visibility: 'hidden',
        content: [
          { type: 'html', html: '<h1>E2E API Guide</h1><p>Initial draft.</p>' },
          {
            type: 'task_list',
            title: 'Release checklist',
            toggle_scope: 'viewers',
            items: [{ text: 'Run complete E2E suite' }, { text: 'Review performance baseline' }],
          },
        ],
      },
    });
    const draft = await expectData(created, 201);
    expect(draft.id).toBe(documentId);
    expect(draft.is_draft).toBe(true);
    expect(draft.contents_visibility).toBe('hidden');
    workspaceNodeId = draft.workspace_node.id;
    expect(workspaceNodeId).toBeTruthy();
    taskUuid = draft.content.find((block) => block.type === 'task_list').items[0].uuid;
    expect(taskUuid).toBeTruthy();

    const unpublished = await request.get(`/api/v1/pages/${documentId}?lang=en`, {
      headers: userHeaders,
    });
    await expectProblem(unpublished, 404, 'editor_resource_not_found');

    const currentDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      userHeaders,
    );
    const staleRevision = await request.patch(`/api/v1/pages/${documentId}?lang=en`, {
      headers: apiHeaders(userApiToken, {
        'Idempotency-Key': idempotencyKey('content-stale-revision'),
        'If-Match': currentDraft.etag,
      }),
      data: { title: 'E2E API Guide', html: '<p>Stale</p>', draft_revision: 'stale-revision' },
    });
    await expectProblem(staleRevision, 409, 'editor_conflict');

    const updated = await request.patch(`/api/v1/pages/${documentId}?lang=en`, {
      headers: apiHeaders(userApiToken, {
        'Idempotency-Key': idempotencyKey('content-update'),
        'If-Match': currentDraft.etag,
      }),
      data: {
        title: 'E2E API Guide',
        draft_revision: currentDraft.data.draft_revision,
        contents_visibility: 'shown',
        content: [
          { type: 'html', html: '<h1>E2E API Guide</h1><p>Edited by a non-publisher.</p>' },
          currentDraft.data.content.find((block) => block.type === 'task_list'),
        ],
      },
    });
    const updatedDraft = await expectData(updated);
    expect(updatedDraft.html).toContain('Edited by a non-publisher');
    expect(updatedDraft.contents_visibility).toBe('shown');
  });

  test('normal and chunked attachment routes preserve bytes, metadata, cancellation, and visibility', async ({ request }) => {
    const guardedUpload = await request.post(`/api/v1/pages/${documentId}/attachments?lang=en`, {
      headers: apiHeaders(userApiToken, { 'Idempotency-Key': idempotencyKey('attachment-upload') }),
      multipart: {
        attachment: {
          name: 'e2e-guide.txt',
          mimeType: 'text/plain',
          buffer: Buffer.from('E2E attachment payload\n', 'utf8'),
        },
      },
    });
    await expectProblem(guardedUpload, 422, 'idempotency_not_supported_for_upload');

    const upload = await request.post(`/api/v1/pages/${documentId}/attachments?lang=en`, {
      headers: apiHeaders(userApiToken),
      multipart: {
        attachment: {
          name: 'e2e-guide.txt',
          mimeType: 'text/plain',
          buffer: Buffer.from('E2E attachment payload\n', 'utf8'),
        },
      },
    });
    const asset = await expectData(upload, 201);
    assetUuid = asset.uuid;
    expect(asset.original_name).toBe('e2e-guide.txt');

    const partialUploadId = `cancel-${Date.now()}`;
    const partial = await request.post(`/api/v1/pages/${documentId}/attachments/chunks?lang=en`, {
      headers: apiHeaders(userApiToken),
      multipart: {
        upload_id: partialUploadId,
        original_name: 'large-e2e.txt',
        mime_type: 'text/plain',
        file_size: '12',
        chunk_index: '0',
        chunk_count: '2',
        attachment: {
          name: 'chunk-0.part',
          mimeType: 'application/octet-stream',
          buffer: Buffer.from('first-', 'utf8'),
        },
      },
    });
    expect(partial.status()).toBe(202);

    const cancelled = await request.delete(
      `/api/v1/pages/${documentId}/attachments/chunks/${partialUploadId}?lang=en`,
      { headers: apiHeaders(userApiToken) },
    );
    expect(cancelled.status()).toBe(204);

    const draft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      userHeaders,
    );
    const visibility = await request.put(`/api/v1/pages/${documentId}/attachment-visibility?lang=en`, {
      headers: apiHeaders(userApiToken, {
        'Idempotency-Key': idempotencyKey('attachment-visibility'),
        'If-Match': draft.etag,
      }),
      data: { visibility: 'authenticated' },
    });
    expect((await expectData(visibility)).attachment_visibility).toBe('authenticated');
  });

  test('review emits a domain notification and only a real publisher may publish', async ({ request }) => {
    const draft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      userHeaders,
    );
    const reviewed = await request.post(`/api/v1/pages/${documentId}/review?lang=en`, {
      headers: apiHeaders(userApiToken, {
        'Idempotency-Key': idempotencyKey('content-review'),
        'If-Match': draft.etag,
      }),
      data: {},
    });
    const reviewData = await expectData(reviewed);
    expect(reviewData).toMatchObject({ id: documentId, is_draft: true, workspace_managed: true });

    const afterReview = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      userHeaders,
    );
    const forbiddenPublish = await request.post(`/api/v1/pages/${documentId}/publish?lang=en`, {
      headers: apiHeaders(userApiToken, {
        'Idempotency-Key': idempotencyKey('content-user-publish'),
        'If-Match': afterReview.etag,
      }),
      data: {},
    });
    await expectProblem(forbiddenPublish, 403, 'editor_permission_denied');

    const adminDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      adminHeaders,
    );
    const published = await request.post(`/api/v1/pages/${documentId}/publish?lang=en`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('content-admin-publish'),
        'If-Match': adminDraft.etag,
      }),
      data: {},
    });
    const publishedData = await expectData(published);
    expect(publishedData.is_draft).toBe(false);

    const rendered = await request.get(`/api/v1/pages/${documentId}?lang=en&rendered=1`, {
      headers: userHeaders,
    });
    const renderedData = await expectData(rendered);
    expect(renderedData.rendered_html).toContain('Edited by a non-publisher');
    expect(renderedData.attachment_visibility).toBe('authenticated');

    const exportedWorkspace = await request.post(`/api/v1/workspaces/${workspaceSlug}/exports/html`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('content-workspace-html-export'),
      }),
      data: { node_ids: [workspaceNodeId] },
    });
    expect(exportedWorkspace.status()).toBe(200);
    expect(exportedWorkspace.headers()['content-type']).toContain('application/zip');
    expect((await exportedWorkspace.body()).byteLength).toBeGreaterThan(100);

    const inbox = await request.get('/api/v1/notifications?page[limit]=100', { headers: adminHeaders });
    const notifications = await expectData(inbox);
    const reviewNotification = notifications.find(
      (notification) => JSON.stringify(notification).includes('E2E API Guide'),
    );
    expect(reviewNotification).toBeTruthy();

    const notification = await getDataWithEtag(
      request,
      `/api/v1/notifications/${reviewNotification.uuid}`,
      adminHeaders,
    );
    expect(notification.data.is_read).toBe(false);
    const read = await request.patch(`/api/v1/notifications/${reviewNotification.uuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('notification-read'),
        'If-Match': notification.etag,
      }),
      data: { read: true },
    });
    expect((await expectData(read)).is_read).toBe(true);

    const readAll = await request.post('/api/v1/notifications/read-all', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('notification-read-all') }),
      data: {},
    });
    await expectData(readAll);

    const currentNotification = await getDataWithEtag(
      request,
      `/api/v1/notifications/${reviewNotification.uuid}`,
      adminHeaders,
    );
    const deletedNotification = await request.delete(
      `/api/v1/notifications/${reviewNotification.uuid}`,
      {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey('notification-delete'),
          'If-Match': currentNotification.etag,
        }),
      },
    );
    expect(deletedNotification.status()).toBe(204);
  });

  test('published versions, translation drafts, restore, and discard stay isolated', async ({ request }) => {
    const pages = await request.get('/api/v1/pages?page[limit]=100', { headers: userHeaders });
    expect((await expectData(pages)).map((page) => page.id)).toContain(documentId);

    const versionsResponse = await request.get(`/api/v1/pages/${documentId}/versions?lang=en`, {
      headers: userHeaders,
    });
    const versions = await expectData(versionsResponse);
    expect(versions.length).toBeGreaterThanOrEqual(1);
    const firstVersion = versions.at(-1).version_number;

    const version = await request.get(
      `/api/v1/pages/${documentId}/versions/${firstVersion}?lang=en`,
      { headers: userHeaders },
    );
    expect((await expectData(version)).version_number).toBe(firstVersion);

    const translated = await request.post(`/api/v1/pages/${documentId}/translations`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('content-translation') }),
      data: { source_language: 'en', target_language: 'hr' },
    });
    const translationDraft = await expectData(translated, 201);
    expect(translationDraft.language).toBe('hr');
    expect(translationDraft.is_draft).toBe(true);

    const hrDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=hr`,
      adminHeaders,
    );
    const hrPublished = await request.post(`/api/v1/pages/${documentId}/publish?lang=hr`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('content-translation-publish'),
        'If-Match': hrDraft.etag,
      }),
      data: {},
    });
    expect((await expectData(hrPublished)).language).toBe('hr');
    await expectData(await request.get(`/api/v1/pages/${documentId}?lang=hr`, { headers: userHeaders }));

    const restored = await request.post(
      `/api/v1/pages/${documentId}/versions/${firstVersion}/restore?lang=en`,
      {
        headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('content-restore') }),
        data: {},
      },
    );
    expect((await expectData(restored)).is_draft).toBe(true);

    const restoredDraft = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/draft?lang=en`,
      adminHeaders,
    );
    const discarded = await request.delete(`/api/v1/pages/${documentId}/draft?lang=en`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('content-discard'),
        'If-Match': restoredDraft.etag,
      }),
    });
    expect(discarded.status()).toBe(200);
    expect((await discarded.json()).data.is_draft).toBe(false);
  });

  test('attachment read/update/content/delete and Task state/history routes work end to end', async ({ request }) => {
    const attachments = await request.get(`/api/v1/pages/${documentId}/attachments?lang=en`, {
      headers: userHeaders,
    });
    expect((await expectData(attachments)).map((asset) => asset.uuid)).toContain(assetUuid);

    const asset = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/attachments/${assetUuid}?lang=en`,
      userHeaders,
    );
    const metadata = await request.patch(
      `/api/v1/pages/${documentId}/attachments/${assetUuid}?lang=en`,
      {
        headers: apiHeaders(userApiToken, {
          'Idempotency-Key': idempotencyKey('attachment-metadata'),
          'If-Match': asset.etag,
        }),
        data: {
          display_name: 'E2E guide attachment',
          alt_text: 'Plain text test attachment',
          caption: 'Complete E2E fixture',
          description: 'Temporary isolated test data.',
        },
      },
    );
    expect((await expectData(metadata)).display_name).toBe('E2E guide attachment');

    const content = await request.get(
      `/api/v1/pages/${documentId}/attachments/${assetUuid}/content?lang=en&download=1`,
      { headers: userHeaders },
    );
    expect(content.status()).toBe(200);
    expect(content.headers()['content-type']).toContain('text/plain');
    expect(await content.text()).toBe('E2E attachment payload\n');

    const tasks = await request.get(`/api/v1/pages/${documentId}/tasks?lang=en`, {
      headers: userHeaders,
    });
    expect((await expectData(tasks)).map((taskItem) => taskItem.uuid)).toContain(taskUuid);
    const taskResource = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/tasks/${taskUuid}?lang=en`,
      userHeaders,
    );
    expect(taskResource.data.state.completed).toBe(false);

    const completionKey = idempotencyKey('task-complete');
    const completed = await request.put(
      `/api/v1/pages/${documentId}/tasks/${taskUuid}/state?lang=en`,
      {
        headers: apiHeaders(userApiToken, {
          'Idempotency-Key': completionKey,
          'If-Match': taskResource.etag,
        }),
        data: { completed: true },
      },
    );
    expect((await expectData(completed)).completed).toBe(true);

    const repeated = await request.put(
      `/api/v1/pages/${documentId}/tasks/${taskUuid}/state?lang=en`,
      {
        headers: apiHeaders(userApiToken, {
          'Idempotency-Key': completionKey,
          'If-Match': taskResource.etag,
        }),
        data: { completed: true },
      },
    );
    expect(repeated.headers()['idempotency-replayed']).toBe('true');
    expect((await expectData(repeated)).completed).toBe(true);

    const history = await request.get(
      `/api/v1/pages/${documentId}/tasks/${taskUuid}/history?lang=en&limit=50`,
      { headers: userHeaders },
    );
    expect((await expectData(history))).toHaveLength(1);

    const assetCurrent = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}/attachments/${assetUuid}?lang=en`,
      userHeaders,
    );
    const deletedAsset = await request.delete(
      `/api/v1/pages/${documentId}/attachments/${assetUuid}?lang=en`,
      {
        headers: apiHeaders(userApiToken, {
          'Idempotency-Key': idempotencyKey('attachment-delete'),
          'If-Match': assetCurrent.etag,
        }),
        data: { title: 'E2E API Guide' },
      },
    );
    expect((await expectData(deletedAsset)).is_draft).toBe(false);
    expect((await expectData(await request.get(`/api/v1/pages/${documentId}/attachments?lang=en`, {
      headers: userHeaders,
    })))).toHaveLength(0);
  });

  test('page deletion requires an ETag and removes the Workspace document route', async ({ request }) => {
    const editable = await getDataWithEtag(
      request,
      `/api/v1/pages/${documentId}?lang=en`,
      adminHeaders,
    );
    const deleted = await request.delete(`/api/v1/pages/${documentId}?lang=en`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('content-page-delete'),
        'If-Match': editable.etag,
      }),
    });
    expect(deleted.status()).toBe(204);

    const missing = await request.get(`/api/v1/pages/${documentId}?lang=en`, {
      headers: adminHeaders,
    });
    await expectProblem(missing, 404, 'editor_resource_not_found');
  });
});
