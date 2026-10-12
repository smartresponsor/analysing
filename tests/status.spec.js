const { test, expect } = require('@playwright/test');

test('standalone status endpoint is healthy', async ({ request }) => {
  const response = await request.get('/status');

  expect(response.ok()).toBeTruthy();
  expect(await response.text()).toContain('analytics');
});

test('dashboard renderer QA route renders deterministic analytics content', async ({ page }) => {
  await page.goto('/qa/dashboard-renderer');

  await expect(page.getByRole('heading', { name: 'Analytics dashboard' })).toBeVisible();
  await expect(page.getByText('2,450.00')).toBeVisible();
  await expect(page.getByText('Timeseries')).toBeVisible();
  await expect(page.getByText('Top vendors')).toBeVisible();
  await expect(page.getByText('qa-dashboard-correlation', { exact: true })).toBeVisible();
});
