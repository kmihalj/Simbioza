<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- This CLI audit defines and invokes local helpers.

/**
 * HR: Provjerava da svaki statički `__()` ključ aplikacije i instaliranih
 *     modula postoji u zasebnim hrvatskim i engleskim katalozima. Provjerava i
 *     dinamičke nazive modula, lokalizirane stavke menija te sprječava povratak
 *     izvornog, jezično ovisnog pregledničkog gumba za odabir datoteke.
 *
 * EN: Verifies every static `__()` key from the application and installed
 *     modules exists in the separate Croatian and English catalogues. It also
 *     checks dynamic module labels, localized menu items, and prevents the
 *     locale-dependent native browser file picker from returning.
 */

declare(strict_types=1);

/**
 * HR: Vraća statičke literalne i spojene literalne ključeve jednog PHP izvora.
 * EN: Returns static literal and concatenated-literal keys from one PHP source.
 *
 * @return list<string>
 */
function staticTranslationKeys(string $source): array
{
    $tokens = token_get_all($source);
    $keys = [];
    $count = count($tokens);
    for ($index = 0; $index < $count; ++$index) {
        $token = $tokens[$index];
        if (!is_array($token)) {
            continue;
        }

        if ($token[0] !== T_STRING) {
            continue;
        }

        if ($token[1] !== '__') {
            continue;
        }

        $cursor = $index + 1;
        while ($cursor < $count && isIgnorableTranslationToken($tokens[$cursor])) {
            ++$cursor;
        }

        if (($tokens[$cursor] ?? null) !== '(') {
            continue;
        }

        $literal = '';
        $valid = true;
        for (++$cursor; $cursor < $count; ++$cursor) {
            $part = $tokens[$cursor];
            if ($part === ')') {
                break;
            }

            if ($part === '.') {
                continue;
            }

            if (isIgnorableTranslationToken($part)) {
                continue;
            }

            if (!is_array($part) || $part[0] !== T_CONSTANT_ENCAPSED_STRING) {
                $valid = false;
                break;
            }

            $literal .= translationLiteralValue($part[1]);
        }

        if ($valid && $literal !== '') {
            $keys[] = $literal;
        }
    }

    return $keys;
}

/**
 * HR: Prepoznaje tokene koji ne mijenjaju vrijednost spojenog literala.
 * EN: Recognizes tokens that do not change a concatenated literal value.
 */
function isIgnorableTranslationToken(mixed $token): bool
{
    return is_array($token)
    && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
}

/**
 * HR: Dekodira PHP literal bez evaluacije koda.
 * EN: Decodes a PHP literal without evaluating code.
 */
function translationLiteralValue(string $literal): string
{
    $quote = $literal[0] ?? '';
    $value = substr($literal, 1, -1);

    return $quote === "'"
    ? str_replace(["\\\\", "\\'"], ["\\", "'"], $value)
    : stripcslashes($value);
}

/**
 * HR: Učitava katalog i odbija datoteku koja ne vraća polje.
 * EN: Loads a catalogue and rejects a file that does not return an array.
 *
 * @return array<string, mixed>
 */
function translationCatalogue(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Missing translation catalogue: ' . $path);
    }

    $catalogue = require $path;
    if (!is_array($catalogue)) {
        throw new RuntimeException('Translation catalogue must return an array: ' . $path);
    }

    return $catalogue;
}

/**
 * HR: Vraća PHP izvore komponente koji mogu sadržavati korisničko sučelje.
 * EN: Returns component PHP sources that can contain user interface text.
 *
 * @return list<string>
 */
function translationSourceFiles(string $root): array
{
    $paths = [];
    foreach (['src', 'views'] as $sourceDirectory) {
        $path = $root . '/' . $sourceDirectory;
        if (!is_dir($path)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $paths[] = $file->getPathname();
            }
        }
    }

    sort($paths);

    return $paths;
}

/**
 * HR: Pronalazi vidljiva izvorna file polja čiji tekst određuje jezik preglednika/OS-a.
 * EN: Finds visible native file inputs whose text is controlled by the browser/OS locale.
 *
 * @return list<string>
 */
function visibleNativeFileInputs(string $source): array
{
    preg_match_all('/<input\b[^>]*\btype\s*=\s*(["\'])file\1[^>]*>/is', $source, $matches);
    $visible = [];
    foreach ($matches[0] as $tag) {
        if (preg_match('/\bclass\s*=\s*(["\'])[^"\']*\b(?:visually-hidden|d-none)\b[^"\']*\1/is', $tag) !== 1) {
            $visible[] = preg_replace('/\s+/', ' ', trim($tag)) ?? trim($tag);
        }
    }

    return $visible;
}

/**
 * HR: Skuplja hrvatske kanonske ključeve lokaliziranih stavki menija.
 * EN: Collects Croatian canonical keys from localized menu items.
 *
 * @return list<string>
 */
