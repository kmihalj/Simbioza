# Simbioza 0.1.99

This release adds optional, installation-private extension points without adding
any private package to Simbioza's public module catalog. An administrator may
keep a package in a local Composer path repository, list it in
`config/modules.local.php`, and register its fail-closed request middleware in
`config/middleware.local.php`. The application reads those files only when they
exist. `config/installation.php` may set a distinct `session_name` for multiple
installations on the same domain; the default remains `HEARTPHRAME_SESSION`.

The updater preserves `composer.local.json` and the local PHP configuration,
merges only new package requirements from existing absolute Composer path
repositories, and refuses to let a local requirement replace a release
dependency. Private packages remain outside GUI module management. This is a
generic mechanism; the private Simbioza demo module and its content are not
shipped by this public release.

There are no database migrations. Installations without local overrides behave
as before and can use the ordinary GUI or CLI update. An installation that
already uses a private path package must first deploy this release's `update.php`
as a file-only updater bootstrap and verify it, then run the complete update:
the updater from 0.1.98 does not yet know how to retain that package. Back up
the installation and verify package presence, middleware enforcement, the
session cookie, and normal HTTP responses after updating. Do not publish
private credentials or package paths in the public module catalog.
