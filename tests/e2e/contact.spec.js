// /contact form sent via fetch (contact.js): what the page shows for each server answer.
// Server answers are faked: the rules behind them (CSRF, honeypot, timing trap, 1 message/minute/IP) are PHPUnit's job
// (ContactControllerTest), and a real send here would trip that per-IP limit on the next run.
import { test, expect } from '@playwright/test';

async function fillAndSend(page, answer) {
  await page.route('**/contact', (route) =>
    route.request().method() === 'POST' ? route.fulfill(answer) : route.continue(),
  );
  await page.goto('/contact');
  await page.locator('#contact-name').fill('Camille');
  await page.locator('#contact-email').fill('camille@example.com');
  await page.locator('#contact-message').fill('Bonjour !');
  await page.locator('#contact-form button[type="submit"]').click();
}

test('a sent message shows a thank-you and empties the form, without leaving the page', async ({
  page,
}) => {
  await fillAndSend(page, { status: 200, json: { success: true } });

  await expect(page.locator('#contact-status')).toHaveText('Message envoyé, merci !');
  await expect(page.locator('#contact-status')).not.toHaveClass(/is-error/);
  await expect(page.locator('#contact-message')).toHaveValue('');
  await expect(page).toHaveURL(/\/contact$/);
});

test('a refused message shows the matching error and keeps what was typed', async ({ page }) => {
  await fillAndSend(page, { status: 422, json: { error: 'invalid_email' } });

  await expect(page.locator('#contact-status')).toHaveText('Adresse email invalide.');
  await expect(page.locator('#contact-status')).toHaveClass(/is-error/);
  await expect(page.locator('#contact-message')).toHaveValue('Bonjour !');
});
