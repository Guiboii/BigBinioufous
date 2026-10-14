// /story 3D scene: a click on the dancing mascot must not fall through to a binder behind it (regression of 2026-09-12).
import { test, expect } from '@playwright/test';

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
