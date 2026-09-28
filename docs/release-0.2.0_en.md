# Simbioza 0.2.0

This release makes permanent deletion of a large, previously deleted Workspace
resumable. The administrator confirms the exact slug once; the browser then
processes bounded batches and shows a persistent progress bar. If the browser
or network connection is interrupted, open **Deleted workspaces** and repeat
the same action to continue. A Workspace whose permanent deletion has begun
cannot be restored as an incomplete Workspace.

The HTML Editor now reads and removes only the documents and attachments in
the current batch. Confluence import cleanup selects only managed staging
paths instead of loading all attachment metadata. These changes address the
PHP memory exhaustion previously seen with imported Workspaces containing
tens of thousands of attachments.

The Workspace module adds one database migration for the persistent purge
marker and progress counters. The normal GUI or CLI application update runs
the migration. Make a restorable backup before updating. The update does not
automatically delete a previously soft-deleted Workspace: after a successful
update, return to **Deleted workspaces** and explicitly start or resume its
permanent deletion. Keep the browser tab open while it runs. If a batch fails,
check the administrative log before retrying; completed batches are retained.

Language packs are versioned separately from the application. If additional
languages are installed, update them in Setup or run
`vendor/bin/hph languages update` to obtain the new progress messages.
