# Installing and maintaining Simbioza

[Hrvatska verzija](installation_hr.md)

Install Simbioza from a tagged release. The initial package contains only the
required core; optional modules are added during or after installation. The
**Theme** module is recommended and selected by default, but can be deselected.

Two operating modes are supported:

- with a dedicated PHP-FPM pool, an administrator can install, remove, enable,
  and disable modules, add languages, and update the application from the GUI;
- without the dedicated pool, package operations use the CLI, while the GUI can
  still enable or disable modules that are already installed.

Routine use and maintenance do not need `sudo`. It is used once to create the
isolated system identities, FPM service, and strictly restricted helper.

## 1. Requirements

- Linux or macOS;
- PHP 8.2 or newer;
- Composer 2 and Git;
- Apache 2.4 or Nginx;
- an empty SQLite, MySQL, or PostgreSQL database;
- HTTPS for every publicly reachable installation.

Required PHP extensions:

```text
ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip
```

Install the matching PDO extension as well: `pdo_sqlite`, `pdo_mysql`, or
`pdo_pgsql`. Verify the environment with:

```bash
php -v
php -m
composer check-platform-reqs
```

## 2. Fetch a tagged release

Copy only the selected tag to the server; do not retain a `.git` directory.
Replace `0.1.75` with the actual release being installed:

```bash
git clone --quiet --depth 1 --branch 0.1.75 --single-branch \
https://github.com/kmihalj/Simbioza.git /tmp/simbioza-release
mkdir -p /srv/simbioza
rsync --archive --exclude=.git/ /tmp/simbioza-release/ /srv/simbioza/
cd /srv/simbioza
composer update --with-all-dependencies --optimize-autoloader
composer check-platform-reqs
```

Simbioza production installations use tagged packages. Do not use `--no-dev`:
development dependencies are not part of the production manifest, and local
source packages are linked only in a development environment.

The required core contains Framework, ORM, Menu, Auth, Notification, HTML
Editor, Workspace, Workspace Search, and Simbioza User. API, Task, Theme,
Audit, E-mail, Comment, Calendar, Confluence Import, and Backup are installed
only when selected or added later.

## 3. Document root and basic permissions

The document root must be `public/`. Never expose `config/`, `data/`,
migrations, or bundled guide packages to the web.

```bash
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
chmod 750 config data resources/config/menu resources/config/theme
```

Do not use `chmod 777`. The dedicated tool in section 6 applies precise owners
and permissions. Without it, the PHP process must be able to read the
application and write to `data/`, dynamic files in `config/`,
`resources/config/menu/`, and `resources/config/theme/`.

## 4. Apache

Minimal VirtualHost:

```apache
<VirtualHost *:443>
ServerName simbioza.example.org
DocumentRoot /srv/simbioza/public

<Directory /srv/simbioza/public>
Options -Indexes +FollowSymLinks
AllowOverride FileInfo Options
Require all granted
</Directory>

<FilesMatch ".+\.php$">
SetHandler "proxy:fcgi://127.0.0.1:9075"
</FilesMatch>

SSLEngine on
# Add the organization's certificate and private key here.
</VirtualHost>
```

Enable `rewrite`, `proxy`, `proxy_fcgi`, TLS, and the matching FastCGI
configuration. Port `9075` listens only on `127.0.0.1` and must not be exposed
to the network.

## 5. Nginx

```nginx
server {
listen 443 ssl http2;
server_name simbioza.example.org;
root /srv/simbioza/public;
index index.php;

location / {
try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
try_files $uri =404;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
fastcgi_pass 127.0.0.1:9075;
}

location ~ /\. {
deny all;
}
}
```

## 6. Recommended dedicated FPM and secure GUI Setup

On Debian, first install the PHP-FPM version matching CLI PHP and the `acl`
package used for separate read access to the private SAML configuration. Then run this
once from the release directory:

```bash
cd /srv/simbioza
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/srv/simbioza \
--maintainer=LOGIN
```

