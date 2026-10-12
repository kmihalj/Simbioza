<?php

declare(strict_types=1);

namespace App\Configuration;

use RuntimeException;

/**
 * EN: Converts the local settings allowlist to active backup paths without changing data.
 *
 * HR: Lokalni popis dopuštenih postavki pretvara u aktivne backup putanje, bez izmjene podataka.
 *
 * @phpstan-import-type ConfigDefinition from \AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider
 * @phpstan-import-type ConfigTarget from \AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider
 */
final readonly class ApplicationBackupConfiguration
{
    /** @var array<string,string> */
    private const INSTALLATION_MAPPING = [
        'name' => 'name', 'localization.locale' => 'primary_locale',
        'localization.supported_locales' => 'supported_locales',
        'localization.detect_browser_locale' => 'detect_browser_locale', 'timezone' => 'timezone',
        'session.options.gc_maxlifetime' => 'session_options.gc_maxlifetime',
        'session.options.cookie_lifetime' => 'session_options.cookie_lifetime',
        'session.options.cookie_secure' => 'session_options.cookie_secure',
        'session.options.cookie_httponly' => 'session_options.cookie_httponly',
        'session.options.cookie_samesite' => 'session_options.cookie_samesite',
    ];

    /**
     * EN: Shares the resolver with all application configuration flows.
     *
     * HR: Dijeli resolver sa svim aplikacijskim konfiguracijskim tokovima.
     */
    public function __construct(private ConfigurationPaths $paths)
    {
    }

    /**
     * EN: The archive never selects files, layers, or key mappings.
     *
     * HR: Arhiv nikada ne bira datoteke, slojeve ili mapiranja ključeva.
     *
     * @return list<ConfigDefinition>
     */
    public function resolve(mixed $definitions): array
    {
        if (!is_array($definitions)) {
            throw new RuntimeException('Application backup definitions must be an array.');
        }

        $this->paths->assertReadable();
        $resolved = [];
        foreach ($definitions as $definition) {
            if (
                !is_array($definition) || !is_string($definition['key'] ?? null)
                || !is_string($definition['file'] ?? null) || !is_array($definition['include_keys'] ?? null)
            ) {
                throw new RuntimeException('Invalid application backup definition.');
            }

            $include = [];
            foreach ($definition['include_keys'] as $key) {
                if (!is_string($key)) {
                    throw new RuntimeException('Backup keys must be strings.');
                }

                $include[] = $key;
            }

            $files = $this->paths->candidateFiles($definition['file']);
            $item = [
                'key' => $definition['key'], 'path' => $files[count($files) - 1],
                'read_paths' => $files, 'include_keys' => $include,
                'document' => (bool)($definition['document'] ?? false),
                'sensitive' => (bool)($definition['sensitive'] ?? false),
                'export' => (bool)($definition['export'] ?? true),
            ];
            $targets = $definition['restore_targets'] ?? [[
                'file' => $definition['file'], 'key_map' => array_combine($include, $include),
                ... (isset($definition['lock']) ? ['lock' => $definition['lock']] : []),
            ]];
            if ($definition['file'] === 'app.php') {
                // EN: Archives cannot replace dynamic app.php.
                //
                // HR: Arhive ne mogu zamijeniti dinamički app.php.
                $targets = [];
                $mapping = array_intersect_key(self::INSTALLATION_MAPPING, array_flip($include));
                if ($mapping !== []) {
                    $targets[] = ['file' => 'installation.php', 'lock' => '.languages.lock', 'key_map' => $mapping];
                }

                if (in_array('modules.enabled', $include, true)) {
                    $targets[] = ['file' => 'modules.php', 'document' => true, 'lock' => '.modules.lock',
                        'key_map' => ['modules.enabled' => 'enabled']];
                }
            }

            if (!is_array($targets) || $targets === []) {
                throw new RuntimeException('Application backup restore targets must be a nonempty array.');
            }

            $item['restore_targets'] = [];
            foreach ($targets as $target) {
                if (
                    !is_array($target) || !is_string($target['file'] ?? null)
                    || !is_array($target['key_map'] ?? null)
                ) {
                    throw new RuntimeException('Invalid application backup restore target.');
                }

                $keyMap = [];
                foreach ($target['key_map'] as $sourceKey => $targetKey) {
                    if (!is_string($sourceKey) || !is_string($targetKey)) {
                        throw new RuntimeException('Backup mapping keys must be strings.');
                    }

                    $keyMap[$sourceKey] = $targetKey;
                }

                $candidates = $this->paths->candidateFiles($target['file']);
                $mapped = [
                    'path' => $this->writeTarget($candidates[count($candidates) - 1]), 'read_paths' => $candidates,
                    'document' => (bool)($target['document'] ?? false), 'key_map' => $keyMap,
                ];
                if (isset($target['lock'])) {
                    if (!is_string($target['lock'])) {
                        throw new RuntimeException('Invalid backup configuration lock.');
                    }

                    $locks = $this->paths->candidateFiles($target['lock']);
                    $mapped['lock_path'] = $this->writeTarget($locks[count($locks) - 1]);
                }

                $item['restore_targets'][] = $mapped;
            }

            $resolved[] = $item;
        }

        return $resolved;
    }

    /**
     * EN: The declared root may be a symlink, unlike arbitrary subdirectories.
     *
     * HR: Sam deklarirani root smije biti symlink, ne proizvoljni poddirektorij.
     */
    private function writeTarget(string $path): string
    {
        $parent = dirname($path);
        $last = $this->paths->directories();
        if ($parent === rtrim($last[count($last) - 1], DIRECTORY_SEPARATOR) && is_link($parent)) {
            $resolved = realpath($parent);
            if (!is_string($resolved)) {
                throw new RuntimeException('The selected configuration directory link is invalid.');
            }

            return $resolved . DIRECTORY_SEPARATOR . basename($path);
        }

        return $path;
    }
}
