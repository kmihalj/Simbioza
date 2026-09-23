# Localization

Simbioza uses Croatian as the canonical source language of its interface.
User-facing text is written in Croatian in code and serves as the same lookup
key in every language file. Translators may work from an English reference;
that does not introduce a second translation step at runtime.
Technical identifiers such as `can_view` and date-format settings stay stable
codes because they are not interface sentences.

## Configuration

The localization system is configured in your `config/app.php` file:

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

## Language Files

Language files are simple PHP files that return an array of translations.
They should be located in the directory specified by `translations_dir`,
named after the locale (e.g., `en.php`, `de.php`).

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

`add` installs one consolidated `lang/<locale>.php` file and enables the locale
in the private installation configuration. The application-level file takes
precedence over module translations. Every pack must include a safe SVG flag.
The installed pack also supplies its native display name and flag to the
application localization registry. The language selector therefore remains
complete when an installation preserves an older private `config/menu.php`;
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
