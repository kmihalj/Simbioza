<?php

declare(strict_types=1);

// HR: Priprema javne jezične pakete prije GUI instalacije bez FPM helpera.
// EN: Prepares published language packs before GUI installation without an FPM helper.

use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Module\ModuleCatalog;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$command = $argv[1] ?? '';
$locales = [];
foreach (array_slice($argv, 2) as $argument) {
    if (str_starts_with($argument, '--locales=')) {
        $locales = explode(',', substr($argument, strlen('--locales=')));
    }
}

if ($command !== 'prepare' || $locales === [] || array_filter(
    $locales,
    static fn(string $locale): bool => preg_match('/\A[a-z]{2}(?:-[a-z0-9]+)*\z/D', $locale) !== 1,
) !== []) {
    fwrite(STDERR, "HR: Uporaba: php scripts/installation_languages.php prepare --locales=de,es,fr,it\n");
    fwrite(STDERR, "EN: Usage: php scripts/installation_languages.php prepare --locales=de,es,fr,it\n");
    exit(2);
}

$repository = new LanguageRepository($root);
$available = $repository->available(true);
$manager = new RepositoryLanguageManager(
    $repository,
    new LanguagePackManager($root, new ModuleCatalog()),
    $root,
);
$status = array_column($manager->status(), null, 'locale');
foreach (array_unique($locales) as $locale) {
    if (!isset($available[$locale])) {
        throw new RuntimeException('Language is not published: ' . $locale);
    }

    if (!empty($status[$locale]['installed']) && empty($status[$locale]['update_available'])) {
        fwrite(STDOUT, $locale . ": already prepared\n");
        continue;
    }

    $manager->install($locale, !empty($status[$locale]['installed']));
    fwrite(STDOUT, $locale . ": prepared\n");
}
