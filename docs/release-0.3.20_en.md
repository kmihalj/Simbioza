# Simbioza 0.3.20 — the complete application, with modular flexibility

[Croatian version](release-0.3.20_hr.md)

> Historical release note. See the [current installation guides](installation_en.md)
> and [Simbioza 0.3.23](release-0.3.23_en.md) for current instructions.

Simbioza combines pages, a Wiki, calendars, surveys and collaboration in one complete application. The modular HeartPhrame framework also allows focused HTML website, Calendar, Wiki and Survey installations for those who do not need every feature. Auth, ORM, Menu, Theme, Backup and Audit form the mandatory foundation.

## Changes

- Adding Workspaces to an existing HTML website retains standalone pages and
  URLs. Administrators can explicitly move pages with translations, history and
  attachments into a Workspace, with repaired links and destination permissions.
- Home and About use theme-controlled content cards for accent and shadow.
- Home and About explain the available combinations. Installed content has
  clear entry points, and settings show the features available on that site.
- Without Wiki, **Guides** provides the complete built-in HTML manual with
  chapter navigation. With Wiki, the existing user-guide Workspace is used.
- HTML Editor 0.3.9 adds user/group authoring rights and public, signed-in or
  restricted page reading. API requests, page assets and attachments enforce
  these rights. Draft content does not become public through page settings.
- Task 0.3.3 and Comment 0.3.3 respect delegated HTML editing permissions.
- Calendar 0.3.1 shows event details to public and read-only visitors.
- New installations receive a separate session cookie name, including sites
  sharing one hostname. Backup restore preserves the destination identity.
- English and Croatian guides, bundled guide archives and all six interface
  language packs are updated. Optional integrations remain optional.

## Updating

Use the secured-FPM GUI updater or the documented CLI updater. Existing sites
retain their selected optional modules and their data. The six foundation
modules are required. An installed HTML Editor, Calendar, Task or Comment
receives its matching release. Framework 0.0.25 and Auth 0.3.1 are unchanged;
this release adds no database migrations.

The provisioned Demo uses its existing private updater, which restores its
clean baseline before updating and captures a new reset baseline afterward.
Visitor-created surveys are cleared by that reset.

Docker deployments use `ghcr.io/kmihalj/simbioza:0.3.20`. Persistent module
selection is retained when replacing the image. See the [Docker guide](docker_en.md).

See [modular installation examples](modularity_en.md),
[built-in guides](bundled-manual_en.md) and
[module dependencies](module-dependencies_en.md).
