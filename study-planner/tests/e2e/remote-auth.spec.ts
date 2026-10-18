import { test, expect } from '@playwright/test';

// Run against the isolated SQLite verification server; never use production credentials.
test('real API login persists across reload, logout revokes it, and login works again', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('remote-auth@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Verification123!');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
  await page.reload();
  await expect(page).toHaveURL(/\/dashboard$/);
  const result = await page.evaluate(async () => {
    const session = JSON.parse(localStorage.getItem('token')!);
    const logout = await fetch('/api/auth/logout', { method: 'POST', headers: { 'X-Study-Planner-Web': '1', Authorization: `Bearer ${session.token}`, Accept: 'application/json' } });
    const revoked = await fetch('/api/auth/me', { headers: { 'X-Study-Planner-Web': '1', Authorization: `Bearer ${session.token}`, Accept: 'application/json' } });
    localStorage.removeItem('token');
    return { logout: logout.status, revoked: revoked.status };
  });
  expect(result).toEqual({ logout: 204, revoked: 401 });
  await page.reload();
  await expect(page).toHaveURL(/\/login$/);
  await page.getByLabel('Email').fill('remote-auth@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Verification123!');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/dashboard$/);
});

test('real API invalid password shows a credential error', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('remote-auth@example.test');
  await page.getByLabel('Password', { exact: true }).fill('WrongPassword!');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.locator('#email-error')).toContainText('credentials do not match');
  await expect(page).toHaveURL(/\/login$/);
});

test('session restores after a temporary offline startup', async ({ page }) => {
  const login = await page.request.post('/api/auth/login', { headers: { 'X-Study-Planner-Web': '1' }, data: { email: 'remote-auth@example.test', password: 'Verification123!' } });
  expect(login.ok()).toBeTruthy();
  const session = (await login.json()).data;
  await page.addInitScript(value => localStorage.setItem('token', JSON.stringify(value)), { token: session.token, refreshToken: session.refresh_token });
  await page.route('**/api/auth/me', route => route.abort('failed'));
  await page.goto('/login');
  await expect(page.getByLabel('Email')).toBeVisible();
  expect(await page.evaluate(() => localStorage.getItem('token'))).not.toBeNull();
  await page.unroute('**/api/auth/me');
  await page.evaluate(() => window.dispatchEvent(new Event('online')));
  await expect(page).toHaveURL(/\/dashboard$/);
});

test('expired access responses trigger one refresh and retry authenticated requests', async ({ page }) => {
  const login = await page.request.post('/api/auth/login', { headers: { 'X-Study-Planner-Web': '1' }, data: { email: 'remote-auth@example.test', password: 'Verification123!' } });
  const session = (await login.json()).data;
  await page.addInitScript(value => localStorage.setItem('token', JSON.stringify(value)), { token: session.token, refreshToken: session.refresh_token });
  let renewals = 0;
  await page.route('**/api/auth/refresh', async route => { renewals++; await route.continue(); });
  let expired = false;
  await page.route('**/api/auth/me', async route => {
    if (!expired) {
      expired = true;
      await route.fulfill({ status: 401, json: { message: 'Unauthenticated' } });
    } else await route.continue();
  });
  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Dashboard', exact: true }).first()).toBeVisible();
  expect(renewals).toBe(1);
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('token')!).token)).toBe('cookie-session');
});
