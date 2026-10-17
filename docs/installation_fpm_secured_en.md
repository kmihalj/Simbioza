# Install Simbioza with secured PHP-FPM

[Croatian version](installation_fpm_secured_hr.md)

This complete guide starts from an empty application directory and assumes no
Simbioza FPM/deploy accounts exist yet. You do not need another installation
guide. The setup tool creates dedicated accounts, groups, a pool/service and a
restricted helper: the web process cannot write code, but it can request package
operations from the separate deployment worker. This is the recommended mode.
For reinstallation, an administrator must first inspect existing identities,
instance names and services; never create a second site under the same identity.

## 1. Prepare the host

Start with a normal administrator shell on Linux or macOS. You need a domain
pointing at the server and a valid TLS certificate for the HTTPS examples.
Run only the commands for your operating system. Do not continue after an error.

On Debian/Ubuntu, install the tools and PHP extensions:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql php-fpm
```

On macOS, install Homebrew first if it is not installed, then:

```bash
brew install git rsync composer httpd php
```

Check the installed tools:

```bash
php -v
php -m
composer --version
git --version
```

PHP must be **8.2 or newer**, Composer must be version 2, and PHP needs
`ctype dom fileinfo json libxml mbstring openssl pdo session xmlreader zip`.
Use `pdo_sqlite`, `pdo_mysql` or `pdo_pgsql` for your chosen database.
CLI PHP and the web PHP handler must have matching versions and extensions.

## 2. Download a clean Simbioza 0.3.3 release

Choose **one** application directory. On Linux:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
```

On macOS, use the internal disk, not a development checkout or a volume
mounted with `noowners`:

```bash
SIMBIOZA_APP_ROOT=/Users/Shared/Simbioza/simbioza
```

Keep this shell open. Change the chosen path now if you need another site.
The directory must **not exist yet**; this check deliberately refuses to
overwrite an existing installation. An administrator reinstalling a site must
first handle its old service and data separately. Nothing here deletes them.

Run the complete download block as your normal maintainer account:

```bash
test ! -e "$SIMBIOZA_APP_ROOT" && test ! -L "$SIMBIOZA_APP_ROOT" &&
sudo install -d -o "$(id -un)" -g "$(id -gn)" -m 0755 "$SIMBIOZA_APP_ROOT" &&
SIMBIOZA_FETCH_DIR="$(mktemp -d)" &&
git clone --depth 1 --branch 0.3.3 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Expect `0.3.3` and `success` for every platform requirement.
Git's **detached HEAD** notice is normal when checking out a release tag.
Do not run Composer with `sudo`, copy another site's configuration or database,
include `.git/` in the deployed application, or use `--no-dev` here.
Version 0.3.0 is a fresh-install baseline, not an upgrade from 0.2.x.

The default configuration root is `config/` (`HPH_CONFIG_PATH`), with a
subdirectory for each module. Persistent files belong under `data/`.
Only `public/` will be exposed by the web server. Keep the default paths
for this first installation; external configuration layers are an advanced
option, not a prerequisite.

## 3. Choose and prepare the database

Choose **one** database. SQLite is the shortest route and needs no database
server or database password: select SQLite in the wizard and it creates
`data/simbioza.sqlite`. Continue to step 4.

If you choose a server database and do not yet have its service, install
**only the one you chose**. On Debian/Ubuntu: `sudo apt-get install mariadb-server`
or `sudo apt-get install postgresql`. On macOS: `brew install mariadb`,
then `brew services start mariadb`, or `brew install postgresql@18`,
then `brew services start postgresql@18`. For the versioned Homebrew
PostgreSQL tools, use `/opt/homebrew/opt/postgresql@18/bin/createuser`
and `/opt/homebrew/opt/postgresql@18/bin/createdb` below.

For **MySQL**, start your MySQL/MariaDB service and enter its administrator
client. Create an empty database and a restricted application account:

```sql
CREATE DATABASE simbioza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simbioza'@'127.0.0.1' IDENTIFIED BY '<unique-database-password>';
GRANT ALL PRIVILEGES ON simbioza.* TO 'simbioza'@'127.0.0.1';
```

Use host `127.0.0.1`, your service's port (normally `3306`), database
`simbioza`, user `simbioza` and that password in the wizard.
Do not use the database administrator as the application's account.

For **PostgreSQL**, start its service and run these commands as a PostgreSQL
administrator (on Debian/Ubuntu, prefix each with `sudo -u postgres`):

```bash
createuser --pwprompt --no-superuser --no-createdb --no-createrole simbioza
createdb --owner=simbioza --encoding=UTF8 simbioza
```

Use host `127.0.0.1`, your service's port (normally `5432`), database and
user `simbioza`, and the password entered at the private prompt.
Check that the database's local TCP authentication allows that account.
Do not put real passwords in documentation, command history or Git.

## 4. Create the dedicated pool

On Debian, install the PHP-FPM package for the same PHP version used by the CLI
and the `acl` package. From the release directory, run the one-time system step:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
```

