import { expect, test } from '@playwright/test';
import { randomBytes } from 'node:crypto';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  expectProblem,
  getDataWithEtag,
  idempotencyKey,
} from './helpers.js';

const { adminApiToken, userApiToken, userLogin } = e2eEnvironment();
const collection = '/api/v1/markdown-pages';
const admin = apiHeaders(adminApiToken);
const user = apiHeaders(userApiToken);
const mutation = (token, action, etag) => apiHeaders(token, {
  'Idempotency-Key': idempotencyKey(action),
  ...(etag ? { 'If-Match': etag } : {}),
});

test('Markdown API preserves source, revision concurrency, translations and attachments', async ({ request }) => {
  const openApi = await request.get('/api/v1/openapi.json', { headers: admin });
  expect(openApi.ok()).toBe(true);
  const contract = await openApi.json();
  expect(contract.paths[collection]?.post).toBeTruthy();
  expect(JSON.stringify(contract['x-heartphrame-scope-groups'])).toContain('markdown-page:publish');

  const source = '## Markdown release\n\nA **native** page.\n';
  const response = await request.post(collection, {
    headers: mutation(adminApiToken, 'md-create'),
    data: { title: 'Markdown release', language: 'en', markdown: source },
  });
  const created = await expectData(response, 201);
  const page = collection + '/' + encodeURIComponent(created.id);
  expect(created).toMatchObject({ format: 'md', markdown: source, published: true });
  expect(created.rendered_html).toContain('<strong>native</strong>');
  expect(response.headers().location).toContain('/api/v1/markdown-pages/');
  await expectProblem(await request.get(page, { headers: user }), 404, 'not_found');
  expect((await expectData(await request.get(collection, { headers: admin })))
    .some(document => document.id === created.id)).toBe(true);

  const first = await getDataWithEtag(request, page + '/draft', admin);
  const updated = await expectData(await request.patch(page, {
    headers: mutation(adminApiToken, 'md-update', first.etag),
    data: { markdown: 'An updated **revision**.\n' },
  }));
  expect(updated.version).toBeGreaterThan(first.data.version);
  await expectProblem(await request.patch(page, {
    headers: mutation(adminApiToken, 'md-stale', first.etag),
    data: { markdown: 'Stale update' },
  }), 412, 'resource_changed');

  const next = await getDataWithEtag(request, page + '/draft', admin);
  const translation = await expectData(await request.post(page + '/translations', {
    headers: mutation(adminApiToken, 'md-translation', next.etag),
    data: { source_language: 'en', target_language: 'hr' },
  }));
  expect(translation).toMatchObject({ language: 'hr', markdown: next.data.markdown });

  const attachment = await expectData(await request.post(page + '/attachments', {
    headers: admin,
    multipart: {
      attachment: { name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('Markdown attachment\n') },
    },
  }));
  expect(attachment).toMatchObject({ complete: true, name: 'notes.txt', mime_type: 'text/plain' });
  const uploadId = randomBytes(16).toString('hex');
  const chunk = Buffer.alloc(1024 * 1024, 'm');
  const tail = Buffer.from('end\n');
  const uploadPart = (buffer, offset) => request.post(page + '/attachments/chunks', {
    headers: admin,
    multipart: {
      attachment: { name: 'part.txt', mimeType: 'text/plain', buffer },
      upload_id: uploadId,
      file_name: 'chunked.txt',
      file_size: String(chunk.length + tail.length),
      offset: String(offset),
    },
  });
  expect(await expectData(await uploadPart(chunk, 0))).toEqual({ complete: false });
  expect(await expectData(await uploadPart(tail, chunk.length))).toMatchObject({ id: uploadId, complete: true });
  const latest = await getDataWithEtag(request, page + '/draft', admin);
  await expectData(await request.patch(page, {
    headers: mutation(adminApiToken, 'md-save-attachment', latest.etag),
    data: { markdown: '[Notes](attachments/' + attachment.id + ')\n' },
  }));
  const asset = page + '/attachments/' + attachment.id;
  const bytes = await request.get(asset + '/content', { headers: admin });
  expect(bytes.ok()).toBe(true);
  expect(await bytes.text()).toBe('Markdown attachment\n');
  await expectProblem(await request.get(asset + '/content', { headers: user }), 404, 'not_found');

  const history = await expectData(await request.get(page + '/versions', { headers: admin }));
  expect(history.some(revision => revision.version === first.data.version)).toBe(true);
  const old = await expectData(await request.get(page + '/versions/' + first.data.version, { headers: admin }));
  expect(old.markdown).toBe(source);
  const latestWithAsset = await getDataWithEtag(request, page + '/draft', admin);
  const restored = await expectData(await request.post(page + '/versions/' + first.data.version + '/restore', {
    headers: mutation(adminApiToken, 'md-restore', latestWithAsset.etag),
  }));
  expect(restored.markdown).toBe(source);
  expect(restored.version).toBeGreaterThan(latestWithAsset.data.version);
});

test('Markdown Workspace drafts require independent publication permission', async ({ request }) => {
  const members = await expectData(await request.get('/api/v1/users?page[limit]=100', { headers: admin }));
  const userId = members.find(member => member.login_identifier === userLogin)?.id;
  expect(userId).toBeTruthy();
  const slug = 'md-e2e-' + Date.now();
  await expectData(await request.post('/api/v1/workspaces', {
    headers: mutation(adminApiToken, 'md-workspace'),
    data: { name: 'Markdown API Workspace', slug, visibility: 'restricted' },
  }), 201);
  await expectData(await request.put('/api/v1/workspaces/' + slug + '/acl', {
    headers: mutation(adminApiToken, 'md-workspace-acl'),
    data: {
      subjects: [{
        type: 'user',
        id: userId,
        permissions: { can_view: true, can_add: true, can_edit: true, can_publish: false },
      }],
    },
  }));

  const draft = await expectData(await request.post(collection, {
    headers: mutation(userApiToken, 'md-workspace-create'),
    data: { title: 'Review Markdown', workspace_slug: slug, node_slug: 'review', language: 'en', markdown: '## Review\n' },
  }), 201);
  expect(draft.published).toBe(false);
  const page = collection + '/' + encodeURIComponent(draft.id);
  await expectProblem(await request.get(page, { headers: user }), 404, 'not_found');
  const working = await getDataWithEtag(request, page + '/draft', user);
  await expectData(await request.post(page + '/review', {
    headers: mutation(userApiToken, 'md-workspace-review', working.etag),
  }));
  await expectProblem(await request.post(page + '/publish', {
    headers: mutation(userApiToken, 'md-workspace-publish-denied', working.etag),
  }), 403, 'forbidden');

  const approved = await getDataWithEtag(request, page + '/draft', admin);
  await expectData(await request.post(page + '/publish', {
    headers: mutation(adminApiToken, 'md-workspace-publish', approved.etag),
  }));
  const published = await expectData(await request.get(page, { headers: user }));
  expect(published).toMatchObject({ published: true, markdown: '## Review\n' });
});
