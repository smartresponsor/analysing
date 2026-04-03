import { test, expect } from '@playwright/test';

test('analytics metrics endpoint returns valid payload', async ({ request }) => {
  const response = await request.get('/api/analytics/export-jobs/metrics');

  expect(response.status()).toBe(200);

  const json = await response.json();

  expect(json).toHaveProperty('jobs_total');
  expect(json).toHaveProperty('status_counts');
  expect(json).toHaveProperty('retryable_failed_jobs');
  expect(json).toHaveProperty('avg_duration_ms');
  expect(json).toHaveProperty('exported_rows_total');

  expect(typeof json.jobs_total).toBe('number');
  expect(typeof json.avg_duration_ms).toBe('number');
});
