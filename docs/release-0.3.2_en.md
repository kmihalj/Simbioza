# Simbioza 0.3.2 — fresh secured-FPM setup

[Croatian version](release-0.3.2_hr.md)

> Historical release note. Current candidate checks are recorded in [the local preflight](release-preflight-0.3.3_en.md); 0.3.3 is not published yet. This 0.3.2 tag remains unchanged.

This application patch fixes the FPM generator used by fresh macOS and Linux
secured installations. Public modules remain **0.3.0**, the private DEMO module
remains **0.3.1**, and Framework remains **v0.0.25**. Existing tags are not moved.

## Changes

- Empty `HPH_CONFIG_OVERRIDE_PATHS` values are omitted from the FPM pool instead
  of producing an invalid empty `env` entry. `clear_env = yes` remains enabled;
  explicitly configured layers and their order remain unchanged.
- Regression tests cover absent, empty and whitespace-only values, and one or
  several configured layers. Real FPM parser checks cover both launchd-owned
  and explicitly declared identities on PHP 8.2–8.5 in isolated CI containers.
- All current installation instructions, bundled EN/HR guide archives and
  Docker examples use **0.3.2**. Fresh installation needs no manual generator patch.
- The complete private DEMO guide installs application 0.3.2 first and attaches
  demo module 0.3.1 afterwards, using the named secured pool and SQLite reset.

## Installation and existing attempts

Use the [complete installation index](installation_en.md) or the private
[complete DEMO guide](https://github.com/kmihalj/simbioza-module-demo/blob/main/docs/demo_installation_en.md).
Missing dedicated users are created; existing users with canonical names are reused.

If an initial 0.3.1 setup stopped with `empty value` before the web wizard was
completed, a full reinstallation is not required: replace only
`scripts/configure_fpm_setup.php` with the version from this tag, then repeat
the same named FPM setup command. Do not reset the database or recreate users.
Never rerun initial setup over an installed DEMO reset baseline.

Docker keeps immutable application code in the image and persistent
configuration/data and PostgreSQL data in their separate existing volumes.
Use `ghcr.io/kmihalj/simbioza:0.3.2`; do not delete volumes to update the image.

[Release and downloads](https://github.com/kmihalj/Simbioza/releases/tag/0.3.2)
