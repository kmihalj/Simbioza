import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  createEditorSurface,
  e2eEnvironment,
  expectData,
  idempotencyKey,
  login,
} from './helpers.js';

const {
  adminApiToken,
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
} = e2eEnvironment();

test('two users can recover a rejected concurrent edit without overwriting the shared draft', async ({
  browser,
  request,
}) => {
  test.setTimeout(120_000);

  const path = await createEditorSurface(request, adminApiToken, 'concurrent-edit');
  const documentId = new URL(`http://localhost${path}`).searchParams.get('document');
  expect(documentId).toBeTruthy();
  const workspaceSlug = documentId.replace('-page-', '-workspace-');
  const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
    headers: apiHeaders(adminApiToken),
  }));
  const regularUserId = users.find((user) => user.login_identifier === userLogin)?.id;
  expect(regularUserId).toBeTruthy();
  await expectData(await request.put(`/api/v1/workspaces/${workspaceSlug}/acl`, {
    headers: apiHeaders(adminApiToken, {
      'Idempotency-Key': idempotencyKey('concurrent-edit-acl'),
    }),
    data: {
      subjects: [{
        type: 'user',
        id: regularUserId,
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

  // HR: Odvojeni preglednički konteksti daju stvarne odvojene sesije i lokalne nacrte.
  // EN: Separate browser contexts provide real separate sessions and local drafts.
  const adminContext = await browser.newContext();
  const editorContext = await browser.newContext();
  try {
    const admin = await adminContext.newPage();
    const editor = await editorContext.newPage();
    await login(admin, adminLogin, adminPassword);
    await login(editor, userLogin, userPassword);
    await admin.goto(path);
    await editor.goto(path);

    const adminSurface = admin.locator('[data-editor-html-surface]');
    const editorSurface = editor.locator('[data-editor-html-surface]');
    await expect(adminSurface).toBeVisible();
    await expect(editorSurface).toBeVisible();
    const adminRevision = await admin.locator('input[name="draft_revision"]').inputValue();
    const editorRevision = await editor.locator('input[name="draft_revision"]').inputValue();
    expect(editorRevision).toBe(adminRevision);

    await adminSurface.fill('First editor saved this shared draft.');
    await Promise.all([
      admin.waitForURL((url) => url.pathname === '/editor-html' && url.searchParams.get('saved') === '1'),
      admin.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);

    await editorSurface.fill('Second editor must keep these rejected changes.');
    await Promise.all([
      editor.waitForURL((url) => url.pathname === '/editor-html'
        && url.searchParams.get('conflict') === '1'),
      editor.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await expect(editorSurface).toContainText('First editor saved this shared draft.');
    await expect(editor.locator('[data-editor-html-draft-notice]')).toBeVisible();
    await expect(editor.locator('[data-editor-html-local-copy]'))
      .toHaveValue(/Second editor must keep these rejected changes/);
    await expect(editor.locator('[data-editor-html-server-copy]'))
      .toHaveValue(/First editor saved this shared draft/);

    await editor.getByRole('button', { name: 'Restore draft' }).click();
    await expect(editorSurface).toContainText('Second editor must keep these rejected changes.');
    await Promise.all([
      editor.waitForURL((url) => url.pathname === '/editor-html' && url.searchParams.get('saved') === '1'),
      editor.getByRole('button', { name: 'Save', exact: true }).click(),
    ]);
    await admin.reload();
    await expect(adminSurface).toContainText('Second editor must keep these rejected changes.');
  } finally {
    await adminContext.close();
    await editorContext.close();
  }
});
