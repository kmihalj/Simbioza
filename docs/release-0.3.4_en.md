# Simbioza 0.3.4 — secured FPM runtime permissions

[Croatian version](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/release-0.3.4_hr.md)

> Historical release note — use the [current installation index](installation_en.md) for a new installation. The original 0.3.4 tag and images remain unchanged.

This patch fixes the secured macOS FPM generator and updates fresh installation
instructions. Framework stays **v0.0.25**, API **0.3.1**, and other public modules
**0.3.0**. The private DEMO companion is **0.3.6**. Existing tags are not moved.

## Changes

- Generated launchd services explicitly use integer `Umask=7` (octal `0007`),
  matching the existing Linux runtime policy. New ordinary logs keep private
  runtime-group write access instead of becoming FPM-only `0644` files.
- The macOS deployment helper sets its mask after sudo changes identity.
  Deployment helpers on both platforms use the application's private `data/tmp`,
  matching FPM and keeping shared temporary locks in the same runtime directory.
- Tests parse the real generated plist, check the post-sudo wrapper and argument
  boundaries, and retain real FPM parser checks. No framework or Auth code changes.
- Installation commands, Docker examples and bundled EN/HR guides use **0.3.4**.
  macOS installation verifies the service mask before opening the wizard.
- DEMO 0.3.6 checks log/database access before any restore/pause, preserves private
  sessions/control, and tests real application bootstrap as separate deploy/FPM
  users. Its complete guide uses the correct mask for installation CLI commands.

## Installation and existing sites

Use the [complete installation index](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/installation_en.md),
[secured FPM guide](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/installation_fpm_secured_en.md)
or [Docker guide](https://github.com/kmihalj/Simbioza/blob/0.3.4/docs/docker_en.md).
The [private DEMO guide](https://github.com/kmihalj/simbioza-module-demo/blob/0.3.6/docs/demo_installation_en.md)
starts from an empty directory and requires repository access.

An application update does not rewrite an existing host's launchd/helper files
or repair old logs. Those need a scoped, backed-up host correction. Do not rerun
generic finalization over a DEMO reset snapshot, delete a lock, grant FPM code
write access or run the application updater as root to bypass the failure.
There are no new database migrations; single-space Confluence import is unchanged.

Linux AMD64/ARM64 images retain the existing security gates and persistent-volume
layout. No upstream libxml2 patch or vulnerability exception is introduced.
Publishing this release does not update demo, HFC or apps-test automatically.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.4)
