# Simbioza 0.3.35 — description contrast without background strips

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.37](release-0.3.37_en.md).
[Croatian version](release-0.3.35_hr.md)

Theme **0.3.11** removes the unwanted backgrounds introduced behind supporting
descriptions in 0.3.33. Contrast is adjusted through the text foreground only.
The calculation uses the text's actual position and line width rather than the
entire hero gradient, so a readable configured color remains unchanged.

The fix covers home-page subtitles, module descriptions, unsaved theme previews,
responsive layouts, light/dark palettes and newly generated portable exports.
Hero gradients, stored palettes, explicitly selected text shadows and the gap
between navigation and content cards remain unchanged.

The twelve optional themes are available as a [separate collection ZIP attached
to release 0.3.33](https://github.com/kmihalj/Simbioza/releases/download/0.3.33/Simbioza-0.3.33-themes.zip).
Extract it, then import individual theme ZIPs in **Settings → Theme → Import
complete theme**. An application upgrade does not import or activate them.

Framework remains **0.0.25**. This patch adds no database migration and does not
reset content, permissions or private configuration. DEMO and apps-test are
upgraded by their operator through their existing CLI or secured FPM GUI flow.
