# Simbioza 0.2.3

> Historical release note — not current installation or upgrade instructions. For the clean 0.3.0 baseline, use [installation](installation_en.md) and [current configuration](configuration_en.md). Older-version upgrade instructions below are retained only as history.

The isolated PHP-FPM setup now accepts a unique `--instance` name for each
Simbioza site on one host. Separate system users, groups, deployment helpers,
FPM configurations, and services prevent the second installation from
overwriting the first. Reusing an instance name for another application root
is rejected before system changes begin. Existing installations that omit
`--instance` retain their previous names and behavior.

The installation guides now document the required inherited maintainer ACL
for macOS Apache mod_php installations. Without it, the graphical installer
can create private files that the later CLI updater cannot read. The FPM
guides also set a 900-second web-server gateway wait for long-running
graphical package preparation; an upstream timeout should be aligned if one
is present. This changes neither the application's package permissions nor
the request limits of unrelated sites.

No database migration is included. Before updating, make a restorable backup.
For a new site, follow the complete [installation guide](installation_en.md),
including the permissions and web-server checks for its selected PHP mode.
