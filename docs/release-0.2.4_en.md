# Simbioza 0.2.4

The isolated PHP-FPM pool now explicitly allows up to 900 seconds for the
initial installer and module operations. On the tested macOS/PHP 8.5 build,
the previous `max_execution_time = 0` still terminated a request after about
60 seconds: the browser could show 503 although the restricted Setup worker
finished successfully in the background. The new pool was verified to return
a normal response after a 65-second request.

Updating application files alone does not change an existing isolated FPM
system pool. After upgrading, rerun `scripts/configure_fpm_setup.php --install`
with the same instance name, application root, and port, then check the pool
and service. Do not reuse the same instance name for another application
directory. Ordinary FPM and Apache mod_php are unaffected.
On an already installed site, rerun `--finalize` with the same arguments
afterwards to restore final application-file ownership.

There are no database migrations. Take a restorable backup before upgrading.
See the [FPM installation guide](installation_fpm_en.md) for the full steps.
