<?php

declare(strict_types=1);

namespace App\Module;

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use RuntimeException;

use function array_filter;
use function array_values;
use function chmod;
use function date;
use function fclose;
use function fgets;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function glob;
use function is_array;
use function is_dir;
use function is_file;
use function is_resource;
use function json_decode;
use function json_encode;
use function mkdir;
use function rsort;
use function rtrim;
use function sprintf;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const LOCK_EX;
use const SORT_STRING;

/**
 * HR: Izvozi podatke opcionalnog modula u čitljiv NDJSON prije uklanjanja
 *     njegove sheme te ih može vratiti nakon ponovne instalacije modula.
 * EN: Exports optional-module data to readable NDJSON before dropping its
 *     schema and can restore it after the module is installed again.
 */
final readonly class ModuleDataArchive
{
    /** HR: Prima bazu i korijen aplikacije. EN: Receives the database and application root. */
    public function __construct(
        private Database $database,
        private string $appRoot,
    ) {
    }

    /**
     * HR: Stvara novu nepromjenjivu sigurnosnu kopiju svih postojećih tablica modula.
     * EN: Creates a new immutable backup of every existing module table.
     *
     * @param list<string> $tables
     */
    public function create(string $slug, array $tables): string
    {
        $root = $this->prepareSharedRoot($slug);
        $directory = $root . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        if (!mkdir($directory . '/tables', 02770, true) && !is_dir($directory . '/tables')) {
            throw new RuntimeException('Unable to create the module backup directory.');
        }

        if (!chmod($directory, 02770) || !chmod($directory . '/tables', 02770)) {
            throw new RuntimeException('Unable to secure the shared module backup directory.');
        }

        $manifest = [
            'format' => 'simbioza-module-data-ndjson',
            'version' => 1,
            'module' => $slug,
            'created_at' => gmdate(DATE_ATOM),
            'tables' => [],
        ];
        foreach ($tables as $table) {
            if (!$this->database->schema()->hasTable($table)) {
                continue;
            }

            $path = $directory . '/tables/' . $table . '.ndjson';
            $stream = fopen($path, 'xb');
            if (!is_resource($stream)) {
                throw new RuntimeException('Unable to open a module backup table file.');
            }

            $count = 0;
            try {
                if (!flock($stream, LOCK_EX)) {
                    throw new RuntimeException('Unable to lock a module backup table file.');
                }

                foreach ($this->database->table($table)->get() as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $encoded = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if (fwrite($stream, $encoded . "\n") === false) {
                        throw new RuntimeException('Unable to write a module backup row.');
                    }

                    ++$count;
                }

                fflush($stream);
            } finally {
                fclose($stream);
            }

            chmod($path, 0660);
            $manifest['tables'][] = ['name' => $table, 'rows' => $count];
        }

        $manifestPath = $directory . '/manifest.json';
        $encodedManifest = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        if (file_put_contents($manifestPath, $encodedManifest . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the module backup manifest.');
        }

        chmod($manifestPath, 0660);

        return $directory;
    }

    /** HR: Vraća najnoviju valjanu kopiju modula. EN: Returns the newest valid module backup. */
    public function latest(string $slug): ?string
    {
        $paths = glob($this->root($slug) . '/*/manifest.json');
        $paths = array_values(array_filter(is_array($paths) ? $paths : [], is_file(...)));
        rsort($paths, SORT_STRING);

        return isset($paths[0]) ? dirname($paths[0]) : null;
    }

    /**
     * HR: Vraća dostupne kopije od najnovije prema starijima.
     * EN: Returns available backups from newest to oldest.
     *
     * @return list<string>
     */
    public function all(string $slug): array
    {
        $paths = glob($this->root($slug) . '/*/manifest.json');
        $directories = array_values(array_map(
            dirname(...),
            array_filter(is_array($paths) ? $paths : [], is_file(...)),
        ));
        rsort($directories, SORT_STRING);

        return $directories;
    }

    /**
     * HR: Vraća retke iz kopije u upravo kreirane prazne tablice modula.
     * EN: Restores backup rows into the module's newly created empty tables.
     *
     * @param list<string> $allowedTables
     * @return array<string,int>
     */
    public function restore(string $directory, array $allowedTables): array
    {
        $manifestPath = rtrim($directory, DIRECTORY_SEPARATOR) . '/manifest.json';
        $manifest = is_file($manifestPath) ? json_decode((string)file_get_contents($manifestPath), true) : null;
        if (!is_array($manifest) || ($manifest['format'] ?? '') !== 'simbioza-module-data-ndjson') {
            throw new RuntimeException('The selected module backup is invalid.');
        }

        $result = $this->database->transaction(function (Database $database) use (
            $manifest,
            $allowedTables,
            $directory,
        ): array {
            $restored = [];
            foreach (is_array($manifest['tables'] ?? null) ? $manifest['tables'] : [] as $tableDefinition) {
                $table = is_array($tableDefinition) && is_string($tableDefinition['name'] ?? null)
                    ? trim($tableDefinition['name'])
                    : '';
                if (
                    $table === ''
                    || !in_array($table, $allowedTables, true)
                    || !$database->schema()->hasTable($table)
                ) {
                    continue;
                }

                if (is_array($database->table($table)->first())) {
                    throw new RuntimeException(sprintf('Restore target table "%s" is not empty.', $table));
                }

                $path = rtrim($directory, DIRECTORY_SEPARATOR) . '/tables/' . $table . '.ndjson';
                if (!is_file($path)) {
                    throw new RuntimeException(sprintf('Backup data for table "%s" is missing.', $table));
                }

                $stream = fopen($path, 'rb');
                if (!is_resource($stream)) {
                    throw new RuntimeException(sprintf('Backup data for table "%s" cannot be opened.', $table));
                }

                $count = 0;
                try {
                    while (($line = fgets($stream)) !== false) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }

                        $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                        if (!is_array($row)) {
                            throw new RuntimeException(sprintf('Backup row for table "%s" is invalid.', $table));
                        }

                        $normalizedRow = [];
                        foreach ($row as $column => $value) {
                            if (!is_string($column)) {
                                throw new RuntimeException('A module backup row contains an invalid column name.');
                            }

                            $normalizedRow[$column] = $value;
                        }

                        $database->table($table)->insert($normalizedRow);
                        ++$count;
                    }
                } finally {
                    fclose($stream);
                }

                $restored[$table] = $count;
            }

            return $restored;
        });
        if (!is_array($result)) {
            throw new RuntimeException('The module restore transaction returned an invalid result.');
        }

        $restored = [];
        foreach ($result as $table => $count) {
            if (!is_string($table) || !is_int($count)) {
                throw new RuntimeException('The module restore result is invalid.');
            }

            $restored[$table] = $count;
        }

        return $restored;
    }

    /** HR: Vraća privatni korijen kopija jednog modula. EN: Returns the private backup root for one module. */
    private function root(string $slug): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/module-backups/' . $slug;
    }

    /**
     * HR: Priprema privatni backup korijen zapisiv FPM-u i CLI održavateljima
     *     iz iste runtime grupe. Eksplicitni chmod poništava stroži process umask.
     * EN: Prepares a private backup root writable by FPM and CLI maintainers in
     *     the same runtime group. Explicit chmod overrides a stricter process umask.
     */
    private function prepareSharedRoot(string $slug): string
    {
        $base = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/module-backups';
        foreach ([$base, $base . '/' . $slug] as $directory) {
            if (!is_dir($directory)) {
                if (!mkdir($directory, 02770) && !is_dir($directory)) {
                    throw new RuntimeException('Unable to create the shared module backup root.');
                }

                if (!chmod($directory, 02770)) {
                    throw new RuntimeException('Unable to secure the shared module backup root.');
                }
            }
        }

        return $base . '/' . $slug;
    }
}
