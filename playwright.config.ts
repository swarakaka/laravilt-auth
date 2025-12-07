import { defineConfig, devices } from '@playwright/test'

/**
 * Laravilt Auth Package - E2E Testing Configuration
 *
 * Tests authentication flows (login, registration, password reset,
 * 2FA challenge, profile settings, etc.) against the demo application
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // Auth tests should run sequentially
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1, // Single worker for auth tests
  reporter: [['html', { open: 'never' }], ['list']],

  use: {
    baseURL: process.env.APP_URL || 'https://laravilt.test',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'on-first-retry',
    ignoreHTTPSErrors: true,
    actionTimeout: 10000,
    navigationTimeout: 30000,
  },

  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
      },
    },
  ],

  timeout: 30000,
  expect: { timeout: 5000 },
  outputDir: 'test-results/',
})
