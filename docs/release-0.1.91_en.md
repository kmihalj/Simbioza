# Simbioza 0.1.91

This maintenance release fixes the component-update display for optional modules. The update checker now honors the version ranges in Simbioza's optional-module catalog and discards incompatible cached results. A historical Theme `v1.0.0` tag is therefore no longer presented as an update to an installation using the current `0.1.x` line. The Theme package itself remains at `0.1.17`; its historical tag is unchanged.

The release also includes Accessibility `0.1.1`, which makes the user's link-emphasis preference override navigation styles that otherwise remove underlines. No database migration or manual configuration change is required. Existing languages, module state, theme settings, and authored content remain installation data. Use the supported GUI or CLI application updater.
