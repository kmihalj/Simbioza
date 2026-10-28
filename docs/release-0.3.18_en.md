# Simbioza 0.3.18 — surveys and the shared HTML Editor

[Croatian version](release-0.3.18_hr.md)

> Historical release note. See the [current installation guides](installation_en.md)
> and [Simbioza 0.3.19](release-0.3.19_en.md) for current instructions.

## Features

- **Survey 0.3.0** is a new optional module. Select it during installation or
  add it through **Settings → Modules and checks**. An ordinary upgrade makes
  it available without installing, enabling or migrating it automatically.
- Surveys support draft/review/publication, optional translations, ten question
  types, multiple pages, conditional questions, public participation,
  application users and independent SAML/CAS/OIDC/OAuth2 providers.
- Anonymous authenticated completion records are separate from answers.
  Identified responses are explicitly disclosed. Accessible charts, readership
  permissions, private CSV, scoped API resources and scheduled reports are included.
- Workspace integration adds survey pages, optional trees, dedicated author
  rights and individual-page user/group/public access exceptions without
  exposing hidden sibling pages or attachments.
- Encrypted Workspace/page backups include survey definitions, settings,
  history, responses and attachments. Full-site backup also includes Survey
  administration and independent provider configuration. Static HTML export
  deliberately omits surveys.
- The full HTML Editor is reusable for HTML fields, including media,
  sanitization and dynamic elements. Document editing uses the shared component.
- **User guides → Surveys** contains illustrated English and Croatian pages
  for authors, participants, administrators and API developers, with separate
  cURL and PHP examples. It has no dependency on test surveys or local URLs.
- The bundled Simbioza light theme uses white secondary-button text. Existing
  customizations follow normal preservation rules.

## Updating

Retain a restorable database and private-storage backup. Use the existing GUI
updater on secured-FPM installations or the documented CLI procedure on other
classic installations. Check final status, module selection and pending
migrations, then explicitly install Survey if wanted. Disabling Survey retains
its data; removal is blocked while operational tables contain rows.

Docker installations replace the image with `ghcr.io/kmihalj/simbioza:0.3.18`,
retaining runtime/database volumes. Do not use the classic updater in a container.

The private Demo has a separate operator-run preparation step which installs
all available optional modules, grants demonstration author rights and captures
a baseline without surveys. Demo reset removes visitor surveys, responses and
private files. This does not change normal installations or deploy any site.

Framework remains **v0.0.25**, Auth **0.3.1**; neither source is modified.
