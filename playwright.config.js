// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  timeout: 60_000,            // free hosting can be slow
  expect: { timeout: 15_000 },
  retries: 1,
  workers: 1,                 // run in order, avoid rate limiting on InfinityFree
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.BASE_URL || 'http://booknest-testing.infinityfree.me',
    screenshot: 'on',         // evidence for the report
    trace: 'on-first-retry',
    // If the host shows a JS "checking your browser" page, try: npx playwright test --headed
    ...devices['Desktop Chrome'],
  },
});