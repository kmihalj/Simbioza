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

const longChildTitle = '2024-09-19 Sastanak urednika repozitorija koji pohranjuju multimedijalne objekte na temu revidiranja metapodatkovnih opisa multimedijskih objekata';
const shortenedChildSlug = '2024-09-19-sastanak-urednika-repozitorija-koji-pohranjuju-multimedijalne-objekte-na-temu-revidiranja-metapodatkovnih-opisa';
const sourceCalendarUuid = '3c1a6576-55e6-4776-9296-b95a00f980b7';

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
  const calendar = join(directory, 'source-calendar.ics');
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
  await writeFile(calendar, [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Simbioza E2E//Confluence Calendar//EN',
    'X-WR-CALNAME:E2E Source Calendar',
    'BEGIN:VEVENT',
    'UID:confluence-calendar-e2e-event@example.invalid',
    'DTSTAMP:20260904T060000Z',
    'DTSTART:20260907T080000Z',
    'DTEND:20260907T090000Z',
    'SUMMARY:Imported Confluence calendar event',
    'END:VEVENT',
    'END:VCALENDAR',
    '',
  ].join('\r\n'));
  await writeFile(entities, `<?xml version="1.0" encoding="UTF-8"?>
<hibernate-generic>
  <object class="Space" package="com.atlassian.confluence.spaces"><id name="id">1</id><property name="key">TINY</property><property name="name">Tiny Confluence Space</property><property name="spaceType">global</property><property name="homePage"><id>100</id></property><property name="creator"><id>u1</id></property></object>
  <object class="ConfluenceUserImpl" package="com.atlassian.confluence.user"><id name="id">u1</id><property name="username">e2e-admin</property><property name="displayName">E2E Administrator</property><property name="emailAddress">e2e-admin@example.invalid</property></object>
  <object class="Page" package="com.atlassian.confluence.pages"><id name="id">100</id><property name="space"><id>1</id></property><property name="title">Imported Home</property><property name="version">1</property><property name="contentStatus">current</property><property name="creator"><id>u1</id></property><property name="lastModifier"><id>u1</id></property></object>
  <object class="Page" package="com.atlassian.confluence.pages"><id name="id">101</id><property name="space"><id>1</id></property><property name="parent"><id>100</id></property><property name="title">${longChildTitle}</property><property name="version">1</property><property name="contentStatus">current</property><property name="creator"><id>u1</id></property><property name="lastModifier"><id>u1</id></property></object>
  <object class="BodyContent" package="com.atlassian.confluence.core"><id name="id">b100</id><property name="content"><id>100</id></property><property name="body">&lt;p&gt;Imported home body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:page ri:content-id="101" ri:content-title="${longChildTitle}"/&gt;&lt;ac:plain-text-link-body&gt;Open child&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;&lt;ac:structured-macro ac:name="tasks-report-macro"&gt;&lt;ac:parameter ac:name="status"&gt;incomplete&lt;/ac:parameter&gt;&lt;/ac:structured-macro&gt;&lt;ac:structured-macro ac:name="calendar" ac:macro-id="calendar-home"&gt;&lt;ac:parameter ac:name="id"&gt;${sourceCalendarUuid}&lt;/ac:parameter&gt;&lt;/ac:structured-macro&gt;&lt;ac:structured-macro ac:name="livesearch"&gt;&lt;ac:parameter ac:name="spaceKey"&gt;&lt;ri:space ri:space-key="TINY" /&gt;&lt;/ac:parameter&gt;&lt;/ac:structured-macro&gt;</property></object>
  <object class="BodyContent" package="com.atlassian.confluence.core"><id name="id">b101</id><property name="content"><id>101</id></property><property name="body">&lt;p&gt;Imported child body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:attachment ri:filename="sample.bin"/&gt;&lt;ac:plain-text-link-body&gt;Download sample&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;&lt;ac:task-list&gt;&lt;ac:task&gt;&lt;ac:task-id&gt;14&lt;/ac:task-id&gt;&lt;ac:task-status&gt;incomplete&lt;/ac:task-status&gt;&lt;ac:task-body&gt;Review https://example.test/task&lt;/ac:task-body&gt;&lt;/ac:task&gt;&lt;/ac:task-list&gt;&lt;ac:structured-macro ac:name="calendar" ac:macro-id="calendar-child"&gt;&lt;ac:parameter ac:name="id"&gt;${sourceCalendarUuid}&lt;/ac:parameter&gt;&lt;/ac:structured-macro&gt;</property></object>
  <object class="CustomContentEntityObject" package="com.atlassian.confluence.content"><id name="id">301</id><property name="title">Confluence source calendar</property><property name="pluginModuleKey">com.atlassian.confluence.extra.team-calendars:calendar-content-type</property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p301</id><property name="content"><id>301</id></property><property name="name">subCalendarId</property><property name="stringValue">${sourceCalendarUuid}</property></object>
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

  return { archive, calendar, directory };
}

test('administrator imports a Confluence space while ACL and private files remain enforced', async ({ browser, page }) => {
  test.setTimeout(120_000);
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
    const jobUuid = new URL(page.url()).searchParams.get('job');
    expect(jobUuid).toBeTruthy();

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

    const importedCalendarName = `E2E imported calendar ${suffix}`;
    await page.goto(`/settings/confluence-import/report/${jobUuid}`);
    const homeReport = page.locator('article').filter({ hasText: 'Imported Home' });
    const homeCalendarIssue = homeReport.locator('[data-confluence-calendar-issue]');
    await expect(homeCalendarIssue).toHaveCount(1);
    const calendarImportForm = homeCalendarIssue.locator('form').filter({
      has: page.locator('input[name="resolution_mode"][value="import"]'),
    });
    await calendarImportForm.locator('input[name="ics_file"]').setInputFiles(fixture.calendar);
    await calendarImportForm.locator('input[name="calendar_name"]').fill(importedCalendarName);
    await calendarImportForm.locator('select[name="calendar_type"]').selectOption('team');
    await calendarImportForm.locator('input[name="is_authenticated_read"][type="checkbox"]').check();
    await calendarImportForm.getByRole('button', { name: 'Import and display on the page' }).click();
    await expect(page.getByRole('status').filter({
      hasText: `The page now displays the calendar “${importedCalendarName}”.`,
    })).toBeVisible();
    await expect(homeCalendarIssue.getByText('Resolved')).toBeVisible();

    await page.goto(`/workspace/${workspaceSlug}/imported-home?lang=en`);
    await expect(page.locator('body')).toContainText('Imported home body.');
    const firstLevelTreeNode = page.locator(
      '#workspace-page-tree [data-workspace-tree-level="1"]',
    ).first();
    await expect(firstLevelTreeNode).toBeVisible();
    await expect(firstLevelTreeNode.locator(
      ':scope > .workspace-tree-row [data-workspace-tree-branch-toggle]',
    )).toHaveCount(0);
    await expect(firstLevelTreeNode.locator(':scope > .workspace-tree-branch')).toBeVisible();
    await expect(firstLevelTreeNode.getByRole('link', { name: longChildTitle })).toBeVisible();
    await expect(page.locator('.calendar-embed-shell')).toHaveCount(1);
    await expect(page.locator('.calendar-embed-shell')).toContainText('Imported Confluence calendar event');
    const importedSearch = page.locator('form[data-workspace-embedded-search="1"]');
    await expect(importedSearch).toHaveCount(1);
    await expect(importedSearch.locator('input[name="workspaces"]')).toHaveValue(workspaceSlug);
    const reportTask = page.locator('.editor-task-list[data-task-list-view="table"] .editor-task-checkbox');
    await expect(reportTask).toHaveCount(1);
    await expect(reportTask).toBeEnabled();
    await expect(reportTask).not.toBeChecked();
    const stateResponse = page.waitForResponse((response) => response.request().method() === 'POST'
      && new URL(response.url()).pathname === '/tasks/state');
    const [stateSaved] = await Promise.all([stateResponse, reportTask.check()]);
    expect(stateSaved.status()).toBe(200);
    await expect(reportTask).toBeChecked();
    await page.reload();
    await expect(reportTask).toBeChecked();
    const childLink = page.locator(`a[href*="/workspace/${workspaceSlug}/${shortenedChildSlug}"]`).first();
    await expect(childLink).toBeVisible();
    await childLink.click();
    await expect(page.locator('body')).toContainText('Imported child body.');
    await expect(page.locator('body')).toContainText(longChildTitle);
    const sourceTask = page.locator('.editor-task-list .editor-task-checkbox');
    await expect(sourceTask).toHaveCount(1);
    await expect(sourceTask).not.toBeChecked();
    await expect(page.getByRole('link', { name: 'https://example.test/task' })).toHaveAttribute(
      'href',
      'https://example.test/task',
    );

    await page.goto(`/settings/confluence-import/report/${jobUuid}`);
    const childReport = page.locator('article').filter({ hasText: longChildTitle });
    const childCalendarIssue = childReport.locator('[data-confluence-calendar-issue]');
    const existingCalendarForm = childCalendarIssue.locator('form').filter({
      has: page.locator('input[name="resolution_mode"][value="existing"]'),
    });
    const calendarOption = existingCalendarForm.locator('option').filter({ hasText: importedCalendarName });
    await expect(calendarOption).toContainText('Team calendar');
    await expect(calendarOption).toContainText('authenticated read');
    await existingCalendarForm.locator('select[name="calendar_uuid"]').selectOption(
      await calendarOption.getAttribute('value'),
    );
    await existingCalendarForm.getByRole('button', { name: 'Link selected calendar' }).click();
    await expect(page.getByRole('status').filter({
      hasText: `The page now displays the calendar “${importedCalendarName}”.`,
    })).toBeVisible();

    await page.goto(`/workspace/${workspaceSlug}/${shortenedChildSlug}?lang=en`);
    await expect(page.locator('.calendar-embed-shell')).toHaveCount(1);
    await expect(page.locator('.calendar-embed-shell')).toContainText('Imported Confluence calendar event');

    // HR: Importirani privitak koristi javni ugovor nativnog HTML Editor privitka;
    //     test ne smije ovisiti o internoj ruti Confluence importera.
    // EN: The imported attachment uses the native HTML Editor attachment contract;
    //     the test must not depend on the Confluence importer's internal route.
    const attachmentLink = page.getByRole('link', { name: 'Download sample' });
    await expect(attachmentLink).toBeVisible();
    const originalAttachmentHref = await attachmentLink.getAttribute('href');
    const attachmentResponse = await page.request.get(originalAttachmentHref);
    expect(attachmentResponse.status()).toBe(200);
    expect(attachmentResponse.headers()['content-disposition']).toContain('attachment;');
    expect((await attachmentResponse.body()).toString()).toBe('private attachment body');

    // HR: Zamjenski uvoz zadržava javni slug područja, slugove stranica i UUID
    //     privitka te ponovno nudi povezivanje već uvezenog kalendara.
    // EN: Replacement import preserves the public Workspace slug, page slugs,
    //     and attachment UUID and offers the existing calendar for relinking.
    await page.goto('/settings/confluence-import');
    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/confluence-import' && url.searchParams.has('job')),
      page.locator('#confluence-import-upload').click(),
    ]);
    const replacementJobUuid = new URL(page.url()).searchParams.get('job');
    expect(replacementJobUuid).toBeTruthy();
    await expect(page.locator('#confluence-import-workspace-name')).toHaveValue(`E2E Confluence ${suffix}`);
    await expect(page.locator('#confluence-import-workspace-slug')).toHaveValue(workspaceSlug);
    await expect(page.locator('input[name="reimport_strategy"][value="replace"]')).toBeChecked();
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#confluence-import-run').click();
    await expect(page.locator('#confluence-import-result')).toBeVisible({ timeout: 60_000 });
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_imported": 1');

    await page.goto(`/workspace/${workspaceSlug}/${shortenedChildSlug}?lang=en`);
    const replacementAttachmentLink = page.getByRole('link', { name: 'Download sample' });
    await expect(replacementAttachmentLink).toHaveAttribute('href', originalAttachmentHref);
    const replacementAttachmentResponse = await page.request.get(originalAttachmentHref);
    expect(replacementAttachmentResponse.status()).toBe(200);
    expect((await replacementAttachmentResponse.body()).toString()).toBe('private attachment body');

    await page.goto(`/settings/confluence-import/report/${replacementJobUuid}`);
    const replacementHomeReport = page.locator('article').filter({ hasText: 'Imported Home' });
    const replacementCalendarIssue = replacementHomeReport.locator('[data-confluence-calendar-issue]');
    const replacementCalendarForm = replacementCalendarIssue.locator('form').filter({
      has: page.locator('input[name="resolution_mode"][value="existing"]'),
    });
    const replacementCalendarOption = replacementCalendarForm.locator('option').filter({
      hasText: importedCalendarName,
    });
    await replacementCalendarForm.locator('select[name="calendar_uuid"]').selectOption(
      await replacementCalendarOption.getAttribute('value'),
    );
    await replacementCalendarForm.getByRole('button', { name: 'Link selected calendar' }).click();
    await page.goto(`/workspace/${workspaceSlug}/imported-home?lang=en`);
    await expect(page.locator('.calendar-embed-shell')).toContainText('Imported Confluence calendar event');

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
