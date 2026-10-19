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
     * @return list<array{locale:string,native_name:string,version:string,local_version:string,source:string,revision_known:bool,installed:bool,active:bool,update_available:bool}>
     */
    public function status(bool $refresh = false): array
    {
        $installed = [];
        foreach ($this->languages->status() as $entry) {
            $installed[$entry['locale']] = $entry;
        }

        $versions = $this->versions();
        $bundled = $this->bundledVersions();
        $storage = new RuntimeLanguageStorage($this->appRoot);
        $result = [];
        foreach ($this->repository->available($refresh) as $locale => $entry) {
            $local = $installed[$locale] ?? null;
            $state = $this->localState($locale, $local !== null, $versions, $bundled, $storage);
            $result[] = [
                'locale' => $locale,
                'native_name' => $entry['native_name'],
                'version' => $entry['version'],
                'local_version' => $state['version'],
                'source' => $state['source'],
                'revision_known' => $state['source'] === 'bundled' || $state['source'] === 'repository',
                'installed' => $local !== null,
                'active' => $local['active'] ?? false,
                'update_available' => $local !== null && $state['comparison_sha256'] !== ''
                    && $state['comparison_sha256'] !== $entry['sha256'],
            ];
            unset($installed[$locale]);
        }

        foreach ($installed as $locale => $local) {
            $state = $this->localState($locale, true, $versions, $bundled, $storage);
            $result[] = [
                'locale' => $locale,
                'native_name' => $local['native_name'],
                'version' => '',
                'local_version' => $state['version'],
                'source' => $state['source'],
                'revision_known' => $state['source'] === 'bundled' || $state['source'] === 'repository',
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
        $bundled = $this->bundledVersions();
        $storage = new RuntimeLanguageStorage($this->appRoot);
        $installed = array_fill_keys(array_column($this->languages->status(), 'locale'), true);
        foreach ($bundled as $locale => $entry) {
            if (isset($installed[$locale]) && !$storage->installed($locale)) {
                $versions[$locale] = ['version' => $entry['version'], 'sha256' => $entry['sha256']];
            }
        }

        $updated = [];
        foreach ($versions as $locale => $version) {
            if (
                isset($remote[$locale])
                && ($remote[$locale]['sha256'] !== $version['sha256']
                    || (!isset($bundled[$locale]) && !$storage->installed($locale)))
            ) {
                $this->install($locale, true);
                $updated[] = $locale;
            }
        }

        return $updated;
    }

    /**
     * EN: Identifies the actual active catalogue, never treating a manual JSON override as bundled.
     * A bundled language has the application's version; its repository digest is only an update baseline,
     * not a claim that the separate repository pack has been installed.
     *
     * HR: Razlikuje stvarni aktivni katalog; ručni JSON override nikada ne prikazuje kao ugrađen.
     * Ugrađeni jezik ima verziju aplikacije; digest repozitorija samo je polazište provjere nadogradnji,
     * ne tvrdnja da je zasebni paket repozitorija instaliran.
     *
     * @param array<string,array{version:string,sha256:string}> $versions
     * @param array<string,array{version:string,sha256:string}> $bundled
     * @return array{source:string,version:string,comparison_sha256:string}
     */
    private function localState(
        string $locale,
        bool $installed,
        array $versions,
        array $bundled,
        RuntimeLanguageStorage $storage,
    ): array {
        if (!$installed) {
            return ['source' => 'none', 'version' => '', 'comparison_sha256' => ''];
        }

        if ($storage->installed($locale)) {
            return isset($versions[$locale])
            ? ['source' => 'repository', 'version' => $versions[$locale]['version'],
                'comparison_sha256' => $versions[$locale]['sha256']]
            : ['source' => 'manual', 'version' => '', 'comparison_sha256' => ''];
        }

        return isset($bundled[$locale])
        ? ['source' => 'bundled', 'version' => $bundled[$locale]['version'],
            'comparison_sha256' => $bundled[$locale]['sha256']]
        : ['source' => 'manual', 'version' => '', 'comparison_sha256' => ''];
    }

    /**
     * EN: Reads immutable release metadata without network access or runtime-state writes.
     * The original repository baseline stays stable across code upgrades, so translation-only
     * publications remain detectable; application VERSION identifies the shipped PHP catalogues.
     *
     * HR: Čita nepromjenjive metapodatke izdanja bez mreže i zapisivanja runtime stanja.
     * Izvorno polazište repozitorija ostaje isto pri nadogradnjama koda kako bi se otkrivale
     * zasebne objave prijevoda; VERSION aplikacije označava isporučene PHP kataloge.
     *
     * @return array<string,array{version:string,sha256:string}>
     */
    private function bundledVersions(): array
    {
        $file = $this->appRoot . '/resources/localization/bundled-languages.json';
        if (!is_file($file)) {
            return [];
        }

        $metadata = json_decode((string)file_get_contents($file, false, null, 0, 65537), true, 64, JSON_THROW_ON_ERROR);
        if (
            !is_array($metadata) || ($metadata['format'] ?? null) !== 'simbioza-bundled-languages'
            || ($metadata['version'] ?? null) !== 1
        ) {
            throw new RuntimeException('Invalid bundled-language release metadata.');
        }

        $version = is_file($this->appRoot . '/VERSION')
        ? trim((string)file_get_contents($this->appRoot . '/VERSION', false, null, 0, 65)) : '';
        if (preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/D', $version) !== 1) {
            throw new RuntimeException('Invalid application version for bundled languages.');
        }

        $result = [];
        $baseline = RuntimeLanguageStorage::validateVersions($metadata['repository_baseline'] ?? null);
        foreach ($baseline as $locale => $entry) {
            if (is_file($this->appRoot . '/lang/' . $locale . '.php')) {
                $result[$locale] = ['version' => $version, 'sha256' => $entry['sha256']];
            }
        }

        return $result;
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
