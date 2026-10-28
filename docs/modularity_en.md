# Choosing Simbioza features

[Hrvatska verzija](modularity_hr.md)

Simbioza brings pages, a Wiki, calendars, surveys and collaboration together in one complete application. If you need only some of these features, the modular HeartPhrame framework also allows a focused installation. The following presets describe that additional option.

**Simbioza** is Croatian for **symbiosis**: different organisms living together.
The application is named after that idea because its modules have distinct
roles but can work together. A page explains a calendar event, a Wiki connects
team knowledge, and a survey collects feedback. The complete Simbioza combines
these tools, tasks, comments, notifications and integrations in one environment.

## Choose a purpose

| What you need | Installation preset | Feature modules |
| --- | --- | --- |
| All available features together | Full Simbioza | All optional modules |
| A simple website with HTML pages | HTML website | HTML Editor |
| Events, meetings and CalDAV | Calendar | Calendar |
| A searchable Wiki organized into Workspaces | Wiki | Workspace, Workspace Search and Simbioza User |
| Surveys with questions, branching and results | Surveys | Survey |

Every installation includes **Auth, ORM, Menu, Theme, Backup and Audit**. They
provide authentication and permissions, database access, menus, appearance,
backups and activity logs. “Standalone” means without other business modules,
not without the shared foundation and required dependencies.

The installer adds required dependencies automatically. For example, Survey
uses HTML Editor for introductions and explanations; the Wiki also needs the
Editor, and Simbioza User adds notifications. API, accessibility and other
optional integrations do not require unrelated business features.

## Install the selected tool

1. Prepare a new installation using the appropriate [installation guide](installation_en.md).
2. In the wizard's application settings step, select an **Installation preset**
   and click **Apply preset**.
3. Review the selected modules. Required dependencies remain included; select
   additional features only if you need them.
4. Select the installation languages and finish setup. Content and translation
   languages are selected from installed, active languages.
5. Sign in as administrator, create your content and add menu links so visitors
   can find it.

The result is one application for your chosen purpose. Sample pages, calendars
and surveys are not business data automatically shipped to every new site;
you create your content after installation.

## Share a standalone HTML website

An HTML website does not need Wiki to work with several authors or private
pages. As administrator, open **Settings → HTML editor → Page permissions**
and add users or groups. **Adding** permits creating and editing their own
pages. **Editing** permits managing all standalone pages, including private
ones; it does not grant access to administrator settings.

On an individual page, open **Page settings and permissions**. Choose public
reading, all signed-in users, or only selected users and groups. This choice
also protects attachments, direct links and API reads. In a Wiki, continue to
use the existing Workspace and page permissions instead.

## Extend an existing installation

For example, add a calendar or surveys to an HTML website, or add surveys,
tasks and comments to a Wiki. **Settings → Setup and modules** shows what is
installed, what is enabled and which dependencies are required.

On a correctly configured dedicated FPM installation, an administrator can
click **Install** beside another module. For mod_php and ordinary FPM, a
maintainer runs the corresponding CLI command from the application root using
the maintenance account:

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

These are separate examples: choose the module you need. The system adds its
required dependencies. Use `task` and `comment` for tasks and comments; a Wiki
can later add `confluence-import` to import existing content. After extending
the installation, configure the new module's permissions, menus and settings.
Installing a module does not give every user permission to edit content.

Make a backup before changing the installation's composition. Disabling an
optional module is not the same as uninstalling it: disabling retains data,
while uninstalling removes its tables and package after preparing a recovery
backup. The six required modules cannot be disabled or uninstalled.

### Add Workspaces to an existing HTML website

Adding Workspaces does not move existing HTML pages. They keep their addresses,
content, translations, attachments and standalone permissions. Standalone HTML
pages and pages in Workspaces can be used together, including through the API.

Only an administrator can move an existing page into a Workspace:

1. Open **Settings → HTML editor → Pages outside workspaces**.
2. Find the page and choose **Move into workspace**.
3. Open the destination list or search by name. More workspaces load as you scroll.
4. Review the destination's visibility and confirm its permissions, then move the page.

The result is one page at the top level of the destination tree. All translations,
version history and attachment versions move with it. Files are transferred into
the Workspace's storage; database HTML remains in the same version records with
new Workspace ownership. Links in stored pages are repaired, including language
choices and anchors, and existing page and attachment addresses remain usable
with the destination's access checks. Published versions remain published and
working drafts remain drafts.

**Destination permissions replace standalone access.** The old readers are not
automatically added as exceptions. Moving a public page into a private Workspace
can restrict it; moving a private page into a public Workspace can make its
published content public. Review the destination before confirming. Take a
backup first if you need a return point: this screen does not offer an inverse move.

## Updates and backups

New installations receive independent sign-in cookies, including when several
sites share one domain. A restore preserves the target site's local session
identity instead of copying another site's sign-in cookie identity.

Dedicated FPM with a correctly configured restricted helper supports updates
through **Settings → Setup and modules → Run update**. Merely using FPM is not
enough: the environment check must confirm that GUI updates are available.

For other classical installations, the maintainer runs these commands from
the application root:

```bash
php update.php --check --lang=en
php update.php --lang=en
```

Updates preserve the selected modules and existing data. A new optional module
becomes available to install; it is not enabled automatically. A full backup
includes the data and portable settings of enabled modules. Also retain a
separate copy of host configuration and local secrets, which are not transferred
by business-data backups.

## Docker

Docker uses the same feature selection. If an image contains all modules,
select and enable only the necessary ones during installation. For a smaller
custom image, build with selected modules and their required dependencies.
A module absent from the image requires a replacement image, not a code change
inside a running container. Updates replace the image while preserving the
database and runtime data. Follow the [Docker guide](docker_en.md).

## Guides and integration with other systems

With a Wiki, these pages live in the **User guides** Workspace. Without a Wiki,
the same manual is available through **Guides**, with a chapter list and
navigation. There is no need to install a Wiki just to read the manual.

API can be added to any purpose. An API key determines permitted calls but
never bypasses its owner's permissions. Available routes and scopes follow
installed, enabled modules. This supports integration with other systems and
custom HeartPhrame applications without rebuilding authentication, navigation,
database access and other common foundations.
