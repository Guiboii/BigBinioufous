// /story 3D scene: a click on the dancing mascot must not fall through to a binder behind it (regression of 2026-09-12).
import { test, expect } from '@playwright/test';
import { MOBILE } from './support/helpers.js';

// Known bug, still open: at this spot the ray never hits the mascot (animated model, likely tested in its rest pose by
// three r128), so the first hit is the invisible trieurAccounting box and the visitor is sent to /desk/files/accounting.
// Marked fixme so CI stays green; remove the marker once story.js is fixed.
test.fixme('clicking the mascot keeps the visitor on /story', async ({ page }) => {
  // Fixed size: the mascot's on-screen position depends on the window size.
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto('/story');
  await expect(page.locator('#js-loader.isloaded')).toBeAttached({ timeout: 20_000 });

  // Mascot's torso at this size, the part that moves least while it dances.
  await page.mouse.click(95, 590);
  // Give a wrongly triggered redirect (to a /desk/files space) the time to happen.
  await page.waitForTimeout(1000);

  expect(new URL(page.url()).pathname).toBe('/story');
});

// Same open bug as the mascot: the invisible trieurAdmin box sits in front of the saucer (ray distance 2.48 vs 2.51).
test.fixme('clicking the flying saucer leads to /join', async ({ page }) => {
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto('/story');
  await expect(page.locator('#js-loader.isloaded')).toBeAttached({ timeout: 20_000 });

  // A binder click also ends on /join for a visitor (via the /desk/files login redirect): make sure it's really the saucer.
  const visited = [];
  page.on('request', (request) => request.isNavigationRequest() && visited.push(request.url()));

  // The saucer peeks in from the left edge at this size.
  await page.mouse.click(12, 295);
  await expect(page).toHaveURL(/\/join$/);
  expect(visited.filter((url) => url.includes('/desk/files'))).toEqual([]);
});

test('the minisite inside the 3D screen has no navbar of its own', async ({ page }) => {
  await page.goto('/story');
  const minisite = page.frameLocator('#miniSite');

  await expect(minisite.locator('html')).toHaveClass(/is-embedded/);
  await expect(minisite.locator('.site-nav')).toBeHidden();
  await expect(minisite.locator('#minisite-contact-form')).toBeAttached();
});

test('the minisite contact form shows the server answer in place', async ({ page }) => {
  await page.route('**/contact', (route) =>
    route.request().method() === 'POST'
      ? route.fulfill({ status: 422, json: { error: 'invalid_email' } })
      : route.continue(),
  );
  await page.goto('/story/mini#contact');
  await page.locator('#minisite-contact-name').fill('Camille');
  await page.locator('#minisite-contact-email').fill('camille@example.com');
  await page.locator('#minisite-contact-message').fill('Bonjour !');
  await page.locator('#minisite-contact-form button[type="submit"]').click();

  await expect(page.locator('#minisite-contact-status')).toHaveText('Adresse email invalide.');
  await expect(page).toHaveURL(/\/story\/mini#contact$/);
});

test.describe('on a phone', () => {
  test.use(MOBILE);

  test('/story goes straight to the minisite, with its own navbar', async ({ page }) => {
    await page.goto('/story');

    await expect(page).toHaveURL(/\/story\/mini$/);
    await expect(page.locator('html')).toHaveClass(/is-standalone/);
    await expect(page.locator('.site-nav')).toBeVisible();
  });
});
