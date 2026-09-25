# Simbioza 0.1.96

An optional module's settings entry now follows the package's actual installation state. The application registers Accessibility settings routes only while the package directory exists. The Menu module 0.1.24 hides persisted entries whose routes no longer exist, including top-level entries. A module can still contribute its settings link when it is installed, without requiring a static application-menu override. Disabling an installed module does not remove its administrative settings route.

This also fixes an installation that retained an Accessibility entry after uninstalling the package. **Do not delete the saved module-data archive or manually edit the settings menu.** Update Simbioza first. The stale link disappears after the new code is loaded; reinstalling Accessibility adds it back through its own menu contribution. Existing administrator menu labels and ordering are preserved.

The release requires Menu 0.1.24 and keeps Accessibility optional. There are no database migrations or additional installation steps. The same behavior applies to FPM and mod_php installations.
