# Install and maintain Simbioza

[Croatian version](installation_hr.md)

The [Docker/Colima candidate guide](docker_en.md) is an additional installation
option. It preserves the classical installers below and uses image replacement
plus explicit migrations, not in-place Composer/updater operations.

The six bundled guides use the current format-2 backup container. Their installer
uses a reserved, non-network destination origin only to resolve links to the selected
installation base path; it never fetches that origin or stores it as the site's public
address. User-created backups require the real source and destination addresses.

Choose the PHP mode **before** configuring the web server. Follow one complete
path rather than mixing directives or permissions from different modes:

| PHP mode | Web server | Initial graphical installer | Later GUI package/update actions |
|---|---|---|---|
| [Dedicated PHP-FPM](installation_fpm_en.md) | Apache or Nginx | Yes; selected optional packages can be installed in the wizard | Yes, after the dedicated setup and permission checks pass |
| [Apache mod_php](installation_mod_php_en.md) | Apache only | Yes; optional packages must first be prepared in the CLI | No; use the CLI for package operations and application updates |

The initial browser wizard is **not** FPM-only. In either mode it configures
the site, database, languages, administrator, and modules. All optional modules
are selected by default, and you can deselect any you do not need. What
differs is whether the web process can request installation of missing
Composer packages and later upgrades. For a new installation, dedicated FPM
is recommended. Nginx does not support Apache's `mod_php`.

## Before choosing a PHP mode

### 1. Check the host

Use Linux or macOS, PHP 8.2 or newer, Composer 2, Git, Apache 2.4 or Nginx,
and HTTPS for any public site. The PHP process used by the web server must
have the same required extensions as CLI PHP:

```text
ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip
```

Install the PDO extension matching your database: `pdo_sqlite`, `pdo_mysql`,
or `pdo_pgsql`. Check the CLI with `php -v` and `php -m`; also verify the
**web** PHP handler and extensions for the mode you choose. A working CLI
does not prove that Apache or FPM runs the same PHP build.

### 2. Fetch a tagged release

