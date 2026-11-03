import { test, expect, type Page } from '@playwright/test';

// Controlled API delays make these navigation checks reproducible and keep
// the suite independent of real accounts and production data.
const user = { id: 1, name: 'Navigation Student', email: 'nav@example.test', role: 'student', is_active: true };
const summary = {
  tasks: { total: 3, completed: 1, completion_rate: 33.33 },
  study_sessions: { total: 2, completed: 1, minutes_completed: 50 },
  quizzes: { attempts: 1, average_percentage: 70 },
};
const task = { id: 1, title: 'Navigation assignment', subject_id: 1, priority: 'high', status: 'pending', deadline: '2030-01-10T18:00:00Z', estimated_minutes: 50, subject: { id: 1, name: 'Mathematics', color: 'blue' } };
const dashboard = { progress: summary, recommendations: [], upcoming_tasks: [task], upcoming_sessions: [], unread_notifications: 0 };
const subjects = [{ id: 1, name: 'Mathematics', color: 'blue', difficulty: 2, topics_count: 1, is_active: true }];
const pagination = (data: unknown[]) => ({ data, current_page: 1, last_page: 1, total: data.length, from: 1, to: data.length });

async function restoreSession(page: Page) {
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.addInitScript(() => localStorage.setItem('token', JSON.stringify({ token: 'cookie-session', refreshToken: 'cookie-session' })));
}

function nav(page: Page, name: string) {
  return page.getByRole('navigation', { name: 'Student navigation', exact: true }).getByRole('link', { name, exact: true });
}

test.describe('Study Plan dates and phone layout', () => {
  test.use({ timezoneId: 'Asia/Manila' });

  test('uses the entered local deadline and keeps navigation below system bars', async ({ page }) => {
    await restoreSession(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.clock.install({ time: new Date('2030-01-09T16:30:00Z') });
    const plannedTask = { ...task, title: 'Midnight assignment', deadline: '2030-01-09T17:15:00Z',
      start_date: null, created_at: '2029-12-01T10:00:00Z' };
    const session = { id: 1, task_id: 1, task: plannedTask, subject: plannedTask.subject,
      scheduled_date: '2030-01-10T00:00:00.000000Z', start_time: '00:45:00', end_time: '01:15:00',
      duration_minutes: 30, status: 'scheduled' };
    await page.route('**/api/**', route => {
      const path = new URL(route.request().url()).pathname;
      if (path.endsWith('/student/tasks')) return route.fulfill({ json: pagination([plannedTask]) });
      const data = path.endsWith('/auth/me') ? user
        : path.endsWith('/student/dashboard') ? dashboard
        : path.endsWith('/student/study-plan') ? [session] : [];
      return route.fulfill({ json: { data } });
    });
    await page.goto('/dashboard');
    await page.evaluate(() => {
      document.documentElement.style.setProperty('--safe-area-inset-top', '45px');
      document.documentElement.style.setProperty('--safe-area-inset-bottom', '24px');
    });
    const menu = page.getByRole('button', { name: 'Toggle navigation menu' });
    await expect(menu).toBeVisible();
    expect((await menu.boundingBox())!.y).toBeGreaterThanOrEqual(45);
    await menu.click();
    const drawer = page.locator('#mobile-menu');
    expect((await drawer.getByRole('heading', { name: 'Navigation', exact: true }).boundingBox())!.y).toBeGreaterThanOrEqual(45);
    await drawer.getByRole('link', { name: 'Study Plan', exact: true }).click();
    await expect(page.getByLabel('Schedule from')).toHaveValue('2030-01-10');
    const card = page.locator('article').filter({ hasText: 'Midnight assignment' });
    await expect(card).toBeVisible();
    await expect(card).toContainText('1/10/2030, 1:15:00 AM');
    await expect(page.getByText('Task deadline:', { exact: false })).toContainText('1/10/2030, 1:15:00 AM');
    await expect(page.getByRole('heading', { name: /Study sessions:/ })).toContainText('January 10');
    await card.click();
    const dialog = page.getByRole('dialog', { name: 'Study task details' });
    await expect(dialog).toContainText('1/10/2030, 1:15:00 AM');
    await expect(dialog.getByText('Scheduled', { exact: true })).not.toBeVisible();
    await expect(dialog.getByText('Start date:', { exact: false })).not.toBeVisible();
    expect((await dialog.boundingBox())!.y).toBeGreaterThanOrEqual(45);
  });
});

test('notifications distinguish past due, upcoming, completed and historical notices', async ({ page }) => {
  await restoreSession(page);
  await page.setViewportSize({ width: 390, height: 844 });
  await page.clock.install({ time: new Date('2030-01-10T10:00:00Z') });
  const notice = (id: number, title: string, task: unknown) => ({ id: String(id), title, task,
    type: 'task_reminder', message: 'Task reminder', delivery_status: 'sent',
    created_at: '2030-01-09T09:00:00Z', read_at: null });
  const notices = [
    notice(1, 'Overdue assignment', { id: 1, deadline: '2030-01-10T17:59:00+08:00', status: 'pending' }),
    notice(2, 'Upcoming assignment', { id: 2, deadline: '2030-01-10T18:00:20+08:00', status: 'in_progress' }),
    notice(3, 'Completed assignment', { id: 3, deadline: '2030-01-09T10:00:00Z', status: 'completed' }),
    notice(4, 'Cancelled assignment', { id: 4, deadline: '2030-01-09T10:00:00Z', status: 'cancelled' }),
    notice(5, 'Old notice', null),
  ];
  await page.route('**/api/**', route => {
    const path = new URL(route.request().url()).pathname;
    return route.fulfill({ json: { data: path.endsWith('/auth/me') ? user
      : path.endsWith('/student/notifications') ? { ...pagination(notices), upcoming_deadlines: [] } : [] } });
  });
  await page.goto('/notifications');
  const badge = (title: string, label: string) => page.getByRole('heading', { name: title, exact: true }).locator('..').getByText(label, { exact: true });
  await expect(badge('Overdue assignment', 'Past due')).toBeVisible();
  await expect(badge('Upcoming assignment', 'Upcoming')).toBeVisible();
  await expect(badge('Completed assignment', 'Completed')).toBeVisible();
  await expect(badge('Cancelled assignment', 'Cancelled')).toBeVisible();
  await expect(badge('Old notice', 'Past notification')).toBeVisible();
  await page.clock.fastForward(31_000);
  await expect(badge('Upcoming assignment', 'Past due')).toBeVisible();
  await expect(page.getByText('Past due', { exact: true })).toHaveCount(2);
});

test('progress shows a retryable failure and renders topic scores after recovery', async ({ page }) => {
  await restoreSession(page);
  let fail = true;
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: user } });
    if (path.endsWith('/student/progress')) {
      if (fail) return route.fulfill({ status: 500, json: { message: 'Unavailable' } });
      return route.fulfill({ json: { data: { summary, topic_performance: [{
        topic_id: 1, topic_name: 'Algebra', subject_name: 'Math', avg_percentage: 65.5,
        best_score: 80.5, worst_score: 50.5, attempt_count: 2, classification: 'needs_review',
      }] } } });
    }
    return route.fulfill({ json: { data: [] } });
  });
  await page.goto('/progress');
  await expect(page.getByRole('alert')).toContainText('Unable to load your progress');
  await expect(page.getByRole('heading', { name: 'Overview', exact: true })).not.toBeVisible();
  fail = false;
  await page.getByRole('button', { name: 'Try again', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
  await expect(page.getByRole('cell', { name: '65.5%', exact: true })).toBeVisible();
  await expect(page.getByRole('cell', { name: '80.5% / 50.5%', exact: true })).toBeVisible();
  await expect(page.getByRole('alert')).not.toBeVisible();
  expect(errors).toEqual([]);
});

