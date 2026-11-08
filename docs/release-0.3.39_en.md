# Simbioza 0.3.39 — reliable HTML editing and current documentation

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.46](release-0.3.46_en.md).

HTML form fields now preserve the complete supported editor content through Apply, Save, reload and re-editing. This includes cards, columns, alerts, tables, tabs, expandable blocks, images, video, safe embeds, charts, timelines and canonical integration references. The stored editable source is kept separate from its rendered preview. Existing media spacing, table appearance and header colours remain unchanged when the editor opens; temporary author actions are not saved as page content.

## Modules and documentation

HTML Editor **0.3.17** and Survey **0.3.6** include the shared-field corrections and regression coverage. The coordinated module releases refresh their English and Croatian documentation. Development plans and obsolete acceptance notes have been removed from the product documentation; supported 0.3.x release history remains available.

The application includes **31 guide chapters in Croatian and English (62 language pages)**. They cover installation, everyday use, permissions, backups, calendars and role-separated survey guidance, including the Survey API. The revised **Editing pages** chapter explains the current toolbar, tabs, accordion, dropdown content, table sorting and pagination, media, publication and page permissions in practical steps. It includes 15 new screenshots per language, captured in the light Simbioza theme.

Developer instructions for embedding HTML Editor in another application and the complete PHP/controller, view, JavaScript and storage-adapter example are maintained in the **HTML Editor module Git repository**, rather than installed in Simbioza's user manual. The [integration reference](html-editor-forms_en.md) links to those module-owned sources. Existing Survey API developer chapters remain in the user manual.

## Additional corrections

- Personal Workspace API access uses the registered `workspace:read` permission. Other users' private content remains inaccessible.
- Calendar **0.3.4** / Workspace archives preserve complete meeting data, validate access to referenced calendars and keep unrelated resource-calendar events outside the selected scope. New archives use schema 2; ordinary schema-1 event archives remain readable, while incomplete legacy meeting archives are rejected before restore.
- New CLI backup exports are recorded for configured retention cleanup. Only tracked expired exports in the configured archive directory are removed; untracked archives and manual retention remain unchanged.

## Upgrade and verification

Framework remains **0.0.25**, unchanged. No application-specific configuration is replaced by this release. Existing guides are refreshed while retaining their identities and access rights. Production installations keep their normal GUI/CLI update path; the separately maintained Demo extension uses its own operator-run upgrade procedure.

Release checks include the module suites, full editor runtime round trips, real HTTP form-field persistence, the integrated application scenarios, package/archive validation and release CI. See [installation](installation_en.md), [the complete form-field guide](html-editor-forms_en.md) and [module dependencies](module-dependencies_en.md).
