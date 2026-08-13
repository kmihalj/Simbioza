import { expect } from '@playwright/test';

/**
 * HR: Vraća obavezne E2E vjerodajnice koje izolirani PHP pokretač predaje
 *     Playwrightu bez zapisivanja tajnih API tokena u repozitorij ili logove.
 * EN: Returns required E2E credentials passed to Playwright by the isolated PHP
 *     runner without storing secret API tokens in the repository or logs.
 */
export function e2eEnvironment() {
  const values = {
    adminLogin: process.env.HPH_E2E_ADMIN_LOGIN,
    adminPassword: process.env.HPH_E2E_ADMIN_PASSWORD,
    userLogin: process.env.HPH_E2E_USER_LOGIN,
    userPassword: process.env.HPH_E2E_USER_PASSWORD,
    adminApiToken: process.env.HPH_E2E_API_TOKEN,
    userApiToken: process.env.HPH_E2E_USER_API_TOKEN,
  };

  for (const [name, value] of Object.entries(values)) {
    if (!value) {
      throw new Error(`${name} is required. Run the suite through composer e2e.`);
    }
  }

  return values;
}

/**
 * HR: Prijavljuje korisnika kroz stvarni lokalni Auth obrazac.
 * EN: Signs a user in through the real local Auth form.
 */
export async function login(page, loginIdentifier, password) {
  await page.goto('/auth/login');
  await page.locator('#auth_login').fill(loginIdentifier);
  await page.locator('#auth_password').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.startsWith('/auth/login')),
    page.locator('#local_override_login button[type="submit"]').click(),
  ]);
}

/**
 * HR: Potvrđuje da je Bootstrap modal stvarno interaktivan, a ne samo vidljiv
 *     ispod vlastite pozadine. Provjera obuhvaća DOM portal, z-index, cilj u
 *     središtu dijaloga i fokus koji mora ostati unutar otvorenog modala.
 *
 * EN: Confirms a Bootstrap modal is genuinely interactive rather than merely
 *     visible below its own backdrop. The check covers the DOM portal, z-index,
 *     center hit target, and focus that must remain inside the open modal.
 */
export async function expectUsableModal(modal) {
  await expect(modal).toBeVisible();
  const state = await modal.evaluate((element) => {
    const backdrop = document.querySelector('.modal-backdrop.show');
    const dialog = element.querySelector('.modal-dialog');
    const rectangle = (dialog || element).getBoundingClientRect();
    const centerTarget = document.elementFromPoint(
      rectangle.left + (rectangle.width / 2),
      rectangle.top + (rectangle.height / 2),
    );
    const modalZIndex = Number.parseInt(getComputedStyle(element).zIndex, 10);
    const backdropZIndex = backdrop instanceof HTMLElement
      ? Number.parseInt(getComputedStyle(backdrop).zIndex, 10)
      : 0;

    return {
      directBodyChild: element.parentElement === document.body,
      modalZIndex,
      backdropZIndex,
      centerBelongsToModal: centerTarget instanceof Element && element.contains(centerTarget),
      pointerEvents: getComputedStyle(element).pointerEvents,
    };
  });

  expect(state.directBodyChild).toBe(true);
  expect(state.modalZIndex).toBeGreaterThan(state.backdropZIndex);
  expect(state.centerBelongsToModal).toBe(true);
  expect(state.pointerEvents).not.toBe('none');

  /*
   * HR: Bootstrap aktivira focus trap tek po završetku fade tranzicije, pa
   *     pristupačnost provjeravamo čekanjem umjesto trenutnim očitanjem.
   * EN: Bootstrap activates its focus trap only after the fade transition,
   *     so accessibility is verified by polling rather than an instant read.
   */
  await expect.poll(() => modal.evaluate((element) => (
    document.activeElement instanceof Element && element.contains(document.activeElement)
  ))).toBe(true);
}

/**
 * HR: Gradi standardna zaglavlja za verzionirani API bez otkrivanja tokena.
 * EN: Builds standard versioned API headers without exposing the token.
 */
export function apiHeaders(token, extra = {}) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    ...extra,
  };
}

/**
 * HR: Zahtijeva uspješnu JSON omotnicu i vraća njezino polje data.
 * EN: Requires a successful JSON envelope and returns its data field.
 */
export async function expectData(response, status = 200) {
  expect(response.status()).toBe(status);
  expect(response.headers()['content-type']).toMatch(/^application\/(?:[^;]+\+)?json(?:;|$)/i);
  const payload = await response.json();
  expect(payload).toHaveProperty('data');
  expect(payload.meta?.request_id).toBeTruthy();

  return payload.data;
}

/**
 * HR: Zahtijeva stabilni RFC 9457 problem odgovor.
 * EN: Requires a stable RFC 9457 problem response.
 */
export async function expectProblem(response, status, code) {
  expect(response.status()).toBe(status);
  expect(response.headers()['content-type']).toContain('application/problem+json');
  const payload = await response.json();
  expect(payload.status).toBe(status);
  expect(payload.code).toBe(code);
  expect(payload.request_id).toBeTruthy();

  return payload;
}

/**
 * HR: Dohvaća resurs i vraća podatke zajedno s ETagom za sigurnu izmjenu.
 * EN: Fetches a resource and returns its data with the ETag needed for a safe update.
 */
export async function getDataWithEtag(request, path, headers) {
  const response = await request.get(path, { headers });
  const data = await expectData(response);
  const etag = response.headers().etag;
  expect(etag).toBeTruthy();

  return { data, etag };
}

/**
 * HR: Stvara jedinstven idempotency ključ dovoljne duljine za jedan logički zapis.
 * EN: Creates a unique sufficiently long idempotency key for one logical write.
 */
export function idempotencyKey(prefix) {
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
