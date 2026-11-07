# Simbioza 0.3.33 — twelve themes and readable page descriptions

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.42](release-0.3.42_en.md).

[Croatian version](release-0.3.33_hr.md)

Theme **0.3.9** keeps a clear gap between navigation and cards on pages without
an inner hero. Supporting descriptions follow their actual background, including
when a narrow layout moves them below the hero. Insufficient text contrast is
corrected without changing the hero gradient. A text-only backdrop is used if no
single foreground can meet 4.5:1 contrast across the gradient.

The [theme collection](theme-collection_en.md) contains twelve optional themes,
each with light and dark variants, local artwork and native editable settings.
Individual importable ZIPs are included in `resources/installation/theme/`.
Import a chosen package in **Settings → Theme → Import complete theme**, then
select it as active. Existing themes and the active selection are preserved.
The About page also avoids duplicate titles when its title is placed in a card.

Framework stays **0.0.25**. There is no database migration or reset of existing
content, permissions or private configuration. DEMO and apps-test are upgraded
by their operator through the existing CLI or secured FPM GUI update flow.
