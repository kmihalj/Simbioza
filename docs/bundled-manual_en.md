# User guides with and without Wiki

[Croatian version](bundled-manual_hr.md)

Every installation includes the same Croatian and English user guides and their
screenshots. Installing Wiki is not required to read them.

## Installations with Wiki

The installer imports the guides as ordinary pages in the **User guides**
Workspace. The existing page tree, translations, attachments and page editing
continue to work. The application updater refreshes release-managed guide pages
using the existing bundled-content update process.

## Installations without Wiki

Choose **Guides** in the top menu. The application displays the bundled manual as
HTML pages, without creating a Workspace or installing the HTML Editor solely
for documentation.

- Choose a chapter in **Guide contents**. On small screens, expand this section
  above the page text.
- Use **Previous chapter** and **Next chapter** to continue reading.
- Where both guide languages are installed, use the flag dropdown to switch
  between Croatian and English.
- Screenshots and other bundled illustrations remain available. Links between
  chapters stay within this installation.

These pages are read-only. They come from the installed release, not from live
test surveys or another site's database. Updating Simbioza updates this copy of
the manual. These HTML pages are not a second editable content repository.

The address `/guides` works in either configuration: without Wiki it opens the
HTML manual; with Wiki it leads to the corresponding native guide page. The
public asset endpoint serves only files referenced by published bundled guide
pages; it does not expose live document attachments, configuration or backups.

## Language availability

The interface uses the installation's active language settings. The manual
offers only translations that exist in the guide package and are installed in
the application. The current manual is available in Croatian and English;
installing another interface language does not automatically translate its text
or screenshots.
