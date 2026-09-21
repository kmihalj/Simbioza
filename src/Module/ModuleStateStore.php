<?php

declare(strict_types=1);

namespace App\Module;

use Composer\InstalledVersions;
use RuntimeException;

use function array_filter;
use function array_values;
use function chmod;
use function dirname;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function preg_match;
use function rename;
use function tempnam;
use function trim;
use function unlink;
use function var_export;

/**
 * HR: Čuva administratorski odabir omogućenih i uklonjenih modula u zasebnoj
 *     privatnoj konfiguraciji koju updater smije sačuvati između izdanja.
 * EN: Stores the administrator's enabled and removed module selection in a
 *     private configuration file preserved by the updater across releases.
 */
final readonly class ModuleStateStore
{
    /** HR: Prima korijen aplikacije i katalog dopuštenih modula. EN: Receives the application root and allowed-module catalog. */
    public function __construct(
        private string $appRoot,
        private ModuleCatalog $catalog,
    ) {
    }

    /**
     * HR: Vraća omogućene pakete ili samo obveznu jezgru za novu instalaciju.
     * EN: Returns enabled packages or only the required core for a new installation.
     *
     * @return list<string>
     */
    public function enabledPackages(): array
    {
        $state = $this->read();
        if (!array_key_exists('enabled', $state)) {
            if (is_file(rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/installation.lock')) {
                return array_values(array_filter(
                    array_map(
                        static fn(array $definition): string => $definition['package'],
                        $this->catalog->definitions(),
                    ),
                    static fn(string $package): bool => InstalledVersions::isInstalled($package),
                ));
            }

            return $this->catalog->packagesForSelection([]);
        }

        $allowed = array_map(
            static fn(array $definition): string => $definition['package'],
            $this->catalog->definitions(),
        );

        return array_values(array_filter(
            is_array($state['enabled'] ?? null) ? $state['enabled'] : [],
            static fn(mixed $package): bool => is_string($package) && in_array($package, $allowed, true),
        ));
    }

    /**
     * HR: Vraća module uklonjene uz zadržanu sigurnosnu kopiju.
     * EN: Returns modules removed with a retained backup.
     *
     * @return list<string>
     */
    public function removedSlugs(): array
    {
        $state = $this->read();
        $allowed = $this->catalog->optionalSlugs();

        return array_values(array_filter(
            is_array($state['removed'] ?? null) ? $state['removed'] : [],
            static fn(mixed $slug): bool => is_string($slug) && in_array($slug, $allowed, true),
        ));
    }

    /** HR: Vraća zapamćeno ograničenje paketa za ponovnu instalaciju. EN: Returns the remembered package constraint for reinstallation. */
    public function requirementConstraint(string $slug): ?string
    {
        return $this->requirementConstraints()[$this->catalog->normalizeSlug($slug)] ?? null;
    }

    /** HR: Pamti točno ograničenje uklonjenog paketa bez promjene stanja modula. EN: Remembers a removed package's exact constraint without changing module state. */
    public function rememberRequirementConstraint(string $slug, string $constraint): void
    {
        $slug = $this->catalog->normalizeSlug($slug);
        if (!in_array($slug, $this->catalog->optionalSlugs(), true)) {
            throw new RuntimeException('Only optional module constraints can be remembered.');
        }

        $constraint = trim($constraint);
        if (preg_match('/\A[^\x00-\x1F\x7F]{1,128}\z/D', $constraint) !== 1) {
            throw new RuntimeException('The module package constraint is invalid.');
        }

        $constraints = $this->requirementConstraints();
        $constraints[$slug] = $constraint;
        $this->write($this->enabledPackages(), $this->removedSlugs(), $constraints);
    }

    /** HR: Provjerava je li modul omogućen. EN: Checks whether a module is enabled. */
    public function isEnabled(string $slug): bool
    {
        $package = $this->catalog->definitionFor($slug)['package'];

        return in_array($package, $this->enabledPackages(), true);
    }

    /** HR: Omogućuje modul i uklanja oznaku uklonjenog stanja. EN: Enables a module and clears its removed-state marker. */
    public function enable(string $slug): void
    {
        $definition = $this->catalog->definitionFor($slug);
        $enabled = $this->enabledPackages();
        if (!in_array($definition['package'], $enabled, true)) {
            $enabled[] = $definition['package'];
        }

        $this->write($this->orderedPackages($enabled), array_values(array_filter(
            $this->removedSlugs(),
            static fn(string $removed): bool => $removed !== $slug,
        )));
    }

    /** HR: Onemogućuje modul bez brisanja podataka. EN: Disables a module without deleting data. */
    public function disable(string $slug): void
    {
        $package = $this->catalog->definitionFor($slug)['package'];
        $this->write(array_values(array_filter(
            $this->enabledPackages(),
            static fn(string $enabled): bool => $enabled !== $package,
        )), $this->removedSlugs());
    }

    /** HR: Bilježi da su modul i njegova shema uklonjeni. EN: Records that a module and its schema were removed. */
    public function markRemoved(string $slug): void
    {
        $package = $this->catalog->definitionFor($slug)['package'];
        $removed = $this->removedSlugs();
        if (!in_array($slug, $removed, true)) {
            $removed[] = $slug;
        }

        $this->write(array_values(array_filter(
            $this->enabledPackages(),
            static fn(string $enabled): bool => $enabled !== $package,
        )), $removed);
    }

    /**
     * HR: Zapisuje početni instalacijski odabir.
     * EN: Writes the initial installer selection.
     *
     * @param list<string> $optionalSlugs
     */
    public function initialize(array $optionalSlugs): void
    {
        $this->write($this->catalog->packagesForSelection($optionalSlugs), [], []);
    }

    /**
     * HR: Učitava privatni config ako postoji.
     * EN: Loads the private config when present.
     *
     * @return array<string,mixed>
     */
    private function read(): array
    {
        $path = $this->path();
        if (!is_file($path)) {
            $path = $this->legacyPath();
            if (!is_file($path)) {
                return [];
            }
        }

        $state = require $path;
        if (!is_array($state)) {
            return [];
        }

        $normalized = [];
        foreach ($state as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * HR: Atomski sprema kanonsko stanje modula dostupno samo vlasniku i
     *     namjenskoj runtime grupi, kako bi isti CLI radio bez sudo pristupa.
     * EN: Atomically stores canonical module state for the owner and the
     *     dedicated runtime group so the same CLI works without sudo access.
     *
     * @param list<string> $enabled
     * @param list<string> $removed
     * @param array<string,string>|null $constraints
     */
    private function write(array $enabled, array $removed, ?array $constraints = null): void
    {
        $path = $this->path();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 02770, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create the module state directory.');
            }

            // HR: mkdir poštuje process umask. Eksplicitna prava nakon stvaranja
            //     nužna su da i FPM i CLI održavatelj mogu atomski zamijeniti stanje.
            // EN: mkdir honors the process umask. Explicit post-create permissions
            //     are required so both FPM and the CLI maintainer can atomically replace state.
            if (!chmod($directory, 02770)) {
                throw new RuntimeException('Unable to secure the module state directory.');
            }
        }

        $temporary = tempnam(dirname($path), '.simbioza-modules-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create a temporary module configuration file.');
        }

        $constraints ??= $this->requirementConstraints();
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export([
            'enabled' => $this->orderedPackages($enabled),
            'removed' => array_values($removed),
            'constraints' => $constraints,
        ], true) . ";\n";
        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || !chmod($temporary, 0660)) {
                throw new RuntimeException('Unable to write the module configuration.');
            }

            if (!rename($temporary, $path)) {
                throw new RuntimeException('Unable to activate the module configuration.');
            }

            // HR: FPM može imati konfiguraciju u OPcacheu. Odmah poništi samo
            //     ovu promijenjenu datoteku kako bi redirect prikazao novo stanje.
            // EN: FPM may have cached the configuration in OPcache. Invalidate
            //     only this changed file so the redirect shows the new state.
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * HR: Vraća samo sigurna zapamćena ograničenja opcionalnih paketa.
     * EN: Returns only safe remembered constraints for optional packages.
     * @return array<string,string>
     */
    private function requirementConstraints(): array
    {
        $stored = $this->read()['constraints'] ?? [];
        if (!is_array($stored)) {
            return [];
        }

        $allowed = array_fill_keys($this->catalog->optionalSlugs(), true);
        $constraints = [];
        foreach ($stored as $slug => $constraint) {
            if (
                is_string($slug)
                && isset($allowed[$slug])
                && is_string($constraint)
                && preg_match('/\A[^\x00-\x1F\x7F]{1,128}\z/D', $constraint) === 1
            ) {
                $constraints[$slug] = $constraint;
            }
        }

        return $constraints;
    }

    /**
     * HR: Vraća pakete jedinstveno i u bootstrap redoslijedu.
     * EN: Returns unique packages in bootstrap order.
     *
     * @param list<string> $packages
     * @return list<string>
     */
    private function orderedPackages(array $packages): array
    {
        $selected = array_fill_keys($packages, true);
        $ordered = [];
        foreach ($this->catalog->definitions() as $definition) {
            if (isset($selected[$definition['package']])) {
                $ordered[] = $definition['package'];
            }
        }

        return $ordered;
    }

    /** HR: Vraća privatnu putanju konfiguracije modula. EN: Returns the private module configuration path. */
    private function path(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/config/modules.php';
    }

    /** HR: Čita staru putanju samo radi prijelaza. EN: Reads the legacy path only for transition. */
    private function legacyPath(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/config/modules.php';
    }
}