On macOS with Homebrew PHP, use the actual application path and FPM binary:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" \
  --maintainer="$(id -un)" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

For a second site, repeat both commands with matching options, for example
`--instance=second --listen=127.0.0.1:9076`. Use the same two options again
with `--finalize` in step 7. Do not accidentally omit them: the unsuffixed
default instance is a different system service.

The check is read-only. The default pool listens on `127.0.0.1:9075`; keep
that port local to the server. The tool gives the FPM account write access to
runtime data, not to the whole release, and installs a narrowly restricted
helper for package changes. It also creates the deploy and runtime groups.
Check every diagnostic result before continuing. Do not use `chmod 777`.

With the default application configuration directory, Auth, Email, HTML Editor,
Workspace, Menu, Theme and Backup have runtime-writable module directories.
Backup needs this for atomic provider-descriptor publication. Release-managed
PHP service configuration remains deploy-owned and read-only to FPM. The tool
does not recreate `data/config` or grant runtime rights to removed legacy
`editor-html.php`, `email.php` or `workspace.php` files.

## 5. Configure the web server

Choose **one** of these configurations. They serve a dedicated hostname at
`/`; replace the hostname, release path, and TLS certificate settings. For a
subdirectory installation, also configure the URL prefix in the web server
and pass that complete URL to `install:prepare` in step 6.

### Apache with PHP-FPM

Enable Apache `rewrite`, `proxy`, `proxy_fcgi`, and TLS. Direct PHP requests
for this site to the dedicated pool; do not route them through `mod_php`.

For Apache on Debian/Ubuntu, enable its FPM proxy modules and create the site:

```bash
sudo a2enmod rewrite proxy proxy_fcgi ssl
sudoedit /etc/apache2/sites-available/simbioza.conf
```

On macOS, edit `/opt/homebrew/etc/httpd/httpd.conf` with `sudoedit`.
Enable `rewrite_module`, `proxy_module`, `proxy_fcgi_module`, `ssl_module`
and `socache_shmcb_module`. Add `Listen 443` if no existing directive listens
there. Do not add a mod_php handler for this site.

For a new Homebrew Apache, explicitly set the shared worker identity in
`httpd.conf` (do not run web workers as your code-owning login):

```apache
User _www
Group _www
```

In the configuration below, replace the domain, TLS certificate paths and
**both** `/srv/simbioza/public` paths with your actual application path
(on macOS, usually `/Users/Shared/Simbioza/simbioza/public`).
Apache does not expand `$SIMBIOZA_APP_ROOT` from your terminal.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public
    ProxyTimeout 900

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler "proxy:fcgi://127.0.0.1:9075"
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

Test the Apache configuration before a graceful reload. Do not expose the
parent release directory as a document root.

After saving, test and start Apache. On Debian/Ubuntu:

```bash
sudo a2ensite simbioza
sudo apache2ctl configtest
sudo systemctl enable --now apache2
sudo systemctl reload apache2
```

On macOS:

```bash
sudo /opt/homebrew/bin/httpd -t
sudo /opt/homebrew/bin/brew services start httpd
```

