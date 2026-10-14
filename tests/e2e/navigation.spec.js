// Behaviours shared by every page: the collapsed mobile menu (app.js).
import { test, expect } from '@playwright/test';
import { MOBILE } from './support/helpers.js';

test.describe('on a phone', () => {
  test.use(MOBILE);

  test('Escape closes the open menu and gives focus back to the burger', async ({ page }) => {
    await page.goto('/contact');
    const burger = page.locator('.navbar-toggler');
    const menu = page.locator(await burger.getAttribute('data-target'));

    await burger.click();
    await expect(menu).toHaveClass(/show/);
    await expect(burger).toHaveAttribute('aria-expanded', 'true');

    await page.keyboard.press('Escape');
    await expect(menu).not.toHaveClass(/show/);
    await expect(burger).toHaveAttribute('aria-expanded', 'false');
    await expect(burger).toBeFocused();
  });
});
