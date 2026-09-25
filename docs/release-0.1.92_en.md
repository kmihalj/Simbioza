# Simbioza 0.1.92

This release fixes installation of the optional Accessibility module from the Setup GUI. The module was listed correctly, but the installer used a separate Composer-constraint list that omitted Accessibility and rejected it before Composer ran. GUI and CLI module operations now read every optional module's version constraint from the release manifest. A regression test exercises the Accessibility installation path and checks the complete optional-module catalogue.

The installation documentation has also been reorganized into separate, step-by-step guides for dedicated PHP-FPM and Apache mod_php. The initial graphical installer works in both modes; later GUI package changes and application updates require the dedicated FPM setup. The active GitHub Sponsors profile is now linked from the repository funding configuration and README.

No database migration or manual configuration change is required. Update the application through the supported GUI or CLI updater, then retry **Install** for Accessibility in **Settings → Setup and modules**. Existing languages, module state, theme settings, and authored content remain installation data.
