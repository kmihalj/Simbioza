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

## Bundled partner themes

In addition to the Simbioza, Srce SUP, and Standard themes, the application
ships the `dabar` and `aai` themes. Both use Theme module capabilities: two
header logos, a decorative hero SVG, standalone navigation, and separate light
and dark palettes. The Theme module supports independent artwork width and
maximum height, offsets, and controlled extension below the hero boundary.

The Dabar package lives in `data/themes/dabar/`. It places the official Dabar
logo on the left, the Srce 55 logo on the right, and the magnifying-glass
illustration in the hero area. Its light hero follows the original red gradient
from `#D71635` to `#A01F23`; the dark variant uses a deeper red palette and
specially adapted logos. The source SVG files were obtained from
`https://dabar.srce.hr/`.

The AAI package lives in `data/themes/aai/`. It places the official AAI@EduHr
logo on the left, the Srce logo on the right, and the AAI banner in the hero
area. Its light hero follows the original `#003567` - `#1F5EA0` - `#1F8CA0`
gradient; the dark variant uses a deeper blue palette and specially adapted
logos. The source SVG files were obtained from `https://www.aaiedu.hr/` and
`https://www.srce.unizg.hr/`.

Both partner themes inherit the Simbioza content widths, wave shape, and content
overlap. Their hero artwork uses an explicit maximum height and vertical offset
so that the larger illustration remains visible over the lower part of the wave
without changing the content width.

Each directory includes a `theme-assets.json` manifest with dimensions, sizes,
and SHA-256 checksums. `Export theme package` includes the saved artwork size,
offsets, and overflow rule in the standalone package. `Export complete theme`
and its import preserve those settings and every hero and logo asset for both
colour variants. A full-site backup also captures the complete
`resources/config/theme` and `data/themes` directories, so restore returns the
same values and files.
