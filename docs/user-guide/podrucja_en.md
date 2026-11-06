# Workspaces

Workspaces are the foundation of content organisation in Simbioza. This guide covers global settings, Workspace and page-tree creation, visibility, permissions, the publishing workflow, direct permissions, restrictions, Workspace themes, static HTML export, backup/restore and mobile behaviour.

<div class="alert alert-info">

**All examples were created on a separate clean SQLite installation.** The local accounts, groups and content are fictional. Screenshots do not contain passwords, API keys, encryption passphrases or other secrets.

</div>

## Contents

1.  [Workspace, page and slug](#concepts)
2.  [Global Workspace settings](#global-settings)
3.  [Creating and managing a Workspace](#creation)
4.  [Permissions and access model](#permissions)
5.  [Tree, contents, page settings and special menus](#tree)
6.  [Editing, preview and publishing](#publishing)
7.  [Restrictions and direct permissions](#restrictions)
8.  [Role comparison](#scenarios)
9.  [Embedded content and access checks](#embedded)
10. [Workspace theme](#themes)
11. [Static HTML export](#export)
12. [Workspace backup and restore](#backup)
13. [Mobile view](#mobile)
14. [Archive, deletion, search and maintenance](#maintenance)
15. [Final verification](#checklist)

<a id="concepts"></a>

## 1. Workspace, page and slug

A **Workspace** is an isolated content area with its own page tree, home page, permissions, theme, private menus and backup. Typical Workspaces represent a project, department, public guide collection or internal knowledge base.

A **page** is a tree item. It may point to an HTML document, an external URL or another supported content type. Parent/child placement controls navigation and the scope of page restrictions.

A **slug** is a stable URL identifier without spaces, such as `project-aurora` or `work-plan`. A URL combines the root segment, Workspace slug and page slug, for example `/workspace/project-aurora/work-plan`.

- Choose short, readable and long-lived slugs.
- Changing a slug changes the URL; recheck external links, embedded content and bookmarks.
- During restore, the target slug decides whether the backup targets an existing Workspace or creates a separate copy.

<a id="global-settings"></a>

## 2. Global Workspace settings

Only administrators see this section under **Settings → Workspaces**. A manager of one Workspace cannot change these application-wide defaults.

### 2.1. General defaults and Workspace creation

The administrator sets the starting behaviour for every new Workspace. These values do not overwrite existing Workspace/page exceptions; they are defaults that can be overridden at the appropriate level.

<figure class="my-4">
<img src="images/bb7a8e05-4697-46f1-8133-6aef86b147c1.png" class="img-fluid rounded border" alt="General settings and Workspace creation" />
<figcaption>The complete section shows the root path, default visibility, initial tree and outline state, the Workspace creators group and page-summary settings.</figcaption>
</figure>

**Workspace root path**

The first URL segment used by all Workspaces. In this example pages use `/workspace/{workspace}/{page}`. The segment must not collide with a route owned by another module.

**Default visibility**

The initial ACL level for a new Workspace: Public, All signed-in users or Restricted. Saved Workspace rights remain the final authority.

**Show the tree initially**

Controls whether hierarchical navigation opens automatically. A reader can still open or close it on the page.

**The page outline is initially visible**

Controls the initial state of the table-of-contents card. A Workspace and an individual page can override it.

**Workspace creation**

Search and add users or groups allowed to open the New Workspace form. The example uses the *Workspace creators* group. Administrators always retain this ability. This is not the same as Manage on an existing Workspace.

**Page summaries**

Sets the initial tree depth, article count, order and filter visibility for the public collection of published-page excerpts. A visitor may temporarily change filters; *All* is offered only below 100 visible articles.

There is no separate Workspace owner. The **Manage** permission controls maintenance of an existing Workspace, while **Workspace creation** only controls who can create another one.

### 2.2. Public and signed-in home pages

- The **public home page** opens for guests and must be publicly readable.
- The **signed-in home page** opens after login and may live in a Workspace available to all signed-in users.

<figure class="my-4">
<img src="images/2192e663-e97b-4dd3-925e-b45f8ef92fd2.png" class="img-fluid rounded border" alt="Application home pages" />
<figcaption>A separate public home page and signed-in home page are configured.</figcaption>
</figure>

<figure class="my-4">
<img src="images/ecc5eff2-b7f3-4429-835c-855e7a25aa64.png" class="img-fluid rounded border" alt="Signed-in home page" />
<figcaption>The signed-in administrator is redirected to the internal knowledge-base home page.</figcaption>
</figure>

### 2.3. Administrative lists

Each administrative screen has a separate operational purpose:

- **All Workspaces** lists active and archived Workspaces, slugs and state, with direct links to settings. Check it for a duplicate before creating or restoring a Workspace.
- **Deleted Workspaces** contains soft-deleted entries that can still be restored. Permanent removal is a separate confirmed maintenance action.
- **Search index** shows the number/state of indexed records and rebuild actions. Rebuild after a large import/restore or when results lag behind published content.
- **Maintenance** measures storage, optimises images, cleans history/deleted items and permanently removes previously deleted Workspaces. Section 14 documents each field.
- **Personal Workspaces** controls whether personal Workspaces are enabled and how they are provisioned for users.

<figure class="my-4">
<img src="images/99fb3ec0-bc38-44e6-8f02-7391dfa56a1d.png" class="img-fluid rounded border" alt="All Workspaces" />
<figcaption>Active, archived and restricted Workspaces with links to their settings.</figcaption>
</figure>

<figure class="my-4">
<img src="images/534d9c32-1f32-4adb-b83e-6bcce163b668.png" class="img-fluid rounded border" alt="Deleted Workspaces" />
<figcaption>Soft-deleted Workspaces can be restored or permanently removed by an administrator.</figcaption>
</figure>

<figure class="my-4">
<img src="images/0a193d9b-3aa3-471d-ba77-7ef8a9892187.png" class="img-fluid rounded border" alt="Search index" />
<figcaption>Workspace indexing status and rebuild actions.</figcaption>
</figure>

<figure class="my-4">
<img src="images/daa9e806-428f-49cb-979e-84071a3e9fbe.png" class="img-fluid rounded border" alt="Personal Workspaces" />
<figcaption>Application-wide personal Workspace behaviour.</figcaption>
</figure>

### 2.4. Personal Workspace user permissions

Whether a personal Workspace is created after first sign-in, by the batch administration action, or with **Create now**, its mapped member is automatically added under **Members and permissions**. The member receives **View**, **Add**, **Edit**, **Publish**, **Delete**, and **Manage**; no follow-up ACL configuration is required.

<figure class="my-4">
<img src="images/3c6d9203-ac50-4360-972a-46bd7ab342b5.png" class="img-fluid rounded border" alt="All personal Workspace permissions" />
<figcaption>Krešimir Mihalj is automatically the only user member of the personal Workspace and has all six permissions.</figcaption>
</figure>

This does not reintroduce a general Workspace owner. Any user with *Manage* can administer a regular Workspace. A personal Workspace additionally has a stable member mapping, and the system keeps that member's complete ACL in place. If the personal Workspace predates this rule, the next successful sign-in restores all six permissions without creating a duplicate.

<a id="creation"></a>

## 3. Creating and managing a Workspace

1.  Open **Workspaces** and select **New Workspace**. The action is available to administrators and globally authorised creators.
2.  Enter a name, durable slug and description that explains the Workspace to other administrators.
3.  Select the initial page-tree and outline state, or inherit the global setting.
4.  Save. Then assign users/groups, theme and special menus and create the Workspace home page.

<figure class="my-4">
<img src="images/e6b8daf6-2220-4e0f-b2d5-432c837aa2bd.png" class="img-fluid rounded border" alt="New Workspace form" />
<figcaption>The complete form includes Name, Slug, Description, Page tree, Page outline and Save.</figcaption>
</figure>

### 3.1. New Workspace fields

- **Name** is the user-facing label in lists, the hero and the page tree.
- **Slug** is the URL identifier. Avoid temporary names if long-lived links are expected. A later change requires link and integration checks.
- **Description** explains the purpose and helps administrators distinguish similarly named Workspaces.
- **Page tree** sets the initial hierarchical-navigation state: Inherit, Show or Hide.
- **Page outline** sets the initial table-of-contents state and may be overridden by a page.

Visibility and members are not saved in this first compact form. After saving, open Workspace management and configure the **Members and permissions** matrix. This avoids partially saved ACL data during creation.

### 3.2. Managing an existing Workspace

<figure class="my-4">
<img src="images/b2a5124a-6add-4cbc-8264-c21424c9ebfb.png" class="img-fluid rounded border" alt="Workspace data and display settings" />
<figcaption>Name, Slug, Description, Page tree, Page outline, archive switch, static export and links to the private theme and special menus.</figcaption>
</figure>

<figure class="my-4">
<img src="images/a4b640cd-73db-4922-b014-6f8c1d831276.jpg" class="img-fluid rounded border" alt="Manage Project Aurora" />
<figcaption>Workspace data, default display, Theme, private menus, backup and the user/group permission matrix.</figcaption>
</figure>

- **An archived Workspace is read-only** stops changes without removing content or URLs.
- **Edit Workspace theme** opens the Workspace-private theme; theme editing is documented separately.
- **Edit Workspace menus** opens the special top and left menus described in section 5.4.
- **Manage Workspace backup** opens full Workspace export/restore.
- The export icon in the data card creates a static HTML package; it is not a recovery backup.
- **Delete Workspace** first performs a soft deletion. Restore and permanent removal remain administrator operations.

<div class="alert alert-secondary">

**A Workspace has no owner.** A user with *Manage* permission can maintain it. This includes the **Edit Workspace theme** link, used to select or create a private theme for that Workspace. The Theme editor itself is covered in a separate guide; this guide only shows where the option is located.

</div>

<a id="permissions"></a>

## 4. Permissions and access model

<div class="table-responsive">

| Permission | Allows | Important detail |
|----|----|----|
| **View** | Opening the Workspace and published pages. | Foundation of every other permission. |
| **Add** | Creating a page in the permitted tree branch. | Does not grant publishing. |
| **Edit** | Creating and changing drafts. | The published version remains visible until the draft is published. |
| **Publish** | Previewing and publishing a draft. | Suitable for a reviewer/approver role. |
| **Delete** | Archiving or deleting a page when the action is available. | Deleting a branch includes its descendants. |
| **Manage** | Workspace settings, ACL, tree, theme, menus, export and backup. | Manage includes all operational permissions. |

</div>

### 4.1. Visibility modes

- **Public**: published content is readable without login; guests never receive editing rights.
- **All signed-in users**: every active signed-in account can read published content without an explicit ACL row.
- **Restricted**: access comes from Workspace user rights, group membership or a direct page permission.

Direct user rights and all group rights are combined. A page restriction can then deny a selected user part of the inherited result.

<a id="tree"></a>

## 5. Tree, contents and page settings

The tree is one Workspace's hierarchical navigation. A manager can maintain it without opening the HTML editor: add an existing document, internal link or external link, change parent/order and open settings for every page. Prepared documents are used here; editing their contents is covered by a separate guide.

### 5.1. Workspace and page display defaults

Display settings have three levels. The global value applies when the Workspace inherits it; the Workspace value applies to its pages; an individual page has the highest priority for its outline. This allows a visible tree across a project while hiding the outline on a short landing page.

<figure class="my-4">
<img src="images/b2a5124a-6add-4cbc-8264-c21424c9ebfb.png" class="img-fluid rounded border" alt="Workspace tree and outline defaults" />
<figcaption>The manager selects Inherit, Show or Hide for the Page tree and Page outline.</figcaption>
</figure>

- **Page tree** controls the initial state of the hierarchical Workspace navigation. A reader can still use the Tree icon.
- **Page outline** controls the current document's heading list. A page chooses Inherit Workspace setting, Show or Hide.
- When a special left menu is active, it occupies the left position and the page tree starts hidden; the Tree icon can still reopen it.

### 5.2. Editing the page-tree arrangement

Select **Tree**, then **Edit tree**. Each row receives a pencil and four direction actions. Up/down changes order among siblings, left outdents one level, and right indents under the previous item. An unavailable move is disabled to prevent an invalid hierarchy.

<figure class="my-4">
<img src="images/d6dbfd3c-4ce3-4cee-88e9-7aecb608c694.png" class="img-fluid rounded border" alt="Page-tree organiser" />
<figcaption>All items, page-settings pencils, move controls, Add item and Save arrangement.</figcaption>
</figure>

Moves remain local to the organiser until **Save arrangement** is selected. Check page parents before saving because a restriction on a parent also applies to its descendants.

### 5.3. Adding and configuring a tree item

**Add item** does not always create new HTML content. The supported types are:

- **Page (HTML document)** links an existing document or creates a page when offered.
- **Internal link** targets an application named route or internal path, such as Calendars.
- **External link** targets a complete external URL. Its title and slug still control its tree presentation.

**Parent page** places the item immediately; it can later be repositioned in the organiser.

<figure class="my-4">
<img src="images/51a7dbeb-aa6d-455f-8a68-b145d95e946f.png" class="img-fluid rounded border" alt="Add a tree item" />
<figcaption>Title, Slug, Item type, Parent page and the target of an internal link are all visible.</figcaption>
</figure>

The pencil beside an item and the **Manage page and permissions** action in page contents open the same panel for administrators and Workspace managers.

<figure class="my-4">
<img src="images/b2fd51b3-d9f0-49ba-9394-071dacfe1c65.png" class="img-fluid rounded border" alt="Page settings" />
<figcaption>Title, Slug, Item type, HTML document, Workspace homepage, labels, properties and default outline display.</figcaption>
</figure>

- **Title and slug** define presentation and the page URL.
- **Workspace homepage** is opened when the Workspace URL has no page slug.
- **Page labels** support filtering and organisation.
- **Page properties** store structured text, status, number, date, user or link values for themes/modules that render them.
- **Default page outline display** overrides the Workspace only for this page.
- **Delete subtree** includes the selected item and every descendant.

<figure class="my-4">
<img src="images/0618a4bd-56f9-483c-9a42-27d075c77f0e.png" class="img-fluid rounded border" alt="Page permissions" />
<figcaption>Inherited restrictions and direct user permissions are clearly separated.</figcaption>
</figure>

### 5.4. Workspace special top and left menus

Special menus apply only on the selected Workspace route and its pages. Top and left menus are edited and saved independently; changing or removing one does not alter the other.

<figure class="my-4">
<img src="images/9eca77c3-6847-4972-913c-e2c07a4837e3.png" class="img-fluid rounded border" alt="Workspace special menus" />
<figcaption>The top context is collapsed while the active three-item left menu shows its title, order and target routes.</figcaption>
</figure>

- **Active** enables the menu context; **Remove** marks the entire context for deletion when saved.
- Protected base top-menu items may be enabled/disabled and reordered, but their label and route remain locked.
- A custom item may use a named route, direct URL and query parameters. Prefer a named route for internal destinations.
- The left menu has its own translatable title and item order. In the example it acts as concise project navigation.
- With the special left menu active the page tree starts hidden, but remains available through the Tree action.

<figure class="my-4">
<img src="images/029c0a41-1771-4478-8d7b-17a4cdf2a0b1.png" class="img-fluid rounded border" alt="Project tree and publishing state" />
<figcaption>The administrator sees the tree, page actions, draft state and publishing workflow.</figcaption>
</figure>

<a id="publishing"></a>

## 6. Editing, preview and publishing

Editing and publishing are deliberately separate. An editor saves a draft while readers continue to see the published version. A publisher previews the draft and approves it.

<figure class="my-4">
<img src="images/3b76bbcd-8f18-4830-8f41-54d912512bd1.png" class="img-fluid rounded border" alt="Editor view" />
<figcaption>Ivan can edit and preview a draft but has no Publish action.</figcaption>
</figure>

<figure class="my-4">
<img src="images/1a9a0b00-99d3-4339-a41b-c3cc997689b4.png" class="img-fluid rounded border" alt="Publisher on a published page" />
<figcaption>Petra sees that a draft exists and can open it for approval.</figcaption>
</figure>

<figure class="my-4">
<img src="images/0c743ad8-6b86-4b5a-a069-2690087c5dcb.png" class="img-fluid rounded border" alt="Draft preview and publish" />
<figcaption>The publisher reviews the draft before selecting Publish.</figcaption>
</figure>

<a id="restrictions"></a>

## 7. Restrictions and direct permissions

### 7.1. Restricting inherited rights

The searchable user picker lists only users who already have a Workspace permission, directly or through a group. Green with a check means inherited and retained; red without a check means explicitly denied; white means the user did not inherit that permission. A restriction cannot grant anything and applies to the selected page and its descendants.

<figure class="my-4">
<img src="images/34c9ad49-a8bd-4b35-88f7-9b7efc2a357d.jpg" class="img-fluid rounded border" alt="Inherited permission restrictions" />
<figcaption>Borna retains View and Add, while inherited Edit is denied on Work plan.</figcaption>
</figure>

<figure class="my-4">
<img src="images/56cf82bc-e3b5-4584-9959-9042a98c4154.png" class="img-fluid rounded border" alt="Result of a page restriction" />
<figcaption>Borna can still read Work plan but no longer sees editing actions.</figcaption>
</figure>

### 7.2. Direct page permissions

A direct permission adds a user or group to one page. It may grant **Read**, **Editing** and **Publish**, without propagating to child pages. The group picker also offers **Public**, which grants read access without signing in, and **All signed-in users**.

In the page settings, find **Direct permissions for users and groups**, select the person or group, check the required rights and save. Clicking the picker opens its first window; search and scrolling load additional matches. A private Workspace then appears for someone who can read that page. Inaccessible parents and other pages remain hidden; a link to them does not grant access. The same applies to a published survey placed in a Workspace. Survey drafts do not become public through this grant.

<figure class="my-4">
<img src="images/4deaf755-91eb-4954-ae61-df9b8a0de56b.jpg" class="img-fluid rounded border" alt="Direct user permission" />
<figcaption>Sara receives Read and Editing on one page without access to the rest of the Workspace.</figcaption>
</figure>

<figure class="my-4">
<img src="images/33608a0f-528f-4ad4-a87f-7095b802aaf7.png" class="img-fluid rounded border" alt="Workspace exposed by direct permission" />
<figcaption>Project Aurora appears in Sara's Workspace list.</figcaption>
</figure>

<figure class="my-4">
<img src="images/4d65b802-db87-4779-aef5-43dd2600819e.png" class="img-fluid rounded border" alt="Directly permitted page" />
<figcaption>Sara opens and edits the permitted Partner summary page.</figcaption>
</figure>

<figure class="my-4">
<img src="images/15993220-eda8-49e2-bfc8-dc73a3d6d2e5.png" class="img-fluid rounded border" alt="Direct permission boundary" />
<figcaption>Another page in the same Workspace remains forbidden.</figcaption>
</figure>

<a id="scenarios"></a>

## 8. Role comparison

<div class="table-responsive">

| Example role | What the user sees and can do |
|----|----|
| Administrator | All Workspaces, global settings, maintenance and every action. |
| Workspace manager | Settings for one Workspace, permissions, tree, private theme, menus, export and backup. |
| Editor | View, add and edit drafts, without publishing. |
| Publisher | Draft review and publishing, without editing unless separately granted. |
| Reader | Only published pages allowed by the ACL. |
| Directly permitted user | The Workspace is listed, but only the directly permitted page is available. |
| Signed-in user without ACL | Public and All signed-in Workspaces, plus directly permitted pages in private Workspaces. |
| Guest | Public content, including published pages/surveys with a public exception in a private Workspace. |

</div>

<figure class="my-4">
<img src="images/6e09a233-75c7-4e63-b8dc-4d2502092270.png" class="img-fluid rounded border" alt="Workspace manager list" />
<figcaption>Marina sees the Workspaces she can access and a manage action for Project Aurora.</figcaption>
</figure>

<figure class="my-4">
<img src="images/c9a8b63c-cead-4503-9ea9-54e9494a48ba.png" class="img-fluid rounded border" alt="Manager settings" />
<figcaption>A manager maintains one Workspace without global administrator settings.</figcaption>
</figure>

<figure class="my-4">
<img src="images/c1bc5dfd-2bf4-4274-92fc-5ce7b4b426ee.png" class="img-fluid rounded border" alt="Signed-in user without explicit ACL" />
<figcaption>Nikola sees Public and All signed-in Workspaces, but not the Restricted project.</figcaption>
</figure>

<figure class="my-4">
<img src="images/e8f4e5ae-9821-40a7-8053-926c00010dde.png" class="img-fluid rounded border" alt="Signed-in home page" />
<figcaption>After login, Nikola lands on the internal knowledge-base home page.</figcaption>
</figure>

<figure class="my-4">
<img src="images/ddfd25cd-e7c0-4ea3-9e8d-3f9d287cfb97.png" class="img-fluid rounded border" alt="Guest Workspace list" />
<figcaption>Example of publicly accessible Workspaces in a guest’s list.</figcaption>
</figure>

<figure class="my-4">
<img src="images/fe41094f-4372-4e1f-98c3-f9c62e9ef5ed.png" class="img-fluid rounded border" alt="Public home page" />
<figcaption>The guest opens the configured public home page.</figcaption>
</figure>

<a id="embedded"></a>

## 9. Embedded content and access checks

Embedding another page, calendar or module never bypasses the source permissions. If the viewer cannot access the source, Simbioza renders a controlled placeholder instead of leaking the page or event data. Always test the final page as the intended reader, not only as an administrator.

<figure class="my-4">
<img src="images/a872ad42-71de-4609-a6d1-3fdf1da3935d.png" class="img-fluid rounded border" alt="Reader Workspace list" />
<figcaption>Luka can open the restricted project because his reader group grants View.</figcaption>
</figure>

<figure class="my-4">
<img src="images/dee15bfd-0881-4e59-8100-7e7bb3135aae.png" class="img-fluid rounded border" alt="Protected embedded sources" />
<figcaption>The same reader cannot see an embedded private page or manager-only calendar.</figcaption>
</figure>

<a id="themes"></a>

## 10. Workspace theme

A Workspace can inherit the system theme or use a private Workspace theme. A user with **Manage** permission opens **Workspace settings → Edit Workspace theme**. The Theme editor itself is documented separately.

<figure class="my-4">
<img src="images/ec43d5a4-2cd8-4256-90a1-36a0eacb4f66.png" class="img-fluid rounded border" alt="Workspace manager opens the Workspace theme" />
<figcaption>A Workspace manager sees the Edit Workspace theme link in that Workspace's settings. The Theme editor itself is covered in a separate guide.</figcaption>
</figure>

<figure class="my-4">
<img src="images/d5517f83-b52b-42d2-9fdc-78a641bc1899.png" class="img-fluid rounded border" alt="Paper and ink theme" />
<figcaption>A public Workspace without the standard header or hero title, using cream, navy and serif typography.</figcaption>
</figure>

<figure class="my-4">
<img src="images/3aceb3d7-8f52-4c99-9f42-b83355503878.png" class="img-fluid rounded border" alt="Orbital Aurora theme" />
<figcaption>A knowledge base without the standard header but with its title in a custom hero and a different ornament.</figcaption>
</figure>

<a id="export"></a>

## 11. Static HTML export

A manager can export the published pages they are allowed to read as a standalone ZIP archive. It is useful for delivery, long-term archive and offline browsing.

<figure class="my-4">
<img src="images/ea3a83a9-7f7b-4da5-b3dc-a8fafade4229.png" class="img-fluid rounded border" alt="HTML export options" />
<figcaption>The export includes accessible published pages, navigation and required public assets.</figcaption>
</figure>

<figure class="my-4">
<img src="images/72b251f1-3f3c-45b8-b4f9-5361467390cc.png" class="img-fluid rounded border" alt="Offline HTML result" />
<figcaption>The exported site opens without an authenticated session or the live Simbioza API.</figcaption>
</figure>

- Drafts and inaccessible pages are excluded.
- Surveys are excluded: an HTML package cannot recreate respondent sign-in, response collection or live results. Use backup to transfer surveys.
- When a document uses approved scripts, local files and inline code are exported, while approved remote CSS/JavaScript remains linked and needs a network connection. Unapproved scripts are excluded. Simbioza’s own interactive tables have a local runtime.
- Features requiring login, permissions or an API do not become an offline application.
- Extract the archive and verify `index.html`, navigation, images and links.

<a id="backup"></a>

## 12. Workspace backup and restore

A Workspace backup is intended for content transfer and recovery. It includes supported content, history, attachments, tree, ACL, private theme, private menus and indexing data. For surveys placed in that Workspace, their provider includes questions, translations, revisions, accepted responses, permissions and referenced media. External provider connections and global Survey settings belong to a full-site backup; see [Survey backup](ankete-backup-detalji_en.md).

**For complete meeting recovery, use a Calendar component or full-site backup.** A Workspace backup transfers linked calendars and events, but not separate participant, response, reservation and approver records or CalDAV credentials. Restoring a copy reuses an existing calendar with the same UUID; it does not create an independent copy of a shared schedule.

<figure class="my-4">
<img src="images/4ed16762-77ed-4168-b2cc-3501623c2c99.png" class="img-fluid rounded border" alt="Workspace backup" />
<figcaption>Encrypted backup export and the restore upload form.</figcaption>
</figure>

1.  Open **Manage Workspace backup**.
2.  For export, set a strong passphrase and store it separately; it is not retained in the backup.
3.  For restore, upload the ZIP, enter the passphrase and run preflight first.
4.  Review the format version, modules, page counts, ACL, theme and slug conflicts.
5.  Restore into an existing Workspace or create a separate copy with a new slug.

<figure class="my-4">
<img src="images/3f14c0f1-7800-4303-a836-f5212d93df81.png" class="img-fluid rounded border" alt="Restore preflight" />
<figcaption>The preflight validates the backup and accepts a new target slug.</figcaption>
</figure>

<figure class="my-4">
<img src="images/9238ffa0-235e-4850-8add-01b9720706a4.png" class="img-fluid rounded border" alt="Restored copy" />
<figcaption>Project Aurora was restored as a separate Workspace with a new slug.</figcaption>
</figure>

<div class="alert alert-warning">

Restoring into an existing Workspace may replace current data. Back up the target first. Prefer a new slug for a trial restore, compare content and permissions, then plan any production replacement.

</div>

<a id="mobile"></a>

## 13. Mobile view

On a narrow screen, the tree and table of contents become side buttons. Each button opens a touch-friendly card over the page and closing it returns to the document.

<figure class="my-4">
<img src="images/402ce251-0561-4375-adf4-1db9c493452d.png" class="img-fluid rounded border" alt="Mobile Workspace page" />
<figcaption>A complete mobile viewport with side buttons for the tree and contents.</figcaption>
</figure>

<figure class="my-4">
<img src="images/c8bc5637-39d0-42df-886b-73ed0d2e1c86.png" class="img-fluid rounded border" alt="Mobile tree card" />
<figcaption>The Workspace tree opens as a dedicated card.</figcaption>
</figure>

<figure class="my-4">
<img src="images/95249319-d339-4a80-af17-0c4839b5afa6.png" class="img-fluid rounded border" alt="Mobile contents card" />
<figcaption>The document contents open as a dedicated card.</figcaption>
</figure>

<figure class="my-4">
<img src="images/3e7340a0-47c3-4050-a707-ff9ea723c4e2.gif" class="img-fluid rounded border" alt="Animated tree and contents controls" />
<figcaption>The animation includes a visible pointer and demonstrates both mobile cards.</figcaption>
</figure>

<a id="maintenance"></a>

## 14. Archive, deletion, search and maintenance

### 14.1. Archive and soft deletion

An **archived Workspace** remains readable while changes are disabled. Use it for completed projects whose URLs and history must remain available. Turning archive off restores changes according to the existing ACL.

**Soft deletion** removes a Workspace from normal lists but preserves it for administrator restore. Before deleting, check application home pages, embedded pages, special menus and external links that may target it.

### 14.2. Search index

The index contains content the search module can offer while enforcing each viewer's permissions. Rebuilding does not change documents; it rereads published pages and refreshes search records. Run it after a large import/restore, mass slug changes or stale results. Review status and record counts before starting a rebuild.

### 14.3. Storage overview

The top of **Maintenance** is a pre-cleanup report. Historical versions and Deleted pages are record counts, Database estimate measures useful row data, and File system reports file storage. Separate history/deleted totals and the per-Workspace table identify which cleanup could actually reclaim space. The database estimate is not the physical SQLite/MySQL/PostgreSQL file size; physical compaction may require database-specific maintenance.

### 14.4. Image optimisation

**Optimize existing images** creates smaller web-ready copies for faster delivery. Originals remain saved and openable, so this action does not discard source quality. It is useful after a large import or many unoptimised photographs.

### 14.5. Cleanup history and deleted items

<figure class="my-4">
<img src="images/b1b44d8b-2948-47c6-b67b-a3bb78cfa2c3.png" class="img-fluid rounded border" alt="Maintenance actions" />
<figcaption>Image optimisation, cleanup scope, history policy, deleted-item age, irreversible confirmation and permanent Workspace deletion are visible.</figcaption>
</figure>

Cleanup is irreversible and requires a complete site backup first:

- **Scope** limits the operation to the entire site or one selected Workspace. Prefer the smallest required scope.
- **Page history** may remain untouched, remove all history except current/published versions, keep the latest 3/5/10 versions, or remove versions older than 10/30/90 days.
- **Permanently remove deleted items** may remain off or remove items older than 10, 30 or 90 days.
- An attachment is retained while any retained version references it.
- The operation cannot run until the administrator explicitly accepts that recovery is possible only from backup.

### 14.6. Permanently delete Workspaces

Only previously soft-deleted Workspaces appear here. Entering the exact slug protects against an accidental click. Confirmation removes pages, history, attachments, ACL, private theme, special menus and related module data. Recovery is possible only from a verified backup, so complete a trial restore before permanent deletion.

<a id="checklist"></a>

## 15. Final verification

1.  Open the Workspace as an administrator and as its manager.
2.  Test an editor without Publish and a publisher without Edit.
3.  Test a reader, a signed-in user without ACL and a guest.
4.  Verify a restriction on its page and descendants.
5.  Verify a direct page permission and confirm the rest of the Workspace remains unavailable.
6.  Open an embedded page and calendar as a user who lacks source rights.
7.  Check inherited and overridden tree/contents display on desktop and mobile.
8.  Export static HTML and open it outside the signed-in session.
9.  Create a backup, restore a trial copy under a new slug and compare pages and ACL.

<div class="alert alert-success">

A well-configured Workspace reveals only the required content and actions to each user. Never validate access solely with an administrator account.

</div>
