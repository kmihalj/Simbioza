import { expect, test } from '@playwright/test';
import {
  apiHeaders,
  e2eEnvironment,
  expectData,
  expectProblem,
  getDataWithEtag,
  idempotencyKey,
} from './helpers.js';

const {
  adminApiToken,
  userApiToken,
  userLogin,
  adminLogin,
  adminPassword,
} = e2eEnvironment();
const adminHeaders = apiHeaders(adminApiToken);
const userHeaders = apiHeaders(userApiToken);

test.describe.serial('Calendar API, ACL, event, and ICS lifecycle', () => {
  let calendarUuid;
  let eventId;
  let ordinaryUserId;

  test('calendar visibility is concealed until an explicit ACL grants it', async ({ request }) => {
    const users = await expectData(await request.get('/api/v1/users?page[limit]=100', {
      headers: adminHeaders,
    }));
    ordinaryUserId = users.find((user) => user.login_identifier === userLogin)?.id;
    expect(ordinaryUserId).toBeTruthy();

    const created = await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('calendar-create') }),
      data: {
        name: 'E2E Release Calendar',
        description: 'Complete isolated Calendar API coverage.',
        type: 'team',
        color: '#1677ff',
        is_enabled: true,
        is_public_read: false,
        is_authenticated_read: false,
      },
    });
    const calendar = await expectData(created, 201);
    calendarUuid = calendar.uuid;
    expect(calendar.name).toBe('E2E Release Calendar');

    const concealed = await request.get(`/api/v1/calendars/${calendarUuid}`, {
      headers: userHeaders,
    });
    await expectProblem(concealed, 404, 'calendar_resource_not_found');

    const acl = await request.put(`/api/v1/calendars/${calendarUuid}/acl`, {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('calendar-acl') }),
      data: {
        rules: [{
          subject_type: 'user',
          subject_id: ordinaryUserId,
          can_read: true,
          can_write: true,
        }],
      },
    });
    const aclCalendar = await expectData(acl);
    expect(aclCalendar.uuid).toBe(calendarUuid);

    const visible = await request.get(`/api/v1/calendars/${calendarUuid}`, {
      headers: userHeaders,
    });
    expect((await expectData(visible)).can_write).toBe(true);
    const list = await request.get('/api/v1/calendars?page[limit]=100', { headers: userHeaders });
    expect((await expectData(list)).map((item) => item.uuid)).toContain(calendarUuid);
  });

  test('event CRUD, mandatory ranges, and recurring expansion switch work', async ({ request }) => {
    const missingRange = await request.get(`/api/v1/calendars/${calendarUuid}/events`, {
      headers: userHeaders,
    });
    await expectProblem(missingRange, 422, 'calendar_validation_failed');

    const created = await request.post(`/api/v1/calendars/${calendarUuid}/events`, {
      headers: apiHeaders(userApiToken, { 'Idempotency-Key': idempotencyKey('calendar-event') }),
      data: {
        title: 'E2E release review',
        description: 'Validate the complete all-module build.',
        location: 'Online',
        starts_at: '2026-08-15 10:00:00',
        ends_at: '2026-08-15 11:00:00',
        timezone: 'Europe/Zagreb',
        is_all_day: false,
      },
    });
    const event = await expectData(created, 201);
    eventId = event.id;
    expect(event.title).toBe('E2E release review');

    const single = await request.get(`/api/v1/calendars/${calendarUuid}/events/${eventId}`, {
      headers: userHeaders,
    });
    expect((await expectData(single)).calendar_id).toBeGreaterThan(0);

    const updated = await request.patch(`/api/v1/calendars/${calendarUuid}/events/${eventId}`, {
      headers: apiHeaders(userApiToken, { 'Idempotency-Key': idempotencyKey('calendar-event-update') }),
      data: { title: 'E2E release review updated', location: 'Hybrid' },
    });
    expect((await expectData(updated)).title).toBe('E2E release review updated');

    const events = await request.get(
      `/api/v1/calendars/${calendarUuid}/events?from=2026-08-01&to=2026-08-31&expand_recurring=0`,
      { headers: userHeaders },
    );
    expect((await expectData(events)).map((item) => item.id)).toContain(eventId);
  });

  test('ICS export, calendar ETags, event deletion, and permanent calendar deletion work', async ({ request }) => {
    const exported = await request.get(`/api/v1/calendars/${calendarUuid}/export.ics`, {
      headers: userHeaders,
    });
    expect(exported.status()).toBe(200);
    expect(exported.headers()['content-type']).toContain('text/calendar');
    const ics = await exported.text();
    expect(ics).toContain('BEGIN:VCALENDAR');
    expect(ics).toContain('E2E release review updated');

    const calendar = await getDataWithEtag(request, `/api/v1/calendars/${calendarUuid}`, adminHeaders);
    const stale = await request.patch(`/api/v1/calendars/${calendarUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('calendar-stale'),
        'If-Match': '"stale-calendar-etag"',
      }),
      data: { description: 'Must not be saved.' },
    });
    await expectProblem(stale, 412, 'resource_changed');

    const updated = await request.patch(`/api/v1/calendars/${calendarUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('calendar-update'),
        'If-Match': calendar.etag,
      }),
      data: { description: 'Updated through a protected Calendar request.' },
    });
    expect((await expectData(updated)).description).toContain('protected Calendar');

    const deletedEvent = await request.delete(`/api/v1/calendars/${calendarUuid}/events/${eventId}`, {
      headers: apiHeaders(userApiToken, { 'Idempotency-Key': idempotencyKey('calendar-event-delete') }),
    });
    expect(deletedEvent.status()).toBe(204);

    const afterUpdate = await getDataWithEtag(request, `/api/v1/calendars/${calendarUuid}`, adminHeaders);
    const deletedCalendar = await request.delete(`/api/v1/calendars/${calendarUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('calendar-delete'),
        'If-Match': afterUpdate.etag,
      }),
    });
    expect(deletedCalendar.status()).toBe(204);

    const missing = await request.get(`/api/v1/calendars/${calendarUuid}`, { headers: adminHeaders });
    await expectProblem(missing, 404, 'calendar_resource_not_found');
  });
});

test.describe.serial('Webhook subscription and outbox lifecycle', () => {
  let webhookUuid;
  let deliveryUuid;

  test('subscription creation returns its secret once and enforces ownership', async ({ request }) => {
    const created = await request.post('/api/v1/webhooks', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('webhook-create') }),
      data: {
        name: 'E2E outbox observer',
        target_url: 'https://example.com/hooks/heartphrame',
        events: ['*'],
        active: true,
      },
    });
    const createdData = await expectData(created, 201);
    webhookUuid = createdData.subscription.uuid;
    expect(createdData.secret).toBeTruthy();

    const fetched = await request.get(`/api/v1/webhooks/${webhookUuid}`, { headers: adminHeaders });
    const fetchedData = await expectData(fetched);
    expect(fetchedData.uuid).toBe(webhookUuid);
    expect(JSON.stringify(fetchedData)).not.toContain(createdData.secret);
    expect(JSON.stringify(fetchedData)).not.toContain('encrypted_secret');

    const concealed = await request.get(`/api/v1/webhooks/${webhookUuid}`, { headers: userHeaders });
    await expectProblem(concealed, 404, 'webhook_not_found');
    const list = await request.get('/api/v1/webhooks?page[limit]=100', { headers: adminHeaders });
    expect((await expectData(list)).map((item) => item.uuid)).toContain(webhookUuid);
  });

  test('ETag update and secret rotation preserve a safe public subscription', async ({ request }) => {
    const current = await getDataWithEtag(request, `/api/v1/webhooks/${webhookUuid}`, adminHeaders);
    const updated = await request.patch(`/api/v1/webhooks/${webhookUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('webhook-update'),
        'If-Match': current.etag,
      }),
      data: { name: 'E2E updated outbox observer', events: ['calendars.*', 'calendar_events.*'] },
    });
    expect((await expectData(updated)).name).toBe('E2E updated outbox observer');

    const updatedResource = await getDataWithEtag(request, `/api/v1/webhooks/${webhookUuid}`, adminHeaders);
    const rotated = await request.post(`/api/v1/webhooks/${webhookUuid}/rotate-secret`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('webhook-rotate'),
        'If-Match': updatedResource.etag,
      }),
      data: {},
    });
    const rotation = await expectData(rotated);
    expect(rotation.secret).toBeTruthy();
    expect(rotation.subscription.uuid).toBe(webhookUuid);
  });

  test('a real mutation creates an outbox delivery that can be inspected and retried', async ({ request }) => {
    const calendar = await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('webhook-event-source') }),
      data: { name: 'E2E Webhook Event Source', type: 'team', is_enabled: true },
    });
    const createdCalendar = await expectData(calendar, 201);

    const deliveries = await request.get(`/api/v1/webhooks/${webhookUuid}/deliveries?page[limit]=100`, {
      headers: adminHeaders,
    });
    const deliveryList = await expectData(deliveries);
    expect(deliveryList.length).toBeGreaterThan(0);
    const matching = deliveryList.find((delivery) => delivery.event.startsWith('calendars.'));
    expect(matching).toBeTruthy();
    deliveryUuid = matching.uuid;

    const delivery = await getDataWithEtag(
      request,
      `/api/v1/webhooks/${webhookUuid}/deliveries/${deliveryUuid}`,
      adminHeaders,
    );
    expect(delivery.data.status).toBe('pending');
    expect(JSON.stringify(delivery.data)).not.toContain('encrypted_secret');

    const retried = await request.post(
      `/api/v1/webhooks/${webhookUuid}/deliveries/${deliveryUuid}/retry`,
      {
        headers: apiHeaders(adminApiToken, {
          'Idempotency-Key': idempotencyKey('webhook-retry'),
          'If-Match': delivery.etag,
        }),
        data: {},
      },
    );
    expect((await expectData(retried)).status).toBe('pending');

    const currentCalendar = await getDataWithEtag(
      request,
      `/api/v1/calendars/${createdCalendar.uuid}`,
      adminHeaders,
    );
    const deletedCalendar = await request.delete(`/api/v1/calendars/${createdCalendar.uuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('webhook-source-cleanup'),
        'If-Match': currentCalendar.etag,
      }),
    });
    expect(deletedCalendar.status()).toBe(204);
  });

  test('subscription deletion is ETag protected', async ({ request }) => {
    const current = await getDataWithEtag(request, `/api/v1/webhooks/${webhookUuid}`, adminHeaders);
    const deleted = await request.delete(`/api/v1/webhooks/${webhookUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('webhook-delete'),
        'If-Match': current.etag,
      }),
    });
    expect(deleted.status()).toBe(204);
    const missing = await request.get(`/api/v1/webhooks/${webhookUuid}`, { headers: adminHeaders });
    await expectProblem(missing, 404, 'webhook_not_found');
  });
});

