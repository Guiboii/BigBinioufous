// File manager behaviours that only exist in the browser: quick-upload zone, its error messages, drag-and-drop move and multi-select.
import { test, expect } from '@playwright/test';
import { ADMIN_STATE, openFreshFolder, uploadFiles, fixture } from './support/helpers.js';

test.use({ storageState: ADMIN_STATE });

test('audio, PDF and image are all accepted in the music space', async ({ page }) => {
  await openFreshFolder(page, 'music', 'upload');
  await uploadFiles(page, [fixture('sample.mp3'), fixture('sample.pdf'), fixture('sample.png')]);

  const documents = page.locator('.desk-document');
  await expect(documents).toHaveCount(3);
  await expect(page.locator('.desk-document--audio')).toHaveCount(1);
  await expect(page.locator('.desk-document--pdf')).toHaveCount(1);
  await expect(page.locator('.desk-document--image')).toHaveCount(1);
});

test('a refused file type shows its precise error, not the generic one', async ({ page }) => {
  await openFreshFolder(page, 'music', 'refused');
  await page.locator('#quick-upload-input').setInputFiles(fixture('not-allowed.zip'));

  await expect(page.locator('#quick-upload-status li')).toHaveText(
    'not-allowed.zip : type de fichier non accepté ici',
  );
  await expect(page.locator('.desk-document')).toHaveCount(0);
});

test('a file too large for PHP shows its precise error', async ({ page }) => {
  await openFreshFolder(page, 'music', 'too-large');
  // A real 200 MB upload would be slow: the server answer is faked, what's tested is the message the script picks.
  await page.route('**/desk/files/music/documents', (route) =>
    route.fulfill({ status: 400, json: { error: 'file_too_large' } }),
  );
  await page.locator('#quick-upload-input').setInputFiles(fixture('sample.mp3'));

  await expect(page.locator('#quick-upload-status li')).toHaveText(
    'sample.mp3 : fichier trop volumineux',
  );
});

test('dragging a document onto a folder moves it there', async ({ page }) => {
  await openFreshFolder(page, 'other', 'drag');
  await page.locator('#new-folder-name').fill('Cible');
  await page.locator('.desk-new-folder button[type="submit"]').click();
  await uploadFiles(page, [fixture('sample.pdf')]);

  await Promise.all([
    page.waitForEvent('load'),
    page
      .locator('.desk-document--pdf')
      .dragTo(page.locator('[data-dropzone="folder"]', { hasText: 'Cible' })),
  ]);

  await expect(page.locator('.desk-document')).toHaveCount(0);
  await page.locator('.desk-folders a', { hasText: 'Cible' }).click();
  await expect(page.locator('.desk-document--pdf')).toBeVisible();
});

test('bulk move only moves the checked items', async ({ page }) => {
  const folderName = await openFreshFolder(page, 'other', 'bulk');
  await page.locator('#new-folder-name').fill('Cible');
  await page.locator('.desk-new-folder button[type="submit"]').click();
  await uploadFiles(page, [fixture('sample.mp3'), fixture('sample.pdf'), fixture('sample.png')]);

  await page.locator('.desk-document--audio .desk-select').check();
  await page.locator('.desk-document--pdf .desk-select').check();
  await expect(page.locator('#desk-bulk-count')).toContainText('2');
  await page.locator('#desk-bulk-move-btn').click();

  // Move mode starts from the space root: browse down to the target folder, then confirm from the banner.
  await page.locator('.desk-folders a', { hasText: folderName }).click();
  await page.locator('.desk-folders a', { hasText: 'Cible' }).click();
  await page.locator('.desk-move-banner button[type="submit"]').click();

  await expect(page.locator('.desk-document')).toHaveCount(2);
  await expect(page.locator('.desk-document--image')).toHaveCount(0);
  await page.goto('/desk/files/other');
  await page.locator('.desk-folders a', { hasText: folderName }).click();
  await expect(page.locator('.desk-document')).toHaveCount(1);
  await expect(page.locator('.desk-document--image')).toBeVisible();
});
