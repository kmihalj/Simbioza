# Workspace and page data storage

[Croatian version](workspace-data_hr.md)

Installation configuration belongs to `HPH_CONFIG_PATH/<module>/`. Workspace
content is application data and stays below `data/`, regardless of the configuration
directory. The GUI, public page routes, attachment UUID URLs and access checks are
unchanged.

## Directory layout

```text
data/workspaces/<storage-slug>--<workspace-ID>/
  pages/<page-label>--<document-UUID>/
    content/<language>/versions/000001.html
    content/<language>/draft/<filename>.html
    attachments/<stored filename>
    menus/<context ID hash>.json
  menus/<context ID hash>.json
  theme/<private theme files and assets>
```

Filesystem HTML versions and drafts live beside the page's attachments. Readable
ASCII labels are limited to 48 characters; the workspace ID and full document UUID
prevent collisions. `workspaces.storage_slug` retains the initial workspace label,
while existing file references retain the page label after a document rename or trash/restore.
Displayed names and public URL slugs are not filesystem identities. Attachment versions share the page's attachment
directory; their existing stored names distinguish historical bytes.

With database HTML storage, HTML payloads remain in the database and no HTML
directory is needed. Metadata, permissions and workflows remain in the database
for both drivers. Attachments remain files for both drivers. Existing Editor
storage migration operations use this layout for filesystem → database and
database → filesystem. New Confluence imports use the same Editor/Workspace
integration for initial HTML, later versions and attachment history.

The database uses validated `@data/workspaces/...` references for owned files,
not absolute deployment paths. These references are internal storage identifiers,
not public download URLs. Trash/restore retains the page identity and file references.

## Ownership and shared data

Only a document with one declared workspace owner gets an owned directory.
Standalone documents and documents shared by multiple workspaces use the Editor's
configured shared roots. Generic Editor and Menu still work without Workspace.

Special menus declare `owner_target` as `workspace.<ID>` or
`workspace.<ID>.page.<node ID>`. Arbitrary route patterns alone do
not establish ownership. Workspace/page-owned menu definitions live in the
corresponding `menus/` directory. `data/menu/contexts.json` remains a small shared
index of `menu_ref` paths and inline unowned contexts. It does not duplicate owned
definitions. Manual edits must preserve valid JSON and the context ID that
determines the filename hash; the GUI reads and writes those same files.
Workspace backups select declared menu owners even when their route patterns
do not contain the workspace slug. Unowned shared contexts retain route-based
selection for shared, unowned content.

Permanent page deletion (including discarding a never-published page) removes
its explicitly owned menu files and index references before the tree node is
removed. Permanent workspace deletion also removes all its page-owned menus.
Soft deletion retains them for restoration. Shared contexts are not removed just
because their route matches the deleted workspace. This cleanup needs the Menu
repository, not its optional GUI services.

Private workspace theme files live in `data/workspaces/<storage-slug>--<ID>/theme/`. Theme
definitions remain in the workspace database; shared system themes remain in
`data/themes/`. Shared caches and generated stylesheet snapshots remain shared
Editor data, not page content.

## Fresh installation and current backup

0.3.0 creates this layout directly. There are no old directory movers, recovery
journals or schema upgrades from earlier Simbioza releases. Use a new database;
do not reinstall over an old data tree.

Workspace records are in `workspaces`; tree nodes and document links in
`workspace_nodes`; HTML versions in `editor_html_document_versions`; permissions
in `workspace_acl` and `workspace_node_acl`. `storage_slug` is domain metadata,
not installation configuration.

Current format-2 backups export referenced HTML and attachment history, owned
menus and private theme bytes. Import maps destination identities, builds current
owned paths and chooses the destination's configured HTML driver. File/configuration
providers retain rollback state until restore succeeds. Old Simbioza archive
schemas are rejected; external Confluence XML import remains separate and supported.

## Links between sites

The archive records the source public site address, including its application
base path. Restore compares it with the destination address. Same-site restores
retain existing link behavior; copies still map identities to copied content.
Cross-site restore resolves internal page, attachment and included-page references
against the imported destination identities and routes. Relative and source-site
absolute links receive destination routes, retaining query strings and anchors.

Workspace link nodes and owned menu links follow the same mapping. External links
are not globally rewritten. An internal reference without an imported target stays
pointed at its source and is reported as unresolved, rather than guessed from an
unrelated destination page with the same slug. Link checking is local to archive
metadata and imported identities; it does not crawl private or external URLs.

Shared caches, generated stylesheet snapshots and files not referenced by an
exported provider are not page payloads. A portable backup is not a raw clone of
credentials, configuration paths, server ownership or deployment settings.
