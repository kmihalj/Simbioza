<?php

declare(strict_types=1);

namespace Tests\Update;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use App\Update\BundledThemeCollection;
use App\Update\BundledThemeRetirement;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: Exercises retirement against synthetic local themes, without distributing institutional assets.
 *
 * HR: Provjerava povlačenje na oglednim lokalnim temama bez isporučivanja institucijskih asseta.
 */
#[CoversNothing]
final class BundledThemeRetirementTest extends TestCase
{
    private string $root;

    private ThemeConfigRepository $themes;

    /** @var array{string|false,string|false} */
    private array $environment;

    /**
     * EN: Builds isolated themes and exact historic fingerprints; no installed site is read or changed.
     *
     * HR: Izrađuje odvojene teme i točne stare hashove; ne čita niti mijenja postojeću instalaciju.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/theme-retirement-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config/theme', 0700, true);
        mkdir($this->root . '/resources/installation/theme', 0700, true);
        $this->environment = [getenv('HPH_CONFIG_PATH'), getenv('HPH_CONFIG_OVERRIDE_PATHS')];
        putenv('HPH_CONFIG_PATH=' . $this->root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS');
        $this->themes = new ThemeConfigRepository(
            new Config(new Helper(), [], $this->root),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-theme',
        );
        $definitions = [['id' => 'standard', 'custom' => 'preserve me'], ['id' => 'simbioza']];
        $entries = [];
        $receipt = [];
        foreach (['aai', 'dabar', 'srce-sup'] as $id) {
            $theme = ['id' => $id, 'label' => ['en' => 'Synthetic legacy theme'], 'system' => true];
            $definitions[] = $theme;
            $directory = $this->root . '/data/themes/' . $id;
            mkdir($directory, 0700, true);
            file_put_contents($directory . '/example.txt', 'synthetic test asset');
            $entries[] = ['id' => $id, 'theme_sha256' => [BundledThemeRetirement::fingerprint($theme)],
                'files' => ['example.txt' => hash('sha256', 'synthetic test asset')]];
            $receipt[$id] = true;
        }

        $this->write('config/theme/themes.json', $definitions);
        $this->write('config/theme/settings.json', [
            'enabled' => false, 'active_theme' => 'aai', 'mode_policy' => 'dark', 'custom' => 'preserve setting',
        ]);
        $this->write('config/theme/collection.json', $receipt);
        $this->write('resources/installation/theme/collection.json', ['version' => 1, 'themes' => []]);
        $this->write('resources/installation/theme/retired.json', ['version' => 1, 'themes' => $entries]);
    }

    /**
     * EN: Restores process configuration and removes only this synthetic site.
     *
     * HR: Vraća konfiguraciju procesa i uklanja samo ovu oglednu instalaciju.
     */
    protected function tearDown(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $index => $name) {
            putenv($this->environment[$index] === false ? $name : $name . '=' . $this->environment[$index]);
        }

        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Exact former bundles are removed once, with a working fallback and untouched unrelated values.
     *
     * HR: Točni stari paketi uklanjaju se jednom uz ispravnu zamjensku temu i netaknute ostale vrijednosti.
     */
    public function testExactBundlesRetireOnceAndManualReimportSurvives(): void
    {
        $updater = new BundledThemeCollection($this->themes);
        $this->assertSame(0, $updater->apply($this->root));
        $remaining = $this->read('config/theme/themes.json');
        $this->assertSame([['id' => 'standard', 'custom' => 'preserve me'], ['id' => 'simbioza']], $remaining);
        $this->assertSame([
            'enabled' => false, 'active_theme' => 'simbioza', 'mode_policy' => 'dark', 'custom' => 'preserve setting',
        ], $this->read('config/theme/settings.json'));
        $receipt = $this->read('config/theme/collection.json');
        foreach (['aai', 'dabar', 'srce-sup'] as $id) {
            $this->assertDirectoryDoesNotExist($this->root . '/data/themes/' . $id);
            $this->assertTrue($receipt['retired:' . $id]);
        }

        $remaining[] = ['id' => 'aai', 'label' => ['en' => 'Synthetic legacy theme'], 'system' => true];
        $this->write('config/theme/themes.json', $remaining);
        mkdir($this->root . '/data/themes/aai', 0700, true);
        file_put_contents($this->root . '/data/themes/aai/example.txt', 'synthetic test asset');
        $before = $this->snapshot();
        $this->assertSame(0, $updater->apply($this->root));
        $this->assertSame(
            $before,
            $this->snapshot(),
            'An explicit reimport after retirement is an administrator choice.',
        );
    }

    /**
     * EN: An ignored installation policy protects exact old bundles, including their original receipt bytes.
     *
     * HR: Zanemarena lokalna politika štiti točne stare pakete, uključujući izvorne bajtove evidencije.
     */
    public function testLocalPolicyPreservesAllPrivateThemeData(): void
    {
        $this->write('config/theme/collection-preserve.json', [
            'version' => 1, 'preserve_theme_ids' => ['aai', 'dabar', 'srce-sup'],
        ]);
        $before = $this->snapshot();
        $this->assertSame(0, (new BundledThemeCollection($this->themes))->apply($this->root));
        $this->assertSame($before, $this->snapshot());
    }

