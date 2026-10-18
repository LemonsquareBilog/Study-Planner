import { test, expect } from '@playwright/test';

for (const viewport of [{ width: 1440, height: 900 }, { width: 393, height: 851 }]) {
  test(`renders the login page at ${viewport.width}px`, async ({ page }) => {
    await page.setViewportSize(viewport);
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign in', exact: true })).toBeVisible();
    expect(errors).toEqual([]);
  });
}

test('shows a loading message while restoring a session', async ({ page }) => {
  let release!: () => void;
  const pending = new Promise<void>(resolve => { release = resolve; });
  await page.addInitScript(() => localStorage.setItem('token', JSON.stringify({ token: 'cookie-session', refreshToken: null })));
  await page.route('**/api/auth/me', async route => {
    await pending;
    await route.fulfill({ status: 401, json: { message: 'Unauthenticated' } });
  });
  try {
    await page.goto('/login');
    await expect(page.getByRole('status')).toHaveText('Loading Study Planner…');
  } finally {
    release();
  }
  await expect(page.getByLabel('Email')).toBeVisible();
});

test('reports an unreachable backend without switching to demo data', async ({ page }) => {
  await page.route('**/api/auth/login', route => route.abort('failed'));
  await page.goto('/login');
  await page.getByLabel('Email').fill('student@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Password123!');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('Unable to reach the server');
  expect(await page.evaluate(() => localStorage.getItem('sp_demo_mode'))).toBeNull();
  await expect(page).toHaveURL(/\/login$/);
});

test('an expired native session returns to login instead of the dashboard', async ({ page }) => {
  await page.addInitScript(() => {
    Object.assign(window, {
      androidBridge: {},
      Capacitor: {
        PluginHeaders: [
          { name: 'App', methods: [{ name: 'addListener', rtype: 'callback' }, { name: 'removeListener', rtype: 'promise' }] },
          { name: 'AuthTokenStorage', methods: ['get', 'set', 'remove'].map(name => ({ name, rtype: 'promise' })) },
        ],
        nativeCallback: () => 'browser-test',
        nativePromise: () => Promise.resolve({}),
      },
    });
    localStorage.setItem('token', 'restored-test-token');
  });
  await page.route('**/api/**', route => {
    const path = new URL(route.request().url()).pathname;
    if (path === '/api/auth/me') {
      return route.fulfill({ json: { data: { id: 1, name: 'Session Test', email: 'session@example.test', role: 'student', is_active: true } } });
    }
    if (path === '/api/student/dashboard') {
      return route.fulfill({ json: { data: { progress: {}, recommendations: [], upcoming_tasks: [], upcoming_sessions: [], unread_notifications: 0 } } });
    }
    return route.fulfill({ status: 401, json: { message: 'Unauthenticated' } });
  });
  await page.goto('/#/dashboard');
  await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible();
  await page.goto('/#/tasks');
  await expect(page.getByLabel('Email')).toBeVisible();
  await expect(page).toHaveURL(/\/#\/login$/);
  expect(await page.evaluate(() => localStorage.getItem('token'))).toBeNull();
});
