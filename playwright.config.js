import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/ui',
    fullyParallel: false,
    workers: 1,
    reporter: 'list',
    outputDir: 'test-results/qa-ui',
    use: {
        baseURL: 'http://127.0.0.1:8021',
        browserName: 'chromium',
        headless: true,
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8021',
        url: 'http://127.0.0.1:8021',
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
            ...process.env,
            APP_ENV: 'local',
            BCRYPT_ROUNDS: '4',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: process.env.QA_UI_DB_DATABASE ?? ':memory:',
            SESSION_CONNECTION: 'sqlite',
            SESSION_DRIVER: 'database',
            CACHE_STORE: 'array',
        },
    },
});
