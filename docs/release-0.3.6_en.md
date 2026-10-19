# Simbioza 0.3.6 — verified secured-FPM installation

> Historical release note. For current installation instructions, use the [installation guide](installation_en.md).

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/release-0.3.6_hr.md)

This application patch fixes the Linux Setup helper and completes the fresh
secured-FPM walkthrough. Framework remains **v0.0.25**, API **0.3.1**, the other
public modules **0.3.0**, and the private DEMO module **0.3.6**. Updater **1.0.8**
and database migrations are unchanged. Existing tags are not moved.

## Changes

- Generated Linux helpers omit empty bind lists and emit one safely quoted
  property for each configured path. A default installation no longer fails
  with `Source path is not absolute` before the Setup worker starts.
- The configurator checks the real helper round trip and background updater's
  CLI functions, not just helper-file existence and sudoers. Failed checks
  return a nonzero status before an administrator proceeds to the wizard.
- Setup diagnostics retain the actual helper failure. Dependent checks which
  did not run are labeled **Not checked**, rather than claiming that `pcntl`
  or `posix` extensions are missing.
- Complete EN/HR installation guides include account/group creation, runtime
  permissions, dedicated service generation, shared-vhost aliases and optional
  SimpleSAMLphp integration. Apache examples permit the `DirectoryIndex`
  directive shipped in `.htaccess` while keeping directory listings disabled.
- Current installation commands, Docker examples and bundled guides use
  **0.3.6**. The release contains Linux AMD64 and ARM64 application images.
- A disposable Debian/systemd/Apache/PHP-FPM regression fixture exercises
  provisioning, the real HTTP wizard, finalization and GUI update availability.
  The existing browser/API and database suites remain required release gates.

## Fresh installation

Start with the [complete installation index](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/installation_en.md)
or [secured FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/installation_fpm_secured_en.md).
They assume an empty server; the generator creates missing identities and reuses
existing identities without replacing their UID/GID. Follow the one applicable
web-server configuration, stop after any failed command and check actual HTTP
access in addition to configuration syntax.

On an existing Linux secured-FPM site, updating application files alone does
not replace the root-owned helper outside the application directory. Refresh
it with the documented administrator-run generator and the same instance/path
options before relying on GUI package operations. Do not grant FPM write
access to application code as a workaround.

For immutable Docker deployments, follow the
[Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.6/docs/docker_en.md)
and use `ghcr.io/kmihalj/simbioza:0.3.6`, preserving the existing runtime and
database volumes. Publishing this release does not update demo or apps-test.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.6)
