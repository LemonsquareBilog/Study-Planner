import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  testMatch: ['qa-remediation.spec.ts', 'stack-rendering.spec.ts', 'dashboard-cache.spec.ts'],
  outputDir: '.verification/playwright-results',
  workers: 1,
  reporter: 'line',
  timeout: 30000,
  use: {
    baseURL: 'http://127.0.0.1:8010',
    launchOptions: { executablePath: process.env.CHROMIUM_PATH || '/usr/bin/chromium', args: ['--no-sandbox'] },
  },
  webServer: {
    command: 'php -S 127.0.0.1:8010 -t public scripts/local-router.php',
    url: 'http://127.0.0.1:8010/up',
    reuseExistingServer: false,
  },
});
