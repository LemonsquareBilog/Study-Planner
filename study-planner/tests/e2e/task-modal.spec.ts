import { test, expect } from '@playwright/test';
for (const width of [1440, 390]) {
  test(`task modal close remains visible at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 700 });
    await page.addInitScript(() => localStorage.setItem('token', JSON.stringify({token:'cookie-session',refreshToken:'cookie-session'})));
    await page.route('**/api/**', route => {
      const path = new URL(route.request().url()).pathname;
      const data = path.endsWith('/auth/me') ? {id:1,name:'Student',role:'student',is_active:true}
        : path.endsWith('/student/tasks') ? {data:[],current_page:1,last_page:1,total:0}
        : [];
      return route.fulfill({json: path.endsWith('/student/tasks') ? data : {data}});
    });
    await page.goto('/tasks');
    await page.getByRole('button',{name:'Create new task'}).click();
    const close = page.getByRole('button',{name:'Close modal'});
    const before = await close.boundingBox();
    await page.locator('form').evaluate(el => {el.scrollTop=el.scrollHeight;});
    const after = await close.boundingBox();
    expect(after?.y).toBe(before?.y);
    expect(after?.width).toBeGreaterThanOrEqual(44);
    await expect(close).toBeInViewport();
    await close.click();
    await expect(page.getByRole('dialog')).toHaveCount(0);
  });
}
