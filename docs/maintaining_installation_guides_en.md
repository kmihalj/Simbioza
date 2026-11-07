# Maintaining installation guides and the complete manual

[Croatian version](maintaining_installation_guides_hr.md)

The complete public manual has **90 chapters in Croatian, English, German, Spanish, French and Italian**. Its canonical source map is `resources/installation/manual-sources.json`; most translated chapters are under `docs/user-guide/`. `resources/installation/manual-assets.json` maps referenced screenshots and illustrations to their maintained files and checksums. Keep each language separate and preserve useful images, portable links and existing page identities.

## Choose the right source

Four introduction/installation pages retain their existing root sources:

| Published page | Sources under `docs/` |
| --- | --- |
| Simbioza | `simbioza_overview_en.md` / `simbioza_overview_hr.md` |
| Installation | `installation_*` plus `modularity_*` |
| Apache mod_php installation | `installation_mod_php_*` |
| PHP-FPM installation | `installation_fpm_*` and `installation_fpm_secured_*` |

Here `*` means the separate `hr.md`, `en.md`, `de.md`, `es.md`, `fr.md` and `it.md` files. Each installation walkthrough is complete; readers do not need prerequisite fragments from a different walkthrough. The native PHP-FPM page contains ordinary and secured FPM procedures with `#ordinary-fpm` and `#secured-fpm` anchors.

The other chapters come from `docs/user-guide/`: pages and Workspaces, calendars and meetings, surveys, collaboration, profiles, accessibility, themes, and menus. Reader and author procedures are separate from the 15 administration chapters and 12 API chapters covering 123 concrete operations. HTML Editor integration in another application is documented separately in its module repository and is not an installed user-guide chapter. Consult the manifest before editing: a maintained copy under another path is not automatically a packaging source.

## One manifest defines the complete hierarchy

`manual-sources.json` defines each chapter's stable `slug`, parent `parent`,
title, and source list for each language. Register new chapters here with both
translations. `BundledGuideIndex` validates unique slugs, allowed source paths,
known parents, and the absence of cycles, then orders parents before children.
An invalid existing manifest stops processing rather than silently omitting
chapters.

The generator and `BundledAssetsUpdater` use the same complete index. Historical
`GUIDE_PAGES` is not a second chapter list to extend manually: it retains old
receipt and recovery keys and supports older packages without a manifest.
Parent references use actual archived UUIDs, including historical identities;
do not derive replacement UUIDs from chapter names.

Updates reconcile the managed chapters' `parent_id` while preserving their UUIDs,
existing order, and ACLs. Unrelated user pages are not reorganized. Applying the
same manifest again must not duplicate pages or repeat an already completed
parent change. Verify a hierarchy change in both the full seed and individual
page packages.

## Rebuild and verify all chapters

From the application checkout with Composer dependencies installed:

```sh
php scripts/sync_user_guides.php --apply
php scripts/sync_user_guides.php --check
php scripts/sync_installation_guides.php --check
```

The full generator uses the source and asset manifests, rebuilds the complete public Workspace seed and individual managed-page packages, and includes the existing installation/overview pipeline. It is the normal command after revising the manual. Preserve page/document identities, permissions, current referenced attachment versions, and their blobs; parent links follow the complete manifest. Packages contain public documentation, never private configuration, live surveys or respondent data.

`--apply` needs Pandoc (`brew install pandoc` or `sudo apt-get install pandoc`) and prepares archives in an isolated build directory. It does not update an installed database. Check modes work offline and detect source/package drift without requiring Pandoc. Run the guide/archive and installation tests as well; matching source hashes alone does not prove clean import, language filtering or working images. Review both languages through native Wiki and `/guides` without Wiki, using a guest account for public images and links.

## Installation-only maintenance

For a change limited to the four root pages, the narrower tool remains available:

```sh
php scripts/sync_installation_guides.php --apply
php scripts/sync_installation_guides.php --check
```

It updates **five archives**: the complete `korisnicke-upute.zip` seed plus four individual page archives. It does not rebuild all other manual chapters. Use the full generator before completing a revision that includes chapters under `docs/user-guide/` or their assets.

Do not edit encrypted HTML payloads independently of their sources. If a supplied guide is edited directly in the development Wiki, backport it to the corresponding Markdown before packaging; otherwise a later managed update may replace it. The public seed passphrase is not a secret and must not protect private operational backups.

## Installed sites and releases

A development checkout may contain newer documentation than an installed release. Changing Markdown or rebuilding packages does not change stored pages by itself. A fresh installation imports its shipped packages; the application updater refreshes every managed chapter and its manifest-defined parent hierarchy through `BundledAssetsUpdater`, preserving identities, access rules, existing order, and unrelated pages. Without Wiki, `/guides` reads the shipped full seed directly.

Archive links are application-relative. Installation/update applies the destination prefix exactly once, including nested installation paths. Link reconciliation repairs known bundled-guide links with repeated prefixes or recorded source addresses only when the shipped destination exists locally. It does not redirect unrelated external links or code examples, or replace theme settings, page identities, access permissions or workflow.

A local documentation refresh affects only the chosen development installation. Other installations receive the rebuilt public assets through their normal upgrade. Published release archives remain historical snapshots; do not move an existing tag to replace their documentation.
