<?php

declare(strict_types=1);

// EN: Checks resolved release packages against the application's declared stable constraints, not one blanket module version.
// This read-only probe also works through container stdin and never loads private configuration.
//
// HR: Provjerava razriješene pakete izdanja prema stabilnim uvjetima aplikacije, ne jednoj verziji svih modula.
// Ova provjera samo čita, radi i kroz stdin kontejnera te ne učitava privatnu konfiguraciju.
if (PHP_SAPI !== 'cli') {
    exit(2);
}
$root = getenv('HPH_APP_PATH') ?: dirname(__DIR__);
$manifest = json_decode((string)file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$lock = json_decode((string)file_get_contents($root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
$constraints = array_replace($manifest['extra']['simbioza']['optional-modules'] ?? [], $manifest['require'] ?? []);
$checked = 0;
foreach ($lock['packages'] ?? [] as $package) {
    $name = $package['name'];
    if (!str_starts_with($name, 'aaieduhr/')) {
        continue;
    }
    $constraint = $constraints[$name] ?? '';
    $version = ltrim((string)$package['version'], 'v');
    if (preg_match('/\A\^?(\d+)\.(\d+)\.(\d+)\z/D', $constraint, $match) !== 1
        || preg_match('/\A\d+\.\d+\.\d+\z/D', $version) !== 1) {
        throw new RuntimeException('Missing stable release constraint/version: ' . $name);
    }
    $minimum = $match[1] . '.' . $match[2] . '.' . $match[3];
    $upper = (int)$match[1] > 0 ? ((int)$match[1] + 1) . '.0.0'
        : ((int)$match[2] > 0 ? '0.' . ((int)$match[2] + 1) . '.0' : '0.0.' . ((int)$match[3] + 1));
    if (version_compare($version, $minimum, '<') || version_compare($version, $upper, '>=')
        || (!str_starts_with($constraint, '^') && $version !== $minimum)) {
        throw new RuntimeException('Resolved release dependency does not match its constraint: ' . $name);
    }
    ++$checked;
}
if ($checked === 0) {
    throw new RuntimeException('No release packages were checked.');
}
printf("Verified %d stable release dependencies.\n", $checked);
