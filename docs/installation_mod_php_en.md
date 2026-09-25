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
the release directory, the code owner prepares exactly the optional modules
that will be offered in the wizard. With no explicit list, this prepares the
recommended Theme:

```bash
cd /srv/simbioza
php scripts/installation_packages.php prepare
```

For a different set, for example Theme, Calendar, and E-mail:

```bash
php scripts/installation_packages.php prepare --modules=theme,calendar,email
php scripts/installation_packages.php status
```

The wizard will show which packages are ready. A missing package must be
prepared in the CLI before it can be selected, then the wizard reloaded. Do
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
one language, time zone, first administrator, and optional modules, then
shows a review before installation. It imports the selected user-guide
languages.

After successful installation, remove only the temporary Backup package used
by the guide importer; an explicitly selected Backup module stays installed:

```bash
php scripts/installation_packages.php cleanup
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

Expect zero pending migrations, successful sign-in and home pages, and a
locked `/install` route. Back up the database, `config/`, user files, and
theme data before going live.

## 5. Maintain this installation

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
