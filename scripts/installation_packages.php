<?php

declare(strict_types=1);

use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\NativeProcessRunner;

// EN: CLI prepares optional packages when the web installer has no privileged
//     FPM helper. It loads neither the application nor an existing database.
//
// HR: CLI priprema opcionalne pakete kada web-installer nema privilegirani
//     FPM helper. Ne učitava aplikaciju niti zahtijeva postojeću bazu.

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is CLI-only.\n");
    exit(2);
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$arguments = $argv;
array_shift($arguments);
$action = strtolower(trim((string)array_shift($arguments)));
$options = parseOptions($arguments);
$catalog = new ModuleCatalog();
$packages = new ComposerPackageManager($catalog, new NativeProcessRunner(), $root);
$statePath = $root . '/data/installation-packages.json';

try {
    match ($action) {
        'prepare' => preparePackages($root, $catalog, $packages, $statePath, $options),
        'cleanup' => cleanupPackages($root, $statePath),
        'status' => printStatus($catalog, $packages, $statePath),
        'help', '--help', '-h', '' => printHelp(),
        default => throw new RuntimeException('Unknown action: ' . $action),
    };
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(2);
}

/**
 * EN: Reads a product preset or explicit module selection without shell interpretation.
 *
 * HR: Čita predložak proizvoda ili izričiti odabir modula bez shell interpretacije.
 *
 * @param list<string> $arguments
 * @return array{modules:list<string>,specified:bool}
 */
function parseOptions(array $arguments): array
{
    $modules = [];
    $specified = false;
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--profile=')) {
            if ($specified) {
                throw new RuntimeException('Use either --profile or --modules once.');
            }
            $profile = (new ModuleCatalog())->installationProfiles()[substr($argument, strlen('--profile='))] ?? null;
            if ($profile === null) {
                throw new RuntimeException('Unknown installation profile. Use full, web, calendar, wiki, surveys or core.');
            }
            $modules = $profile['modules'];
            $specified = true;
            continue;
        }
        if (!str_starts_with($argument, '--modules=')) {
            throw new RuntimeException('Supported options: --profile=web or --modules=calendar,api');
        }
        if ($specified) {
            throw new RuntimeException('Use either --profile or --modules once.');
        }
        $specified = true;
        foreach (explode(',', substr($argument, strlen('--modules='))) as $slug) {
            $slug = strtolower(trim($slug));
            if ($slug !== '') {
                $modules[] = $slug;
            }
        }
    }

    return ['modules' => array_values(array_unique($modules)), 'specified' => $specified];
}

/**
 * EN: Installs selected feature packages and their necessary dependencies.
 *
 * HR: Instalira odabrane funkcijske pakete i njihove nužne ovisnosti.
 *
 * @param array{modules:list<string>,specified:bool} $options
 */
function preparePackages(
    string $root,
    ModuleCatalog $catalog,
    ComposerPackageManager $packages,
    string $statePath,
    array $options,
): void {
    $requested = $options['modules'];
    if (!$options['specified']) {
        $requested = $catalog->optionalSlugs();
    }
    foreach ($requested as $slug) {
        if (!in_array($slug, $catalog->optionalSlugs(), true)) {
            throw new RuntimeException('Unknown optional module: ' . $slug);
        }
    }
    $selectedPackages = $catalog->packagesForSelection($requested);
    $install = array_values(array_filter(
        $catalog->optionalSlugs(),
        static fn(string $slug): bool => in_array($catalog->definitionFor($slug)['package'], $selectedPackages, true),
    ));
    $new = [];
    foreach ($install as $slug) {
        if (!$packages->isInstalled($slug)) {
            $packages->install($slug);
            $new[] = $slug;
        }
    }
    writeState($root, $statePath, ['requested' => $requested, 'new' => $new]);
    fwrite(STDOUT, 'Prepared optional modules: ' . implode(', ', $requested) . PHP_EOL);
    fwrite(STDOUT, "Required core modules, including Backup, remain installed.\n");
}

/**
 * EN: Removes only the completed preparation record, never a required package.
 *
 * HR: Uklanja samo dovršeni zapis pripreme, nikada obvezni paket.
 */
function cleanupPackages(string $root, string $statePath): void
{
    if (!is_file($root . '/data/installation.lock')) {
        throw new RuntimeException('Complete the web installer before cleanup.');
    }
    if (is_file($statePath)) {
        unlink($statePath);
    }
    fwrite(STDOUT, "Preparation record cleared; installed packages preserved.\n");
}

/**
 * EN: Prints prepared and physically installed optional packages.
 *
 * HR: Ispisuje pripremljene i stvarno instalirane opcionalne pakete.
 */
function printStatus(ModuleCatalog $catalog, ComposerPackageManager $packages, string $statePath): void
{
    $state = readState($statePath);
    foreach ($catalog->optionalSlugs() as $slug) {
        fwrite(STDOUT, sprintf("%-20s %s\n", $slug, $packages->isInstalled($slug) ? 'installed' : 'not-installed'));
    }
    if ($state !== []) {
        fwrite(STDOUT, 'Prepared selection: ' . implode(', ', $state['requested'] ?? []) . PHP_EOL);
    }
}

/**
 * EN: Atomically stores the small preparation plan outside the web root.
 *
 * HR: Atomski sprema mali plan pripreme izvan web korijena.
 */
function writeState(string $root, string $path, array $state): void
{
    if (!is_dir($root . '/data') && !mkdir($root . '/data', 0770, true) && !is_dir($root . '/data')) {
        throw new RuntimeException('Unable to create the private data directory.');
    }
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $temporary = tempnam(dirname($path), '.installation-packages-');
    if (!is_string($temporary)) {
        throw new RuntimeException('Unable to prepare package state.');
    }
    try {
        if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !chmod($temporary, 0660)) {
            throw new RuntimeException('Unable to write package state.');
        }
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Unable to activate package state.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

/**
 * EN: Reads the previous plan and rejects unexpected structure.
 *
 * HR: Čita prethodni plan i odbija neočekivanu strukturu.
 *
 * @return array{requested?:list<string>,new?:list<string>}
 */
function readState(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $state = json_decode((string)file_get_contents($path), true);
    if (!is_array($state)) {
        throw new RuntimeException('Installation package state is invalid.');
    }
    foreach (['requested', 'new'] as $key) {
        if (isset($state[$key]) && (!is_array($state[$key]) || array_filter($state[$key], 'is_string') !== $state[$key])) {
            throw new RuntimeException('Installation package state is invalid.');
        }
    }

    return $state;
}

/**
 * EN: Prints the non-FPM installation workflow.
 *
 * HR: Ispisuje postupak za instalaciju bez FPM helpera.
 */
function printHelp(): void
{
    fwrite(STDOUT, <<<'HELP'
Simbioza installation package preparation / Priprema instalacijskih paketa

  php scripts/installation_packages.php prepare
  php scripts/installation_packages.php prepare --profile=web
  php scripts/installation_packages.php prepare --modules=calendar
  php scripts/installation_packages.php prepare --modules=
  php scripts/installation_packages.php status
  php scripts/installation_packages.php cleanup

Run `prepare` before opening the web installer when no dedicated FPM Setup
helper is configured. Without a selection it prepares all optional modules.
Use --profile=web|calendar|wiki|surveys|core|full or --modules=a,b for a focused
installation, then choose the same preset or modules in the wizard. An
empty --modules= value keeps only the six required core modules.
Run `cleanup` after success to clear the preparation record; it removes no module.
HELP . PHP_EOL);
}