test('recent dashboard, subjects, and progress reopen without another API wait; refresh bypasses reuse', async ({ page }, testInfo) => {
  await restoreSession(page);
  const counts: Record<string, number> = {};
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    counts[path] = (counts[path] || 0) + 1;
    const data = path.endsWith('/auth/me') ? user
      : path.endsWith('/student/dashboard') ? dashboard
      : path.endsWith('/student/subjects') ? subjects
      : path.endsWith('/student/progress') ? { summary, topic_performance: [] } : null;
    if (path.endsWith('/student/dashboard')) await new Promise(resolve => setTimeout(resolve, 700));
    await route.fulfill({ json: { data } });
  });
  await page.goto('/dashboard');
  await expect(page.getByText(task.title, { exact: true })).toBeVisible();
  await nav(page, 'Subjects').click();
  await expect(page.getByRole('heading', { name: 'Mathematics', exact: true })).toBeVisible();
  await nav(page, 'Progress').click();
  await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();

  const started = Date.now();
  await nav(page, 'Dashboard').click();
  await expect(page.getByText(task.title, { exact: true })).toBeVisible();
  const revisitMs = Date.now() - started;
  expect(counts['/api/student/dashboard']).toBe(1);
  await nav(page, 'Subjects').click();
  await expect(page.getByRole('heading', { name: 'Mathematics', exact: true })).toBeVisible();
  expect(counts['/api/student/subjects']).toBe(1);
  await nav(page, 'Progress').click();
  await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
  expect(counts['/api/student/progress']).toBe(1);
  await page.getByRole('button', { name: 'Refresh progress' }).click();
  await expect.poll(() => counts['/api/student/progress']).toBe(2);
  await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
  await testInfo.attach('navigation-measurement', { body: JSON.stringify({ injectedDashboardDelayMs: 700, cachedDashboardRevisitMs: revisitMs, counts }), contentType: 'application/json' });
});

