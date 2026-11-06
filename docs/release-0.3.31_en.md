# Simbioza 0.3.31 — theme-aware script settings and table controls

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.37](release-0.3.37_en.md).

[Croatian version](release-0.3.31_hr.md)

HTML Editor script settings use the active theme's code background, text and border colours. Nested code no longer creates a separate coloured strip. Both library loading snippets and optional startup code follow light and dark mode.

DataTables pagination displays first, previous, next and last arrows while retaining the original localized accessible names. Sorting indicators use the table header palette: available directions remain visible before sorting, and the active direction is emphasized. Ordinary tables are unaffected.

These adjustments are supplied through theme CSS. Existing approved DataTables definitions, script identifiers, integrity hashes and per-page selections remain unchanged. No script gains approval or activation during upgrade.

Includes HTML Editor **0.3.12** and Theme **0.3.8**. Framework remains **0.0.25**. No new database migration or configuration reset is required. Installation guides and their bundled copies use the current release and module inventory. See [installation](installation_en.md) and [module dependencies](module-dependencies_en.md).

Light and dark mode, Croatian and English controls, keyboard sorting, pagination and unchanged ordinary tables were checked using the built-in browser. Module checks, application CI, isolated upgrade preservation and release packages are verified separately before publication.
