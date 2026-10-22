# Activity audit and technical logging

Simbioza keeps two deliberately separate logs because they answer different
questions and have different retention, privacy, and backup requirements.

## Activity audit

Open **Settings → Logs → Activity audit** as an administrator. The append-only
database audit answers who performed which business action, when, through which
channel, with which outcome, and on which Workspace, page, or other safe target.
It covers authenticated users, guests, workers, CLI jobs, API calls, denied
actions, page views after ACL approval, edits, publishing, authentication,
search statistics, notifications, e-mail delivery state, webhook delivery,
backup/restore, imports, exports, uploads, and module-setting changes.

Filters include time range, user, module, action, outcome, authentication
method, channel, Workspace, page, event key, and target. Displayed timestamps
use the configured application time zone and current interface language. CSV is
convenient for spreadsheets and NDJSON preserves complete typed rows for tools
and long-running analysis.

Sensitive values are never intended for this table. Event producers must not
include passwords, tokens, cookies, request/response bodies, document or e-mail
contents, search query text, webhook URLs/signatures, or file contents. The
Audit service recursively redacts suspicious metadata keys as a second guard.

When Backup is installed, **Activity audit** is an optional component for site
or component archives. Stable identities replace local numeric references and
UUID conflict handling makes repeated merge restores safe.

The results show the matching-record total, page size and numbered pagination
above and below the table. Large page counts use ellipses, and navigation
preserves the selected filters without overflowing a mobile screen.

## Technical log

Open **Settings → Logs → Technical log** as an administrator. Select the
application log or an existing log offered by an enabled module. Email 0.3.3
offers `email-worker.log` after the configured worker creates it in
`app.logs.dir`; Audit 0.3.1 discovers its neutral package descriptor without
making Email depend on Audit. This does not inspect or install cron.

Level, module/channel and text filters search the whole selected active file.
The matching-record total and bounded numbered pagination cover nonempty
lines, not rotated archives. Large lines have a bounded display portion;
export downloads the complete selected active file. Both views remain
administrator-only. The rotating application handler uses `app.logs.filename`,
`app.logs.max_bytes` and `app.logs.max_files` from configuration loaded through
`HPH_CONFIG_PATH`, plus `env.log_level` from the environment configuration.
Default rotation retains ten files including the active file, approximately
10 MiB each, and serializes rotation with append across PHP processes.

That handler does not rotate cron redirection, updater, installer or FPM
output. Follow the separate [host and Docker rotation guide](logging_en.md).
An application update does not install a privileged operating-system job.

Modules use the technical log for unexpected exceptions, failed optional
listeners, worker retries and terminal failures, maintenance failures, and
server-error responses. Structured context must include `module`, safe UUID or
numeric resource identifiers, attempt/status information, and the exception.
Free-form business content and credentials are forbidden. Common credential
forms are redacted from messages, structured context, and exception messages.

Technical logs can contain deployment-specific paths, stack-level failure
details, and operational noise. Therefore `data/logs` is excluded from full and
component backups. Preserve or ship these files through the deployment's log
management policy, not through application restore archives.

## Developer example

```php
// Diagnostic failure: PSR-3 technical log.
$logger->error('Workspace tree update failed.', [
    'module' => 'workspace',
    'workspace_id' => $workspaceId,
    'node_id' => $nodeId,
    'exception' => $exception,
]);

// Business action: database activity audit.
$audit->record('workspace.page.publish', [
    'module' => 'workspace',
    'action' => 'publish',
    'workspace_id' => $workspaceId,
    'page_id' => $nodeId,
    'outcome' => 'success',
]);
```

An audit failure must never break the original business operation. The Audit
service reports its own persistence failure to PSR-3 and then returns.
