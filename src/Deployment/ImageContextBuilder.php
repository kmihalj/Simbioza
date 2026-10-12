<?php

declare(strict_types=1);

namespace App\Deployment;

use App\Module\ModuleCatalog;
use App\Module\NativeProcessRunner;
use RuntimeException;

/**
 * EN: Exports an explicit application allowlist and Git-visible package code, not a live installation.
 * Local candidate packages are development-only inputs; no HFC secrets/data/vendor symlinks are exported.
 *
 * HR: Izvozi izričit aplikacijski allowlist i Git-visible kod paketa, ne živu instalaciju.
 * Lokalni kandidati služe razvoju; HFC tajne/podaci/vendor symlinkovi ne izvoze se.
 */
final readonly class ImageContextBuilder
{
    private const FACTORY_CONFIG = [
        'app.php', 'bootstrap.php', 'commands.php', 'listeners.php', 'middleware.php',
        'routes.php', 'services.php', 'setup.php', 'languages.php',
        'api/api.php', 'backup/backup.php', 'backup/backup-providers.php',
        'calendar/calendar.php', 'menu/menu.php', 'theme/theme.php',
        'confluence-import/confluence-import.php', 'workspace-search/workspace-search.php',
    ];

    /**
     * EN: Uses fixed source/workspace roots provided by the local CLI tool.
     *
     * HR: Koristi fiksne izvorne/workspace korijene koje zadaje lokalni CLI alat.
     */
    public function __construct(private string $sourceRoot, private string $workspaceRoot)
    {
    }

    /**
     * EN: Creates only a new context; never merges into an existing directory or modifies source manifests.
     * Selection is dependency-expanded; a missing module must be baked into a subsequent image.
     *
     * HR: Stvara samo novi kontekst; ne spaja postojeći direktorij niti mijenja izvorne manifeste.
     * Odabir se proširuje ovisnostima; nedostajući modul ugrađuje se u sljedeći image.
     *
     * @param list<string> $optional
     */
    public function build(string $destination, bool $local, array $optional): string
    {
        if (file_exists($destination) || is_link($destination) || !is_dir(dirname($destination))) {
            throw new RuntimeException('The image context must be a new directory under an existing parent.');
        }
        if (
            !mkdir($destination, 0700) || !mkdir($destination . '/app', 0700)
            || !mkdir($destination . '/packages', 0700) || !mkdir($destination . '/docker', 0700)
        ) {
            throw new RuntimeException('Unable to create image context.');
        }
        $catalog = new ModuleCatalog();
        // EN: Backup is also required by the bundled first-install guide importer.
        //
        // HR: Backup je potreban i ugrađenom uvozniku vodiča prve instalacije.
        $selected = array_fill_keys([...$catalog->requiredSlugs(), ...$optional, 'backup'], true);
        do {
            $previous = count($selected);
            foreach (array_keys($selected) as $slug) {
                foreach ($catalog->definitionFor($slug)['dependencies'] as $dependency) {
                    $selected[$dependency] = true;
                }
            }
        } while (count($selected) !== $previous);
        $packages = $catalog->packagesForSelection(array_keys($selected));
        $files = $this->gitFiles($this->sourceRoot);
        $digest = hash_init('sha256');
        foreach ($files as $file) {
            $allowed = preg_match('~\A(?:src|views|public|database|lang|resources|bin)/~', $file) === 1
            || in_array($file, ['bootstrap.php', 'VERSION', 'LICENSE', 'update.php'], true)
            || (str_starts_with($file, 'config/') && in_array(substr($file, 7), self::FACTORY_CONFIG, true))
            || in_array($file, ['docker/entrypoint.php', 'docker/healthcheck.php'], true);
            if ($allowed) {
                $this->copy($this->sourceRoot, $file, $destination . '/app', $digest);
            }
        }
        foreach (['Dockerfile', 'apache.conf', 'php.ini'] as $file) {
            $this->copy($this->sourceRoot, 'docker/' . $file, $destination, $digest);
        }

        $manifest = json_decode(
            (string)file_get_contents($this->sourceRoot . '/composer.json'),
            true,
            128,
            JSON_THROW_ON_ERROR,
        );
        if (!is_array($manifest) || !is_array($manifest['require'] ?? null)) {
            throw new RuntimeException('Invalid source Composer manifest.');
        }
        foreach (array_keys($manifest['require']) as $package) {
            if (
                $package !== 'php' && $package !== 'aaieduhr/heartphrame-framework'
                && !in_array($package, $packages, true)
            ) {
                unset($manifest['require'][$package]);
            }
        }
        unset($manifest['require-dev'], $manifest['autoload-dev'], $manifest['scripts']);
        foreach (array_keys($selected) as $slug) {
            $definition = $catalog->definitionFor($slug);
            if (!isset($manifest['require'][$definition['package']])) {
                $manifest['require'][$definition['package']] = $catalog->constraintFor($slug);
            }
        }
        $configuration = is_array($manifest['config'] ?? null) ? $manifest['config'] : [];
        $configuration['cache-dir'] = '/tmp/composer-cache';
        $configuration['allow-plugins'] = false;
        $manifest['config'] = $configuration;
        if ($local) {
            $repositories = [];
            foreach (['aaieduhr/heartphrame-framework', ...$packages] as $package) {
                $directory = $package === 'aaieduhr/heartphrame-framework'
                ? 'heartphrame-framework' : basename($package);
                $source = $this->workspaceRoot . '/' . $directory;
                foreach ($this->gitFiles($source) as $file) {
                    $code = preg_match('~\A(?:src|views|resources|public|lang|bin)/~', $file) === 1
                    || (str_starts_with($file, 'config/')
                        && (str_ends_with($file, '.php') || str_ends_with($file, '.dist')))
                    || in_array($file, ['composer.json', 'heartphrame-manifest.php', 'LICENSE', 'bootstrap.php'], true)
                    || (!str_contains($file, '/') && str_ends_with($file, '.dist'));
                    if (!$code || str_starts_with(basename($file), '.') || str_ends_with($file, '.lock')) {
                        continue;
                    }
                    $this->copy($source, $file, $destination . '/packages/' . $directory, $digest);
                }
                // EN: Use explicit candidate versions satisfying unchanged inter-module constraints.
                // This affects only exported metadata, never a sibling repository's manifest.
                //
                // HR: Izričite candidate verzije zadovoljavaju neizmijenjene međumodulske ovisnosti.
                // Mijenja se samo izvoz, nikada manifest susjednog repozitorija.
                $version = $package === 'aaieduhr/heartphrame-framework' ? '0.0.25' : '0.3.0';
                $repositories[] = ['type' => 'path', 'url' => '/opt/simbioza-build/packages/' . $directory,
                    'options' => ['symlink' => false, 'versions' => [$package => $version]]];
                $manifest['require'][$package] = $version;
            }
            $manifest['repositories'] = $repositories;
            $manifest['minimum-stability'] = 'dev';
            $manifest['prefer-stable'] = true;
        }
        $manifestBytes = json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        hash_update($digest, $manifestBytes);
        file_put_contents($destination . '/app/composer.json', $manifestBytes . "\n");
        file_put_contents($destination . '/packages/.context', "Sanitized build inputs only.\n");
        $id = hash_final($digest);
        $markerDirectory = $destination . '/app/resources/deployment';
        if (!is_dir($markerDirectory) && !mkdir($markerDirectory, 0700, true)) {
            throw new RuntimeException('Unable to create the image marker directory.');
        }
        file_put_contents($markerDirectory . '/immutable.json', json_encode([
            'format' => 'simbioza-image-v1', 'code_id' => $id, 'modules' => $packages,
            'source' => $local ? 'unpublished-local-candidate' : 'release-source',
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        return $id;
    }

    /**
     * EN: Git's index plus nonignored work identifies code; deleted files are not resurrected.
     *
     * HR: Gitov indeks i neignorirane izmjene određuju kod; obrisane datoteke ne vraćaju se.
     *
     * @return list<string>
     */
    private function gitFiles(string $source): array
    {
        $result = (new NativeProcessRunner())->run(
            ['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'],
            $source,
        );
        if ($result->exitCode !== 0) {
            throw new RuntimeException('Unable to enumerate source repository files.');
        }
        $files = array_values(array_unique(array_filter(explode("\0", $result->stdout))));
        sort($files, SORT_STRING);
        return $files;
    }

    /**
     * EN: Copies regular allowlisted files only, without following source links or hidden data directories.
     *
     * HR: Kopira samo regularne allowlist datoteke, bez praćenja izvornih linkova ili skrivenih podataka.
     *
     * @param \HashContext $digest
     */
    private function copy(string $root, string $file, string $target, \HashContext $digest): void
    {
        if (str_starts_with($file, '/') || str_contains($file, '..') || str_contains($file, "\0")) {
            throw new RuntimeException('Invalid source filename.');
        }
        $source = $root . '/' . $file;
        if (!file_exists($source)) {
            return;
        }
        $cursor = $root;
        foreach (explode('/', $file) as $part) {
            $cursor .= '/' . $part;
            if (is_link($cursor)) {
                throw new RuntimeException('Source links cannot be exported into an image.');
            }
        }
        if (!is_file($source)) {
            throw new RuntimeException('Image inputs must be regular files.');
        }
        $destination = $target . '/' . $file;
        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0700, true)) {
            throw new RuntimeException('Unable to prepare image input directories.');
        }
        if (!copy($source, $destination)) {
            throw new RuntimeException('Unable to copy image source.');
        }
        chmod($destination, 0644);
        hash_update($digest, $file . "\0" . hash_file('sha256', $source) . "\0");
    }
}
