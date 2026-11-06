# User guides with and without Wiki

[Croatian version](bundled-manual_hr.md)

Simbioza ships one public manual with **33 chapters in Croatian and English**, including screenshots. It covers installation, everyday use, calendars and meetings, surveys, administration and developer integrations. Installing Wiki is not required to read it. The visible language choices follow the languages installed in your application.

## Installations with Wiki

The installer imports the manual as ordinary pages in the **User guides** Workspace. Use its page tree and language dropdown to navigate. Translations, attachments and normal page editing remain available according to the Workspace permissions.

Application updates refresh release-managed guide pages while preserving their identities and existing access permissions; unrelated Workspace pages are not replaced. If you maintain local edits to a supplied guide, keep a separate copy or contribute the change to the maintained sources before updating: a managed page’s supplied text may be refreshed by a later release.

## Installations without Wiki

Choose **Guides** in the top menu. Simbioza renders the same prepared manual as HTML pages, without creating a Workspace or installing HTML Editor solely for documentation.

- Select a chapter under **Guide contents**. On a small screen, expand it above the page text.
- Continue with **Previous chapter** and **Next chapter**.
- Where both manual languages are installed, use the flag dropdown to switch between Croatian and English.
- Screenshots and other bundled illustrations remain available. Links between chapters stay inside this installation.

These pages are read-only and come from the installed release. They do not depend on live test surveys or another site’s database. Updating Simbioza updates this packaged copy; it is not a second editable content repository.

## Addresses and languages

`/guides` opens the introduction. `/guides/ankete?lang=en`, for example, opens the survey chapter without Wiki or redirects to its native Workspace page when Wiki is enabled. Prefix every path with the installation subdirectory when applicable. The public guide-asset endpoint serves only verified files referenced by published bundled guide pages, not live document attachments, private configuration or backups.

The interface uses the installation’s active language settings. The manual offers translations available in the package and installed in the application. Its text and screenshots are currently Croatian and English; installing another interface language does not automatically translate them. Screenshots are examples, so names and dates may differ from your installation.

## Maintaining the manual

Maintainers edit the separate EN/HR sources under `docs/user-guide/` and the installation/overview sources listed in `resources/installation/manual-sources.json`. The asset map is `resources/installation/manual-assets.json`. These sources generate both the native Wiki packages and the manual displayed without Wiki; edits are not maintained independently in two formats.

See [Maintaining the installation guides and complete manual](maintaining_installation_guides_en.md) for source selection, rebuilding and verification. Rebuilding packages alone does not change a live database or upgrade another installation.
