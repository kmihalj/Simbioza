# Simbioza theme collection

[Hrvatska verzija](theme-collection_hr.md)

Simbioza includes **26 independent themes**, each with light and dark appearances. They use native Theme settings: colours, system fonts, navigation, hero, cards, borders and shadows. All settings remain editable in the interface. Choosing a theme changes presentation, not content or access permissions.

The complete collection combines six established themes (AAI, Dabar, Simbioza, Simbioza Orbit, Srce - sup and Standard), the twelve styles listed below and [eight themes for sport, education and creative communities](creative-themes_en.md). See the [complete gallery](theme-gallery_en.md) for all 26 themes and both appearances.

## Twelve editorial, modern and retro styles

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

## Selecting a theme

Open **Settings → Theme**, choose its name in **Active theme**, and save the settings. Select automatic, light or dark mode. To edit a theme, select it in the editor; existing themes remain independent. The collection does not change the active default theme.

## Importing into another installation

[Download the ZIP collection of all 26 themes](https://github.com/kmihalj/Simbioza/releases/download/0.3.42/Simbioza-0.3.42-themes.zip)

Starting with release 0.3.36, upgrades add missing bundled themes once. Existing themes and the active selection are preserved. Deleting an additional theme prevents later upgrades from restoring it. The import receipt is stored in Theme configuration and included in site backup/restore. You can still reimport a theme manually from its ZIP.

Each ZIP in `resources/installation/theme/<theme-id>.zip` is a complete export of one theme; all 26 IDs are listed in `resources/installation/theme/collection.json`. Under **Settings → Theme**, open **Import complete theme**, select the appropriate ZIP and import it. Then select it as the active theme. Do not upload the entire collection ZIP into the single-theme form; extract an individual package first.

Packages include both palettes, layout and the assets used by that theme, with checksums. The themes load no remote fonts, scripts or images. The Simbioza mark remains the original application mark; decorative vector illustrations were created specifically for this collection.

## Accessibility and portability

The gallery shows the actual Theme export with consistent sample content. The native skip link and keyboard controls remain available. Before applying a customized theme, check text contrast (normally at least 4.5:1), focus visibility, menus, breadcrumbs and cards in both appearances and at desktop and narrow-screen widths. A palette check alone does not prove that every page or photograph is accessible. Theme colours, hero height, artwork position and spacing remain editable. Pages without a hero should retain space between navigation and content.

## Maintainer sources

The twelve source configurations are in `resources/theme-collection/catalog.json`; the eight creative configurations have their own [source guide](creative-themes_en.md). The authoritative installation set is the 26 checked packages and `resources/installation/theme/collection.json`.

`python3 scripts/generate_theme_collection.py --output output/theme-sources` regenerates the twelve source definitions and artwork in a separate directory. It requires the Theme package and the local Simbioza mark inputs used by the generator. It does not install themes or change the active configuration. Regeneration is a maintainer task, not an installation or upgrade step. Review generated output before replacing prepared packages.

[Complete gallery of all 26 themes](theme-gallery_en.md).
