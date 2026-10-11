import { Page, expect } from '@playwright/test';

/**
 * Ensures the application is running in local demo mode with fully seeded data.
 */
export async function setupApp(page: Page) {
  await page.addInitScript(() => {
    localStorage.setItem('sp_demo_mode', 'true');
  });
}

/**
 * Logs in as demo student and verifies landing on dashboard.
 */
export async function loginAsStudent(page: Page) {
  await setupApp(page);
  await page.goto('/login');
  await page.locator('#email').fill('student@studyplanner.test');
  await page.locator('#password').fill('StudentPassword123!');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/dashboard/, { timeout: 15000 });
}

/**
 * Logs in as demo administrator and verifies landing on admin panel.
 */
export async function loginAsAdmin(page: Page) {
  await setupApp(page);
  await page.goto('/login');
  await page.locator('#email').fill('admin@studyplanner.test');
  await page.locator('#password').fill('AdminPassword123!');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/admin/, { timeout: 15000 });
}
