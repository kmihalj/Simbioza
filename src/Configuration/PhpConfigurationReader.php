<?php

declare(strict_types=1);

namespace App\Configuration;

use HeartPhrame\Config\ConfigArrayMerger;
use RuntimeException;

/**
 * EN: Early bootstrap reads selected PHP configuration using the framework's native merge.
 *
 * HR: Rani bootstrap čita odabranu PHP konfiguraciju uz izvorno spajanje frameworka.
 */
final readonly class PhpConfigurationReader
{
    /**
     * EN: Receives the same ordered paths as the application.
     *
     * HR: Prima isti uređeni skup putanja kao aplikacija.
     */
    public function __construct(private ConfigurationPaths $paths)
    {
    }

    /**
     * EN: Merges all layers of one file and refreshes OPcache only for its data.
     *
     * HR: Spaja sve slojeve jedne datoteke i osvježava OPcache samo njenih podataka.
     *
     * @return array<array-key,mixed>
     */
    public function read(string $relativeFile): array
    {
        // EN: Check declared directories and an invalid highest document before loading.
        //
        // HR: Provjeri deklarirane direktorije i nevaljan najviši dokument prije učitavanja.
        $this->paths->effectiveFile($relativeFile);
        $configuration = [];
        foreach ($this->paths->candidateFiles($relativeFile) as $path) {
            if (!file_exists($path) && !is_link($path)) {
                continue;
            }

            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException('Configuration file is not readable: ' . $path);
            }

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }

            // EN: Executable configuration variables must not alter reader state.
            //
            // HR: Varijable izvršivog configa ne smiju mijenjati stanje učitavača.
            $values = (static fn(string $file): mixed => require $file)($path);
            if (!is_array($values)) {
                throw new RuntimeException('Configuration file must return an array: ' . $path);
            }

            $configuration = ConfigArrayMerger::merge($configuration, $values);
        }

        return $configuration;
    }

    /**
     * EN: A complete registry selects the highest document; deleted keys never reappear from below.
     *
     * HR: Potpuni registar bira najviši dokument; uklonjeni ključ ne vraća niži sloj.
     *
     * @return array<array-key,mixed>
     */
    public function readDocument(string $relativeFile): array
    {
        $path = $this->paths->effectiveFile($relativeFile);
        if ($path === null) {
            return [];
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        // EN: Load complete documents in a separate local scope too.
        //
        // HR: I potpuni dokument učitaj u odvojenom lokalnom dosegu.
        $values = (static fn(string $file): mixed => require $file)($path);
        if (!is_array($values)) {
            throw new RuntimeException('Configuration file must return an array: ' . $path);
        }

        return $values;
    }
}
