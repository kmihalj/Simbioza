import { expect, test } from '@playwright/test';
import { execFileSync, spawn } from 'node:child_process';
import { once } from 'node:events';
import { mkdtemp, readdir, rm, writeFile } from 'node:fs/promises';
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
const sourceCalendarName = 'E2E Source Calendar';

/**
 * EN: Uses only synthetic importer fixtures from the disposable runner application.
 *
 * HR: Koristi samo sintetičke primjere importera iz potrošne aplikacije pokretača.
 */
async function confluenceSiteArchive(extraRows = 0) {
  const directory = await mkdtemp(join(tmpdir(), 'simbioza-site-workflow-e2e-'));
  const archive = join(directory, 'tiny-site.zip');
  const project = process.env.HPH_E2E_PROJECT;
  if (!project) throw new Error('Run through scripts/run_e2e.php.');
  execFileSync('php', ['-r', `
require $argv[1] . '/vendor/autoload.php';
require $argv[1] . '/vendor/aaieduhr/simbioza-module-confluence-import/tests/Support/TeamCalendarsFixture.php';
$fixture = AaiEduHr\\SimbiozaModuleConfluenceImport\\Tests\\Support\\TeamCalendarsFixture::class;
$tables = $fixture::tables();
$tables['SUBCALS'][0]['NAME'] = 'alice - official calendar';
$tables['SUBCALS'][] = array_replace($tables['SUBCALS'][0], ['ID' => 'calendar-two', 'NAME' => 'Shared services']);
for ($i = 1; $i <= (int)$argv[3]; ++$i) {
    $tables['SUBCALS'][] = array_replace($tables['SUBCALS'][0], ['ID' => 'pagination-calendar-' . $i, 'NAME' => sprintf('Pagination calendar %03d', $i)]);
}
$fixture::archive($argv[2], $tables);
$z = new ZipArchive(); $z->open($argv[2]);
$xml = $z->getFromName('entities.xml');
$body = '<p>Unified site page.</p><ac:structured-macro ac:name="calendar"><ac:parameter ac:name="id">calendar-one,missing-calendar,calendar-two,calendar-one</ac:parameter><ac:parameter ac:name="defaultView">week</ac:parameter></ac:structured-macro>';
$page = '<object class="Page"><id>100</id><property name="space"><id>1</id></property><property name="title">Unified Home</property><property name="version">1</property><property name="creator"><id>alice-key</id></property></object>'
    . '<object class="BodyContent"><id>body100</id><property name="content"><id>100</id></property><property name="body">' . htmlspecialchars($body, ENT_XML1) . '</property></object>';
for ($i = 1; $i <= (int)$argv[3]; ++$i) {
    $page .= '<object class="Space"><id>' . (1000 + $i) . '</id><property name="key">PAGINATION' . $i . '</property><property name="name">' . sprintf('Pagination space %03d', $i) . '</property></object>';
}
$z->addFromString('entities.xml', str_replace('</hibernate-generic>', $page . '</hibernate-generic>', $xml)); $z->close();
`, project, archive, String(extraRows)]);
  return { directory, archive };
}

/**
 * EN: Two synthetic duplicate accounts own separate personal spaces with a cross-link and attachment.
 * Both sources must survive one explicit additive merge and a repeated import without duplication.
 *
 * HR: Dva sintetička dupla računa imaju zasebna osobna područja s poveznicom i privitkom.
 * Oba izvora moraju preživjeti izričito spajanje dodavanjem i ponavljanje bez dupliciranja.
 */
async function duplicatePersonalSiteArchive(archival = false) {
  const directory = await mkdtemp(join(tmpdir(), 'simbioza-duplicate-personal-e2e-'));
  const archive = join(directory, 'duplicate-personal-site.zip');
  const entities = join(directory, 'entities.xml');
  await writeFile(entities, `<hibernate-generic>
    <object class="InternalUser"><id>901</id><property name="name">duplicate-old</property><property name="emailAddress">duplicate@example.invalid</property>${archival ? '<property name="active">false</property>' : ''}</object>
    <object class="InternalUser"><id>902</id><property name="name">duplicate-current</property><property name="emailAddress">duplicate@example.invalid</property>${archival ? '<property name="active">false</property>' : ''}</object>
    <object class="ConfluenceUserImpl"><id>duplicate-old-key</id><property name="name">duplicate-old</property></object>
    <object class="ConfluenceUserImpl"><id>duplicate-current-key</id><property name="name">duplicate-current</property></object>
    <object class="Space"><id>11</id><property name="key">~duplicate-old</property><property name="name">Old Personal</property><property name="spaceType">personal</property><property name="creator"><id>duplicate-old-key</id></property><property name="homePage"><id>111</id></property></object>
    <object class="Space"><id>12</id><property name="key">~duplicate-current</property><property name="name">Current Personal</property><property name="spaceType">personal</property><property name="creator"><id>duplicate-current-key</id></property><property name="homePage"><id>112</id></property></object>
    <object class="Page"><id>111</id><property name="space"><id>11</id></property><property name="title">Old Personal Page</property><property name="version">1</property><property name="creator"><id>duplicate-old-key</id></property><property name="lastModifier"><id>duplicate-old-key</id></property></object>
    <object class="Page"><id>112</id><property name="space"><id>12</id></property><property name="title">Current Personal Page</property><property name="version">1</property><property name="creator"><id>duplicate-current-key</id></property><property name="lastModifier"><id>duplicate-current-key</id></property></object>
    <object class="BodyContent"><id>body111</id><property name="content"><id>111</id></property><property name="body">&lt;p&gt;Old source body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:page ri:space-key="~duplicate-current" ri:content-title="Current Personal Page" ri:content-id="112" /&gt;&lt;ac:plain-text-link-body&gt;Other personal source&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;</property></object>
    <object class="BodyContent"><id>body112</id><property name="content"><id>112</id></property><property name="body">&lt;p&gt;Current source body.&lt;/p&gt;&lt;ac:link&gt;&lt;ri:attachment ri:filename="merged.bin" /&gt;&lt;ac:plain-text-link-body&gt;Merged attachment&lt;/ac:plain-text-link-body&gt;&lt;/ac:link&gt;</property></object>
    <object class="Attachment"><id>700</id><property name="containerContent"><id>112</id></property><property name="space"><id>12</id></property><property name="title">merged.bin</property><property name="version">1</property></object>
  </hibernate-generic>`);
  execFileSync('php', ['-r', '$z=new ZipArchive();$z->open($argv[1],ZipArchive::CREATE);$z->addFile($argv[2],"entities.xml");$z->addFromString("exportDescriptor.properties","exportType=site\\nbackupAttachments=true\\n");$z->addFromString("attachments/112/700/1","merged private attachment");$z->close();', archive, entities]);
  return { directory, archive };
}

