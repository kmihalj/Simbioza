# Simbioza 0.3.12 — portable guide links and same-site restore

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/release-0.3.12_hr.md)

> Historical release note. Use the [current installation guide](installation_en.md)
> and [Simbioza 0.3.13](release-0.3.13_en.md) for refreshed public instructions.

This patch fixes broken User guides links after installation and corrects
portable HTML restoration on the original site. HTML Editor is **0.3.2** and
Backup **0.3.1**. Auth, API, Email and Theme remain **0.3.1**, Menu **0.3.2**,
other public modules **0.3.0**, Framework **v0.0.25**
and updater **1.0.8**. Existing tags and database migrations are unchanged.
Candidate 0.3.11 was not published; its immutable tag is retained.

## Fixes

- Fresh installations no longer prepend an already resolved application prefix
  a second time (`/demo/demo/...` or `/simbioza/simbioza/...`).
- Updates repair existing known bundled-guide links with repeated prefixes or
  the recorded source address, even when the archive has not changed. Only
  shipped guide destinations that exist locally are resolved. Text, code
  examples and unrelated external links are preserved.
- Repair does not replace themes, private configuration, page identities,
  publication workflow or permissions. It is idempotent and does not add versions.
  Derived backlinks are refreshed only for the changed guide pages and languages.
- Same-site Editor restore adds the installation prefix removed for a portable
  archive. The fix applies to workspace/page and full/component backups, with
  database or filesystem HTML, including a change of storage driver.
- Cross-site restore still maps imported page and attachment identities,
  preserves external URLs, queries and anchors, and reports unresolved targets
  rather than guessing unrelated pages. Relative URLs alone cannot resolve a
  target omitted from the archive.
- When the optional Backup package has been uninstalled, guide archive work is
  skipped before accessing its classes or archives, regardless of update receipts.
  An installed but disabled Backup module can still refresh the bundled guides.
- Current installation commands, bundled EN/HR instructions and Docker examples
  use 0.3.12. The Docker release supports Linux AMD64 and ARM64.

No reinstallation, manual URL replacement or database migration is needed.

## Updating

On a configured secured-FPM site, use **Settings → Setup and modules** and run
the **0.3.12** application update. It installs the compatible module versions
and repairs the managed guides. Review your User guides links afterwards.

See [installation](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/installation_en.md),
[configuration and backup](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/configuration_en.md#backup-and-permissions)
and [Docker deployment](https://github.com/kmihalj/Simbioza/blob/0.3.12/docs/docker_en.md).
