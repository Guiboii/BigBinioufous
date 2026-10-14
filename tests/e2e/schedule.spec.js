// /schedule mini calendar (schedule.js): which month opens first, month navigation, and the phone layout.
// The clock is fixed because the opening month depends on today's date. A visitor only sees the public demo events:
// the concerts of 2026-09-20 and 2026-10-02. Month labels are short ("OCT 2026").
import { test, expect } from '@playwright/test';
import { MOBILE } from './support/helpers.js';

const title = (page) => page.locator('.mini-calendar__title');

test('opens on the current month when it has events', async ({ page }) => {
  await page.clock.setFixedTime(new Date('2026-10-14T10:00:00'));
  await page.goto('/schedule');
  await expect(title(page)).toHaveText(/oct\.? 2026/i);
});

test('skips to the next month with events when the current one is empty', async ({ page }) => {
  await page.clock.setFixedTime(new Date('2026-08-01T10:00:00'));
  await page.goto('/schedule');
  await expect(title(page)).toHaveText(/sept\.? 2026/i);
});

test('a #month-XX link opens straight on that month', async ({ page }) => {
  await page.clock.setFixedTime(new Date('2026-10-14T10:00:00'));
  await page.goto('/schedule#month-09');
  await expect(title(page)).toHaveText(/sept\.? 2026/i);
});

test('next/previous buttons change month, and an event day links to its month', async ({
  page,
}) => {
  await page.clock.setFixedTime(new Date('2026-10-14T10:00:00'));
  await page.goto('/schedule');

  await page.locator('[data-nav="next"]').click();
  await expect(title(page)).toHaveText(/nov\.? 2026/i);

  await page.locator('[data-nav="prev"]').click();
  await page.locator('[data-nav="prev"]').click();
  await expect(title(page)).toHaveText(/sept\.? 2026/i);
  await page.locator('.mini-calendar__day--event', { hasText: /^20$/ }).click();
  await expect(page).toHaveURL(/#month-09$/);
});

test.describe('on a phone', () => {
  test.use(MOBILE);

  test('the 3D scene is skipped and the events stay readable', async ({ page }) => {
    await page.clock.setFixedTime(new Date('2026-10-14T10:00:00'));
    await page.goto('/schedule');
    await expect(page.locator('.mascotte')).toBeHidden();
    await expect(page.locator('#month-10')).toBeVisible();
    await expect(title(page)).toHaveText(/oct\.? 2026/i);
  });
});
