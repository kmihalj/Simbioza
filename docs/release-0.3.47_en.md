# Simbioza 0.3.47 — reliable guide navigation

## Contents links in the user guide

The table of contents in **Login and users** now opens the selected section correctly. All **90 contents links** across Croatian, English, German, Spanish, French and Italian use the existing stable section anchors, including sections whose headings contain numbers or accented characters.

The corrected chapter is included in both the complete manual and its individual update package. Existing guide page identities, permissions, text and screenshots are preserved. The same content serves native Wiki guides and standalone HTML guides.

## Regression coverage

Automated checks cover **91 chapters, 546 translated pages and 234 fragment references** within and between chapters. They verify that every reference still has exactly one target after the HTML editor's normal sanitization and outline rendering, for both native Wiki and standalone guide routes.

## Upgrade

Use the normal GUI update on a correctly configured secured PHP-FPM installation, or the documented CLI procedure on other classic installations. Demo keeps its separate operator-managed update procedure.

This is an application and documentation patch. Module versions remain as in **0.3.46**; no new module installation or database migration is introduced. HeartPhrame Framework remains **0.0.25**, unchanged.

See [installation](installation_en.md), [Docker deployment](docker_en.md) and [release history](releases_en.md). The unchanged collection of **23 themes** is available in the [release theme package](https://github.com/kmihalj/Simbioza/releases/download/0.3.47/Simbioza-0.3.47-themes.zip) and the [gallery](theme-gallery_en.md).