    /**
     * EN: Unknown fields and changed or extra library files identify local customizations.
     *
     * HR: Nepoznata svojstva te izmijenjene ili dodatne datoteke biblioteke označavaju lokalne prilagodbe.
     */
    public function testCustomConfigurationAndAssetsAreNeverRemoved(): void
    {
        $definitions = $this->read('config/theme/themes.json');
        $definitions[2]['custom-local-property'] = 'keep';
        $this->write('config/theme/themes.json', $definitions);
        file_put_contents($this->root . '/data/themes/dabar/example.txt', 'edited asset');
        file_put_contents($this->root . '/data/themes/srce-sup/extra.txt', 'own asset');
        $before = $this->snapshot();
        $this->assertSame(0, (new BundledThemeCollection($this->themes))->apply($this->root));
        $after = $this->snapshot();
        unset($before['config/theme/collection.json'], $after['config/theme/collection.json']);
        $this->assertSame($before, $after);
        foreach (['aai', 'dabar', 'srce-sup'] as $id) {
            $this->assertTrue($this->read('config/theme/collection.json')['retired:' . $id]);
        }
    }

    /**
     * EN: A missing historic receipt and another theme's shared asset reference both prevent deletion.
     *
     * HR: Nedostajuća stara evidencija i zajednička referenca druge teme sprečavaju brisanje.
     */
    public function testReceiptAndCrossThemeReferencesAreRequiredForRemoval(): void
    {
        $receipt = $this->read('config/theme/collection.json');
        unset($receipt['aai']);
        $this->write('config/theme/collection.json', $receipt);
        $definitions = $this->read('config/theme/themes.json');
        $definitions[0]['shared-image'] = '@theme-assets/dabar/example.txt';
        $this->write('config/theme/themes.json', $definitions);
        (new BundledThemeCollection($this->themes))->apply($this->root);
        $this->assertSame(
            ['standard', 'simbioza', 'aai', 'dabar'],
            array_column($this->read('config/theme/themes.json'), 'id'),
        );
        $this->assertFileExists($this->root . '/data/themes/aai/example.txt');
        $this->assertFileExists($this->root . '/data/themes/dabar/example.txt');
        $this->assertDirectoryDoesNotExist($this->root . '/data/themes/srce-sup');
    }

    /**
     * EN: Symbolic links or new empty directories are private library changes, never cleanup targets.
     *
     * HR: Simbolički linkovi i novi prazni direktoriji privatne su izmjene biblioteke, a ne cilj čišćenja.
     */
    public function testPrivateDirectoriesAndSymlinksArePreserved(): void
    {
        mkdir($this->root . '/data/themes/aai/own-directory', 0700);
        file_put_contents($this->root . '/outside.txt', 'outside');
        symlink($this->root . '/outside.txt', $this->root . '/data/themes/dabar/link.txt');
        (new BundledThemeCollection($this->themes))->apply($this->root);
        $this->assertDirectoryExists($this->root . '/data/themes/aai/own-directory');
        $this->assertTrue(is_link($this->root . '/data/themes/dabar/link.txt'));
        $this->assertSame('outside', file_get_contents($this->root . '/outside.txt'));
        $this->assertSame(
            ['standard', 'simbioza', 'aai', 'dabar'],
            array_column($this->read('config/theme/themes.json'), 'id'),
        );
    }

    /**
     * EN: Invalid preservation metadata fails before changing configuration or managed files.
     *
     * HR: Neispravni metapodaci čuvanja prekidaju postupak prije izmjene postavki ili upravljanih datoteka.
     */
    public function testInvalidPolicyCannotMutateLocalData(): void
    {
        $this->write('config/theme/collection-preserve.json', ['version' => 1, 'preserve_theme_ids' => ['../unsafe']]);
        $before = $this->snapshot();
        try {
            (new BundledThemeCollection($this->themes))->apply($this->root);
            self::fail('Expected preservation-policy rejection.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('preserved theme', $runtimeException->getMessage());
        }

        $this->assertSame($before, $this->snapshot());
    }

    /**
     * EN: Historic fingerprints compare semantic key order while retaining list order and custom fields.
     *
     * HR: Stari hashovi zanemaruju redoslijed ključeva, ali čuvaju redoslijed lista i vlastita svojstva.
     */
    public function testCanonicalFingerprintsNeverIgnoreCustomFields(): void
    {
        $first = ['id' => 'example', 'items' => [['value' => 1, 'label' => 'one'], ['label' => 'two', 'value' => 2]]];
        $second = ['items' => [['label' => 'one', 'value' => 1], ['value' => 2, 'label' => 'two']], 'id' => 'example'];
        $this->assertSame(BundledThemeRetirement::fingerprint($first), BundledThemeRetirement::fingerprint($second));
        $second['custom'] = true;
        $this->assertNotSame(BundledThemeRetirement::fingerprint($first), BundledThemeRetirement::fingerprint($second));
    }

    /**
     * EN: Writes synthetic fixture JSON only.
     *
     * HR: Zapisuje samo ogledni testni JSON.
     *
     * @param array<mixed> $value
     */
    private function write(string $relative, array $value): void
    {
        file_put_contents(
            $this->root . '/' . $relative,
            json_encode($value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n",
        );
    }

    /**
     * EN: Reads synthetic fixture JSON only.
     *
     * HR: Čita samo ogledni testni JSON.
     *
     * @return array<mixed>
     */
    private function read(string $relative): array
    {
        $data = json_decode((string)file_get_contents($this->root . '/' . $relative), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        return $data;
    }

    /**
     * EN: Proves that private configuration and assets remain byte-for-byte identical when preserved.
     *
     * HR: Dokazuje da sačuvane privatne postavke i asseti ostaju bajtovno jednaki.
     *
     * @return array<string,string>
     */
    private function snapshot(): array
    {
        $result = [];
        foreach (['config/theme', 'data/themes'] as $name) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $this->root . '/' . $name,
                \FilesystemIterator::SKIP_DOTS,
            ));
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile() && !$file->isLink()) {
                    $hash = hash_file('sha256', $file->getPathname());
                    $this->assertIsString($hash);
                    $result[substr($file->getPathname(), strlen($this->root) + 1)] = $hash;
                }
            }
        }

        ksort($result);
        return $result;
    }
}