/**
 * EN: Holds the real preparation lock in the disposable app to exercise busy/reload/progress UI paths.
 * Test-controlled phase transitions avoid artificial delays and never touch HFC's archives or database.
 *
 * HR: Drži stvarno zaključavanje pripreme u potrošnoj aplikaciji za provjeru čekanja/osvježavanja/napretka.
 * Testom upravljane promjene faza ne trebaju umjetne pauze i ne diraju HFC arhive ili bazu.
 */
async function controlledCalendarPreparation() {
  const project = process.env.HPH_E2E_PROJECT;
  const directory = join(project, 'data', 'confluence-import', 'uploads');
  const archives = (await readdir(directory)).filter((name) => name.endsWith('.zip'));
  expect(archives).toHaveLength(1);
  const worker = spawn('php', ['-r', `
require $argv[1] . '/vendor/autoload.php';
$path = $argv[2]; $hash = hash_file('sha256', $path);
$lock = fopen($path . '.calendars.lock', 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit(2);
$progress = new AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceCalendarPreparation($path, $hash);
$progress->update('scan', 120);
echo "ready\\n"; fflush(STDOUT);
if (trim((string)fgets(STDIN)) !== 'partition') exit(3);
$progress->update('partition', 3, 10);
echo "partition\\n"; fflush(STDOUT);
if (trim((string)fgets(STDIN)) !== 'finish') exit(4);
fclose($lock);
$config = new AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceImportConfig(new HeartPhrame\\Config\\Config(new HeartPhrame\\Helper\\Helper(), [], $argv[1]), $argv[1] . '/vendor/aaieduhr/simbioza-module-confluence-import');
$reader = new AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceTeamCalendarReader(new AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceArchive($config));
(new AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceSiteCalendarIndex($reader, $config))->prepare($path, $hash);
`, project, join(directory, archives[0])]);
  const [ready] = await once(worker.stdout, 'data');
  expect(String(ready)).toContain('ready');
  return worker;
}

/**
 * EN: Creates a small real Confluence XML ZIP with hierarchy, an internal
 *     link, and a private attachment without committing a binary fixture.
 *
 * HR: Izrađuje mali stvarni Confluence XML ZIP s hijerarhijom, internom
 *     poveznicom i privatnim privitkom bez spremanja binarnog fixturea u Git.
 */
