<?php

declare(strict_types=1);

namespace Tests\Branding;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

use function basename;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function str_starts_with;

#[CoversNothing]
final class SimbiozaBrandingTest extends TestCase
{
    /**
     * EN: Checks canonical packaged branding, including all 24 approved assets,
     *     without depending on an installation's mutable configuration or data.
     *
     * HR: Provjerava tvornički branding iz paketa, uključujući sva 24 odobrena asseta,
     *     bez ovisnosti o promjenjivoj konfiguraciji ili podacima instalacije.
     */
    public function testCanonicalSimbiozaPackageContainsApprovedArtwork(): void
    {
        $archive = $this->themeArchive('simbioza');
        try {
            $simbioza = $this->decodeArchiveJson($archive, 'theme.json');
            $this->assertSame('simbioza', $simbioza['id'] ?? null);
            $components = $this->arrayValue($simbioza, 'components');
            $hero = $this->arrayValue($components, 'hero');
            $header = $this->arrayValue($components, 'header');
            $headerItems = $this->arrayValue($header, 'items');
            $logo = $this->arrayValue($headerItems, 0);

            $this->assertSame('medium', $hero['home_size']);
            $this->assertSame('medium', $hero['inner_size']);
            $this->assertSame(560, $hero['visual_width_px']);
            $this->assertSame(-48, $hero['visual_top_px']);
            $manifest = $this->decodeArchiveJson($archive, 'theme-files/theme-assets.json');
            $assets = $this->arrayValue($manifest, 'assets');
            $this->assertCount(24, $assets);
            $manifestFiles = [];
            foreach ($assets as $asset) {
                $this->assertIsArray($asset);
                $file = $asset['file'] ?? null;
                $this->assertIsString($file);
                $manifestFiles[$file] = $asset;
            }

            $this->assertCount(24, $manifestFiles);
            foreach (['light', 'dark'] as $variant) {
                $heroSource = $hero['visual_src_' . $variant] ?? null;
                $iconSource = $logo['src_' . $variant] ?? null;
                $this->assertIsString($heroSource);
                $this->assertIsString($iconSource);
                $this->assertStringStartsWith('@theme-assets/simbioza/', $heroSource);
                $this->assertStringStartsWith('@theme-assets/simbioza/', $iconSource);
                $this->assertSame('hero', $manifestFiles[basename($heroSource)]['role'] ?? null);
                $this->assertContains($manifestFiles[basename($iconSource)]['role'] ?? null, ['icon', 'logo']);
            }

            $palettes = [
                'natural-light',
                'adriatic-light',
                'botanical-light',
                'natural-dark',
                'adriatic-dark',
                'botanical-dark',
            ];
            foreach ($palettes as $palette) {
                foreach (['hero' => 1600, 'icon' => 512] as $kind => $size) {
                    foreach (['png', 'svg'] as $extension) {
                        $file = $kind . '-' . $palette . '.' . $extension;
                        $bytes = $this->archiveFile($archive, 'theme-files/assets/' . $file);
                        $this->assertArrayHasKey($file, $manifestFiles);
                        $this->assertSame(strlen($bytes), $manifestFiles[$file]['bytes'] ?? null);
                        $this->assertSame(hash('sha256', $bytes), $manifestFiles[$file]['sha256'] ?? null);

                        if ($extension === 'png') {
                            $dimensions = getimagesizefromstring($bytes);
                            $this->assertIsArray($dimensions);
                            $this->assertSame([$size, $size], [$dimensions[0], $dimensions[1]]);
                            $this->assertSame(6, ord($bytes[25]), $file . ' must use RGBA transparency.');
                        } else {
                            $this->assertStringContainsString(
                                'width="' . $size . '" height="' . $size . '" viewBox="0 0 1600 1600"',
                                $bytes,
                            );
                            $this->assertStringContainsString('<path ', $bytes);
                        }
                    }
                }
            }

            $master = $this->archiveFile($archive, 'theme-files/source/simbioza-master-natural-dark.png');
            $this->assertNotSame('', $master);
            $this->assertIsArray(getimagesizefromstring($master));
        } finally {
            $archive->close();
        }
    }

