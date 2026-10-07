import { test, expect } from '@playwright/test';
import { setupApp, loginAsAdmin } from './test-helpers';

test.describe('Audit: Administrator Operations & Management', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('Admin Dashboard: renders metrics, system overview, and recent users', async ({ page }) => {
    await expect(page).toHaveURL(/\/admin/);
    await expect(page.getByRole('heading', { name: /admin dashboard/i })).toBeVisible();
    await expect(page.getByText('Platform overview and key metrics.')).toBeVisible();

    // Verify recent users section
    await expect(page.getByRole('heading', { name: /recent users/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /view all/i }).first()).toBeVisible();
  });

  test('Admin Resources: renders repository table, search, and type filters', async ({ page }) => {
    await page.getByRole('navigation', { name: 'Admin navigation' }).getByRole('link', { name: 'Resources' }).click();
    await expect(page).toHaveURL(/\/admin\/resources/);
    await expect(page.getByRole('heading', { name: 'Resources' })).toBeVisible();
    await expect(page.getByRole('button', { name: /add resource|create new resource/i })).toBeVisible();

    // Verify filters
    await expect(page.locator('input[placeholder="Search resources..."]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by type"]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by difficulty"]')).toBeVisible();

    // Verify table structure
    const table = page.locator('table[role="table"]');
    await expect(table).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Resource' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Type' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Difficulty' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Actions' })).toBeVisible();
  });

  test('Admin Subjects & Topics: manages academic structure and topics hierarchy', async ({ page }) => {
    await page.getByRole('navigation', { name: 'Admin navigation' }).getByRole('link', { name: 'Subjects' }).click();
    await expect(page).toHaveURL(/\/admin\/subjects/);
    await expect(page.getByRole('heading', { name: 'Subjects & Topics' })).toBeVisible();
    await expect(page.getByRole('button', { name: /add subject/i })).toBeVisible();

    // Verify subject card items
    const subjectCards = page.locator('article');
    await expect(subjectCards.first()).toBeVisible({ timeout: 10000 });
    const count = await subjectCards.count();
    expect(count).toBeGreaterThan(0);

    // Verify topics section inside subject card
    await expect(page.getByRole('heading', { name: /topics/i }).first()).toBeVisible();
  });

  test('Admin Users Management: renders user roster, roles, and status controls', async ({ page }) => {
    await page.getByRole('navigation', { name: 'Admin navigation' }).getByRole('link', { name: 'Users' }).click();
    await expect(page).toHaveURL(/\/admin\/users/);
    await expect(page.getByRole('heading', { name: 'Users' })).toBeVisible();

    // Verify search and role filters
    await expect(page.locator('input[placeholder="Search users..."]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by role"]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by status"]')).toBeVisible();

    // Verify user table headers
    const table = page.locator('table[role="table"]');
    await expect(table).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'User' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Role' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
  });

  test('Admin System Settings: displays application settings and threshold configurations', async ({ page }) => {
    await page.getByRole('navigation', { name: 'Admin navigation' }).getByRole('link', { name: 'Settings' }).click();
    await expect(page).toHaveURL(/\/admin\/settings/);
    await expect(page.getByRole('heading', { name: 'System Settings' })).toBeVisible();
    await expect(page.getByText('Configure application-wide settings and thresholds.')).toBeVisible();

    // Verify settings list items
    const settingsList = page.locator('.glass-card .divide-y > div');
    await expect(settingsList.first()).toBeVisible({ timeout: 10000 });
    const count = await settingsList.count();
    expect(count).toBeGreaterThan(0);
  });
});
