# Simbioza 0.2.7

The optional Accessibility module is upgraded to 0.1.3. Its first-party widget now has theme-aware cards, visible on/off states, an active preference count, and bounded text sizing from 100% to 200%.

New controls include independent line height, paragraph spacing, text weight, heading emphasis, yellow-on-black high contrast, high/low saturation and monochrome filters, a reading guide and mask, and a large cursor. Existing bundled fonts, semantic color palettes, link emphasis, stronger focus indicators, and reduced motion remain available. Every control is translated into Croatian, English, German, French, Spanish, and Italian.

Preferences remain local to the browser and application path, synchronize between open tabs, and preserve earlier settings. The panel supports keyboard navigation and narrow screens. High contrast takes precedence over color filters; unsupported filters and blocked browser storage are explained. No external scripts or font services are loaded. The widget is a personal presentation aid, not a substitute for accessible content or an accessibility-conformance guarantee.

There are no database migrations. Make a restorable backup before updating. Existing installations without the Accessibility module do not have it installed automatically; installed copies are updated with the normal application/module update.
