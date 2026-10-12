<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\SimbiozaModuleConfluenceImport\Service\ConfluenceImportConfig;
use AaiEduHr\SimbiozaModuleWorkspaceSearch\Service\WorkspaceSearchConfig;
use HeartPhrame\Config\Config;
use HeartPhrame\Config\ConfigPathResolver;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: Exercises the real framework configuration tree for Import and Search.
 * No private settings, installation migrations or GUI are required.
 *
 * HR: Provjerava stvarno frameworkovo konfiguracijsko stablo za Import i Search.
 * Nisu potrebne privatne postavke, instalacijske migracije ni GUI.
 */
#[CoversNothing]
final class ImportSearchConfigurationTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /** @var list<string> */
    private array $files = [];

    /** @var list<string> */
    private array $directories = [];

    /**
     * EN: Creates only owned temporary directories and resets declared layers.
     *
     * HR: Stvara samo vlastite privremene direktorije i resetira deklarirane slojeve.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-import-search-config-' . bin2hex(random_bytes(8));
        $this->directory($this->root);
        foreach (['config', 'external', 'last'] as $directory) {
            $this->directory($this->root . '/' . $directory);
        }
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
            putenv($key);
        }
    }

    /**
     * EN: Deletes only tracked test files/directories and restores the environment.
     *
     * HR: Briše samo evidentirane testne datoteke/direktorije i vraća okruženje.
     */
    protected function tearDown(): void
    {
        foreach (array_reverse($this->files) as $path) {
            unlink($path);
        }
        foreach (array_reverse($this->directories) as $path) {
            rmdir($path);
        }
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }
    }

    /**
     * EN: Both consumers follow the native nested keys and declared layer order.
     * Old flat values cannot override the canonical file or an external root.
     *
     * HR: Oba čitača prate izvorne ugniježđene ključeve i redoslijed slojeva.
     * Stare ravne vrijednosti ne nadjačavaju kanonsku datoteku ni vanjski korijen.
     */
    #[DataProvider('modes')]
    public function testNativeLayeredConfiguration(string $mode): void
    {
        $this->write('config', 'confluence_import.php', ['chunk_size' => 999]);
        $this->write('config', 'workspace-search/workspace-search.php', ['results_per_page' => 5]);
        $this->write('config', 'confluence-import/confluence-import.php', ['chunk_size' => 7]);
        if ($mode !== 'default') {
            $this->write('external', 'workspace-search/workspace-search.php', [
                'results_per_page' => 8, 'maximum_results_per_page' => 50,
            ]);
            $this->write('external', 'confluence-import/confluence-import.php', [
                'chunk_size' => 11, 'default_language' => 'en',
            ]);
            putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        }
        if ($mode === 'layered') {
            $this->write('last', 'workspace-search/workspace-search.php', ['results_per_page' => 9]);
            $this->write('last', 'confluence-import/confluence-import.php', ['chunk_size' => 13]);
            putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/last');
        }
        [$search, $import] = $this->consumers();
        self::assertSame(match ($mode) {
            'default' => 5, 'external' => 8, default => 9
        }, $search->resultsPerPage());
        self::assertSame($mode === 'default' ? 100 : 50, $search->maximumResultsPerPage());
        self::assertSame(match ($mode) {
            'default' => 7, 'external' => 11, default => 13
        }, $import->chunkSize());
        self::assertSame($mode === 'default' ? 'hr' : 'en', $import->defaultLanguage());
        self::assertSame($this->root . '/data/confluence-import', $import->dataDirectory());
        self::assertDirectoryDoesNotExist($this->root . '/data');
    }

    /**
     * EN: Empty higher-layer maps clear lower settings under native merge rules.
     *
     * HR: Prazne mape višeg sloja brišu niže postavke prema izvornoj politici spajanja.
     */
    public function testEmptyLayerReturnsToPackageDefaults(): void
    {
        $this->write('config', 'workspace-search/workspace-search.php', ['results_per_page' => 5]);
        $this->write('config', 'confluence-import/confluence-import.php', ['chunk_size' => 7]);
        $this->write('last', 'workspace-search/workspace-search.php', []);
        $this->write('last', 'confluence-import/confluence-import.php', []);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/last');
        [$search, $import] = $this->consumers();
        self::assertSame(20, $search->resultsPerPage());
        self::assertSame(8388608, $import->chunkSize());
    }

    /**
     * EN: Corrupt policies and traversal paths fail rather than escaping data storage.
     *
     * HR: Nevaljane politike i putanje izlaska prekidaju rad umjesto napuštanja data spremišta.
     */
    public function testInvalidConfigurationFailsExplicitly(): void
    {
        foreach (['workspace-search', 'confluence-import'] as $module) {
            $config = new Config(new Helper(), [$module => [$module => false]], $this->root);
            try {
                $module === 'workspace-search'
                ? new WorkspaceSearchConfig($config, $this->moduleRoot($module))
                : new ConfluenceImportConfig($config, $this->moduleRoot($module));
                self::fail('Invalid configuration must not fall back to defaults.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('must be an array', $exception->getMessage());
            }
        }
        foreach (['../outside', '/outside', 'a/../../outside', 'a\\outside', "a\0b"] as $path) {
            $config = new Config(new Helper(), ['confluence-import' => [
                'confluence-import' => ['data_path' => $path],
            ]], $this->root);
            try {
                (new ConfluenceImportConfig($config, $this->moduleRoot('confluence-import')))->dataDirectory();
                self::fail('Import data must remain inside data.');
            } catch (RuntimeException $exception) {
                self::assertStringContainsString('data_path', $exception->getMessage());
            }
        }
    }

    /**
     * EN: A lone obsolete flat policy is not a hidden module configuration source.
     *
     * HR: Samostalna zastarjela ravna politika nije skriveni izvor konfiguracije modula.
     */
    public function testFlatPoliciesAreNotReadByModuleConsumers(): void
    {
        $this->write('config', 'workspace-search.php', ['results_per_page' => 99]);
        $this->write('config', 'confluence_import.php', ['chunk_size' => 999]);
        [$search, $import] = $this->consumers();
        self::assertSame(20, $search->resultsPerPage());
        self::assertSame(8388608, $import->chunkSize());
    }

    /**
     * EN: Enumerates default, external and layered configuration modes.
     *
     * HR: Navodi zadani, vanjski i slojeviti način konfiguracije.
     * @return array<string,array{string}>
     */
    public static function modes(): array
    {
        return ['default' => ['default'], 'external' => ['external'], 'layered' => ['layered']];
    }

    /**
     * EN: Loads the actual framework tree before constructing both module services.
     *
     * HR: Učitava stvarno stablo frameworka prije izrade servisa oba modula.
     * @return array{WorkspaceSearchConfig,ConfluenceImportConfig}
     */
    private function consumers(): array
    {
        $config = new Config(new Helper(), [], $this->root);
        $config->loadLayeredDirectories(ConfigPathResolver::fromEnvironment($this->root));
        return [new WorkspaceSearchConfig($config, $this->moduleRoot('workspace-search')),
            new ConfluenceImportConfig($config, $this->moduleRoot('confluence-import'))];
    }

    /**
     * EN: Resolves the source package without private installation settings.
     *
     * HR: Pronalazi izvorni paket bez privatnih instalacijskih postavki.
     */
    private function moduleRoot(string $module): string
    {
        return dirname(__DIR__, 3) . '/vendor/aaieduhr/simbioza-module-' . $module;
    }

    /**
     * EN: Writes a fictional PHP array only inside a tracked fixture directory.
     *
     * HR: Piše izmišljeno PHP polje samo unutar evidentiranog fixture direktorija.
     * @param array<string,mixed> $values
     */
    private function write(string $layer, string $relative, array $values): void
    {
        $path = $this->root . '/' . $layer . '/' . $relative;
        if (!is_dir(dirname($path))) {
            $this->directory(dirname($path));
        }
        self::assertNotFalse(file_put_contents($path, '<?php return ' . var_export($values, true) . ';'));
        $this->files[] = $path;
    }

    /**
     * EN: Tracks each newly created directory for non-recursive cleanup.
     *
     * HR: Evidentira svaki novi direktorij za nerekurzivno čišćenje.
     */
    private function directory(string $path): void
    {
        self::assertTrue(mkdir($path, 0700));
        $this->directories[] = $path;
    }
}
