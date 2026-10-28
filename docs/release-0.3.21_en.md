# Simbioza 0.3.21 — full application navigation

[Croatian version](release-0.3.21_hr.md)

Full Simbioza installations use **Workspaces** to navigate their content. With
Workspaces enabled, the automatic **HTML pages** and **Surveys** top-menu entries
appear only when the corresponding standalone content exists. HTML and surveys
are checked independently; content owned by Workspaces does not count.

This also preserves navigation after Workspaces are added to an existing HTML or
Survey installation. Drafts count, so authors keep access while preparing content.
Deleted or disabled HTML pages and archived surveys do not count. After the last
standalone page is moved into a Workspace or removed, its automatic entry goes
away. The same applies after the last standalone survey is placed or archived.

Upgrading from 0.3.20 removes unnecessary automatic entries during normal startup.
Focused installations without Workspaces retain their content entries even before
their first page or survey is created. Custom administrator links are preserved.
Existing content URLs, attachments and permissions remain unchanged.

Update through the secured-FPM GUI or the documented CLI updater. This release
does not change module versions or add database migrations. Docker installations
use `ghcr.io/kmihalj/simbioza:0.3.21` with their existing persistent volumes.

See the [modularity guide](modularity_en.md), [Docker guide](docker_en.md) and
[module versions](module-dependencies_en.md).
