<?php

declare(strict_types=1);

namespace App\Localization;

use RuntimeException;

/**
 * EN: Stores installed translations as bounded JSON data; package PHP remains read-only code.
 *
 * HR: Instalirane prijevode sprema kao ograničene JSON podatke; paketni PHP ostaje read-only kod.
 */
final readonly class RuntimeLanguageStorage
{
    public const MAX_BYTES = 2097152;

    /**
     * EN: Uses the application's data mount, never the configuration directory.
     *
     * HR: Koristi podatkovni mount aplikacije, nikada konfiguracijski direktorij.
     */
    public function __construct(private string $appRoot)
    {
    }

    /**
     * EN: Rejects traversal, stream paths, and non-canonical locale identifiers.
     *
     * HR: Odbija traversal, stream putanje i nekanonske oznake jezika.
     */
    public static function assertLocale(string $locale): string
    {
        if (strlen($locale) > 32 || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1) {
            throw new RuntimeException('Invalid runtime locale.');
        }

        return $locale;
    }

    /**
     * EN: Resolves only locally defined language files and refuses subordinate symlinks.
     * The data root itself may be a deployment mount or declared symlink.
     *
     * HR: Razrješava samo lokalno zadane jezične datoteke i odbija podređene symlinkove.
     * Sam podatkovni root smije biti deployment mount ili deklarirani symlink.
     */
    public function path(string $relative): string
    {
        $pattern = '~\A(?:catalogs/([a-z0-9_-]+)\.json|flags/([a-z0-9_-]+)\.svg'
        . '|repository-versions\.json|\.packs\.lock)\z~D';
        if (preg_match($pattern, $relative, $match) !== 1) {
            throw new RuntimeException('Invalid runtime language path.');
        }

        foreach (array_slice($match, 1) as $locale) {
            if ($locale !== '') {
                self::assertLocale($locale);
            }
        }

        $path = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data';
        foreach (explode('/', 'languages/' . $relative) as $segment) {
            $path .= '/' . $segment;
            if (is_link($path)) {
                throw new RuntimeException('Runtime language files must not follow symbolic links.');
            }
        }

        return $path;
    }

    /**
     * EN: Tests resource presence without creating directories.
     *
     * HR: Provjerava prisutnost resursa bez stvaranja direktorija.
     */
    public function installed(string $locale): bool
    {
        return is_file($this->path('catalogs/' . self::assertLocale($locale) . '.json'));
    }

    /**
     * EN: Reads a data-only catalogue; invalid installed state is not silently ignored.
     *
     * HR: Čita podatkovni katalog; nevaljano instalirano stanje ne zanemaruje prešutno.
     *
     * @return array<string,string>
     */
    public function catalog(string $locale): array
    {
        $locale = self::assertLocale($locale);
        $bytes = $this->bytes('catalogs/' . $locale . '.json');
        if ($bytes === null) {
            return [];
        }

        $document = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        if (
            !is_array($document) || ($document['format'] ?? null) !== 'simbioza-runtime-translations'
            || ($document['version'] ?? null) !== 1 || ($document['locale'] ?? null) !== $locale
        ) {
            throw new RuntimeException('Invalid installed language catalogue.');
        }

        return self::translations($document['translations'] ?? null);
    }

    /**
     * EN: Allows only nonempty string keys and string values, without evaluating code.
     *
     * HR: Dopušta samo neprazne tekstualne ključeve i vrijednosti, bez izvršavanja koda.
     *
     * @return array<string,string>
     */
    public static function translations(mixed $values): array
    {
        if (!is_array($values)) {
            throw new RuntimeException('Language translations must be an object.');
        }

        $result = [];
        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new RuntimeException('Invalid language translation entry.');
            }

            $result[$key] = $value;
        }

        ksort($result, SORT_STRING);
        return $result;
    }

    /**
     * EN: Atomically publishes normalized JSON; no PHP resource is generated.
     *
     * HR: Atomski objavljuje normalizirani JSON; ne stvara PHP resurs.
     *
     * @param array<string,string> $translations
     */
    public function install(string $locale, array $translations): void
    {
        $locale = self::assertLocale($locale);
        $this->write('catalogs/' . $locale . '.json', json_encode([
            'format' => 'simbioza-runtime-translations', 'version' => 1,
            'locale' => $locale, 'translations' => (object)self::translations($translations),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }

    /**
     * EN: Enumerates only canonical installed JSON catalogues.
     *
     * HR: Navodi samo kanonske instalirane JSON kataloge.
     *
     * @return list<string>
     */
    public function locales(): array
    {
        $directory = dirname($this->path('catalogs/en.json'));
        $result = [];
        foreach (glob($directory . '/*.json') ?: [] as $file) {
            $locale = self::assertLocale(basename($file, '.json'));
            $this->path('catalogs/' . $locale . '.json');
            $result[] = $locale;
        }

        sort($result, SORT_STRING);
        return $result;
    }

    /**
     * EN: Bounds reads and refuses unreadable or non-regular files.
     *
     * HR: Ograničava čitanje i odbija nečitljive ili neregularne datoteke.
     */
    public function bytes(string $relative): ?string
    {
        $path = $this->path($relative);
        if (!file_exists($path)) {
            return null;
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Runtime language file is not readable.');
        }

        $bytes = file_get_contents($path, false, null, 0, self::MAX_BYTES + 1);
        if (!is_string($bytes) || strlen($bytes) > self::MAX_BYTES) {
            throw new RuntimeException('Runtime language file exceeds its size limit.');
        }

        return $bytes;
    }

    /**
     * EN: Validates bounded repository revision data, never treating malformed state as current.
     *
     * HR: Provjerava ograničene podatke revizija; nevaljano stanje nikad ne prikazuje kao aktualno.
     *
     * @return array<string,array{version:string,sha256:string}>
     */
    public static function validateVersions(mixed $versions): array
    {
        if (!is_array($versions)) {
            throw new RuntimeException('Invalid language revision state.');
        }

        $result = [];
        foreach ($versions as $locale => $entry) {
            if (
                !is_string($locale) || !is_array($entry) || !is_string($entry['version'] ?? null)
                || strlen($entry['version']) > 64 || !is_string($entry['sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $entry['sha256']) !== 1
            ) {
                throw new RuntimeException('Invalid language revision entry.');
            }

            $result[self::assertLocale($locale)] = ['version' => $entry['version'], 'sha256' => $entry['sha256']];
        }

        ksort($result, SORT_STRING);
        return $result;
    }

    /**
     * EN: Reads validated local revisions without silently ignoring broken JSON.
     *
     * HR: Čita provjerene lokalne revizije bez prešutnog zanemarivanja neispravnog JSON-a.
     *
     * @return array<string,array{version:string,sha256:string}>
     */
    public function versions(): array
    {
        $bytes = $this->bytes('repository-versions.json');
        return $bytes === null ? [] : self::validateVersions(json_decode($bytes, true, 64, JSON_THROW_ON_ERROR));
    }

    /**
     * EN: Publishes revisions under the caller's language lock.
     *
     * HR: Objavljuje revizije pod jezičnim lockom pozivatelja.
     *
     * @param array<string,array{version:string,sha256:string}> $versions
     */
    public function writeVersions(array $versions): void
    {
        $this->write('repository-versions.json', json_encode(
            (object)self::validateVersions($versions),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ) . "\n");
    }

    /**
     * EN: Checks the nearest parent before any state mutation.
     *
     * HR: Provjerava najbližeg roditelja prije bilo koje izmjene stanja.
     */
    public function assertWritable(string $relative): void
    {
        clearstatcache();
        $path = $this->path($relative);
        if (file_exists($path) && (!is_file($path) || !is_readable($path) || !is_writable($path))) {
            throw new RuntimeException('Runtime language file is not writable.');
        }

        $parent = dirname($path);
        while (!file_exists($parent)) {
            $parent = dirname($parent);
        }

        if (
            !is_dir($parent) || !is_writable($parent) || !is_readable($parent)
            || (DIRECTORY_SEPARATOR !== '\\' && !is_executable($parent))
        ) {
            throw new RuntimeException('Runtime language directory is not writable.');
        }
    }

    /**
     * EN: Writes within the data mount using same-directory atomic replacement.
     *
     * HR: Zapisuje unutar podatkovnog mounta atomskom zamjenom u istom direktoriju.
     */
    public function write(string $relative, string $bytes, int $mode = 0660): void
    {
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new RuntimeException('Runtime language file exceeds its size limit.');
        }

        $this->assertWritable($relative);
        $path = $this->path($relative);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the runtime language directory.');
        }

        $temporary = tempnam($directory, '.language-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create a temporary language file.');
        }

        try {
            if (
                file_put_contents($temporary, $bytes, LOCK_EX) === false || !chmod($temporary, $mode)
                || !rename($temporary, $path)
            ) {
                throw new RuntimeException('Unable to publish the runtime language file.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * EN: Removes only a validated data resource, never a bundled catalogue.
     *
     * HR: Uklanja samo provjereni podatkovni resurs, nikada paketni katalog.
     */
    public function remove(string $relative): void
    {
        $this->assertWritable($relative);
        $path = $this->path($relative);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to remove the runtime language file.');
        }
    }

    /**
     * EN: GUI, CLI, and backup share a nonblocking data-resource lock.
     *
     * HR: GUI, CLI i backup dijele neblokirajući lock podatkovnih resursa.
     *
     * @return resource
     */
    public function lock(): mixed
    {
        $this->assertWritable('.packs.lock');
        $path = $this->path('.packs.lock');
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new RuntimeException('Unable to create the runtime language directory.');
        }

        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the runtime language lock.');
        }

        if (!chmod($path, 0660) || !flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Runtime languages are being edited or are not writable.');
        }

        return $handle;
    }
}
