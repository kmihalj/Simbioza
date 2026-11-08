# Simbioza 0.3.36 — all themes, galleries and durable theme choices

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.45](release-0.3.45_en.md).

This release includes all **26 themes**, including Orbit, the first twelve-theme collection and eight creative themes for sport, schools, preschools, film, art and communities. Every theme has light and dark appearances. Theme module: **0.3.12**. Framework remains **0.0.25**, unchanged.

## Installation and upgrades

Missing collection themes are imported once. Existing themes, administrator edits, the active selection and mode remain unchanged. The persistent receipt in `HPH_CONFIG_PATH/theme/collection.json` prevents subsequent updates from restoring deleted additional themes. Site backup/restore includes this receipt. The same behavior applies to fresh installations, classic CLI/GUI updates and persistent Docker installations.

[Download all 26 themes, their gallery and individual import ZIPs](https://github.com/kmihalj/Simbioza/releases/download/0.3.36/Simbioza-0.3.36-themes.zip). Extract the collection before importing one `themes/<id>.zip` under **Settings → Theme → Import complete theme**.

## Galleries and presentation

- [All 26 light/dark previews](theme-gallery_en.md), also in the Theme module documentation.
- The **Simbioza** user-guide page includes previews of every theme; images are local application assets and work without Wiki through the existing Guides interface.
- Hero settings for the eight newest themes use compact inner-page geometry.
- Desktop breadcrumbs clear navigation; tablet hero text avoids artwork; mobile header controls wrap when needed.

The public Demo adds a theme-gallery page and a visitor-only theme selector through its separately maintained Demo extension. A selection is remembered in that browser for up to 30 days. It never changes global settings and is not installed on normal production instances.

## Verification and update

Focused import, deletion/reimport, rollback, request-isolation and gallery checks accompany the integrated suite and release CI. Runtime settings and Framework modifications are excluded from the release. Normal secured FPM installations can use **Settings → Application update**; other classic installations use `php update.php` from their application directory. Keep an application backup before an update.
