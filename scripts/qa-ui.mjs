import { execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';

const temporaryDirectory = mkdtempSync(join(tmpdir(), 'scolaris-qa-ui-'));
const environment = {
    ...process.env,
    APP_ENV: 'local',
    BCRYPT_ROUNDS: '4',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: join(temporaryDirectory, 'qa.sqlite'),
    DB_URL: '',
    SESSION_CONNECTION: 'sqlite',
    SESSION_DRIVER: 'database',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    QA_UI_DB_DATABASE: join(temporaryDirectory, 'qa.sqlite'),
};

try {
    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        cwd: process.cwd(),
        env: environment,
        stdio: 'inherit',
    });
    execFileSync('php', ['artisan', 'db:seed', '--class=DemoSchoolSeeder', '--force', '--quiet'], {
        cwd: process.cwd(),
        env: environment,
        stdio: 'inherit',
    });

    const playwrightCli = resolve('node_modules', '@playwright', 'test', 'cli.js');
    execFileSync(process.execPath, [playwrightCli, 'test', ...process.argv.slice(2)], {
        cwd: process.cwd(),
        env: environment,
        stdio: 'inherit',
    });
} finally {
    rmSync(temporaryDirectory, { recursive: true, force: true });
}
