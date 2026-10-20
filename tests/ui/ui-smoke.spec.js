import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';

const viewports = [
    { width: 360, height: 800 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1280, height: 800 },
    { width: 1920, height: 1080 },
];

test('landing and login render without browser errors or horizontal overflow', async ({ page }) => {
    test.setTimeout(120_000);

    const consoleErrors = [];
    const failedRequests = [];
    const badResponses = [];
    const overflowingPages = [];
    const bodyFonts = [];
    const loadedRobotoFonts = [];

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

    for (const route of ['/', '/admin/login']) {
        await page.setViewportSize(viewports[0]);
        const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
        expect(response?.status(), route).toBe(200);
        await page.waitForFunction((currentRoute) => {
            const stylesheet = currentRoute === '/'
                ? '/build/assets/app-'
                : '/build/assets/filament-admin-premium-';

            return [...document.styleSheets].some((sheet) => sheet.href?.includes(stylesheet));
        }, route);

        for (const viewport of viewports) {
            await page.setViewportSize(viewport);
            const result = await page.evaluate(async () => {
                await document.fonts.ready;
                return {
                    width: window.innerWidth,
                    scrollWidth: document.documentElement.scrollWidth,
                    font: getComputedStyle(document.body).fontFamily,
                    stylesheets: [...document.styleSheets].map((stylesheet) => stylesheet.href ?? 'inline'),
                    loadedRoboto: [...document.fonts].some((font) => font.family === 'Roboto' && font.status === 'loaded'),
                };
            });

            if (result.scrollWidth > result.width) {
                overflowingPages.push(`${route} at ${viewport.width}px (${result.scrollWidth}px)`);
            }
            bodyFonts.push(`${route}: ${result.font} [${result.stylesheets.join(', ')}]`);
            loadedRobotoFonts.push(result.loadedRoboto);

            if (route === '/admin/login' && viewport.width === 390) {
                const captureDirectory = resolve('docs', 'qa', 'captures');
                mkdirSync(captureDirectory, { recursive: true });
                const suffix = process.env.QA_CAPTURE_SUFFIX ?? 'after';
                await page.screenshot({
                    path: resolve(captureDirectory, `ui-001-login-${suffix}.png`),
                    fullPage: true,
                });
            }
        }
    }

    expect(consoleErrors, 'erreurs console JavaScript').toEqual([]);
    expect(failedRequests, 'requêtes réseau en échec').toEqual([]);
    expect(badResponses, 'réponses HTTP en erreur').toEqual([]);
    expect(overflowingPages, 'débordements horizontaux').toEqual([]);
    expect(bodyFonts.every((font) => /Roboto/i.test(font)), `polices appliquées: ${bodyFonts.join('; ')}`).toBe(true);
    expect(loadedRobotoFonts, 'Roboto chargé depuis une vraie face de police').toEqual(Array(10).fill(true));
});
