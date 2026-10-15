import { expect, test } from '@playwright/test';
import { E2E_PASSWORD, E2E_USER } from '../env';

// Background jobs and live notifications (ADR-0014): the test job goes through the Redis queue,
// the ml-queue worker and Reverb, and its result comes back to this page as a toast.
test('the test background job reports back live', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Username').fill(E2E_USER);
  await page.getByLabel('Password').fill(E2E_PASSWORD);
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await page.getByRole('button', { name: 'Run test job' }).click();
  await expect(page.getByText('Test job queued')).toBeVisible();
  await expect(page.getByText(/Test background job finished after \d+s/)).toBeVisible({ timeout: 20_000 });

  await page.getByRole('button', { name: 'Run failing job' }).click();
  await expect(page.getByText('The background task failed')).toBeVisible({ timeout: 20_000 });
});
