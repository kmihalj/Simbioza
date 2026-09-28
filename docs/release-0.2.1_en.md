# Simbioza 0.2.1

This release fixes a PHP memory exhaustion during the verification of a large
Confluence XML archive. The import job now stores only the inventory needed for
identity mapping instead of keeping every page and attachment in its database
summary. Job lists and upload cancellation no longer load that large summary.

The later import steps can temporarily raise their own PHP memory limit to
512 MB. If the server prohibits this, the importer reports a clear memory-limit
requirement before modifying content. Administrators can adjust
`import_memory_limit_mb` in the Confluence import module configuration for
particularly large archives. PHP-FPM must permit the configured limit.

Update the application through Setup or the CLI after making a restorable
backup. Installed optional modules are updated during the application update;
the Confluence import module must reach version 0.1.38. Language packs have a
separate revision and can be refreshed through Setup when installed.

An unsuccessful upload from before this release should be cancelled before
retrying. Removing that upload does not delete the original Confluence export.
The actual import of a large archive can take time; keep the import view open
and consult the recent-jobs list or technical log if it reports an error.
