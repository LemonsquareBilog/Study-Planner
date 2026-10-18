import { test, expect } from '@playwright/test';

test('password controls retain input, expose accessible strength, and render backend rejection', async ({ page }) => {
  await page.goto('/register');
  const password = page.getByLabel('Password', { exact: true });
  const confirmation = page.getByLabel('Confirm password', { exact: true });
  await password.fill('password');
  await expect(page.getByRole('meter')).toHaveAttribute('aria-valuetext', 'Weak');
  await page.getByRole('button', { name: 'Show password', exact: true }).click();
  await expect(password).toHaveAttribute('type', 'text');
  await expect(password).toHaveValue('password');
  await page.getByRole('button', { name: 'Hide password', exact: true }).press('Enter');
  await expect(password).toHaveAttribute('type', 'password');
  await confirmation.fill('password');
  await page.getByRole('button', { name: 'Show confirm password', exact: true }).click();
  await expect(confirmation).toHaveAttribute('type', 'text');
  await expect(confirmation).toHaveValue('password');
  await page.getByLabel('Full name').fill('Policy Test');
  await page.getByLabel('Email address').fill('policy-ui@example.test');
  await page.getByRole('button', { name: 'Create account', exact: true }).click();
  await expect(page.locator('#password-error')).toContainText('15 characters');
  await expect(page.locator('#password-error')).not.toContainText('["');
  await password.fill('7zN!q3@Hv9#Lk2$Df8%Bs6');
  await expect(page.getByRole('meter')).toHaveAttribute('aria-valuetext', 'Strong');
});
