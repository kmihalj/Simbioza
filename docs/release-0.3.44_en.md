# Simbioza 0.3.44 — images stay within HTML content

Images now fit within the HTML content container, including large images imported from Markdown and images with a fixed pixel width. A width of **100%** uses the available space inside that container. The correction preserves the image's proportions, smaller chosen widths, links and captions; it does not crop the image or enlarge small images.

## Consistent behaviour across views

The HTML Editor **0.3.19** shares the same media boundaries between the document editor, published pages, reusable HTML form fields, survey HTML content and newly generated standalone HTML exports. Guides displayed without Wiki have the same protection. Images inside cards, tabs, accordions, tables and links also respect their containing content area and spacing.

A browser regression test uses a portable PNG image and covers oversized originals, percentage and fixed widths, captions, nested content and spacing in the editor, published view and offline HTML export at desktop and mobile widths. The source HTML remains editable; this correction changes presentation rules rather than rewriting stored content.

## Upgrade

Use the normal GUI update on a correctly configured secured PHP-FPM installation, or the documented CLI update elsewhere. The Demo uses its installed operator-managed upgrade helper. Running Demo and production installations are not upgraded automatically.

During the upgrade, Simbioza automatically refreshes the shared media CSS used by existing filesystem-backed HTML pages. This changes only the media stylesheet: stored HTML, document revisions and `theme.css` remain untouched. A project using the generic HTML Editor module can perform the same refresh from its application directory:

```sh
vendor/bin/hph editor-html:refresh-media-css
```

No database migrations are added. The HeartPhrame Framework remains **0.0.25**, unchanged. Previously downloaded HTML export archives remain independent files: generate a new export after updating to include the corrected stylesheet.

See [installation](installation_en.md), [Docker deployment](docker_en.md) and [the release history](releases_en.md).
