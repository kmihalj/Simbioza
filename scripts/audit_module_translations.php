<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- This CLI audit defines and invokes local helpers.

/**
 * HR: Provjerava da svaki statički `__()` ključ instaliranih modula postoji u
 *     zasebnim hrvatskim i engleskim katalozima. Ne izvršava izvorni kod.
 *
 * EN: Verifies every static `__()` key from installed modules exists in the
 *     separate Croatian and English catalogues. Source code is never executed.
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

$vendorRoot = dirname(__DIR__) . '/vendor/aaieduhr';
$moduleRoots = glob($vendorRoot . '/heartphrame-module-*', GLOB_ONLYDIR);
if (!is_array($moduleRoots) || $moduleRoots === []) {
    fwrite(STDERR, "[FAIL] No installed HeartPhrame modules were found.\n");
    exit(1);
}

$failed = false;
sort($moduleRoots);
foreach ($moduleRoots as $moduleRoot) {
    $keys = [];
    foreach (['src', 'views'] as $sourceDirectory) {
        $path = $moduleRoot . '/' . $sourceDirectory;
        if (!is_dir($path)) {
            continue;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if (!$file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (is_string($source)) {
                $keys = [...$keys, ...staticTranslationKeys($source)];
            }
        }
    }

    $keys = array_values(array_unique($keys));
    sort($keys);
    foreach (['en', 'hr'] as $locale) {
        $catalogue = translationCatalogue($moduleRoot . '/lang/' . $locale . '.php');
        $missing = array_values(array_diff($keys, array_keys($catalogue)));
        if ($missing === []) {
            continue;
        }

        $failed = true;
        fwrite(
            STDERR,
            sprintf("[FAIL] %s %s is missing %d static keys:\n", basename($moduleRoot), $locale, count($missing)),
        );
        foreach ($missing as $key) {
            fwrite(STDERR, '  - ' . str_replace("\n", '\\n', $key) . "\n");
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "[OK] Installed module translation catalogues cover every static __() key.\n");
