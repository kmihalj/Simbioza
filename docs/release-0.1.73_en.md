# Simbioza 0.1.73

This release refines attachment editing, administrator-menu upgrades, and
available component release checks.

## Attachment editing

Metadata fields and action buttons now form the first and most important row of
an attachment card. The original filename, MIME type, size, version, uploader,
and localized timestamp appear below in an aligned responsive grid. File
history remains a separate section below those facts. The layout reflows on
mobile screens without horizontal overflow. The information row uses the active
theme colors, keeping it clearly readable in both light and dark modes.

## Settings and new modules

The updater preserves every existing administrator-managed menu setting. A new
entry delivered by a module or feature is added only when missing and always at
the end. This also adds **Setup** to existing installations without reordering
or overwriting their current settings.

An installation still using an updater from an older release should fetch the
new `update.php` before its first upgrade to 0.1.73. This ensures the new
settings merge runs during that same upgrade pass.

## Release checks

Development installations without `composer.lock` now discover public
repositories from Composer's installed inventory. A transient network failure
is retried once, while a real persistent failure remains clearly marked only on
the affected component.
