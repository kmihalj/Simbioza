# Simbioza 0.3.7 — bundled language revisions

> Historical release note. For current installation instructions, use the [installation guide](installation_en.md).

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/release-0.3.7_hr.md)

This application patch recognizes built-in translations on new and upgraded
installations. Framework remains **v0.0.25**, API **0.3.1**, other public modules
**0.3.0**, and the private DEMO module **0.3.6**. Updater **1.0.8** and database
migrations are unchanged. Existing tags are not moved.

## Changes

- Setup shows **Bundled translations** and the installed Simbioza version for
  the built-in English/Croatian catalogues. It no longer asks for a one-time CLI
  language installation merely to recognize a fresh installation.
- The installed application version and the available repository-pack revision
  are explicitly separate. Bundled translations are not falsely recorded as
  an installed external pack.
- A later repository digest change can be installed through **Update**, even
  if the publisher keeps the same language revision. The real pack revision is
  then recorded, preserving the language's prior enabled/disabled state.
- Manual translations remain identifiable and are not automatically replaced.
  A verified replacement can be explicitly confirmed in the GUI.
- Fresh and upgraded installations need no data/configuration migration or
  extra language commands. Existing runtime translations, flags and receipts
  are preserved. Runtime state stays under `data/languages/`; user settings
  continue to follow `HPH_CONFIG_PATH`.
- Secured-FPM guides distinguish an existing HTTPS vhost with an Alias from
  a newly created standalone `simbioza.conf` **before** the Apache commands.
  The latter's activation commands stop after failure.
- Installation commands, examples and bundled EN/HR guides use **0.3.7**.
  Native Docker AMD64/ARM64 images retain configuration/data and database volumes.

## Updating

On a configured secured-FPM site, use **Settings → Setup and modules →
Check for a new release**, then start the **0.3.7** application update.
After completion, refresh Setup → Languages: built-in languages show
`Simbioza 0.3.7`, unless you deliberately installed an external or manual pack.

See the [localization guide](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/localization_en.md),
[complete secured-FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/installation_fpm_secured_en.md)
and [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.7/docs/docker_en.md).

For the already provisioned local DEMO installation, run its private
`data/demo-local/updatedemo.sh --check` followed by `data/demo-local/updatedemo.sh 0.3.7`
from the DEMO root. This explicit maintenance script restores the clean reset
baseline before updating, verifies the result and captures a new baseline.
Do not run the ordinary application update directly over visitor changes.
Publishing this release does not update DEMO or apps-test automatically.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.7)
