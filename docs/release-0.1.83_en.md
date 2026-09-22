# Simbioza 0.1.83

This release introduces the public Simbioza language catalogue. The installer
offers the published languages and requires at least one selection. Croatian
and English remain the built-in choices; the Setup page can install, enable,
disable, remove, and later refresh additional language packs. Existing
installations retain their configured languages after updating. Language-pack
downloads are size-limited, checked against the catalogue's SHA-256 digest,
and validated before activation.

Croatian user-facing text in the application and maintained modules is now
the canonical translation key. Lookups in every installed language are direct
from that key; they do not depend on an English translation chain. Dates and
times shown to users follow the selected locale, while stored values and
machine-readable timestamps are unchanged.

The public catalogue contains Croatian, English, German, French, Spanish, and
Italian packs, each with a multilingual name and SVG flag. The French,
Spanish, and Italian translations were prepared with AI assistance; their
provenance and review limitations are documented in the language repository.
Installer documentation now covers language selection, catalogue updates,
and the delta-extraction workflow for new translation strings.

This release changes no database migrations or business data. It updates
module requirements to tagged, CI-verified releases. After updating the
application through the GUI, open Setup to see the available catalogue
languages and install the ones you need.
