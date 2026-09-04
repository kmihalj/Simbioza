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
     * HR: Dokazuje da aktivna administratorska tema postoji i da tvornički
     *     Simbioza branding sadrži 24 cjelovita upravljana asseta.
     *
     * EN: Proves the administrator-selected active theme exists and that the
     *     bundled Simbioza branding contains 24 complete managed assets.
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
        $activeThemeId = $settings['active_theme'] ?? null;
        $this->assertIsString($activeThemeId);
        $this->assertNotSame('', trim($activeThemeId));

        $simbioza = null;
        $activeThemeExists = false;
        foreach ($themes as $theme) {
            if (is_array($theme) && ($theme['id'] ?? null) === $activeThemeId) {
                $activeThemeExists = true;
            }

            if (is_array($theme) && ($theme['id'] ?? null) === 'simbioza') {
                $simbioza = $theme;
            }
        }

        $this->assertTrue($activeThemeExists, 'The selected active theme must exist in the theme library.');

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

        foreach (['light', 'dark'] as $variant) {
            $heroSource = $hero['visual_src_' . $variant] ?? null;
            $iconSource = $logo['src_' . $variant] ?? null;
            $selectedHero = is_string($heroSource) ? $heroSource : '';
            $selectedIcon = is_string($iconSource) ? $iconSource : '';
            $this->assertTrue(str_starts_with($selectedHero, '@theme-assets/simbioza/'));
            $this->assertTrue(str_starts_with($selectedIcon, '@theme-assets/simbioza/'));
            $this->assertSame('hero', $manifestFiles[basename($selectedHero)]['role'] ?? null);
            $this->assertContains($manifestFiles[basename($selectedIcon)]['role'] ?? null, ['icon', 'logo']);
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
     * HR: Dokazuje da partnerske teme koriste potpune lokalne pakete i dva logotipa.
     *
     * EN: Proves that partner themes use complete local packages and two logos.
     */
    public function testDabarAndAaiThemesUseManagedAssetsAndDualHeaderLogos(): void
    {
        $root = $this->projectRoot();
        $themes = $this->decodeJsonFile($root . '/resources/config/theme/themes.json');
        $themesById = [];
        foreach ($themes as $theme) {
            if (is_array($theme) && is_string($theme['id'] ?? null)) {
                $themesById[$theme['id']] = $theme;
            }
        }

        $simbiozaTheme = $themesById['simbioza'] ?? null;
        $this->assertIsArray($simbiozaTheme);
        $simbiozaComponents = $this->arrayValue($simbiozaTheme, 'components');

        $expectations = [
            'dabar' => [
                'hero' => 'hero-dabar-povecalo.svg',
                'logos' => ['logo-dabar-light.svg', 'logo-srce-55-light.svg'],
                'visual' => [650, 320, -64, 24],
                'light_gradient' => ['#D71635', '#A01F23'],
                'dark_gradient' => ['#A80F27', '#53131A'],
            ],
            'aai' => [
                'hero' => 'hero-aai-banner.svg',
                'logos' => ['logo-aaieduhr-light.svg', 'logo-srce-light.svg'],
                'visual' => [650, 320, -48, 24],
                'light_gradient' => ['#003567', '#1F8CA0'],
                'dark_gradient' => ['#001D38', '#155F70'],
            ],
        ];

        foreach ($expectations as $themeId => $expected) {
            $theme = $themesById[$themeId] ?? null;
            $this->assertIsArray($theme, 'Missing theme: ' . $themeId);
            $this->assertTrue($theme['system'] ?? false);

            $components = $this->arrayValue($theme, 'components');
            $header = $this->arrayValue($components, 'header');
            $simbiozaHeader = $this->arrayValue($simbiozaComponents, 'header');
            foreach (['sticky', 'container'] as $key) {
                $this->assertSame($simbiozaHeader[$key] ?? null, $header[$key] ?? null);
            }

            $headerItems = $this->arrayValue($header, 'items');
            $logoItems = array_values(array_filter(
                $headerItems,
                static fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === 'logo',
            ));
            $this->assertCount(2, $logoItems);
            $this->assertSame('start', $logoItems[0]['position'] ?? null);
            $this->assertSame('end', $logoItems[1]['position'] ?? null);
            $startLogoSource = $logoItems[0]['src_light'] ?? null;
            $endLogoSource = $logoItems[1]['src_light'] ?? null;
            $this->assertIsString($startLogoSource);
            $this->assertIsString($endLogoSource);
            $this->assertSame($expected['logos'][0], basename($startLogoSource));
            $this->assertSame($expected['logos'][1], basename($endLogoSource));

            $hero = $this->arrayValue($components, 'hero');
            $simbiozaHero = $this->arrayValue($simbiozaComponents, 'hero');
            foreach (['container', 'home_size', 'inner_size', 'wave'] as $key) {
                $this->assertSame($simbiozaHero[$key] ?? null, $hero[$key] ?? null);
            }

            $this->assertSame(
                '@theme-assets/' . $themeId . '/' . $expected['hero'],
                $hero['visual_src_light'] ?? null,
            );
            $this->assertSame($hero['visual_src_light'], $hero['visual_src_dark'] ?? null);
            $this->assertTrue($hero['wave'] ?? false);
            $this->assertTrue($hero['visual_allow_overflow'] ?? false);
            $this->assertSame($expected['visual'][0], $hero['visual_width_px'] ?? null);
            $this->assertSame($expected['visual'][1], $hero['visual_max_height_px'] ?? null);
            $this->assertSame($expected['visual'][2], $hero['visual_top_px'] ?? null);
            $this->assertSame($expected['visual'][3], $hero['visual_right_px'] ?? null);

            foreach (['navigation', 'content', 'cards'] as $componentName) {
                $this->assertSame(
                    $this->arrayValue($simbiozaComponents, $componentName),
                    $this->arrayValue($components, $componentName),
                );
            }

            foreach (['light', 'dark'] as $variant) {
                $variantConfig = $this->arrayValue($theme, $variant);
                $colors = $this->arrayValue($variantConfig, 'colors');
                $this->assertSame($expected[$variant . '_gradient'][0], $colors['hero_gradient_1'] ?? null);
                $this->assertSame($expected[$variant . '_gradient'][1], $colors['hero_gradient_5'] ?? null);
            }

            $themeRoot = $root . '/data/themes/' . $themeId;
            $manifest = $this->decodeJsonFile($themeRoot . '/theme-assets.json');
            $assets = $this->arrayValue($manifest, 'assets');
            $this->assertCount(5, $assets);
            foreach ($assets as $asset) {
                $this->assertIsArray($asset);
                $file = $asset['file'] ?? null;
                $this->assertIsString($file);
                $path = $themeRoot . '/assets/' . $file;
                $this->assertFileExists($path);
                $this->assertSame(filesize($path), $asset['bytes'] ?? null);
                $this->assertSame(hash_file('sha256', $path), $asset['sha256'] ?? null);
            }
        }
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
