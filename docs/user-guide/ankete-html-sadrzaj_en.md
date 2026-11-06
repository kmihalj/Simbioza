# HTML content and safety

[For developers — API](ankete-developeri_en.md)

Integrated HTML passes through the same server-side sanitizer as editor input. Source editing is not an exception to the security policy.

<div class="table-responsive">

| Content | Behaviour |
|----|----|
| Ordinary text, b/strong, i/em, headings, lists, tables and safe media | Allowed within HTML Editor policy. |
| script, style and handlers such as onclick/onerror | Removed; never use them for survey logic. |
| javascript:, data: and similar executable URLs | Rejected. |
| Unsupported attributes and CSS | May be removed even when not dangerous. |
| Charts, roadmaps, tabs, accordions, dropdowns and interactive tables | Their supported Editor markup and settings are preserved and use the shared Editor display. |
| Calendar, task, included-page and Workspace blocks | Safe references and settings are preserved. Without a host service that resolves the block, it remains an inert placeholder. |
| Iframe embeds | Only embeds accepted by the shared Editor policy are retained, including their required protection settings. |

</div>

Use the canonical structures created by the Editor rather than arbitrary executable markup. Applying and saving retain supported content; the generated chart or roadmap display does not replace its editable source. Survey does not turn script-registry references into executable scripts or grant access to the targets of dynamic blocks. Supply question conditions as supported declarative definitions, not JavaScript.

Respect attachment authorization when integrating systems. A link does not grant access to private content. Always treat participant answers as data, not executable HTML.
