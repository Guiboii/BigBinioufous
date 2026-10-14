// Behaviours shared by every page: the collapsed mobile menu (app.js).
import { test, expect } from '@playwright/test';
import { ADMIN_STATE, MOBILE } from './support/helpers.js';

test.describe('on a phone', () => {
  test.use({ ...MOBILE, storageState: ADMIN_STATE });

  // One page per full HTML layout (base, desk/base, minisite), none with a wide table (those still overflow, see #39).
  // Without a viewport meta tag, phones lay pages out ~980px wide and no mobile CSS rule ever applies.
  for (const path of ['/contact', '/music', '/desk/profile', '/desk/files/music', '/story/mini']) {
    test(`${path} is laid out at the phone's width`, async ({ page }) => {
      // The dev-only Symfony debug toolbar is wider than a phone: it doesn't exist in prod, keep it out.
      await page.route('**/_wdt/**', (route) => route.abort());
      await page.goto(path);
      expect(await page.evaluate(() => window.innerWidth)).toBe(MOBILE.viewport.width);
    });
  }

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
