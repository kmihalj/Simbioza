# Install Simbioza with ordinary PHP-FPM

[Croatian version](installation_fpm_hr.md)

This complete guide starts at a normal administrator terminal with no existing
Simbioza installation. It includes every step through the first administrator
sign-in; no other installation guide is required. PHP-FPM runs through the
system's shared web account. Optional package preparation and application
updates are CLI-only; this mode does not install the secured deployment helper.
The initial graphical installer, configuration GUI and language GUI still work.

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

## 2. Download a clean Simbioza 0.3.5 release

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
git clone --depth 1 --branch 0.3.5 https://github.com/kmihalj/Simbioza.git "$SIMBIOZA_FETCH_DIR/source" &&
rsync -a --exclude=.git/ "$SIMBIOZA_FETCH_DIR/source/" "$SIMBIOZA_APP_ROOT/" &&
cd "$SIMBIOZA_APP_ROOT" &&
head -n 1 VERSION &&
composer update --no-interaction --prefer-dist --with-all-dependencies --optimize-autoloader &&
composer check-platform-reqs &&
mkdir -p data/cache data/logs data/sessions data/setup-requests data/tmp
```

Expect `0.3.5` and `success` for every platform requirement.
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
Debian/Ubuntu, `_www` for the Homebrew PHP-FPM example. Confirm your actual
FPM worker user/group before applying these commands. The code remains owned by
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

## 5. Configure an ordinary PHP-FPM pool

This mode uses the web account installed by the operating system, not a
dedicated Simbioza deployment identity. The maintainer owns code, and the
shared PHP account has only the narrow runtime access from step 4.
Do not run `configure_fpm_setup.php --install` for this mode: that selects
the separate secured architecture.

On Debian/Ubuntu, create a pool file for the installed PHP series:

```bash
SIMBIOZA_PHP_SERIES="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit "/etc/php/$SIMBIOZA_PHP_SERIES/fpm/pool.d/simbioza.conf"
```

On macOS, create a pool file under Homebrew's configuration directory:

```bash
SIMBIOZA_PHP_SERIES="$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')"
sudoedit "/opt/homebrew/etc/php/$SIMBIOZA_PHP_SERIES/php-fpm.d/simbioza.conf"
```

Paste the **complete pool** below. Use `www-data` on Debian/Ubuntu and
`_www` on macOS. Replace both `/srv/simbioza` paths with your actual
application root; PHP-FPM does not expand terminal variables here.

```ini
[simbioza]
user = www-data
group = www-data
listen = 127.0.0.1:9077
listen.allowed_clients = 127.0.0.1
pm = ondemand
pm.max_children = 4
pm.process_idle_timeout = 10s
pm.max_requests = 500
clear_env = yes
security.limit_extensions = .php
env[TMPDIR] = /srv/simbioza/data/tmp
php_admin_value[session.save_path] = /srv/simbioza/data/sessions
php_admin_value[max_execution_time] = 900
php_admin_flag[display_errors] = off
```

Verify and start the installed FPM service. On Debian/Ubuntu:

```bash
sudo "php-fpm$SIMBIOZA_PHP_SERIES" -tt
sudo systemctl enable --now "php$SIMBIOZA_PHP_SERIES-fpm"
sudo systemctl reload "php$SIMBIOZA_PHP_SERIES-fpm"
```

On macOS:

```bash
sudo /opt/homebrew/sbin/php-fpm -tt
sudo /opt/homebrew/bin/brew services start php
```

Expect a successful configuration test. The FPM master must be started by
the system/root so it can run workers as `_www`; a user-owned Homebrew
service cannot switch identities to an unrelated web account.
The listener must remain loopback-only. For another site, choose a different
pool name and port rather than reusing this one.

## 6. Configure the web server

Choose **one** of these configurations. They serve a dedicated hostname at
`/`; replace the hostname, release path, and TLS certificate settings. For a
subdirectory installation, also configure the URL prefix in the web server
and pass that complete URL to `install:prepare` in step 8.

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
            SetHandler "proxy:fcgi://127.0.0.1:9077"
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
        fastcgi_pass 127.0.0.1:9077;
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
The ordinary pool shown above also sets PHP's
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
        SetHandler "proxy:fcgi://127.0.0.1:9077"
    </FilesMatch>
</Directory>
```

Use `--base-url=https://your-host.example/simbioza` for the installer, not
the hostname without its prefix. For a demo at `/demo`, replace **both**
occurrences of the prefix and use `--base-url=https://your-host.example/demo`.
The directory path is a filesystem path; the prefix is a URL path, not the
application directory's name. Keep private `config/` and `data/` outside
the exposed alias. Validate Apache before its graceful reload.

## 7. Prepare optional packages in the CLI

The graphical installer cannot run Composer as the shared FPM web account. From
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

## 8. Run the graphical installer

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
