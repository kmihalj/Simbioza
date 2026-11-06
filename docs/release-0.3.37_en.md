# Simbioza 0.3.37 — reliable HTML editing and current documentation

HTML form fields now preserve the complete supported editor content through Apply, Save, reload and re-editing. This includes cards, columns, alerts, tables, tabs, expandable blocks, images, video, safe embeds, charts, timelines and canonical integration references. The stored editable source is kept separate from its rendered preview. Existing media spacing, table appearance and header colours remain unchanged when the editor opens; temporary author actions are not saved as page content.

## Modules and documentation

HTML Editor **0.3.15** and Survey **0.3.6** include the shared-field corrections and regression coverage. The coordinated module releases refresh their English and Croatian documentation. Development plans and obsolete acceptance notes have been removed from the product documentation; supported 0.3.x release history remains available.

The application includes **33 bilingual guide chapters**, with 440 referenced public assets. Guides cover respondents, survey authors, administrators and developers, as well as installation, permissions, backups, calendars and page editing. The new developer chapter shows how another application can use the full HTML editor in an ordinary form field without creating a Wiki page or Editor document. The module provides a complete controller, view, JavaScript and storage-adapter example.

## Additional corrections

- Personal Workspace API access uses the registered `workspace:read` permission. Other users' private content remains inaccessible.
- Calendar/Workspace archives preserve complete meeting data, validate access to referenced calendars and keep unrelated resource-calendar events outside the selected scope. New archives use schema 2; ordinary schema-1 event archives remain readable, while incomplete legacy meeting archives are rejected before restore.
- New CLI backup exports are recorded for configured retention cleanup. Only tracked expired exports in the configured archive directory are removed; untracked archives and manual retention remain unchanged.

## Upgrade and verification

Framework remains **0.0.25**, unchanged. No application-specific configuration is replaced by this release. Existing guides are refreshed while retaining their identities and access rights. Production installations keep their normal GUI/CLI update path; the separately maintained Demo extension uses its own operator-run upgrade procedure.

Release checks include the module suites, full editor runtime round trips, real HTTP form-field persistence, the integrated application scenarios, package/archive validation and release CI. See [installation](installation_en.md), [the complete form-field guide](user-guide/html-editor-u-obrascu_en.md) and [module dependencies](module-dependencies_en.md).
