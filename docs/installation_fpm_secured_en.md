# Install Simbioza with secured PHP-FPM

[Croatian version](installation_fpm_secured_hr.md)

This complete guide starts from an empty application directory and assumes no
Simbioza FPM/deploy accounts exist yet. You do not need another installation
guide. The setup tool creates dedicated accounts, groups, a pool/service and a
restricted helper: the web process cannot write code, but it can request package
operations from the separate deployment worker. This is the recommended mode.
For reinstallation, an administrator must first inspect existing identities,
instance names and services; never create a second site under the same identity.

**SimpleSAMLphp is optional.** Local Simbioza login, OIDC, CAS and OAuth2 do not
require it. Complete steps 1–7 first. Follow the optional SAML walkthrough only
if this particular installation needs SAML authentication.

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

The 0.3.4 generator sets launchd `Umask` to integer **7** (octal `0007`),
matching Linux's `UMask=0007`. Both the web process and the restricted deployment
worker use this mask; the worker sets it **after** switching accounts. Their
`TMPDIR` is this application's `data/tmp`, so shared temporary locks stay in
the same private, runtime-group directory. New ordinary log files are `0660`,
not FPM-owned `0644` files which the updater cannot append to.

On macOS, verify the generated service before opening the wizard:

```bash
/usr/libexec/PlistBuddy -c 'Print :Umask' /Library/LaunchDaemons/hr.simbioza.php-fpm.plist | grep -qx 7
```

For a named instance, use its matching service file, for example
`hr.simbioza-second.php-fpm.plist`. A missing mask is a failed prerequisite,
not a reason to make code writable or run the updater as root. Do not rerun
generic finalization on an existing DEMO reset snapshot to repair permissions.
Session files remain private (`0600`); do not recursively make all data writable.

The check is read-only. The default pool listens on `127.0.0.1:9075`; keep
that port local to the server. The tool gives the FPM account write access to
runtime data, not to the whole release, and installs a narrowly restricted
helper for package changes. It also creates the deploy and runtime groups.
Check every diagnostic result before continuing. Do not use `chmod 777`.

### Accounts, groups and permissions created by the tool

The `--install` command above includes account/group creation; do not separately
run `useradd` or `groupadd`. Missing identities are created, while existing
identities with these names are reused without replacing their UID/GID.
The tool ensures the required primary groups and supplementary memberships.
For a named instance, every name below receives the same `-instance` suffix.

| Identity | Purpose |
| --- | --- |
| `fpm-simbioza` / `app-simbioza` | Locked web-service account and primary group; cannot write application code. |
| `simbioza-deploy` / `deploy-simbioza` | Locked deployment account and code-maintenance group; your maintainer account also joins this group. |
| `run-simbioza` | Shared private runtime group for FPM, deployment and the maintainer. |

On Linux, inspect the result after reconnecting to SSH:

```bash
getent passwd fpm-simbioza simbioza-deploy &&
getent group app-simbioza deploy-simbioza run-simbioza &&
id "$(id -un)" &&
getfacl -p "$SIMBIOZA_APP_ROOT/config" "$SIMBIOZA_APP_ROOT/data"
```

Code uses deployment ownership and a read-only FPM sandbox; private runtime
directories use the `run-simbioza` group (`2770`), and the configuration root
uses a sticky directory (`3770`) for controlled atomic updates. Normal runtime
sharing uses these group permissions, not broad ACL write grants. With optional
private SimpleSAMLphp configuration, the tool additionally sets Linux access and
default ACLs giving `app-simbioza` **read/traverse only** on that configuration.
The SAML walkthrough below includes setup and ACL verification.
Do not apply the ordinary/shared-FPM `www-data` write-ACL recipe to secured FPM.

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

**Skip this entire section unless you need SAML.** It is unrelated to configuring
OIDC, CAS, OAuth2 or local login. Keep the local administrator and break-glass
login available until SAML works. A public demo does not need SAML merely
because its User guides contain this section.

### S1. Select the library and public addresses

Finish the normal installation first. In your maintainer shell, set the actual
paths and URLs. Example for a dedicated hostname:

```bash
SIMBIOZA_APP_ROOT=/srv/simbioza
SIMBIOZA_SAML_ROOT=/opt/simplesamlphp
SIMBIOZA_SAML_URL=https://simbioza.example.org/simplesaml/
SIMBIOZA_SAML_CONFIG="$SIMBIOZA_APP_ROOT/data/saml/config"
```

For a site at `https://your-host.example/simbioza`, use
`SIMBIOZA_SAML_URL=https://your-host.example/simbioza/simplesaml/` instead.
Keep the final slash. The library root is **not** Simbioza's directory.
An existing system-managed library may be reused, for example
`/usr/share/simplesamlphp-aai`; do not modify its shared configuration,
certificates or another application's SP.

