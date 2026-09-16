<?php

declare(strict_types=1);

// HR: CI koristi privremeni manifest sa svim opcionalnim modulima i alatima.
//     Produkcijski composer.json zato ostaje minimalan i bez dev ovisnosti.
// EN: CI uses a temporary manifest with every optional module and QA tool.
//     The production composer.json therefore stays minimal and dev-free.

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is CLI-only.\n");
    exit(2);
}

$root = dirname(__DIR__);
$source = $root . '/composer.json';
$target = $root . '/composer.ci.json';
$manifest = json_decode((string)file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
if (!is_array($manifest)) {
    throw new RuntimeException('The production Composer manifest is invalid.');
}

$extra = is_array($manifest['extra'] ?? null) ? $manifest['extra'] : [];
$simbioza = is_array($extra['simbioza'] ?? null) ? $extra['simbioza'] : [];
$optional = is_array($simbioza['optional-modules'] ?? null) ? $simbioza['optional-modules'] : [];
$tools = is_array($simbioza['development-tools'] ?? null) ? $simbioza['development-tools'] : [];
$require = is_array($manifest['require'] ?? null) ? $manifest['require'] : [];
foreach ($optional as $package => $constraint) {
    if (!is_string($package) || !is_string($constraint)) {
        throw new RuntimeException('The optional-module catalog is invalid.');
    }
    $require[$package] = $constraint;
}
foreach ($tools as $package => $constraint) {
    if (!is_string($package) || !is_string($constraint)) {
        throw new RuntimeException('The development-tool catalog is invalid.');
    }
}
ksort($require, SORT_STRING);
ksort($tools, SORT_STRING);
$manifest['name'] = 'aaieduhr/simbioza-ci';
$manifest['require'] = $require;
$manifest['require-dev'] = $tools;

$json = json_encode(
    $manifest,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
) . "\n";
if (file_put_contents($target, $json, LOCK_EX) === false) {
    throw new RuntimeException('Unable to write the temporary CI Composer manifest.');
}

fwrite(STDOUT, "Prepared composer.ci.json from stable package catalogs.\n");
