# Simbioza 0.1.95

This release makes optional module settings entries reliable. Menu 0.1.23 now
accepts contributions before or after its own bootstrap, adds only missing
entries, and preserves administrator labels, ordering, and enabled states.
Accessibility 0.1.2 uses this optional integration when the host provides an
accessibility settings route. It still works without Menu or Theme.

The graphical installer reports language preparation failures separately from
package failures. A deploy user can prepare published languages before using
the wizard on installations without a Setup helper. Language catalogue cache
replacement and existing configuration writes work across the dedicated FPM
and deployment identities. Theme storage permissions are checked before
database migrations begin.

For an installation where Accessibility has been removed, update Simbioza to
0.1.95 and then install Accessibility again from Setup. No deletion of the
module's saved data archive is required. The Menu module and Accessibility
module must resolve to at least 0.1.23 and 0.1.2, respectively.
