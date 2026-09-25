# Install Simbioza with a dedicated PHP-FPM pool

[Croatian version](installation_fpm_hr.md) · [Installation overview](installation_en.md)

This is the recommended installation for a new site. The one-time system setup
creates a dedicated PHP process and a restricted deployment helper. The initial
installation, optional package management, language management, and application
updates can then be initiated in the browser. The maintainer can also use the CLI.

**One installation per host with the current setup helper:**
`configure_fpm_setup.php` currently uses fixed system account, helper, service,
and configuration names. Do not run `--install` for a second Simbioza site on a
host where it has already configured one; that would replace the first site's
FPM configuration. This limitation does not affect a single installation.

## 1. Prepare the release and database

Complete [the common prerequisites, release download, and database preparation](installation_en.md#before-choosing-a-php-mode).
Use an ownership-enforcing filesystem. In particular, a macOS external volume
mounted with `noowners` is not suitable for the isolated FPM installation.

For the examples below, replace `/srv/simbioza`, `LOGIN`, the domain, and the
PHP-FPM executable with values for your host. On macOS a suitable application
path is `/Users/Shared/Simbioza/simbioza`, not the Git development checkout.
The website's document root must point to the release's `public/` directory.

## 2. Create the dedicated pool

On Debian, install the PHP-FPM package for the same PHP version used by the CLI
and the `acl` package. From the release directory, run the one-time system step:

```bash
cd /srv/simbioza
sudo php scripts/configure_fpm_setup.php --install \
  --app-root=/srv/simbioza --maintainer=LOGIN
php scripts/configure_fpm_setup.php --check \
  --app-root=/srv/simbioza --maintainer=LOGIN
```

On macOS with Homebrew PHP, use the actual application path and FPM binary:

```bash
cd /Users/Shared/Simbioza/simbioza
sudo php scripts/configure_fpm_setup.php --install \
  --app-root=/Users/Shared/Simbioza/simbioza \
  --maintainer="$USER" --php-fpm="$(brew --prefix)/sbin/php-fpm"
php scripts/configure_fpm_setup.php --check \
  --app-root=/Users/Shared/Simbioza/simbioza \
  --maintainer="$USER" --php-fpm="$(brew --prefix)/sbin/php-fpm"
```

The check is read-only. The default pool listens on `127.0.0.1:9075`; keep
that port local to the server. The tool gives the FPM account write access to
runtime data, not to the whole release, and installs a narrowly restricted
helper for package changes. It also creates the deploy and runtime groups.
Check every diagnostic result before continuing. Do not use `chmod 777`.

## 3. Configure the web server

Choose **one** of these configurations. They serve a dedicated hostname at
`/`; replace the hostname, release path, and TLS certificate settings. For a
subdirectory installation, also configure the URL prefix in the web server
and pass that complete URL to `install:prepare` in step 4.

### Apache with PHP-FPM

Enable Apache `rewrite`, `proxy`, `proxy_fcgi`, and TLS. Direct PHP requests
for this site to the dedicated pool; do not route them through `mod_php`.

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

### Nginx with PHP-FPM

Nginx cannot run `mod_php`; it sends PHP requests to the same dedicated FPM
pool. Keep the `SCRIPT_FILENAME` and `try_files` rules together:

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
    }

    location ~ /\. {
        deny all;
    }
}
```

Test the Nginx configuration before reloading. The FPM listener must not be
reachable from an untrusted network.

## 4. Run the graphical installer

Generate a one-time installer URL from the release directory:

```bash
bin/simbioza install:prepare --base-url=https://simbioza.example.org
```

For a site at `/simbioza`, use
`--base-url=https://simbioza.example.org/simbioza` instead. Open the URL in a
private browser window; do not share or screenshot its token. The wizard checks
requirements and database access, asks for the site identity, languages, time
zone, first administrator, and optional modules, then shows a review before
installation. With the dedicated helper working, selected optional packages
are installed through the GUI. Theme is recommended, but not mandatory.

If you intentionally omit the dedicated helper, prepare the selected optional
packages as the deploy user with
`php scripts/installation_packages.php prepare --modules=theme,calendar` and
additional languages with
`php scripts/installation_languages.php prepare --locales=de,es,fr,it` before
continuing. Match both lists to your selections and reload the installer.
Without the helper, later GUI package and language management is unavailable.

After installation, sign in as the administrator. In **Settings → Setup and
modules**, verify that the dedicated pool, helper, and runtime permissions are
all reported as ready. The wizard imports the selected user-guide languages.

## 5. Finalize permissions and verify

Run the matching one-time command after the wizard has created runtime files:

```bash
sudo php scripts/configure_fpm_setup.php --finalize \
  --app-root=/srv/simbioza --maintainer=LOGIN
php scripts/configure_fpm_setup.php --check \
  --app-root=/srv/simbioza --maintainer=LOGIN
vendor/bin/hph modules migrate-status
composer check-platform-reqs
```

On macOS, add the same `--php-fpm` argument and application path used in step
2. Sign out and back in so the maintainer receives the new group memberships.
Expect zero pending migrations, successful sign-in and home pages, and a locked
`/install` route. Back up the database, `config/`, user files, and theme data.

The one-time `sudo` setup does **not** mean normal updates require root.
See [maintenance commands](installation_en.md#after-installation) for GUI and
CLI module, language, and application updates.

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
