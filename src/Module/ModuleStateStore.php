<?php

declare(strict_types=1);

namespace App\Module;

use App\Configuration\ConfigurationPaths;
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
 * EN: Stores the administrator's enabled and removed module selection in a
 *     private configuration file preserved by the updater across releases.
 *
 * HR: Čuva administratorski odabir omogućenih i uklonjenih modula u zasebnoj
 *     privatnoj konfiguraciji koju updater smije sačuvati između izdanja.
 */
final readonly class ModuleStateStore
{
    private ConfigurationPaths $configuration;

    /**
     * EN: Receives the application root and allowed-module catalog.
     *
     * HR: Prima korijen aplikacije i katalog dopuštenih modula.
     */
    public function __construct(
        private string $appRoot,
        private ModuleCatalog $catalog,
    ) {
        $this->configuration = ConfigurationPaths::fromEnvironment($appRoot);
    }

    /**
     * EN: Returns enabled packages or only the required core for a new installation.
     *
     * HR: Vraća omogućene pakete ili samo obveznu jezgru za novu instalaciju.
     *
     * @return list<string>
     */
    public function enabledPackages(): array
    {
        $state = $this->read();
        if (!array_key_exists('enabled', $state)) {
            if (is_file(rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/installation.lock')) {
                throw new RuntimeException('Installed applications require modules.php in HPH_CONFIG_PATH.');
            }

            return $this->catalog->packagesForSelection([]);
        }

        $allowed = array_map(
            static fn(array $definition): string => $definition['package'],
            $this->catalog->definitions(),
        );

        // EN: A formerly optional core module cannot remain disabled after the core policy changes.
        //
        // HR: Nekada opcionalni modul jezgre ne može ostati isključen nakon promjene pravila jezgre.
        return array_values(array_unique([
            ...$this->catalog->packagesForSelection([]),
            ...array_filter(
                is_array($state['enabled'] ?? null) ? $state['enabled'] : [],
                static fn(mixed $package): bool => is_string($package) && in_array($package, $allowed, true),
            ),
        ]));
    }

    /**
     * EN: Returns modules removed with a retained backup.
     *
     * HR: Vraća module uklonjene uz zadržanu sigurnosnu kopiju.
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

    /**
     * EN: Returns the remembered package constraint for reinstallation.
     *
     * HR: Vraća zapamćeno ograničenje paketa za ponovnu instalaciju.
     */
    public function requirementConstraint(string $slug): ?string
    {
        return $this->requirementConstraints()[$this->catalog->normalizeSlug($slug)] ?? null;
    }

    /**
     * EN: Remembers a removed package's exact constraint without changing module state.
     *
     * HR: Pamti točno ograničenje uklonjenog paketa bez promjene stanja modula.
     */
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

        $this->withLock(function () use ($slug, $constraint): void {
            $constraints = $this->requirementConstraints();
            $constraints[$slug] = $constraint;
            $this->write($this->enabledPackages(), $this->removedSlugs(), $constraints);
        });
    }

    /**
     * EN: Checks whether a module is enabled.
     *
     * HR: Provjerava je li modul omogućen.
     */
    public function isEnabled(string $slug): bool
    {
        $package = $this->catalog->definitionFor($slug)['package'];

        return in_array($package, $this->enabledPackages(), true);
    }

    /**
     * EN: Enables a module and clears its removed-state marker.
     *
     * HR: Omogućuje modul i uklanja oznaku uklonjenog stanja.
     */
    public function enable(string $slug): void
    {
        $this->withLock(function () use ($slug): void {
            $definition = $this->catalog->definitionFor($slug);
            $enabled = $this->enabledPackages();
            if (!in_array($definition['package'], $enabled, true)) {
                $enabled[] = $definition['package'];
            }

            $this->write($this->orderedPackages($enabled), array_values(array_filter(
                $this->removedSlugs(),
                static fn(string $removed): bool => $removed !== $slug,
            )));
        });
    }

    /**
     * EN: Disables a module without deleting data.
     *
     * HR: Onemogućuje modul bez brisanja podataka.
     */
    public function disable(string $slug): void
    {
        $this->withLock(function () use ($slug): void {
            $package = $this->catalog->definitionFor($slug)['package'];
            $this->write(array_values(array_filter(
                $this->enabledPackages(),
                static fn(string $enabled): bool => $enabled !== $package,
            )), $this->removedSlugs());
        });
    }

    /**
     * EN: Records that a module and its schema were removed.
     *
     * HR: Bilježi da su modul i njegova shema uklonjeni.
     */
    public function markRemoved(string $slug): void
    {
        $this->withLock(function () use ($slug): void {
            $package = $this->catalog->definitionFor($slug)['package'];
            $removed = $this->removedSlugs();
            if (!in_array($slug, $removed, true)) {
                $removed[] = $slug;
            }

            $this->write(array_values(array_filter(
                $this->enabledPackages(),
                static fn(string $enabled): bool => $enabled !== $package,
            )), $removed);
        });
    }

    /**
     * EN: Writes the initial installer selection.
     *
     * HR: Zapisuje početni instalacijski odabir.
     *
     * @param list<string> $optionalSlugs
     */
    public function initialize(array $optionalSlugs): void
    {
        $this->withLock(fn () => $this->write($this->catalog->packagesForSelection($optionalSlugs), [], []));
    }

    /**
     * EN: Loads the private config when present.
     *
     * HR: Učitava privatni config ako postoji.
     *
     * @return array<string,mixed>
     */
    private function read(): array
    {
        clearstatcache();
        $path = $this->configuration->effectiveFile('modules.php');

        return $path === null ? [] : $this->readFile($path);
    }

    /**
     * EN: Invalid configuration must not silently enable every installed module.
     *
     * HR: Nevaljana konfiguracija ne smije tiho uključiti sve instalirane module.
     *
     * @return array<string,mixed>
     */
    private function readFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Module configuration is not readable: ' . $path);
        }

        // EN: A CLI change cannot invalidate the OPcache of another web process.
        //
        // HR: Promjena iz CLI-ja ne može poništiti OPcache drugog web procesa.
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $state = require $path;
        if (!is_array($state)) {
            throw new RuntimeException('Module configuration must return an array: ' . $path);
        }

        $normalized = [];
        foreach ($state as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Module configuration must use named keys: ' . $path);
            }

            $normalized[$key] = $value;
        }

        if (!is_array($normalized['enabled'] ?? null) || !array_is_list($normalized['enabled'])) {
            throw new RuntimeException('Module configuration must contain an enabled package list: ' . $path);
        }

        foreach (['enabled', 'removed'] as $name) {
            $items = $normalized[$name] ?? [];
            if (!is_array($items) || !array_is_list($items)) {
                throw new RuntimeException('Module configuration must contain lists: ' . $path);
            }

            foreach ($items as $item) {
                if (!is_string($item) || trim($item) === '') {
                    throw new RuntimeException('Module configuration list items must be non-empty strings: ' . $path);
                }
            }
        }

        if (isset($normalized['constraints']) && !is_array($normalized['constraints'])) {
            throw new RuntimeException('Module constraints must be an array: ' . $path);
        }

        return $normalized;
    }

    /**
     * EN: Atomically stores canonical module state for the owner and the
     *     dedicated runtime group so the same CLI works without sudo access.
     *
     * HR: Atomski sprema kanonsko stanje modula dostupno samo vlasniku i
     *     namjenskoj runtime grupi, kako bi isti CLI radio bez sudo pristupa.
     *
     * @param list<string> $enabled
     * @param list<string> $removed
     * @param array<string,string>|null $constraints
     */
    private function write(array $enabled, array $removed, ?array $constraints = null): void
    {
        $path = $this->configuration->writableFile('modules.php');
        $constraints ??= $this->requirementConstraints();
        $state = array_replace($this->read(), [
            'enabled' => $this->orderedPackages($enabled),
            'removed' => array_values($removed),
            'constraints' => $constraints,
        ]);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($state, true) . ";\n";
        $this->activate($path, $contents);
    }

    /**
     * EN: Atomically stores validated contents without a lower fallback.
     *
     * HR: Atomski sprema već provjereni sadržaj bez nižeg fallbacka.
     */
    private function activate(string $path, string $contents): void
    {
        $this->configuration->writableFile('modules.php');
        $temporary = tempnam(dirname($path), '.simbioza-modules-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create a temporary module configuration file.');
        }

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || !chmod($temporary, 0660)) {
                throw new RuntimeException('Unable to write the module configuration.');
            }

            if (!rename($temporary, $path)) {
                throw new RuntimeException('Unable to activate the module configuration.');
            }

            // EN: FPM may have cached the configuration in OPcache. Invalidate
            //     only this changed file so the redirect shows the new state.
            //
            // HR: FPM može imati konfiguraciju u OPcacheu. Odmah poništi samo
            //     ovu promijenjenu datoteku kako bi redirect prikazao novo stanje.
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
     * EN: A shared lock serializes the entire read-modify-write for GUI and CLI.
     *
     * HR: Zajednički lock serijalizira cijeli read-modify-write za GUI i CLI.
     *
     * @param callable():void $operation
     */
    private function withLock(callable $operation): void
    {
        $this->configuration->writableFile('modules.php');
        $path = $this->configuration->writableFile('.modules.lock');
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the module configuration lock.');
        }

        try {
            if (((fileperms($path) & 0777) !== 0660 && !chmod($path, 0660)) || !flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to secure or acquire the module configuration lock.');
            }

            $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * EN: Returns only safe remembered constraints for optional packages.
     *
     * HR: Vraća samo sigurna zapamćena ograničenja opcionalnih paketa.
     *
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
     * EN: Returns unique packages in bootstrap order.
     *
     * HR: Vraća pakete jedinstveno i u bootstrap redoslijedu.
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
}
