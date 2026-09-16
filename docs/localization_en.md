# Localization

HeartPhrame provides a simple yet powerful localization system to translate
your application into multiple languages.

## Configuration

The localization system is configured in your `config/app.php` file:

Croatian is the site's default and fallback language. Browser detection and a
manual language selection can still activate any supported locale.

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
    'welcome' => 'Welcome to our application!',
    'greet' => 'Hello, :name!',
    'auth' => [
        'login' => 'Please log in',
    ],
];
```

## Usage

### Using the Helper Function

The `__()` and `__e()` (with escaping) helper functions are available globally
and can be used anywhere in your application (views, controllers, etc.).

```php
echo __('welcome');
echo __e('greet', ['name' => 'John']);
echo __('auth.login'); // Supports dot notation for nested arrays
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
        $message = $this->translator->trans('welcome');
        // ...
    }
}
```

### In Views

The `translator` service is also available as a global variable in all view
templates. However, using the `__()` or `__e()` helper is recommended for
brevity.

```php
<h1><?= __('welcome') ?></h1>
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

Adding a locale does not require editing every module. Simbioza can collect the
application and every bundled module key into one JSON pack:

```bash
vendor/bin/hph languages list
vendor/bin/hph languages template de --source=en --output=/tmp/de.json
vendor/bin/hph languages validate /tmp/de.json
vendor/bin/hph languages add /tmp/de.json
```

Translate only values inside the JSON `translations` object. Do not change keys
or placeholders such as `:name`, `%s`, or `{{value}}`. `validate` compares all
keys and placeholders with the source locale. An incomplete pack is rejected
unless `--allow-missing` is deliberately supplied, and an existing locale is
not overwritten without `--replace`.

`add` installs one consolidated `lang/<locale>.php` file and enables the locale
in the private installation configuration. The application-level file takes
precedence over module translations, so the pack is complete immediately and
the modules need no edits. A later release can add keys; create a new template,
carry existing translations forward, translate the new values, validate it,
and install it with `--replace`.

The included German `de` locale is only a complete example pack and is not
added or enabled automatically. It has the same key count and preserves
placeholders. A native-language review is still recommended before publishing
any new translation in production.
