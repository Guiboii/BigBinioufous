// End-to-end browser tests. Only for behaviour PHPUnit can't see (JavaScript, drag-and-drop, rendering); everything else stays in tests/ (PHPUnit).
import { defineConfig, devices } from '@playwright/test';

// Separate SQLite database so the e2e run never touches the local MariaDB dev data. A real env var wins over the .env files.
const databaseUrl = 'sqlite:///%kernel.project_dir%/var/e2e.db';
const port = 8001;

export default defineConfig({
  testDir: './tests/e2e',
  globalSetup: './tests/e2e/global-setup.js',
  // Tests share one database: run them one after another to keep their data predictable.
  workers: 1,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    baseURL: `http://127.0.0.1:${port}`,
    locale: 'fr-FR',
    trace: 'retain-on-failure',
  },
  projects: [
    // Logs in once and saves the session (see auth.setup.js), so specs don't each go through the 3D /join page.
    { name: 'setup', testMatch: /.*\.setup\.js/ },
    { name: 'chromium', use: { ...devices['Desktop Chrome'] }, dependencies: ['setup'] },
  ],
  webServer: {
    // variables_order=EGPCS: without the E, php -S hides env vars from $_ENV and Symfony would silently fall back to the .env database.
    command: `php -d variables_order=EGPCS -d upload_max_filesize=200M -d post_max_size=210M -S 127.0.0.1:${port} -t public`,
    url: `http://127.0.0.1:${port}/join`,
    env: { DATABASE_URL: databaseUrl },
    reuseExistingServer: !process.env.CI,
    timeout: 60_000,
  },
});

export { databaseUrl };
