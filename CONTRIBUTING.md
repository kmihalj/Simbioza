# Contributing

[Croatian version](CONTRIBUTING_hr.md)

Thank you for helping improve Simbioza. Bug reports, documentation fixes,
translations, tests, and focused code changes are welcome. Contributions are
reviewed when maintainer time permits; opening an issue or pull request does
not imply acceptance or a response deadline.

## Before you start

- Search [existing issues](https://github.com/kmihalj/Simbioza/issues). For
  larger changes, open an issue first to agree on scope.
- Report vulnerabilities privately as described in the
  [security policy](SECURITY.md). Never include them in a public issue or pull
  request.
- This repository assembles the application. Modules have separate
  repositories; see the [dependency matrix](docs/module-dependencies_en.md).
  Send module changes to their owning repositories. HeartPhrame Framework is
  an upstream tagged dependency, not part of this repository's development.

## Prepare a change

1. Fork the repository and create a focused branch from `main`.
2. Keep changes small, preserve compatibility with supported PHP versions and
   database drivers, and add regression tests for behavior changes.
3. Update both English and Croatian documentation or translations affected by
   the change. New or changed PHP methods must keep the project's bilingual
   HR/EN PHPDoc convention.
4. Do not commit credentials, instance configuration, user data, generated
   backups, local Composer path-repository settings, or test artifacts.
5. Run the checks below and explain what you verified in the pull request.

## Checks

From the repository root:

```bash
composer update --with-all-dependencies
composer check-platform-reqs
composer on-commit
```

For changes across application and module boundaries, or browser-visible
behavior, also run the isolated end-to-end suite. It creates disposable test
data; never point it at a production database. See the
[E2E guide](docs/end-to-end-testing_en.md).

```bash
npm install --no-package-lock
npx playwright install chromium
composer e2e
```

Include a concise problem statement, implementation summary, test results,
and screenshots for visible UI changes in your pull request. Submit only work
you have the right to contribute. Accepted contributions are distributed
under the repository's [EUPL 1.2 licence](LICENSE).
