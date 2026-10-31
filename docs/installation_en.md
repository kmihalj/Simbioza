# Choose a Simbioza installation

[Croatian version](installation_hr.md)

These guides install Simbioza **0.3.30**. Email uses **0.3.5**, HTML Editor **0.3.9**, Theme **0.3.6**, Menu/Backup/Task/Comment/Survey **0.3.3**,
Workspace/Notification **0.3.2**, Accessibility/Audit/Auth/API/Calendar/Confluence Import **0.3.1**,
and the other public modules **0.3.0**; Framework
is unchanged. The 0.3.x baseline starts with a new application directory,
configuration and database. An upgrade from 0.2.x or reuse of its Simbioza
backups is not supported.

Choose **one complete guide** below. Each starts at an administrator terminal,
installs/checks the host tools, downloads the release, prepares the database
and permissions, configures the web server, runs the wizard and verifies the
first sign-in. Do not use this overview as an extra installation step.

| Installation | PHP identity | Package preparation and later application updates | Complete guide |
|---|---|---|---|
| Apache mod_php | Shared Apache account | CLI, as the code owner | [Start the mod_php installation](installation_mod_php_en.md) |
| Ordinary PHP-FPM | Shared system web account in an FPM pool | CLI, as the code owner | [Start the ordinary FPM installation](installation_fpm_en.md) |
| Secured PHP-FPM (recommended) | Dedicated FPM and deployment accounts, restricted helper | GUI through the worker, or CLI as a deploy maintainer | [Start the secured FPM installation](installation_fpm_secured_en.md) |

All three have a graphical **initial installer** and configuration GUI.
Ordinary FPM is not the same as secured FPM: adding a FastCGI handler alone
does not create deployment isolation or enable GUI Composer/application updates.
Nginx requires FPM; it cannot run Apache's mod_php.
[Docker installation](docker_en.md) is a separate, optional image-based route.

## Before choosing a PHP mode

The default examples are for a new Linux or macOS host, not an existing
production pool. Have an administrator shell, a domain/TLS certificate for
a public site and a database choice. SQLite needs no database service.
Every chosen guide repeats the exact preparation commands; no existing
Simbioza FPM/deploy account is assumed.

For reinstallation, the system administrator first handles the old site's
processes and data, checks existing identities and selects the correct instance
and port. The guides never delete another installation or recreate a running
site merely because the download directory exists.

Private module configuration uses `HPH_CONFIG_PATH` (default `config/`);
persistent files use `data/`. Only `public/` is exposed by the web server.

## After installation

Set up [log rotation](logging_en.md) for cron, updater and FPM output. The
application's rotating `app.log` handler does not rotate those separate files.

Each complete guide includes its own module, language and update commands,
backup boundaries and final checks. Routine package/code work belongs to the
maintainer/deploy identity, not the web account and not root. Language packs can
be updated independently of an application release.

The application's updater refreshes bundled User guides from the installed
release packages. Reading GitHub `main` does not automatically replace pages
already stored on a site; their content can belong to an older release.
The bundled installation pages are generated from these Markdown sources.