async function confluenceArchive(calendarMonth) {
  const directory = await mkdtemp(join(tmpdir(), 'simbioza-confluence-e2e-'));
  const descriptor = join(directory, 'exportDescriptor.properties');
  const entities = join(directory, 'entities.xml');
  const attachment = join(directory, 'sample.bin');
  const historicalAttachment = join(directory, 'historical-sample.bin');
  const calendar = join(directory, 'source-calendar.ics');
  const archive = join(directory, 'tiny-space.xml.zip');

  // EN: Two known HTML macros check header associations after the complete import.
  //
  // HR: Dva poznata HTML makroa provjeravaju veze zaglavlja nakon cijelog uvoza.
  const tableMacro = '<ac:structured-macro ac:name="html"><ac:plain-text-body><![CDATA['
    + '<table><caption>Imported accessible table</caption><tr><th id="year" scope="col">2026</th>'
    + '<th id="month" headers="year" scope="col">September</th></tr>'
    + '<tr><td headers="year month">Imported cell</td><td>Other cell</td></tr></table>'
    + ']]></ac:plain-text-body></ac:structured-macro>';
  // EN: Empty date elements must survive the complete import and editor sanitization.
  //
  // HR: Prazni elementi datuma moraju preživjeti cijeli import i čišćenje editora.
  const dateStorage = '<table><caption>Imported dates</caption><tbody><tr>'
    + '<td><time datetime="2023-05-10" /></td><td><time datetime="2025-09-30" /></td>'
    + '</tr></tbody></table><p><time datetime="2023-05-10">10 May 2023</time></p>';
  const tableMacrosXml = (tableMacro + tableMacro + dateStorage).replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;').replaceAll('>', '&gt;');

  await writeFile(descriptor, [
    'exportType=space',
    'spaceKey=TINY',
    'spaceKeys=TINY',
    'createdByVersionNumber=10.2.11',
    'backupAttachments=true',
    '',
  ].join('\n'));
  await writeFile(attachment, 'private attachment body');
  await writeFile(historicalAttachment, 'historical private attachment body');
  await writeFile(calendar, [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Simbioza E2E//Confluence Calendar//EN',
    `X-WR-CALNAME:${sourceCalendarName}`,
    'BEGIN:VEVENT',
    'UID:confluence-calendar-e2e-event@example.invalid',
    `DTSTAMP:${calendarMonth}01T060000Z`,
    `DTSTART:${calendarMonth}15T080000Z`,
    `DTEND:${calendarMonth}15T090000Z`,
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
  <object class="CustomContentEntityObject" package="com.atlassian.confluence.content"><id name="id">301</id><property name="title">${sourceCalendarName}</property><property name="pluginModuleKey">com.atlassian.confluence.extra.team-calendars:calendar-content-type</property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p301</id><property name="content"><id>301</id></property><property name="name">subCalendarId</property><property name="stringValue">${sourceCalendarUuid}</property></object>
  <object class="Attachment" package="com.atlassian.confluence.pages"><id name="id">201</id><property name="containerContent"><id>101</id></property><property name="space"><id>1</id></property><property name="title">sample.bin</property><property name="version">2</property><property name="contentStatus">current</property></object>
  <object class="Attachment" package="com.atlassian.confluence.pages"><id name="id">202</id><property name="containerContent"><id>101</id></property><property name="space"><id>1</id></property><property name="title">sample.bin</property><property name="version">1</property><property name="contentStatus">current</property><property name="originalVersion"><id>201</id></property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p201a</id><property name="content"><id>201</id></property><property name="name">MEDIA_TYPE</property><property name="stringValue">application/octet-stream</property></object>
  <object class="ContentProperty" package="com.atlassian.confluence.core"><id name="id">p201b</id><property name="content"><id>201</id></property><property name="name">FILESIZE</property><property name="longValue">23</property></object>
</hibernate-generic>`.replace('&lt;p&gt;Imported home body.', `${tableMacrosXml}&lt;p&gt;Imported home body.`));

  execFileSync('php', [
    '-r',
    '$z=new ZipArchive();$z->open($argv[1],ZipArchive::CREATE|ZipArchive::OVERWRITE);$z->addFile($argv[2],"entities.xml");$z->addFile($argv[3],"exportDescriptor.properties");$z->addFile($argv[4],"attachments/101/201/2");$z->addFile($argv[5],"attachments/101/201/1");if(!$z->close()){exit(1);}',
    archive,
    entities,
    descriptor,
    attachment,
    historicalAttachment,
  ]);

  return { archive, calendar, directory };
}

/**
 * EN: Confirms a completed import retained no private ZIP or staging copies.
 *
 * HR: Potvrđuje da dovršeni import nije ostavio privatni ZIP ni staging kopije.
 */
async function expectImportStagingEmpty() {
  const project = process.env.HPH_E2E_PROJECT;
  expect(project).toBeTruthy();
  for (const directory of ['uploads', 'staging', 'attachments']) {
    await expect.poll(async () => (
      await readdir(join(project, 'data', 'confluence-import', directory)).catch((error) => {
        if (error.code === 'ENOENT') return [];
        throw error;
      })
    ).filter((name) => !/^[0-9a-f-]{36}\.(?:site|workflow)\.lock$/i.test(name)).length).toBe(0);
  }
}

test('site settings import together once and retain separate user calendar and space reports', async ({ page }) => {
  test.setTimeout(120_000);
  const fixture = await confluenceSiteArchive();
  const slug = `e2e-site-workflow-${Date.now()}`;
  const consoleErrors = [];
  let preparationWorker;
  page.on('pageerror', (error) => consoleErrors.push(error.message));
  const saveAndReload = async (locator) => {
    await Promise.all([page.waitForEvent('load'), locator.click()]);
  };
  try {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/confluence-import');
    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([
      page.waitForURL((url) => url.searchParams.has('job')),
      page.locator('#confluence-import-upload').click(),
    ]);
    await saveAndReload(page.locator('#confluence-site-access-prepare'));
    await page.locator('#site-source-url').fill(`https://${slug}.test`);
    await Promise.all([
      page.waitForEvent('load'),
      page.locator('#confluence-site-access [name="create_unmapped_users"]').check(),
    ]);
    await expect(page.locator('#site-source-url')).toHaveValue(`https://${slug}.test`);
    await page.locator('#confluence-site-selection [name="space_ids[]"][value="1"]').check();
    await saveAndReload(page.locator('#confluence-site-selection [type="submit"]'));
    await page.locator('[data-site-space="1"] [data-space-name]').fill('Unified E2E');
    await page.locator('[data-site-space="1"] [data-space-slug]').fill(slug);
    preparationWorker = await controlledCalendarPreparation();
    await page.locator('[data-site-calendar-action="prepare"]').click();
    await expect(page.locator('#site-calendar-preparation-phase')).toHaveText('Reading calendars from the archive…');
    await expect(page.locator('[data-site-calendar-action="prepare"]')).toBeDisabled();
    await expect(page.locator('#site-calendar-preparation-bar')).not.toHaveAttribute('aria-valuenow');
    await expect(page.locator('#site-calendar-preparation-detail')).toContainText('120');
    // EN: Reload reconnects to the same worker; no second click or import is required.
    //
    // HR: Osvježavanje se povezuje s istim postupkom; nije potreban drugi klik ili uvoz.
    await page.reload();
    await expect(page.locator('[data-site-calendar-action="prepare"]')).toBeDisabled();
    await expect(page.locator('#site-calendar-preparation-phase')).toHaveText('Reading calendars from the archive…');
    preparationWorker.stdin.write('partition\n');
    await expect(page.locator('#site-calendar-preparation-bar')).toHaveAttribute('aria-valuenow', '30');
    await expect(page.locator('#site-calendar-preparation-detail')).toContainText('3 / 10 (30 %)');
    await expect(page.locator('#site-calendar-preparation-bar')).toBeInViewport();
    await page.screenshot({ path: test.info().outputPath('calendar-preparation-progress.png') });
    const preparationExit = once(preparationWorker, 'exit');
    await Promise.all([
      page.waitForEvent('load'),
      Promise.resolve(preparationWorker.stdin.end('finish\n')),
    ]);
    const [preparationCode] = await preparationExit;
    expect(preparationCode).toBe(0);
    await expect(page.locator('#confluence-site-calendars')).toBeVisible();
    // EN: Catalog preparation must not discard unsaved content configuration.
    //
    // HR: Priprema pregleda ne smije odbaciti nespremljene postavke sadržaja.
    await expect(page.locator('[data-site-space="1"] [data-space-slug]')).toHaveValue(slug);
    const row = page.locator('[data-calendar-row]').filter({ hasText: 'alice - official calendar' });
    // EN: A proposal is not an accepted owner or a selected import. Search excludes hidden picker options.
    //
    // HR: Prijedlog nije prihvaćeni vlasnik ni odabir uvoza. Pretraga ne uključuje skrivene opcije odabira.
    await expect(row.locator('[data-calendar-owner]')).toHaveValue('');
    await page.locator('#site-calendar-filter').fill('alice');
    await expect(page.locator('[data-calendar-row]:not(.d-none)')).toHaveCount(1);
    await page.locator('#site-calendar-filter').fill('');
    await row.getByRole('button', { name: 'Accept suggested owner', exact: true }).click();
    await expect(row.locator('[data-calendar-owner]')).toHaveValue('internal-user:1');
    await expect(row.locator('[data-calendar-type]')).toHaveValue('personal');
    await expect(row.locator('[name="calendar_ids[]"]')).not.toBeChecked();
    const localOwnerRow = page.locator('[data-calendar-row]').filter({ hasText: 'Shared services' });
    const ownerPicker = localOwnerRow.locator('[data-identity-picker]');
    await ownerPicker.locator('[data-identity-picker-toggle]').click();
    await ownerPicker.locator('[data-identity-picker-search]').fill(adminLogin);
    const ownerResult = ownerPicker.locator('[data-identity-picker-results] button').filter({ hasText: adminLogin });
    await expect(ownerResult).toHaveCount(1);
    const localOwnerRef = await ownerResult.getAttribute('data-identity-picker-choice');
    expect(localOwnerRef).toMatch(/^local-user:[1-9][0-9]*$/);
    await ownerResult.click();
    await expect(localOwnerRow.locator('[data-calendar-owner]')).toHaveValue(localOwnerRef);
    await expect(ownerPicker.locator('[data-identity-picker-toggle]')).toContainText(adminLogin);
    await expect(localOwnerRow.locator('[data-calendar-type]')).toHaveValue('personal');
    // EN: Import both a selected source proposal and a separate explicit existing local owner.
    //
    // HR: Uvezi odabrani izvorni prijedlog i zasebnog izričito odabranog postojećeg lokalnog vlasnika.
    await localOwnerRow.locator('[name="calendar_ids[]"]').check();
    await row.locator('[name="calendar_ids[]"]').check();
    await saveAndReload(page.locator('#confluence-site-calendars [type="submit"]'));
    await expect(page.locator('[data-site-space="1"] [data-space-slug]')).toHaveValue(slug);
    await saveAndReload(page.locator('#site-workflow-review'));
    await expect(page.locator('#site-workflow-start')).toBeEnabled();
    expect((await page.request.get(`/workspace/${slug}/unified-home`)).status()).toBe(404);
    expect(await page.locator('#confluence-site-access-apply').count()).toBe(0);
    page.once('dialog', (dialog) => dialog.accept());
    await saveAndReload(page.locator('#site-workflow-start'));
    await expect(page.locator('#site-workflow')).toContainText('The combined site import is complete.', { timeout: 60_000 });
    await expect(page.getByRole('progressbar', { name: 'Current step progress' })).toBeVisible();
    await expect(page.getByRole('progressbar', { name: 'Current step progress' })).toHaveAttribute('aria-valuenow', '100');
    await expect(page.locator('#site-workflow-percent')).toHaveText('100%');
    const overview = page.getByRole('table').filter({ hasText: 'Site users, groups and memberships' });
    await expect(overview.locator('tbody tr')).toHaveCount(3);
    await expect(overview.getByRole('link', { name: 'Open import report' })).toHaveCount(3);
    page.once('dialog', (dialog) => dialog.accept());
    await saveAndReload(page.locator('#site-source-cleanup'));
    // EN: Wait for the cleanup reload, not a previous completion-page navigation.
    //
    // HR: Čeka reload čišćenja, ne prethodnu navigaciju završnog ekrana.
    await expect(page.locator('#site-source-cleanup')).toHaveCount(0);
    await expect(page.getByRole('table').filter({ hasText: 'Site users, groups and memberships' }).locator('tbody tr')).toHaveCount(3);
    // EN: Each standalone site report stays on a theme-owned content surface, not over the hero.
    //
    // HR: Svaki zasebni izvještaj sitea ostaje na sadržajnoj površini teme, ne preko zaglavlja.
    const completedUrl = page.url();
    const reportLinks = await page.getByRole('table').filter({ hasText: 'Site users, groups and memberships' })
      .getByRole('link', { name: 'Open import report' }).evaluateAll((links) => links.map((link) => link.getAttribute('href')));
    for (const [index, kind] of [[0, 'identities'], [1, 'calendars']]) {
      await page.goto(reportLinks[index]);
      await expect(page.locator(`main [data-confluence-site-report="${kind}"] > .card-body`)).toBeVisible();
    }
    const siteJob = new URL(completedUrl).searchParams.get('job');
    await page.goto(`/settings/confluence-import/report/${siteJob}`);
    await expect(page.locator('main [data-confluence-site-report="overview"] > .card-body')).toBeVisible();
    await expect(page.locator('[data-confluence-site-report="overview"] tbody tr')).toHaveCount(3);
    await expectImportStagingEmpty();
    await page.goto(`/workspace/${slug}/unified-home?lang=en`);
    await expect(page.locator('.calendar-embed-shell')).toHaveCount(1);
    await expect(page.locator('.calendar-embed-shell')).toBeVisible();
    await expect(page.getByText('Confluence calendar: missing-calendar', { exact: true })).toBeVisible();
    // EN: One native block retains both resolved source selections and its source week view.
    // A deleted source reference stays a separate review item; it does not discard its neighbors.
    //
    // HR: Jedan nativni blok čuva oba razriješena izvorna odabira i izvorni tjedni prikaz.
    // Obrisana izvorna referenca ostaje zasebna stavka pregleda; ne odbacuje susjedne kalendare.
    const embedState = execFileSync('php', ['-r', `
require $argv[1] . '/bootstrap.php'; require $argv[1] . '/vendor/autoload.php';
$app = new HeartPhrame\\App($argv[1] . '/config', $argv[1]); $app->loadCommands();
$db = $app->getContainer()->get(AaiEduHr\\HeartPhrameModuleOrm\\Database\\Database::class);
$job = $db->table('simbioza_confluence_import_jobs')->where('source_space_key', '=', 'ONE')->where('operation', '=', 'site_space')->first();
$summary = json_decode($job['summary_json'], true);
$issues = $summary['review_pages'][0]['issues'];
echo json_encode(array_map(static fn($issue) => [$issue['source_calendar_id'], $issue['resolved'] ?? false, $issue['calendar_view']], $issues));
`, process.env.HPH_E2E_PROJECT], { encoding: 'utf8' });
    expect(JSON.parse(embedState)).toEqual([['calendar-one', true, 'week'], ['missing-calendar', false, 'week'], ['calendar-two', true, 'week']]);
    expect(consoleErrors).toEqual([]);
  } finally {
    if (preparationWorker && preparationWorker.exitCode === null) preparationWorker.kill();
    await rm(fixture.directory, { recursive: true, force: true });
  }
});

