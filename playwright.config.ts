import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright config untuk DIX Game Farm.
 *
 * PRASYARAT (jalankan dulu, satu kali):
 *   1. XAMPP MySQL hidup, .env terisi
 *   2. php spark migrate
 *   3. php spark db:seed DatabaseSeeder
 *   4. php spark db:seed ShipmentScenarioSeeder
 *   5. php spark serve --port 8080  (di terminal terpisah, biarin jalan)
 *   6. npm install
 *   7. npx playwright install chromium
 *
 * Lalu jalankan tests:
 *   npx playwright test                    # headless (default)
 *   npx playwright test --headed           # buka browser visible
 *   npx playwright show-report             # buka HTML report
 *
 * Screenshot full-page disimpan otomatis ke artifacts/playwright/.
 */
export default defineConfig({
    testDir: './tests',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'artifacts/playwright/html-report', open: 'never' }],
    ],
    outputDir: 'artifacts/playwright/output',

    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost:8080',
        actionTimeout: 10_000,
        navigationTimeout: 15_000,
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        trace: 'on-first-retry',
        ignoreHTTPSErrors: true,
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
