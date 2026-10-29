# Simbioza 0.3.22 — accessibility and Survey performance

[Croatian version](release-0.3.22_hr.md)

Survey questions now keep help, limits and validation errors associated with their
controls. Required questions have a text equivalent, error links lead to the
correct question, and page/review navigation restores keyboard focus. User and
group dropdowns retain input focus, support arrow-key selection and Escape, and
cancel obsolete searches. Matrices have larger selection targets. Content-language
controls and navigation use readable theme colors. Native form filling remains
available without JavaScript.

The shared Theme derives the shading needed for readable text across a gradient.
Stored gradient stops and text colors remain unchanged. Live previews, generated
stylesheets, mobile navigation and exported themes use the same calculation.
Already readable gradients retain their appearance. The Menu keeps valid named
navigation semantics after a mobile dialog closes. High-contrast settings also
correct the backgrounds of paragraph notices and quotations.

Survey result pages batch answers and, for identified surveys, the selected
identities. Anonymous results do not read identities. Aggregate questions share
grouped reads; numeric values are processed in bounded windows. Permission labels,
report recipients and admission profiles use batched reads. Workspace listings
resolve Survey extension grants once per actor/request, and mixed page trees do
not read participant answers. Direct Wiki page permissions reuse their batched
directory data. Authorization, privacy filters, small-cell protection and fresh
admission checks remain in force.

This release includes Survey 0.3.3, Workspace 0.3.2, Menu 0.3.3, Theme 0.3.5 and
Accessibility 0.3.1. Auth and Framework versions are unchanged. A reversible Survey
migration adds a grant lookup index without changing permissions or responses.
The updater applies it only where Survey is installed. Existing optional modules
remain optional and are not automatically enabled by this update.

Update through the secured-FPM GUI or the documented CLI updater. Docker
installations use `ghcr.io/kmihalj/simbioza:0.3.22` with their persistent volumes.
See the [module inventory](module-dependencies_en.md), [update guide](installation_en.md)
and [Docker guide](docker_en.md).
