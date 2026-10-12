# Container readiness — 0.3.0 candidate

[Croatian version](container-readiness_hr.md)

Updated on 12 October 2026. A4 now implements and tests an immutable Docker
candidate in Colima. The classical HFC installation remains supported and was
not converted. Use the [Docker guide](docker_en.md) for current commands; the
[pre-release audit](pre-release-audit-0.3.0_en.md) records acceptance evidence.

## Current storage and configuration

- Application/PHP/vendor code and factory defaults are in the read-only image.
  Do not place a persistent volume over application code.
- One runtime volume contains private config, data and optional SAML state.
  Its data subdirectory is a direct volume-subpath mount, not a symlink.
- PostgreSQL has a separate volume and a non-superuser application role.
- Factory configuration uses `HPH_CONFIG_PATH=/opt/simbioza/config`;
  private writes use the final `HPH_CONFIG_OVERRIDE_PATHS=/runtime/config` layer.
  Every module retains its own subdirectory in that layer.
- Content, attachments, owned menus/themes, caches, logs, jobs, backups,
  translations and sessions are runtime state, never code.
- Installed translations use `data/languages/catalogs/`; packaged `lang/`
  stays read-only. Script approvals remain destination-local in backup/import.

## Operations verified by A4

The short root init job prepares only missing private state. HTTP/CLI/workers
run as UID/GID 33 with read-only root and restricted privileges. Image migrations
are explicit and locked; serving rejects a mismatched image ID. Failed migration
does not mark success. Images missing previously applied migrations are rejected.

Only code-mutating Setup/Composer/updater actions are prohibited in image mode.
The configuration/content/language GUI and module activation remain available.
The baked marker cannot be disabled from private config. Ordinary installations
retain their existing package/update workflow.

The Colima lab covers clean PostgreSQL installation, preserved volume state,
image replacement, failed/retried migration, unsafe downgrade rejection,
local login and session persistence, Setup activation, language installation,
workspace and application-language backup/restore, and an empty SMTP worker.
Prior A3 read-only-code FPM/secure-FPM probes remain separate evidence.

## Optional SimpleSAMLphp

Auth requires its library in the same PHP process; an external URL alone is
not a library integration. A4 selects an optional read-only compatible code
mount with separate runtime config/certificates/metadata and sessions/cache.
Only the library's public directory is exposed at `/saml`.

SimpleSAMLphp 2.4.4 code loading and fictional SP metadata were verified.
A real container SP/IdP registration and login remain an operator deployment
check. HFC SSO secrets and settings were not reused or changed. The default image
does not contain SimpleSAMLphp or private certificates. See the Docker guide;
a separate SAML service or image variant is not implicitly supported.

## Release boundaries

A4 is local candidate validation, not publication or production approval.
A5 local verification is complete. Released image artifacts, dependency/vulnerability
verification and real environment-specific HTTPS/proxy/SSO checks belong to
release/deployment work. No demo/apps-test update or Framework change was made.
