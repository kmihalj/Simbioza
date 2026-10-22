# Simbioza 0.3.17 — preserve shared language configuration during updates

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.17/docs/release-0.3.17_hr.md)

This application patch fixes two cross-user failures in secured-FPM installations.
All module versions remain unchanged: Audit/Backup/Auth/API **0.3.1**, Email
**0.3.3**, HTML Editor/Menu/Theme **0.3.2**, other public modules **0.3.0**.
Framework stays **v0.0.25**, updater **1.0.8**. Existing tags are not moved.

## Fixes

- Updating a language no longer replaces existing `installation.php` or
  `languages.php` with a file owned by the deploy user. Existing configuration
  retains its inode, owner, group, mode and ACL; writes use the shared language
  operation lock. New files are still published atomically with private permissions.
- Refreshing an already-active language does not rewrite unchanged installation
  settings. Existing configuration layers and unrelated administrator settings
  remain intact.
- An existing, correctly secured `.packs.lock` is reused without trying to change
  its permissions. FPM and deploy can take turns using it through the existing
  runtime group, without elevated rights or broader access to application code.
- Existing language locks open without `O_CREAT`, respecting Linux
  `protected_regular` in sticky configuration directories. New runtime and download
  directories remain group-writable even with a restrictive deploy CLI umask.
- The disposable Linux secured-FPM regression exercises language installation,
  activation/deactivation and the updater's final metadata restoration as both
  identities, in addition to the actual HTTP GUI language-update test.
- Current installation instructions, Docker examples and bundled installation
  guides use **0.3.17**. No schema or module upgrade is introduced by this patch.

## Updating

On a healthy classic installation, select **0.3.17** in Setup's application update
form, or run the existing application updater as documented for that installation.
Docker installations replace the image with **0.3.17**, retaining the runtime and
database volumes; do not run the classic updater inside the immutable image.

If an earlier update failed at **96%** with a configuration metadata error,
the version file may already show the target release although maintenance remains
enabled. Do not remove maintenance or restore older code blindly: schema migrations
may already have run. Preserve the private update log and backup, establish the
affected files' original metadata from that backup, and have an administrator
restore only those ownership/mode differences before running the corrected release.
Do not recursively change application ownership or give the web user code-write rights.

Language-pack revisions are independent of the application release. A deferred
language refresh does not mean that the complete application update failed;
check its final status and log, then update languages through Setup after this fix.
