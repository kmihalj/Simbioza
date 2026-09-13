# Simbioza 0.1.68

This coordinated release adds meeting planning and fixes local administrator
recovery for all active Administrator-group members, not just the initial
Administrator account. It includes the page backup/import/transfer and HTML
disclosure improvements developed alongside meetings.

## Bundled content on installation and update

Fresh installations include the current Simbioza theme and bilingual meeting
instructions under User guides → Calendars → Meetings. The meeting guide is
a regular Workspace page with screenshot attachments and can be exported.
All screenshots use the light theme.

After normal code/dependency update and migrations, `update.php` runs the
CLI-only bundled-assets step. It updates only the Simbioza theme and meeting
guide. The application's CLI command adapter also runs this step after a
successful updater migration, so a previously loaded older `update.php`
applies the new bundles on its first run. Normal migrations outside updater
maintenance, other connections, and other migration paths do not import bundles.
The existing meeting page keeps its identity, tree position, and permissions.
Other themes, the active-theme policy, and unrelated guide pages remain unchanged.
Changed bundles have private
recovery snapshots; unchanged bundle hashes are not imported again.

For older installations, the base path is read from installation metadata or
existing guide images, including file-backed guide versions. Ambiguous paths
stop the content step instead of guessing. An administrator may run it explicitly:

```bash
php scripts/update_bundled_assets.php --base-path=/simbioza
```

Recovery state is stored in `data/bundled-assets.json` with permissions 0600.
Keep it private: it contains the passphrase for the pre-update page snapshot.
The public starter-guide package passphrase is not used for that private
snapshot.

The release does not automatically update apps-test. Its operator runs:

```bash
cd /data/www/simbioza && sudo php update.php
```
