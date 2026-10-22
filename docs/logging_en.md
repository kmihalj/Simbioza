# Logs and rotation

[Croatian version](logging_hr.md)

Configuration belongs under `HPH_CONFIG_PATH`, log **data** does not. The
application's `app.logs.dir` defaults to `data/logs`. Adapt all paths below if
you changed it. Logs and exports can contain personal data: keep them outside
`public/`, restrict access and choose retention according to your policy.

## Which mechanism owns each log?

| Log | Writer | Rotation |
| --- | --- | --- |
| `data/logs/app.log` | Shared application logger | Audit's `RotatingFileLogHandler`: 10 MiB per file, 10 files including the active file by default; shared `app.logs.max_bytes` / `app.logs.max_files` settings |
| `data/logs/email-worker.log` | Shell redirection in the Email cron example | Host `logrotate`; installing Email or adding a cron does **not** install rotation |
| `data/logs/application-update.log` | Deployment helper, stdout/stderr | Host `logrotate`; preserve deployment ownership |
| `data/logs/installer.log` | Initial installer | Host `logrotate`; preserve its private mode |
| `data/logs/php-fpm.log`, `php-fpm-launchd.log` | Dedicated FPM / macOS launchd | Host rotation; preserve the writer's ownership; do not restart unrelated pools |
| `data/saml/logs/*.log` | Optional, separately configured SimpleSAMLphp | Inspect its actual logging destination, then configure a separate host rule |
| Apache, PostgreSQL and system journal | Operating system or container | Their own platform policies, not the PHP application's handler |
| Performance / E2E files under `build/` | Explicit test runs only | Not enabled on ordinary installations; remove completed test artifacts according to your development policy |
| `audit_events` | Activity audit, database | Not a file log; never delete or truncate it through logrotate. Business-history retention needs a separate deliberate policy |

The application uses Audit's rotating handler when that package is installed.
A generic HeartPhrame host must explicitly wire the handler into its PSR-3
logger; merely enabling an Audit GUI does not change an arbitrary host logger.
Without that handler, configure host rotation for the application's log too.
Do not rotate the same file with both mechanisms. The handler serializes
rotation and append through a persistent `.rotation.lock`; do not delete that
lock while writers run. A single unusually large record can exceed the size limit.

## Linux: FPM Secured

This is **host administration**, not application configuration. Install
`logrotate` using your distribution's package manager (Debian/Ubuntu:
`sudo apt-get install --no-install-recommends logrotate`). As the administrator, create
`/etc/logrotate.d/simbioza`:

```conf
/srv/simbioza/data/logs/email-worker.log /srv/simbioza/data/logs/application-update.log /srv/simbioza/data/logs/installer.log /srv/simbioza/data/logs/php-fpm.log /srv/simbioza/data/logs/php-fpm-launchd.log {
    su root root
    daily
    maxsize 10M
    rotate 14
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    nocreate
}
```

Replace **every** `/srv/simbioza` with the real application root, for example
`/data/www/simbioza`. Keep the rule `root:root`, mode `0644`, never writable by
the web user. `su root root` permits the administrator-owned service to handle
the different runtime/deploy/FPM owners in this private directory. This does
**not** run the email worker as root. Explicit filenames avoid capturing
unrelated files or database/backup data.

`copytruncate` keeps the active inode, owner, ACLs and permissions, so cron,
FPM and an already running updater can continue writing without a restart.
The copy/truncate interval can lose a few concurrently written bytes. If your
policy permits no such loss, use a centralized collector or writer-specific
reopen/locking arrangements instead; do not silently rename a live FPM log.
The active log plus at most 14 archives remain, with compression delayed by
one rotation. Existing archives beyond that count are not a backup policy.

Validate without changing files, then check the scheduler:

```sh
sudo chown root:root /etc/logrotate.d/simbioza &&
sudo chmod 0644 /etc/logrotate.d/simbioza &&
sudo logrotate --debug /etc/logrotate.d/simbioza &&
sudo systemctl status logrotate.timer --no-pager
```

`maxsize` is checked **when logrotate runs**, not continuously: a daily timer
can still allow a busy log to grow during that day. For hourly checks on a
systemd distribution, use `sudo systemctl edit logrotate.timer` and enter:

```ini
[Timer]
OnCalendar=
OnCalendar=hourly
AccuracySec=1min
```

Then `sudo systemctl daemon-reload && sudo systemctl enable --now logrotate.timer`.
This changes the check frequency for the host's logrotate service; review it
with the server administrator or configuration-management owner. Hosts using
cron instead of a timer need the equivalent scheduler adjustment. Do not
schedule two separate rotators for the same files/state. A size bound between
checks is approximate, not a disk quota.

For optional SimpleSAMLphp, first confirm the configured `loggingdir` and
logger. Add only its actual log filenames in a separate rule with suitable
permissions; never include its certificates, configuration or metadata.

## mod_php and macOS

For Linux mod_php use the same rule with the installation's actual paths.
`copytruncate` preserves the existing `www-data` (or other Apache account)
owner. FPM-only log filenames are harmless when absent (`missingok`). Apache's
own log files normally already have distribution rules: inspect them rather
than adding a second overlapping rule.

macOS has no Debian `systemctl` or `/etc/cron.d` setup. Use the administrator's
existing rotation service, or install Homebrew `logrotate` and arrange a
root-owned launchd job with explicit absolute paths to its binary, private
configuration and persistent state file. Reuse the same log rule with macOS
paths and appropriate `su` identities (not Linux's `root:root`). Check it with
`logrotate --debug` first. Installing the PHP module does not install a
privileged launchd job. Keep a single rotation owner per file.

## Docker

The bundled Compose stack sets Docker's `local` logging driver with
`max-size: "10m"` and `max-file: "5"` for each service. This bounds collected
**stdout/stderr**; changing Compose requires recreating containers, not merely
restarting them. Do not edit Docker-managed log files yourself.

This does **not** rotate files inside the runtime volume. `app.log` still uses
the PHP handler. If you redirect an email worker into
`/runtime/data/logs/email-worker.log` so Audit can inspect it, arrange a separate
rotation job with access to that volume; Docker's log driver cannot see that
redirection. A worker writing only stdout is inspected with
`docker compose logs email-worker`, not Audit. See the
[Email worker guide](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-email/-/blob/main/docs/workers_en.md).

References: [logrotate manual](https://man7.org/linux/man-pages/man8/logrotate.8.html)
and [Docker local logging](https://docs.docker.com/engine/logging/drivers/local/).
