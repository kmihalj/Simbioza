<?php

declare(strict_types=1);

namespace App\Configuration;

use HeartPhrame\Config\ConfigPathResolver;
use InvalidArgumentException;
use RuntimeException;

/**
 * EN: The application path contract uses the framework resolver without changing formats or data.
 *
 * HR: Aplikacijski dogovor o putanjama koristi resolver frameworka, bez promjene formata ili podataka.
 */
final readonly class ConfigurationPaths
{
    /**
     * EN: Records ordered layers; resolving paths alone does not create directories.
     *
     * HR: Pamti uređene slojeve; samo određivanje putanja ne stvara direktorije.
     *
     * @param non-empty-list<non-empty-string> $directories
     */
    private function __construct(private array $directories)
    {
    }

    /**
     * EN: The framework resolver alone determines the base path, priorities, and deduplication.
     *
     * HR: Baznu putanju, prioritete i deduplikaciju određuje isključivo resolver frameworka.
     */
    public static function fromEnvironment(string $appRoot): self
    {
        return new self(ConfigPathResolver::fromEnvironment($appRoot));
    }

    /**
     * EN: Returns layers from base to highest priority, as used by framework HTTP/CLI loading.
     *
     * HR: Vraća slojeve od osnovnog do najvišeg prioriteta, kao za framework HTTP/CLI učitavanje.
     *
     * @return non-empty-list<non-empty-string>
     */
    public function directories(): array
    {
        return $this->directories;
    }

    /**
     * EN: Returns the base directory, not necessarily the write target.
     *
     * HR: Vraća osnovni direktorij, ne nužno cilj spremanja.
     */
    public function baseDirectory(): string
    {
        return $this->directories[0];
    }

    /**
     * EN: Builds paths for the same relative file in all layers without accessing files.
     *
     * HR: Gradi putanje iste relativne datoteke u svim slojevima, bez pristupa datotekama.
     *
     * @return non-empty-list<non-empty-string>
     */
    public function candidateFiles(string $relativeFile): array
    {
        $this->assertRelativeFile($relativeFile);

        return array_map(
            static fn(string $directory): string => rtrim($directory, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile),
            $this->directories,
        );
    }

    /**
     * EN: For complete data documents, selects the last existing file without merging contents.
     * PHP configuration is still merged by the framework; a missing file returns null, not defaults.
     *
     * HR: Za potpune podatkovne dokumente bira zadnju postojeću datoteku, bez spajanja sadržaja.
     * PHP konfiguraciju i dalje spaja framework; nedostajuća datoteka daje null, ne zadani sadržaj.
     */
    public function effectiveFile(string $relativeFile): ?string
    {
        $files = $this->candidateFiles($relativeFile);
        $this->assertReadable();
        foreach (array_reverse($files, true) as $index => $file) {
            $this->assertParents($this->directories[$index], $relativeFile);
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (!is_file($file) || !is_readable($file)) {
                throw new RuntimeException('Configuration file is not readable: ' . $file);
            }

            return $file;
        }

        return null;
    }

    /**
     * EN: Writes target only the last declared layer, never a lower writable layer.
     * Creates nothing; the caller must safely lock and atomically persist the data.
     *
     * HR: Zapis ide isključivo u zadnji deklarirani sloj, nikada u slabiji zapisivi sloj.
     * Ne stvara ništa; pozivatelj mora sigurno zaključati i atomskim zapisom spremiti podatke.
     */
    public function writableFile(string $relativeFile): string
    {
        $files = $this->candidateFiles($relativeFile);
        $this->assertReadable();
        $file = $files[count($files) - 1];
        $this->assertParents($this->directories[count($this->directories) - 1], $relativeFile, true);
        if (is_link($file)) {
            throw new RuntimeException('Configuration write target must not be a symbolic link: ' . $file);
        }

        if (file_exists($file) && (!is_file($file) || !is_readable($file) || !is_writable($file))) {
            throw new RuntimeException('Configuration file is not writable: ' . $file);
        }

        $parent = dirname($file);
        while (!file_exists($parent) && !is_link($parent)) {
            $parent = dirname($parent);
        }

        // EN: Atomic replacement and subdirectory creation need a writable, searchable directory.
        //
        // HR: Atomska zamjena i stvaranje poddirektorija trebaju zapisiv pretraživ direktorij.
        if (
            !is_dir($parent) || !is_readable($parent) || !is_writable($parent)
            || (DIRECTORY_SEPARATOR !== '\\' && !is_executable($parent))
        ) {
            throw new RuntimeException('Configuration directory is not writable: ' . $parent);
        }

        return $file;
    }

    /**
     * EN: Every declared layer must be readable and searchable, even when empty.
     *
     * HR: Svaki deklarirani sloj mora biti čitljiv i pretraživ, čak i ako je prazan.
     */
    public function assertReadable(): void
    {
        clearstatcache();
        foreach ($this->directories as $directory) {
            if (str_contains($directory, "\0") || str_contains($directory, '://')) {
                throw new InvalidArgumentException('Configuration directories must be local filesystem paths.');
            }

            if (
                !is_dir($directory) || !is_readable($directory)
                || (DIRECTORY_SEPARATOR !== '\\' && !is_executable($directory))
            ) {
                throw new RuntimeException('Configuration directory is missing or not readable: ' . $directory);
            }
        }
    }

    /**
     * EN: An invalid parent is not a missing document; writes do not follow subdirectory symlinks.
     *
     * HR: Nevaljan roditelj nije nedostajući dokument; zapisi ne slijede poddirektorijske symlinkove.
     */
    private function assertParents(string $directory, string $relativeFile, bool $writing = false): void
    {
        $segments = explode('/', $relativeFile);
        array_pop($segments);
        foreach ($segments as $segment) {
            $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $segment;
            if (!file_exists($directory) && !is_link($directory)) {
                return;
            }

            if ($writing && is_link($directory)) {
                throw new RuntimeException('Configuration write parent must not be a symbolic link: ' . $directory);
            }

            if (
                !is_dir($directory) || !is_readable($directory)
                || (DIRECTORY_SEPARATOR !== '\\' && !is_executable($directory))
            ) {
                throw new RuntimeException('Configuration parent directory is not readable: ' . $directory);
            }
        }
    }

    /**
     * EN: Accepts only slash-separated relative names, without layer traversal or stream paths.
     *
     * HR: Prihvaća samo relativna imena s kosom crtom, bez izlaska iz sloja ili stream putanja.
     */
    private function assertRelativeFile(string $relativeFile): void
    {
        if (
            $relativeFile === '' || $relativeFile !== trim($relativeFile)
            || str_contains($relativeFile, "\0") || str_contains($relativeFile, ':')
            || str_contains($relativeFile, '\\')
        ) {
            throw new InvalidArgumentException('Configuration file names must be safe relative paths.');
        }

        foreach (explode('/', $relativeFile) as $segment) {
            if (in_array($segment, ['', '.', '..'], true)) {
                throw new InvalidArgumentException('Configuration file names must not leave their layer.');
            }
        }
    }
}
