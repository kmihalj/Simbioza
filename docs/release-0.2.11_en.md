# Simbioza 0.2.11

## Confluence migration walkthrough

The English and Croatian READMEs now introduce the optional Confluence Import
module near the top and include a playable walkthrough. The approved video
shows exporting a Confluence space, importing its XML ZIP, and inspecting the
result. It has British English narration, English captions, and blurred private
identities. It also explains that Simbioza's built-in page outline replaces
Confluence's table-of-contents macro.

The bilingual Confluence import user guide includes the same video and a
preview image as local page attachments. New installations receive them in the
starter user-guide Workspace; existing installations receive the updated page
through the normal updater. Playback from the installed guide does not depend
on an external video host.

## Safe guide updates

The updater now manages the Confluence import guide individually, alongside
the installation and meeting guides. It keeps the existing page identity,
position, and permissions, preserves unrelated pages, and saves a private
recovery archive before replacement. An unchanged package is not imported twice.

No new database migrations or application module versions are required. This
release does not install Confluence Import on sites where it is absent, repeat
an import, or change imported business content. Back up the installation before
updating as usual.

The separately maintained private demo module also carries the walkthrough
and localized descriptions for its protected Confluence migration article.
Its editorial content is refreshed through the host-controlled baseline
procedure, never an automatic Composer content-overwrite hook.
