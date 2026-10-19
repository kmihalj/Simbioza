# Install Simbioza with Apache mod_php

[Croatian version](installation_mod_php_hr.md)

This is a complete fresh-install guide, from a normal terminal to the first
administrator sign-in. No other installation guide is required. Apache executes
PHP inside its shared web process; package operations and application updates
remain CLI-only. Nginx cannot use mod_php. Never make the application code
web-writable to obtain GUI package installation.

## 1. Prepare the host

Start with a normal administrator shell on Linux or macOS. You need a domain
pointing at the server and a valid TLS certificate for the HTTPS examples.
Run only the commands for your operating system. Do not continue after an error.

On Debian/Ubuntu, install the tools and PHP extensions:

```bash
sudo apt-get update
sudo apt-get install git rsync composer acl apache2 php-cli php-curl php-mbstring php-xml php-zip php-intl php-sqlite3 php-mysql php-pgsql libapache2-mod-php
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

## 2. Download a clean Simbioza 0.3.11 release

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
git clone --depth 1 --branch 0.3.11 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Expect `0.3.11` and `success` for every platform requirement.
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

## 4. Prepare narrow runtime permissions

The system packages provide the shared web account: `www-data` on
Debian/Ubuntu, `_www` for the Homebrew Apache example. Confirm your actual
Apache user/group before applying these commands. The code remains owned by
your maintainer; the web account writes only runtime data and dynamic settings.

On Debian/Ubuntu:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp www-data config
sudo chmod 3770 config
sudo chgrp -R www-data data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo setfacl -R -m u:"$SIMBIOZA_MAINTAINER":rwX,u:www-data:rwX data
sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp www-data "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo setfacl -m u:www-data:rwx,d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec setfacl -m d:u:"$SIMBIOZA_MAINTAINER":rwx,d:u:www-data:rwx {} +
```

On macOS:

```bash
cd "$SIMBIOZA_APP_ROOT"
SIMBIOZA_MAINTAINER="$(id -un)"
sudo chgrp _www config
sudo chmod 3770 config
sudo chgrp -R _www data
sudo chmod -R g+rwX data
sudo find data -type d -exec chmod 2770 {} +
sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
for SIMBIOZA_MODULE in auth email editor-html workspace menu theme backup api calendar; do
  mkdir -p "config/$SIMBIOZA_MODULE"
  sudo chgrp _www "config/$SIMBIOZA_MODULE"
  sudo chmod 3770 "config/$SIMBIOZA_MODULE"
  sudo chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" "config/$SIMBIOZA_MODULE"
done
sudo find data -type d -exec chmod +a "user:${SIMBIOZA_MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

Do not grant web write access to the release root, `src/`, `scripts/`,
`public/`, `vendor/`, `composer.json` or static PHP service configuration.
Sticky configuration directories permit atomic replacement of web-owned
settings without permitting replacement of maintainer-owned service code.
Never use `chmod 777`.

## 5. Confirm that Apache actually runs mod_php

The CLI PHP version alone is not proof that Apache uses the same PHP. Inspect
the active Apache modules and handler; verify that PHP in a browser uses a
supported version and the required extensions. The usual non-thread-safe
`mod_php` build requires Apache's `prefork` MPM. Do not enable both the FPM
proxy handler and the `mod_php` handler for this site.

On Debian/Ubuntu, enable the non-threaded PHP handler and required modules:

```bash
sudo a2dismod mpm_event
sudo a2enmod mpm_prefork rewrite ssl
sudo a2enmod "php$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit /etc/apache2/sites-available/simbioza.conf
```

On macOS, edit `/opt/homebrew/etc/httpd/httpd.conf` with `sudoedit`.
Enable `mpm_prefork_module`, `rewrite_module`, `ssl_module` and
`socache_shmcb_module`; disable `mpm_event_module`. Add `Listen 443` if no directive already
listens there, then add:

```apache
LoadModule php_module /opt/homebrew/opt/php/lib/httpd/modules/libphp.so
```

For a new Homebrew Apache, explicitly set the shared worker identity in
`httpd.conf` (do not run web workers as your code-owning login):

```apache
User _www
Group _www
```

Use the following virtual host in the selected configuration file. Replace
the domain and certificate paths and replace **both** application paths with
your chosen `SIMBIOZA_APP_ROOT/public`. Apache configuration does not expand
your shell variables.

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Indexes Options
        Require all granted
        DirectoryIndex index.php

        <FilesMatch "\.php$">
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/fullchain.pem
    SSLCertificateKeyFile /path/to/privkey.pem
</VirtualHost>
```

