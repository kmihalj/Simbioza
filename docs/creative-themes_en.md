# Themes for sport, education and creative communities

[Hrvatska verzija](creative-themes_hr.md)

These eight themes extend the [first theme collection](theme-collection_en.md).
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
configuration, artwork and checksums. Themes are optional; upgrades do not
replace your existing themes.

## Artwork and sources

Seven raster illustrations and photographs were created with the built-in
`image_gen` tool. They do not depict a specified real venue or event. Festival
uses original static SVG ribbons. The Simbioza mark stays unchanged. Themes
load no remote images, fonts or additional scripts.

Original raster assets and their prompts are in
`resources/theme-collection/creative/artwork/`. Each package includes its
corresponding prompt in `source/artwork.json`. Regenerate palettes and manifests
with `python3 scripts/generate_creative_themes.py`; it writes to a separate output
directory and does not change active settings. Final configurations are in
`resources/theme-collection/creative/catalog.json`.
