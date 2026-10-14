// Logs in as the admin once per run and saves the session cookie for the specs that need it.
import { test as setup } from '@playwright/test';
import { login, ADMIN_STATE } from './support/helpers.js';

setup('log in as admin', async ({ page }) => {
  await login(page);
  await page.context().storageState({ path: ADMIN_STATE });
});