The PHP module's `LoadModule` directive is distribution-specific and must
point to the actual installed module. Test Apache's configuration before a
graceful reload. For a subdirectory installation, configure its URL alias and
use that full base URL in step 7.

Test and start only this configured Apache service. On Debian/Ubuntu:

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

Expect `Syntax OK`. If the service already exists, use a validated graceful
reload rather than restarting unrelated sites.

### Optional: install under a URL prefix such as /simbioza

For a shared hostname, place this block **inside its existing HTTPS virtual
host**, instead of creating another virtual host with the same name.
Replace the release path, URL prefix and (for FPM) the pool port:

```apache
Alias /simbioza "/srv/simbioza/public"
<Directory "/srv/simbioza/public">
    Options -Indexes +FollowSymLinks
    AllowOverride FileInfo Indexes Options
    Require all granted
    DirectoryIndex index.php
    <FilesMatch "\.php$">
        SetHandler "application/x-httpd-php"
    </FilesMatch>
</Directory>
```

Use `--base-url=https://your-host.example/simbioza` for the installer, not
the hostname without its prefix. For a demo at `/demo`, replace **both**
occurrences of the prefix and use `--base-url=https://your-host.example/demo`.
The directory path is a filesystem path; the prefix is a URL path, not the
application directory's name. Keep private `config/` and `data/` outside
the exposed alias. Validate Apache before its graceful reload.

## 6. Prepare optional packages in the CLI

The graphical installer cannot run Composer as the Apache web account. From
the release directory, the code owner prepares optional modules. Without an
explicit list, the command prepares **all** optional modules because the wizard
selects them all by default:

```bash
cd "$SIMBIOZA_APP_ROOT"
php scripts/installation_packages.php prepare
```

For a smaller set, such as Theme, Calendar, and E-mail, prepare only those
modules and deselect the others in the wizard:

```bash
php scripts/installation_packages.php prepare --modules=theme,calendar,email
php scripts/installation_packages.php status
```

To install no optional modules, use
`php scripts/installation_packages.php prepare --modules=`. This temporarily
prepares only Backup for the starter guides; deselect every optional module
in the wizard.

The `status` command shows which packages are actually installed. The wizard
marks a missing package for CLI preparation; reload the page afterwards. Do
not grant the web account write access to `vendor/` or `composer.json` to
work around this boundary.

## 7. Run the graphical installer

Create the single-use installer URL:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

Include the subdirectory in `--base-url` when applicable. Open the token URL
in a private browser window, without sharing or screenshotting it. The wizard
checks requirements and database access, asks for the site identity, at least
one language, time zone, first administrator, and optional modules (all selected
by default), then
shows a review before installation. It imports the selected user-guide
languages.

The wizard creates private files as the web account. Before the first
maintainer CLI command, finalize shared access only to its runtime files;
this also activates inherited Linux ACL masks on files initially created
with mode `0600`. Static PHP service code is not changed:

```bash
cd "$SIMBIOZA_APP_ROOT"
sudo find data -type f -exec chmod 0660 {} +
sudo find config -type f \( -name '*.json' -o -name '*.lock' \) -exec chmod 0660 {} +
for SIMBIOZA_FILE in database.php env.php installation.php modules.php languages.php; do
  if [ -f "config/$SIMBIOZA_FILE" ]; then
    sudo chmod 0660 "config/$SIMBIOZA_FILE"
  fi
done
```

After successful installation, clean up the temporary preparation state.
Backup remains installed when selected in the wizard; otherwise cleanup removes
it only if preparation added it:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Expect zero pending migrations, successful sign-in and home pages, and a
locked `/install` route. Back up the database, `config/`, user files, and
theme data before going live.


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
