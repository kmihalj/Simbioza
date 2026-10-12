# Simbioza 0.2.6

> Historical release note — not current installation or upgrade instructions. For the clean 0.3.0 baseline, use [installation](installation_en.md) and [current configuration](configuration_en.md). Older-version upgrade instructions below are retained only as history.

The new installation guide replaces the former Installation page in User guides. Its Apache mod_php and PHP-FPM subpages cover the ordinary and isolated FPM paths, prerequisites, web-server configuration, first administrator, package preparation, and subsequent maintenance. The bundled guides are available to new installations and are refreshed on existing sites during the application update without replacing unrelated pages or their permissions.

The first-installation wizard now selects all optional modules by default. Deselect any modules you do not need. For Apache mod_php or ordinary FPM without the dedicated Setup helper, `php scripts/installation_packages.php prepare` prepares all optional packages before opening the wizard; `--modules=theme,calendar` prepares a smaller set, and `--modules=` prepares none. The CLI choice and wizard selection must match. Dedicated isolated FPM can prepare selected packages through Setup.

There are no database migrations. Make a restorable backup before updating. Existing installations do not have modules added automatically: the new default applies only to a fresh installation.
