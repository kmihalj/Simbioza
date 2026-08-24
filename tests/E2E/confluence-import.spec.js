import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { e2eEnvironment, login } from './helpers.js';

const {
  adminLogin,
  adminPassword,
  userLogin,
  userPassword,
} = e2eEnvironment();

/**
 * HR: Izrađuje mali stvarni Confluence XML ZIP s hijerarhijom, internom
 *     poveznicom i privatnim privitkom bez spremanja binarnog fixturea u Git.
 * EN: Creates a small real Confluence XML ZIP with hierarchy, an internal
 *     link, and a private attachment without committing a binary fixture.
 */
async function confluenceArchive() {
  const directory = await mkdtemp(join(tmpdir(), 'simbioza-confluence-e2e-'));
  const descriptor = join(directory, 'exportDescriptor.properties');
  const entities = join(directory, 'entities.xml');
  const attachment = join(directory, 'sample.bin');
  const archive = join(directory, 'tiny-space.xml.zip');

  await writeFile(descriptor, [
    'exportType=space',
    'spaceKey=TINY',
    'spaceKeys=TINY',
    'createdByVersionNumber=10.2.11',
    'backupAttachments=true',
    '',
  ].join('\n'));
  await writeFile(attachment, 'private attachment body');
  await writeFile(entities, `<?xml version="1.0" encoding="UTF-8"?>
<hibernate-generic>
  <object class="Space" package="com.atlassian.confluence.spaces"><id name="id">1</id><property name="key">TINY</property><property name="name">Tiny Confluence Space</property><property name="spaceType">global</property><property name="homePage"><id>100</id></property><property name="creator"><id>u1</id></property></object>
  <object class="ConfluenceUserImpl" package="com.atlassian.confluence.user"><id name="id">u1</id><property name="username">e2e-admin</property><property name="displayName">E2E Administrator</property><property name="emailAddress">e2e-admin@example.invalid</property></object>
  <object class="Page" package="com.atlassian.confluence.pages"><id name="id">100</id><property name="space"><id>1</id></property><property name="title">Imported Home</property><property name="version">1</property><property name="contentStatus">current</property><property name="creator"><id>u1</id></property><property name="lastModifier"><id>u1</id></property></object>
  <object class="Page" package="com.atlassian.confluence.pages"><id name="id">101</id><property name="space"><id>1</id></property><property name="parent"><id>100</id></property><property name="title">Imported Child</property><property name="version">1</property><property name="contentStatus">current</property><property name="creator"><id>u1</id></property><property name="lastModifier"><id>u1</id></property></object>
  <object class="BodyContent" package="com.atlassian.confluence.core"><id name="id">b100</id><property name="content"><id>100</id></property><property name="body">&lt;p&gt;Imported home body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:page ri:content-id="101" ri:content-title="Imported Child"/&gt;&lt;ac:plain-text-link-body&gt;Open child&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;</property></object>
  <object class="BodyContent" package="com.atlassian.confluence.core"><id name="id">b101</id><property name="content"><id>101</id></property><property name="body">&lt;p&gt;Imported child body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:attachment ri:filename="sample.bin"/&gt;&lt;ac:plain-text-link-body&gt;Download sample&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;</property></object>
  <object class="Attachment" package="com.atlassian.confluence.pages"><id name="id">201</id><property name="containerContent"><id>101</id></property><property name="space"><id>1</id></property><property name="title">sample.bin</property><property name="version">1</property><property name="contentStatus">current</property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p201a</id><property name="content"><id>201</id></property><property name="name">MEDIA_TYPE</property><property name="stringValue">application/octet-stream</property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p201b</id><property name="content"><id>201</id></property><property name="name">FILESIZE</property><property name="longValue">23</property></object>
</hibernate-generic>`);

  execFileSync('php', [
    '-r',
    '$z=new ZipArchive();$z->open($argv[1],ZipArchive::CREATE|ZipArchive::OVERWRITE);$z->addFile($argv[2],"entities.xml");$z->addFile($argv[3],"exportDescriptor.properties");$z->addFile($argv[4],"attachments/101/201/1");if(!$z->close()){exit(1);}',
    archive,
    entities,
    descriptor,
    attachment,
  ]);

  return { archive, directory };
}

test('administrator imports a Confluence space while ACL and private files remain enforced', async ({ browser, page }) => {
  test.setTimeout(90_000);
  const fixture = await confluenceArchive();
  const suffix = Date.now();
  const workspaceSlug = `e2e-confluence-${suffix}`;

  try {
    await login(page, adminLogin, adminPassword);
    const response = await page.goto('/settings/confluence-import');
    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText(/Internal Server Error|Fatal error/i);

    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/confluence-import' && url.searchParams.has('job')),
      page.locator('#confluence-import-upload').click(),
    ]);

    await expect(page.locator('#confluence-import-workspace-name')).toHaveValue('Tiny Confluence Space');
    await expect(page.locator('#confluence-import-workspace-slug')).toHaveValue('TINY');
    await page.locator('#confluence-import-workspace-name').fill(`E2E Confluence ${suffix}`);
    await page.locator('#confluence-import-workspace-slug').fill(workspaceSlug);

    await page.locator('details.confluence-import-mapping').first().locator('summary').click();
    const identity = page.locator('[data-identity-map="u1"]');
    const adminOption = identity.locator('option').filter({ hasText: 'E2E Administrator' });
    await identity.selectOption(await adminOption.getAttribute('value'));

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#confluence-import-run').click();
    await expect(page.locator('#confluence-import-result')).toBeVisible({ timeout: 60_000 });
    await expect(page.locator('#confluence-import-result')).toContainText('"pages_imported": 2');
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_imported": 1');

    await page.goto(`/workspace/${workspaceSlug}/imported-home?lang=en`);
    await expect(page.locator('body')).toContainText('Imported home body.');
    const childLink = page.locator(`a[href*="/workspace/${workspaceSlug}/imported-child"]`).first();
    await expect(childLink).toBeVisible();
    await childLink.click();
    await expect(page.locator('body')).toContainText('Imported child body.');

    // HR: Importirani privitak koristi javni ugovor nativnog HTML Editor privitka;
    //     test ne smije ovisiti o internoj ruti Confluence importera.
    // EN: The imported attachment uses the native HTML Editor attachment contract;
    //     the test must not depend on the Confluence importer's internal route.
    const attachmentLink = page.getByRole('link', { name: 'Download sample' });
    await expect(attachmentLink).toBeVisible();
    const attachmentResponse = await page.request.get(await attachmentLink.getAttribute('href'));
    expect(attachmentResponse.status()).toBe(200);
    expect(attachmentResponse.headers()['content-disposition']).toContain('attachment;');
    expect((await attachmentResponse.body()).toString()).toBe('private attachment body');

    const regularContext = await browser.newContext();
    const regularPage = await regularContext.newPage();
    await login(regularPage, userLogin, userPassword);
    const denied = await regularPage.goto(`/workspace/${workspaceSlug}/imported-home?lang=en`);
    expect([403, 404]).toContain(denied?.status());
    await regularContext.close();
  } finally {
    await rm(fixture.directory, { recursive: true, force: true });
  }
});
