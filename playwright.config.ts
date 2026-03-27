import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/Playwright',
  timeout: 30_000,
  fullyParallel: true,
  retries: 0,
  reporter: 'list',
  use: {
    trace: 'on-first-retry',
    headless: true,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
