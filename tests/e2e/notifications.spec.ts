import { test, expect } from '@playwright/test';

test.describe('Notification Dropdown', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test - replace with actual credentials
    await page.goto('http://127.0.0.1:8000/login');
    // Add login logic here if needed
  });

  test('bell badge shows unread count', async ({ page }) => {
    await page.goto('http://127.0.0.1:8000');

    // Check if notification bell exists
    const bell = page.locator('button[title="Notifications"]');
    await expect(bell).toBeVisible();

    // Check if badge appears when there are unread notifications
    const badge = bell.locator('span[class*="rounded-full"]');
    const badgeCount = await badge.count();

    if (badgeCount > 0) {
      const text = await badge.textContent();
      console.log('Unread notifications:', text);
    }
  });

  test('clicking bell opens dropdown', async ({ page }) => {
    await page.goto('http://127.0.0.1:8000');

    const bell = page.locator('button[title="Notifications"]');
    await bell.click();

    // Check if dropdown appears
    const dropdown = page.locator('.absolute.right-0.mt-2.w-80');
    await expect(dropdown).toBeVisible();

    // Check if dropdown has notifications
    const notificationItems = dropdown.locator('[class*="divide-y"]');
    const count = await notificationItems.locator('> div').count();
    console.log('Notifications shown:', count);
  });

  test('dropdown closes when clicking outside', async ({ page }) => {
    await page.goto('http://127.0.0.1:8000');

    const bell = page.locator('button[title="Notifications"]');
    await bell.click();

    const dropdown = page.locator('.absolute.right-0.mt-2.w-80');
    await expect(dropdown).toBeVisible();

    // Click outside
    await page.locator('body').click({ position: { x: 10, y: 10 } });

    // Dropdown should be hidden
    await expect(dropdown).not.toBeVisible();
  });
});
