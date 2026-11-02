import { test, expect, type Page } from '@playwright/test';

const admin = { id: 1, name: 'QA Administrator', email: 'admin@example.test', role: 'admin', is_active: true };
const student = { id: 2, name: 'QA Student', email: 'student@example.test', role: 'student', is_active: true };
const paginate = (data: unknown[], page = 1, total = data.length) => ({ data, current_page: page, last_page: Math.max(1, Math.ceil(total / 20)), total, from: data.length ? (page - 1) * 20 + 1 : 0, to: (page - 1) * 20 + data.length, per_page: 20 });
async function session(page: Page, role = 'admin') {
  await page.addInitScript(() => localStorage.setItem('token', JSON.stringify({ token: 'cookie-session', refreshToken: null })));
  await page.route('**/api/**', route => {
    const path = new URL(route.request().url()).pathname;
    if (path === '/api/auth/me') return route.fulfill({ json: { data: role === 'admin' ? admin : student } });
    return route.fulfill({ json: { data: [] } });
  });
}

for (const width of [1440, 390]) {
  test(`Users renders populated data and editing at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 });
    const errors: string[] = [];
    page.on('pageerror', e => errors.push(e.message));
    await session(page);
    await page.route('**/api/admin/users**', route => route.fulfill({ json: paginate([student]) }));
    await page.goto('/admin/users');
    await expect(page.getByText('QA Student', { exact: true }).filter({ visible: true }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /add.*user/i })).toHaveCount(0);
    await page.getByRole('button', { name: 'Edit user', exact: true }).filter({ visible: true }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByLabel('Name', { exact: true })).toHaveValue('QA Student');
    expect(errors).toEqual([]);
  });
}

test('Users has recoverable error and empty states', async ({ page }) => {
  await session(page);
  let failed = true;
  await page.route('**/api/admin/users**', route => route.fulfill(failed ? { status: 503, json: { message: 'Unavailable' } } : { json: paginate([]) }));
  await page.goto('/admin/users');
  await expect(page.getByRole('alert')).toContainText('Could not load users');
  failed = false;
  await page.getByRole('button', { name: 'Retry', exact: true }).click();
  await expect(page.getByText('No users found.', { exact: true }).first()).toBeVisible();
});

test('Book filter uses the existing ebook contract', async ({ page }) => {
  await session(page);
  const requests: string[] = [];
  await page.route('**/api/admin/resources**', route => {
    if (route.request().url().includes('/options')) return route.fulfill({ json: { data: { subjects: [], topics: [] } } });
    requests.push(route.request().url());
    return route.fulfill({ json: paginate([{ id: 1, title: 'QA Ebook', resource_type: 'ebook', subjects: [], topics: [], url: 'https://example.test/book', is_active: true, difficulty: 'beginner' }]) });
  });
  await page.goto('/admin/resources');
  await page.getByLabel('Filter by type').selectOption('ebook');
  await expect.poll(() => requests.some(url => url.includes('resource_type=ebook'))).toBe(true);
  await expect(page.getByText('QA Ebook', { exact: true }).first()).toBeVisible();
});

test('Settings distinguishes failure, empty, populated, failed save and success', async ({ page }) => {
  await session(page);
  let mode = 'failed';
  await page.route('**/api/admin/system-settings**', route => {
    if (route.request().method() === 'PATCH') return route.fulfill(mode === 'save-failed'
      ? { status: 422, json: { message: 'Invalid value' } }
      : { json: { data: { id: 1, key: 'study_limit', value: '42', typed_value: 42, type: 'integer' } } });
    if (mode === 'failed') return route.fulfill({ status: 503, json: { message: 'Unavailable' } });
    return route.fulfill({ json: { data: mode === 'empty' ? [] : [{ id: 1, key: 'study_limit', value: '30', typed_value: 30, type: 'integer' }] } });
  });
  await page.goto('/admin/settings');
  await expect(page.getByRole('alert')).toContainText('Could not load settings');
  mode = 'empty';
  await page.getByRole('button', { name: 'Retry', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('No system settings');
  mode = 'populated';
  await page.reload();
  await page.getByLabel('study_limit', { exact: true }).fill('42');
  mode = 'save-failed';
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('Your input is preserved');
  await expect(page.getByLabel('study_limit', { exact: true })).toHaveValue('42');
  mode = 'saved';
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await expect(page.getByText('Saved', { exact: true })).toBeVisible();
});

test('invalid stored session recovers to login without requesting authorization', async ({ page }) => {
  const errors: string[] = [];
  const authorization: string[] = [];
  page.on('pageerror', e => errors.push(e.message));
  await page.addInitScript(() => localStorage.setItem('token', '{"token":"cookie-session",broken'));
  page.on('request', r => { if (r.headers().authorization) authorization.push(r.headers().authorization); });
  await page.goto('/login');
  await expect(page.getByLabel('Email')).toBeVisible();
  expect(await page.evaluate(() => localStorage.getItem('token'))).toBeNull();
  expect(authorization).toEqual([]);
  expect(errors).toEqual([]);
});

test('quiz pages and history details load on demand', async ({ page }) => {
  await session(page, 'student');
  const calls: string[] = [];
  await page.route('**/api/student/quizzes**', route => {
    const url = new URL(route.request().url()); calls.push(url.pathname + url.search);
    const number = Number(url.searchParams.get('page') || 1);
    return route.fulfill({ json: paginate([{ id: number, title: `Quiz page ${number}`, topic_id: 1, is_active: true, passing_score: 70 }], number, 25) });
  });
  await page.route('**/api/student/quiz-attempts**', route => {
    const path = new URL(route.request().url()).pathname; calls.push(path);
    const attempt = { id: 9, quiz_id: 1, percentage: 100, score: 1, total_questions: 1, completed_at: '2026-11-03T00:00:00Z', quiz: { title: 'History fixture' } };
    return route.fulfill({ json: path.endsWith('/9') ? { data: { ...attempt, answers: [{ id: 1, is_correct: true, selected_answer: 'A', question: { question: 'Detail loaded', correct_answer: 'A', option_a: 'Correct' } }] } } : paginate([attempt]) });
  });
  await page.goto('/quizzes');
  await expect(page.getByText('Quiz page 1', { exact: true })).toBeVisible();
  expect(calls.some(path => path.includes('quiz-attempts'))).toBe(false);
  await page.getByRole('button', { name: 'Next', exact: true }).click();
  await expect(page.getByText('Quiz page 2', { exact: true })).toBeVisible();
  await page.getByRole('tab', { name: /Completed Quizzes/ }).click();
  await expect(page.getByText('History fixture', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Review', exact: true }).click();
  await expect(page.getByText('Detail loaded', { exact: true })).toBeVisible();
  expect(calls).toContain('/api/student/quiz-attempts/9');
});

test('task pagination traverses three pages and search resets the page', async ({ page }) => {
  await session(page, 'student');
  const requests: URL[] = [];
  await page.route('**/api/student/tasks**', route => {
    const url = new URL(route.request().url()); requests.push(url);
    const current = Number(url.searchParams.get('page') || 1);
    return route.fulfill({ json: paginate([{ id: current, title: `Tasks page ${current}`, status: 'pending', priority: 'high', deadline: '2026-12-01T12:00:00Z' }], current, 45) });
  });
  await page.goto('/tasks');
  await expect(page.getByText('Tasks page 1', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Next page', exact: true }).click();
  await expect(page.getByText('Tasks page 2', { exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Next page', exact: true }).click();
  await expect(page.getByText('Tasks page 3', { exact: true })).toBeVisible();
  await page.getByLabel('Search tasks').fill('Assignment');
  await expect(page.getByText('Tasks page 1', { exact: true })).toBeVisible();
  expect(requests.at(-1)?.searchParams.get('search')).toBe('Assignment');
  await page.getByLabel('Sort tasks').selectOption('priority');
  await expect.poll(() => requests.at(-1)?.searchParams.get('sort')).toBe('priority');
});

test('quiz retry keeps its issued identity and a retake obtains a new one', async ({ page }) => {
  await session(page, 'student');
  let issued = 0;
  const submissions: any[] = [];
  await page.route('**/api/student/quizzes/1', route => route.fulfill({ json: { data: {
    id: 1, title: 'Retry fixture', passing_score: 70, submission_id: `issued-${++issued}`,
    questions: [{ id: 1, question: 'Choose A', option_a: 'A', option_b: 'B', option_c: 'C', option_d: 'D' }],
  } } }));
  await page.route('**/api/student/quizzes/1/submit', route => {
    submissions.push(route.request().postDataJSON());
    if (submissions.length === 1) return route.abort('failed');
    return route.fulfill({ json: { data: { id: submissions.length, score: 1, total_questions: 1, percentage: 100, answers: [] } } });
  });
  await page.goto('/quizzes/1');
  await page.getByRole('button', { name: 'Start quiz', exact: true }).click();
  await page.getByLabel('Option A', { exact: true }).check();
  await page.getByRole('button', { name: 'Submit quiz', exact: true }).click();
  await expect(page.getByText('Your quiz could not be submitted.', { exact: false })).toBeVisible();
  await page.getByRole('button', { name: 'Submit quiz', exact: true }).click();
  await expect(page.getByText('Quiz Passed!', { exact: true })).toBeVisible();
  expect(submissions[0]).toEqual(submissions[1]);
  expect(submissions[0].submission_id).toBe('issued-1');
  await page.getByRole('button', { name: 'Retake Quiz', exact: true }).click();
  await page.getByRole('button', { name: 'Start quiz', exact: true }).click();
  await page.getByRole('button', { name: 'Submit quiz', exact: true }).click();
  await expect(page.getByText('Quiz Passed!', { exact: true })).toBeVisible();
  expect(submissions[2].submission_id).toBe('issued-2');
});
