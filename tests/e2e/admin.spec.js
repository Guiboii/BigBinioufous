// Admin/back-office scripts: bulk selection of pending registrations, the Markdown editor, and the quote form helpers.
import { test, expect } from '@playwright/test';
import { ADMIN_STATE } from './support/helpers.js';

test.use({ storageState: ADMIN_STATE });

test('"select all" on pending registrations checks every row and updates the count', async ({
  page,
}) => {
  await page.goto('/admin/valid');
  const selectAll = page.locator('#registration-select-all');
  const rows = page.locator('.registration-select');
  const total = await rows.count();
  expect(total).toBeGreaterThan(1);

  await selectAll.check();
  await expect(page.locator('.registration-select:checked')).toHaveCount(total);
  await expect(page.locator('#desk-bulk-count')).toContainText(String(total));

  // Unchecking one row leaves "select all" half-checked.
  await rows.first().uncheck();
  await expect(page.locator('#desk-bulk-count')).toContainText(String(total - 1));
  expect(await selectAll.evaluate((box) => box.indeterminate)).toBe(true);
});

test('the Markdown editor toolbar formats text that is then saved', async ({ page }) => {
  const title = `Note e2e ${Date.now()}`;
  await page.goto('/desk/notes/new');
  await page.locator('#note_title').fill(title);

  // EasyMDE replaces the textarea with a CodeMirror editor: type into it, select everything, then press "Gras".
  const editor = page.locator('.EasyMDEContainer .CodeMirror');
  await editor.click();
  await page.keyboard.type('important');
  await page.keyboard.press('ControlOrMeta+A');
  await page.locator('.editor-toolbar').getByRole('button', { name: 'Gras' }).click();
  await expect(page.locator('.editor-preview-side strong')).toHaveText('important');

  await page.locator('form[name="note"] button[type="submit"]').click();
  await page.getByRole('link', { name: title, exact: true }).click();
  await expect(page.locator('main strong', { hasText: 'important' })).toBeVisible();
});

test('quote lines can be added and removed, and a saved client fills its details', async ({
  page,
}) => {
  const clientName = `Client e2e ${Date.now()}`;
  await page.goto('/desk/files/accounting/clients/new');
  await page.locator('#client_name').fill(clientName);
  await page.locator('#client_address').fill('1 rue du Hautbois, Lyon');
  await page.locator('#client_contact').fill('client@example.com');
  await page.locator('form[name="client"] button[type="submit"]').click();

  await page.goto('/desk/files/accounting/documents/new/quote');
  await page.waitForLoadState('load');

  // Picking the saved client copies its details into the free-text fields, which stay editable.
  await page.locator('#accounting_document_client').selectOption({ label: clientName });
  await expect(page.locator('#accounting_document_clientName')).toHaveValue(clientName);
  await expect(page.locator('#accounting_document_clientAddress')).toHaveValue(
    '1 rue du Hautbois, Lyon',
  );
  await expect(page.locator('#accounting_document_clientContact')).toHaveValue(
    'client@example.com',
  );

  const lines = page.locator('#accounting-document-lines [data-line-row]');
  const before = await lines.count();
  await page.locator('#accounting-add-line').click();
  await page.locator('#accounting-add-line').click();
  await expect(lines).toHaveCount(before + 2);
  await lines.last().locator('[data-remove-line]').click();
  await expect(lines).toHaveCount(before + 1);
  // The form opens with one blank server-rendered line (required fields): removing it works the same way.
  await lines.first().locator('[data-remove-line]').click();
  await expect(lines).toHaveCount(before);

  const line = lines.last();
  await line.locator('input[id$="_label"]').fill('Concert de rue');
  await line.locator('input[id$="_unitPrice"]').fill('250');
  await line.locator('input[id$="_quantity"]').fill('2');
  await page.locator('form[name="accounting_document"] button[type="submit"]').click();

  await expect(page).toHaveURL(/\/desk\/files\/accounting\/documents\/\d+$/);
  await expect(page.locator('body')).toContainText('Concert de rue');
});