The tool creates locked `fpm-simbioza` and `simbioza-deploy` accounts and
separate `app-simbioza`, `deploy-simbioza`, and `run-simbioza` groups. It adds
the maintainer to the deploy and runtime groups. The web process cannot modify
the whole code tree. On Linux the pool runs in its own systemd service with
`ProtectSystem=strict`, a read-only application-code bind, and writes limited
to documented runtime paths. The restricted root-owned helper accepts only the
random ID of a prevalidated request. On Linux it then asks systemd to run a
separate unprivileged `simbioza-deploy` worker with `NoNewPrivileges`
re-enabled; the web process receives neither a general root shell nor direct
Composer access.

The read-only check is:

```bash
php scripts/configure_fpm_setup.php \
--check \
--app-root=/srv/simbioza \
--maintainer=LOGIN
```

After completing the web installer, harden the newly created runtime files
once:

```bash
sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/srv/simbioza \
--maintainer=LOGIN
```

Log out and back in so the shell receives the new group memberships. CLI, GUI
Setup, and updates then work without `sudo`.

### 6.1. SAML authentication and FPM settings

When an installation uses SimpleSAMLphp, the application and its
SimpleSAMLphp endpoint must run through the **same** dedicated FPM pool. Moving
only Simbioza to FPM while leaving the shared `/simplesaml` endpoint on a
different PHP handler breaks session continuity when `store.type = phpsession`.

The system SimpleSAMLphp code remains shared, while every Simbioza installation
has private settings and runtime data. Prepare, for example:

```text
/srv/simbioza/data/saml/config/config.php
/srv/simbioza/data/saml/config/authsources.php
/srv/simbioza/data/saml/cert/
/srv/simbioza/data/saml-runtime/cache/
/srv/simbioza/data/saml-runtime/data/
/srv/simbioza/data/sessions/
```

The private `config.php` needs unique `secretsalt`, `assets.salt`, admin
password, cookie names, and cookie path. Never copy shared salts, the shared
admin password, or SP private keys. For an installation below `/simbioza/`,
the relevant settings look like this:

```php
'baseurlpath' => 'https://simbioza.example.org/simbioza/simplesaml/',
'cachedir' => '/srv/simbioza/data/saml-runtime/cache',
'datadir' => '/srv/simbioza/data/saml-runtime/data',
'certdir' => '/srv/simbioza/data/saml/cert',
'metadatadir' => '/usr/share/simplesamlphp-aai/metadata',
'store.type' => 'phpsession',
'session.phpsession.savepath' => '/srv/simbioza/data/sessions',
'session.phpsession.cookiename' => 'SimpleSAMLSimbioza',
'session.cookie.name' => 'SimpleSAMLSimbiozaStore',
'session.cookie.path' => '/simbioza/',
```

Pass the private directory to both initial setup and final hardening. The tool
validates it, exposes it only to the Simbioza pool, and keeps it writable by
the deploy account but read-only for FPM:

```bash
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/srv/simbioza \
--maintainer=LOGIN \
--simplesaml-config-dir=/srv/simbioza/data/saml/config

sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/srv/simbioza \
--maintainer=LOGIN \
--simplesaml-config-dir=/srv/simbioza/data/saml/config
```

On Apache, route the application-specific SAML PHP endpoint to the same
`127.0.0.1:9075` pool before general PHP rules:

```apache
ProxyPassMatch "^/simbioza/simplesaml/(.+?[.]php)(/.*)?$" \
"fcgi://127.0.0.1:9075/usr/share/simplesamlphp-aai/public/$1$2"
Alias /simbioza/simplesaml "/usr/share/simplesamlphp-aai/public/"

<Directory "/usr/share/simplesamlphp-aai/public">
Options -Indexes
AllowOverride None
Require all granted
</Directory>
```

Register the new application-specific EntityID, ACS, and SLO URLs below
`/simbioza/simplesaml/` in the AAI/proxy registry. On an existing installation,
keep the old endpoint active until the new records are registered and
propagated; only then switch sign-in and verify the complete browser sign-in
and sign-out flow. The `auth_source` may remain `default-sp`, and the
application continues to load the shared SimpleSAMLphp package autoloader.

