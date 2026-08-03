<?php

declare(strict_types=1);

namespace Tests\Branding;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
     * HR: Dokazuje da je tema aktivna i da branding sadrži 24 cjelovita upravljana asseta.
     *
     * EN: Proves the theme is active and branding contains 24 complete managed assets.
     */
    public function testSimbiozaThemeAndApprovedArtworkAreInstalled(): void
    {
        $root = $this->projectRoot();
        $app = $this->appConfig($root . '/config/app.php');
        $settings = $this->decodeJsonFile($root . '/resources/config/theme/settings.json');
        $themes = $this->decodeJsonFile($root . '/resources/config/theme/themes.json');

        $this->assertSame('Simbioza', $app['name']);
        $localization = $this->arrayValue($app, 'localization');
        $this->assertSame('hr', $localization['locale']);
        $this->assertSame('simbioza', $settings['active_theme']);

        $simbioza = null;
        foreach ($themes as $theme) {
            if (is_array($theme) && ($theme['id'] ?? null) === 'simbioza') {
                $simbioza = $theme;
                break;
            }
        }

        if (!is_array($simbioza)) {
            throw new RuntimeException('The Simbioza theme is missing.');
        }

        $components = $this->arrayValue($simbioza, 'components');
        $hero = $this->arrayValue($components, 'hero');
        $header = $this->arrayValue($components, 'header');
        $headerItems = $this->arrayValue($header, 'items');
        $logo = $this->arrayValue($headerItems, 0);

        $this->assertSame('medium', $hero['home_size']);
        $this->assertSame('medium', $hero['inner_size']);
        $this->assertSame(560, $hero['visual_width_px']);
        $this->assertSame(-48, $hero['visual_top_px']);
        $themeRoot = $root . '/data/themes/simbioza';
        $manifest = $this->decodeJsonFile($themeRoot . '/theme-assets.json');
        $assets = $this->arrayValue($manifest, 'assets');
        $this->assertCount(24, $assets);
        $manifestFiles = [];
        foreach ($assets as $asset) {
            if (is_array($asset) && is_string($asset['file'] ?? null)) {
                $manifestFiles[$asset['file']] = $asset;
            }
        }

        $selectedHero = is_string($hero['visual_src'] ?? null) ? $hero['visual_src'] : '';
        $selectedIcon = is_string($logo['src'] ?? null) ? $logo['src'] : '';
        $this->assertTrue(str_starts_with($selectedHero, '@theme-assets/simbioza/'));
        $this->assertTrue(str_starts_with($selectedIcon, '@theme-assets/simbioza/'));
        $this->assertSame('hero', $manifestFiles[basename($selectedHero)]['role'] ?? null);
        $this->assertContains($manifestFiles[basename($selectedIcon)]['role'] ?? null, ['icon', 'logo']);

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
                    $path = $themeRoot . '/assets/' . $file;
                    $this->assertFileExists($path);
                    $this->assertArrayHasKey($file, $manifestFiles);
                    $this->assertSame(hash_file('sha256', $path), $manifestFiles[$file]['sha256']);

                    if ($extension === 'png') {
                        $dimensions = getimagesize($path);
                        $this->assertIsArray($dimensions);
                        $this->assertSame([$size, $size], [$dimensions[0], $dimensions[1]]);
                        $header = file_get_contents($path, false, null, 0, 26);
                        $this->assertIsString($header);
                        $this->assertSame(6, ord($header[25]), $file . ' must use RGBA transparency.');
                    } else {
                        $svg = file_get_contents($path);
                        $this->assertIsString($svg);
                        $this->assertStringContainsString(
                            'width="' . $size . '" height="' . $size . '" viewBox="0 0 1600 1600"',
                            $svg,
                        );
                        $this->assertStringContainsString('<path ', $svg);
                    }
                }
            }
        }

        $this->assertFileExists($themeRoot . '/source/simbioza-master-natural-dark.png');
        $this->assertFileDoesNotExist($root . '/public/theme-assets/simbioza');
    }

    /**
     * HR: Učitava aplikacijsku PHP konfiguraciju kao polje.
     *
     * EN: Loads the application PHP configuration as an array.
     *
     * @return array<mixed>
     */
    private function appConfig(string $path): array
    {
        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('Application config must return an array.');
        }

        return $config;
    }

    /**
     * HR: Čita JSON datoteku i zahtijeva objekt ili popis.
     *
     * EN: Reads a JSON file and requires an object or list.
     *
     * @return array<mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new RuntimeException('Unable to read JSON file: ' . $path);
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('JSON file must contain an object or list: ' . $path);
        }

        return $decoded;
    }

    /**
     * HR: Vraća obvezno ugniježđeno polje iz konfiguracije.
     *
     * EN: Returns a required nested array from configuration.
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
     * HR: Vraća korijen aplikacijskog projekta.
     *
     * EN: Returns the application project root.
     */
    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
