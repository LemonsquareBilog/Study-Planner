import { test, expect } from '@playwright/test';
import { setupApp, loginAsStudent } from './test-helpers';

test.describe('Audit: Student Core Features & Workflows', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsStudent(page);
  });

  test('Student Dashboard: renders metrics, level progress, and upcoming tasks', async ({ page }) => {
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();

    // Verify greeting
    await expect(page.getByText(/welcome back/i)).toBeVisible();

    // Verify Level Status card and progress bar
    await expect(page.locator('#level-status-heading')).toBeVisible();
    await expect(page.locator('[role="progressbar"]').first()).toBeVisible();

    // Verify Key Stat cards
    await expect(page.getByText('Tasks Completed').first()).toBeVisible();
    await expect(page.getByText('Study Minutes').first()).toBeVisible();
    await expect(page.getByText('Quiz Attempts').first()).toBeVisible();
    await expect(page.getByText('Unread Alerts').first()).toBeVisible();

    // Verify Upcoming Tasks section
    await expect(page.locator('#upcoming-tasks-heading')).toBeVisible();
  });

  test('Subjects Catalog: lists academic subjects and navigates to subject detail', async ({ page }) => {
    await page.goto('/subjects');
    await expect(page.getByRole('heading', { name: 'Subjects' })).toBeVisible();
    await expect(page.getByRole('button', { name: /new subject/i })).toBeVisible();

    // Verify subject cards are rendered
    const subjectArticles = page.locator('article');
    await expect(subjectArticles.first()).toBeVisible({ timeout: 10000 });
    const count = await subjectArticles.count();
    expect(count).toBeGreaterThan(0);

    // Navigate to first subject's detail view
    const viewDetailsLink = subjectArticles.first().getByRole('link', { name: /view details/i });
    await viewDetailsLink.click();

    // Verify Subject Detail view
    await expect(page).toHaveURL(/\/subjects\/\d+/);
    await expect(page.locator('#topics-heading')).toBeVisible();
    await expect(page.getByRole('button', { name: /create new topic|add topic/i })).toBeVisible();
  });

  test('Tasks Management: renders tasks table, filters, and action controls', async ({ page }) => {
    await page.goto('/tasks');
    await expect(page.getByRole('heading', { name: 'Tasks' })).toBeVisible();
    await expect(page.getByRole('button', { name: /new task/i })).toBeVisible();

    // Verify filter dropdowns
    await expect(page.locator('select[aria-label="Filter by status"]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by priority"]')).toBeVisible();
    await expect(page.locator('select[aria-label="Filter by subject"]')).toBeVisible();

    // Verify table structure
    const table = page.locator('table[role="table"]');
    await expect(table).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Task' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Subject' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Priority' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Deadline' })).toBeVisible();
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible();
  });

  test('Study Plan: renders scheduling calendar and plan generation controls', async ({ page }) => {
    await page.goto('/study-plan');
    await expect(page.getByRole('heading', { name: 'Study Plan' })).toBeVisible();

    // Verify date range inputs and generate plan button
    await expect(page.locator('input[type="date"]').first()).toBeVisible();
    await expect(page.getByRole('button', { name: /generate study plan|generate plan/i })).toBeVisible();

    // Verify Tasks in plan section
    await expect(page.locator('#tasks-in-plan-heading')).toBeVisible();
  });

  test('Quizzes & Assessment: lists quizzes and enables quiz execution', async ({ page }) => {
    await page.goto('/quizzes');
    await expect(page.getByRole('heading', { name: 'Quizzes' })).toBeVisible();

    // Verify tabs
    await expect(page.getByRole('tab', { name: /available quizzes/i })).toBeVisible();
    await expect(page.getByRole('tab', { name: /completed quizzes/i })).toBeVisible();

    // Verify quiz cards
    const quizCards = page.locator('article');
    await expect(quizCards.first()).toBeVisible({ timeout: 10000 });

    // Open first quiz
    const startQuizBtn = quizCards.first().getByRole('link', { name: /start quiz/i });
    await startQuizBtn.click();

    // Verify Quiz preview screen with Start Quiz button
    await expect(page).toHaveURL(/\/quizzes\/\d+/);
    const startAction = page.getByRole('button', { name: /start quiz/i });
    await expect(startAction).toBeVisible();
    await startAction.click();

    // Verify question taking mode
    await expect(page.locator('.quiz-taking-form')).toBeVisible();
    await expect(page.getByRole('button', { name: /submit quiz|next question/i })).toBeVisible();
  });

  test('Recommendations: displays personalized weak areas and resources', async ({ page }) => {
    await page.goto('/recommendations');
    await expect(page.getByRole('heading', { name: 'Recommendations' })).toBeVisible();
    await expect(page.getByRole('button', { name: /refresh/i })).toBeVisible();
  });

  test('Progress & Analytics: tracks study metrics and topic performance', async ({ page }) => {
    await page.goto('/progress');
    await expect(page.getByRole('heading', { name: 'Progress' })).toBeVisible();

    // Verify Summary section
    await expect(page.locator('#summary-heading')).toBeVisible();
    await expect(page.getByText('Tasks Completed').first()).toBeVisible();
    await expect(page.getByText('Completion Rate').first()).toBeVisible();
    await expect(page.getByText('Study Minutes').first()).toBeVisible();
    await expect(page.getByText('Avg Quiz Score').first()).toBeVisible();

    // Verify Topic Performance heading
    await expect(page.locator('#topic-performance-heading')).toBeVisible();
  });

  test('Notifications: displays notification list and unread management', async ({ page }) => {
    await page.goto('/notifications');
    await expect(page.getByRole('heading', { name: 'Notifications' })).toBeVisible();
    await expect(page.locator('select[aria-label="Filter notifications"]')).toBeVisible();
  });
});