For the AAI `default-sp` source, register the following new values (replace the
domain and base path with those of the real installation):

| Field | New value |
|---|---|
| **EntityID** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/metadata.php/default-sp` |
| **AssertionConsumerService URL** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp` |
| **SingleLogoutService URL** | `https://simbioza.example.org/simbioza/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp` |

If `proxy-sp` is also used, register the same URL set ending in `/proxy-sp`
instead of `/default-sp`. The matching `authsources.php` entry must contain the
same EntityID registered for that source. Changing the PHP handler without
registering these values breaks the IdP return and logout even when the
Simbioza landing page itself still opens normally.

## 7. Installation without dedicated FPM

The web installer never runs Composer from an ordinary web process. Prepare
the packages you intend to select before opening the wizard. With no argument,
the recommended Theme is prepared:

```bash
php scripts/installation_packages.php prepare
```

For a custom selection:

```bash
php scripts/installation_packages.php prepare \
--modules=theme,calendar,email
```

After the web installation succeeds, remove only the transient Backup package
that was needed to import the bundled guides:

```bash
php scripts/installation_packages.php cleanup
```

An explicitly selected Backup module remains installed.

## 8. Empty database

SQLite needs no server; the installer creates `data/simbioza.sqlite`. For
MySQL or PostgreSQL, create an empty database and a dedicated application user
without global administrative privileges.

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

## 9. Web installer

Create the one-time address:

```bash
bin/simbioza install:prepare \
--base-url=https://simbioza.example.org
```

Include a subdirectory in the base URL when applicable:

```bash
bin/simbioza install:prepare \
--base-url=https://simbioza.example.org/simbioza
```

Open the token address in a private browser window. Do not send it by e-mail,
chat, ticket, or screenshot. The token is consumed on the first valid visit.

The wizard covers:

1. PHP, extension, path, and package checks;
2. connection to an empty database;
3. application name, languages, time zone, and first administrator;
4. optional-module selection;
5. a password-free review and the actual installation.

Theme is recommended and selected by default. If retained, the bundled
Simbioza theme is imported and activated. The bilingual **User guides**
workspace is always imported; pages for optional modules are imported only
when their module is available.

## 10. Setup after sign-in

An administrator opens **Settings → Setup and modules**. Diagnostics show the
actual FPM mode, helper availability, owner, group, and mode of each relevant
path.

When every required FPM check passes, the GUI supports:

- installing and uninstalling an optional module;
- enabling and disabling an installed module;
- restoring NDJSON-backed data or performing a fresh reinstall;
- adding a validated JSON language pack;
- checking and starting a complete application update.

Without dedicated FPM, the GUI offers only safe state changes for existing
modules and displays the exact CLI command for every unavailable package
operation.

## 11. Module CLI

```bash
vendor/bin/hph modules list
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
vendor/bin/hph modules remove calendar --yes
vendor/bin/hph modules backups calendar
vendor/bin/hph modules add calendar --restore
```

The same commands are used in both installation modes. On a correctly
configured FPM installation, CLI Composer package add/remove automatically
uses the same restricted deploy helper as the GUI; the signed-in maintainer
runs `enable`, `disable`, and migrations directly. Without dedicated FPM, the
installation owner performs package operations. Routine work needs no `sudo`
in either mode.

On the next request, `disable` stops loading the module manifest, routes,
services, menu entries, and tables, while retaining its package, tables, and
data. `remove` first creates an NDJSON backup in `data/module-backups/`, then
removes the module migrations, tables, and Composer package. On a later add,
choose `--restore` or `--fresh`. A required module cannot be removed, and
dependencies are checked before every change.

Confluence Import is not needed for normal display of already imported pages.
Removal still stops if any content version contains an unresolved temporary
import reference.

## 12. Adding a language

```bash
vendor/bin/hph languages list
vendor/bin/hph languages template de \
--source=en \
--output=/tmp/de.json
vendor/bin/hph languages validate /tmp/de.json
vendor/bin/hph languages add /tmp/de.json
```

