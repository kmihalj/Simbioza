# Simbioza documentation

[Croatian documentation](index_hr.md)

Simbioza combines pages, a searchable Wiki, calendars and meetings, surveys and team collaboration. These guides describe the current 0.3.x application and its reusable modules. The HeartPhrame Framework is an upstream dependency maintained separately.

## Choose a starting point

| You want to… | Start here |
| --- | --- |
| Use pages, Workspaces, calendars or surveys | [User guide and chapter navigation](bundled-manual_en.md) |
| Install Simbioza | [Choose an installation method](installation_en.md) |
| Install only a focused set of features or extend an existing site | [Modular installation options](modularity_en.md) |
| Operate, update or back up an installation | [Operations and troubleshooting](troubleshooting_en.md) |
| Embed the HTML editor in a field of your own application | [HTML editor in form fields](html-editor-forms_en.md) |
| Connect another system through the API | [API contract](api-v1-contract_en.md) |
| Customize the appearance | [Theme collection](theme-collection_en.md) and [light/dark gallery](theme-gallery_en.md) |

## Install and maintain

- [Apache mod_php](installation_mod_php_en.md), [ordinary PHP-FPM](installation_fpm_en.md), or [secured PHP-FPM](installation_fpm_secured_en.md): choose one complete walkthrough.
- [Docker installation and image upgrades](docker_en.md).
- [Required dependencies and optional integrations](module-dependencies_en.md).
- [Application and module configuration](configuration_en.md), [database configuration](database_en.md), and [Workspace data layout](workspace-data_en.md).
- [Logs and rotation](logging_en.md), [activity audit versus technical logs](audit-logging_en.md), and [troubleshooting](troubleshooting_en.md).
- [Language packs and translations](localization_en.md).
- [Maintaining the bundled user guide](maintaining_installation_guides_en.md).

## Use and customize

- [Simbioza overview](simbioza_overview_en.md).
- [User guide with and without Wiki](bundled-manual_en.md).
- [Personal Workspaces](personal-workspaces_en.md) and [personal appearance](personal-appearance_en.md).
- [Themes, import and deletion](theme-collection_en.md), [creative themes](creative-themes_en.md), [all 26 previews](theme-gallery_en.md), and [branding](branding_en.md).

The installed **User guides** Workspace contains the practical instructions for sign-in, pages, calendars, meetings and surveys. Without Wiki, **Guides** presents the same published chapters as HTML. The guide offers only translations that both exist and are installed; installing another interface language does not translate the manual.

## Develop and integrate

- [Embed the HTML editor in a form field](html-editor-forms_en.md).
- [Project structure](project-structure_en.md), [common workflows](common-workflows_en.md), and [module registration](modules_en.md).
- [API v1 contract](api-v1-contract_en.md), [shared services](common-services_en.md), and [request lifecycle](request-lifecycle_en.md).
- [Routes](routes_en.md), [middleware](middleware_en.md), [views](views_en.md), and [sessions](sessions_en.md).
- [Encryption](encryption_en.md) and [event dispatch](event-dispatcher_en.md).
- [Documentation and example checks](documentation-quality_en.md) and [end-to-end verification](end-to-end-testing_en.md).

Every reusable module has its own README and language-separated documentation in its source repository. Use the module's current contract for host integration; enabling a module never replaces the host application's authorization or persistence rules.

## Release history

[Supported 0.3.x release notes](releases_en.md) record what changed in each version. They are historical references, not the starting point for a new installation. Current examples and commands belong in the guides above.