    /**
     * EN: Checks the exact public collection and every packaged managed asset,
     *     including portable references, independently of installed themes.
     *
     * HR: Provjerava točnu javnu kolekciju i svaki zapakirani upravljani asset,
     *     uključujući prenosive reference, neovisno o instaliranim temama.
     */
    public function testPublicThemeCollectionHasExactThemesAndManagedAssetIntegrity(): void
    {
        $directory = $this->projectRoot() . '/resources/installation/theme';
        $collection = $this->decodeJsonFile($directory . '/collection.json');
        $this->assertSame(1, $collection['version'] ?? null);
        $entries = $this->arrayValue($collection, 'themes');
        $expectedIds = [
            'simbioza', 'simbioza-atelje', 'simbioza-aurora', 'simbioza-blueprint',
            'simbioza-botanika', 'simbioza-cassette', 'simbioza-espresso', 'simbioza-festival',
            'simbioza-folio', 'simbioza-jadran', 'simbioza-kinoteka', 'simbioza-mali-svijet',
            'simbioza-manga', 'simbioza-neon-arcade', 'simbioza-nocturne', 'simbioza-orbit',
            'simbioza-planina', 'simbioza-pop-studio', 'simbioza-sakura', 'simbioza-stadion',
            'simbioza-terminal-84', 'simbioza-ucionica', 'standard',
        ];
        $ids = [];
        foreach ($entries as $entry) {
            $this->assertIsArray($entry);
            $id = $entry['id'] ?? null;
            $this->assertIsString($id);
            $this->assertContains($id, $expectedIds);
            $ids[] = $id;
            $path = $directory . '/' . $id . '.zip';
            $this->assertFileExists($path);
            $this->assertSame(hash_file('sha256', $path), $entry['sha256'] ?? null);

            $archive = $this->themeArchive($id);
            try {
                $theme = $this->decodeArchiveJson($archive, 'theme.json');
                $manifest = $this->decodeArchiveJson($archive, 'manifest.json');
                $this->assertSame($id, $theme['id'] ?? null);
                $this->assertSame('heartphrame-theme-export', $manifest['format'] ?? null);
                $this->assertSame(3, $manifest['version'] ?? null);
                $this->assertSame($id, $manifest['theme_id'] ?? null);
                $files = $this->arrayValue($manifest, 'files');
                $expectedMembers = ['manifest.json', 'theme.json'];
                foreach ($files as $file) {
                    $this->assertIsArray($file);
                    $relative = $file['path'] ?? null;
                    $this->assertIsString($relative);
                    $this->assertCanonicalPath($relative);
                    $member = 'theme-files/' . $relative;
                    $this->assertSame($member, $file['archive_path'] ?? null);
                    $expectedMembers[] = $member;
                    $bytes = $this->archiveFile($archive, $member);
                    $this->assertSame(hash('sha256', $bytes), $file['sha256'] ?? null);
                }

                $members = [];
                for ($index = 0; $index < $archive->numFiles; ++$index) {
                    $member = $archive->getNameIndex($index);
                    $this->assertIsString($member);
                    $this->assertCanonicalPath($member);
                    $members[] = $member;
                }

                $this->assertCount(count($members), array_unique($members), 'Duplicate ZIP members: ' . $id);
                $this->assertCount(count($expectedMembers), array_unique($expectedMembers));
                sort($expectedMembers);
                sort($members);
                $this->assertSame($expectedMembers, $members, 'Unlisted or missing package file: ' . $id);
                $this->assertManagedReferencesExist($archive, $theme, $id);

                if ($id === 'standard') {
                    $this->assertSame([], $files);
                    continue;
                }

                $managed = $this->decodeArchiveJson($archive, 'theme-files/theme-assets.json');
                $assets = $this->arrayValue($managed, 'assets');
                $this->assertNotSame([], $assets);
                $managedMembers = [];
                foreach ($assets as $asset) {
                    $this->assertIsArray($asset);
                    $file = $asset['file'] ?? null;
                    $this->assertIsString($file);
                    $this->assertCanonicalPath($file);
                    $this->assertSame(basename($file), $file);
                    $member = 'theme-files/assets/' . $file;
                    $managedMembers[] = $member;
                    $bytes = $this->archiveFile($archive, $member);
                    $this->assertSame(strlen($bytes), $asset['bytes'] ?? null);
                    $this->assertSame(hash('sha256', $bytes), $asset['sha256'] ?? null);
                }

                $assetMembers = array_values(array_filter(
                    $members,
                    static fn (string $member): bool => str_starts_with($member, 'theme-files/assets/'),
                ));
                $this->assertCount(count($managedMembers), array_unique($managedMembers));
                sort($managedMembers);
                sort($assetMembers);
                $this->assertSame($assetMembers, $managedMembers, 'Unmanaged or missing artwork: ' . $id);
            } finally {
                $archive->close();
            }
        }

        sort($ids);
        sort($expectedIds);
        $this->assertSame($expectedIds, $ids, 'The collection must contain exactly the 23 public themes.');
        $archives = glob($directory . '/*.zip');
        $this->assertIsArray($archives);
        $archiveIds = array_map(static fn (string $path): string => basename($path, '.zip'), $archives);
        sort($archiveIds);
        $this->assertSame($expectedIds, $archiveIds, 'The bundled ZIP inventory must match the public collection.');
    }

