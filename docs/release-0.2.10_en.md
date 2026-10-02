# Simbioza 0.2.10

This release coordinates HTML Editor 0.1.39, the optional Confluence Import
module 0.1.43, and updated translations in all six supported languages.

## Explicit attachment history

Confluence imports transfer only current attachment versions by default.
Earlier versions are transferred only when **Page and attachment history** is
selected. Leaving this option unchecked no longer imports attachment history.

The completion summary and permanent report separate current attachments from
historical versions. Failed historical versions are clearly distinguished from
missing current files. Older completed imports also receive this clearer report
without changing their imported content or stored job records.

## Source image presentation

Imported images retain the width or height specified by Confluence, their
proportions, and center/right alignment. The editor preserves these settings
when opening and saving a page. Images still shrink proportionally on narrow
screens, and authors can explicitly choose the editor's existing width presets.

## Update and verification

Regression tests cover default/history-enabled imports, replacement imports,
attachment downloads, reports, and safe image sizing. The actual NR archive was
also imported in an isolated browser test: 246 pages and 378 current attachments,
with no transfer failures and no attempt to import its 61 historical versions.

Make a restorable backup before updating. No new database migrations are added.
Existing installations without Confluence Import do not have it installed
automatically. The normal application update upgrades installed copies together
with the required editor version and refreshes installed language packs.

Updating does not rewrite existing imported pages or repeat an import. To apply
the improved image presentation to existing content, repeat the import from the
original ZIP after upgrading, reviewing the replacement, identity, and permission
options before confirming it. Do not enable history unless older versions are
actually required.