/**
 * EN: Real paged drafts retain off-page selections and edits; navigation never writes application content.
 *
 * HR: Stvarni straničeni nacrti čuvaju odabire i unose drugih stranica; navigacija ne upisuje sadržaj aplikacije.
 */
test('site space and calendar pagination retains filtered off-page draft settings', async ({ page, browser }) => {
  test.setTimeout(180_000);
  const fixture = await confluenceSiteArchive(130);
  const saveAndReload = async (locator) => Promise.all([page.waitForEvent('load'), locator.click()]);
  try {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/confluence-import');
    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([page.waitForURL((url) => url.searchParams.has('job')), page.locator('#confluence-import-upload').click()]);
    await saveAndReload(page.locator('#confluence-site-access-prepare'));
    await page.locator('#site-source-url').fill('https://pagination-source.test');
    const spaces = page.locator('#confluence-site-selection');
    const topSpacePager = spaces.locator('[data-site-table-pagination]').first();
    await expect(spaces.locator('[data-filter-row]:not(.d-none)')).toHaveCount(25);
    await expect(topSpacePager.locator('[data-pager-count]')).toHaveText('Showing 1–25 of 131.');
    await expect(topSpacePager.locator('[data-pager-pages]')).toContainText('…');
    await spaces.locator('#confluence-site-select-visible').click();
    await expect(spaces.locator('[name="space_ids[]"]:checked')).toHaveCount(25);
    await topSpacePager.getByRole('button', { name: 'Next page', exact: true }).click();
    await expect(topSpacePager.locator('[data-pager-count]')).toHaveText('Showing 26–50 of 131.');
    await expect(spaces.locator('[name="space_ids[]"]:checked')).toHaveCount(25);
    await spaces.locator('#confluence-site-filter').fill('Pagination space 130');
    await expect(spaces.locator('[data-filter-row]:not(.d-none)')).toHaveCount(1);
    await spaces.locator('#confluence-site-select-visible').click();
    await spaces.locator('#confluence-site-filter').fill('no matching source');
    await expect(topSpacePager.locator('[data-pager-count]')).toHaveText('Showing 0–0 of 0.');
    await expect(spaces.locator('[data-pager-empty]')).toBeVisible();
    await spaces.locator('#confluence-site-filter').fill('');
    await spaces.locator('#confluence-site-select-filtered').click();
    await expect(spaces.locator('[name="space_ids[]"]:checked')).toHaveCount(131);
    await saveAndReload(spaces.locator('[type="submit"]'));
    const content = page.locator('#confluence-site-content');
    await expect(content.locator('[data-site-space]:not(.d-none)')).toHaveCount(25);
    await content.locator('#site-content-filter').fill('PAGINATION130');
    await content.locator('[data-site-space="1130"] [data-space-name]').fill('Changed off-page name');
    await content.locator('#site-content-filter').fill('');
    await content.locator('[data-site-table-pagination]').first().getByRole('button', { name: 'Last page', exact: true }).click();
    await content.locator('[data-site-table-pagination]').last().getByRole('button', { name: 'First page', exact: true }).click();
    await content.locator('#site-content-filter').fill('PAGINATION130');
    await expect(content.locator('[data-site-space="1130"] [data-space-name]')).toHaveValue('Changed off-page name');
    // EN: Native validation reveals an invalid hidden row instead of losing it or submitting a partial draft.
    //
    // HR: Nativna provjera prikazuje neispravan skriveni redak umjesto gubitka ili slanja djelomičnog nacrta.
    await content.locator('[data-site-space="1130"] [data-space-name]').fill('');
    await content.locator('#site-content-filter').fill('Unified Home has no matching space');
    await expect(content.locator('[data-site-space]:not(.d-none)')).toHaveCount(0);
    await content.locator('[type="submit"]').click();
    await expect(content.locator('#site-content-filter')).toHaveValue('');
    await expect(content.locator('[data-site-space="1130"] [data-space-name]')).toBeVisible();
    await content.locator('[data-site-space="1130"] [data-space-name]').fill('Changed off-page name');
    await saveAndReload(page.locator('[data-site-calendar-action="prepare"]'));
    await expect(page.locator('#confluence-site-calendars')).toBeVisible();
    const calendars = page.locator('#confluence-site-calendars');
    // EN: Read-only proposals are owner-only, separately paged, and never change the saved draft.
    //
    // HR: Prijedlozi bez upisa dostupni su vlasniku, zasebno straničeni i ne mijenjaju spremljeni nacrt.
    const draftUrl = page.url();
    await page.locator('#site-calendars').getByRole('link', { name: 'Review duplicate account and calendar proposals' }).click();
    const reviewUrl = page.url();
    await expect(page.locator('[data-site-mapping-review]')).toBeVisible();
    await expect(page.locator('#review-calendars [data-review-row]')).toHaveCount(132);
    await expect(page.locator('#review-calendars [data-review-row]:not(.d-none)')).toHaveCount(25);
    await expect(page.locator('[data-site-mapping-review] form')).toHaveCount(0);
    const guest = await browser.newContext();
    try {
      const guestPage = await guest.newPage();
      await guestPage.goto(reviewUrl);
      await expect(guestPage.locator('[data-site-mapping-review]')).toHaveCount(0);
    } finally {
      await guest.close();
    }
    const reviewSearch = page.locator('#review-calendars [data-review-search]');
    await reviewSearch.fill('Pagination calendar 130');
    await expect(page.locator('#review-calendars [data-review-row]:not(.d-none)')).toHaveCount(1);
    await page.locator('#review-calendars [data-review-row]:not(.d-none)').getByRole('link', { name: 'Edit choice' }).click();
    await expect(calendars.locator('[data-calendar-row]:not(.d-none)')).toHaveCount(1);
    await expect(calendars.locator('[data-calendar-row]:not(.d-none)')).toContainText('Pagination calendar 130');
    await page.goto(draftUrl);
    const topCalendarPager = calendars.locator('[data-site-table-pagination]').first();
    await expect(calendars.locator('[data-calendar-row]:not(.d-none)')).toHaveCount(25);
    await calendars.locator('#site-calendar-select-visible').click();
    await expect(calendars.locator('[name="calendar_ids[]"]:checked')).toHaveCount(25);
    await topCalendarPager.getByRole('button', { name: 'Last page', exact: true }).click();
    await expect(topCalendarPager.locator('[data-pager-count]')).toHaveText('Showing 126–132 of 132.');
    await calendars.locator('#site-calendar-filter').fill('alice');
    const ownerRow = calendars.locator('[data-calendar-row]:not(.d-none)');
    await expect(ownerRow).toHaveCount(1);
    await ownerRow.locator('[data-calendar-type]').selectOption('team');
    await calendars.locator('#site-calendar-filter').fill('');
    await topCalendarPager.locator('[data-pager-size]').selectOption('50');
    await expect(calendars.locator('[data-site-table-pagination]').last().locator('[data-pager-size]')).toHaveValue('50');
    await expect(calendars.locator('[data-calendar-row]:not(.d-none)')).toHaveCount(50);
    await calendars.locator('#site-calendar-select-filtered').click();
    await expect(calendars.locator('[name="calendar_ids[]"]:checked')).toHaveCount(132);
    await saveAndReload(calendars.locator('[type="submit"]'));
    await expect(calendars.locator('[name="calendar_ids[]"]:checked')).toHaveCount(132);
    await calendars.locator('#site-calendar-filter').fill('alice');
    await expect(calendars.locator('[data-calendar-row]:not(.d-none) [data-calendar-type]')).toHaveValue('team');
    await content.locator('#site-content-filter').fill('PAGINATION130');
    await expect(content.locator('[data-site-space="1130"] [data-space-name]')).toHaveValue('Changed off-page name');
    await page.screenshot({ path: test.info().outputPath('site-table-pagination.png') });
    page.once('dialog', (dialog) => dialog.accept());
    await saveAndReload(spaces.locator('[data-cancel-job]'));
    await expectImportStagingEmpty();
  } finally {
    await rm(fixture.directory, { recursive: true, force: true });
  }
});

