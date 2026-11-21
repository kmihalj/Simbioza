<?php

declare(strict_types=1);

namespace Tests\Update;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use App\Update\BundledThemeCollection;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: Exercises real archive imports and permanent administrator opt-outs in isolated sites.
 *
 * HR: Provjerava stvaran uvoz arhiva i trajno administratorsko uklanjanje u odvojenim siteovima.
 */
#[CoversNothing]
final class BundledThemeCollectionTest extends TestCase
{
    private string $root;

    private ThemeConfigRepository $themes;

    private mixed $oldConfig;

    private mixed $oldOverrides;

    /**
     * EN: Uses one shipped archive, real Theme storage and no HFC settings.
     *
     * HR: Koristi jednu priloženu arhivu i stvarno spremište Tema, bez HFC postavki.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/theme-collection-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config/theme', 0770, true);
        mkdir($this->root . '/resources/installation/theme', 0770, true);
        $this->oldConfig = getenv('HPH_CONFIG_PATH');
        $this->oldOverrides = getenv('HPH_CONFIG_OVERRIDE_PATHS');
        putenv('HPH_CONFIG_PATH=' . $this->root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS');
        $this->themes = new ThemeConfigRepository(
            new Config(new Helper(), [], $this->root),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-theme',
        );
        $archive = dirname(__DIR__, 3) . '/resources/installation/theme/simbioza-stadion.zip';
        copy($archive, $this->root . '/resources/installation/theme/simbioza-stadion.zip');
        file_put_contents($this->root . '/resources/installation/theme/collection.json', json_encode([
            'version' => 1, 'themes' => [['id' => 'simbioza-stadion', 'sha256' => hash_file('sha256', $archive)]],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($this->root . '/config/theme/themes.json', '[{"id":"standard","custom":"preserved"}]');
        file_put_contents(
            $this->root . '/config/theme/settings.json',
            '{"enabled":false,"active_theme":"standard","mode_policy":"dark","custom":"preserved"}',
        );
    }

    /**
     * EN: Removes only this test site and restores layered configuration variables.
     *
     * HR: Uklanja samo ovaj testni site i vraća varijable slojevite konfiguracije.
     */
    protected function tearDown(): void
    {
        putenv($this->oldConfig === false ? 'HPH_CONFIG_PATH' : 'HPH_CONFIG_PATH=' . $this->oldConfig);
        putenv($this->oldOverrides === false
            ? 'HPH_CONFIG_OVERRIDE_PATHS' : 'HPH_CONFIG_OVERRIDE_PATHS=' . $this->oldOverrides);
        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Imports once, keeps raw settings, respects later deletion and permits explicit reimport.
     *
     * HR: Uvozi jednom, čuva izvorne postavke, poštuje kasnije brisanje i dopušta izričit ponovni uvoz.
     */
    public function testUpgradeNeverResurrectsDeletedThemes(): void
    {
        $settings = file_get_contents($this->themes->settingsJsonPath());
        $upgrade = new BundledThemeCollection($this->themes);
        $this->assertSame(1, $upgrade->apply($this->root));
        $raw = json_decode((string)file_get_contents($this->themes->themesJsonPath()), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['id' => 'standard', 'custom' => 'preserved'], array_column($raw, null, 'id')['standard']);
        $this->assertSame($settings, file_get_contents($this->themes->settingsJsonPath()));
        $this->assertSame(0, $upgrade->apply($this->root));
        $this->themes->deleteTheme('simbioza-stadion');
        $this->assertSame(0, $upgrade->apply($this->root));
        $this->assertNotContains('simbioza-stadion', array_column($this->themes->themes(), 'id'));
        $archive = new ThemeArchiveService($this->themes, new ThemeAssetLibrary($this->themes));
        $this->assertSame('simbioza-stadion', $archive->importFile(
            $this->root . '/resources/installation/theme/simbioza-stadion.zip',
        ));
        $this->assertSame($settings, file_get_contents($this->themes->settingsJsonPath()));
        $this->assertFileExists($this->themes->jsonWritePath('collection'));
    }

    /**
     * EN: Existing customized themes are recorded without importing bundled replacements.
     *
     * HR: Postojeće prilagođene teme evidentiraju se bez uvoza priloženih zamjena.
     */
    public function testExistingThemeIsNeverReplaced(): void
    {
        $raw = '[{"id":"simbioza-stadion","custom":"my theme"},{"id":"standard"}]';
        file_put_contents($this->themes->themesJsonPath(), $raw);
        $this->assertSame(0, (new BundledThemeCollection($this->themes))->apply($this->root));
        $this->assertSame($raw, file_get_contents($this->themes->themesJsonPath()));
        $this->themes->deleteTheme('simbioza-stadion');
        $this->assertSame(0, (new BundledThemeCollection($this->themes))->apply($this->root));
    }

    /**
     * EN: New installs use wide containers; only the unmodified previous theme receives that default on upgrade.
     *
     * HR: Nove instalacije koriste široke okvire, a nadogradnja ih postavlja samo na neizmijenjenoj prethodnoj temi.
     */
    #[TestWith([false])]
    #[TestWith([true])]
    public function testDefaultWidthUpgradePreservesLocalEdits(bool $hasCustomWidth): void
    {
        $archivePath = $this->root . '/resources/installation/theme/simbioza.zip';
        copy(dirname(__DIR__, 3) . '/resources/installation/theme/simbioza.zip', $archivePath);
        $archive = new ThemeArchiveService($this->themes, new ThemeAssetLibrary($this->themes));
        $this->assertSame('simbioza', $archive->importFile($archivePath));
        $path = $this->themes->themesJsonPath();
        $settings = file_get_contents($this->themes->settingsJsonPath());
        $before = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $previous = $before;
        $index = array_search('simbioza', array_column($before, 'id'), true);
        $this->assertIsInt($index);
        foreach (['header', 'navigation', 'hero', 'content'] as $component) {
            $this->assertSame('wide', $before[$index]['components'][$component]['container']);
            $previous[$index]['components'][$component]['container'] = 'fluid';
        }
        $existing = $previous;
        if ($hasCustomWidth) {
            $existing[$index]['components']['content']['container'] = 'xxl';
        }
        file_put_contents($path, json_encode($existing, JSON_THROW_ON_ERROR));

        $upgrade = new BundledThemeCollection($this->themes);
        $upgrade->apply($this->root);
        $actual = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $actualIndex = array_search('simbioza', array_column($actual, 'id'), true);
        $this->assertIsInt($actualIndex);
        $this->assertEquals($hasCustomWidth ? $existing[$index] : $before[$index], $actual[$actualIndex]);
        $this->assertSame($settings, file_get_contents($this->themes->settingsJsonPath()));

        $actual[$actualIndex] = $previous[$index];
        file_put_contents($path, json_encode($actual, JSON_THROW_ON_ERROR));
        $upgrade->apply($this->root);
        $this->assertSame($actual, json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * EN: A damaged archive fails before any configuration or receipt is changed.
     *
     * HR: Oštećena arhiva prekida postupak prije izmjene konfiguracije ili evidencije.
     */
    public function testCorruptArchiveCannotMutateSettings(): void
    {
        $before = file_get_contents($this->themes->themesJsonPath());
        file_put_contents($this->root . '/resources/installation/theme/simbioza-stadion.zip', 'corrupt');
        try {
            (new BundledThemeCollection($this->themes))->apply($this->root);
            $this->fail('Expected checksum rejection.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('checksum mismatch', $runtimeException->getMessage());
        }

        $this->assertSame($before, file_get_contents($this->themes->themesJsonPath()));
        $this->assertFileDoesNotExist($this->themes->jsonWritePath('collection'));
    }
}
