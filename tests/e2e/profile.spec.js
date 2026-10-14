// /desk/profile: the "which one?" field tied to the "Autre" instrument (instrument-other-toggle.js).
import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './support/helpers.js';

test.use({ storageState: ADMIN_STATE });

test('the "which instrument" field only exists when "Autre" is picked', async ({ page }) => {
  await page.goto('/desk/profile');
  const select = page.locator('select[data-instrument-select]');
  const otherRow = page.locator('[data-other-instrument-row]');
  const otherInput = otherRow.locator('input, textarea');

  await select.selectOption({ label: 'Tuba' });
  await expect(otherRow).toBeHidden();
  await expect(otherInput).toBeDisabled();

  await select.selectOption({ label: 'Autre' });
  await expect(otherRow).toBeVisible();
  await expect(otherInput).toBeEnabled();
});
