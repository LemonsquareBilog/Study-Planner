import { test, expect } from '@playwright/test';
import { setupApp, loginAsStudent, loginAsAdmin } from './test-helpers';

test.describe('Audit: Authentication & Access Control', () => {
  test.beforeEach(async ({ page }) => {
    await setupApp(page);
  });

  test('Guest protection: unauthenticated users redirected to login', async ({ page }) => {
    // Attempt accessing protected routes directly
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);

    await page.goto('/subjects');
    await expect(page).toHaveURL(/\/login/);

    await page.goto('/tasks');
    await expect(page).toHaveURL(/\/login/);

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/login/);
  });

  test('Login validation: invalid credentials display error message', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill('nonexistent@user.test');
    await page.locator('#password').fill('WrongPassword123!');
    await page.locator('button[type="submit"]').click();

    // Verify error alert is displayed
    const errorAlert = page.locator('[role="alert"]');
    await expect(errorAlert).toBeVisible({ timeout: 5000 });
  });

  test('Student Authentication: valid credentials log in and reach dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill('student@studyplanner.test');
    await page.locator('#password').fill('StudentPassword123!');
    await page.locator('button[type="submit"]').click();

    await expect(page).toHaveURL(/\/dashboard/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /dashboard/i })).toBeVisible();
    await expect(page.getByText('Demo Student').first()).toBeVisible();
  });

  test('Admin Authentication: valid credentials log in and reach admin panel', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#email').fill('admin@studyplanner.test');
    await page.locator('#password').fill('AdminPassword123!');
    await page.locator('button[type="submit"]').click();

    await expect(page).toHaveURL(/\/admin/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /admin dashboard/i })).toBeVisible();
    await expect(page.getByText('System Administrator')).toBeVisible();
  });

  test('Role-based access control: student cannot access admin routes', async ({ page }) => {
    await loginAsStudent(page);
    await page.goto('/admin');
    // Router guard redirects non-admin back to /dashboard
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
  });

  test('Registration page: form structure and navigation links are valid', async ({ page }) => {
    await page.goto('/register');
    await expect(page.getByRole('heading', { name: /create your account/i })).toBeVisible();

    await expect(page.locator('#name')).toBeVisible();
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('#password_confirmation')).toBeVisible();
    await expect(page.getByRole('button', { name: /create account/i })).toBeVisible();

    // Test sign in backlink
    const signInLink = page.getByRole('link', { name: /sign in/i });
    await expect(signInLink).toBeVisible();
    await signInLink.click();
    await expect(page).toHaveURL(/\/login/);
  });

  test('Logout audit: logging out clears session and redirects to login', async ({ page }) => {
    await loginAsStudent(page);
    const logoutBtn = page.getByRole('button', { name: /log out/i });
    await expect(logoutBtn).toBeVisible();
    await logoutBtn.click();

    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
    // Attempting to visit dashboard again redirects to login
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });
});
