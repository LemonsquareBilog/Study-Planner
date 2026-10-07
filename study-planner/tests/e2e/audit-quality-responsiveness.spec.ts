import { test, expect } from '@playwright/test';
import { setupApp, loginAsStudent, loginAsAdmin } from './test-helpers';

test.describe('Audit: Quality, Responsiveness & Platform Integrity', () => {
  test('Mobile Viewport Audit: responsive layout and mobile drawer navigation', async ({ page }) => {
    // Emulate mobile smartphone viewport
    await page.setViewportSize({ width: 390, height: 844 });
    await loginAsStudent(page);

    // Verify desktop nav is hidden and mobile toggle button is visible
    const menuToggle = page.getByRole('button', { name: /toggle navigation menu/i });
    await expect(menuToggle).toBeVisible();

    // Open mobile navigation menu
    await menuToggle.click();
    const mobileNav = page.getByRole('navigation', { name: /mobile student navigation/i });
    await expect(mobileNav).toBeVisible();

    // Verify key mobile navigation links
    await expect(mobileNav.getByRole('link', { name: 'Dashboard' })).toBeVisible();
    await expect(mobileNav.getByRole('link', { name: 'Subjects' })).toBeVisible();
    await expect(mobileNav.getByRole('link', { name: 'Tasks' })).toBeVisible();
    await expect(mobileNav.getByRole('link', { name: 'Quizzes' })).toBeVisible();

    // Navigate via mobile link
    await mobileNav.getByRole('link', { name: 'Tasks' }).click();
    await expect(page).toHaveURL(/\/tasks/);
    await expect(page.getByRole('heading', { name: 'Tasks' })).toBeVisible();
  });

  test('Console Error Audit: route navigation produces zero unhandled runtime exceptions', async ({ page }) => {
    const errorLogs: string[] = [];
    page.on('pageerror', (err) => {
      errorLogs.push(`PageError: ${err.message}`);
    });

    await loginAsStudent(page);

    const studentRoutes = [
      '/dashboard',
      '/subjects',
      '/tasks',
      '/study-plan',
      '/quizzes',
      '/recommendations',
      '/progress',
      '/notifications',
    ];

    for (const route of studentRoutes) {
      await page.goto(route);
      await expect(page).toHaveURL(new RegExp(route));
      await page.waitForLoadState('domcontentloaded');
    }

    // Fail if any unhandled page errors were recorded
    expect(errorLogs).toEqual([]);
  });

  test('Semantic Structure Audit: headings and primary interactive landmarks exist', async ({ page }) => {
    await loginAsStudent(page);

    // Verify main landmark
    const mainLandmark = page.locator('main[role="main"]');
    await expect(mainLandmark).toBeVisible();

    // Verify top header
    const header = page.locator('header');
    await expect(header).toBeVisible();

    // Verify primary heading exists
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
  });
});
