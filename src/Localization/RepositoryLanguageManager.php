<?php

declare(strict_types=1);

namespace App\Localization;

use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use RuntimeException;

/**
 * EN: Connects verified repository packs with the local installation state.
 *
 * HR: Povezuje provjerene pakete repozitorija sa stanjem lokalne instalacije.
 */
final readonly class RepositoryLanguageManager
{
    /**
     * EN: Receives the catalogue, installer, and application root.
     *
     * HR: Prima katalog, instalacijski alat i korijen aplikacije.
     */
    public function __construct(
        private LanguageRepository $repository,
        private LanguagePackManager $languages,
        private string $appRoot,
    ) {
    }

    /**
     * EN: Combines remote revisions with local state without assuming network availability.
     *
     * HR: Spaja udaljene revizije s lokalnim stanjem bez pretpostavke da je mreža dostupna.
     *
     * @return list<array{locale:string,native_name:string,version:string,local_version:string,revision_known:bool,installed:bool,active:bool,update_available:bool}>
     */
    public function status(bool $refresh = false): array
    {
        $installed = [];
        foreach ($this->languages->status() as $entry) {
            $installed[$entry['locale']] = $entry;
        }

        $versions = $this->versions();
        $result = [];
        foreach ($this->repository->available($refresh) as $locale => $entry) {
            $local = $installed[$locale] ?? null;
            $result[] = [
                'locale' => $locale,
                'native_name' => $entry['native_name'],
                'version' => $entry['version'],
                'local_version' => $versions[$locale]['version'] ?? '',
                'revision_known' => isset($versions[$locale]),
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
                'local_version' => $versions[$locale]['version'] ?? '',
                'revision_known' => isset($versions[$locale]),
                'installed' => true,
                'active' => $local['active'],
                'update_available' => false,
            ];
        }

        usort($result, static fn(array $left, array $right): int => strcmp($left['locale'], $right['locale']));
        return $result;
    }

    /**
     * EN: Downloads a released revision, validates the pack, and preserves its prior active state.
     *
     * HR: Preuzima objavljenu reviziju, provjerava paket i čuva prethodno stanje uključenosti.
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

        $installation = (new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->appRoot)))
            ->read('installation.php');
        $configuredLocales = is_array($installation['supported_locales'] ?? null)
        ? $installation['supported_locales'] : [];
        $activate = $existing !== null ? $existing['active']
        : (!isset($this->versions()[$locale]) || in_array($locale, $configuredLocales, true));
        $path = $this->repository->download($locale);
        try {
            if (hash_file('sha256', $path) !== $entry['sha256']) {
                throw new RuntimeException('The language catalogue changed during installation; retry the check.');
            }

            $this->languages->install($path, $replace, false, $activate, [
                'version' => $entry['version'], 'sha256' => $entry['sha256'],
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * EN: Installs multiple published languages through one constrained Setup request.
     *
     * HR: Instalira više objavljenih jezika jednim ograničenim Setup zahtjevom.
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
     * EN: Refreshes only previously installed packs when the repository publishes a new digest.
     *
     * HR: Osvježava samo ranije instalirane pakete kada repozitorij objavi novi digest.
     *
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

    /**
     * EN: Removes a language and its revision record.
     *
     * HR: Uklanja jezik i njegovu evidenciju revizije.
     */
    public function uninstall(string $locale): void
    {
        $this->languages->uninstall($locale);
    }

    /**
     * EN: Loads validated local repository revisions.
     *
     * HR: Učitava provjerene lokalne revizije repozitorija.
     *
     * @return array<string,array{version:string,sha256:string}>
     */
    private function versions(): array
    {
        return (new RuntimeLanguageStorage($this->appRoot))->versions();
    }
}
