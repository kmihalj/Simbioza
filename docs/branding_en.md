# Simbioza brand identity

Simbioza is the product name of the collaborative knowledge application powered
by HeartPhrame.

## Message

- Name: **Simbioza**
- Signature: **Simbioza by HeartPhrame**
- Slogan: **Knowledge that lives together.**
- Description: **A shared space for knowledge, collaboration, and content that
  grows with your community.**

## Mark and hero artwork

The mark combines a hermit crab and a sea anemone as a three-colour
shadow-theatre illustration. The broad anemone foot is attached to the shell,
and the shell spiral ends in a small heart. The active Natural Dark palette uses
coral `#FF8064` for the anemone, gold `#E9B84A` for the shell, and seafoam
`#72D4C8` for the crab.

The complete brand package lives in `data/themes/simbioza/`, outside the public
web root. Its `assets/` directory contains six complete transparent 1600 x 1600
hero PNG files, six matching vector SVG files, and the same six variants as
512 x 512 PNG and SVG application icons. No file is cropped from a contact
sheet. The reproducible geometric master is kept under `source/`, and
`theme-assets.json` provides bilingual labels, roles, dimensions, sizes, and
SHA-256 checksums. Regenerate the entire set with:

```bash
php scripts/generate_simbioza_brand_assets.php
```

The active `simbioza` theme selects `hero-natural-dark.png` and
`icon-natural-light.png` through managed `@theme-assets/...` references. The
application serves only requested library files through the Theme module route;
it does not duplicate the theme library below `public` or `vendor`.

`Export theme package` includes only the artwork and icon selected by the theme.
`Export complete theme` includes the full `data/themes/simbioza` directory,
including unused variants and source material, so a later import restores the
whole editable library.
