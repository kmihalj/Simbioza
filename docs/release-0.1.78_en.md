# Simbioza 0.1.78

This corrective release repairs GUI Setup application updates on Linux
installations that use the dedicated FPM configuration.

The transient systemd unit now tracks the Setup worker's complete process
group. The short web request can therefore finish immediately while the
restricted `simbioza-deploy` updater remains alive, supervised, and isolated
until completion. The previous helper stopped the background process as soon
as the worker returned its initial acknowledgement, leaving the interface at
**The update is queued** with an empty private log.

A stale status whose PID no longer exists is now reported as a failed update
and no longer locks out a retry. This release adds no migrations or business
data changes.

Installations whose FPM setup came from release 0.1.77 or earlier must run
`configure_fpm_setup.php --finalize` once after the CLI update. This refreshes
the root-owned helper; subsequent GUI and CLI updates again work without
`sudo`.
