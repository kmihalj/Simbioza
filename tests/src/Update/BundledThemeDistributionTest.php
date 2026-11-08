<?php

declare(strict_types=1);

namespace Tests\Update;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: The shipped archives and public previews expose the same neutral theme collection.
 *
 * HR: Isporučene arhive i javni pregledi sadrže istu neutralnu kolekciju tema.
 */
#[CoversNothing]
final class BundledThemeDistributionTest extends TestCase
{
    /**
     * EN: Each included archive is checksummed and retired institution presets are absent.
     *
     * HR: Svaka uključena arhiva ima provjeren hash, a povučeni institucijski preseti nisu prisutni.
     */
    public function testArchivesAndPublicGalleryMatch(): void
    {
        $root = dirname(__DIR__, 3);
        $manifest = json_decode(
            (string)file_get_contents($root . '/resources/installation/theme/collection.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($manifest);
        $this->assertIsArray($manifest['themes']);
        $this->assertCount(23, $manifest['themes']);
        $ids = [];
        foreach ($manifest['themes'] as $entry) {
            $this->assertIsArray($entry);
            $this->assertIsString($entry['id']);
            $ids[] = $entry['id'];
            $path = $root . '/resources/installation/theme/' . $entry['id'] . '.zip';
            $this->assertFileExists($path);
            $this->assertSame($entry['sha256'], hash_file('sha256', $path));
        }

        $catalog = json_decode(
            (string)file_get_contents($root . '/public/assets/theme-gallery/catalog.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($catalog);
        $this->assertEqualsCanonicalizing($ids, array_column($catalog, 'id'));
        foreach (['aai', 'dabar', 'srce-sup'] as $retired) {
            $this->assertNotContains($retired, $ids);
            $this->assertFileDoesNotExist($root . '/resources/installation/theme/' . $retired . '.zip');
            $this->assertSame([], glob($root . '/public/assets/theme-gallery/' . $retired . '-*.webp'));
            $this->assertSame([], glob($root . '/docs/images/theme-gallery/' . $retired . '-*.webp'));
        }
    }

    /**
     * EN: Both languages and appearances keep matching documentation images and no stale gallery links.
     *
     * HR: Oba jezika i izgleda imaju iste dokumentacijske slike bez zastarjelih poveznica galerije.
     */
    public function testScreenshotsAndOfflineLinksAreComplete(): void
    {
        $root = dirname(__DIR__, 3);
        $public = $root . '/public/assets/theme-gallery';
        $index = (string)file_get_contents($public . '/index.html');
        foreach (['en', 'hr'] as $locale) {
            $guide = (string)file_get_contents($root . '/docs/theme-gallery_' . $locale . '.md');
            foreach (['light', 'dark'] as $mode) {
                $files = glob($public . '/*-' . $mode . '-' . $locale . '.webp');
                $this->assertIsArray($files);
                $files = array_values(array_filter(
                    $files,
                    static fn(string $path): bool => !str_starts_with(basename($path), 'overview-'),
                ));
                $this->assertCount(23, $files);
                foreach ($files as $file) {
                    $this->assertStringContainsString(basename($file), $index);
                    $this->assertStringContainsString(basename($file), $guide);
                    $this->assertSame(
                        hash_file('sha256', $file),
                        hash_file('sha256', $root . '/docs/images/theme-gallery/' . basename($file)),
                    );
                }

                $name = 'overview-' . $mode . '-' . $locale . '.webp';
                $this->assertSame(
                    hash_file('sha256', $public . '/' . $name),
                    hash_file('sha256', $root . '/docs/images/theme-gallery/' . $name),
                );
            }

            foreach (['aai', 'dabar', 'srce-sup'] as $retired) {
                $this->assertStringNotContainsString('/' . $retired . '-', $guide);
                $this->assertStringNotContainsString('"' . $retired . '-', $index);
            }
        }
    }
}
