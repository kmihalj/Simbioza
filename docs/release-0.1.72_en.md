# Simbioza 0.1.72

This release adds module and language management, secure GUI Setup through a
dedicated PHP-FPM pool, and complete, clearer attachment versioning.

## Modular installation and Setup

A new installation contains only required modules, plus the recommended Theme
module unless explicitly deselected. Optional modules may later be installed,
enabled, disabled, or removed through CLI. With a correctly configured dedicated
FPM pool, the administrator can perform the same operations, check versions, and
start a complete application update under **Settings → Setup and modules**. The
GUI never executes arbitrary commands directly: requests pass through a private
queue and a strictly allowlisted helper.

Setup shows installed and available application/module versions, diagnoses the
owner and permissions of important paths, and adapts to mobile screens. New
bilingual tools and guides export all translatable strings and import a validated
language pack with its flag and localized language names.

## Dedicated FPM and SAML

The installer and documentation support separate `fpm-simbioza` and
`simbioza-deploy` accounts, separate deploy/read/runtime groups, and an allowlisted
Setup worker. The root-owned helper entry accepts only a validated request ID,
and systemd executes the action as the unprivileged deploy account. The Linux
pool has its own systemd service, `ProtectSystem=strict`, read-only code, and
only explicitly listed writable runtime paths. A private
SimpleSAMLphp configuration can be bound only to that pool. The
application and its `/simbioza/simplesaml/` endpoint must use the same pool; the
guide covers EntityID, ACS and SLO registration, a no-outage transition from the
existing login, and final checks.

## Attachments and Confluence import

Uploading the same file name again now creates a new version. The current version
shows its version number, MIME type, size, uploader, and timestamp. History and
older downloads are available only in the editor to users allowed to edit,
publish, or manage the page. Both views are more compact on desktop and avoid
horizontal overflow on mobile screens.

Backup, restore, copy, move, and Confluence import preserve versions, metadata,
and attribution. Imported pages no longer depend on temporary Confluence tables
during normal viewing, while ancestor and attachment reads use request caching
and batch queries.

## Permissions and large downloads

Manage now includes every action, while Add lets an author continue editing only
pages created by that author. A publisher may open the editor to work with
attachment metadata and history. Large downloads refresh their execution timer
only while transfer progress continues, so a short PHP limit no longer interrupts
a healthy response.

The updater creates a safety backup before applying the new attachment-version
and Confluence-attribution migrations.
