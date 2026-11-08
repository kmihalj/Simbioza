# Simbioza 0.3.46 — personal work and clearer content navigation

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.47](release-0.3.47_en.md).

## Return to your work

The account menu now includes **My work**, with separate views for recent contributions, drafts and content awaiting your publication review. Workspace pages, standalone HTML pages and surveys have independent, bounded lists. Every request checks current permissions; past authorship does not restore revoked access. Survey collaborators' successful edits and review actions are recorded with their own timestamps, without changing ownership or linking survey respondents to this authoring history.

The user guide includes a new **My work** chapter beneath **Sign in and my profile**, with updated profile, search and group-management instructions in Croatian, English, German, Spanish, French and Italian. New Croatian and English screenshots use the light Simbioza theme.

## Find and manage content

- Workspace search can filter existing labels, structured properties and publication status. Searchable selectors load their options in small server-side pages and enforce content permissions before offering values.
- Group deletion is an explicit action with a confirmation showing its consequences. An empty group name no longer deletes a group. The confirmation detects intervening membership, exclusion or mapping-rule changes.
- Saving a profile redirects to a fresh GET page, preserving entered values on validation errors and allowing normal language switching after a successful save.
- HTML document and editor language selectors offer only installed, active languages while preserving stored translations in other languages.

## Theme collection

The distributable collection contains **23 themes**. Three installation-specific themes, `aai`, `dabar` and `srce-sup`, are no longer bundled or shown in the public galleries. Upgrade cleanup retires unchanged copies installed from the old collection once; customized or independently installed copies remain. If an unchanged retired theme was active, the installation falls back to Simbioza while retaining its appearance mode and enabled setting. Explicit later imports are respected.

Release checks validate the canonical theme packages independently of any installation-specific theme library.

Download the [theme collection](https://github.com/kmihalj/Simbioza/releases/download/0.3.46/Simbioza-0.3.46-themes.zip) or browse the [gallery](theme-gallery_en.md).

## Upgrade

Use the normal GUI update on a correctly configured secured PHP-FPM installation, or the documented CLI procedure on other classic installations. Demo uses its separate operator-managed support update; production installations keep their existing behavior.

Survey **0.3.8** adds the migration `20261107000100_install_survey_author_activity_schema`. Survey backups include the new author activity records, restore user identifiers through the existing mapping, and remain compatible with older archives that lack these records. No respondent answers or identity rules are changed. Older collaborator activity that was never stored is not invented.

The coordinated modules are Auth **0.3.5**, HTML Editor **0.3.20**, Survey **0.3.8**, Theme **0.3.15** and Workspace Search **0.3.4**. HeartPhrame Framework remains **0.0.25**, unchanged. See [installation](installation_en.md), [Docker deployment](docker_en.md) and [release history](releases_en.md).
