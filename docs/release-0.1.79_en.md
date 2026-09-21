# Simbioza 0.1.79

This corrective release completes the transition of older installations to
the secure dedicated FPM Setup.

FPM finalization now always creates the writable private `data/config`
directory used for persistent module state. Installations upgraded by the
0.1.74 or an older updater therefore no longer fail the **Module state is
writable** check, and GUI module installation and removal become available
without changing the existing enabled-module selection.

The FPM check run by a regular maintainer now probes the restricted Setup
helper directly. It no longer reports an indeterminate state merely because
`/etc/sudoers.d` is intentionally unreadable to regular accounts.

This release contains no migrations or business-data changes.
