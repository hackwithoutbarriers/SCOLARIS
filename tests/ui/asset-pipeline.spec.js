import { test, expect } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';

test('production Docker build scans Filament PHP classes and publishes Filament assets', async () => {
    const dockerfile = await readFile(resolve('Dockerfile'), 'utf8');
    const sourceCopyIndex = dockerfile.indexOf('COPY app/Filament app/Filament');
    const buildIndex = dockerfile.indexOf('RUN npm run build');

    expect(sourceCopyIndex).toBeGreaterThanOrEqual(0);
    expect(sourceCopyIndex).toBeLessThan(buildIndex);
    expect(dockerfile).toContain('php artisan filament:assets --no-interaction');

    const manifest = JSON.parse(await readFile(resolve('public/build/manifest.json'), 'utf8'));
    const stylesheet = manifest['resources/css/filament-admin-premium.css']?.file;

    expect(stylesheet).toBeTruthy();

    const css = await readFile(resolve('public/build', stylesheet), 'utf8');

    expect(css).toContain('.opacity-60');
    expect(css).toContain('.bg-gray-100');
});
