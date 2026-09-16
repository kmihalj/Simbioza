<?php

declare(strict_types=1);

use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\NativeProcessRunner;

// HR: CLI priprema opcionalne pakete kada web-installer nema privilegirani
//     FPM helper. Ne učitava aplikaciju niti zahtijeva postojeću bazu.
// EN: CLI prepares optional packages when the web installer has no privileged
//     FPM helper. It loads neither the application nor an existing database.

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
        'cleanup' => cleanupPackages($root, $packages, $statePath),
        'status' => printStatus($catalog, $packages, $statePath),
        'help', '--help', '-h', '' => printHelp(),
        default => throw new RuntimeException('Unknown action: ' . $action),
    };
} catch (Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(2);
}

/**
 * HR: Čita samo `--modules=a,b` opciju bez shell interpretacije.
 * EN: Reads only the `--modules=a,b` option without shell interpretation.
 *
 * @param list<string> $arguments
 * @return array{modules:list<string>}
 */
function parseOptions(array $arguments): array
{
    $modules = [];
    foreach ($arguments as $argument) {
        if (!str_starts_with($argument, '--modules=')) {
            throw new RuntimeException('Supported option: --modules=theme,calendar');
        }
        foreach (explode(',', substr($argument, strlen('--modules='))) as $slug) {
            $slug = strtolower(trim($slug));
            if ($slug !== '') {
                $modules[] = $slug;
            }
        }
    }

    return ['modules' => array_values(array_unique($modules))];
}

/**
 * HR: Instalira odabrane pakete i privremeni Backup potreban početnim uputama.
 * EN: Installs selected packages and the temporary Backup needed by starter guides.
 *
 * @param array{modules:list<string>} $options
 */
function preparePackages(
    string $root,
    ModuleCatalog $catalog,
    ComposerPackageManager $packages,
    string $statePath,
    array $options,
): void {
    $requested = $options['modules'];
    if ($requested === []) {
        $requested = $catalog->recommendedSlugs();
    }
    foreach ($requested as $slug) {
        if (!in_array($slug, $catalog->optionalSlugs(), true)) {
            throw new RuntimeException('Unknown optional module: ' . $slug);
        }
    }
    $install = array_values(array_unique([...$requested, 'backup']));
    $new = [];
    foreach ($install as $slug) {
        if (!$packages->isInstalled($slug)) {
            $packages->install($slug);
            $new[] = $slug;
        }
    }
    writeState($root, $statePath, ['requested' => $requested, 'new' => $new]);
    fwrite(STDOUT, 'Prepared optional modules: ' . implode(', ', $requested) . PHP_EOL);
    fwrite(STDOUT, "Backup is temporarily available for the starter-guide import.\n");
}

/** HR: Nakon instalacije uklanja samo stvarno privremeni Backup. EN: After installation removes only the truly transient Backup package. */
function cleanupPackages(string $root, ComposerPackageManager $packages, string $statePath): void
{
    if (!is_file($root . '/data/installation.lock')) {
        throw new RuntimeException('Complete the web installer before cleanup.');
    }
    $state = readState($statePath);
    $lock = json_decode((string)file_get_contents($root . '/data/installation.lock'), true);
    $selected = is_array($lock) && is_array($lock['optional_modules'] ?? null)
        ? array_values(array_filter($lock['optional_modules'], 'is_string'))
        : ($state['requested'] ?? []);
    if (in_array('backup', $state['new'] ?? [], true) && !in_array('backup', $selected, true)) {
        $packages->uninstall('backup');
        fwrite(STDOUT, "Removed transient Backup package.\n");
    } else {
        fwrite(STDOUT, "No transient package needs removal.\n");
    }
    if (is_file($statePath)) {
        unlink($statePath);
    }
}

/** HR: Ispisuje pripremljene i stvarno instalirane opcionalne pakete. EN: Prints prepared and physically installed optional packages. */
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

/** HR: Atomski sprema mali plan pripreme izvan web korijena. EN: Atomically stores the small preparation plan outside the web root. */
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
 * HR: Čita prethodni plan i odbija neočekivanu strukturu.
 * EN: Reads the previous plan and rejects unexpected structure.
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

/** HR: Ispisuje postupak za instalaciju bez FPM helpera. EN: Prints the non-FPM installation workflow. */
function printHelp(): void
{
    fwrite(STDOUT, <<<'HELP'
Simbioza installation package preparation / Priprema instalacijskih paketa

  php scripts/installation_packages.php prepare --modules=theme,calendar
  php scripts/installation_packages.php status
  php scripts/installation_packages.php cleanup

Run `prepare` before opening the web installer when no dedicated FPM Setup
helper is configured. Choose the same modules in the web installer. Run
`cleanup` once after success; it removes only a newly added, unselected Backup.
HELP . PHP_EOL);
}
