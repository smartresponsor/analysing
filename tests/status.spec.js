const { test, expect } = require('@playwright/test');

test('standalone status endpoint is healthy', async ({ request }) => {
  const response = await request.get('/status');

  expect(response.ok()).toBeTruthy();
  expect(await response.text()).toContain('analytics');
});