One pack contains every string from the application and currently installed
modules, multilingual language names, and a safe SVG flag. German is a bundled
example only; it is not enabled automatically. See the
[localization guide](localization_en.md) for details.

## 13. Updates

In dedicated FPM mode, updates can be checked and started from GUI Setup. The
background job then runs as the restricted `simbioza-deploy` account, while
the FPM process receives no write access to application code.

The same CLI works in both installation modes:

```bash
php update.php --check
php update.php
```

To select a tag:

```bash
php update.php --tag=0.1.75
```

On a dedicated FPM installation, run it as the signed-in maintainer who became
a member of `deploy-simbioza` and `run-simbioza` after initial setup and a new
login session. Do not run the updater as `fpm-simbioza`; `sudo` is not needed:

```bash
cd /srv/simbioza
php update.php --check
php update.php
```

Without dedicated FPM, run the same commands as the Unix account that owns the
application code and writable settings. If the permissions check fails, fix
ownership once; do not routinely run the web application or updater as `root`.

The updater backs up code, enables maintenance, preserves private
configuration and data, updates tagged packages, verifies bootstrap, applies
migrations, refreshes bundled guides and the theme, and clears caches. A
failure before migrations rolls back automatically; after migrations start,
maintenance remains enabled for controlled recovery.

`data/update-maintenance.json` is an updater safety guard. Do not move or
delete it until you have verified that no update process is running. The
updater removes it after success or a safe automatic rollback. After a failure
that occurred once migrations had started, it deliberately keeps the guard in
place and prints the backup path for controlled recovery.

On the first update of a legacy installation, the updater extracts the current
module state from the static `config/app.php` or legacy `config/modules.php`
into persistent `data/config/modules.php`, then installs the new dynamic
configuration. The selection remains unchanged, GUI and CLI can both use an
atomic replacement of the same file, and FPM still cannot modify release
configuration.

The existing administrator-managed settings menu remains untouched. When a new
release provides settings for a new module or feature, the updater appends only
the missing entries at the end without changing existing labels, order, or
enabled state.

For a coordinated release, publish tags for every changed module first, then
publish the main Simbioza tag that references them.

## 14. macOS specifics

For a local or internal macOS server, install Homebrew PHP, Composer, Git, and
Apache:

```bash
brew install php composer git httpd
brew_prefix="$(brew --prefix)"
php_fpm="$brew_prefix/sbin/php-fpm"
```

Homebrew normally uses `/opt/homebrew` on Apple Silicon and `/usr/local` on
Intel. The setup tool detects the prefix from the actual `php-fpm` binary and
stores its isolated configuration and runtime directory under that prefix.

Place the application on a filesystem where macOS enforces ownership, for
example `/Users/Shared/Simbioza/simbioza`. An external volume mounted with
`noowners` is unsuitable: `chown` can appear to succeed while user isolation
is not enforced. Every parent directory must permit traversal by the dedicated
FPM user.

```bash
sudo php scripts/configure_fpm_setup.php \
--install \
--app-root=/Users/Shared/Simbioza/simbioza \
--maintainer="$USER" \
--php-fpm="$php_fpm"
```

The tool creates the system launchd service `hr.simbioza.php-fpm`. Homebrew
Apache must load `mod_proxy` and `mod_proxy_fcgi`, use `public/` as its
DocumentRoot, and forward PHP to `127.0.0.1:9075` as shown in section 4.

After the web installation:

```bash
sudo php scripts/configure_fpm_setup.php \
--finalize \
--app-root=/Users/Shared/Simbioza/simbioza \
--maintainer="$USER" \
--php-fpm="$php_fpm"
```

Sign in to the macOS session again. Further GUI and CLI maintenance does not
require `sudo`.

## 15. Final verification

```bash
php scripts/configure_fpm_setup.php \
--check \
--app-root=/srv/simbioza \
--maintainer=LOGIN
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Expect zero pending migrations, HTTP 200 for sign-in and home, and 404 for the
locked `/install`. Verify backups of the database, `config/`, user files, and
themes. Never include confidential values in a bug report.
