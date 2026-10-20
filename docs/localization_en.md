# Localization

Simbioza uses Croatian as the canonical source language of its interface.
User-facing text is written in Croatian in code and serves as the same lookup
key in every language file. Translators may work from an English reference;
that does not introduce a second translation step at runtime.
Technical identifiers such as `can_view` and date-format settings stay stable
codes because they are not interface sentences.

## Configuration

The localization defaults are defined by the application `config/app.php`.
Installation choices are stored in `installation.php` and the complete language
registry in `languages.php`, using `HPH_CONFIG_PATH` and its ordered override
layers. GUI and CLI read the same effective settings and save in the last
declared layer; `.languages.lock` serializes changes. The highest existing
`languages.php` is a complete registry, not a partial key merge. See the
[configuration guide](configuration_en.md#installation-module-selection-and-languages)
for permissions, manual edits, and resource paths.

Croatian is the default in a standard new installation, but the installer can
select another primary language and even omit Croatian. The selected primary
language becomes the site's fallback; users can switch among enabled locales.
The following snippet illustrates the usual Croatian configuration:

```php
return [
    'localization' => [
        'locale' => 'hr',
        'fallback_locale' => 'hr',
        'detect_browser_locale' => true,
        'translations_dir' => __DIR__ . '/../lang',
    ],
];
```

With `detect_browser_locale` enabled (the default when an installation does
not override it), the first enabled language matching the browser's
`Accept-Language` preference is selected. Regional preferences such as `fr-CH`
can use the installed `fr` pack. If none matches, the application's primary
language remains the default. A language chosen explicitly in the menu takes
precedence and is remembered in this browser for one year. The preference
cookie is limited to the application's base path, so `/demo` and `/hfc` do not
change one another's language. Clearing site cookies restores browser-language
detection. An installation may explicitly set `detect_browser_locale` to
`false` to always start from its primary language until the user chooses one.

## Packaged language files

Application/module source catalogues are PHP files returning translation arrays.
They are code resources in the `translations_dir`/module `lang` directories,
named after the locale (e.g. `en.php`, `hr.php`). Developers edit these as part
of code changes. Administrators install additional or replacement packs as JSON
under `data/languages/catalogs/`, never as PHP in application `lang/`.
See [runtime storage and backup](#runtime-storage-precedence-and-backup) below.

### Example `lang/en.php`

```php
<?php

return [
    'Dobro došli!' => 'Welcome!',
    'Pozdrav, :name!' => 'Hello, :name!',
];
```

## Usage

### Using the Helper Function

The `__()` and `__e()` (with escaping) helper functions are available globally
and can be used anywhere in your application (views, controllers, etc.).

```php
echo __('Dobro došli!');
echo __e('Pozdrav, :name!', ['name' => 'John']);
```

### Placeholders

You can define placeholders in your translations using `:name` syntax.
The second argument of the `__()` function accepts an associative array
to replace these placeholders.

The translator also supports case variants:
- `:name` -> `John`
- `:NAME` -> `JOHN`
- `:Name` -> `John` (uppercase first)

### In Controllers

The `TranslatorInterface` is automatically injected into the
`AbstractController`.

```php
namespace App\Controller;

use HeartPhrame\Controller\AbstractController;
use Psr\Http\Message\ResponseInterface;

class HomeController extends AbstractController
{
    public function index(): ResponseInterface
    {
        $message = $this->translator->trans('Dobro došli!');
        // ...
    }
}
```

### In Views

The `translator` service is also available as a global variable in all view
templates. However, using the `__()` or `__e()` helper is recommended for
brevity.

```php
<h1><?= __('Dobro došli!') ?></h1>
```

## Changing Locale at Runtime

You can change the current locale using the `TranslatorInterface`:

```php
$translator->setLocale('de');
```

# Modules

Translations are loaded from the module automatically if the module has
a directory named as the translations directory in the root of the application.
For example, if the option is set to
`'translations_dir' => __DIR__ . '/../lang'` like in the example above,
translations from the `lang` directory of the module will be loaded
automatically.

## Consolidated language packs

Adding a locale does not require editing every module. Released packs are
listed in the [public language repository](https://github.com/kmihalj/simbioza-languages):

```bash
vendor/bin/hph languages list
vendor/bin/hph languages available
vendor/bin/hph languages install de
vendor/bin/hph languages disable de
vendor/bin/hph languages enable de
vendor/bin/hph languages update
vendor/bin/hph languages remove de
```

For a new translation, Simbioza can collect the application and every
installed module key into one JSON pack:

```bash
vendor/bin/hph languages template de --source=en --output=/tmp/de.json
vendor/bin/hph languages validate /tmp/de.json
vendor/bin/hph languages add /tmp/de.json
```

JSON keys are Croatian source strings from the code, not their English
translations. Translate only values inside `translations`. `source_locale`
records whether the translator used English or Croatian reference text; at
runtime, each locale is looked up directly by its Croatian key, with no
HR → EN → target-language chain. Do not change keys or placeholders such as
`:name`, `%s`, or `{{value}}`. `validate` compares all
keys and placeholders with the source locale. An incomplete pack is rejected
unless `--allow-missing` is deliberately supplied, and an existing locale is
not overwritten without `--replace`.

`add` installs one consolidated JSON catalogue at
`data/languages/catalogs/<locale>.json` and enables the locale in the private
installation configuration. Installed values take precedence over packaged
application and module translations. Every pack must include a safe SVG flag.
The installed pack also supplies its native display name and flag to the
application localization registry. The language selector therefore remains
complete with private `HPH_CONFIG_PATH/menu/menu.php`;
explicit labels and paths in that file still take precedence as overrides.
The last enabled language cannot be disabled or removed. In GUI Setup,
installed languages remain in the table and published languages not yet
installed appear in a searchable multi-select control. One constrained Setup
request installs all selected languages.

When application or module strings change, run `php scripts/sync.php
/path/to/Simbioza --version=YYYY.MM.DD.N` in the language repository. It writes
only new and changed source keys to `pending/<locale>.json` and never overwrites
translated values. Review the pending keys, update the pack, remove its pending
file after review, run `languages validate`, and publish a newer pack revision.
The application's next update refreshes installed repository packs whose
published digest changed; `languages update` performs the check on demand.
German is published with AI-assisted translations and should still receive
native-speaker review. The published French, Spanish, and Italian translations
were also prepared with the ChatGPT/Codex AI agent.

### Dates and times

User-facing dates and times use ICU rules for the selected interface locale:
month names, field order, and 12/24-hour conventions therefore vary by
language. Stored timestamps, API fields, HTML machine-readable dates, and logs
keep their stable formats. The installation time zone determines the displayed
local time; switching language does not alter the underlying instant. A
Cyrillic locale such as `sr-cyrl` can be packaged in UTF-8 in the same way.

## Checking translation-only updates

In Setup → Languages, **Check language updates** refreshes the public catalogue
without installing code or language files. The table distinguishes three sources:

- **Bundled translations** show the installed application version, for example
  `Simbioza 0.3.13`. They are recognized immediately on a fresh installation and
  after an application update; no CLI language installation is needed.
- **Language pack** shows the actual revision installed from the repository.
- **Manual translation** has no verified repository revision. It is never
  automatically replaced or reported as an unchanged published pack.

The available pack revision uses its own numbering, independently of Simbioza.
The read-only `resources/localization/bundled-languages.json` records the
original repository baseline for bundled languages. It is release metadata,
not a user setting or a receipt claiming that the repository pack was installed.
Its baseline stays stable across application updates so a later translation-only
publication remains detectable, including a digest change under the same revision.
Installing the offered update through **Update** records the real pack revision.
If the network
check fails, the GUI does not report the stale cached catalogue as a successful
check.

Language installation, replacement, activation and removal do not change code
and do not require the privileged package helper. GUI actions work on mod_php,
FPM and secure FPM when the data directory and final configuration layer are
writable. Existing administrator and CSRF guards still apply. If permissions
prevent GUI changes, it displays these commands; run them from the Simbioza root
as an authorized runtime/deploy user, with the site's same `HPH_CONFIG_PATH`:

```bash
vendor/bin/hph languages available --refresh
vendor/bin/hph languages update
```

For a manual translation, **Download language pack** offers a verified
replacement through the GUI and asks for confirmation. The CLI equivalent is:

```bash
vendor/bin/hph languages install hr --replace
```

Changing only translation values requires no application/module tag or Composer
release. Publish the language JSON and its updated SHA-256 in the language
repository's manifest; a new language revision is recommended. Detection uses
the digest even when the revision string is unchanged. Pack checksum,
placeholder/completeness validation and preservation of the prior enabled state
still apply. A manually installed JSON pack clears its previous repository
revision: a custom translation is not reported as an unchanged published pack.

## Runtime storage, precedence and backup

| Location | Purpose |
| --- | --- |
| Application/module `lang/<locale>.php` | Read-only packaged translations, replaced with code updates |
| `data/languages/catalogs/<locale>.json` | Installed, consolidated translations; never PHP source |
| `data/languages/flags/<locale>.svg` | Validated installed SVG flag |
| `data/languages/repository-versions.json` | Published revision and source-pack digest, not configuration |
| `HPH_CONFIG_PATH/languages.php` | Locale names and registry in the final declared layer |
| `HPH_CONFIG_PATH/installation.php` | Enabled locales and primary locale in the final declared layer |

`App\Localization\RuntimeTranslator` extends the existing Framework translator
in the host application. The Framework is not modified. Its package directory
remains named `lang`, so module lookup is unchanged. Exact installed keys win;
missing keys still use the current application/module catalogue and the existing
fallback locale. New requests read replaced JSON immediately, without PHP
OPcache invalidation. A malformed installed catalogue raises an error rather
than silently falling back to a stale translation.

Install through JSON packs and the GUI/CLI, not by copying PHP to the data
directory. The generated runtime document has this small, data-only shape:

```json
{
  "format": "simbioza-runtime-translations",
  "version": 1,
  "locale": "de",
  "translations": {"Jezici": "Sprachen"}
}
```

This is an internal catalogue example, not a complete distributable language
pack. Editing an already installed catalogue changes its values on the next
request; adding a new locale also requires the registry and activation policy.
Prefer `languages template`, `validate`, and `add` so names, placeholders and
flags are checked together. Runtime files are limited to 2 MiB each. Subordinate
symlink targets and invalid locale/path identifiers are rejected.

GUI and CLI use the final configuration `.languages.lock`, followed by
`data/languages/.packs.lock`. Installation/removal roll back all changed language
resources and private language configuration if publication fails. Removal
never deletes packaged application/module PHP files. Updates keep the prior
enabled state; the last enabled language remains protected.

Application-settings backup schema 3 includes locale registry, installed JSON,
validated flags and revision records. Active/primary locales remain in the
application settings. Site/component restore uses destination-local paths, not
source paths, and never imports executable PHP catalogues. Explicit replace
removes absent runtime packs; merge retains local-only locales. For an incoming
locale, a bundled-only catalogue or flag replaces any destination runtime override,
including during merge. If a later
provider or database commit fails, configuration, JSON, flags and revisions are
rolled back. Cache, downloads, templates and lock files are not backed up.
Page/workspace-only backups do not carry installation languages.

Keep the data and configuration mounts across image/code replacement; do not
make application `lang/` writable for language administration. Simbioza 0.3.0
uses fresh installation: no old PHP-pack migration is part of runtime startup.
