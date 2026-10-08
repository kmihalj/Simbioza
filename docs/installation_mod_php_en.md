# Install Simbioza with Apache mod_php

[Croatian version](installation_mod_php_hr.md) · [Installation overview](installation_en.md)

This route is for an Apache server that already uses a compatible `mod_php`
module. It is **not** an Nginx configuration: Nginx requires a FastCGI PHP
process such as PHP-FPM. For a new deployment, prefer the
[dedicated FPM route](installation_fpm_en.md). The initial web installer works
here too, but the web process cannot install Composer packages or update the
application. Prepare optional packages in the CLI first, and run later package
operations and application updates as the installation owner in the CLI.

## 1. Prepare the release and database

Complete [the common prerequisites, release download, and database preparation](installation_en.md#before-choosing-a-php-mode).
Use a dedicated Unix account to own the release and run Composer and the CLI
updater. The Apache/PHP process must be able to read the code and write only
the required runtime paths: `data/`, dynamic settings in `config/`,
`resources/config/menu/`, and `resources/config/theme/`. Arrange ownership or
ACLs for these exact paths on your system; do not make the whole release
web-writable and do not use `chmod 777`. The document root is `public/`, never
the release directory.

On macOS, do this **before** opening the wizard. Replace `_www` if Apache runs
under another group. The maintainer's inherited ACL is important: the wizard
creates private `0600` files which must remain readable to later CLI updates.

```bash
cd /srv/simbioza
MAINTAINER="$(id -un)"
sudo chgrp -R _www config data resources/config/menu resources/config/theme
chmod 3770 config
chmod 2770 resources/config/menu resources/config/theme
chmod -R g+rwX data resources/config/menu resources/config/theme
sudo chmod +a "user:${MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" config
sudo find data resources/config/menu resources/config/theme -type d -exec chmod +a "user:${MAINTAINER} allow read,write,append,execute,delete,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit" {} +
```

Use the actual release path rather than `/srv/simbioza` on macOS. On Linux,
configure the equivalent narrow write and default ACLs with `setfacl`, and
verify the maintainer can read each generated private file after installation;
POSIX ACL masks can restrict access when a file is explicitly created `0600`.
Do not change its public mode to fix that. The installer and CLI must both
retain access to the runtime paths, while `vendor/` remains web-read-only.

## 2. Confirm that Apache actually runs mod_php

The CLI PHP version alone is not proof that Apache uses the same PHP. Inspect
the active Apache modules and handler; verify that PHP in a browser uses a
supported version and the required extensions. The usual non-thread-safe
`mod_php` build requires Apache's `prefork` MPM. Do not enable both the FPM
proxy handler and the `mod_php` handler for this site.

Configure Apache `rewrite` and TLS, then use a virtual host like this:

```apache
<VirtualHost *:443>
    ServerName simbioza.example.org
    DocumentRoot /srv/simbioza/public

    <Directory /srv/simbioza/public>
        Options -Indexes +FollowSymLinks
        AllowOverride FileInfo Options
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
use that full base URL in step 4.

## 3. Prepare optional packages in the CLI

The graphical installer cannot run Composer as the Apache web account. From
the release directory, the code owner prepares optional modules. Without an
explicit list, the command prepares **all** optional modules because the wizard
selects them all by default:

```bash
cd /srv/simbioza
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

## 4. Run the graphical installer

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

## 5. Maintain this installation

Versions older than 0.2.12 must first run `php update.php --tag=0.2.12`
as the code owner. Do not skip this required updater bridge.

In **Settings → Setup and modules**, an administrator may enable or disable
modules already installed. Package add/remove and application updates must
be run by the Unix code owner, not by Apache:

```bash
vendor/bin/hph modules add calendar --fresh
vendor/bin/hph modules disable calendar
vendor/bin/hph modules enable calendar
php update.php --check
php update.php
```

See [the common maintenance reference](installation_en.md#after-installation)
for removal/restore commands, language commands, updater safeguards, and
verification. Routine CLI work should not be performed as `root`.
