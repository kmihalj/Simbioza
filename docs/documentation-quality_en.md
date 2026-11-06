# Maintaining documentation

The project keeps developer references in Git and user instructions inside
Simbioza. Both must describe the behavior of the installed code. Framework is
an upstream dependency; these maintenance tools do not change its source.

## Source of the user manual

The chapter registry is `resources/installation/manual-sources.json`. Most
editable chapters are `docs/user-guide/*_en.md` and `*_hr.md`; shared overview,
installation and gallery sources remain under `docs/`. Referenced public assets
are recorded in `manual-assets.json`, with screenshots under
`docs/user-guide/images/`. Keep English and Croatian separate.
Use the corresponding interface language in every screenshot, and verify that
the image actually shows the described action and result. Examples must remain
portable: no links to development surveys, private workspaces or local files.

The same chapters are packaged as the **User guides** Workspace for Wiki
installations. Without Wiki, the **Guides** menu renders that bundled content
as read-only HTML with chapter navigation. Do not maintain a second manual for
those smaller installations or edit generated package records by hand.

For a new chapter, add both language sources and its placement to the source
registry; register new public images with their checksums in the asset registry.
The generation step requires the installed development dependencies and Pandoc.
After editing sources, rebuild and verify the generated manual:

```bash
php scripts/sync_user_guides.php --apply
php scripts/sync_user_guides.php --check
```

`--apply` updates generated release artifacts from the tracked sources;
`--check` detects drift. This source-maintenance step does not publish a release
or authorize replacing an administrator's live content. Review its diff before
using the normal controlled installation/upgrade procedure. The
[HTML Editor form-field integration](html-editor-forms_en.md) is maintained in
the module repository for developers; it is not part of the installed user manual.

## Language and example rules

- `README.md` and `docs/*_en.md` use English; `README_hr.md` and `*_hr.md` use Croatian.
- Code comments and method PHPDoc use a complete `EN:` block, a blank comment
  line, then a complete `HR:` block. Type annotations stay outside that prose.
- Complete PHP examples start with `<?php` and must pass syntax validation.
  Clearly label configuration entries and layout insertion points as fragments.
- JSON examples must be valid objects/lists. Inert `_documentation.en` and
  `_documentation.hr` descriptions in distribution examples are not settings.
- Never copy a `.dist` example over live private settings. Preserve existing
  modules, application services and values when showing an addition.
- Do not include passwords, tokens, private identities or real response data.

## Automated checks

Run these in an isolated source checkout with its development dependencies:

```bash
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
php scripts/audit_module_translations.php
php scripts/sync_user_guides.php --check
```

They check marked PHP comments/PHPDoc, required dependency descriptions, paired
EN/HR documents, local links and heading anchors, complete PHP/JSON examples,
translation-key coverage and generated manual consistency. They do not establish
that an external website exists, that a screenshot matches a new UI, or that a
code example satisfies a custom application's permission rules. Review those
against the real code and relevant user flow.

`php scripts/normalize_bilingual_comments.php` is a dry run. Its explicit
`--write` option is for source maintenance only: executable-token hashes and
PHPDoc annotation signatures must stay unchanged. Review the resulting diff;
the tool excludes Framework, vendor packages, private settings and runtime data.

Use [installation](installation_en.md), [configuration](configuration_en.md),
[Docker](docker_en.md) and [end-to-end checks](end-to-end-testing_en.md) as current
operating references. Release notes describe their historical release, not the
current installation procedure. Keep development plans and one-off acceptance
logs outside the published manual.
