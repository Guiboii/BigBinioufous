// /music behaviours that only exist in the browser: the setlist modal submitted via fetch (no reload) and the wavesurfer player.
import { test, expect } from '@playwright/test';
import { ADMIN_STATE, MOBILE, openFreshFolder, uploadFiles, fixture } from './support/helpers.js';

// A song needs a top-level music folder holding an audio file to become playable: created through the file manager first.
async function addPlayableSong(page, { youtubeUrl = '' } = {}) {
  const folderName = await openFreshFolder(page, 'music', 'song');
  await uploadFiles(page, [fixture('sample.mp3')]);
  const title = `Morceau ${folderName}`;

  await page.goto('/music');
  await page.waitForLoadState('load');
  // Set before any action: a full page reload would wipe it.
  await page.evaluate(() => (window.__noReload = true));
  await page.locator('#uploadNew').click();
  const dialog = page.locator('#setlistManageDialog');
  await expect(dialog).toBeVisible();
  await dialog.locator('#setlist-title').fill(title);
  await dialog.locator('#setlist-folder').selectOption({ label: folderName });
  await dialog.locator('#setlist-youtube').fill(youtubeUrl);
  await dialog.locator('form[action$="/setlist"] button[type="submit"]').click();

  return title;
}

function playlistTrack(page, title) {
  return page.locator('#playlist a.list-group-item', { hasText: title });
}

test.use({ storageState: ADMIN_STATE });
// Each test uploads a file and loads the 3D scene of /music: more than the default 30s on a slow machine.
test.slow();

test('adding a song refreshes the modal and playlist in place, and the new track plays', async ({
  page,
}) => {
  const title = await addPlayableSong(page);

  await expect(playlistTrack(page, title)).toBeVisible();
  await expect(page.locator('.setlist-admin-list summary', { hasText: title })).toBeVisible();
  await expect(page.locator('#setlistManageDialog')).toBeVisible();
  expect(await page.evaluate(() => window.__noReload)).toBe(true);

  // #playlist was rebuilt with innerHTML: the delegated click listener must still catch the new link.
  await page.locator('#setlistManageDialog .close').click();
  await playlistTrack(page, title).click();
  await expect(page.locator('#powerLed')).toHaveClass(/is-playing/);
});

test('arriving on the page loads the first track without playing it', async ({ page }) => {
  await addPlayableSong(page);

  const audioLoaded = page.waitForResponse(/\/uploads\/documents\/.+\.mp3/);
  await page.reload();
  await audioLoaded;
  // Leave wavesurfer time to fire "ready", which used to start playback on its own.
  await page.waitForTimeout(1500);

  await expect(page.locator('#playlist a.list-group-item').first()).toHaveClass(/active/);
  await expect(page.locator('#powerLed')).not.toHaveClass(/is-playing/);
});

test('reordering and deleting a song happen in place', async ({ page }) => {
  const title = await addPlayableSong(page);
  const summaries = page.locator('.setlist-admin-list summary');
  await expect(summaries.last()).toHaveText(title);

  await page
    .locator('.setlist-admin-list li', { hasText: title })
    .locator('form[action$="/move-up"] button')
    .click();
  await expect(summaries.last()).not.toHaveText(title);
  await expect(summaries.filter({ hasText: title })).toHaveCount(1);

  page.once('dialog', (dialog) => dialog.accept());
  await page
    .locator('.setlist-admin-list li', { hasText: title })
    .locator('button.btn-red')
    .click();
  await expect(summaries.filter({ hasText: title })).toHaveCount(0);
  await expect(playlistTrack(page, title)).toHaveCount(0);
  expect(await page.evaluate(() => window.__noReload)).toBe(true);
});

test('a visitor opens a YouTube video over the scene and closes it with Escape', async ({
  page,
}) => {
  const title = await addPlayableSong(page, {
    youtubeUrl: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
  });
  await expect(page.locator('#playlist li', { hasText: title })).toBeVisible();
  await page.goto('/logout');
  // Never load the real YouTube player: only the overlay and the iframe address are checked.
  await page.route(/youtube-nocookie\.com/, (route) => route.abort());

  await page.goto('/music');
  await page.waitForLoadState('load');
  await page.locator('#playlist li', { hasText: title }).locator('[data-youtube-id]').click();

  const overlay = page.locator('#videoOverlay');
  await expect(overlay).toBeVisible();
  await expect(overlay.locator('#youtube-embed iframe')).toHaveAttribute(
    'src',
    /youtube-nocookie\.com\/embed\/dQw4w9WgXcQ/,
  );
  await expect(overlay.locator('.video-overlay-close')).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(overlay).toBeHidden();
  // Closing must stop the video, not just hide it.
  await expect(overlay.locator('#youtube-embed iframe')).toHaveCount(0);
});

test.describe('on a phone', () => {
  test.use({ ...MOBILE, storageState: { cookies: [], origins: [] } });

  test('the 3D scene is skipped and the playlist stays usable', async ({ page }) => {
    await page.goto('/music');

    await expect(page.locator('html')).toHaveClass(/is-mobile/);
    await expect(page.locator('.mascotte')).toBeHidden();
    await expect(page.locator('#playlist')).toBeVisible();
    await expect(page.locator('#playPause')).toBeVisible();
  });
});
