import { test, expect, type Page } from '@playwright/test';

const userA = { id: 1, name: 'First Student', email: 'first@example.test', role: 'student', is_active: true };
const userB = { id: 2, name: 'Second Student', email: 'second@example.test', role: 'student', is_active: true };
const dashboard = (task: string) => ({ data: {
  progress: { tasks: { total: 1, completed: 0, completion_rate: 0 }, quizzes: { attempts: 0, average_percentage: 0 }, study_sessions: { minutes_completed: 0 } },
  upcoming_tasks: [{ id: 1, title: task, deadline: '2026-12-01T12:00:00Z', priority: 'high' }],
  upcoming_sessions: [], recommendations: [], unread_notifications: 0,
} });
async function setup(page: Page, nextUser = userB) {
  await page.addInitScript(() => localStorage.setItem('token', JSON.stringify({token: 'cookie-session', refreshToken: null})));
  await page.route('**/api/**', route => {
    const path = new URL(route.request().url()).pathname;
    if (path === '/api/auth/me') return route.fulfill({ json: { data: userA } });
    if (path === '/api/auth/logout') return route.fulfill({ json: { data: null } });
    if (path === '/api/auth/login') return route.fulfill({ json: { data: { user: nextUser, token: 'cookie-session', refresh_token: 'cookie-session' } } });
    if (path === '/api/student/tasks') return route.fulfill({json: {data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0}});
    return route.fulfill({ json: { data: [] } });
  });
}
async function navigate(page: Page, name: string) {
  await page.getByRole('link', { name, exact: true }).filter({ visible: true }).first().click();
}

test('repeat dashboard visit immediately shows same-session data while fetching fresh content', async ({ page }) => {
  await setup(page);
  let count = 0;
  let release!: () => void;
  const pending = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/api/student/dashboard', async route => {
    count++;
    if (count === 2) { await pending; return route.fulfill({json: dashboard('Fresh task')}); }
    return route.fulfill({json: dashboard('Earlier task')});
  });
  try {
    await page.goto('/dashboard');
    await expect(page.getByText('Earlier task')).toBeVisible();
    await navigate(page, 'Tasks');
    await navigate(page, 'Dashboard');
    await expect(page.getByText('Earlier task')).toBeVisible();
    await expect(page.getByRole('status')).toContainText('Updating dashboard');
    expect(count).toBe(2);
    release();
    await expect(page.getByText('Fresh task')).toBeVisible();
  } finally { release(); }
});

test('dashboard errors are visible and retry loads data', async ({ page }) => {
  await setup(page);
  let fail = true;
  await page.route('**/api/student/dashboard', route => fail ? route.fulfill({status: 503, json: {message:'Unavailable'}}) : route.fulfill({json: dashboard('Retry task')}));
  await page.goto('/dashboard');
  await expect(page.getByRole('alert')).toContainText('Could not load the dashboard');
  await expect(page.getByText('No upcoming tasks.')).toHaveCount(0);
  fail = false;
  await page.getByRole('button', {name: 'Retry', exact: true}).click();
  await expect(page.getByText('Retry task')).toBeVisible();
});

for (const nextUser of [userA, userB]) {
 test(`logout and login as user ${nextUser.id} reject cached data and late old responses`, async ({ page }) => {
  await setup(page, nextUser);
  let call = 0;
  let releaseOld!: () => void;
  let releaseNew!: () => void;
  const oldPending = new Promise<void>(resolve => { releaseOld = resolve; });
  const newPending = new Promise<void>(resolve => { releaseNew = resolve; });
  await page.route('**/api/student/dashboard', async route => {
    call++;
    if (call === 1) return route.fulfill({json: dashboard('First private task')});
    if (call === 2) { await oldPending; return route.fulfill({json: dashboard('Late old private task')}); }
    await newPending; return route.fulfill({json: dashboard('Second private task')});
  });
  try {
    await page.goto('/dashboard');
    await expect(page.getByText('First private task')).toBeVisible();
    await navigate(page, 'Tasks');
    await navigate(page, 'Dashboard');
    await expect(page.getByText('First private task')).toBeVisible();
    await page.getByRole('button', {name: 'Log out', exact: true}).filter({visible: true}).first().click();
    await expect(page.getByLabel('Email')).toBeVisible();
    await page.getByLabel('Email').fill('second@example.test');
    await page.getByLabel('Password', {exact: true}).fill('SecondPassword123!');
    await page.getByRole('button', {name:'Sign in',exact:true}).click();
    await expect(page.getByRole('heading', {name: 'Dashboard', exact: true})).toBeVisible();
    await expect(page.getByText('First private task')).toHaveCount(0);
    releaseOld();
    await expect(page.getByText('Late old private task')).toHaveCount(0);
    releaseNew();
    await expect(page.getByText('Second private task')).toBeVisible();
    await navigate(page, 'Tasks');
    await navigate(page, 'Dashboard');
    await expect(page.getByText('Late old private task')).toHaveCount(0);
  } finally { releaseOld(); releaseNew(); }
});
}
