# Simbioza 0.1.76

This corrective release completes the promised identical CLI update behaviour
on dedicated FPM and non-FPM installations.

On FPM, an ordinary maintainer now runs the same `php update.php` command
without `sudo`. The updater verifies that the global helper explicitly belongs
to that exact installation, submits the strictly allowlisted background update,
and streams its new private log to the terminal until a final status is
available. Release code remains owned by `simbioza-deploy`, and the maintainer
receives no general root shell. On non-FPM installations, the code owner still
runs the same updater directly.

The FPM configurator now refreshes the helper during `--finalize` and grants
the narrow permission to the `deploy-simbioza` group. Existing installations
configured with release 0.1.75 or earlier must repeat that system step only
once; GUI and CLI maintenance then need no `sudo`.

Release retrieval now uses a quiet fetch followed by checkout, so an annotated
Git tag no longer emits a warning that the tag object itself is not a commit.
Regression checks cover exact installation-root binding, the code-owner
decision, and sudoers rules for both GUI and maintainer CLI. A real macOS FPM
installation was additionally updated through the same CLI command without
`sudo`, with 34 executed and zero pending migrations.
