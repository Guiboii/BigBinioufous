// Rebuilds the e2e SQLite database from the entity mapping (not the migrations, which are MySQL-specific) and loads the demo fixtures before each run.
import { execFileSync } from 'node:child_process';
import { databaseUrl } from '../../playwright.config.js';

function runConsole(...args) {
  execFileSync('php', ['bin/console', ...args, '--no-interaction', '--quiet'], {
    env: { ...process.env, DATABASE_URL: databaseUrl },
    stdio: 'inherit',
  });
}

export default function globalSetup() {
  try {
    runConsole('doctrine:schema:drop', '--force', '--full-database');
    runConsole('doctrine:schema:create');
    runConsole('doctrine:fixtures:load');
  } catch (error) {
    throw new Error(`Could not prepare the e2e database (is PHP installed?): ${error.message}`, {
      cause: error,
    });
  }
}
