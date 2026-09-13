# Simbioza 0.1.69

Includes every improvement from 0.1.68: meeting planning, local administrator
recovery for Administrator-group members, page backup/import/transfers,
the current Simbioza theme, and bilingual guides with 72 light-theme screenshots.
The theme and meeting guide are bundled both for installation and existing-site updates.

Final upgrade verification found that a Composer cache owned by a different
Unix user can stop an update run with `sudo`. The updater now uses a separate
private cache within its temporary process directory for every Composer step,
including rollback. Existing caches, Git ownership checks, and global settings
remain unchanged. Environment settings are restored even on failure, and the
private cache is removed when the updater finishes.

If an older, already loaded updater fails on another Unix user's cache, run the
current `update.php` or explicitly set `COMPOSER_CACHE_DIR` to a new private
directory. Do not disable Git ownership checks.

Apps-test is not updated automatically. Its administrator runs:

```bash
cd /data/www/simbioza && sudo php update.php
```
