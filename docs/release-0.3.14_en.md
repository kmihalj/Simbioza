# Simbioza 0.3.14 — clear Setup update summaries

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.14/docs/release-0.3.14_hr.md)

This release improves the update screen and includes the published **Email 0.3.2**
worker. HTML Editor and Menu remain **0.3.2**; Backup, Auth, API and Theme remain
**0.3.1**, other public modules **0.3.0**, Framework **v0.0.25** and updater
**1.0.8**. Existing tags are not moved. No database migration or configuration
reset is introduced.

## Changes

- **Settings → Setup and modules** prominently lists components needing updates,
  including installed and available versions, even when only a module is newer.
- Module rows have an explicit update badge and accent border. Enabled/disabled
  state remains separate. Cached results render without JavaScript and the
  automatic release check refreshes the list without interpreting metadata as HTML.
- Essential update details use normal card text rather than decorative muted
  colors. Saved theme settings are neither modified nor replaced.
- Email 0.3.2 adds `--max-messages=20` for at most 20 delivery attempts per worker
  invocation, independent of internal `--batch-size`. Its EN/HR documentation
  covers secured FPM, mod_php and a separate Docker worker.
- Installation instructions, bundled managed guides and Linux AMD64/ARM64 Docker
  examples use **0.3.14**. The image includes Email 0.3.2; configuration/data and
  PostgreSQL remain on their existing persistent volumes.

## Updating

On a configured secured-FPM site, use **Run update** in Setup and select **0.3.14**.
The improved screen appears after the update. The existing updater updates the
application and compatible modules together, preserving private settings and data.
No reinstallation or separate Theme module update is needed.

After upgrading, verify `vendor/bin/hph email help` includes `--max-messages`
before configuring a bounded cron job. See the
[Email worker guide](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/0.3.2/docs/workers_en.md).
Do not run a scheduled worker during application/database maintenance.

For Docker, follow the [deployment guide](https://github.com/kmihalj/Simbioza/blob/0.3.14/docs/docker_en.md),
stop the web/worker services for migration and replace their image together.
Never remove persistent volumes as an update step.
