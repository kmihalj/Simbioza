<?php

declare(strict_types=1);

namespace App\Localization;

use JsonException;
use RuntimeException;

/**
 * HR: Povezuje provjerene pakete repozitorija sa stanjem lokalne instalacije.
 * EN: Connects verified repository packs with the local installation state.
 */
final readonly class RepositoryLanguageManager
{
    /** HR: Prima katalog, instalacijski alat i korijen aplikacije. EN: Receives the catalogue, installer, and application root. */
    public function __construct(
        private LanguageRepository $repository,
        private LanguagePackManager $languages,
        private string $appRoot,
    ) {
    }

    /**
     * HR: Spaja udaljene revizije s lokalnim stanjem bez pretpostavke da je mreža dostupna.
     * EN: Combines remote revisions with local state without assuming network availability.
     * @return list<array{locale:string,native_name:string,version:string,installed:bool,active:bool,update_available:bool}>
     */
    public function status(): array
    {
        $installed = [];
        foreach ($this->languages->status() as $entry) {
            $installed[$entry['locale']] = $entry;
        }

        $versions = $this->versions();
        $result = [];
        foreach ($this->repository->available() as $locale => $entry) {
            $local = $installed[$locale] ?? null;
            $result[] = [
                'locale' => $locale,
                'native_name' => $entry['native_name'],
                'version' => $entry['version'],
                'installed' => $local !== null,
                'active' => $local['active'] ?? false,
                'update_available' => $local !== null && isset($versions[$locale])
                    && $versions[$locale]['sha256'] !== $entry['sha256'],
            ];
            unset($installed[$locale]);
        }

        foreach ($installed as $locale => $local) {
            $result[] = [
                'locale' => $locale,
                'native_name' => $local['native_name'],
                'version' => $versions[$locale]['version'] ?? '',
                'installed' => true,
                'active' => $local['active'],
                'update_available' => false,
            ];
        }

        usort($result, static fn(array $left, array $right): int => strcmp($left['locale'], $right['locale']));
        return $result;
    }

    /**
     * HR: Preuzima objavljenu reviziju, provjerava paket i čuva prethodno stanje uključenosti.
     * EN: Downloads a released revision, validates the pack, and preserves its prior active state.
     */
    public function install(string $locale, bool $replace = false): void
    {
        $entry = $this->repository->available()[$locale] ?? null;
        if ($entry === null) {
            throw new RuntimeException('The language is not published in the catalogue.');
        }

        $existing = null;
        foreach ($this->languages->status() as $local) {
            if ($local['locale'] === $locale) {
                $existing = $local;
                break;
            }
        }

        $installationPath = $this->appRoot . '/config/installation.php';
        $installation = is_file($installationPath) ? require $installationPath : [];
        $configuredLocales = is_array($installation) && is_array($installation['supported_locales'] ?? null)
        ? $installation['supported_locales'] : [];
        $activate = $existing !== null ? $existing['active']
        : (!isset($this->versions()[$locale]) || in_array($locale, $configuredLocales, true));
        $path = $this->repository->download($locale);
        try {
            $this->languages->install($path, $replace, false, $activate);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $versions = $this->versions();
        $versions[$locale] = ['version' => $entry['version'], 'sha256' => $entry['sha256']];
        $this->writeVersions($versions);
    }

    /**
     * HR: Instalira više objavljenih jezika jednim ograničenim Setup zahtjevom.
     * EN: Installs multiple published languages through one constrained Setup request.
     *
     * @param list<string> $locales
     * @return list<string>
     */
    public function installMany(array $locales, bool $replace = false): array
    {
        $available = $this->repository->available();
        $validated = [];
        foreach ($locales as $locale) {
            if (
                !is_string($locale)
                || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1
                || strlen($locale) > 32
                || !isset($available[$locale])
            ) {
                throw new RuntimeException('Jezik nije objavljen u katalogu.');
            }

            $validated[$locale] = true;
        }

        if ($validated === []) {
            throw new RuntimeException('Potrebno je odabrati barem jedan jezik.');
        }

        $installed = [];
        foreach (array_keys($validated) as $locale) {
            $this->install($locale, $replace);
            $installed[] = $locale;
        }

        return $installed;
    }

    /**
     * HR: Osvježava samo ranije instalirane pakete kada repozitorij objavi novi digest.
     * EN: Refreshes only previously installed packs when the repository publishes a new digest.
     * @return list<string>
     */
    public function updateInstalled(): array
    {
        $remote = $this->repository->available(true);
        $versions = $this->versions();
        $installed = array_fill_keys(array_column($this->languages->status(), 'locale'), true);
        $updated = [];
        foreach ($versions as $locale => $version) {
            if (
                isset($remote[$locale])
                && ($remote[$locale]['sha256'] !== $version['sha256'] || !isset($installed[$locale]))
            ) {
                $this->install($locale, true);
                $updated[] = $locale;
            }
        }

        return $updated;
    }

    /** HR: Uklanja jezik i njegovu evidenciju revizije. EN: Removes a language and its revision record. */
    public function uninstall(string $locale): void
    {
        $this->languages->uninstall($locale);
        $versions = $this->versions();
        unset($versions[$locale]);
        $this->writeVersions($versions);
    }

    /**
     * HR: Učitava provjerene lokalne revizije repozitorija.
     * EN: Loads validated local repository revisions.
     * @return array<string,array{version:string,sha256:string}>
     */
    private function versions(): array
    {
        $path = $this->versionPath();
        if (!is_file($path)) {
            return [];
        }

        try {
            $decoded = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        $result = [];
        foreach (is_array($decoded) ? $decoded : [] as $locale => $entry) {
            if (
                is_string($locale) && is_array($entry) && is_string($entry['version'] ?? null)
                && is_string($entry['sha256'] ?? null)
                && preg_match('/\A[a-f0-9]{64}\z/D', $entry['sha256']) === 1
            ) {
                $result[$locale] = ['version' => $entry['version'], 'sha256' => $entry['sha256']];
            }
        }

        return $result;
    }

    /**
     * HR: Atomski sprema revizije bez zapisa tajni.
     * EN: Atomically stores revisions without recording secrets.
     * @param array<string,array{version:string,sha256:string}> $versions
     */
    private function writeVersions(array $versions): void
    {
        $path = $this->versionPath();
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the language state directory.');
        }

        ksort($versions, SORT_STRING);
        $temporary = tempnam($directory, '.simbioza-versions-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create temporary language state.');
        }

        try {
            $contents = json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
            if (
                file_put_contents($temporary, $contents, LOCK_EX) === false
                || !chmod($temporary, 0660) || !rename($temporary, $path)
            ) {
                throw new RuntimeException('Unable to store language revisions.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /** HR: Vraća privatnu putanju revizija. EN: Returns the private revision path. */
    private function versionPath(): string
    {
        return $this->appRoot . '/data/languages/repository-versions.json';
    }
}