Expect `Syntax OK`; do not start/reload after a failed check.
For an already running Apache, use one validated graceful reload instead of
starting a second service. Continue to the installer; the Nginx alternative
below is not an additional step.

### Nginx with PHP-FPM

Nginx cannot run `mod_php`; it sends PHP requests to the same dedicated FPM
pool. Keep the `SCRIPT_FILENAME` and `try_files` rules together:

Choose this alternative **instead of Apache**: use `nginx` rather than
`apache2` in step 1's package list (`brew install nginx` on macOS), and do
not execute the Apache configuration/start commands. Edit
`/etc/nginx/sites-available/simbioza` on Debian/Ubuntu or
`/opt/homebrew/etc/nginx/nginx.conf` on macOS. Replace the domain, certificate
paths and application root in the complete server block below.

```nginx
server {
    listen 443 ssl;
    server_name simbioza.example.org;
    root /srv/simbioza/public;
    index index.php;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9075;
        fastcgi_read_timeout 900s;
    }

    location ~ /\. {
        deny all;
    }
}
```

On Debian/Ubuntu, enable the new server block, test and start:

```bash
sudo ln -s /etc/nginx/sites-available/simbioza /etc/nginx/sites-enabled/simbioza
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

On macOS: `sudo /opt/homebrew/bin/nginx -t`, then
`sudo /opt/homebrew/bin/brew services start nginx`.
If the link or service already exists, inspect it rather than duplicating it.

Test the Nginx configuration before reloading. The FPM listener must not be
reachable from an untrusted network. Package preparation through the graphical
installer can take several minutes; the 900-second Apache/Nginx gateway wait
prevents a default 60-second 504 while the restricted worker is still running.
Adjust any additional upstream reverse-proxy timeout accordingly, but do not
disable the FPM or web-server request limits for unrelated sites.
The dedicated pool generated by `configure_fpm_setup.php` also sets PHP's
`max_execution_time` to 900 seconds. This matters: on the tested macOS/PHP
8.5 build, a value of `0` still terminated a long request after one minute,
although it should mean no limit. Check both timeout settings in the
generated pool and test a longer package preparation.

### Optional: install under a URL prefix such as /simbioza

For a shared hostname, place this block **inside its existing HTTPS virtual
host**, instead of creating another virtual host with the same name.
Replace the release path, URL prefix and (for FPM) the pool port:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "proxy:fcgi://127.0.0.1:9075"
    </FilesMatch>
</Directory>
```

Use `--base-url=https://your-host.example/simbioza` for the installer, not
the hostname without its prefix. For a demo at `/demo`, replace **both**
occurrences of the prefix and use `--base-url=https://your-host.example/demo`.
The directory path is a filesystem path; the prefix is a URL path, not the
application directory's name. Keep private `config/` and `data/` outside
the exposed alias. Validate Apache before its graceful reload.

## 6. Run the graphical installer

Generate a one-time installer URL from the release directory:

```bash
sudo -H -u fpm-simbioza php bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

If you chose `--instance=second`, use `-u fpm-simbioza-second` instead.
This runs PHP as the FPM identity, not as root. For a site at `/simbioza`, use
`--base-url=https://simbioza.example.org/simbioza` instead. Open the URL in a
private browser window; do not share or screenshot its token. The wizard checks
requirements and database access, asks for the site identity, languages, time
zone, first administrator, and optional modules, then shows a review before
installation. All optional modules are preselected; deselect those you do not
want. With the dedicated helper working, selected optional packages are
installed through the GUI.

If you intentionally omit the dedicated helper, prepare the selected optional
packages as the deploy user with
`php scripts/installation_packages.php prepare` for all modules, or add
`--modules=theme,calendar` for a smaller selection. Match the list to your
selections and reload the installer. Without the helper, later GUI package
changes are unavailable. Language installation/update/removal writes only
runtime data and configuration and does not require this helper. Ensure those
directories are writable by PHP; see [localization](localization_en.md).

