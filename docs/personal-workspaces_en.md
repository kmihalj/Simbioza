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

1. Apply the application migrations:

   ```bash
   php vendor/bin/hph orm-migrate:up
   ```

2. Open **Settings → Workspaces → Personal Workspaces**.
3. Keep **Automatically create a personal Workspace at first sign-in** enabled if
   new users should receive one automatically.
4. Use **Create personal Workspaces for existing users** once when introducing the
   feature to an existing installation.

The table on the same screen provides a per-user exception and a manual
**Create now** action. Disabling automatic creation does not delete an existing
Workspace. A soft-deleted personal Workspace stays mapped to its user and can be
restored through the regular deleted-Workspace administration; the next login
does not silently create a replacement.

## Access rules

- The user for whom the personal Workspace is provisioned receives one direct
  ACL row with all permissions: **View**, **Add**, **Edit**, **Publish**,
  **Delete**, and **Manage**.
- The Workspace is created with `restricted` visibility.
- Other users and groups receive no implicit ACL rows. The mapped user or an
  administrator can grant them the usual Workspace permissions later.
- A guest cannot open or discover the Workspace through the normal Workspace list.

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

After creation, **My profile → Following and notifications** contains a direct
link to **My personal Workspace**. An authenticated API key with `workspaces:read`
can read only its own mapping through:

```http
GET /api/v1/me/personal-workspace
```

The endpoint is read-only and never creates a space.

## Backup and restore

Workspace backup includes the personal-space mapping when the selected
Workspace is personal. The **Users** component preserves per-user creation
policies and mappings, while **Settings** preserves the global automatic-
creation rule. A full-site backup contains all three parts.

During a copy import, an existing personal-Workspace mapping wins because one user
can have only one personal Workspace. The imported copy remains an ordinary
restricted Workspace associated with the same mapped user. Search indexes remain
derived data and are rebuilt automatically after restore.
