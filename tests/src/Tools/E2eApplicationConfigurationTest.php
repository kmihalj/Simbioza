<?php

declare(strict_types=1);

namespace Tests\Tools;

use AaiEduHr\HeartPhrameModuleTheme\Controller\ThemeAssetController;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemePackageExporter;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeRenderer;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\Routing\UrlGenerator;
use HeartPhrame\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class E2eApplicationConfigurationTest extends TestCase
{
    private string $root;

    /**
     * EN: Creates only the small configuration tree consumed by the actual E2E preparation helper.
     *
     * HR: Stvara samo malo stablo postavki koje koristi stvarna pomoćna funkcija pripreme E2E instalacije.
     */
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/run_e2e.php';
        $this->root = sys_get_temp_dir() . '/simbioza-e2e-config-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config/api', 0700, true);
        mkdir($this->root . '/config/theme', 0700, true);
        file_put_contents($this->root . '/config/app.php', "<?php return ['cookie_secure' => 1,];\n");
        file_put_contents($this->root . '/config/api/api.php', "<?php return [];\n");
        file_put_contents($this->root . '/config/theme/settings.json', '{}');
    }

    /**
     * EN: Removes only this test's temporary files, never a real application configuration.
     *
     * HR: Uklanja samo privremene datoteke ovoga testa, nikada postavke stvarne aplikacije.
     */
    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }

    /**
     * EN: Tests clean installations, existing restrictions and already-configured fixtures with duplicate rows.
     *
     * HR: Provjerava čiste instalacije, postojeća ograničenja i već podešene testne tipove s ponovljenim retcima.
     *
     * @return \Iterator<string, array{list<string>|null, list<string>}>
     */
    public static function mimeConfigurations(): \Iterator
    {
        yield 'missing editor JSON' => [null, ['text/plain', 'image/png']];
        yield 'existing custom restriction' => [
            ['application/pdf', 'text/plain', 'application/pdf'],
            ['application/pdf', 'text/plain', 'image/png'],
        ];
        yield 'existing PNG and repeated fixture types' => [
            ['image/png', 'application/pdf', 'text/plain', 'image/png', 'text/plain'],
            ['image/png', 'application/pdf', 'text/plain'],
        ];
    }

    /**
     * EN: Preparation explicitly admits both binary PNG and text fixtures while retaining existing settings.
     *
     * HR: Priprema izričito dopušta binarne PNG i tekstne testne datoteke te čuva postojeće postavke.
     *
     * @param ?list<string> $existing
     * @param list<string> $expected
     */
    #[DataProvider('mimeConfigurations')]
    public function testAttachmentFixturePolicyPreservesExistingMimeTypes(?array $existing, array $expected): void
    {
        $path = $this->root . '/config/editor-html/editor-html.json';
        if ($existing !== null) {
            mkdir(dirname($path), 0700);
            file_put_contents($path, json_encode([
                'uploads' => ['allowed_mime_types' => $existing, 'max_size_mb' => 17],
                'storage' => ['driver' => 'database'],
            ], JSON_THROW_ON_ERROR));
        } else {
            $this->assertFileDoesNotExist($path);
        }

        \HFClean\Tools\configureE2eApplication($this->root);

        $configured = json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $configured['uploads']['allowed_mime_types']);
        if ($existing !== null) {
            $this->assertSame(17, $configured['uploads']['max_size_mb']);
            $this->assertSame(['driver' => 'database'], $configured['storage']);
        }
    }

    /**
     * EN: A source-only fixture starts without a theme library and imports every public file and managed reference.
     *     It must not depend on untracked artwork from the development site or leak its configuration layers.
     *
     * HR: Testni projekt samo iz izvornog paketa nema biblioteku tema i uvozi svaku javnu datoteku i referencu.
     *     Ne smije ovisiti o razvojnim datotekama izvan Gita niti ostaviti tuđe konfiguracijske slojeve.
     */
    public function testCleanPublicThemeFixtureImportsCompleteArchives(): void
    {
        $source = dirname(__DIR__, 3) . '/resources/installation/theme';
        $destination = $this->root . '/resources/installation/theme';
        mkdir($destination, 0700, true);
        $manifest = json_decode(
            (string)file_get_contents($source . '/collection.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        copy($source . '/collection.json', $destination . '/collection.json');
        foreach ($manifest['themes'] as $entry) {
            copy($source . '/' . $entry['id'] . '.zip', $destination . '/' . $entry['id'] . '.zip');
        }

        $oldConfig = getenv('HPH_CONFIG_PATH');
        $oldOverrides = getenv('HPH_CONFIG_OVERRIDE_PATHS');
        $this->assertDirectoryDoesNotExist($this->root . '/data/themes');
        \HFClean\Tools\configureE2eApplication($this->root);
        $this->assertSame($oldConfig, getenv('HPH_CONFIG_PATH'));
        $this->assertSame($oldOverrides, getenv('HPH_CONFIG_OVERRIDE_PATHS'));

        $themes = json_decode(
            (string)file_get_contents($this->root . '/config/theme/themes.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $expectedIds = array_column($manifest['themes'], 'id');
        $actualIds = array_column($themes, 'id');
        sort($expectedIds);
        sort($actualIds);
        $this->assertCount(23, $actualIds);
        $this->assertSame($expectedIds, $actualIds);
        foreach ($manifest['themes'] as $entry) {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($destination . '/' . $entry['id'] . '.zip'));
            try {
                $files = json_decode((string)$zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
                foreach ($files['files'] as $file) {
                    $target = $this->root . '/data/themes/' . $entry['id'] . '/' . $file['path'];
                    $this->assertFileExists($target);
                    $this->assertSame($file['sha256'], hash_file('sha256', $target));
                }
            } finally {
                $zip->close();
            }
        }

        putenv('HPH_CONFIG_PATH=' . $this->root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS');
        try {
            $repository = new \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository(
                new \HeartPhrame\Config\Config(new \HeartPhrame\Helper\Helper(), [], $this->root),
                dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-theme',
            );
            $renderer = new ThemeRenderer($repository, $this->createStub(UrlGenerator::class));
            $controller = new ThemeAssetController(
                new ResponseFactory(new StreamFactory(), $this->createStub(View::class)),
                $renderer,
                new ThemePackageExporter($repository, $renderer),
                new ThemeArchiveService($repository, new ThemeAssetLibrary($repository)),
                $repository,
            );
            $servedAssets = [];
            $checkReferences = function (mixed $value) use (
                &$checkReferences,
                $repository,
                $controller,
                &$servedAssets,
            ): void {
                if (is_array($value)) {
                    foreach ($value as $child) {
                        $checkReferences($child);
                    }
                } elseif (is_string($value) && str_starts_with($value, '@theme-assets/')) {
                    $target = $repository->themeAssetPath($value);
                    $this->assertNotNull($target, $value);
                    $this->assertFileExists($target);
                    if (!isset($servedAssets[$value])) {
                        [$themeId, $file] = explode('/', substr($value, strlen('@theme-assets/')), 2);
                        $response = $controller->libraryAsset($themeId, $file);
                        $this->assertSame(200, $response->getStatusCode(), $value);
                        $this->assertStringStartsWith('image/', $response->getHeaderLine('Content-Type'), $value);
                        $this->assertSame(hash_file('sha256', $target), hash('sha256', (string)$response->getBody()));
                        $servedAssets[$value] = true;
                    }
                }
            };
            $checkReferences($themes);
            $this->assertNotEmpty($servedAssets);
        } finally {
            putenv($oldConfig === false ? 'HPH_CONFIG_PATH' : 'HPH_CONFIG_PATH=' . $oldConfig);
            putenv($oldOverrides === false
                ? 'HPH_CONFIG_OVERRIDE_PATHS' : 'HPH_CONFIG_OVERRIDE_PATHS=' . $oldOverrides);
        }
    }

    /**
     * EN: Locally inherited private artwork is discarded only inside the disposable test project.
     *
     * HR: Lokalno naslijeđene privatne slike uklanjaju se samo unutar izdvojenoga testnog projekta.
     */
    public function testInheritedPrivateThemeFilesAreRemovedFromDisposableFixture(): void
    {
        foreach (['aai', 'dabar', 'srce-sup'] as $privateId) {
            mkdir($this->root . '/data/themes/' . $privateId, 0700, true);
            file_put_contents($this->root . '/data/themes/' . $privateId . '/private-marker.svg', 'private fixture');
        }

        \HFClean\Tools\configureE2eApplication($this->root);
        foreach (['aai', 'dabar', 'srce-sup'] as $privateId) {
            $this->assertDirectoryDoesNotExist($this->root . '/data/themes/' . $privateId);
        }

        $this->assertFileExists($this->root . '/data/themes/simbioza-blueprint/assets/hero-light.svg');
    }

    /**
     * EN: A host override cannot redirect the importer outside the disposable theme library.
     *     Rejection preserves both the external marker and the fixture's original theme files.
     *
     * HR: Aplikacijska postavka ne smije usmjeriti uvoz izvan izdvojene biblioteke tema.
     *     Odbijanje čuva vanjsku kontrolnu datoteku i izvorne datoteke tema testnog projekta.
     */
    public function testCustomThemeStorageIsRejectedBeforeAnyThemeFileChanges(): void
    {
        $external = $this->root . '/external-theme-store';
        mkdir($external, 0700);
        file_put_contents($external . '/marker.txt', 'external storage must remain untouched');
        mkdir($this->root . '/data/themes', 0700, true);
        file_put_contents($this->root . '/data/themes/marker.txt', 'fixture storage must remain untouched');
        file_put_contents(
            $this->root . '/config/theme/theme.php',
            '<?php return ' . var_export(['storage' => ['themes_dir' => $external]], true) . ';' . PHP_EOL,
        );
        $oldConfig = getenv('HPH_CONFIG_PATH');
        $oldOverrides = getenv('HPH_CONFIG_OVERRIDE_PATHS');

        try {
            \HFClean\Tools\configureE2eApplication($this->root);
            $this->fail('Custom theme storage must be rejected before import.');
        } catch (\RuntimeException $runtimeException) {
            $this->assertSame(
                'E2E theme storage must remain inside the disposable project.',
                $runtimeException->getMessage(),
            );
        }

        $this->assertSame('external storage must remain untouched', file_get_contents($external . '/marker.txt'));
        $this->assertSame(['marker.txt'], array_values(array_diff(scandir($external), ['.', '..'])));
        $this->assertSame(
            'fixture storage must remain untouched',
            file_get_contents($this->root . '/data/themes/marker.txt'),
        );
        $this->assertDirectoryDoesNotExist($this->root . '/data/themes/simbioza-blueprint');
        $this->assertSame($oldConfig, getenv('HPH_CONFIG_PATH'));
        $this->assertSame($oldOverrides, getenv('HPH_CONFIG_OVERRIDE_PATHS'));
    }
}