test('duplicate source accounts merge personal spaces additively and repeat without duplication', async ({ page }) => {
  test.setTimeout(180_000);
  const fixture = await duplicatePersonalSiteArchive();
  const sourceUrl = `https://duplicate-personal-${Date.now()}.test`;
  const saveAndReload = async (locator) => Promise.all([page.waitForEvent('load'), locator.click()]);
  const inspect = () => JSON.parse(execFileSync('php', ['-r', `
require $argv[1] . '/bootstrap.php'; require $argv[1] . '/vendor/autoload.php';
$app = new HeartPhrame\\App($argv[1] . '/config', $argv[1]); $app->loadCommands(); $c = $app->getContainer();
$db = $c->get(AaiEduHr\\HeartPhrameModuleOrm\\Database\\Database::class);
$repo = $c->get(AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceImportRepository::class);
$key = fn($id) => AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceSiteAccessService::mappingKey($argv[2], 'users', $id);
$owners = [$repo->mappedUserId($key('internal-user:901')), $repo->mappedUserId($key('internal-user:902'))];
$personal = $c->get(AaiEduHr\\SimbiozaModuleUser\\Service\\PersonalWorkspaceService::class)->forUser($owners[0]);
$workspace = $personal['workspace'];
$maps = $repo->withSiteContext($argv[2], [], fn() => $repo->spacesBySourceIds(['11', '12']));
$nodes = $db->table('workspace_nodes')->where('workspace_id', '=', $workspace['id'])->orderBy('id')->get();
$keys = array_values(array_column(array_filter($nodes, fn($n) => in_array($n['title'], ['Old Personal Page', 'Current Personal Page'], true)), 'document_key'));
$documents = $db->table('editor_html_documents')->whereIn('document_key', $keys)->orderBy('id')->get();
$user = $db->table('auth_users')->where('id', '=', $owners[0])->first();
echo json_encode(['owners' => $owners, 'targets' => array_values(array_column($maps, 'target_workspace_id')),
  'workspace_hash' => hash('sha256', json_encode($workspace)), 'nodes_hash' => hash('sha256', json_encode($nodes)),
  'documents_hash' => hash('sha256', json_encode($documents)),
  'authors' => array_map(fn($d) => [(int)$d['created_by_user_id'], (int)$d['updated_by_user_id']], $documents),
  'nodes' => array_map(fn($n) => array_intersect_key($n, array_flip(['id', 'title', 'slug', 'created_by_user_id', 'updated_by_user_id'])), $nodes),
  'inactive' => !(bool)$user['is_active'], 'admin' => (bool)$user['is_admin'], 'password_empty' => $user['password_hash'] === null,
  'slug' => $workspace['slug']], JSON_THROW_ON_ERROR);
`, process.env.HPH_E2E_PROJECT, sourceUrl], { encoding: 'utf8' }));
  try {
    await login(page, adminLogin, adminPassword);
    let first;
    for (const repeat of [false, true]) {
      await page.goto('/settings/confluence-import');
      await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
      await Promise.all([page.waitForURL((url) => url.searchParams.has('job')), page.locator('#confluence-import-upload').click()]);
      await saveAndReload(page.locator('#confluence-site-access-prepare'));
      await page.locator('#site-source-url').fill(sourceUrl);
      await saveAndReload(page.locator('#confluence-site-access [type="submit"]'));
      if (!repeat) {
        const primary = page.locator('[data-site-kind="users"][data-source-id="internal-user:902"]').locator('..');
        await primary.locator('[data-identity-picker-toggle]').click();
        await primary.locator('[data-identity-picker-choice="__create__"]').click();
        await saveAndReload(page.locator('#confluence-site-access [type="submit"]'));
        const alias = page.locator('[data-site-kind="users"][data-source-id="internal-user:901"]').locator('..');
        await alias.locator('[data-identity-picker-toggle]').click();
        await alias.locator('[data-identity-picker-search]').fill('duplicate-current');
        await alias.locator('[data-identity-picker-choice="__source__:internal-user:902"]').click();
        await expect(alias.locator('[data-identity-picker-toggle]')).toContainText('Same user as source account');
        await saveAndReload(page.locator('#confluence-site-access [type="submit"]'));
      }
      await page.locator('#confluence-site-select-visible').click();
      await saveAndReload(page.locator('#confluence-site-selection [type="submit"]'));
      await expect(page.locator('[data-personal-space-groups]')).toContainText('~duplicate-old');
      await page.locator('[data-personal-space-append]').click();
      await expect(page.locator('[data-site-space="11"] [data-space-strategy]')).toHaveValue('append');
      await expect(page.locator('[data-site-space="12"] [data-space-strategy]')).toHaveValue('append');
      await saveAndReload(page.locator('#confluence-site-content [type="submit"]'));
      await saveAndReload(page.locator('#site-workflow-review'));
      await expect(page.locator('#site-workflow-start')).toBeEnabled();
      page.once('dialog', (dialog) => dialog.accept());
      await page.locator('#site-workflow-start').click();
      await expect(page.locator('#site-workflow')).toContainText('The combined site import is complete.', { timeout: 60_000 });
      const state = inspect();
      expect(state.owners[0]).toBe(state.owners[1]);
      expect(new Set(state.targets).size).toBe(1);
      expect(state.targets).toHaveLength(2);
      expect(state.inactive).toBe(true);
      expect(state.admin).toBe(false);
      expect(state.password_empty).toBe(true);
      const importedNodes = state.nodes.filter((node) => ['Old Personal Page', 'Current Personal Page'].includes(node.title));
      expect(importedNodes).toHaveLength(2);
      expect(state.authors).toEqual([[state.owners[0], state.owners[0]], [state.owners[0], state.owners[0]]]);
      if (repeat) expect(state).toEqual(first);
      else first = state;
      const workflowUrl = page.url();
      await page.goto(`/workspace/${state.slug}/old-personal-page?lang=en`);
      await expect(page.getByRole('link', { name: 'Other personal source', exact: true })).toHaveAttribute('href', `/workspace/${state.slug}/current-personal-page`);
      await page.goto(`/workspace/${state.slug}/current-personal-page?lang=en`);
      const href = await page.getByRole('link', { name: 'Merged attachment', exact: true }).getAttribute('href');
      const attachment = await page.request.get(href);
      expect(attachment.status()).toBe(200);
      expect(await attachment.text()).toBe('merged private attachment');
      await page.goto(workflowUrl);
      page.once('dialog', (dialog) => dialog.accept());
      await saveAndReload(page.locator('#site-source-cleanup'));
      await expect(page.locator('#site-source-cleanup')).toHaveCount(0);
    }
  } finally {
    await rm(fixture.directory, { recursive: true, force: true });
  }
});

