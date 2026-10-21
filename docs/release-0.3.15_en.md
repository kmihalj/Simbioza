# Simbioza 0.3.15 — automatic and custom button text colors

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.15/docs/release-0.3.15_hr.md)

This release includes **Theme 0.3.2**. HTML Editor, Menu and Email remain
**0.3.2**, Backup/Auth/API **0.3.1**, other public modules **0.3.0**, Framework
**v0.0.25** and updater **1.0.8**. Existing tags are not moved.

## Changes

- Each filled button family (primary, secondary, success, warning, danger and
  info) has its own text color for light and dark appearance. In **Theme settings
  → Button colors → Filled button text colors**, choose **Automatic** or select
  a custom HEX color. Automatic chooses black or white for the better contrast.
  A manual choice is honored exactly, so check readability against its background.
- The live preview uses the same settings as actual buttons, including the
  calendar toolbar. Outline buttons keep readable accent text while unfilled;
  their filled hover/pressed states use the selected button text color.
- Classic application updates and explicit Docker `migrate` add only the missing
  twelve options to `HPH_CONFIG_PATH/theme/themes.json`, defaulting to `auto`.
  Existing values, additional fields and theme selection are preserved. Repeating
  the upgrade does not rewrite a complete file. This also works when the optional
  Backup package is absent or the installed Theme GUI is disabled.
- The bundled Simbioza theme ZIP contains the new options for **fresh
  installations**. Updating an existing site does **not** import that package
  or replace customized themes. Workspace-private themes use the same rendering
  rules; missing options behave as Automatic without resetting their data.
- Installation instructions, managed guide archives and Docker AMD64/ARM64
  examples use **0.3.15**. No database schema change is introduced.

## Updating

On a configured secured-FPM site, use **Run update** in Setup and select
**0.3.15**. No separate theme-package import or reinstallation is needed.
Afterward, set the secondary button text to white in both variants if that is
the desired calendar-toolbar appearance. Existing installations remain on
Automatic until explicitly changed; automatic contrast can correctly select
black for an orange background.

For Docker, follow the [deployment guide](https://github.com/kmihalj/Simbioza/blob/0.3.15/docs/docker_en.md),
stop web/worker services, run the explicit migration, and restart them using the
same new image. Keep the existing runtime and PostgreSQL volumes.

The [Theme configuration reference](https://gitlab.opencode.hr/srce/aai-eduhr/heartphrame/heartphrame-module-theme/-/blob/0.3.2/docs/configuration_en.md)
documents all six keys, file-only usage and preservation behavior. Email remains
0.3.2, including `--max-messages`; its scheduling instructions are unchanged.
