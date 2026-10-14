// Accessible login popup on /join (join.js): keyboard focus handling and the failed login message.
import { test, expect } from '@playwright/test';

test('the login popup moves focus in on open and back to its trigger on Escape', async ({
  page,
}) => {
  await page.goto('/join');
  const trigger = page.locator('button.login');
  const popup = page.locator('#loginForm');

  await trigger.click();
  await expect(popup).toBeVisible();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(page.locator('input[name="_username"]')).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(popup).toBeHidden();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(trigger).toBeFocused();
});

test('wrong credentials show an error the user can actually see', async ({ page }) => {
  await page.goto('/join');
  await page.locator('button.login').click();
  await page.locator('input[name="_username"]').fill('admin@admin.com');
  await page.locator('input[name="_password"]').fill('wrong-password');
  await page.locator('#loginForm button[type="submit"]').click();

  await expect(page).toHaveURL(/\/join/);
  await expect(page.locator('#loginForm [role="alert"]')).toBeVisible();
});
