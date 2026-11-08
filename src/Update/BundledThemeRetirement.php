<?php

declare(strict_types=1);

namespace App\Update;

use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use RuntimeException;

/**
 * EN: Retires only unchanged, previously bundled themes; local exceptions and custom copies remain private.
 *
 * HR: Povlači samo neizmijenjene ranije priložene teme; lokalne iznimke i prilagođene kopije ostaju privatne.
 */
final readonly class BundledThemeRetirement
{
    /**
     * EN: Uses Theme's layered, atomic configuration writer and managed file paths.
     *
     * HR: Koristi slojevito atomsko zapisivanje postavki i upravljane putanje modula Tema.
     */
    public function __construct(private ThemeConfigRepository $themes)
    {
    }

    /**
     * EN: Checks the old import receipt, exact configuration and every file before removing anything.
     *     Permanent retirement markers also preserve any later explicit manual reimport.
     *
     * HR: Prije uklanjanja provjerava staru evidenciju uvoza, točne postavke i svaku datoteku.
     *     Trajne oznake povlačenja čuvaju i kasniji izričiti ručni ponovni uvoz.
     *
     * @param array<string, bool> $seen
     *
     * @return array<string, bool>
     */
    public function apply(string $root, array $seen): array
    {
        $path = $root . '/resources/installation/theme/retired.json';
        if (!is_file($path)) {
            return $seen;
        }

        $entries = $this->entries($path);
        $preserved = $this->preservedIds();
        $original = (string)file_get_contents($this->themes->themesJsonPath());
        $current = json_decode($original, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($current) || !array_is_list($current)) {
            throw new RuntimeException('Invalid installed theme list.');
        }

        $remove = [];
        foreach ($entries as $entry) {
            $id = $entry['id'];
            $marker = 'retired:' . $id;
            if (isset($preserved[$id]) || isset($seen[$marker])) {
                continue;
            }

            $theme = null;
            foreach ($current as $item) {
                if (is_array($item) && ($item['id'] ?? null) === $id) {
                    $theme = $item;
                    break;
                }
            }

            if (
                isset($seen[$id]) && is_array($theme)
                && in_array(self::fingerprint($theme), $entry['theme_sha256'], true)
                && $this->filesMatch($id, $entry['files'])
                && !$this->referencedElsewhere($current, $id)
            ) {
                $remove[] = $id;
            }

            // EN: Unknown or edited themes are a deliberate opt-out, never a future deletion candidate.
            //
            // HR: Nepoznate ili uređene teme trajna su iznimka, a ne kandidat za kasnije brisanje.
            $seen[$marker] = true;
        }

        if ($remove === []) {
            return $seen;
        }

        $remaining = array_values(array_filter(
            $current,
            static fn(mixed $item): bool => !is_array($item) || !in_array($item['id'] ?? null, $remove, true),
        ));
        if ($remaining === []) {
            throw new RuntimeException('Retiring bundled themes requires a retained fallback theme.');
        }

        $settings = json_decode(
            (string)file_get_contents($this->themes->settingsJsonPath()),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (!is_array($settings)) {
            throw new RuntimeException('Invalid installed theme settings.');
        }

        $fallback = null;
        if (in_array($settings['active_theme'] ?? null, $remove, true)) {
            $ids = array_column($remaining, 'id');
            $fallback = in_array('simbioza', $ids, true) ? 'simbioza'
            : (in_array('standard', $ids, true) ? 'standard' : ($ids[0] ?? null));
            if (!is_string($fallback)) {
                throw new RuntimeException('No valid retained theme is available.');
            }
        }

        $this->themes->writeJsonContents('themes', $this->json($remaining));
        try {
            if ($fallback !== null) {
                $settings['active_theme'] = $fallback;
                $this->themes->writeJsonContents('settings', $this->json($settings));
            }
        } catch (\Throwable $throwable) {
            $this->themes->writeJsonContents('themes', $original);
            throw $throwable;
        }

        foreach ($remove as $id) {
            $this->themes->deleteThemeFiles($id);
        }

        return $seen;
    }

    /**
     * EN: Canonical hashes retain every custom property and ignore only JSON object key order.
     *
     * HR: Kanonski hash čuva svako prilagođeno svojstvo i zanemaruje samo redoslijed ključeva JSON objekta.
     *
     * @param array<mixed> $theme
     */
    public static function fingerprint(array $theme): string
    {
        return hash('sha256', json_encode(
            self::sorted($theme),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }

    /**
     * EN: Validates trusted retirement metadata before touching local data.
     *
     * HR: Provjerava pouzdane metapodatke povlačenja prije izmjene lokalnih podataka.
     *
     * @return list<array{id:string,theme_sha256:list<string>,files:array<string,string>}>
     */
    private function entries(string $path): array
    {
        $data = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || ($data['version'] ?? null) !== 1 || !is_array($data['themes'] ?? null)) {
            throw new RuntimeException('Invalid retired theme manifest.');
        }

        $entries = [];
        foreach ($data['themes'] as $item) {
            if (
                !is_array($item) || !is_string($item['id'] ?? null)
                || preg_match('/\A[a-z0-9][a-z0-9._-]*\z/D', $item['id']) !== 1
                || !is_array($item['theme_sha256'] ?? null) || !array_is_list($item['theme_sha256'])
                || $item['theme_sha256'] === [] || !is_array($item['files'] ?? null)
            ) {
                throw new RuntimeException('Invalid retired theme entry.');
            }

            $hashes = [];
            foreach ($item['theme_sha256'] as $hash) {
                if (!is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1) {
                    throw new RuntimeException('Invalid retired theme fingerprint.');
                }

                $hashes[] = $hash;
            }

            $files = [];
            foreach ($item['files'] as $file => $hash) {
                if (
                    !is_string($file) || preg_match('~\A[a-zA-Z0-9._-]+(?:/[a-zA-Z0-9._-]+)*\z~D', $file) !== 1
                    || in_array('..', explode('/', $file), true) || in_array('.', explode('/', $file), true)
                    || !is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1
                ) {
                    throw new RuntimeException('Invalid retired theme file fingerprint.');
                }

                $files[$file] = $hash;
            }

            ksort($files);
            $entries[] = ['id' => $item['id'], 'theme_sha256' => $hashes, 'files' => $files];
        }

        return $entries;
    }

    /**
     * EN: A private, ignored configuration-layer policy can retain development or institutional themes.
     *
     * HR: Privatna zanemarena politika konfiguracijskog sloja može sačuvati razvojne ili institucijske teme.
     *
     * @return array<string, bool>
     */
    private function preservedIds(): array
    {
        foreach (array_reverse($this->themes->configurationFiles()->directories()) as $directory) {
            $path = $directory . '/theme/collection-preserve.json';
            if (!file_exists($path) && !is_link($path)) {
                continue;
            }

            if (is_link($path) || !is_file($path)) {
                throw new RuntimeException('Unsafe local theme preservation policy.');
            }

            $data = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (
                !is_array($data) || ($data['version'] ?? null) !== 1
                || !is_array($data['preserve_theme_ids'] ?? null) || !array_is_list($data['preserve_theme_ids'])
            ) {
                throw new RuntimeException('Invalid local theme preservation policy.');
            }

            $ids = [];
            foreach ($data['preserve_theme_ids'] as $id) {
                if (!is_string($id) || preg_match('/\A[a-z0-9][a-z0-9._-]*\z/D', $id) !== 1) {
                    throw new RuntimeException('Invalid preserved theme identity.');
                }

                $ids[$id] = true;
            }

            return $ids;
        }

        return [];
    }

    /**
     * EN: Extra files, directories, links or changed bytes identify a custom library and prevent removal.
     *
     * HR: Dodatne datoteke, direktoriji, linkovi ili izmijenjeni bajtovi označavaju
     *     vlastitu biblioteku i sprečavaju brisanje.
     *
     * @param array<string, string> $expected
     */
    private function filesMatch(string $id, array $expected): bool
    {
        $root = $this->themes->themesDirectoryPath(false) . '/' . $id;
        if (is_link($root) || (file_exists($root) && !is_dir($root))) {
            return false;
        }

        if (!is_dir($root)) {
            return $expected === [];
        }

        $actual = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                return false;
            }

            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
            if ($file->isLink()) {
                return false;
            }

            if ($file->isDir()) {
                $hasExpectedChild = false;
                foreach (array_keys($expected) as $path) {
                    $hasExpectedChild = $hasExpectedChild || str_starts_with($path, $relative . '/');
                }

                if (!$hasExpectedChild) {
                    return false;
                }

                continue;
            }

            if (!$file->isFile() || !isset($expected[$relative])) {
                return false;
            }

            $actual[$relative] = hash_file('sha256', $file->getPathname());
        }

        ksort($actual);

        return $actual === $expected;
    }

    /**
     * EN: Shared references in other local themes protect the source library as well.
     *
     * HR: Zajedničke reference drugih lokalnih tema štite i izvornu biblioteku.
     *
     * @param list<mixed> $themes
     */
    private function referencedElsewhere(array $themes, string $id): bool
    {
        foreach ($themes as $theme) {
            if (
                is_array($theme) && ($theme['id'] ?? null) !== $id
                && $this->references($theme, '@theme-assets/' . $id . '/')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * EN: Searches nested definitions without interpreting or executing their contents.
     *
     * HR: Pretražuje ugniježđene definicije bez tumačenja ili izvršavanja njihova sadržaja.
     */
    private function references(mixed $value, string $prefix): bool
    {
        if (is_string($value)) {
            return str_starts_with($value, $prefix);
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->references($item, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * EN: Recursively sorts JSON objects while preserving list order.
     *
     * HR: Rekurzivno sortira JSON objekte uz očuvanje redoslijeda lista.
     */
    private static function sorted(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = self::sorted($item);
        }

        return $value;
    }

    /**
     * EN: Encodes private records without dropping unknown settings.
     *
     * HR: Zapisuje privatne zapise bez odbacivanja nepoznatih postavki.
     *
     * @param array<mixed> $value
     */
    private function json(array $value): string
    {
        return json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ) . PHP_EOL;
    }
}