function localizedMenuKeys(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $keys = [];
    if (
        isset($value['label'])
        && is_array($value['label'])
        && is_string($value['label']['hr'] ?? null)
        && is_string($value['label']['en'] ?? null)
        && $value['label']['hr'] !== $value['label']['en']
    ) {
        $keys[] = $value['label']['hr'];
    }

    foreach ($value as $child) {
        $keys = [...$keys, ...localizedMenuKeys($child)];
    }

    return $keys;
}

/**
 * HR: Izvorno dohvaća kanonske hrvatske oznake Setup provjera iz njihovih poziva.
 * EN: Statically extracts canonical Croatian Setup-check labels from their calls.
 *
 * @return list<string>
 */
function setupDiagnosticKeys(string $source): array
{
    preg_match_all(
        '/\$this->(?:check|pathCheck|modeCheck)\(\s*\'[^\']+\'\s*,\s*\'((?:\\\\\'|[^\'])+)\'\s*,/s',
        $source,
        $matches,
    );

    return array_map(
        static fn(string $value): string => str_replace(["\\\\", "\\'"], ["\\", "'"], $value),
        $matches[1],
    );
}

$appRoot = dirname(__DIR__);
$vendorRoot = $appRoot . '/vendor/aaieduhr';
$moduleRoots = [
    ...(glob($vendorRoot . '/heartphrame-module-*', GLOB_ONLYDIR) ?: []),
    ...(glob($vendorRoot . '/simbioza-module-*', GLOB_ONLYDIR) ?: []),
];
if ($moduleRoots === []) {
    fwrite(STDERR, "[FAIL] No installed HeartPhrame modules were found.\n");
    exit(1);
}

$componentRoots = [$appRoot, ...$moduleRoots];
$failed = false;
sort($componentRoots);
$aggregate = ['en' => [], 'hr' => []];
foreach ($componentRoots as $componentRoot) {
    $keys = [];
    foreach (translationSourceFiles($componentRoot) as $sourcePath) {
        $source = file_get_contents($sourcePath);
        if (!is_string($source)) {
            continue;
        }

        $keys = [...$keys, ...staticTranslationKeys($source)];
        foreach (visibleNativeFileInputs($source) as $tag) {
            $failed = true;
            fwrite(STDERR, sprintf(
                "[FAIL] %s exposes a browser-localized native file input:\n  - %s\n",
                $sourcePath,
                $tag,
            ));
        }
    }

    $keys = array_values(array_unique($keys));
    sort($keys);
    foreach (['en', 'hr'] as $locale) {
        $catalogue = translationCatalogue($componentRoot . '/lang/' . $locale . '.php');
        $aggregate[$locale] += $catalogue;
        $missing = array_values(array_diff($keys, array_keys($catalogue)));
        if ($missing === []) {
            continue;
        }

        $failed = true;
        fwrite(
            STDERR,
            sprintf("[FAIL] %s %s is missing %d static keys:\n", basename($componentRoot), $locale, count($missing)),
        );
        foreach ($missing as $key) {
            fwrite(STDERR, '  - ' . str_replace("\n", '\\n', $key) . "\n");
        }
    }
}

require_once $appRoot . '/vendor/autoload.php';
$dynamicKeys = setupDiagnosticKeys((string)file_get_contents($appRoot . '/src/Setup/SetupDiagnostics.php'));
foreach ((new App\Module\ModuleCatalog())->definitions() as $definition) {
    $dynamicKeys[] = $definition['label_hr'];
}
foreach (glob($appRoot . '/resources/config/menu/*.json') ?: [] as $path) {
    $payload = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $dynamicKeys = [...$dynamicKeys, ...localizedMenuKeys($payload)];
}
$dynamicKeys = array_values(array_unique($dynamicKeys));
sort($dynamicKeys);
foreach (['en', 'hr'] as $locale) {
    $missing = array_values(array_diff($dynamicKeys, array_keys($aggregate[$locale])));
    if ($missing === []) {
        continue;
    }

    $failed = true;
    fwrite(STDERR, sprintf("[FAIL] Aggregate %s catalogue is missing %d dynamic UI keys:\n", $locale, count($missing)));
    foreach ($missing as $key) {
        fwrite(STDERR, '  - ' . str_replace("\n", '\\n', $key) . "\n");
    }
}

foreach (['en', 'hr'] as $locale) {
    $otherLocale = $locale === 'en' ? 'hr' : 'en';
    $missing = array_values(array_diff(array_keys($aggregate[$otherLocale]), array_keys($aggregate[$locale])));
    if ($missing === []) {
        continue;
    }

    $failed = true;
    fwrite(STDERR, sprintf("[FAIL] Aggregate %s catalogue is missing %d keys present in %s.\n", $locale, count($missing), $otherLocale));
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "[OK] Application and module catalogues cover static, dynamic, and menu UI keys; file pickers are localized.\n");
