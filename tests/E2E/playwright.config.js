import { defineConfig } from '@playwright/test';

const baseURL = process.env.HPH_E2E_BASE_URL;
// EN: Database runs keep distinct artifacts instead of overwriting one another.
//
// HR: Testovi različitih baza čuvaju odvojene rezultate umjesto prepisivanja.
const database = process.env.HPH_E2E_DATABASE || 'sqlite';
if (!['sqlite', 'mysql', 'mariadb', 'pgsql'].includes(database)) {
  throw new Error('Unsupported HPH_E2E_DATABASE.');
}
const artifactSuffix = database === 'sqlite' ? '' : `-${database}`;

if (!baseURL) {
  throw new Error('HPH_E2E_BASE_URL is required. Run the suite through composer e2e.');
}

export default defineConfig({
  testDir: '.',
  testMatch: '**/*.spec.js',
  fullyParallel: false,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: [
    ['list'],
    ['html', { outputFolder: `../../build/playwright-report${artifactSuffix}`, open: 'never' }],
  ],
  outputDir: `../../build/test-results${artifactSuffix}`,
  use: {
    baseURL,
    browserName: 'chromium',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
});
