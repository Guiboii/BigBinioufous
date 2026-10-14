// Shared helpers for the e2e specs: login and a fresh, uniquely named folder per test so tests sharing the database never see each other's files.
import { expect, devices } from '@playwright/test';

// Phone settings for test.use() inside a describe: defaultBrowserType can't be overridden there, so it's dropped.
const pixel = { ...devices['Pixel 7'] };
delete pixel.defaultBrowserType;
export const MOBILE = pixel;

export const ADMIN = { email: 'admin@admin.com', password: 'admin' };

// Saved by auth.setup.js; a spec opts in with test.use({ storageState: ADMIN_STATE }).
export const ADMIN_STATE = 'playwright/.auth/admin.json';

export async function login(page, account = ADMIN) {
  await page.goto('/join');
  await page.locator('button.login').click();
  await page.locator('input[name="_username"]').fill(account.email);
  await page.locator('input[name="_password"]').fill(account.password);
  await page.locator('#loginForm button[type="submit"]').click();
  await expect(page).toHaveURL(/\/desk/);
}

// Creates a top-level folder in the space through the UI and opens it. Returns its name.
export async function openFreshFolder(page, space, prefix = 'e2e') {
  const name = `${prefix}-${Date.now()}`;
  await page.goto(`/desk/files/${space}`);
  await page.locator('#new-folder-name').fill(name);
  await page.locator('.desk-new-folder button[type="submit"]').click();
  await page.locator('.desk-folders a', { hasText: name }).click();
  await expect(page.locator('.desk-breadcrumb [aria-current="page"]')).toContainText(name);
  // Module scripts (quick-upload, drag-move...) run late: interacting before "load" can hit a page with no listeners yet.
  await page.waitForLoadState('load');

  return name;
}

// Uploads files through the hidden input of the drop zone; the page reloads itself once every file is sent.
export async function uploadFiles(page, paths) {
  await Promise.all([
    page.waitForEvent('load'),
    page.locator('#quick-upload-input').setInputFiles(paths),
  ]);
}

export function fixture(name) {
  return new URL(`../fixtures/${name}`, import.meta.url).pathname;
}
