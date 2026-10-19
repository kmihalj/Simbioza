# Simbioza 0.3.8 — preserve theme settings and selected colors

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/release-0.3.8_hr.md)

This patch removes an automatic theme replacement from application updates and
makes the saved muted-text color match the live preview after a reload.
Theme is **0.3.1**, API remains **0.3.1**, other public modules **0.3.0**,
the private DEMO module **0.3.6**, Framework **v0.0.25** and updater **1.0.8**.
Existing tags and database migrations are unchanged.

## Configuration preservation

- The final bundled-content step no longer imports the factory Simbioza theme.
  Edited colors, layout, active-theme selection and the complete theme library
  remain administrator-owned, even when the shipped ZIP changes or no previous
  theme-update receipt exists. New installations still receive the bundled theme.
- Regression coverage explicitly edits the shipped `simbioza` theme, not only a
  custom clone, and verifies both configuration bytes and administrator-owned files.
- Broader updater tests cover Auth, Email, Editor/external scripts, Workspace,
  Calendar, Backup, API, Search, Confluence Import, Menu, Theme and application
  settings across source synchronization, external/inside configuration, GUI/CLI
  execution and rollback. Existing values, ownership and permissions are preserved.
- Docker persistence evidence compares every private configuration and theme
  library file by checksum before and after container recreation, without exposing
  private values. Image upgrades retain the runtime and database volumes.

## Selected colors

Theme 0.3.1 uses a valid explicit `muted_text` color exactly as saved in both
variants, including the live preview, generated CSS and portable exports. It no
longer silently changes the color to satisfy a contrast calculation. Derived
button foregrounds and explicit accessibility preferences remain supported.
Choose a readable combination for the intended background.

The previous behavior could store the chosen color correctly while showing a
darker/lighter approximation after reloading the page. No manual reinstallation
or configuration migration is required.

## Updating

On a configured secured-FPM site, open **Settings → Setup and modules**, check
for a new release and start the **0.3.8** update. The application requires the
published Theme **0.3.1** release. Refresh the theme editor after completion.
Installation examples and bundled EN/HR guides target 0.3.8.

For an existing provisioned local DEMO, run its private
`data/demo-local/updatedemo.sh --check` and then
`data/demo-local/updatedemo.sh 0.3.8` from the DEMO root. The wrapper restores the
clean reset baseline before updating and captures a new baseline afterwards.
Publishing the release does not deploy to DEMO or apps-test automatically.

An old private recovery archive created by an earlier updater remains in
`data/backups/bundled-assets/`; this patch neither deletes nor automatically
restores it. Restore only deliberately selected settings after comparing them
with any more recent administrator changes.

See [configuration preservation](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/configuration_en.md#updates-preserve-administrator-configuration),
the [secured-FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/installation_fpm_secured_en.md)
and the [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.8/docs/docker_en.md).
