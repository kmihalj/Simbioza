# End-to-end testing

HFClean has a browser and HTTP API end-to-end suite for the assembled
application. Unlike a module unit test, it installs the latest `dev-main`
Framework and module heads into a new temporary project, runs all official
migrations against a new SQLite database, starts a real HTTP server, and drives
Chromium through Playwright.

The suite never uses `config/database.php` from your working application. Test
users, the Bearer key, Workspace records, sessions, logs, and caches exist only
inside a directory below the operating system's temporary
`heartphrame-clean-matrix` directory. The runner refuses to work outside that
directory and removes the project when it finishes.

## What is covered

- the home page and static assets load through the real front controller;
- the mobile menu opens as a right-side drawer and closes again;
- the configured hero artwork loads on a mobile viewport;
- equal Home and Inner hero-size settings produce equal rendered heights;
- the hero reaches the viewport edge without horizontal overflow;
- a guest is redirected from Auth administration to login;
- a local administrator can log in, open Auth settings, and log out;
- a logged-in non-administrator receives HTTP `403` for Auth settings;
- absent and invalid Bearer keys receive the same RFC problem response;
- a valid key can read API discovery and `/api/v1/me` without password data;
- an administrator key completes a real Workspace create/read API cycle.

## First run

Install the latest Playwright test package without creating a lock file, then
install Chromium:

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

The application and internal packages intentionally follow moving `dev-main`
heads. Therefore `package-lock.json` and `composer.lock` are not committed.

## Test local module edits

The default command resolves remote `dev-main` package heads, matching CI. To
test the allowed sibling module checkouts before pushing them:

```bash
composer e2e -- --local
```

The local mode uses path repositories only for the modules that belong to this
project. It never substitutes the upstream Framework or Demo module.

## Debug a failure

Run a visible browser and retain the isolated application:

```bash
composer e2e -- --local --headed --keep
```

The runner prints the retained project path. Playwright traces, screenshots,
videos, and the HTML report are written below `build/`; the PHP server output is
in `build/e2e-server.log`. All these paths are ignored by Git. Remove a retained
project after inspection or rerun without `--keep`.

## CI

GitHub Actions installs the latest Node.js, resolves the latest npm package,
installs Chromium with its Linux dependencies, and runs `composer e2e`. A test
failure retains the Playwright report, traces, screenshots, videos, and server
log as a downloadable CI artifact.

The browser suite is deliberately a separate job from PHP unit and static
analysis jobs. This makes it clear whether a failure belongs to an isolated
module, clean installation, network database, or assembled browser/API flow.
