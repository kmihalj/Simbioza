# Personal Workspaces

Simbioza can give every active user a personal Workspace. A personal Workspace is not a
separate document system: it is an ordinary restricted Workspace with a stable
user-to-Workspace mapping. Consequently, its pages, history, attachments,
comments, tasks, calendars, theme, menus, search, and ACL rules work exactly as
they do in any other Workspace.

The initial title is **Workspace of: Display Name** (or the login identifier when a
display name is unavailable). The generated title and description follow the
current interface language, so Croatian and English views never expose a mixed
label. The mapped user may later rename it like any Workspace; a custom title and
description are preserved, and the stable database mapping does not depend on
the name or slug.

## Administrator setup

After installing the modules and applying migrations, open **Settings → Personal
Workspaces** (`/settings/personal-workspaces`). The screen has two global options:

- **Automatically create a personal Workspace on first sign-in** is enabled by
  default. A missing personal workspace is created at an active user's next
  successful sign-in; enabling the option does not run bulk creation for all
  existing accounts.
- **Allow users to create a personal Workspace** is disabled by default and is
  available only while automatic creation is off. Users without a personal
  workspace then see **Create my personal Workspace** under **My profile →
  Following and notifications**.

Select **Save**. With both options off, these paths do not create new personal
workspaces. Existing workspaces are retained.

**Created personal Workspaces** is a read-only list of existing workspaces and
links. There is no bulk creation, per-user exception or **Create now** action.
The legacy `simbioza_user_personal_workspace_policies` table remains in the
migration schema for compatibility; the current service and form do not apply
its policies. A soft-deleted personal workspace remains mapped to its user and
is restored under **Workspace settings → Deleted workspaces**; another sign-in
does not create a replacement.

## Access rules

- The user for whom the personal Workspace is provisioned receives one direct
  ACL row with all permissions: **View**, **Add**, **Edit**, **Publish**,
  **Delete**, and **Manage**.
- The Workspace is created with `restricted` visibility.
- Other users and groups receive no implicit ACL rows. The mapped user or an
  administrator can grant them the usual Workspace permissions later.
- Initially a guest cannot open or discover the Workspace through the normal
  Workspace list; any later public exception follows the ordinary workspace
  or page permission controls.

Auth publishes a neutral successful-sign-in event. Simbioza User listens for
that event, so Auth remains independent and continues to work when Workspace or
Simbioza User is absent. Provisioning is idempotent: repeated or simultaneous
sign-ins keep a single mapping. The next successful sign-in also verifies an
existing personal Workspace ACL and restores the mapped user's full permission
row when the Workspace predates this rule.

General Workspaces still have no separate owner concept: every user with
**Manage** may maintain one. A personal Workspace only adds a stable user
mapping and automatically grants that mapped user the complete ACL.

## User profile and API

If self-creation is enabled, a user without a workspace may create it in their
profile. After creation, **My profile → Following and notifications** contains a direct
link to **My personal Workspace**. An authenticated API key with `workspace:read`
can read only its own mapping through:

```http
GET /api/v1/me/personal-workspace
```

The endpoint is read-only and never creates a space.

## Backup and restore

Workspace backup includes the personal-space mapping when the selected
Workspace is personal. The **Users** component preserves supported user data and mappings, while
**Settings** preserves the global creation settings. Legacy stored per-user
policies are not an active form or service capability. A full-site backup contains all three parts.

During a copy import, an existing personal-Workspace mapping wins because one user
can have only one personal Workspace. The imported copy remains an ordinary
restricted Workspace associated with the same mapped user. Search indexes remain
derived data and are rebuilt automatically after restore.
