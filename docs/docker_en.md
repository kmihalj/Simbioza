# Docker deployment — 0.3.3 candidate

[Croatian version](docker_hr.md)

This candidate is unpublished. Registry/download links below become usable only
after coordinated release approval; local preflight uses a locally built image.

The classical installer remains supported. Docker is an additional deployment
method, not a conversion of an existing site. The versioned image is
`ghcr.io/kmihalj/simbioza:0.3.3` for Linux AMD64 and ARM64, including Apple Silicon
through Colima. Get the Compose bundle and checksums from the
[0.3.3 release](https://github.com/kmihalj/Simbioza/releases/tag/0.3.3) after publication.
Importer, Calendar and Simbioza User use 0.3.1; other modules remain at 0.3.0.
The Framework and deployment layout are unchanged.

## What persists

| Location | Purpose |
| --- | --- |
| Application image, `/opt/simbioza` | Application, PHP libraries, vendor packages, factory defaults and public starter guides; read-only, not a persistent code volume |
| `runtime` volume, `/runtime/config` | Private application/module configuration, including database and Auth secrets |
| Same volume, `/runtime/data` | Pages/files, attachments, themes, logs, caches, backups, imports, translations and PHP sessions |
| Same volume, `/runtime/saml` | Optional private SimpleSAMLphp configuration, metadata and certificates |
| `database` volume | PostgreSQL data; independent of application image updates |

There are two long-running containers (application and PostgreSQL) and a short
initialization job. The third *component* is the runtime volume, not another
running container. Application code belongs in an image, not a replaceable data
volume. Do not run `docker compose down -v` on a real installation: it removes
the named runtime and database volumes.

Configuration uses the existing Framework layering contract:

```text
HPH_CONFIG_PATH=/opt/simbioza/config
HPH_CONFIG_OVERRIDE_PATHS=/runtime/config
```

The first layer contains read-only factory PHP defaults. The final layer is the
private write target for the GUI and CLI. Module documents remain grouped there,
for example `auth/auth.json`, `email/email.json`, `menu/` and `theme/`.
Do not copy the complete factory directory into the private layer: this would
freeze old services/routes and override defaults from the next image.

The `data` subdirectory of the same runtime volume is mounted **directly** at
`/opt/simbioza/data`, using Compose `volume.subpath`; it is not a symlink. Keep
Docker Engine 27+ and a Compose version supporting `volume.subpath`. The tested
environment used Engine 29.5.2 and Compose 5.6.0. Private files are outside
Apache's `public/` document root.

## Build without exporting a live installation

From an installed development/release checkout with its Composer autoloader:

```sh
mkdir -p build/docker
php scripts/build_container_context.php --output=build/docker/context --local
docker build -t simbioza:0.3.3-candidate \
  -f build/docker/context/docker/Dockerfile build/docker/context
```

The destination must be new; never reuse or merge an old context. `--local`
exports Git-visible current package code from sibling repositories and marks it
as an **unpublished local candidate**. Candidate version labels only satisfy
Composer dependency constraints; they do not certify a published release.
Remove `--local` only after the referenced release packages exist. Record the
built image digest and its generated `composer.lock` for deployment; a rebuild
is not guaranteed to resolve identical dependencies.
The builder seals the read-only code marker with the resolved lock hash, so
changed dependency bytes require an explicit migration generation even when
the application source is unchanged.

The exporter allowlists code/defaults and excludes HFC private configuration,
active Auth documents, databases, content, Composer credentials and live vendor
symlinks. Never run `docker build .` in the live HFC directory.

All optional modules are included by default. A new smaller image can use
`--modules=theme,calendar`, or `--modules=` for the minimum. Required dependencies
and the Backup library used to import initial guides are included automatically.
The installer must select only packages actually baked into that image.
For an existing database, retain packages owning applied migrations; disable an
unneeded module instead of removing its schema/package from the image.

## First installation

1. Copy `docker/.env.example` to `docker/.env`. Verify the released image digest
   (or set `SIMBIOZA_IMAGE` to your own verified build). Create two private password
   files at the declared paths: one for Simbioza and one for PostgreSQL's admin.
   Each contains a distinct strong password on one line. Keep both out of Git.
   Set the secret directory to `0700` and its files to `0444`: the directory
   prevents other host users from reading them, while read-only Compose mounts
   allow the PostgreSQL container's different UID to read its assigned secrets.
   File-backed Compose secrets do not remap file ownership. Do not place these
   files in a public or shared host directory; the app never receives the admin secret.
2. The default listener is `127.0.0.1:1511`. Put a trusted HTTPS reverse proxy
   in front of it; do not publish the database port. Keep cookie security enabled.
   For an isolated local HTTP lab only, set `SIMBIOZA_COOKIE_SECURE=0`.
   `SIMBIOZA_PORT` in `.env` changes the host TCP port if another service uses
   1511. The container's internal port remains 8080. Registration of a port for
   another service does not itself occupy that port, but firewall rules may
   need adjustment. Keep the listener on loopback unless remote access is
   deliberately required.
3. From `docker/`, use one consistent Compose project name:

```sh
docker compose -p simbioza run --rm --no-deps init
docker compose -p simbioza up -d database app
docker compose -p simbioza run --rm --no-deps app install:prepare \
  --base-url=https://simbioza.example.org
```

4. Open the generated one-time installer URL privately. Select PostgreSQL,
   host `database`, port `5432`, database/user `simbioza`, and the application's
   password (not the admin password). Complete the ordinary installation wizard.
5. Record the initialized image generation before the first controlled restart:

```sh
docker compose -p simbioza stop app
docker compose -p simbioza run --rm --no-deps app migrate
docker compose -p simbioza up -d app
```

The application and CLI run as UID/GID `33:33`, with dropped capabilities,
`no-new-privileges`, read-only root filesystem and temporary `/tmp` storage.
The short root init job has only directory/file ownership capabilities and no
network. It creates missing private configuration without overwriting existing
files or recursively changing the ownership of existing data. A mismatched
volume owner is an explicit operator error, not silently repaired.

PostgreSQL first-volume initialization creates a separate `simbioza` database
owner with `NOSUPERUSER`, `NOCREATEDB`, `NOCREATEROLE` and `NOREPLICATION`.
Changing the secret file later does **not** rotate an existing database password;
rotate it deliberately in PostgreSQL and private application configuration.

## Release image verification

CI checks PostgreSQL installation, backup/restore, language storage, runtime
persistence and immutable permissions on both architectures. Release evidence
includes dependency locks, SBOMs, vulnerability reports and image digests.
Fixable high/critical findings block publication; the full reports also retain
findings without an available upstream fix for operator review.

## Updates and migrations

The immutable marker is part of the code image. Private settings cannot disable
it. In-place `update.php`, Composer/package installation/removal and corresponding
privileged worker actions fail before changing code. Setup explains the image
workflow; configuration/content/languages and module enable/disable remain usable.

For each update:

1. Build/pull the intended versioned image before maintenance; verify its digest.
2. Stop the web application **and every separately running worker**. Take a
   consistent PostgreSQL backup and a runtime-volume snapshot while writers are
   stopped. Application page/workspace archives do not replace a whole deployment
   recovery copy, especially private SAML certificates and database credentials.
3. Change `SIMBIOZA_IMAGE` in `.env`, retaining both named volumes and project name.
4. Run initialization and explicit migrations, then start the web application:

```sh
docker compose -p simbioza run --rm --no-deps init
docker compose -p simbioza run --rm --no-deps app migrate
docker compose -p simbioza up -d app
```

5. Check health, login, representative content and jobs before restarting workers.

Migration runs under a nonblocking shared-runtime lock, loads the same config
layers without booting services requiring new tables, applies baked migration
files in order, clears regenerable cache, and records the new image ID **last**.
Serving/worker startup rejects an installed site whose recorded ID does not
match the image. A failed migration keeps the previous ID and must be repaired
or restored explicitly. There is no automatic rollback of partially applied DDL.
An image missing previously applied migrations is rejected: reverting only the
image after a database upgrade is not a safe rollback.

Use exactly one runtime volume per deployment. The file lock does not coordinate
unrelated volumes sharing one database; multi-replica/high-availability deployment
is not established by this candidate. An already running old container is not
automatically stopped by the migration command; stopping writers is mandatory.
Do not skip future release-specific data/config migration instructions.

## CLI, workers and language updates

```sh
docker compose -p simbioza run --rm --no-deps app cli orm-migrate:status
docker compose -p simbioza run --rm --no-deps app cli modules list
docker compose -p simbioza run --rm --no-deps app cli languages install de
docker compose -p simbioza run --rm --no-deps app cli email worker --batch-size=20
```

The email worker requires configured/enabled SMTP; the default disables delivery.
For a persistent worker, add an operator-managed service using the same image,
runtime mounts, user, environment and security restrictions, with command
`["cli", "email", "worker", "--watch", "--sleep=2"]`. Stop it during updates.
All CLI invocations use the same private config and sessions/data layout as HTTP.

## Optional SimpleSAMLphp

Auth loads its PHP library **in the same PHP process**. A URL to a separate
installation cannot replace its `autoload_path`. The supported candidate layout
is the optional `docker/compose.saml.yaml` overlay:

```sh
docker compose -p simbioza -f compose.yaml -f compose.saml.yaml up -d app
```

Set `SIMBIOZA_SAML_LIBRARY` to an operator-prepared compatible Linux PHP library
tree including `vendor/`, mounted read-only at `/opt/simplesamlphp`. Use a
sanitized code-only tree: do not expose the host's live configuration, private
keys, metadata, cache or sessions through that mount. Keep private files in the
runtime volume:

```text
/runtime/saml/config/config.php
/runtime/saml/config/authsources.php
/runtime/saml/metadata/
/runtime/saml/cert/
/runtime/data/saml/cache/
/runtime/data/saml/sessions/
```

`SIMPLESAMLPHP_CONFIG_DIR=/runtime/saml/config` is already set in the image.
Configure absolute private `certdir`, `cachedir`, `datadir`, metadata sources and
PHP-session save path there. Set `baseurlpath` to the public HTTPS `/saml/` URL,
register that SP and its metadata/ACS with the IdP, and enable the required SAML
module. In the Auth SAML profile use
`autoload_path=/opt/simplesamlphp/vendor/autoload.php` and its `auth_source` name.
Apache exposes only `/opt/simplesamlphp/public` at `/saml`; absent library/config
does not enable SAML automatically.

The lab verified loading SimpleSAMLphp 2.4.4 with separate private config,
sessions/cache and a fictional SP's metadata endpoint. It did **not** register a
real container SP with AAI@EduHr or prove a real IdP login. Perform that operator
check before enabling production SAML. Existing HFC SSO settings are not moved,
reused or changed. A separate SAML service/image variant remains optional future
work, not an implicit dependency of the normal Simbioza image.

## Verification and scope

See the A4 record in the [pre-release audit](pre-release-audit-0.3.0_en.md).
Only disposable fictional sites are used for container tests. HFC remains the
classical development installation; demo/apps-test remain user-managed. Image
publication and scanning are part of release CI. Real HTTPS/proxy and SAML SP/IdP
registration/login remain operator-specific deployment checks. See the
[candidate preflight record](release-preflight-0.3.3_en.md) and its recorded security limitations.
