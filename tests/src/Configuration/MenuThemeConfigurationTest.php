<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Service\FilesystemBackupProvider;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleMenu\Backup\MenuBackupProvider;
use AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTheme\Backup\ThemeBackupProvider;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use App\Navigation\StandaloneContentPresence;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Helper\Helper;
use HeartPhrame\View\View;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * EN: Uses real module stores and ZIP archives with synthetic, independent site roots.
 *
 * HR: Koristi stvarna spremišta modula i ZIP arhive sa sintetičkim, odvojenim korijenima siteova.
 */
#[CoversNothing]
final class MenuThemeConfigurationTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates environment values and all synthetic files.
     *
     * HR: Izolira varijable okruženja i sve sintetičke datoteke.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/menu-theme-config-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0770);
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    /**
     * EN: Restores the environment and removes only this test's temporary tree.
     *
     * HR: Vraća okruženje i uklanja samo privremeno stablo ovog testa.
     */
    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }

        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Exercises both independently configured generic modules.
     *
     * HR: Provjerava oba neovisno konfigurirana generička modula.
     *
     * @return iterable<string,array{string,string}>
     */
    public static function modules(): iterable
    {
        yield 'Menu' => ['menu', 'top'];
        yield 'Theme' => ['theme', 'themes'];
    }

    /**
     * EN: Covers upgrades of full installations and administrator choices in focused products.
     *
     * HR: Pokriva nadogradnje punih instalacija i administratorske odluke u samostalnim alatima.
     *
     * @return iterable<string,array{bool,bool,bool,bool}>
     */
    public static function workspaceModes(): iterable
    {
        yield 'Full Simbioza without standalone content' => [true, false, false, true];
        yield 'Workspace with standalone HTML' => [true, true, false, true];
        yield 'Workspace with standalone Survey' => [true, false, true, true];
        yield 'Workspace with both catalogues' => [true, true, true, true];
        yield 'Optional schemas not installed yet' => [true, false, false, false];
        yield 'Empty focused product' => [false, false, false, true];
        yield 'Focused product with content' => [false, true, true, true];
    }

    /**
     * EN: Keeps only useful automatic catalogues with Workspace, preserving custom links and focused menus.
     *
     * HR: Uz Područja čuva samo korisne automatske kataloge, vlastite poveznice i izbornike samostalnih alata.
     */
    #[DataProvider('workspaceModes')]
    public function testContentNavigationAfterUpgrade(
        bool $workspaceEnabled,
        bool $pages,
        bool $surveys,
        bool $tables,
    ): void {
        $site = $this->site('navigation');
        $enabled = ['aaieduhr/heartphrame-module-menu', 'aaieduhr/heartphrame-module-editor-html',
            'aaieduhr/heartphrame-module-survey'];
        if ($workspaceEnabled) {
            $enabled[] = 'aaieduhr/simbioza-module-workspace';
        }

        $config = new Config(new Helper(), ['app' => ['modules' => ['enabled' => $enabled]],
            'database' => ['connections' => ['default' => ['driver' => 'sqlite', 'database' => ':memory:']]],
            'env' => ['environment' => 'development']], $site);
        $database = new Database($config, new Helper());
        if ($tables) {
            $database->execute('CREATE TABLE editor_html_documents '
                . '(id INTEGER,document_key TEXT,is_enabled INTEGER,is_deleted INTEGER)');
            $database->execute('CREATE TABLE workspace_nodes (document_key TEXT)');
            $database->execute('CREATE TABLE survey_surveys (uuid TEXT,state TEXT)');
            $database->execute('CREATE TABLE survey_placements (survey_uuid TEXT)');
            $database->execute("INSERT INTO editor_html_documents VALUES (1,'owned',1,0),(2,'deleted',1,1),"
                . "(3,'disabled',0,0)");
            $database->execute("INSERT INTO workspace_nodes VALUES ('owned')");
            $database->execute("INSERT INTO survey_surveys VALUES ('owned','draft'),('old','archived')");
            $database->execute("INSERT INTO survey_placements VALUES ('owned')");
            if ($pages) {
                $database->execute("INSERT INTO editor_html_documents VALUES (4,'standalone',1,0)");
            }

            if ($surveys) {
                $database->execute("INSERT INTO survey_surveys VALUES ('standalone','draft')");
            }
        }

        $menu = $this->repository('menu', $site, $config);
        $this->assertInstanceOf(MenuConfigRepository::class, $menu);
        $menu->writeJsonContentsToSection('top', json_encode([
            ['id' => 'custom-pages', 'label' => ['en' => 'Our pages'], 'route' => 'content.pages', 'order' => 17],
            ['id' => 'custom-surveys', 'label' => ['en' => 'Our surveys'], 'route' => 'content.surveys', 'order' => 18],
            ['id' => 'simbioza.pages', 'label' => ['en' => 'Saved pages'], 'route' => 'content.pages',
                'enabled' => false, 'order' => 81],
            ['id' => 'group', 'label' => ['en' => 'Group'], 'children' => [
                ['id' => 'simbioza.surveys', 'label' => ['en' => 'Saved surveys'], 'route' => 'content.surveys',
                    'enabled' => false, 'order' => 82],
            ]],
            ['id' => 'simbioza.guides', 'route' => 'guides.index'],
        ], JSON_THROW_ON_ERROR));
        $queries = 0;
        $database->listen(static function () use (&$queries): void {
            $queries++;
        });
        $navigation = null;
        $view = $this->createMock(View::class);
        $view->method('addGlobal')->willReturnCallback(
            static function (string $name, mixed $value) use (&$navigation, $view): View {
                if ($name === 'simbiozaContentNavigation') {
                    $navigation = $value;
                }

                return $view;
            },
        );
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeast(4))->method('get')->willReturnMap([
            [ConfigInterface::class, $config], [MenuConfigRepository::class, $menu],
            [View::class, $view], [LoggerInterface::class, new NullLogger()],
            [StandaloneContentPresence::class, new StandaloneContentPresence($database)],
        ]);
        $bootstraps = require dirname(__DIR__, 3) . '/config/bootstrap.php';
        $bootstraps[0]($container);
        $this->assertSame(0, $queries, 'API and CLI bootstrap must not query content for HTML navigation.');
        $this->assertIsCallable($navigation);
        $beforeRendering = array_column($menu->rowsForSection('top'), null, 'id');
        $this->assertArrayHasKey('simbioza.pages', $beforeRendering);
        $this->assertArrayHasKey('simbioza.surveys', $beforeRendering);
        $navigation();
        $rows = array_column($menu->rowsForSection('top'), null, 'id');
        $this->assertSame('Our pages', $rows['custom-pages']['label']['en']);
        $this->assertSame('Our surveys', $rows['custom-surveys']['label']['en']);
        $this->assertSame(17, $rows['custom-pages']['order']);
        $this->assertSame(!$workspaceEnabled || $pages, isset($rows['simbioza.pages']));
        $this->assertSame(!$workspaceEnabled || $surveys, isset($rows['simbioza.surveys']));
        $this->assertSame(!$workspaceEnabled, isset($rows['simbioza.guides']));

        if (!$workspaceEnabled || $pages) {
            $this->assertFalse($rows['simbioza.pages']['enabled']);
            $this->assertSame('Saved pages', $rows['simbioza.pages']['label']['en']);
        }

        if (!$workspaceEnabled || $surveys) {
            $this->assertFalse($rows['simbioza.surveys']['enabled']);
            $this->assertSame('group', $rows['simbioza.surveys']['parent_id']);
        }

        $before = file_get_contents($menu->jsonPathForSection('top'));
        $bootstraps[0]($container);
        $navigation();
        $this->assertSame($before, file_get_contents($menu->jsonPathForSection('top')));
    }

    /**
     * EN: PHP layers merge, JSON documents replace, GUI writes target the highest layer, and manual edits are read.
     *
     * HR: PHP slojevi se spajaju, JSON dokumenti zamjenjuju, GUI zapisuje u najviši sloj, a ručne izmjene se čitaju.
     */
    #[DataProvider('modules')]
    public function testLayeredConfigurationAndManualEdits(string $module, string $section): void
    {
        $site = $this->site('layered');
        mkdir($this->root . '/override');
        mkdir($this->root . '/override/' . $module);
        $base = $site . '/config/' . $module;
        mkdir($base);
        file_put_contents($base . '/' . $module . '.php', '<?php return ["enabled"=>true, "extension"=>"kept"];');
        file_put_contents(
            $this->root . '/override/' . $module . '/' . $module . '.php',
            '<?php return ["settings"=>["enabled"=>false]];',
        );
        file_put_contents($base . '/' . $section . '.json', '[{"id":"lower"}]');
        putenv('HPH_CONFIG_PATH=' . $site . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $config = new Config(new Helper(), [], $site);
        $config->loadLayeredDirectories([$site . '/config', $this->root . '/override']);

        $repository = $this->repository($module, $site, $config);
        $this->assertSame('kept', $repository->configurationFiles()->options()['extension']);
        $this->assertFalse($repository->configurationFiles()->options()['settings']['enabled']);
        $this->assertSame($base . '/' . $section . '.json', $repository->configurationFiles()->readJson($section));
        $this->write($repository, $section, '[{"id":"higher"}]');
        $target = $this->root . '/override/' . $module . '/' . $section . '.json';
        $this->assertSame('[{"id":"higher"}]', file_get_contents($target));
        $this->assertSame('[{"id":"lower"}]', file_get_contents($base . '/' . $section . '.json'));
        file_put_contents($target, '[{"id":"manual"}]');
        $this->assertSame(
            '[{"id":"manual"}]',
            file_get_contents($repository->configurationFiles()->readJson($section)),
        );
        chmod($target, 0440);
        try {
            $this->write($repository, $section, '[]');
            self::fail('Read-only target was accepted.');
        } catch (RuntimeException) {
            $this->assertSame('[{"id":"manual"}]', file_get_contents($target));
        } finally {
            chmod($target, 0660);
        }
    }

    /**
     * EN: Special menus remain data even with external configuration; empty Theme settings objects are valid.
     *
     * HR: Posebni meniji ostaju podaci i uz vanjski config; prazni objekti postavki Tema su ispravni.
     */
    public function testDataSeparationAndEmptyThemeObject(): void
    {
        $site = $this->site('separate');
        mkdir($this->root . '/private');
        putenv('HPH_CONFIG_PATH=' . $this->root . '/private');
        $menu = $this->repository('menu', $site);
        $this->assertInstanceOf(MenuConfigRepository::class, $menu);
        $menu->writeJsonContentsToSection('contexts', '[{"id":"workspace.4"}]');
        $this->assertFileExists($site . '/data/menu/contexts.json');
        $this->assertDirectoryDoesNotExist($this->root . '/private/menu');
        $theme = $this->repository('theme', $site);
        $this->assertInstanceOf(ThemeConfigRepository::class, $theme);
        $theme->writeJsonContents('settings', '{}');
        $this->assertTrue($theme->isEnabled());
        $before = $theme->assetVersion();
        $theme->writeJsonContents('settings', '{"enabled":false}');
        $this->assertFalse($theme->isEnabled());
        $this->assertNotSame($before, $theme->assetVersion());
    }

    /**
     * EN: Current and old file-provider records restore to another site's layers, with exact rollback.
     *
     * HR: Aktualni i stari zapisi datotečnog providera vraćaju se u slojeve drugog sitea uz točan rollback.
     */
    #[DataProvider('modules')]
    public function testPortableArchiveAndRollback(string $module, string $section): void
    {
        $source = $this->site('source');
        $repository = $this->repository($module, $source);
        $this->write($repository, $section, '[{"id":"archived"}]');
        if ($repository instanceof ThemeConfigRepository) {
            $repository->writeJsonContents('settings', '{"active_theme":"archived"}');
            $library = $repository->themesDirectoryPath();
            mkdir($library . '/nested', 0770);
            file_put_contents($library . '/nested/image.svg', '<svg/>');
        } else {
            $repository->writeJsonContentsToSection('contexts', '[{"id":"workspace.4"}]');
        }

        $provider = $this->provider($repository);
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($this->backupConfig(), new BackupFilesystem(), $scope, [], 'P7');
        $writer->beginProvider($provider->metadata());

        $provider->export(new BackupExportContext($scope, [$module]), $writer);
        $archive = $writer->finish();
        $target = $this->site('target');
        mkdir($this->root . '/highest');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/highest');
        $destination = $this->repository($module, $target);
        $this->write($destination, $section, '[{"id":"original"}]');
        if ($destination instanceof ThemeConfigRepository) {
            $destination->writeJsonContents('settings', '{}');
            file_put_contents($destination->themesDirectoryPath() . '/old.svg', '<old/>');
        }

        $importer = $this->provider($destination);
        $reader = new BackupArchiveReader($archive, $this->backupConfig(), new BackupFilesystem());
        $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_REPLACE, [$module]);
        $this->assertTrue($importer->preflight($context, $reader)->isAllowed());
        $importer->prepareImport($context, $reader);
        $importer->import($context, $reader);
        $importer->finalizeImport($context, $reader);
        $importer->abortImport($context);

        $path = $this->root . '/highest/' . $module . '/' . $section . '.json';
        $this->assertSame('[{"id":"original"}]', file_get_contents($path));
        if ($module === 'theme') {
            $this->assertFileExists($target . '/data/themes/old.svg');
            $this->assertFileDoesNotExist($target . '/data/themes/nested/image.svg');
        }

        $importer->import($context, $reader);
        $importer->finalizeImport($context, $reader);
        $importer->completeImport($context);
        $this->assertSame('[{"id":"archived"}]', file_get_contents($path));
        $this->assertFileDoesNotExist($target . '/config/' . $module . '/' . $section . '.json');
        $this->assertDirectoryDoesNotExist($target . '/resources/config/' . $module);
        $sourceFile = $source . '/config/' . $module . '/' . $section . '.json';
        $this->assertSame('[{"id":"archived"}]', file_get_contents($sourceFile));
        if ($module === 'theme') {
            $asset = $target . '/data/themes/nested/image.svg';
            $this->assertSame('<svg/>', file_get_contents($asset));
            $this->assertSame(0660, fileperms($asset) & 0777);
            $this->assertSame(filegroup($target . '/data/themes'), filegroup(dirname($asset)));
            $this->assertSame(filegroup($target . '/data/themes'), filegroup($asset));
            $this->assertFileDoesNotExist($target . '/data/themes/old.svg');
        } else {
            $this->assertSame(
                [['id' => 'workspace.4']],
                json_decode(file_get_contents($target . '/data/menu/contexts.json'), true, 512, JSON_THROW_ON_ERROR),
            );
        }

        $copy = new BackupImportContext($scope, BackupImportContext::CONFLICT_COPY, [$module]);
        $this->assertFalse($importer->preflight($copy, $reader)->isAllowed());
        $reader->close();
    }

    /**
     * EN: Archive filenames cannot restore executable PHP or escape a module folder.
     *
     * HR: Imena datoteka arhiva ne mogu vratiti izvršivi PHP niti izaći iz foldera modula.
     */
    #[DataProvider('modules')]
    public function testUnsafeArchiveRejectedBeforeWrite(string $module, string $section): void
    {
        $site = $this->site('unsafe');
        $provider = $this->provider($this->repository($module, $site));
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($this->backupConfig(), new BackupFilesystem(), $scope, [], 'P7 unsafe');
        $writer->beginProvider($provider->metadata());
        file_put_contents($this->root . '/bad-fixture.php', '<?php exit;');
        $blob = $writer->addBlobFile($this->root . '/bad-fixture.php', 'text/plain', 'bad.php');
        $writer->writeRecord($module, 'files', [
            'root' => $module . '-config', 'path' => '../bad.php', 'blob' => $blob,
        ]);
        $reader = new BackupArchiveReader($writer->finish(), $this->backupConfig(), new BackupFilesystem());
        $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_REPLACE, [$module]);
        $this->assertFalse($provider->preflight($context, $reader)->isAllowed());
        $this->assertFileDoesNotExist($site . '/bad.php');
        $this->assertDirectoryDoesNotExist($site . '/config/' . $module);
        $reader->close();
    }

    /**
     * EN: Produces an actual legacy filesystem-provider archive and restores it to current local paths.
     * Theme preflight must not create the destination's missing asset library.
     *
     * HR: Stvara stvarnu staru arhivu datotečnog providera i vraća je u aktualne lokalne putanje.
     * Theme preflight ne smije stvoriti nedostajuću biblioteku asseta odredišta.
     */
    #[DataProvider('modules')]
    public function testLegacyFilesystemArchive(string $module, string $section): void
    {
        $source = $this->site('old');
        $old = $source . '/resources/config/' . $module;
        mkdir($old, 0770, true);
        file_put_contents($old . '/' . $section . '.json', '[{"id":"legacy"}]');
        file_put_contents($old . '/settings.json', $module === 'theme' ? '{}' : '[]');
        if ($module === 'menu') {
            file_put_contents($old . '/contexts.json', '[{"id":"context"}]');
        }

        $target = $this->site('new');
        $destination = $this->repository($module, $target);
        $importer = $this->provider($destination);
        $legacy = new FilesystemBackupProvider(
            $importer->metadata(),
            new BackupFilesystem(),
            [['key' => $module . '-config', 'path' => $old]],
        );
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($this->backupConfig(), new BackupFilesystem(), $scope, [], 'Legacy P7');
        $writer->beginProvider($legacy->metadata());

        $legacy->export(new BackupExportContext($scope, [$module]), $writer);
        $reader = new BackupArchiveReader($writer->finish(), $this->backupConfig(), new BackupFilesystem());
        $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_REPLACE, [$module]);
        $this->assertTrue($importer->preflight($context, $reader)->isAllowed());
        $this->assertDirectoryDoesNotExist($target . '/data/themes');
        $importer->import($context, $reader);
        $importer->finalizeImport($context, $reader);
        $importer->completeImport($context);

        $targetFile = $target . '/config/' . $module . '/' . $section . '.json';
        $this->assertSame('[{"id":"legacy"}]', file_get_contents($targetFile));
        if ($module === 'menu') {
            $this->assertSame(
                [['id' => 'context']],
                json_decode(file_get_contents($target . '/data/menu/contexts.json'), true, 512, JSON_THROW_ON_ERROR),
            );
        }

        $this->assertDirectoryDoesNotExist($target . '/resources/config/' . $module);
        $reader->close();
    }

    /**
     * EN: Creates a new synthetic installation and selects its configuration root.
     *
     * HR: Stvara novu sintetičku instalaciju i odabire njezin konfiguracijski korijen.
     */
    private function site(string $name): string
    {
        $site = $this->root . '/' . $name;
        mkdir($site . '/config', 0770, true);
        putenv('HPH_CONFIG_PATH=' . $site . '/config');
        return $site;
    }

    /**
     * EN: Builds the real repository without the Simbioza GUI.
     *
     * HR: Stvara stvarni repozitorij bez Simbiozinog GUI-a.
     */
    private function repository(
        string $module,
        string $site,
        ?Config $config = null,
    ): MenuConfigRepository|ThemeConfigRepository {
        $config ??= new Config(new Helper(), ['menu' => ['contexts' => ['enabled' => true]],
            'theme' => ['enabled' => true]], $site);
        $moduleRoot = dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-' . $module;
        return $module === 'menu' ? new MenuConfigRepository($config, $moduleRoot)
        : new ThemeConfigRepository($config, $moduleRoot);
    }

    /**
     * EN: Connects the appropriate optional Backup provider.
     *
     * HR: Povezuje odgovarajući opcionalni Backup provider.
     */
    private function provider(
        MenuConfigRepository|ThemeConfigRepository $repository,
    ): MenuBackupProvider|ThemeBackupProvider {
        return $repository instanceof MenuConfigRepository ? new MenuBackupProvider($repository)
        : new ThemeBackupProvider($repository, new BackupFilesystem());
    }

    /**
     * EN: Uses the same validated write API as GUI and import operations.
     *
     * HR: Koristi isti provjereni API zapisivanja kao GUI i import radnje.
     */
    private function write(MenuConfigRepository|ThemeConfigRepository $repository, string $section, string $bytes): void
    {
        if ($repository instanceof MenuConfigRepository) {
            $repository->writeJsonContentsToSection($section, $bytes);
        } else {
            $repository->writeJsonContents($section, $bytes);
        }
    }

    /**
     * EN: Keeps archive creation and extraction inside this synthetic test root.
     *
     * HR: Čuva stvaranje i raspakiravanje arhiva unutar sintetičkog korijena testa.
     */
    private function backupConfig(): BackupConfig
    {
        $config = new Config(new Helper(), ['backup' => ['archive_dir' => $this->root . '/archives',
            'staging_dir' => $this->root . '/staging']], $this->root);
        $composer = new class ($this->root) extends ComposerBridge {
            public function __construct(private readonly string $root)
            {
            }

            /** @return array<string,mixed> */
            public function getRootPackage(): array
            {
                return ['install_path' => $this->root];
            }
        };
        return new BackupConfig($config, $composer, dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-backup');
    }
}
