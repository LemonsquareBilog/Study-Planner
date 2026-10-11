import { test, expect } from '@playwright/test';

test.describe('Login Page - Demo Access Removal Audit', () => {
  test.beforeEach(async ({ page }) => {
    page.on('console', msg => console.log(`Console ${msg.type()}: ${msg.text()}`));
    page.on('pageerror', error => console.log(`Page error: ${error.message}`));
    await page.goto('/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
  });

  test('should NOT show the Interactive Demo Access section', async ({ page }) => {
    // The "Interactive Demo Access" label must not exist
    await expect(page.getByText('Interactive Demo Access')).not.toBeVisible();
    // The "1-Tap Demo" badge must not exist
    await expect(page.getByText('1-Tap Demo')).not.toBeVisible();
  });

  test('should NOT show Demo Student or Demo Admin buttons', async ({ page }) => {
    await expect(page.getByRole('button', { name: /demo student/i })).not.toBeVisible();
    await expect(page.getByRole('button', { name: /demo admin/i })).not.toBeVisible();
  });

  test('should NOT display demo credentials reference card', async ({ page }) => {
    await expect(page.getByText('Demo Accounts Reference')).not.toBeVisible();
    await expect(page.getByText('student@studyplanner.test')).not.toBeVisible();
    await expect(page.getByText('admin@studyplanner.test')).not.toBeVisible();
    await expect(page.getByText('StudentPassword123!')).not.toBeVisible();
    await expect(page.getByText('AdminPassword123!')).not.toBeVisible();
  });

  test('should still have a functional login form', async ({ page }) => {
    // Email and password fields should be present
    const emailInput = page.locator('#email');
    const passwordInput = page.locator('#password');
    const submitButton = page.getByRole('button', { name: /sign in/i });

    await expect(emailInput).toBeVisible();
    await expect(passwordInput).toBeVisible();
    await expect(submitButton).toBeVisible();
  });

  test('should still have a register link', async ({ page }) => {
    const registerLink = page.getByRole('link', { name: /create a new student account/i });
    await expect(registerLink).toBeVisible();
  });

  test('should have the Study Planner heading', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Study Planner' })).toBeVisible();
  });
});