/**
 * EN: Explicit custody keeps former owners' sources separate and does not manufacture administrator identities.
 * This runs only against the disposable runner application, never HFC's database or source archive.
 *
 * HR: Izričito skrbništvo čuva zasebne izvore bivših vlasnika i ne stvara mapiranja identiteta na administratora.
 * Izvršava se samo u potrošnoj aplikaciji pokretača, nikada nad HFC bazom ni izvornom arhivom.
 */
test('inactive personal owners can use separate archival workspaces without identity or membership fallback', async ({ page }) => {
  test.setTimeout(120_000);
  const fixture = await duplicatePersonalSiteArchive(true);
  const sourceUrl = `https://archival-custody-${Date.now()}.test`;
  const saveAndReload = async (locator) => Promise.all([page.waitForEvent('load'), locator.click()]);
  try {
    await login(page, adminLogin, adminPassword);
    await page.goto('/settings/confluence-import');
    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([page.waitForURL((url) => url.searchParams.has('job')), page.locator('#confluence-import-upload').click()]);
    await saveAndReload(page.locator('#confluence-site-access-prepare'));
    await page.locator('#site-source-url').fill(sourceUrl);
    await saveAndReload(page.locator('#confluence-site-access [name="auto_map_users"]'));
    await Promise.all([page.waitForEvent('load'), page.locator('#confluence-site-access [name="create_unmapped_users"]').check()]);
    await expect(page.locator('#confluence-site-access [name="create_archival_users"]')).not.toBeChecked();
    await expect(page.locator('[data-source-activity="account_disabled"]')).toHaveCount(2);
    for (const id of ['11', '12']) await page.locator(`#confluence-site-selection [name="space_ids[]"][value="${id}"]`).check();
    await saveAndReload(page.locator('#confluence-site-selection [type="submit"]'));
    for (const id of ['11', '12']) {
      await page.locator(`[data-site-space="${id}"] [data-space-owner-mode]`).selectOption('custody');
    }
    await saveAndReload(page.locator('#site-workflow-review'));
    await expect(page.locator('#site-workflow-start')).toBeEnabled();
    page.once('dialog', (dialog) => dialog.accept());
    await saveAndReload(page.locator('#site-workflow-start'));
    await expect(page.locator('#site-workflow')).toContainText('The combined site import is complete.', { timeout: 60_000 });
    const state = JSON.parse(execFileSync('php', ['-r', `
require $argv[1] . '/bootstrap.php'; require $argv[1] . '/vendor/autoload.php';
$app = new HeartPhrame\\App($argv[1] . '/config', $argv[1]); $app->loadCommands(); $c = $app->getContainer();
$repo = $c->get(AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceImportRepository::class);
$db = $c->get(AaiEduHr\\HeartPhrameModuleOrm\\Database\\Database::class);
$maps = $repo->withSiteContext($argv[2], [], fn() => $repo->spacesBySourceIds(['11', '12']));
$targets = array_values(array_column($maps, 'target_workspace_id'));
$workspaces = $db->table('workspaces')->whereIn('id', $targets)->get();
$identities = array_map(fn($id) => $repo->mappedUserId(AaiEduHr\\SimbiozaModuleConfluenceImport\\Service\\ConfluenceSiteAccessService::mappingKey($argv[2], 'users', $id)), ['internal-user:901', 'internal-user:902']);
echo json_encode(['targets' => $targets, 'identities' => $identities,
  'restricted' => array_column($workspaces, 'visibility'), 'descriptions' => array_column($workspaces, 'description')]);
`, process.env.HPH_E2E_PROJECT, sourceUrl], { encoding: 'utf8' }));
    expect(new Set(state.targets).size).toBe(2);
    expect(state.identities).toEqual([null, null]);
    expect(state.restricted).toEqual(['restricted', 'restricted']);
    for (const description of state.descriptions) expect(description).toContain('duplicate-');
    const reports = page.getByRole('table').filter({ hasText: 'Site users, groups and memberships' });
    await expect(reports.locator('tbody tr')).toHaveCount(3);
    page.once('dialog', (dialog) => dialog.accept());
    await saveAndReload(page.locator('#site-source-cleanup'));
    await expectImportStagingEmpty();
  } finally {
    await rm(fixture.directory, { recursive: true, force: true });
  }
});

