# Themes for sport, education and creative communities

[Hrvatska verzija](creative-themes_hr.md)

These eight themes are part of Simbioza’s [complete 23-theme collection](theme-collection_en.md), alongside the twelve modern/retro styles and three established themes. The [gallery](theme-gallery_en.md) shows every theme in both appearances.
Each has light and dark palettes and five separate hero gradient stops. Layout,
colours, fonts, artwork, cards and shadows remain editable in Theme settings.

Headers use medium height on the home page and a compact layout on inner pages.
Smaller artwork and spacing leave more room for content.

| Theme | Appearance and intended use |
| --- | --- |
| Stadium | A running track at sunset; for sports clubs and competitions. |
| Classroom | A collage of books, geometry and space; for schools, teachers and learning. |
| Little World | A paper sun, rainbow and little houses; for preschools and family communities. |
| Cinema Club | A film ribbon and a projector beam; for film, culture and stories. |
| Manga | An original anime adventurer and comic panels; for comics, games and creative clubs. |
| Atelier | Watercolour on warm paper; for art workshops and creative journals. |
| Mountain | A mountain lake at dawn; for hiking and outdoor activities. |
| Festival | Colourful ribbons, confetti and a violet-to-teal gradient; for events and music. |

## Selecting and importing

In Simbioza, open **Settings → Theme**, select the theme under **Active theme**, then
save. Names follow the interface language, for example *Classroom / Učionica*.
Adding the collection does not change the currently selected theme.

For another installation, select an individual
`resources/installation/theme/simbioza-<name>.zip` package in **Import complete
theme**. For example: `simbioza-mali-svijet.zip`. Each package contains its
configuration, artwork and checksums. Themes are optional. Upgrades add missing collection IDs once, preserve existing themes and active selection, and remember administrator deletions. A deleted theme returns only through explicit manual import, not the next upgrade.

## Artwork and sources

Seven raster illustrations and photographs were generated for this collection. They do not depict a specified real venue or event. Festival
uses original static SVG ribbons. The Simbioza mark stays unchanged. Themes
load no remote images, fonts or additional scripts.

Original raster assets and their prompts are in
`resources/theme-collection/creative/artwork/`. Each package includes its
corresponding prompt in `theme-files/source/artwork.json`. Regenerate palettes and manifests
with `python3 scripts/generate_creative_themes.py --output output/creative-theme-sources`; it requires the local Theme package and source artwork, writes to a separate output directory and does not install themes or change active settings. Review generated output before replacing prepared packages. Final configurations are in
`resources/theme-collection/creative/catalog.json`.
