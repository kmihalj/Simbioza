# Simbioza 0.1.98

This corrective release makes both application-file synchronization and its
rollback compatible with writable directories owned by a dedicated PHP-FPM
account. The updater no longer attempts to change those directories' modification
times as the separate deployment account. It continues to preserve ownership,
permissions, private configuration, and application data.

The release also contains the concurrent-editing and browser-language changes
from 0.1.97. There are no database migrations. The ordinary GUI or CLI update
is sufficient where the currently installed updater can write release files.
The fix takes effect only after this version's `update.php` has been installed:
an older CLI updater that has already failed on a directory `utimensat` error
may need a one-time ownership correction of that directory by the server
administrator before retrying. First verify that the application is serving
normally and has left maintenance mode. Do not assume that a failed rollback
has fully restored all files without checking the installation.