test.describe.serial('CalDAV discovery, collection, and object lifecycle', () => {
  let calendarUuid;
  let calendarSlug;
  const basicAuthorization = `Basic ${Buffer.from(`${adminLogin}:${adminPassword}`).toString('base64')}`;
  const calDavHeaders = {
    Authorization: basicAuthorization,
    Accept: 'application/xml, text/calendar;q=0.9, */*;q=0.1',
  };

  test('well-known discovery, HEAD, OPTIONS, root, and principal PROPFIND work', async ({ request }) => {
    const wellKnown = await request.get('/.well-known/caldav', {
      headers: calDavHeaders,
      maxRedirects: 0,
    });
    expect([301, 302, 307, 308]).toContain(wellKnown.status());
    expect(wellKnown.headers().location).toContain('/caldav');

    const wellKnownHead = await request.fetch('/.well-known/caldav', {
      method: 'HEAD',
      headers: calDavHeaders,
      maxRedirects: 0,
    });
    expect([301, 302, 307, 308]).toContain(wellKnownHead.status());

    const head = await request.fetch('/caldav', { method: 'HEAD', headers: calDavHeaders });
    expect(head.status()).toBe(200);
    const options = await request.fetch('/caldav', { method: 'OPTIONS', headers: calDavHeaders });
    expect([200, 204]).toContain(options.status());
    expect(options.headers().allow).toContain('PROPFIND');
    expect(options.headers().dav).toContain('calendar-access');

    const root = await request.fetch('/caldav', {
      method: 'PROPFIND',
      headers: { ...calDavHeaders, Depth: '1', 'Content-Type': 'application/xml' },
      data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:prop><d:current-user-principal/></d:prop></d:propfind>',
    });
    expect(root.status()).toBe(207);
    expect(root.headers()['content-type']).toContain('application/xml');
    const rootXml = await root.text();
    expect(rootXml).toContain('multistatus');
    expect(rootXml).toContain('/caldav/principals/');

    const principalMatch = rootXml.match(/\/caldav\/principals\/([^<\/]+)\/?/);
    expect(principalMatch).not.toBeNull();
    const principalPath = `/caldav/principals/${principalMatch[1]}`;
    const principalHead = await request.fetch(principalPath, {
      method: 'HEAD',
      headers: calDavHeaders,
    });
    expect(principalHead.status()).toBe(200);
    const principal = await request.fetch(principalPath, {
      method: 'PROPFIND',
      headers: { ...calDavHeaders, Depth: '0', 'Content-Type': 'application/xml' },
      data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:"><d:allprop/></d:propfind>',
    });
    expect(principal.status()).toBe(207);
  });

  test('calendar collection supports PROPFIND, REPORT, and object PUT/GET/DELETE', async ({ request }) => {
    const created = await request.post('/api/v1/calendars', {
      headers: apiHeaders(adminApiToken, { 'Idempotency-Key': idempotencyKey('caldav-calendar') }),
      data: { name: 'E2E CalDAV Calendar', type: 'team', is_enabled: true },
    });
    const calendar = await expectData(created, 201);
    calendarUuid = calendar.uuid;
    calendarSlug = calendar.slug;
    expect(calendarSlug).toBeTruthy();

    const collectionPath = `/caldav/${calendarSlug}`;
    const collectionHead = await request.fetch(collectionPath, {
      method: 'HEAD',
      headers: calDavHeaders,
    });
    expect(collectionHead.status()).toBe(200);
    const collectionOptions = await request.fetch(collectionPath, {
      method: 'OPTIONS',
      headers: calDavHeaders,
    });
    expect([200, 204]).toContain(collectionOptions.status());
    expect(collectionOptions.headers().allow).toContain('REPORT');

    const collection = await request.fetch(collectionPath, {
      method: 'PROPFIND',
      headers: { ...calDavHeaders, Depth: '1', 'Content-Type': 'application/xml' },
      data: '<?xml version="1.0"?><d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop><d:displayname/><c:calendar-description/></d:prop></d:propfind>',
    });
    expect(collection.status()).toBe(207);
    expect(await collection.text()).toContain('E2E CalDAV Calendar');

    const objectPath = `${collectionPath}/e2e-caldav-event.ics`;
    const icalendar = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//HFClean E2E//EN',
      'BEGIN:VEVENT',
      'UID:e2e-caldav-event',
      'DTSTART:20260820T080000Z',
      'DTEND:20260820T090000Z',
      'SUMMARY:E2E CalDAV event',
      'DESCRIPTION:Created through the real CalDAV endpoint',
      'LOCATION:Remote',
      'END:VEVENT',
      'END:VCALENDAR',
      '',
    ].join('\r\n');
    const put = await request.fetch(objectPath, {
      method: 'PUT',
      headers: {
        ...calDavHeaders,
        'Content-Type': 'text/calendar; charset=utf-8',
        'If-None-Match': '*',
      },
      data: icalendar,
    });
    expect([201, 204]).toContain(put.status());

    const objectOptions = await request.fetch(objectPath, {
      method: 'OPTIONS',
      headers: calDavHeaders,
    });
    expect([200, 204]).toContain(objectOptions.status());
    expect(objectOptions.headers().allow).toContain('PUT');

    const object = await request.get(objectPath, { headers: calDavHeaders });
    expect(object.status()).toBe(200);
    expect(object.headers()['content-type']).toContain('text/calendar');
    expect(await object.text()).toContain('SUMMARY:E2E CalDAV event');

    const report = await request.fetch(collectionPath, {
      method: 'REPORT',
      headers: { ...calDavHeaders, Depth: '1', 'Content-Type': 'application/xml' },
      data: [
        '<?xml version="1.0"?>',
        '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">',
        '<d:prop><d:getetag/><c:calendar-data/></d:prop>',
        '<c:filter><c:comp-filter name="VCALENDAR"><c:comp-filter name="VEVENT"/></c:comp-filter></c:filter>',
        '</c:calendar-query>',
      ].join(''),
    });
    expect(report.status()).toBe(207);
    expect(await report.text()).toContain('e2e-caldav-event.ics');

    const deletedObject = await request.delete(objectPath, { headers: calDavHeaders });
    expect(deletedObject.status()).toBe(204);
    const missingObject = await request.get(objectPath, { headers: calDavHeaders });
    expect(missingObject.status()).toBe(404);

    const current = await getDataWithEtag(request, `/api/v1/calendars/${calendarUuid}`, adminHeaders);
    const cleanup = await request.delete(`/api/v1/calendars/${calendarUuid}`, {
      headers: apiHeaders(adminApiToken, {
        'Idempotency-Key': idempotencyKey('caldav-calendar-delete'),
        'If-Match': current.etag,
      }),
    });
    expect(cleanup.status()).toBe(204);
  });
});
