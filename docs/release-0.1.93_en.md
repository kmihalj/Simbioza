# Simbioza 0.1.93

This maintenance release fixes the permissions of optional Composer packages installed or removed through the Setup GUI and CLI. The restricted FPM helper runs with a private umask; previously, a newly installed package could be marked enabled while its code was unreadable to the web process. After each successful package operation, Simbioza now makes vendor code readable and directories traversable without widening write permissions or exposing Git metadata. A regression test reproduces the restrictive permissions and verifies the result.

The Setup module table also wraps long status labels inside their own column, so the “removed, data backup available” label is no longer obscured by action buttons.

No database migration is required. On an installation where Accessibility was removed after the failed attempt, update Simbioza to 0.1.93 first, then choose **Restore from backup** for Accessibility if its saved module data should be retained, or **New installation** otherwise. Verify that **Settings → Accessibility** and the widget appear after installation.