If a compatible library is already installed, skip the download block. Otherwise
this example installs upstream **2.5.3.1 full**, with its published checksum.
It requires PHP **8.3 or newer** and the extensions from the
[SimpleSAMLphp requirements](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html).
Check [upstream releases](https://simplesamlphp.org/download/) for security updates;
when choosing another version, change both URL and checksum together.
Run the following only for a **new**, nonexistent library directory:

```bash
test ! -e "$SIMBIOZA_SAML_ROOT" && test ! -L "$SIMBIOZA_SAML_ROOT" &&
SIMBIOZA_SAML_FETCH="$(mktemp -d)" &&
curl --fail --location --proto '=https' --tlsv1.2 \
  --output "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  https://github.com/simplesamlphp/simplesamlphp/releases/download/v2.5.3.1/simplesamlphp-2.5.3.1-full.tar.gz &&
test "$(openssl dgst -sha256 "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" | awk '{print $NF}')" = \
  b199ebcba6e30220e7b5df2783ca42f8947dd9bdf046e9e6302fef3d3a438d01 &&
sudo install -d -m 0755 "$SIMBIOZA_SAML_ROOT" &&
sudo tar -xzf "$SIMBIOZA_SAML_FETCH/simplesamlphp.tar.gz" \
  --strip-components=1 -C "$SIMBIOZA_SAML_ROOT"
```

Stop if any command fails. Verify the selected library:

```bash
test -f "$SIMBIOZA_SAML_ROOT/vendor/autoload.php" &&
test -f "$SIMBIOZA_SAML_ROOT/public/module.php" &&
php -r 'require $argv[1]; exit(class_exists("SimpleSAML\\Auth\\Simple") ? 0 : 1);' \
  "$SIMBIOZA_SAML_ROOT/vendor/autoload.php"
```

### S2. Prepare private configuration and trusted metadata

From `$SIMBIOZA_APP_ROOT`, create private directories and copy **templates**.
This block refuses to overwrite configuration. When restoring preserved settings
for the **same SP**, restore its `config`, certificates and required metadata
instead; do not copy another site's credentials or restore sessions/cache/the
former application database.

```bash
mkdir -p "$SIMBIOZA_APP_ROOT/data/saml/config" \
  "$SIMBIOZA_APP_ROOT/data/saml/metadata" "$SIMBIOZA_APP_ROOT/data/saml/cert" \
  "$SIMBIOZA_APP_ROOT/data/saml/data" "$SIMBIOZA_APP_ROOT/data/saml/cache" \
  "$SIMBIOZA_APP_ROOT/data/saml/logs" "$SIMBIOZA_APP_ROOT/data/saml/tmp" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/config.php" &&
test ! -e "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/config.php.dist" "$SIMBIOZA_SAML_CONFIG/config.php" &&
cp "$SIMBIOZA_SAML_ROOT/config/authsources.php.dist" "$SIMBIOZA_SAML_CONFIG/authsources.php"
```

Edit `config.php` with `sudoedit "$SIMBIOZA_SAML_CONFIG/config.php"`. Set these
keys inside its existing `$config` array. Replace example filesystem paths with
your application path; PHP files do not expand terminal variables.

| Key | Value or action |
| --- | --- |
| `baseurlpath` | The full HTTPS `SIMBIOZA_SAML_URL` value, including the final slash. |
| `certdir`, `metadatadir` | `/srv/simbioza/data/saml/cert` and `/srv/simbioza/data/saml/metadata`. |
| `datadir`, `cachedir`, `loggingdir`, `tempdir` | The corresponding private `data`, `cache`, `logs`, `tmp` directories under `/srv/simbioza/data/saml`. |
| `metadata.sources` | A trusted source, e.g. `[['type' => 'flatfile']]` using private `metadatadir`; use your federation's verified mechanism when applicable. |
| `secretsalt`, `assets['salt']` | Two different random values, each generated with `openssl rand -hex 32`; edit `salt` inside the existing `assets` array, not a new top-level `assets.salt` key. Never use template values or publish them. |
| `auth.adminpassword` | A hash generated interactively with `php "$SIMBIOZA_SAML_ROOT/bin/pwgen.php"`; separate from your Simbioza password. |
| `store.type` | `phpsession` for this walkthrough. |
| `session.phpsession.cookiename` | A site-specific name, e.g. `SimbiozaSAML`, different from Simbioza's and other sites' session cookies. |
| `session.phpsession.savepath` | `null`, so both endpoints use this pool's private `data/sessions`. |
| `session.cookie.path` | `/` for a dedicated hostname or `/simbioza/` for that prefix; must cover both application and SAML endpoint. |
| `session.cookie.secure`, `session.phpsession.httponly` | `true`; use HTTPS. |
| `showerrors` | `false` outside controlled troubleshooting. |

In `authsources.php`, configure `default-sp` as `saml:SP` with your own stable
`entityID` and the trusted IdP's `idp` entity ID. Remove unused example sources.
Obtain IdP metadata from your federation/IdP administrator; save trusted PHP
metadata as `data/saml/metadata/saml20-idp-remote.php`, or configure the
federation's supported metadata mechanism. Do not disable signature checks or
trust an unverified signing certificate.

If the IdP requires an SP certificate, generate a private key/certificate for a
**new** SP in this site's `data/saml/cert` and reference the filenames through
`privatekey` and `certificate` in `default-sp`, following federation policy.
For the **same registered SP**, retain its EntityID, existing keys/certificates
and relevant metadata rather than generating replacements.

### S3. Configure the dedicated pool and verify ACLs

From `$SIMBIOZA_APP_ROOT`, rerun the generator for the **same application and
instance**, adding the private configuration argument. This updates and restarts
only that dedicated pool, reusing its existing accounts/groups:

```bash
cd "$SIMBIOZA_APP_ROOT" &&
sudo php scripts/configure_fpm_setup.php --install \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG" &&
php scripts/configure_fpm_setup.php --check \
  --app-root="$SIMBIOZA_APP_ROOT" --maintainer="$(id -un)" \
  --simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG"
```

On macOS add `--php-fpm="$(brew --prefix)/sbin/php-fpm"` to **all three**
commands. For a named instance repeat the original `--instance` and `--listen`
options too. This is not a DEMO reset-baseline repair procedure.
The generator exports `SIMPLESAMLPHP_CONFIG_DIR` inside the FPM worker despite
`clear_env = yes`; an SSH `export` or Apache `SetEnv` alone is not sufficient.

On Linux, verify read access and private configuration ACLs:

```bash
getfacl -p "$SIMBIOZA_SAML_CONFIG" "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/config.php" &&
sudo -u fpm-simbioza test -r "$SIMBIOZA_SAML_CONFIG/authsources.php" &&
sudo -u fpm-simbioza test ! -w "$SIMBIOZA_SAML_CONFIG/config.php"
```

Expect `group:app-simbioza:r-x` and a matching default ACL on the directory;
files are readable, not writable, by FPM. Deployment retains ownership.
Use suffixed identities for a named instance. On macOS the generator uses group
ownership instead of Linux POSIX ACL commands.

### S4. Send the SAML endpoint to the same FPM pool

Add this block to the **existing HTTPS vhost**, not a second vhost or a shared
global SimpleSAMLphp alias. Replace URL prefix and filesystem paths with S1's
values; Apache does not expand shell variables. A longer SAML alias must appear
**before** a general `/simbioza` application alias.

```apache
Alias /simplesaml "/opt/simplesamlphp/public"
<Directory "/opt/simplesamlphp/public">
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    AcceptPathInfo On
</Directory>
ProxyPassMatch "^/simplesaml/(.+\.php(?:/.*)?)$" "fcgi://127.0.0.1:9075/opt/simplesamlphp/public/$1"
```

For `/simbioza`, use `Alias /simbioza/simplesaml` and regex
`^/simbioza/simplesaml/(.+\.php(?:/.*)?)$`. For a system package, replace
**both** `/opt/simplesamlphp/public` paths with its actual `public/` directory.
Use your pool's actual port. The explicit proxy preserves `module.php/...`
PATH_INFO and uses the application's FPM identity, environment and session
directory. Its ordinary PHP handler does not cover this separate alias.

On Debian/Ubuntu validate, then gracefully reload Apache:

```bash
sudo apache2ctl configtest && sudo systemctl reload apache2
```

On Homebrew Apache use `sudo /opt/homebrew/bin/httpd -t` and, only on success,
`sudo /opt/homebrew/bin/httpd -k graceful`. With Puppet/Ansible, change the
authoritative managed vhost rather than a generated file. For Nginx, use the
[official alias/PATH_INFO configuration](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#configuring-nginx)
with this same dedicated pool and URL prefix, not a shared default pool.
Never expose `config/`, `cert/` or private metadata through the web alias.

### S5. Register the SP, map attributes and test authentication

Open the SAML base URL and obtain `default-sp` metadata through the SimpleSAMLphp
administrator interface. Send it to the IdP/federation administrator. Verify
the registered **EntityID, ACS and SLO** use this site's HTTPS SAML prefix and
wait until registration is active.

In Simbioza **Settings → Authentication**, enable the SimpleSAMLphp protocol.
Create a SAML profile with the library's absolute `vendor/autoload.php` path
and `auth_source = default-sp`. Use **Test profile** to capture actual IdP
attributes; choose a stable subject/login attribute and map email/name fields.
Do not guess attribute names from a different provider. Test again until the
profile is operational, enable it, and check that the intended users may use it.
Account auto-creation is an explicit profile choice.

The GUI writes Simbioza's settings to `HPH_CONFIG_PATH/auth/auth.json`; it
does not edit external SimpleSAMLphp files. For manual Auth configuration,
start with the Auth module's `auth.json.dist`, never by replacing a live file.

Test in a private browser: SAML login → return to Simbioza → protected page →
logout → another login. Metadata loading alone does not prove authentication.
With `phpsession`, a different pool/session directory can cause login loops:
correct routing/environment instead of granting wider permissions. Retain
local administrator access until the complete browser flow succeeds.

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