After installation, sign in as the administrator. In **Settings → Setup and
modules**, verify that the dedicated pool, helper, and runtime permissions are
all reported as ready. The wizard imports the selected user-guide languages.

## 7. Finalize permissions and verify

After the wizard, sign out and back into SSH/the terminal to activate the new
deploy/runtime group memberships. Restore `SIMBIOZA_APP_ROOT` to the path
selected in step 2, then run the matching commands:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)"
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

On macOS, add the same `--php-fpm` argument and application path used in step
4. Sign out and back in so the maintainer receives the new group memberships.
Expect zero pending migrations, successful sign-in and home pages, and a locked
`/install` route. Back up the database, `config/`, user files, and theme data.

The one-time `sudo` setup does **not** mean normal updates require root.
The maintenance commands are included at the end of this guide.

## Optional SimpleSAMLphp integration

When SAML is used, its endpoint and Simbioza must run through the **same**
dedicated FPM pool if sessions use `store.type = phpsession`. Keep private SAML
settings and runtime data under this installation's `data/` tree. The private
config must have unique salts, admin password, cookie names, and cookie path;
never copy another installation's secrets or SP keys.

Prepare `data/saml/config/config.php`, `authsources.php`, and private cert and
runtime directories before pool setup. Add
`--simplesaml-config-dir=/srv/simbioza/data/saml/config` to both `--install`
and `--finalize`. Route the installation-specific `/simplesaml` PHP endpoint
through this pool, then register its matching EntityID, ACS, and SLO URLs with
the identity provider. Do not retire an existing endpoint until the new
registration has propagated and browser sign-in and sign-out both succeed.

## Maintenance after installation

Keep routine CLI commands under the code owner's account, never the web
account and not root. Back up the database, the active `HPH_CONFIG_PATH`
directories and persistent `data/` before an update.

Read-only checks:

```bash
cd "$SIMBIOZA_APP_ROOT"
vendor/bin/hph modules list
vendor/bin/hph languages available
php update.php --check
```

To add a module later, run `vendor/bin/hph modules add calendar --fresh`;
`modules disable calendar` retains its data and `modules enable calendar`
enables it again. Removal uses `modules remove calendar --yes` and creates a
module backup; inspect it with `modules backups calendar` before a deliberate
`modules add calendar --restore`. These are alternatives, not a block to
execute one after another.

Language packs are independent of application releases. Use
`vendor/bin/hph languages install de` or `vendor/bin/hph languages update`;
the language GUI also works when its runtime directories are writable.

When you intend to update the application, use `php update.php` as the code
owner/deploy maintainer. The updater refreshes itself first, preserves selected
modules and private configuration, applies migrations and refreshes bundled
guides. Do not substitute a standalone `composer update` on an installed site.
Before migrations, a failed update can roll back code; after migrations start,
maintenance stays enabled for controlled recovery. Never delete
`data/update-maintenance.json` to hide an unfinished update.

Afterwards check zero pending migrations, real sign-in, content and attachments.
The examples use the default `config/` and `data/` paths; if you later choose
external configuration layers, keep their permissions and backup coverage explicit.

## External configuration layers

Prepare the complete configuration before selecting it in private `framework.php`.
The generated pool and fixed helper pin the same base and override parameters;
they do not rely on inherited variables surviving `clear_env` or `sudo`. Linux
sandbox rules bind lower layers read-only and expose the final layer plus the
configured Setup request directory. This permits writes only where Unix rights
already allow them; the tool does not chown/chmod external layers.

Give the FPM and deploy accounts read/search access. Keep PHP service code
deploy-owned and read-only to FPM; give runtime-owned writable data files/locks
the shared runtime group and atomic-replacement permissions. Do not use a
protected home directory or a public document root for private configuration.
Pre-create a nondefault request directory with the required shared permissions.
After changing paths on an existing site, repeat `--install`, then `--finalize`,
using the same instance, port, and SAML options. `--finalize` alone refreshes the
helper/ownership, not the FPM pool. See the [configuration guide](configuration_en.md).

