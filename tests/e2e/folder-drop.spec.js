// Regression test for issue #36: a folder dropped into a folder that shares its name with a sibling must land in the viewed folder.
import { execFileSync } from 'node:child_process';
import { test, expect } from '@playwright/test';
import { databaseUrl } from '../../playwright.config.js';

async function login(page) {
  await page.goto('/join');
  await page.locator('button.login').click();
  await page.locator('input[name="_username"]').fill('admin@admin.com');
  await page.locator('input[name="_password"]').fill('admin');
  await page.locator('#loginForm button[type="submit"]').click();
  await expect(page).toHaveURL(/\/desk/);
}

// The UI refuses to create a duplicate name, but moves/restores don't check yet (issue #43): seed the duplicates directly in the database.
function insertRootFolder(space, name) {
  execFileSync(
    'php',
    [
      'bin/console',
      'dbal:run-sql',
      `INSERT INTO folder (parent_id, name, space) SELECT id, '${name}', '${space}' FROM folder WHERE space = '${space}' AND parent_id IS NULL`,
      '--quiet',
    ],
    { env: { ...process.env, DATABASE_URL: databaseUrl } },
  );
}

/**
 * Drops a folder tree on the upload zone. A synthetic DataTransfer can't hold real folder entries, so the
 * browser's FileSystemEntry API (webkitGetAsEntry/createReader/readEntries) is mimicked, including readEntries()
 * returning an empty batch at the end. Everything after that (path building, upload, server-side folder tree) is real.
 */
async function dropFolder(page, tree) {
  await page.locator('#quick-upload-zone').evaluate((zone, files) => {
    function fileEntry(path, content) {
      return {
        isFile: true,
        isDirectory: false,
        fullPath: '/' + path,
        file: (resolve) =>
          resolve(new File([content], path.split('/').pop(), { type: 'text/plain' })),
      };
    }

    function directoryEntry(path) {
      const prefix = path + '/';
      const directChildren = new Map();
      Object.keys(files)
        .filter((filePath) => filePath.startsWith(prefix))
        .forEach((filePath) => {
          const rest = filePath.slice(prefix.length);
          const name = rest.split('/')[0];
          const childPath = prefix + name;
          if (!directChildren.has(name)) {
            directChildren.set(
              name,
              rest.includes('/')
                ? directoryEntry(childPath)
                : fileEntry(childPath, files[filePath]),
            );
          }
        });
      let served = false;

      return {
        isFile: false,
        isDirectory: true,
        fullPath: '/' + path,
        createReader: () => ({
          readEntries: (resolve) => {
            resolve(served ? [] : Array.from(directChildren.values()));
            served = true;
          },
        }),
      };
    }

    const topLevel = Object.keys(files)[0].split('/')[0];
    const event = new Event('drop', { bubbles: true, cancelable: true });
    Object.defineProperty(event, 'dataTransfer', {
      value: { items: [{ webkitGetAsEntry: () => directoryEntry(topLevel) }], files: [] },
    });
    zone.dispatchEvent(event);
  }, tree);
}

test('a dropped folder lands in the viewed folder, even next to a same-name sibling', async ({
  page,
}) => {
  await login(page);
  // First visit creates the space's root folder.
  await page.goto('/desk/files/music');
  insertRootFolder('music', 'Set');
  insertRootFolder('music', 'Set');
  await page.reload();

  const setLinks = page.locator('.desk-folders a', { hasText: /^\s*Set\s*$/ });
  await expect(setLinks).toHaveCount(2);

  // Open the second "Set": name-based resolution used to send the files to the first one.
  await setLinks.nth(1).click();
  const viewedUrl = page.url();

  await Promise.all([
    page.waitForEvent('load'),
    dropFolder(page, {
      'Medley/voix-alto.txt': 'Voix alto',
      'Medley/Partitions/tuba.txt': 'Partition tuba',
    }),
  ]);

  expect(page.url()).toBe(viewedUrl);
  await page.locator('.desk-folders a', { hasText: 'Medley' }).click();
  await expect(page.locator('.desk-document', { hasText: 'voix-alto' })).toBeVisible();
  await page.locator('.desk-folders a', { hasText: 'Partitions' }).click();
  await expect(page.locator('.desk-document', { hasText: 'tuba' })).toBeVisible();

  // The other "Set" must stay empty.
  await page.goto('/desk/files/music');
  await setLinks.nth(0).click();
  await expect(page.locator('.desk-folders a', { hasText: 'Medley' })).toHaveCount(0);
});
