# Simbioza 0.1.80

This corrective release hardens GUI and CLI updates on dedicated FPM
installations.

After Composer installation, the updater now ensures that the new `vendor`
tree is readable by the FPM process without changing ownership, groups, or
write permissions. The restricted deploy helper's `umask 0007` can therefore
no longer cause an HTTP 500 while loading `vendor/autoload.php` after a
successful update. The same rule is applied during automatic rollback.

The GUI no longer reports an active background update as failed merely because
Linux process protection hides the deploy PID from the FPM user. Process
existence is checked with a safe POSIX signal probe without sending a signal.

This release contains no migrations or business-data changes.