test('tasks render while subject options are still pending and edits invalidate the dashboard', async ({ page }) => {
  await restoreSession(page);
  let release!: () => void;
  const held = new Promise<void>(resolve => { release = resolve; });
  let completed = false;
  let dashboards = 0;
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: user } });
    if (path.endsWith('/student/dashboard')) {
      dashboards++;
      return route.fulfill({ json: { data: { ...dashboard, upcoming_tasks: completed ? [] : [task] } } });
    }
    if (path.endsWith('/student/subjects')) {
      await held;
      return route.fulfill({ json: { data: subjects } });
    }
    if (path.endsWith('/tasks/1/complete')) {
      completed = true;
      return route.fulfill({ json: { data: { ...task, status: 'completed' } } });
    }
    if (path.endsWith('/student/tasks')) return route.fulfill({ json: pagination([{ ...task, status: completed ? 'completed' : 'pending' }]) });
    return route.fulfill({ status: 404, json: {} });
  });
  try {
    await page.goto('/dashboard');
    await expect(page.getByText(task.title, { exact: true })).toBeVisible();
    await nav(page, 'Tasks').click();
    await expect(page.getByRole('cell', { name: task.title, exact: true })).toBeVisible();
  } finally {
    release();
  }
  await page.getByRole('button', { name: 'Mark complete' }).click();
  await expect(page.getByRole('cell', { name: 'Completed', exact: true })).toBeVisible();
  await nav(page, 'Dashboard').click();
  await expect(page.getByText(/No upcoming tasks/)).toBeVisible();
  expect(dashboards).toBe(2);
});

test('available quizzes render before the larger history response completes', async ({ page }) => {
  await restoreSession(page);
  let release!: () => void;
  const held = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: user } });
    if (path.endsWith('/student/quiz-attempts')) {
      await held;
      return route.fulfill({ json: { data: [] } });
    }
    return route.fulfill({ json: { data: [{ id: 1, title: 'Algebra practice', questions_count: 5, passing_score: 60, topic: { name: 'Algebra', subject: { name: 'Mathematics' } } }] } });
  });
  try {
    await page.goto('/quizzes');
    await expect(page.getByRole('heading', { name: 'Algebra practice', exact: true })).toBeVisible();
    await page.getByRole('tab', { name: /Completed Quizzes/ }).click();
    await expect(page.getByRole('status', { name: 'Loading quiz history' })).toBeVisible();
  } finally {
    release();
  }
  await expect(page.getByText('No completed quizzes yet')).toBeVisible();
});

test('signing out and signing in as another student never reuses the previous dashboard', async ({ page }) => {
  await restoreSession(page);
  let secondAccount = false;
  let dashboards = 0;
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: user } });
    if (path.endsWith('/auth/logout')) return route.fulfill({ status: 204 });
    if (path.endsWith('/auth/login')) {
      secondAccount = true;
      return route.fulfill({ json: { data: { token: 'cookie-session', refresh_token: 'cookie-session', user: { ...user, id: 2, name: 'Second Student' } } } });
    }
    dashboards++;
    return route.fulfill({ json: { data: { ...dashboard, upcoming_tasks: [{ ...task, title: secondAccount ? 'Second account task' : task.title }] } } });
  });
  await page.goto('/dashboard');
  await expect(page.getByText(task.title, { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Log out', exact: true }).click();
  await page.getByLabel('Email').fill('second@example.test');
  await page.getByLabel('Password', { exact: true }).fill('A long test password');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.getByText('Second account task', { exact: true })).toBeVisible();
  await expect(page.getByText(task.title, { exact: true })).toHaveCount(0);
  expect(dashboards).toBe(2);
});

for (const section of ['subjects', 'resources']) {
  test(`admin ${section} appear without waiting for edit-form options`, async ({ page }) => {
    await restoreSession(page);
    let release!: () => void;
    const held = new Promise<void>(resolve => { release = resolve; });
    await page.route('**/api/**', async route => {
      const path = new URL(route.request().url()).pathname;
      if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: { ...user, role: 'admin' } } });
      if (path.endsWith('/subjects/options') || path.endsWith('/resource-options')) {
        await held;
        return route.fulfill({ json: { data: path.endsWith('/resource-options') ? { subjects: [], topics: [] } : [] } });
      }
      if (section === 'subjects') return route.fulfill({ json: { data: [{ ...subjects[0], user, topics: [] }] } });
      return route.fulfill({ json: pagination([{ id: 1, title: 'Fast resource', resource_type: 'tutorial', difficulty: 'beginner', url: 'https://example.test/resource', is_active: true, subjects: [], topics: [] }]) });
    });
    try {
      await page.goto(`/admin/${section}`);
      await expect(page.getByText(section === 'subjects' ? 'Mathematics' : 'Fast resource', { exact: true }).first()).toBeVisible();
    } finally {
      release();
    }
  });
}

test('subject details appear before secondary topic/session requests finish', async ({ page }) => {
  await restoreSession(page);
  let release!: () => void;
  const held = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/api/**', async route => {
    const path = new URL(route.request().url()).pathname;
    if (path.endsWith('/auth/me')) return route.fulfill({ json: { data: user } });
    if (path.endsWith('/subjects/1')) return route.fulfill({ json: { data: { ...subjects[0], topics: [], tasks: [], resources: [] } } });
    await held;
    return route.fulfill({ json: { data: [] } });
  });
  try {
    await page.goto('/subjects/1');
    await expect(page.getByRole('heading', { name: 'Mathematics', exact: true })).toBeVisible();
  } finally {
    release();
  }
});
