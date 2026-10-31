# Simbioza 0.3.28 — controlled page scripts and translated help

> Historical release notes. See the [current installation guide](installation_en.md) and [Simbioza 0.3.29](release-0.3.29_en.md).

[Croatian version](release-0.3.28_hr.md)

Application JavaScript, including Bootstrap, is served locally. Published pages may use administrator-approved third-party script bundles only when the page author selects them. Content Security Policy authorizes the application templates and the exact approved page scripts; inline event handlers are blocked.

HTML Editor settings accept a complete loading snippet containing JavaScript and optional CSS, with optional separate initialization code. Source previews preserve formatting. Usage instructions are edited in the existing WYSIWYG editor, separately for installed languages. Authors open them through the circled question mark beside an approved script. Help uses the interface language, then English, then the first available translation. Empty help has no question mark. The New script form is initially collapsed.

The bundled DataTables example includes instructions in Croatian, English, German, Spanish, French and Italian. New installations and upgrades leave it pending for administrator review. Approval alone does not activate it on pages. Existing local names, approvals and translations are preserved. The private Demo preparation separately approves the example for visitors to opt into; it is not part of the public module's approval policy.

HTML exports include approved, selected page scripts: remote resources remain links and inline code becomes local files. Configured integrity hashes are retained. Page, Workspace and site backups retain the resources and translated instructions without granting new approval at the destination.

The release removes unused private Auth methods and an unnecessary service dependency, simplifies an ORM type check and locks the browser test tool versions. Authentication protocols, Framework code, theme colors and database migrations are unchanged.

Use the secured-FPM GUI or the documented CLI updater. Installation-specific configuration and the selection of optional modules are preserved. Docker uses `ghcr.io/kmihalj/simbioza:0.3.28` with existing persistent volumes. See [installation](installation_en.md), [module inventory](module-dependencies_en.md) and [Docker](docker_en.md).
