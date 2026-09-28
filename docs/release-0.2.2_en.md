# Simbioza 0.2.2

Large Confluence imports now reconcile links and included pages in bounded,
resumable finalization steps. This prevents a single expensive final request
from exceeding a typical reverse-proxy timeout. The importer also reduces the
number of requests needed to copy large attachment sets.

If the connection is interrupted, the browser checks the same job and resumes
it. After a refresh, a running job has a **Resume import** action in **Recent
Confluence imports**. Do not start another import while the original job is
running. A proxy timeout does not by itself mean that the import failed: check
the job status and report before retrying.

Empty Confluence layout rows are omitted on new imports. The document viewer
also keeps Bootstrap grid content below its action toolbar, so already imported
pages display correctly without reimporting them. The authentication module
reconciles SSO profile entries on every settings page; a removed profile no
longer lingers in another module's sidebar.

Before updating, make a restorable backup. Update Simbioza through Setup or
the CLI; Auth should reach 0.1.15, HTML Editor 0.1.38, and Confluence Import
0.1.41.
The interface language packs have revision `2026.09.28.4` and can be refreshed
separately through Setup.
