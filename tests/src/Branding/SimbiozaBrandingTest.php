<?php

declare(strict_types=1);

namespace Tests\Branding;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;

#[CoversNothing]
final class SimbiozaBrandingTest extends TestCase
{
    /**
     * HR: Dokazuje da je nova tema aktivna i da su svi odobreni vizuali zasebne datoteke.
     *
     * EN: Proves that the new theme is active and every approved artwork is a separate file.
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
        $this->assertSame(
            '@app/theme-assets/simbioza/simbioza-mark-natural-dark.png',
            $hero['visual_src'],
        );
        $this->assertSame(
            '@app/theme-assets/simbioza/simbioza-app-icon.png',
            $logo['src'],
        );

        foreach (
            [
                '01-natural-light.png',
                '02-adriatic-light.png',
                '03-botanical-light.png',
                '04-natural-dark.png',
                '05-adriatic-dark.png',
                '06-botanical-dark.png',
            ] as $preview
        ) {
            $this->assertFileExists($root . '/public/theme-assets/simbioza/previews/' . $preview);
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
