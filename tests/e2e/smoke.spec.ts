import { test, expect } from "@playwright/test";

/**
 * Smoke E2E — requires the app to be running locally:
 *   npm run dev
 *   npm run test:e2e
 *
 * Auth-gated flows need Clerk test credentials; this suite only covers public routes.
 */
test.describe("public pages", () => {
  test("home page loads", async ({ page }) => {
    const response = await page.goto("/");
    expect(response?.ok()).toBeTruthy();
    await expect(page.locator("body")).toBeVisible();
  });

  test("login page is reachable", async ({ page }) => {
    await page.goto("/login");
    await expect(page).toHaveURL(/login/);
    await expect(page.locator("body")).toBeVisible();
  });
});
