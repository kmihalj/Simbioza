<?php

declare(strict_types=1);

namespace App\Module;

use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use JsonException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * HR: Dodaje i uklanja samo pakete iz fiksnog kataloga modula. Korisnički
 *     unos nikada ne postaje naredba, putanja ni Composer opcija.
 * EN: Adds and removes only packages from the fixed module catalog. User input
 *     never becomes a command, path, or Composer option.
 */
final readonly class ComposerPackageManager
{
    /** HR: Prima katalog, runner i korijen jedne instalacije. EN: Receives the catalog, runner, and one installation root. */
    public function __construct(
        private ModuleCatalog $catalog,
        private ProcessRunnerInterface $processes,
        private string $appRoot,
        private ?ModuleStateStore $state = null,
    ) {
    }

    /** HR: Provjerava postoji li paket u stvarno instaliranom Composer skupu. EN: Checks whether the package exists in the actual Composer installation. */
    public function isInstalled(string $slug): bool
    {
        return in_array(
            $this->catalog->definitionFor($slug)['package'],
            $this->installedPackages(),
            true,
        );
    }

    /**
     * HR: Čita stvarno instalirane pakete iz točno ove aplikacije. Ne koristi
     *     globalni Composer cache jer ga dugotrajni FPM proces može zastarjeti.
     * EN: Reads packages actually installed in this application. It avoids the
     *     global Composer cache because a long-lived FPM process can stale it.
     *
     * @return list<string>
     */
    public function installedPackages(): array
    {
        // HR: JSON se čita kao podatak, ne kao PHP, pa OPcache ne može vratiti
        //     staru snimku neposredno nakon Composerove atomske zamjene datoteke.
        // EN: JSON is read as data rather than PHP, so OPcache cannot return a
        //     stale snapshot immediately after Composer atomically replaces it.
        $installed = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/vendor/composer/installed.json';
        if (!is_file($installed)) {
            return [];
        }

        try {
            $metadata = json_decode((string)file_get_contents($installed), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException('Composer installed-package metadata is invalid.', 0, $jsonException);
        }

        $packages = is_array($metadata) && is_array($metadata['packages'] ?? null)
        ? $metadata['packages']
        : $metadata;
        if (!is_array($packages)) {
            throw new RuntimeException('Composer installed-package metadata is invalid.');
        }

        $names = [];
        foreach ($packages as $package) {
            if (is_array($package) && is_string($package['name'] ?? null)) {
                $names[] = $package['name'];
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * HR: Registrira svježu autoload mapu nakon što je odvojeni deploy proces
     *     dodao paket. Postojeći Composer loader u FPM-u inače zadržava mapu
     *     učitanu na početku zahtjeva.
     * EN: Registers a fresh autoload map after the separate deploy process adds
     *     a package. The existing Composer loader in FPM otherwise retains the
     *     map loaded at the start of the request.
     */
    public function registerCurrentAutoloader(): void
    {
        $composerDirectory = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/vendor/composer';
        $loader = new ClassLoader(dirname($composerDirectory));
        foreach ($this->autoloadMap($composerDirectory . '/autoload_psr4.php') as $prefix => $paths) {
            $normalizedPaths = $this->autoloadPaths($paths);
            if (is_string($prefix) && $normalizedPaths !== null) {
                $loader->setPsr4($prefix, $normalizedPaths);
            }
        }

        foreach ($this->autoloadMap($composerDirectory . '/autoload_namespaces.php') as $prefix => $paths) {
            $normalizedPaths = $this->autoloadPaths($paths);
            if (is_string($prefix) && $normalizedPaths !== null) {
                $loader->set($prefix, $normalizedPaths);
            }
        }

        $classMap = [];
        foreach ($this->autoloadMap($composerDirectory . '/autoload_classmap.php') as $class => $path) {
            if (is_string($class) && is_string($path)) {
                $classMap[$class] = $path;
            }
        }

        $loader->addClassMap($classMap);
        $loader->register(true);

        foreach ($this->autoloadMap($composerDirectory . '/autoload_files.php') as $file) {
            if (is_string($file) && is_file($file)) {
                require_once $file;
            }
        }
    }

    /** HR: Instalira točno jedan katalogizirani opcionalni paket. EN: Installs exactly one catalogued optional package. */
    public function install(string $slug): void
    {
        $definition = $this->optionalDefinition($slug);
        if ($this->isInstalled($slug) && $this->isProductionRequirement($definition['package'])) {
            return;
        }

        $constraint = $this->state?->requirementConstraint($slug) ?? $this->catalog->constraintFor($slug);
        $this->changeRequirement($definition['package'], $constraint, true);
        if (!$this->isInstalled($slug)) {
            throw new RuntimeException('Composer completed without installing module package: ' . $slug);
        }
    }

    /** HR: Uklanja točno jedan katalogizirani opcionalni paket. EN: Removes exactly one catalogued optional package. */
    public function uninstall(string $slug): void
    {
        $definition = $this->optionalDefinition($slug);
        if (!$this->isInstalled($slug) && !$this->isProductionRequirement($definition['package'])) {
            return;
        }

        $constraint = $this->productionRequirement($definition['package']) ?? $this->catalog->constraintFor($slug);
        $this->state?->rememberRequirementConstraint($slug, $constraint);
        $this->changeRequirement($definition['package'], $constraint, false);
        if ($this->isInstalled($slug)) {
            throw new RuntimeException('Composer completed without removing module package: ' . $slug);
        }
    }

    /**
     * HR: Vraća samo opcionalnu definiciju; obvezni paket nije dopušten cilj.
     * EN: Returns only an optional definition; a required package is never an allowed target.
     *
     * @return array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}
     */
    private function optionalDefinition(string $slug): array
    {
        $definition = $this->catalog->definitionFor($slug);
        if (!$definition['optional']) {
            throw new RuntimeException('Required packages cannot be changed.');
        }

        return $definition;
    }

    /** HR: Provjerava je li paket trajno odabran za produkciju. EN: Checks whether a package is persistently selected for production. */
    private function isProductionRequirement(string $package): bool
    {
        return $this->productionRequirement($package) !== null;
    }

    /** HR: Vraća trenutačno produkcijsko ograničenje paketa. EN: Returns the package's current production constraint. */
    private function productionRequirement(string $package): ?string
    {
        $require = $this->readManifest()['require'] ?? null;
        if (!is_array($require) || !is_string($require[$package] ?? null)) {
            return null;
        }

        $constraint = trim($require[$package]);

        return $constraint !== '' ? $constraint : null;
    }

    /**
     * HR: Dodaje ili uklanja tagirani opcionalni paket isključivo iz običnog
     *     aplikacijskog skupa. Opcionalni moduli nikada nisu razvojne ovisnosti.
     *     Pri pogrešci vraća oba Composer metapodatka na prethodno stanje.
     * EN: Adds or removes a tagged optional package only from the regular
     *     application set. Optional modules are never development dependencies.
     *     On failure both Composer metadata files are restored.
     */
    private function changeRequirement(string $package, string $constraint, bool $install): void
    {
        $manifestPath = $this->appRoot . '/composer.json';
        $lockPath = $this->appRoot . '/composer.lock';
        $originalManifest = file_get_contents($manifestPath);
        $originalLock = is_file($lockPath) ? file_get_contents($lockPath) : null;
        if (!is_string($originalManifest) || (is_file($lockPath) && !is_string($originalLock))) {
            throw new RuntimeException('Composer metadata could not be backed up.');
        }

        $manifest = $this->readManifest();
        $require = is_array($manifest['require'] ?? null) ? $manifest['require'] : [];
        if ($install) {
            $require[$package] = $constraint;
        } else {
            unset($require[$package]);
        }

        ksort($require, SORT_STRING);
        $manifest['require'] = $require;

        try {
            $this->writeManifest($manifestPath, $manifest);
            if ($install) {
                $this->composer([
                    'update',
                    $package,
                    '--with-all-dependencies',
                ]);
            } else {
                $this->composer(['update', '--with-all-dependencies']);
            }
        } catch (\Throwable $throwable) {
            file_put_contents($manifestPath, $originalManifest, LOCK_EX);
            if (is_string($originalLock)) {
                file_put_contents($lockPath, $originalLock, LOCK_EX);
            } elseif (is_file($lockPath)) {
                unlink($lockPath);
            }

            throw $throwable;
        }
    }

    /**
     * HR: Čita i provjerava korijenski Composer manifest.
     * EN: Reads and validates the root Composer manifest.
     * @return array<string,mixed>
     */
    private function readManifest(): array
    {
        try {
            $manifest = json_decode(
                (string)file_get_contents($this->appRoot . '/composer.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $jsonException) {
            throw new RuntimeException('Composer manifest is invalid.', 0, $jsonException);
        }

        if (!is_array($manifest)) {
            throw new RuntimeException('Composer manifest is invalid.');
        }

        $normalized = [];
        foreach ($manifest as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Composer manifest must contain named root keys.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * HR: Atomski zapisuje formatirani Composer manifest.
     * EN: Atomically writes a formatted Composer manifest.
     * @param array<string,mixed> $manifest
     */
    private function writeManifest(string $path, array $manifest): void
    {
        try {
            $contents = json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . "\n";
        } catch (JsonException $jsonException) {
            throw new RuntimeException('Composer manifest could not be encoded.', 0, $jsonException);
        }

        $temporary = tempnam(dirname($path), '.simbioza-composer-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Composer manifest could not be prepared.');
        }

        try {
            if (
                file_put_contents($temporary, $contents, LOCK_EX) === false
                || !chmod($temporary, 0664)
                || !rename($temporary, $path)
            ) {
                throw new RuntimeException('Composer manifest could not be activated.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * HR: Pokreće lokalni Composer samo s internim argumentima i sigurnim
     *     neinteraktivnim opcijama.
     * EN: Runs the local Composer only with internal arguments and safe
     *     non-interactive options.
     *
     * @param list<string> $arguments
     */
    private function composer(array $arguments): void
    {
        $composer = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/composer.phar';
        $command = is_file($composer)
        ? [PHP_BINARY, $composer]
        : ['composer'];
        $command = [
            ...$command,
            ...$arguments,
            '--no-interaction',
            '--no-progress',
            '--no-ansi',
            '--optimize-autoloader',
        ];
        $cache = $this->privateComposerCache();
        $previousCache = getenv('COMPOSER_CACHE_DIR');
        putenv('COMPOSER_CACHE_DIR=' . $cache);
        try {
            $result = $this->processes->run($command, $this->appRoot);
        } finally {
            if ($previousCache === false) {
                putenv('COMPOSER_CACHE_DIR');
            } else {
                putenv('COMPOSER_CACHE_DIR=' . $previousCache);
            }

            $this->removePrivateComposerCache($cache);
        }

        if ($result->exitCode !== 0) {
            $message = trim($result->stderr) !== '' ? trim($result->stderr) : trim($result->stdout);
            throw new RuntimeException('Composer module operation failed: ' . $message);
        }

        $this->normalizeVendorReadAccess();
    }

    /**
     * HR: Sigurni FPM helper koristi umask 0007. Nakon Composerove promjene
     *     kod paketa mora ostati čitljiv FPM-u, ali `.git` metapodaci i prava
     *     pisanja ne smiju postati javni.
     * EN: The restricted FPM helper uses umask 0007. After a Composer change,
     *     package code must remain readable by FPM, without exposing `.git`
     *     metadata or widening write permissions.
     */
    private function normalizeVendorReadAccess(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $vendor = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/vendor';
        if (!is_dir($vendor)) {
            throw new RuntimeException('Composer vendor directory is unavailable after module operation.');
        }

        $directory = new RecursiveDirectoryIterator($vendor, FilesystemIterator::SKIP_DOTS);
        $filtered = new RecursiveCallbackFilterIterator(
            $directory,
            static fn(SplFileInfo $item): bool => $item->getFilename() !== '.git',
        );
        $paths = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);
        $this->makeVendorPathReadable(new SplFileInfo($vendor));
        foreach ($paths as $path) {
            if ($path instanceof SplFileInfo) {
                $this->makeVendorPathReadable($path);
            }
        }
    }

    /** HR: Dodaje samo čitanje/prolaz za ostale korisnike. EN: Adds only other-user read/traverse permission. */
    private function makeVendorPathReadable(SplFileInfo $item): void
    {
        if ($item->isLink()) {
            return;
        }

        $path = $item->getPathname();
        $permissions = @fileperms($path);
        if (!is_int($permissions)) {
            throw new RuntimeException('Unable to read Composer path permissions: ' . $path);
        }

        $mode = $permissions & 07777;
        $required = $item->isDir() ? 0005 : 0004;
        if (($mode & $required) !== $required && !@chmod($path, $mode | $required)) {
            throw new RuntimeException('Unable to make Composer path web-readable: ' . $path);
        }
    }

    /**
     * HR: Za svaku paketnu radnju stvara cache u vlasništvu trenutačnog
     *     FPM/deploy/CLI korisnika i tako izbjegava Git dubious-owner kvar.
     * EN: Creates a per-operation cache owned by the current FPM/deploy/CLI
     *     account, preventing Git dubious-owner failures across identities.
     */
    private function privateComposerCache(): string
    {
        $root = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/tmp';
        if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
            throw new RuntimeException('Unable to create the private Composer cache root.');
        }

        $cache = $root . '/composer-module-' . bin2hex(random_bytes(8));
        if (!mkdir($cache, 0700)) {
            throw new RuntimeException('Unable to create a private Composer module cache.');
        }

        return $cache;
    }

    /** HR: Uklanja samo jednokratni Composer cache ove radnje. EN: Removes only this operation's one-time Composer cache. */
    private function removePrivateComposerCache(string $cache): void
    {
        if (!is_dir($cache)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cache, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }

            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($cache);
    }

    /**
     * HR: Učitava samo Composerovu generiranu autoload mapu iz ove instalacije.
     * EN: Loads only a Composer-generated autoload map from this installation.
     *
     * @return array<array-key,mixed>
     */
    private function autoloadMap(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Composer autoload metadata is incomplete.');
        }

        $map = require $path;
        if (!is_array($map)) {
            throw new RuntimeException('Composer autoload metadata is invalid.');
        }

        return $map;
    }

    /**
     * HR: Svodi Composerove putanje na tip koji ClassLoader sigurno prihvaća.
     * EN: Narrows Composer paths to the type safely accepted by ClassLoader.
     * @return string|list<string>|null
     */
    private function autoloadPaths(mixed $paths): string|array|null
    {
        if (is_string($paths)) {
            return $paths;
        }

        if (!is_array($paths)) {
            return null;
        }

        $normalized = array_values(array_filter($paths, is_string(...)));
        return count($normalized) === count($paths) ? $normalized : null;
    }
}
