// Regression test for issue #44: clicking an HTML element drawn over a 3D scene (here the navbar burger) must not also click the 3D object behind it.
// At 1280x800 (default viewport), the burger sits over the home page mascot: the old window-wide click listener made it react.
import { test, expect } from '@playwright/test';

const scenes = ['/', '/story', '/join', '/schedule', '/music'];

for (const path of scenes) {
  test(`burger click on ${path} doesn't reach the 3D scene behind it`, async ({ page }) => {
    await page.goto(path);
    // The loader gets .isloaded once the 3D model is ready: before that, raycasts hit nothing anyway.
    await expect(page.locator('#js-loader.isloaded')).toBeAttached({ timeout: 20_000 });

    await page.locator('.navbar-toggler').click();
    // Give a wrongly triggered 3D action (redirect, contact popup) the time to happen.
    await page.waitForTimeout(500);

    expect(new URL(page.url()).pathname).toBe(path);
    if (path === '/story') {
      await expect(page.locator('#contactForm')).toBeHidden();
    }
  });
}