Install a tagged release into a directory **without** a `.git` checkout.
After coordinated publication, use `0.3.0` or a newer compatible stable tag on the
[release page](https://github.com/kmihalj/Simbioza/releases). This guide describes
the 0.3.0 baseline; do not substitute a 0.2.x tag while 0.3.0 is being prepared:

```bash
SIMBIOZA_TAG=0.3.0
SIMBIOZA_FETCH_DIR="$(mktemp -d)"
mkdir "$SIMBIOZA_FETCH_DIR/release"
git -C "$SIMBIOZA_FETCH_DIR/release" init -q
git -C "$SIMBIOZA_FETCH_DIR/release" remote add origin \
  https://github.com/kmihalj/Simbioza.git
git -C "$SIMBIOZA_FETCH_DIR/release" fetch --quiet --depth 1 origin \
  "refs/tags/$SIMBIOZA_TAG:refs/tags/$SIMBIOZA_TAG"
git -C "$SIMBIOZA_FETCH_DIR/release" -c advice.detachedHead=false checkout --quiet "$SIMBIOZA_TAG"
mkdir -p /srv/simbioza
rsync --archive --exclude=.git/ "$SIMBIOZA_FETCH_DIR/release/" /srv/simbioza/
cd /srv/simbioza
composer update --with-all-dependencies --optimize-autoloader
composer check-platform-reqs
```

Use the same shell account that will own and maintain the release. On macOS,
replace `/srv/simbioza` with a path on an ownership-enforcing local volume,
for example `/Users/Shared/Simbioza/simbioza`. The application manifest uses
tagged packages; do not substitute local development modules or `--no-dev`.
The required base includes Framework, ORM, Menu, Auth, Notification, HTML
Editor, Workspace, Workspace Search, and Simbioza User. Other modules can be
selected during or after installation.

Only `public/` may be exposed to the web. Never expose `config/`, `data/`,
Composer files, migrations, or bundled guides as the document root. Prepare
the runtime paths:

```bash
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Do not use `chmod 777`. The dedicated FPM setup applies its own precise
ownership; the mod_php guide explains its narrower writable paths.

### 3. Prepare an empty database

SQLite needs no database service. Select it in the installer and Simbioza
creates `data/simbioza.sqlite`. For MySQL or PostgreSQL, create an **empty**
database and a dedicated application user without global privileges.

MySQL example:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

PostgreSQL example:

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Now continue with **either** the [FPM steps](installation_fpm_en.md) **or**
the [Apache mod_php steps](installation_mod_php_en.md). Both guides include
their own web-server configuration, installer, final checks, and applicable
permissions. For multiple isolated FPM installations on one host, give each
installation a unique `--instance` name and `--listen` port as shown in the FPM
guide. The setup tool refuses to reuse an instance name for a different root.

## After installation

### Modules

The administrator opens **Settings → Setup and modules**. On dedicated FPM,
the GUI can install, remove, enable, or disable optional modules when all
environment checks pass. On mod_php, the GUI can enable or disable modules
that are already installed; for package changes use the CLI. The CLI commands
are the same in both modes:

```bash
vendor/bin/hph modules list
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
vendor/bin/hph modules backups calendar
vendor/bin/hph modules remove calendar --yes
vendor/bin/hph modules add calendar --restore
vendor/bin/hph modules migrate-status
```

Disabling retains the package, tables, and data but stops loading the module's
routes, services, menus, and tables on subsequent requests. Removing first
creates an NDJSON backup under `data/module-backups/`, then removes the
module's migrations, tables, and package. Re-adding requires an explicit fresh
start or restore. Required modules cannot be removed; dependencies are
checked. Already imported Confluence pages remain usable without the import
module, but removal is blocked while unresolved temporary import references
remain.

The selected module list is stored in `HPH_CONFIG_PATH/modules.php` (the
default is `config/modules.php`), not under `data/`. The GUI and CLI use the
same file; with configuration overrides, writes go only to the final declared
layer. See the [configuration guide](configuration_en.md) for the `.dist`
template, current module paths, and required permissions. The installer
also uses these shared paths. Language management, the updater, and application
settings backup/restore use them too. Prepare the complete current configuration before selecting an external root.

### Languages

The installer reads published languages from the
[language repository](https://github.com/kmihalj/simbioza-languages).
Croatian and English are preselected, but either can be deselected as long as
at least one language remains. Administrators manage published packs in the GUI
on mod_php or FPM when runtime data and the final configuration layer are writable.
Installed catalogues are data-only JSON, not application code. CLI is also available:

```bash
vendor/bin/hph languages list
vendor/bin/hph languages available
vendor/bin/hph languages install de
vendor/bin/hph languages disable de
vendor/bin/hph languages enable de
vendor/bin/hph languages update
vendor/bin/hph languages remove de
```

`languages update` checks installed packs independently of an application
release. The last active language cannot be disabled or removed. The wizard
imports the English and/or Croatian user guides selected at installation;
other interface languages can be installed without requiring translated
guides to exist yet.

### Application updates

Simbioza 0.3.0 requires a fresh installation. Updates from 0.2.x and old Simbioza backup formats are not supported. Future 0.3.x+ updates use the self-updating updater.

On a correctly configured dedicated FPM installation, the administrator may
check and start an update in GUI Setup. The actual job runs as the restricted
deployment account, not as the web process. The signed-in Unix maintainer can
also run the CLI after receiving deploy and runtime group membership and
signing in to a new shell session. On mod_php, the Unix code owner uses the
CLI. Neither routine requires root:

```bash
cd /srv/simbioza
php update.php --check
php update.php
```

To request a specific published tag, use `php update.php --tag=<TAG>`. The updater first validates the updater from the latest stable release,
even if you request a different application tag. A changed updater is backed
up privately, replaced atomically, and started in a fresh PHP process with the
same update lock, deployment identity, language, target tag, and GUI progress
channel. Only then does it back up code, enable maintenance, preserve private
configuration and data, resolve the installed optional modules, install
compatible tagged packages, check bootstrap, apply migrations, refresh guides
and the theme, and clear caches. Do **not** replace it with a standalone
`composer update` on an existing installation: that could drop optional
packages from the installation's selected set.

A failure **before** migrations automatically restores code and Composer
packages. Once migrations have started, maintenance deliberately remains on
for controlled recovery. `data/update-maintenance.json` is a safety guard:
never move or delete it until you have proved that no update process is still
running and inspected the log and backup path. Confirm the final updater
status, zero pending migrations, and real sign-in/content flows after every
update. Back up the database, settings, uploaded files, and themes separately.

Updater backups are stored in `data/backups/updater/` with mode `0600`;
temporary work and handoff files stay inside `data/` and are removed when the
job finishes. Application rollback does not downgrade the refreshed updater.
`--check` is read-only; `--updater-info` displays the installed updater version
and handoff protocol without requiring application dependencies. Releases
older than 0.3.0 are not valid targets for this updater.

Avoid routine `sudo php update.php` and never run the updater as the
web/FPM account.

The current updater follows `framework.php`, `HPH_CONFIG_PATH`, and ordered
configuration overrides without initializing the application before its own
refresh. It migrates obsolete module-selection storage before application
preflight and can undo that transition before database migrations start. Private
external layers are excluded from code synchronization and code-recovery archives;
keep a separate secure backup of them. Application settings restore targets the
active final layer and never replaces dynamic `app.php` with evaluated data. See
the [configuration guide](configuration_en.md#backup-and-permissions).
After changing a dedicated FPM installation's paths, explicitly regenerate its
pool/helper with the same instance/options and prepare external permissions.
