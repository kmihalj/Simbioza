# Simbioza theme collection

[Hrvatska verzija](theme-collection_hr.md)

The collection contains **12 independent themes**, each with light and dark variants. The themes use native Theme settings: colours, system fonts, navigation, hero, cards, borders and shadows. All settings remain editable in the interface.

| Theme | Style and purpose |
| --- | --- |
| Aurora | Northern lights, cool blues and airy cards. For contemporary portals and team knowledge. |
| Folio | Warm newsprint, wine-red accents and serif typography. For magazines, stories and personal blogs. |
| Neon Arcade | Neon cyan, violet and a geometric gamepad. For gaming communities, tournaments and digital clubs. |
| Terminal 84 | Monospace, phosphor green and crisp square borders. A retro computing style for technical teams. |
| Cassette Sunset | Sunsets, cassette tapes and rounded seventies lines. For music, creative communities and nostalgic projects. |
| Botanika | Sage, forest green and illustrated leaves. A calm space for education, sustainability and communities. |
| Jadran | Adriatic blue, limestone and sails. A fresh theme for associations, travel and public portals. |
| Blueprint | Technical drawings, grid lines and indigo. A precise theme for documentation, engineering and research projects. |
| Sakura | Blush paper, plum and cherry blossoms. A gentle theme for personal notes, workshops and creative blogs. |
| Pop Studio | Cobalt, yellow and playful geometric shapes. A graphic theme for design, events and creative collectives. |
| Espresso | Coffee, copper and long-form serif text. An editorial theme for journals, essays and expert articles. |
| Nocturne | Midnight blue, lunar orbits and gold details. An elegant theme for cultural institutions and special projects. |

## Selecting a theme on HFC

Open **Settings → Theme**, choose its name in **Active theme**, and save the settings. Select automatic, light or dark mode. To edit a theme, select it in the editor; existing themes remain independent. The collection does not change the active default theme.

## Importing into another installation

Each ZIP in `resources/installation/theme/simbioza-*.zip` is a complete export of one theme. Under **Settings → Theme**, open **Import complete theme**, select the appropriate ZIP and import it. Then select it as the active theme. Do not upload the entire collection ZIP into the single-theme form; extract an individual package first.

Packages include both palettes, layout and all four associated image files with checksums. The themes load no remote fonts, scripts or images. The Simbioza mark remains the original application mark; decorative vector illustrations were created specifically for this collection.

## Accessibility and portability

Palette text is checked against a 4.5:1 contrast ratio, including the hero, buttons, tables and alerts. The native skip link and keyboard controls stay enabled. Previews use the same CSS exported by the Theme module. Themes were checked in WebKit at desktop width and at 390 px, including real HFC pages in light and dark modes. Pages without a hero retain spacing between navigation and content. Content on an individual installation may still need layout adjustments.

Reproduce the artwork sources and palettes with `python3 scripts/generate_theme_collection.py`. It writes into `output/playwright/themes-20261104` and does not overwrite active settings. Final validated configurations are in `resources/theme-collection/catalog.json`.
