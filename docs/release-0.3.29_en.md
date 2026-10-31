# Simbioza 0.3.29 — survey removal and Playground content

[Croatian version](release-0.3.29_hr.md)

Survey pages now have a trash action in the header, matching ordinary pages. Deletion moves the survey to recoverable trash and stops collection; accepted responses, revisions and settings remain available for administrator recovery. Restoring a previously published survey leaves it closed until a publisher explicitly reopens it. Report schedules remain disabled after deletion.

A working draft can be discarded separately while its published revision and accepted responses remain unchanged. Discarding an entirely unpublished survey moves it to trash. Current permissions, confirmation and revision checks apply to browser and API actions; unauthorized users cannot delete or restore content. Administrators find removed surveys under Settings → Surveys → General → Surveys · Trash.

Editors may collaborate on survey drafts. Draft and In review badges distinguish working versions in the workspace tree. Readers and respondents see only the published revision and do not learn that a newer draft exists. Workspace publication notifications now run after the source transaction commits, preventing nested index transactions during survey changes.

The new-page form initially shows the required primary-language title field. A title entered there can be used immediately to create and edit a page. The private Demo policy permits normal page, survey and comment operations in the Playground while protecting the supplied documentation and administrative settings.

HTML page creation, draft saving, editing and publication were checked with administrator and editor accounts. Opt-in DataTables was checked on published pages, including all-column sorting, numeric/date ordering, search, pagination, keyboard controls, English/Croatian labels and public access. The HTML export retains the selected approved resources. Ordinary tables remain unchanged, and approved content scripts are not executed in the editor or draft preview.

This release uses Survey 0.3.5 and Workspace 0.3.4. The private Demo support package is 0.3.12. No database migration is added. The public upgrade preserves installed optional modules and local settings. The Demo owner must first update the private support package with its documented operator command; ordinary installations do not install that package. See [installation](installation_en.md) and [module inventory](module-dependencies_en.md).
