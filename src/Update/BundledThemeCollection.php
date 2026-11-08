<?php

declare(strict_types=1);

namespace App\Update;

use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use RuntimeException;
use ZipArchive;

/**
 * EN: Offers each optional theme once, preserving administrator edits and deletions.
 *
 * HR: Svaku dodatnu temu nudi jednom, uz čuvanje administratorskih izmjena i brisanja.
 */
final readonly class BundledThemeCollection
{
    /**
     * EN: Uses the installed Theme service without enabling its runtime or GUI.
     *
     * HR: Koristi instalirani servis Tema bez uključivanja prikaza ili GUI-ja.
     */
    public function __construct(private ThemeConfigRepository $themes)
    {
    }

    /**
     * EN: Records existing themes and imports only new, checksummed collection IDs.
     *     Receipt IDs are permanent: changing a ZIP never resurrects a deleted theme.
     *
     * HR: Evidentira postojeće teme i uvozi samo nove ID-jeve provjerene kolekcije.
     *     Evidencija ID-jeva je trajna: izmjena ZIP-a ne vraća izbrisanu temu.
     *
     * @phpstan-impure
     */
    public function apply(string $root): int
    {
        $manifestPath = $root . '/resources/installation/theme/collection.json';
        if (!is_file($manifestPath)) {
            return 0;
        }

        $directory = $root . '/data';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Theme collection state directory is unavailable.');
        }

        $lockPath = $directory . '/theme-collection.lock';
        if (is_link($lockPath) || (file_exists($lockPath) && !is_file($lockPath))) {
            throw new RuntimeException('Unsafe theme collection lock path.');
        }

        $lock = fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Unable to lock theme collection installation.');
        }

        try {
            return $this->applyLocked($manifestPath);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * EN: Preflights every pending archive before modifying the theme library.
     *
     * HR: Provjerava sve nove arhive prije izmjene biblioteke tema.
     */
    private function applyLocked(string $manifestPath): int
    {
        $manifest = json_decode((string)file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || ($manifest['version'] ?? null) !== 1 || !is_array($manifest['themes'] ?? null)) {
            throw new RuntimeException('Invalid bundled theme collection manifest.');
        }

        $receipt = $this->themes->jsonWritePath('collection');
        if (!is_dir(dirname($receipt)) && !mkdir(dirname($receipt), 0770, true) && !is_dir(dirname($receipt))) {
            throw new RuntimeException('Theme collection configuration directory is unavailable.');
        }

        $recorded = is_file($receipt)
        ? json_decode((string)file_get_contents($receipt), true, 512, JSON_THROW_ON_ERROR) : [];
        if (!is_array($recorded)) {
            throw new RuntimeException('Invalid theme collection receipt.');
        }

        $seen = [];
        foreach ($recorded as $id => $done) {
            if (!is_string($id) || $done !== true) {
                throw new RuntimeException('Invalid theme collection receipt entry.');
            }

            $seen[$id] = true;
        }

        $ids = [];
        $existing = array_column($this->themes->themes(), null, 'id');
        $pending = [];
        foreach ($manifest['themes'] as $item) {
            if (
                !is_array($item) || !is_string($item['id'] ?? null)
                || preg_match('/\A[a-z0-9][a-z0-9._-]*\z/D', $item['id']) !== 1
                || !is_string($item['sha256'] ?? null)
                || preg_match('/\A[a-f0-9]{64}\z/D', $item['sha256']) !== 1
            ) {
                throw new RuntimeException('Invalid bundled theme entry.');
            }

            $id = $item['id'];
            $ids[] = $id;
            if (isset($seen[$id]) || isset($existing[$id])) {
                continue;
            }

            $path = dirname($manifestPath) . '/' . $id . '.zip';
            if (!is_file($path) || hash_file('sha256', $path) !== $item['sha256']) {
                throw new RuntimeException('Bundled theme checksum mismatch: ' . $id);
            }

            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                throw new RuntimeException('Bundled theme archive is unavailable: ' . $id);
            }

            try {
                $theme = json_decode((string)$zip->getFromName('theme.json'), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($theme) || ($theme['id'] ?? null) !== $id) {
                    throw new RuntimeException('Bundled theme identity mismatch: ' . $id);
                }
            } finally {
                $zip->close();
            }

            $pending[$id] = $path;
        }

        $archive = new ThemeArchiveService($this->themes, new ThemeAssetLibrary($this->themes));
        $count = 0;
        foreach ($ids as $id) {
            if (isset($seen[$id])) {
                continue;
            }

            if (isset($pending[$id])) {
                if ($archive->importFile($pending[$id]) !== $id) {
                    throw new RuntimeException('Unexpected imported theme identity.');
                }

                ++$count;
            }

            $seen[$id] = true;
            $this->writeReceipt($receipt, $seen);
        }

        $retired = (new BundledThemeRetirement($this->themes))->apply(dirname($manifestPath, 4), $seen);
        if ($retired !== $seen) {
            $this->writeReceipt($receipt, $retired);
        }

        return $count;
    }

    /**
     * EN: Commits progress atomically so an interrupted upgrade can safely resume.
     *
     * HR: Atomski sprema napredak kako bi se prekinuta nadogradnja sigurno nastavila.
     *
     * @param array<string, bool> $seen
     */
    private function writeReceipt(string $path, array $seen): void
    {
        $temporary = tempnam(dirname($path), '.theme-collection-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create theme collection receipt.');
        }

        try {
            $json = json_encode($seen, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
            if (
                file_put_contents($temporary, $json) === false || !chmod($temporary, 0660)
                || !rename($temporary, $path)
            ) {
                throw new RuntimeException('Unable to save theme collection receipt.');
            }

            BundledAssetPermissions::inheritOwnership(dirname($path), [basename($path)]);
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
