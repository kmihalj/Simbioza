# Documentation and comment checks

These rules apply to the 0.3.17 application and its 18 public modules. Framework
is upstream code and is not rewritten by these tools.

## Language and example rules

`README.md` and `docs/*_en.md` contain English prose; `README_hr.md` and
`docs/*_hr.md` contain Croatian prose. Code comments and method PHPDoc use one
complete `EN:` block, a blank comment line, then one complete `HR:` block.
Do not alternate languages or put both on one line. Type annotations and tool
directives stay outside the translated prose. Translation dictionaries and
multilingual JSON menu labels are application data, not documentation prose.

Complete PHP examples start with `<?php`; their syntax is checked without
execution. Array entries and layout insertion points are fragments, not
replacement application files. JSON examples are complete valid objects/lists.
Preserve existing host settings when adding options or enabled modules.

JSON `.dist` examples explain their options in separate, non-empty
`_documentation.en` then `_documentation.hr` sections. The script-registry example
uses equivalent `_description_en` then `_description_hr` sections. These are
inert descriptions, not operational options. Copy only needed examples to their
documented `HPH_CONFIG_PATH/<module>/` target, never over private settings.
`.dist` is not loaded automatically. See [configuration](configuration_en.md).

## Run the checks

From the application source checkout:

```bash
php scripts/audit_bilingual_phpdoc.php
php scripts/audit_module_documentation.php
php scripts/audit_module_translations.php
php scripts/normalize_bilingual_comments.php
```

The first checks actual marked PHP comments throughout Git-visible source,
including templates/tests, and bilingual method documentation in production
source. Long adjacent PHPDoc is valid. Strings containing examples are not
comments and are not rewritten. The second checks required Composer dependencies
early in README, document pairs, relative file links, heading anchors, complete
PHP/JSON examples and starting-example descriptions. Historical notices use the
application version only for application release notes. Module releases have
independent version numbers; their PHP/JSON examples are still checked. It does not fetch remote
websites or prove that every example fits a custom host. The third checks
application/module translation-key coverage.

The last command is a **dry run**. For source maintenance only, add `--write`
to reformat eligible PHP comments. Every edit requires identical executable-token
hashes and preserved PHPDoc annotation signatures; reports go to `build/a5/`.
Framework, vendor packages, ignored private settings and data are excluded.
Review the diff and rerun checks afterwards. JavaScript and CI comments follow
the same language rule, but this PHP formatter does not parse those languages.

## Current instructions and history

[Installation](installation_en.md), [configuration](configuration_en.md) and
[Docker](docker_en.md) describe the current clean-install layout. Older release
notes retain original details under explicit historical notices; they are not
current installation/upgrade instructions. The
[initial pre-release audit](pre-release-audit-0.3.0_en.md) records the initial
acceptance checks; [0.3.17 release notes](release-0.3.17_en.md) describe the current
publication. Passing these checks is not release approval.
