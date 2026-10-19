# Simbioza 0.3.10 — preserve settings and selected theme colors

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/release-0.3.10_hr.md)

> Historical release notes. For current installation and update commands, use
> [the installation guide](installation_en.md) and
> [Simbioza 0.3.11](release-0.3.11_en.md).

This patch preserves administrator-owned configuration during updates, removes
automatic replacement of the installed theme and makes the saved muted-text
color match the preview after a reload. Auth, HTML Editor, Email, Theme and API
are **0.3.1**, Menu is **0.3.2**, other public modules **0.3.0**, the private DEMO module **0.3.6**,
Framework **v0.0.25** and updater **1.0.8**. Existing tags and database migrations
are unchanged. Candidates 0.3.8 and 0.3.9 were not published; use 0.3.10.

## Configuration preservation

- Application updates no longer import the factory Simbioza theme. Edited
  colors, layout, active-theme selection and the entire theme library remain
  administrator-owned, even if the shipped ZIP changes or an old update receipt
  is absent. New installations still receive the bundled theme.
- The central `framework.php` remains private, including its owner, group and
  permissions. Final permission checks no longer try to make it publicly
  readable or change it through the deploy account. This also works when the
  maintainer owns the file and grants the runtime group read-only access.
- Regression coverage edits the actual shipped `simbioza` theme, not only a
  clone, and checks configuration bytes and administrator-owned theme files.
- Updater tests cover Auth, Email, Editor/external scripts, Workspace, Calendar,
  Backup, API, Search, Confluence Import, Menu, Theme, language/module selection
  and application settings. Existing values and private ownership/permissions
  are preserved through source synchronization, external/inside configuration,
  GUI/CLI execution and rollback. Missing menu entries may be appended without
  replacing existing labels, order, enabled state or options.
- Menu 0.3.2 makes repeated module registration preserve an existing item's
  labels, destination, disabled state, children and placement. New module
  defaults no longer silently replace those administrator choices at startup.
- Email, HTML Editor and static Auth navigation also preserve saved labels,
  destinations, disabled entries and placement. SSO-derived provider/profile
  status continues following Auth configuration. Authentication, protocol
  validation, SMTP delivery, HTML sanitization and storage behavior are unchanged.
- Equivalent navigation metadata does not cause a rewrite on every request.
- Docker persistence checks compare all private configuration and theme-library
  files by checksum before and after container recreation. Private values are
  not printed. Image upgrades retain the runtime and database volumes.

## Selected colors

Theme 0.3.1 uses a valid explicit `muted_text` color exactly as saved in both
variants, the live preview, generated CSS and portable exports. It no longer
silently substitutes a contrast-adjusted color after reloading. Derived button
foregrounds and explicit accessibility preferences remain supported. Choose
a readable combination for the intended background.

No reinstallation or configuration migration is required. This patch does not
restore older settings automatically: a previous recovery archive may contain
values older than your latest edits.

## Updating

On a configured secured-FPM site, open **Settings → Setup and modules**, check
for a new release and start the **0.3.10** update. Its minimum published module
versions are listed above. Refresh the theme editor after completion. Current
installation examples, Docker examples and bundled EN/HR guides use 0.3.10.

For an existing provisioned local DEMO, run its private wrapper from the DEMO
root: `sh data/demo-local/updatedemo.sh --check`, followed by
`sh data/demo-local/updatedemo.sh 0.3.10`. It restores the clean reset baseline
before updating and captures a new baseline afterwards. Publishing this release
does not upgrade DEMO or apps-test automatically.

Old private recovery archives in `data/backups/bundled-assets/` are retained.
Restore only deliberately selected settings after comparing them with later
administrator changes.

See [configuration preservation](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/configuration_en.md#updates-preserve-administrator-configuration),
the [secured-FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/installation_fpm_secured_en.md)
and the [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.10/docs/docker_en.md).