    /**
     * EN: Rejects absolute, ambiguous and parent-traversing package paths.
     *
     * HR: Odbija apsolutne, nejednoznačne putanje i izlazak iz direktorija paketa.
     */
    private function assertCanonicalPath(string $path): void
    {
        $this->assertStringNotContainsString('\\', $path);
        foreach (explode('/', $path) as $component) {
            $this->assertNotContains($component, ['', '.', '..']);
        }
    }

    /**
     * EN: Requires every portable theme asset reference to resolve inside its own package.
     *
     * HR: Zahtijeva da svaka prenosiva referenca asseta postoji unutar vlastitog paketa.
     */
    private function assertManagedReferencesExist(ZipArchive $archive, mixed $value, string $themeId): void
    {
        if (is_array($value)) {
            foreach ($value as $child) {
                $this->assertManagedReferencesExist($archive, $child, $themeId);
            }
        } elseif (is_string($value) && str_starts_with($value, '@theme-assets/')) {
            $prefix = '@theme-assets/' . $themeId . '/';
            $this->assertStringStartsWith($prefix, $value);
            $relative = substr($value, strlen($prefix));
            $this->assertCanonicalPath($relative);
            $this->assertNotSame('', $this->archiveFile($archive, 'theme-files/assets/' . $relative));
        }
    }

    /**
     * EN: Opens an immutable, bundled source package rather than an installed theme.
     *
     * HR: Otvara tvornički paket iz izvora umjesto instalirane teme.
     */
    private function themeArchive(string $themeId): ZipArchive
    {
        $path = $this->projectRoot() . '/resources/installation/theme/' . $themeId . '.zip';
        $this->assertFileExists($path);
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path), 'Cannot read bundled theme: ' . $themeId);
        return $archive;
    }

    /**
     * EN: Reads a required archive member without extracting files into an installation.
     *
     * HR: Čita obveznu stavku arhive bez raspakiravanja datoteka u instalaciju.
     */
    private function archiveFile(ZipArchive $archive, string $path): string
    {
        $bytes = $archive->getFromName($path);
        if (!is_string($bytes)) {
            throw new RuntimeException('Missing archive member: ' . $path);
        }

        return $bytes;
    }

    /**
     * EN: Decodes a required JSON archive member.
     *
     * HR: Dekodira obveznu JSON stavku arhive.
     *
     * @return array<mixed>
     */
    private function decodeArchiveJson(ZipArchive $archive, string $path): array
    {
        return $this->decodeJson($this->archiveFile($archive, $path));
    }

    /**
     * EN: Reads a canonical source JSON file.
     *
     * HR: Čita tvorničku izvornu JSON datoteku.
     *
     * @return array<mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new RuntimeException('Unable to read JSON file: ' . $path);
        }

        return $this->decodeJson($json);
    }

    /**
     * EN: Requires decoded JSON to contain an object or list.
     *
     * HR: Zahtijeva da dekodirani JSON sadrži objekt ili popis.
     *
     * @return array<mixed>
     */
    private function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('JSON must contain an object or list.');
        }

        return $decoded;
    }

    /**
     * EN: Returns a required nested array from configuration.
     *
     * HR: Vraća obvezno ugniježđeno polje iz konfiguracije.
     *
     * @param array<mixed> $source
     * @return array<mixed>
     */
    private function arrayValue(array $source, int|string $key): array
    {
        $value = $source[$key] ?? null;
        if (!is_array($value)) {
            throw new RuntimeException('Expected a nested configuration array.');
        }

        return $value;
    }

    /**
     * EN: Returns the application source root.
     *
     * HR: Vraća korijen aplikacijskog izvora.
     */
    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
