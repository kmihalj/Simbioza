# Simbioza 0.3.42 — complete multilingual guides and reusable modules

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.46](release-0.3.46_en.md).

The bundled manual now contains **90 chapters in six languages**: Croatian, English, German, Spanish, French and Italian. It covers everyday reading and editing, Workspaces, calendars and meetings, surveys, collaboration, themes, menus, administration, installation and 123 API operations. Chapters are organised by task and role. Croatian and English retain screenshots of their own interfaces; the four new translations reuse the English screenshots with a clear notice and translated descriptions.

## Guides in every installation

Wiki installations keep the manual in their native **User guides** Workspace. Installations without Wiki use the same published chapters through **Guides**, including navigation and screenshots. Language choices follow the installation's installed and active languages. Document content is marked with its own language for assistive technology, independently of the interface language.

An upgrade refreshes the maintained chapters and their hierarchy, preserves page identities, order, permissions and unrelated custom pages, and adds the new translations without revision-number collisions. The package includes the current light-theme screenshots and is checked for missing images, chapter links and local anchors. Existing interface language choices and site configuration are preserved.

## Using modules in another PHP project

All **15 generic HeartPhrame modules** now have separate English and Croatian **How-to** guides in their own Git repositories. Each guide includes a complete small HeartPhrame project with only the required dependencies, installation and enablement steps, configuration, working PHP examples, expected results and links to the detailed reference. Modules can be used without Simbioza. See the [module guide directory](module-dependencies_en.md#using-generic-modules-in-your-own-project).

The HTML Editor guide retains the complete form-field example: edit HTML in a textarea, apply changes, validate and save the submitted content, then render and reopen it without losing supported elements. This developer material belongs to the module documentation, separate from the installed user manual.

## Additional corrections

- Calendar, menu and Workspace property labels follow the selected interface language.
- Scoped Workspace menu lookups use the Workspace's actual management permissions.
- JSON API endpoints consistently accept a JSON object, including an empty object, and reject top-level lists and scalars.
- Audit API pagination honours its requested page size.
- Workspace HTML exports preserve supported editor styles.
- Bundled guide updates refresh changed screenshots as well as chapter content.

## Upgrade

The application release coordinates the updated generic modules, Workspace **0.3.6**, Simbioza User **0.3.6** and language packs **2026.11.07.1**. Framework remains **0.0.25**, unchanged. No database migrations are added by this release.

Use the normal GUI update on a correctly configured secured PHP-FPM installation, or the documented CLI update elsewhere. The Demo remains an operator-managed installation using its installed Demo upgrade helper. The release does not update a running Demo or production site automatically.

See [installation](installation_en.md), [the manual](bundled-manual_en.md) and [module dependencies and How-to guides](module-dependencies_en.md).