test('administrator imports a Confluence space while ACL and private files remain enforced', async ({ browser, page }) => {
  test.setTimeout(120_000);
  // EN: The event must fall in the browser's displayed month, not a fixed September.
  //
  // HR: Događaj mora biti u mjesecu koji preglednik prikazuje, ne u fiksnom rujnu.
  const calendarMonth = await page.evaluate(() => {
    const now = new Date();
    return `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}`;
  });
  const fixture = await confluenceArchive(calendarMonth);
  const suffix = Date.now();
  const workspaceSlug = `e2e-confluence-${suffix}`;

  try {
    await login(page, adminLogin, adminPassword);
    const response = await page.goto('/settings/confluence-import');
    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText(/Internal Server Error|Fatal error/i);

    // EN: The localized native picker has an unambiguous name and visible focus.
    //
    // HR: Lokalizirani nativni birač ima jedinstven naziv i vidljiv fokus.
    const fileInput = page.locator('#confluence-import-file');
    await expect(fileInput).toHaveAccessibleName('Confluence XML ZIP archive Choose file');
    await fileInput.focus();
    expect(await page.locator('#confluence-import-file-choice').evaluate((label) => (
      getComputedStyle(label).outlineStyle
    ))).toBe('solid');

    await page.locator('#confluence-import-file').setInputFiles(fixture.archive);
    await Promise.all([
      page.waitForURL((url) => url.pathname === '/settings/confluence-import' && url.searchParams.has('job')),
      page.locator('#confluence-import-upload').click(),
    ]);
    const jobUuid = new URL(page.url()).searchParams.get('job');
    expect(jobUuid).toBeTruthy();

    await expect(page.locator('#confluence-import-workspace-name')).toHaveValue('Tiny Confluence Space');
    await expect(page.locator('#confluence-import-workspace-slug')).toHaveValue('TINY');
    await page.locator('#confluence-import-source-base-url').fill('https://wiki.example.org');
    await page.locator('#confluence-import-workspace-name').fill(`E2E Confluence ${suffix}`);
    await page.locator('#confluence-import-workspace-slug').fill(workspaceSlug);

    await page.locator('details.confluence-import-mapping').first().locator('summary').click();
    const identity = page.locator('[data-identity-map="u1"]');
    const identityPicker = identity.locator('xpath=..');
    const identityToggle = identityPicker.locator('[data-identity-picker-toggle]');
    await expect(identityToggle).toHaveAccessibleName(/Target user|Ciljni korisnik/);
    await identityPicker.locator('[data-identity-picker-toggle]').click();
    await identityPicker.locator('[data-identity-picker-search]').fill('E2E');
    const adminResult = identityPicker.locator('[data-identity-picker-results] button').filter({
      hasText: 'Administrator E2E',
    });
    await expect(adminResult).toBeVisible();
    await adminResult.focus();
    await adminResult.press('Enter');
    await expect(identityToggle).toBeFocused();
    await expect(identityToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(identity).not.toHaveValue('');
    await identityToggle.press('Enter');
    await expect(identityPicker.getByRole('searchbox')).toBeFocused();
    await identityPicker.getByRole('searchbox').press('Escape');
    await expect(identityToggle).toBeFocused();
    await expect(identityPicker.locator('[data-identity-picker-panel]')).toBeHidden();

    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#confluence-import-run').click();
    await expect(page.locator('#confluence-import-result')).toBeVisible({ timeout: 60_000 });
    await expect(page.locator('#confluence-import-result')).toContainText('"pages_imported": 2');
    await expect(page.locator('#confluence-import-result')).toContainText('Current attachments: 1 / 1');
    await page.locator('#confluence-import-result details summary').click();
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_imported": 1');
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_failed": 0');
    await expectImportStagingEmpty();

    await page.goto(`/settings/confluence-import/report/${jobUuid}`);
    const homeReport = page.locator('article').filter({ hasText: 'Imported Home' });
    const homeCalendarIssue = homeReport.locator('[data-confluence-calendar-issue]');
    await expect(homeCalendarIssue).toHaveCount(1);
    const calendarImportForm = homeCalendarIssue.locator('form').filter({
      has: page.locator('input[name="resolution_mode"][value="import"]'),
    });
    await expect(calendarImportForm.locator('input[name="calendar_name"]')).toHaveCount(0);
    await expect(calendarImportForm).toContainText(
      'The calendar name is read from the ICS file; if it is missing, the Confluence name is used.',
    );
    await calendarImportForm.locator('input[name="ics_file"]').setInputFiles(fixture.calendar);
    await calendarImportForm.locator('select[name="calendar_type"]').selectOption('team');
    await calendarImportForm.locator('input[name="is_authenticated_read"][type="checkbox"]').check();
    await calendarImportForm.getByRole('button', { name: 'Import and display on the page' }).click();
    await expect(page.getByRole('status').filter({
      hasText: `The page now displays the calendar “${sourceCalendarName}”.`,
    })).toBeVisible();
    await expect(homeCalendarIssue.getByText('Resolved')).toBeVisible();

    await page.goto(`/workspace/${workspaceSlug}/imported-home?lang=en`);
    await expect(page.locator('body')).toContainText('Imported home body.');
    const importedTables = page.getByRole('table', { name: 'Imported accessible table', exact: true });
    await expect(importedTables).toHaveCount(2);
    const headerIds = [];
    for (const table of await importedTables.all()) {
      const year = table.getByRole('columnheader', { name: '2026', exact: true });
      const month = table.getByRole('columnheader', { name: 'September', exact: true });
      const yearId = await year.getAttribute('id');
      const monthId = await month.getAttribute('id');
      expect(yearId).toMatch(/^import-table-[a-f0-9]{24}-1$/);
      expect(monthId).toMatch(/^import-table-[a-f0-9]{24}-2$/);
      await expect(month).toHaveAttribute('headers', yearId);
      await expect(table.getByRole('cell', { name: 'Imported cell', exact: true }))
        .toHaveAttribute('headers', `${yearId} ${monthId}`);
      headerIds.push(yearId, monthId);
    }
    expect(new Set(headerIds).size).toBe(4);
    const importedDates = page.getByRole('table', { name: 'Imported dates', exact: true });
    await expect(importedDates.getByRole('cell', { name: '2023-05-10', exact: true })).toBeVisible();
    await expect(importedDates.getByRole('cell', { name: '2025-09-30', exact: true })).toBeVisible();
    await expect(page.locator('article')).toContainText('10 May 2023');
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
    await reportTask.focus();
    const [stateSaved] = await Promise.all([stateResponse, reportTask.press('Space')]);
    expect(stateSaved.status()).toBe(200);
    await expect(reportTask).toBeChecked();
    await expect(reportTask).toBeFocused();
    // EN: Failed saves restore state and retain focus; the message is explicitly dismissed.
    //
    // HR: Neuspjelo spremanje vraća stanje i zadržava fokus; poruka se ručno zatvara.
    await page.route('**/tasks/state', (route) => route.fulfill({
      status: 500, contentType: 'application/json', body: JSON.stringify({ ok: false, error: 'E2E task failure' }),
    }));
    await reportTask.press('Space');
    await expect(page.getByRole('alert')).toContainText('E2E task failure');
    await expect(reportTask).toBeChecked();
    await expect(reportTask).toBeFocused();
    // EN: The real error notification must stay above the visible widget.
    //
    // HR: Stvarna poruka pogreške mora ostati iznad vidljivog widgeta.
    await expect(page.locator('[data-hph-a11y-open]')).toBeVisible();
    await expect.poll(() => page.locator('.editor-task-toast').evaluate((toast) => {
      const launcher = document.querySelector('[data-hph-a11y-open]');
      const gap = launcher.getBoundingClientRect().top - toast.getBoundingClientRect().bottom;
      const rem = parseFloat(getComputedStyle(document.documentElement).fontSize);
      return gap - rem * .75;
    })).toBeGreaterThanOrEqual(-1);
    await page.locator('.editor-task-toast-close').focus();
    await page.locator('.editor-task-toast-close').press('Enter');
    await expect(reportTask).toBeFocused();
    await page.unroute('**/tasks/state');
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
    const calendarOption = existingCalendarForm.locator('option').filter({ hasText: sourceCalendarName });
    await expect(calendarOption).toContainText('Team calendar');
    await expect(calendarOption).toContainText('authenticated read');
    await expect(existingCalendarForm).toContainText(
      'An existing calendar with the same name was found and selected. Verify it before linking.',
    );
    await expect(existingCalendarForm.locator('select[name="calendar_uuid"]')).toHaveValue(
      await calendarOption.getAttribute('value'),
    );
    await existingCalendarForm.getByRole('button', { name: 'Link selected calendar' }).click();
    await expect(page.getByRole('status').filter({
      hasText: `The page now displays the calendar “${sourceCalendarName}”.`,
    })).toBeVisible();

    await page.goto(`/workspace/${workspaceSlug}/${shortenedChildSlug}?lang=en`);
    await expect(page.locator('.calendar-embed-shell')).toHaveCount(1);
    await expect(page.locator('.calendar-embed-shell')).toContainText('Imported Confluence calendar event');

    // EN: The imported attachment uses the native HTML Editor attachment contract;
    //     the test must not depend on the Confluence importer's internal route.
    //
    // HR: Importirani privitak koristi javni ugovor nativnog HTML Editor privitka;
    //     test ne smije ovisiti o internoj ruti Confluence importera.
    const attachmentLink = page.getByRole('link', { name: 'Download sample' });
    await expect(attachmentLink).toBeVisible();
    const originalAttachmentHref = await attachmentLink.getAttribute('href');
    const attachmentResponse = await page.request.get(originalAttachmentHref);
    expect(attachmentResponse.status()).toBe(200);
    expect(attachmentResponse.headers()['content-disposition']).toContain('attachment;');
    expect((await attachmentResponse.body()).toString()).toBe('private attachment body');

    // EN: Replacement import preserves the public Workspace slug, page slugs,
    //     and attachment UUID and offers the existing calendar for relinking.
    //
    // HR: Zamjenski uvoz zadržava javni slug područja, slugove stranica i UUID
    //     privitka te ponovno nudi povezivanje već uvezenog kalendara.
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
    await page.locator('#confluence-import-source-base-url').fill('https://wiki.example.org');
    await page.locator('input[name="include_history"]').check();
    page.once('dialog', (dialog) => dialog.accept());
    await page.locator('#confluence-import-run').click();
    await expect(page.locator('#confluence-import-result')).toBeVisible({ timeout: 60_000 });
    await expect(page.locator('#confluence-import-result')).toContainText('Attachment history: 1 / 1');
    await page.locator('#confluence-import-result details summary').click();
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_imported": 2');
    await expect(page.locator('#confluence-import-result')).toContainText('"attachments_failed": 0');
    await expectImportStagingEmpty();

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
      hasText: sourceCalendarName,
    });
    await expect(replacementCalendarForm).toContainText(
      'An existing calendar with the same name was found and selected. Verify it before linking.',
    );
    await expect(replacementCalendarForm.locator('select[name="calendar_uuid"]')).toHaveValue(
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
