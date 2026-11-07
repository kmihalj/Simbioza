# User guides with and without Wiki

[Croatian version](bundled-manual_hr.md)

Simbioza ships one public manual with **90 chapters in Croatian, English, German, Spanish, French and Italian**, including screenshots. It covers installation, everyday use, calendars and meetings, surveys, collaboration, themes, menus, administration, and the API of all available business modules. Installing Wiki is not required to read it. The visible language choices follow the languages installed in your application.

## Choose a guide for your role

| You want to… | Open |
| --- | --- |
| Read content and manage your own profile | [My profile](user-guide/osobni-profil_en.md) and [Reading Workspaces](user-guide/podrucja-citanje_en.md) |
| Write pages or manage content | [HTML editor](user-guide/uredivanje-stranica_en.md), [standalone pages](user-guide/samostalne-html-stranice_en.md), and [Workspaces](user-guide/podrucja_en.md) |
| Organize events and meetings | [Calendars](user-guide/kalendari_en.md) and [Meetings](user-guide/sastanci_en.md) |
| Create or answer surveys | [For authors](user-guide/ankete-autori_en.md) or [for respondents](user-guide/ankete-ispunjavanje_en.md) |
| Customize appearance or navigation | [Themes](user-guide/teme_en.md) and [Menus](user-guide/izbornici_en.md) |
| Configure the entire installation | [Simbioza administration](user-guide/admin-pregled_en.md) |
| Integrate your own application | [Complete API manual](user-guide/api-koristenje_en.md) |

Administrator procedures are separate from everyday use. The developer API has 12 chapters: the main index, getting started, users and groups, Workspaces, HTML pages, calendars, surveys, tasks, notifications and follows, webhooks, audit and search, and other protocol boundaries. It covers **114 declared REST routes and 123 concrete operations**, because the generic survey change route supports several distinct actions. Examples explain permissions and parameters and show cURL/PHP requests and actual responses. CalDAV/ICS are described separately from REST.

## Installations with Wiki

The installer imports the manual as ordinary pages in the **User guides** Workspace. Use its page tree and language dropdown to navigate. Translations, attachments and normal page editing remain available according to the Workspace permissions.

Application updates refresh release-managed guide pages while preserving their identities and existing access permissions. Managed chapters follow the parent hierarchy in the shipped manifest; identities, existing order, and unrelated Workspace pages are not replaced. If you maintain local edits to a supplied guide, keep a separate copy or contribute the change to the maintained sources before updating: a managed page’s supplied text may be refreshed by a later release.

## Installations without Wiki

Choose **Guides** in the top menu. Simbioza renders the same prepared manual as HTML pages, without creating a Workspace or installing HTML Editor solely for documentation.

- Select a chapter under **Guide contents**. On a small screen, expand it above the page text.
- Continue with **Previous chapter** and **Next chapter**.
- Use the flag dropdown to select any manual language installed in the application.
- Screenshots and other bundled illustrations remain available. Links between chapters stay inside this installation.

These pages are read-only and come from the installed release. They do not depend on live test surveys or another site’s database. Updating Simbioza updates this packaged copy; it is not a second editable content repository.

## Addresses and languages

`/guides` opens the introduction. `/guides/ankete?lang=en`, for example, opens the survey chapter without Wiki or redirects to its native Workspace page when Wiki is enabled. Prefix every path with the installation subdirectory when applicable. The public guide-asset endpoint serves only verified files referenced by published bundled guide pages, not live document attachments, private configuration or backups.

The interface uses the installation’s active language settings. The manual offers translations available in the package and installed in the application. All 90 chapters are supplied in Croatian, English, German, Spanish, French and Italian. Croatian and English use their matching screenshots. German, Spanish, French and Italian reuse the English-interface screenshots, with a notice and translated descriptions. Screenshots are examples, so names and dates may differ from your installation.

## Maintaining the manual

Maintainers edit the separate language sources under `docs/user-guide/` and the installation/overview sources listed in `resources/installation/manual-sources.json`. The asset map is `resources/installation/manual-assets.json`. These sources generate both the native Wiki packages and the manual displayed without Wiki; edits are not maintained independently in two formats.

See [Maintaining the installation guides and complete manual](maintaining_installation_guides_en.md) for source selection, rebuilding and verification. Rebuilding packages alone does not change a live database or upgrade another installation.
