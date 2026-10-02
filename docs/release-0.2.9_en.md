# Simbioza 0.2.9

The optional Confluence Import module is upgraded to 0.1.42.

Dates stored only in an empty Confluence `time` element's `datetime` attribute now become visible text before HTML sanitization. This preserves dates in tables, paragraphs, and supported rich-text macros, including after editing and saving the imported page. Existing visible date labels are retained; date values and time zones are not changed.

Historical attachment versions now resolve their binary files using the original logical attachment ID. Exports using the historical record ID are still supported, and an exact requested version takes priority over a newer fallback. This fixes false “attachment not found” errors for older versions present in the archive.

Regression tests cover both fixes in the module and the integrated browser import/reimport workflow. No new translations or database migrations are required.

Make a restorable backup before updating. Existing installations without Confluence Import do not have it installed automatically. Installed copies are upgraded through the normal application/module update. To recover dates missing from previously imported content, repeat the import from the original ZIP after upgrading; updating alone does not rewrite existing pages. Review identity and permission mappings before confirming a replacement import.
