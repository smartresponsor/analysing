import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  fullyParallel: true,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: 'http://127.0.0.1:8098',
    trace: 'on-first-retry',
    headless: true,
  },
  webServer: {
    command: 'php -S 127.0.0.1:8098 -t public public/index.php',
    url: 'http://127.0.0.1:8098/status',
    reuseExistingServer: false,
    timeout: 30_000,
    env: {
      APP_ENV: 'test',
      APP_DEBUG: '1',
    },
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
