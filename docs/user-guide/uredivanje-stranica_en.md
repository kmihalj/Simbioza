# Editing pages

Learn how to create a page, add text, images and other content, and prepare it for readers. You work in a visual editor, much like a word processor. **You do not need to know HTML or write programming code for everyday editing.**

For your first page, follow [page creation](#new-page), [basic formatting](#text) and [saving and publishing](#saving). Return to the other sections when you need them. Surveys have their own [separate guides](ankete_en.md).

<a id="new-page"></a>
## 1. Create a new page or open an existing one

1. Open **Workspaces** and choose the workspace where you want to work.
2. To create a page, select the **New page** icon beside the workspace tree. Hover over an icon to read its name.
3. Enter the **Page title**. Check its language, the destination **Workspace** and **Parent page**. The parent determines where the new page appears in the tree; choose **Tree root** for the top level. The form states which primary language requires a title.
4. You can leave **Slug** empty: the system derives it from the title. It is the short part of the page's web address, so avoid changing it later without a reason.
5. Select **Create and edit**. The new page opens in the editor.

**Result:** the page exists in the selected place and is ready for content. Creating the page and typing text are separate from publishing it to readers.

<figure>
<img src="images/page-edit-01-en.jpg" alt="New page form with a title, language, workspace and parent page." class="img-fluid" loading="lazy">
<figcaption>New page: enter a title, check its language and position in the tree, then select Create and edit.</figcaption>
</figure>

To change an existing page, open it from the tree and select the **Edit** pencil. If the create or edit action is missing, ask an administrator or workspace manager to check your permissions.

An installation without Workspaces may have **HTML pages** and **Manage content**. Create standalone pages there and edit their content with the same editor. If Workspaces are installed later, standalone pages keep working until an administrator explicitly moves them into a workspace.

<a id="editor"></a>
## 2. Find your way around the editor

Document details, language selection and save actions are at the top. The **toolbar** sits immediately above the content you edit. **Attachments**, below the content, holds files associated with the page.

<figure>
<img src="images/page-edit-02-en.jpg" alt="Editor with document details, language selection, save buttons and the toolbar." class="img-fluid" loading="lazy">
<figcaption>The top of the editor: document details and save actions above the toolbar.</figcaption>
</figure>

Where offered, **Comments enabled** allows new comments; existing comments remain when new comments are switched off. On a workspace page this change follows publication. **Default page table-of-contents display** can inherit the workspace setting or choose **Show / Hide** for that page.

Click where you want to type or insert something. Select existing words before formatting them; to format a whole paragraph, place the caret inside it.

Hover over a picture button to read its name. On a narrower window the toolbar wraps onto several rows. Use Tab and Shift+Tab to move between buttons and fields; activate a focused button with Enter or Space.

| Toolbar group | What it does |
| --- | --- |
| **Format** | Paragraphs, headings, quotations or preformatted text. |
| **Check document** | Checks headings, images, links and tables before publication. |
| **Bold**, **Italic**, **Underline**, **Remove formatting** | Formats selected text. |
| Four alignment buttons | Left, centre, right or justified paragraph alignment. |
| **Unordered list**, **Ordered list**, **Link**, **Unlink** | Lists and links. |
| **Undo**, **Redo** | Reverses or repeats ordinary text changes in the current editing session. |
| **Dynamic elements** | Tabs, accordion, dropdown content, charts, timelines and available integrations. |
| **Insert**, **Bootstrap alerts**, **Cards** | Columns, a horizontal rule, embedded content and highlighted sections. |
| **Table**, **Media** | Works on the selected table, image or other media. |
| **External scripts** | Additional features prepared and approved by an administrator. |
| **Source code**, **Fullscreen** | Advanced HTML view or more space for visual editing. |

Some features appear only when the corresponding module is enabled and you have the necessary permissions. An unavailable table or media command often simply means that you have not selected a table cell or image yet.

<figure>
<img src="images/page-edit-03-en.jpg" alt="Open Dynamic elements menu with tabs, accordion, dropdown content and other blocks." class="img-fluid" loading="lazy">
<figcaption>Dynamic elements opens the settings for more complex content. Available integrations depend on installed modules.</figcaption>
</figure>

<a id="text"></a>
## 3. Write and format text

### Headings, paragraphs and lists

1. Write a short opening paragraph explaining the page's subject.
2. For a new section, type a heading, place the caret in it and choose its level under **Format**. The page title is already the main heading; body sections normally start with **Heading 2**, followed by **Heading 3** for subsections.
3. Use **Unordered list** for items and **Ordered list** for steps that must happen in sequence.
4. Select a few words and apply bold or italic for emphasis. To remove unwanted pasted styling, select that text and choose **Remove formatting**.

**Result:** the page has clear sections, making it easier for readers and assistive technology to navigate. Do not use an ordinary bold paragraph as a substitute for a real heading.

Use **Quote** for quoted words. **Preformatted** preserves spaces and the layout of a short text example. Left alignment works best for longer paragraphs; justification can introduce large gaps between words.

### Links

1. Select words that should lead somewhere, such as “Application requirements”.
2. Select **Link**.
3. For an external page, enter the web address and select **Insert URL link**. For a Simbioza page, choose a workspace and page under **Internal link**, then confirm.
4. For a long list, use search and **Load more**. The picker offers pages you may select.

**Unlink** keeps the words and removes their destination. After saving, open the link from the page view to check it.

<a id="layout"></a>
## 4. Separate sections with columns, alerts and cards

| You want… | What to do | Result |
| --- | --- | --- |
| Two or three parallel sections | **Insert → Two columns / Three columns**, then replace the text in each column. | Content appears side by side on a wide screen and stacks on a narrow screen. |
| A short important note | Open **Bootstrap alerts**, choose a style and replace the sample text. | The message stands out. Begin with a clear word such as “Important”; colour alone is not enough. |
| A separate box of related information | **Cards → Card with title / Card without title**. Fill in the body and, where applicable, the header. | A summary, contact or related section is separated from the surrounding text. |
| A simple divider | **Insert → Horizontal rule**. | A horizontal line separates two sections. |

Use columns and cards for short related content, and headings for a longer article's structure. Always replace all inserted sample text.

<a id="tabs"></a>
## 5. Tabs: several alternatives in one place

Tabs work well for alternatives within one topic, such as “Before arrival”, “Programme” and “Contact”. Readers select a tab title to see its content.

1. Place the caret where the block should go and open **Dynamic elements → Insert tabs**.
2. Enter **2 to 6 titles**, one per line, and select **Insert**.
3. In the main editor, fill in the content for each tab. You can also edit titles there. During editing, the contents are laid out so that you can work on each one.
4. Save and check the page view: selecting a title should display the corresponding content.

<figure>
<img src="images/page-edit-04-en.jpg" alt="Insert tabs dialog with three titles, each on a separate line." class="img-fluid" loading="lazy">
<figcaption>This example creates three tabs. After inserting them, write their bodies in the main editor.</figcaption>
</figure>

The pencil beside the block reopens its settings. **Remove tabs** removes the whole block. Removing a title from the title list also removes its content. Change titles and content together; rewriting or rearranging title lines does not automatically move the associated text.

<a id="accordion"></a>
## 6. Accordion: sections readers can expand

An accordion suits frequently asked questions or several short procedures. Readers select a question title to open its answer. They can keep several sections open at once.

1. Choose **Dynamic elements → Insert accordion**.
2. Enter **1 to 12 titles**, one per line, then select **Insert**.
3. In the main editor, fill in the body under each title. You can add links, images, tables and other content.
4. In the page view, open and close individual sections to check their titles and content.

<figure>
<img src="images/page-edit-05-en.jpg" alt="Insert accordion dialog with three question titles on separate lines." class="img-fluid" loading="lazy">
<figcaption>Each line becomes one accordion section title; write the answers after inserting the block.</figcaption>
</figure>

Use the block's pencil to change the number of sections. A new title receives an empty body; removing a title also removes its body. **Remove accordion** removes the entire group.

<a id="dropdown-content"></a>
## 7. Dropdown content: one expandable note

For a single additional note or explanation, choose **Dynamic elements → Insert dropdown content**.

1. Enter a **Title** that clearly describes what is inside.
2. Enable **Show content expanded when the page loads** if readers should see the text immediately.
3. Select **Insert** and fill in the body beneath the title in the main editor.

<figure>
<img src="images/page-edit-06-en.jpg" alt="Insert dropdown content dialog with a title and the initially expanded option enabled." class="img-fluid" loading="lazy">
<figcaption>The expanded-content setting determines what a reader sees when the page loads.</figcaption>
</figure>

The body stays open in the editor so that you can edit it. The page view follows your chosen initial state; readers select the title to open or close it. The block's pencil opens its settings and offers removal.

Tabs, accordion and dropdown content can contain formatted text and other elements. Keep the page's most important information visible without requiring readers to open a hidden section.

<figure>
<img src="images/page-edit-15-en.jpg" alt="Published page with the Materials tab selected, an accordion answer open and dropdown content expanded." class="img-fluid" loading="lazy">
<figcaption>The result for readers: the selected tab shows materials, while selecting a heading opens an answer or an additional note.</figcaption>
</figure>

<a id="tables"></a>
## 8. Tables, sorting and row counts

### Create and edit a table

Choose **Table → Insert table** and fill in the prepared cells. Use the first row for column headings, such as “Name”, “Status” and “Due date”.

To change a table, first click the relevant cell and reopen **Table**:

- **Insert row above / below** and **Delete row** act on that cell's row.
- **Insert column left / right** and **Delete column** act on its column.
- **Merge with cell right / below** joins cells; **Split cell** separates a previously merged cell.

Use tables for data, rather than page layout. Use [columns](#layout) to arrange content.

### Let readers sort and browse rows

1. Click a table cell.
2. Open **Table → Table settings**.
3. Enable **Sorting and pagination**.
4. Choose the **Initial rows per page**: **10, 25, 50, 100 or All**.
5. Select **Apply**, then save the page.

<figure>
<img src="images/page-edit-07-en.jpg" alt="Table settings with sorting and pagination enabled and an initial page size of 10 rows." class="img-fluid" loading="lazy">
<figcaption>These settings apply to the selected table. Readers can change the row count afterwards.</figcaption>
</figure>

**Result:** readers select a column heading to sort ascending, select it again for descending order and a third time for the original order. Arrows show the direction. They can choose a row count beside the table and browse pages with the pagination controls. **All** displays every row.

Enable this **separately for each table**. The editor still shows every row; a reader's sorting does not change the saved data. No external script is required. The controls also work in HTML exports, and printing shows all rows.

This feature needs one header row, the same number of cells in each data row, no merged cells and no table nested inside a cell. For a more complex table, keep the ordinary view or split the data into simpler tables.

<figure>
<img src="images/page-edit-08-en.jpg" alt="Sorted table in page view with 10 rows per page selected and page two showing rows 11 to 12 of 12." class="img-fluid" loading="lazy">
<figcaption>Reader view: 10 rows per page is selected. Page two shows the remaining two rows, 11–12 of 12. The header arrow indicates sorting.</figcaption>
</figure>

<a id="attachments"></a>
## 9. Upload an image, video or another file

1. Below the content, open **Attachments**, choose **Add attachment / Choose files**, then **Upload**. Wait for the upload to finish.
2. Enter a **Display name** beside the file. This is the name you want readers to see; it can be clearer than the original filename.
3. For an image, enter **Alternative text**: a short description of the information it conveys. For example, “Room layout with the entrance marked”, rather than “image1.png”.
4. **Caption** is a short note beside the inserted media; **Description** provides additional information about the attachment. Select **Save metadata**.
5. Put the caret where the file should appear and select **Insert** beside the attachment.

**Result:** an image or video appears in the content; another file appears as a link for opening or downloading. Uploading alone does not insert a file into the text.

<figure>
<img src="images/page-edit-10-en.jpg" alt="Image and text attachments with fields for display name, alternative text, caption and description." class="img-fluid" loading="lazy">
<figcaption>Enter descriptive information beside the uploaded image and save its metadata. Insert adds the selected attachment to the content.</figcaption>
</figure>

### Media size, spacing and alignment

Select an inserted image or other media and open **Media**. Choose a width of **25%, 50%, 75% or 100%**, **no, small, medium or large** spacing, and **left, centre or right** alignment. **Reset media** restores its initial appearance settings.

**Edit image alternative text** changes the selected image's description. Use **Mark image as decorative** only when the image conveys no information, such as a decorative divider. A visible caption does not replace alternative text.

A full width is usually easiest to read for a screenshot. Check the page in a narrow window after making changes.

<figure>
<img src="images/page-edit-09-en.jpg" alt="Selected image and the full Media menu with width, spacing, alignment and alternative-text commands." class="img-fluid" loading="lazy">
<figcaption>First select the image, then use Media to set its appearance and description.</figcaption>
</figure>

### Attachment lists and history

**Visibility** in the attachment section controls who sees the attachment **list**: no list, everyone including guests, or authenticated users. It does not itself grant access to a private page or file.

You can download a file, change its metadata and, with the appropriate permissions, delete it. Where **File history** is available, review previous versions and restore one if necessary. Removing an image from the text and deleting its attachment are different actions: deleting the file can affect places that use it.

<a id="embedded-content"></a>
## 10. Embed content from another service

For content such as an interactive learning activity or a video supplied by another service, open **Insert → Insert embedded content**.

1. Paste the provider's **Iframe code or HTTPS URL**.
2. Enter an **Accessible title**, such as “Exercise: identify geometric shapes”.
3. Set the **Height** and, if needed, **Allow fullscreen**.
4. Select **Insert**, save and check the page. Use the block's pencil for later changes.

If the content does not appear, the provider may forbid embedding or require a separate sign-in. If an additional script is required, ask the administrator to prepare a supported integration; do not paste unfamiliar programming code yourself.

<a id="chart"></a>
## 11. Compare numerical data with a chart

1. Choose **Dynamic elements → Insert chart**.
2. Select **Bar, Line, Area, Pie or Doughnut**. A bar chart works well for comparing participant counts by month.
3. Enter a title, categories and numerical values. **Add category** adds another category; **Add series** adds a second set of values, such as another year.
4. Add axis names and a description if useful; choose a legend, orientation, appearance, width and alignment. The background can follow the theme.
5. Select **Insert chart** and check the page view.

**Result:** the values you entered are displayed as a chart. They are not automatically fetched from a survey or another table. Use the chart's pencil to change its values or appearance; its settings also offer removal.

<figure>
<img src="images/page-edit-11-en.jpg" alt="Chart settings with three categories, their numerical values and a bar-chart preview." class="img-fluid" loading="lazy">
<figcaption>A chart with three categories. The preview shows the effect as you change its values and appearance.</figcaption>
</figure>

<a id="roadmap"></a>
## 12. Show activities on a timeline

1. Choose **Dynamic elements → Insert timeline**.
2. Enter a title and the start and end of the period you want to show.
3. Select the time scale: **Days, Weeks, Months or Quarters**.
4. Use **Add group** to add a group, such as “Event preparation”, and activities with start and end dates. Add further groups and important-date markers where needed.
5. Select **Insert timeline** and check that every activity fits the chosen period.

**Result:** the page shows when each activity starts and ends. This is a presentation of the plan you entered, rather than an automatic calendar or reminder service. Use the block's pencil to reopen its settings.

<figure>
<img src="images/page-edit-12-en.jpg" alt="Timeline settings with a date range, weekly scale and two activities in one lane." class="img-fluid" loading="lazy">
<figcaption>A timeline example: one lane with two activities and individual start and end dates.</figcaption>
</figure>

<a id="integrations"></a>
## 13. Show data from other parts of Simbioza

These **Dynamic elements** menu entries depend on installed modules. If an entry is missing, ask an administrator whether that feature is available in your installation.

| Feature | What to do | What readers see |
| --- | --- | --- |
| **Insert task list** | Enter a title and tasks, one per line. Choose a list or table and who may change statuses. | Tasks and their statuses, according to the selected permissions. |
| **Insert calendar** | Choose one or more offered calendars, a title, month/week/day view and the current or a fixed date. | Events from calendars the reader may access. Embedding does not change calendar permissions. |
| **Include page content** | Search for a workspace and page; use **Load more** where needed, then confirm. | The selected page's content without maintaining a second copy; reader access is still checked. |
| **Insert dynamic content** | Choose a page-property table, attachment gallery, workspace search or recent changes. Set workspaces, a title, filters and item count. | An up-to-date view of accessible pages and their data. |

Use the inserted block's pencil to change its settings. Some blocks appear as a settings summary in the editor. Save and open the page view to check their final appearance.

### Properties for a page table

For a project overview with columns such as “Status” and “Due date”, first add consistently named properties and values to the source pages. In the dynamic block, choose **Pages and properties table** and list those properties as columns. If data is missing, check the exact property name, selected workspaces, label filter and source-page read permissions. See [Workspaces](podrucja_en.md) for properties and page settings.

### External scripts prepared by an administrator

1. Open **External scripts**.
2. If a script has a **circled ?**, select it and read the instructions. The question mark appears only when instructions exist; reading them does not enable the script.
3. Select the script you need and save the page. The selection is kept with that content version.

Only approved scripts appear. If the instructions require a source-code change that you do not know how to make, ask an administrator or experienced editor to prepare an example for your page. **Built-in table sorting and pagination do not need an external script.**

Check scripts on the published page; they do not run in the editor, drafts or history. A revoked script stops running. HTML export includes selected approved scripts; remote scripts still need a network connection.

<a id="saving"></a>
## 14. Save, check and publish your work

| Action | Meaning |
| --- | --- |
| **Save** | Saves changes as a draft. In a workspace, the existing published version remains visible to readers. |
| **Save and submit for review** | Saves the draft and submits it for someone with publishing permission. Use it when the content is ready but you do not publish it yourself. |
| **Save and publish** | With editing and publishing permissions, saves the content and publishes it immediately. |
| **Cancel** | Leaves the editor; if changes are unsaved, follow the message before leaving. |

Submitting for review and publishing apply to workspace pages. Standalone HTML pages do not use the same workspace review process: saving updates their own content.

### Check before publishing

1. Select **Check document** on the toolbar.
2. Correct reported issues, such as an empty heading, skipped heading level, image without alternative text or table without a header.
3. Select **Check again**. The check helps, but cannot judge whether your text is understandable or every fact is correct.
4. Save changes and open **Preview**. Check which version and language you are viewing. To inspect unpublished workspace changes, choose **Preview draft**; saving a draft does not replace the ordinary published page view.
5. Test links, tabs and expandable sections; check images, data and the layout in a narrow window.
6. Publish or submit for review. After publishing, reopen the page as a reader.

A **local draft** offered after an interruption is saved only in that browser. It does not replace **Save**. If another editor has changed the shared draft, compare the offered versions before restoring your changes.

**Discard shared draft** removes unpublished shared changes and returns to the last published version. Coordinate with other editors before confirming. On a new page that has **never been published**, discarding its draft can delete the page permanently; read the confirmation before continuing.

In the page view you may also see **Submit for review**, **Return to draft** or **Publish**, depending on status and permissions. A publisher can publish a draft directly; a prior review submission is not mandatory. If you only have publishing permission, open **Preview draft**, check the content and language, then choose **Publish** in the page action bar; that permission alone does not allow text editing. An archived page must be returned to draft and published again before becoming available to readers.

<a id="languages-history"></a>
## 15. Translate a page or restore earlier content

### Another page language

1. Under **Document language**, choose the language you want to edit. Offered languages are installed and enabled in the application.
2. Check the title in that language using the menu beside **Title**.
3. **Translations → Copy translation** can copy existing content as a starting point. Choose a source and target language, then **Copy for translation**.
4. Translate the text, tab titles, captions and image descriptions; check and publish that language version.

**Copying does not translate the text automatically.** An existing target translation is replaced by a new version. You do not have to translate into every offered language; publish only those for which you have prepared content.

### History

Open **History**. Select **Preview** beside an older version to read it first. To bring its content back, select **Restore** and follow the confirmation. Then check the current draft and publish if needed. Toolbar **Undo** and **Redo** handle ordinary text changes in the current editing session. They do not cover every media-setting or special-block change, so do not rely on them to restore those settings. Select **Save** before larger changes; history keeps saved versions.

<a id="page-access"></a>
## 16. Who can read and edit a page

Most pages inherit workspace permissions. For everyday writing you only need the relevant editing permissions; you do not have to change access settings yourself. A draft without a published version is available to the relevant editors, publishers and managers, rather than ordinary readers. The tree and page status identify it as a draft.

Where you have permission, open **Manage page and permissions** in the page's upper action bar. Distinguish between:

- **Restrictions on inherited permissions:** further limit existing rights for the page and its descendants. They do not add someone who had no access before.
- **Direct permissions for users and groups:** exceptions for the selected page. You can choose users or groups; an exception does not automatically open other workspace pages or descendants.

Open the searchable user or group picker, find the recipient and add them. If there are more results, search or load additional offered items. Check **Read**, **Editing** and **Publish**, then select **Save direct permissions**. Editing and publishing include reading; publishing alone does not include editing. The separate restriction panel applies to individual users who already have inherited rights, not to whole groups.

The **Public** audience grants read access, including to guests. It cannot grant editing or publishing. This can make a published page public inside a private workspace while the remaining private content stays protected; it does not publish a draft. The workspace may then appear among available workspaces, but its tree shows only content that reader may access.

<figure>
<img src="images/page-edit-13-en.jpg" alt="Direct permissions: Ana can read, the Editors group can read and edit, and the Public audience can only read." class="img-fluid" loading="lazy">
<figcaption>Direct permissions for a single page: a user, a group and the Public audience have different rights.</figcaption>
</figure>

Changing permissions can expose content to others. If uncertain, ask an administrator or workspace manager to configure access. Afterwards, ask the intended reader to open the page; an administrator's view is not proof that access is correct for everyone else. See [Workspace permissions](podrucja_en.md) for more detail.

<a id="page-actions"></a>
## 17. Other page actions

The page's upper action bar shows only actions available in your installation and for your account. Hover over an icon to read its name.

<figure>
<img src="images/page-edit-14-en.jpg" alt="Top of a published page with its action bar." class="img-fluid" loading="lazy">
<figcaption>Published-page actions: the offered icons depend on your permissions and enabled features.</figcaption>
</figure>

- **Show attachments / Hide attachments** opens or closes the file list. **Show table of contents / Hide table of contents** shows or hides the list of headings within the page.
- **History** opens saved versions; the flag menu chooses an existing translation.
- **Copy or move page** is available to workspace managers. Choose copy or move, a destination workspace and parent, and whether to transfer history and direct permissions/restrictions. Content, translations and attachments travel together. A copy leaves the original in place; a move removes it after successful transfer. Only the chosen page is copied; moving a page with descendants is unavailable. Check destination permissions afterwards.
- **Export page to HTML** downloads a ZIP containing `index.html` and associated local files. Extract it before opening the HTML file. For workspace pages, this exports the published version, rather than an unpublished draft. It is not a backup for restoring the whole workspace.
- For a PDF, open the browser's **Print → Save as PDF**. Check the print preview and layout before saving.
- **Delete page and subtree** also removes descendants; read the confirmation carefully. Published pages use recoverable deletion, while deleting a new never-published document through the editor can be permanent. Ask an administrator about recovery instead of assuming every deletion can be undone.
- Where enabled, **Follow** and comments support change notifications and discussion.

In the editor, **Source code** displays HTML for experienced editors; it is unnecessary for the procedures above. **Fullscreen** gives content more room; use the same icon to return to document details and save actions.

<a id="help"></a>
## 18. When something is unexpected

| What you see | What to do |
| --- | --- |
| No edit or publish action. | Check the account and workspace. Ask a manager or administrator to check your permissions. |
| You changed text but readers still see the old page. | Save the draft and check whether publication is still needed. Check the language too. |
| A table or image command does nothing. | First click a table cell or select the image itself, then repeat the command. |
| Dropdown content stays open in the editor. | This is the editing view. Check the initial state in the page view. |
| Tabs or accordion have titles but no body text. | After insertion, fill in each section's body in the main editor. |
| Sorting and row counts are missing. | Enable them in **that table's settings**, check its simple header and view the saved page. |
| A file is uploaded but absent from the text. | Place the caret in the content, then choose **Insert** beside the attachment. |
| You see a calendar, included page or dynamic list, but a reader does not. | Check their rights on the source content too. Embedding does not open private data. |
| A script is missing or does not work. | Read its instructions and check the published view. An administrator prepares and approves scripts. |
| Pasted text has inconsistent styling. | Apply **Remove formatting**, then set headings and lists again. |
| Undo does not restore a changed image width or other block settings. | Restore those settings through their menu. Save before larger changes. Choose **Cancel** to discard unsaved changes; changes already saved remain saved. |

Before finishing, check **content, saving, the selected language, links and the reader's expected access**. For most pages these matter more than the number of decorative elements you insert.
