# Simbioza 0.3.16 — log pagination, worker diagnostics and rotation

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/release-0.3.16_hr.md)

This release includes **Audit 0.3.1** and **Email 0.3.3**. HTML Editor/Menu/Theme
remain **0.3.2**, Backup/Auth/API **0.3.1**, the other public modules **0.3.0**,
Framework **v0.0.25** and updater **1.0.8**. Existing tags are not moved.

## Changes

- Activity audit and Technical log show the matching-record total and numbered
  pagination above and below the table. Large page counts use ellipses, filters
  and page size are preserved, and the navigation wraps on narrow screens.
- Technical log searches the whole active file rather than a recent-line tail,
  using streaming reads with bounded page memory. Rotated archives are not
  included in the total. Filters apply to the displayed portion of exceptionally
  long lines; exporting still downloads the complete active file.
- Installed, enabled modules can contribute existing log files through a
  neutral package descriptor. Email contributes `email-worker.log` when the
  scheduled worker has created it in `app.logs.dir`. Select it under
  **Technical log → Log source**. This does not install or inspect system cron;
  Email remains usable without Audit. Display and export remain administrator-only.
- The application log handler serializes rotation and append across processes,
  retains a bounded number of copies and preserves private permissions.
- The Docker Compose bundle bounds collected stdout/stderr with the `local`
  log driver (10 MiB, five files per service). Runtime-volume log files are a
  separate concern, not covered by Docker's output rotation.
- EN/HR rotation instructions cover cron, updater, installer, FPM, optional
  SimpleSAMLphp logs, macOS and Docker. Current installation examples and
  bundled guides use **0.3.16**.

## Updating and host rotation

On a configured secured-FPM installation, run the application update in Setup
with target **0.3.16**. No reinstallation or database schema change is needed.
Existing SMTP settings, themes, configuration files, content and activity
history are preserved; no automatic history deletion is introduced.

**Upgrading the PHP application does not install a privileged logrotate rule.**
The cron-redirection file needs a separate host rule and scheduler. Follow the
[rotation guide](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/logging_en.md):
its example uses a 10 MiB threshold and at most 14 archives. The threshold is
checked when logrotate runs, not continuously; arrange an appropriate check
frequency. Do not rotate `app.log` with two mechanisms. The documented
`copytruncate` approach preserves active file ownership and open writers but
can lose a small amount of concurrent output during copy/truncate.

For Docker follow the [deployment guide](https://github.com/kmihalj/Simbioza/blob/0.3.16/docs/docker_en.md).
Retain the runtime and PostgreSQL volumes and recreate services to apply the
new image and Compose logging settings. A worker writing only stdout is viewed
with `docker compose logs email-worker`; to inspect a shared log file in Audit,
explicitly redirect to it and separately arrange its file rotation.

Module references: [Audit 0.3.1](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-audit/-/blob/0.3.1/docs/audit_en.md)
and [Email 0.3.3 worker setup](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/0.3.3/docs/workers_en.md).
