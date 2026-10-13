# Maintaining the installation guides

[Croatian version](maintaining_installation_guides_hr.md)

The maintained sources are `installation_en.md`/`installation_hr.md` and the
separate, complete `installation_mod_php_*`, `installation_fpm_*` and
`installation_fpm_secured_*` Markdown pairs. Each installation starts from
scratch and includes its own commands; prerequisite fragments in another
installation guide are not required.

The installed User guides have an overview page, a mod_php page and a PHP-FPM
page. The PHP-FPM page contains two independent complete walkthroughs, with
`#ordinary-fpm` and `#secured-fpm` anchors. Both its languages and the full
starter-workspace archive are generated from the same Markdown sources.

## Edit and verify

From a development checkout with Composer dependencies installed:

```bash
php scripts/sync_installation_guides.php --apply
php scripts/sync_installation_guides.php --check
```

Only `--apply` requires Pandoc (`brew install pandoc` or
`sudo apt-get install pandoc`). It renders public Markdown, updates four
encrypted guide archives and preserves document identities, permissions,
unrelated pages and attachment blobs. It does not connect to an application
database or copy private configuration. `--check` is offline and needs no
Pandoc; CI uses it to reject source/package drift.

Do not edit the encrypted HTML payload separately. Edit the EN/HR Markdown
pair, rebuild, check the diff and run the guide/installation tests. These
are development commands, not part of an end-user installation.

## Installed sites and tagged releases

GitHub `main` can contain newer documentation than a site's installed package.
Changing Markdown does not directly change its stored pages. Application
updates refresh the bundled pages through `BundledAssetsUpdater` without
replacing unrelated pages or their ACLs. A fresh installation imports the
guide packages shipped with its chosen release.

For a documentation-only development refresh, publish the rebuilt packages
and refresh the development site's managed guides using its current services.
Do not assume this also deploys them to another site. An older release archive
remains a historical snapshot until a new release ships the rebuilt assets;
do not move an application tag merely to make its embedded documentation match
`main`.
