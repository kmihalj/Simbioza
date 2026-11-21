# Simbioza 0.3.48 — large Workspace HTML exports

> Historical release note. See the [current release](release-0.3.55_en.md).

Large Workspace HTML downloads could stop partway through when PHP reached its
execution time limit. Workspace **0.3.8** gives authorized exports from the
form and API at least 900 seconds of PHP execution where the web runtime permits
it. This covers preparing and sending the ZIP.

The [troubleshooting guide](troubleshooting_en.md) explains which PHP, web
server and proxy settings to check and how to verify the downloaded archive.

Use the GUI updater on a correctly configured secured PHP-FPM installation or
the documented CLI procedure on other classic installations. Demo has its own
upgrade procedure. This release introduces no database migration.

See [installation](installation_en.md), [Docker deployment](docker_en.md)
and [release history](releases_en.md). The collection of 23 themes is available
in the [theme package](https://github.com/kmihalj/Simbioza/releases/download/0.3.48/Simbioza-0.3.48-themes.zip).
