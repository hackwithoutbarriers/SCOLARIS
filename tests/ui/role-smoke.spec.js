import { test, expect } from '@playwright/test';

const demoAccounts = [
    { role: 'directeur — école A', email: 'director@example.com', password: 'password' },
    { role: 'comptable — école A', email: 'accountant@example.com', password: 'password' },
    { role: 'secrétaire — école A', email: 'secretary@example.com', password: 'password' },
    { role: 'enseignant — école A', email: 'teacher@example.com', password: 'password' },
    { role: 'censeur — école A', email: 'censeur@example.com', password: 'password' },
    { role: 'directeur — école B', email: 'direction@demo.scolaris.test', password: 'DemoScolaris!2026' },
    { role: 'comptable — école B', email: 'caisse@demo.scolaris.test', password: 'DemoScolaris!2026' },
    { role: 'secrétaire — école B', email: 'secretariat@demo.scolaris.test', password: 'DemoScolaris!2026' },
    { role: 'enseignant — école B', email: 'enseignant01@demo.scolaris.test', password: 'DemoScolaris!2026' },
    { role: 'super-admin', email: 'superadmin@example.com', password: 'password' },
];

test('les rôles quotidiens ouvrent le tableau de bord sur mobile et bureau', async ({ page }) => {
    test.setTimeout(420_000);

    const consoleErrors = [];
    const failedRequests = [];
    const badResponses = [];
    const overflows = [];

    page.on('console', (message) => {
        if (message.type() === 'error') {
            consoleErrors.push(message.text());
        }
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));
    page.on('requestfailed', (request) => {
        failedRequests.push(`${request.url()}: ${request.failure()?.errorText ?? 'requête échouée'}`);
    });
    page.on('response', (response) => {
        if (response.status() >= 400) {
            badResponses.push(`${response.status()} ${response.url()}`);
        }
    });

    for (const account of demoAccounts) {
        await page.context().clearCookies();
        await page.setViewportSize({ width: 390, height: 844 });
        const loginResponse = await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });
        expect(loginResponse?.status(), `connexion ${account.role}`).toBe(200);

        await page.getByLabel('Adresse e-mail ou téléphone').fill(account.email);
        await page.getByLabel('Mot de passe').fill(account.password);
        const loginSubmission = page.waitForResponse(
            (response) => response.url().includes('/livewire/update'),
            { timeout: 90_000 },
        );
        await page.getByRole('button', { name: 'Connexion' }).click();
        const submissionResponse = await loginSubmission;
        expect(submissionResponse.status(), `réponse de connexion ${account.role}`).toBe(200);
        await expect(page).toHaveURL(/\/admin\/?$/, { timeout: 60_000 });
        await page.getByRole('main').waitFor({ state: 'visible' });

        for (const viewport of [
            { width: 390, height: 844 },
            { width: 1280, height: 800 },
        ]) {
            await page.setViewportSize(viewport);
            const size = await page.evaluate(() => ({
                width: window.innerWidth,
                scrollWidth: document.documentElement.scrollWidth,
            }));
            if (size.scrollWidth > size.width) {
                overflows.push(`${account.role} à ${viewport.width}px (${size.scrollWidth}px)`);
            }
        }
    }

    expect(consoleErrors, 'erreurs console JavaScript').toEqual([]);
    expect(failedRequests, 'requêtes réseau en échec').toEqual([]);
    expect(badResponses, 'réponses HTTP en erreur').toEqual([]);
    expect(overflows, 'débordements horizontaux').toEqual([]);
});
