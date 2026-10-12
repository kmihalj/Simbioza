<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleEditorHtml\Backup\EditorFilesBackupProvider;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorWorkspaceIntegration;
use AaiEduHr\HeartPhrameModuleMenu\Backup\MenuBackupProvider;
use AaiEduHr\HeartPhrameModuleMenu\Backup\MenuWorkspaceBackupProvider;
use AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\SimbiozaModuleWorkspace\Backup\WorkspaceFilesBackupProvider;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceConfig;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceMenuContextOwnerResolver;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceMenuService;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspacePayloadOwner;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * EN: Synthetic integration fixtures exercise current storage layout and portable file restoration.
 * No development database or private payload is used.
 *
 * HR: Sintetičke integracijske fixture provjeravaju aktualni raspored pohrane i prenosivu obnovu datoteka.
 * Ne koriste razvojnu bazu ni privatni sadržaj.
 */
#[CoversNothing]
final class WorkspaceDataLayoutTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Separates fixture storage and removes host configuration overrides temporarily.
     *
     * HR: Odvaja pohranu fixture i privremeno uklanja konfiguracijske override hosta.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/workspace-data-layout-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
        $names = ['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'];
        foreach ($names as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    /**
     * EN: Removes only this test's generated directory and restores the environment.
     *
     * HR: Uklanja samo generirani direktorij testa i vraća okruženje.
     */
    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }

        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Site and workspace-copy imports remap page owners and roll back the complete menu file set.
     *
     * HR: Uvoz sitea i kopije područja remapira vlasnike stranica i vraća potpuni skup datoteka menija.
     */
    public function testOwnedMenusRestoreWithDifferentWorkspaceAndPageIds(): void
    {
        foreach ([BackupScope::SITE, BackupScope::WORKSPACE] as $type) {
            [$source, $sourceConfig, , $backup] = $this->fixture('source-menu-' . $type, 'database', 7);
            [$target, $targetConfig] = $this->fixture('target-menu-' . $type, 'database', 41);
            $target->table('workspace_nodes')->where('id', '=', 8)->update(['id' => 19]);
            $sourceSlug = 'source-menu-' . $type;
            $targetSlug = 'target-menu-' . $type;
            $sourceMenus = new MenuConfigRepository(
                $sourceConfig,
                $this->module('heartphrame-module-menu'),
                new WorkspaceMenuContextOwnerResolver($source),
            );
            $targetMenus = new MenuConfigRepository(
                $targetConfig,
                $this->module('heartphrame-module-menu'),
                new WorkspaceMenuContextOwnerResolver($target),
            );
            $sourceMenus->contextFiles()->write([
                ['id' => 'workspace.7.top', 'top' => [], 'left' => [],
                    'path_patterns' => ['/workspace/' . $sourceSlug]],
                ['id' => 'page-special', 'owner_target' => 'workspace.7.page.8', 'top' => [], 'left' => [],
                    'path_patterns' => ['/custom/page']],
            ]);
            $targetMenus->contextFiles()->write([
                ['id' => 'keep', 'top' => [], 'left' => [], 'path_patterns' => ['/other']],
            ]);
            $this->assertTrue($sourceMenus->contextFiles()->workspaceMembership(
                ['owner_target' => 'workspace.7.page.8'],
                $sourceSlug,
            ));
            $this->assertFalse($sourceMenus->contextFiles()->workspaceMembership(
                ['owner_target' => 'workspace.7.page.8'],
                'another-workspace',
            ));
            $this->assertTrue($sourceMenus->contextFiles()->workspaceMembership(
                ['owner_target' => 'workspace.7.page.999'],
                $sourceSlug,
            ));
            $before = $targetMenus->contextFiles()->snapshot();
            $scope = new BackupScope($type, $type === BackupScope::WORKSPACE ? $sourceSlug : null);
            $exporter = $type === BackupScope::WORKSPACE
            ? new MenuWorkspaceBackupProvider($sourceMenus) : new MenuBackupProvider($sourceMenus);
            $importer = $type === BackupScope::WORKSPACE
            ? new MenuWorkspaceBackupProvider($targetMenus) : new MenuBackupProvider($targetMenus);
            $writer = new BackupArchiveWriter($backup, new BackupFilesystem(), $scope, [], 'Owned menu fixture');
            $writer->beginProvider($exporter->metadata());
            $exporter->export(new BackupExportContext($scope, []), $writer);
            $reader = new BackupArchiveReader($writer->finish(), $backup, new BackupFilesystem());
            try {
                $context = new BackupImportContext($scope, $type === BackupScope::WORKSPACE
                    ? BackupImportContext::CONFLICT_COPY : BackupImportContext::CONFLICT_REPLACE, []);
                $context->state->map($type === BackupScope::WORKSPACE ? 'workspace.id' : 'workspace.workspace', 7, 41);
                $context->state->map(
                    $type === BackupScope::WORKSPACE ? 'workspace.node-source-id' : 'workspace.node',
                    8,
                    19,
                );
                $context->state->map('workspace.slug', $sourceSlug, $targetSlug);
                $this->assertTrue($importer->preflight($context, $reader)->isAllowed());
                $importer->prepareImport($context, $reader);
                $importer->import($context, $reader);
                $importer->finalizeImport($context, $reader);
                $owners = array_column($targetMenus->contextFiles()->read(), 'owner_target');
                $this->assertContains('workspace.41', $owners);
                $this->assertContains('workspace.41.page.19', $owners);
                $this->assertCount($type === BackupScope::WORKSPACE ? 3 : 2, $targetMenus->contextFiles()->read());
                $files = $targetMenus->contextFiles()->snapshot();
                $this->assertCount(3, $files);
                $this->assertStringContainsString(
                    '/workspaces/' . $targetSlug . '--41/pages/page--' . $this->uuid() . '/menus/',
                    implode('\n', array_keys($files)),
                );
                $importer->abortImport($context);
                $this->assertSame($before, $targetMenus->contextFiles()->snapshot());
            } finally {
                $reader->close();
            }
        }
    }

    /**
     * EN: Permanent deletion removes real owner files and index references without Menu GUI services.
     * Shared menus survive even when their route matches the deleted workspace.
     *
     * HR: Trajno brisanje uklanja stvarne vlasničke datoteke i reference indeksa bez Menu GUI servisa.
     * Zajednički meniji ostaju i kada im ruta odgovara obrisanom području.
     */
    public function testPermanentDeletionRemovesOwnedMenusAndKeepsSharedMenus(): void
    {
        [$db, $config] = $this->fixture('menu-cleanup', 'filesystem', 7);
        $repository = new MenuConfigRepository(
            $config,
            $this->module('heartphrame-module-menu'),
            new WorkspaceMenuContextOwnerResolver($db),
        );
        $contexts = [
            ['id' => 'workspace.7.top', 'owner_target' => 'workspace.7', 'top' => [], 'left' => []],
            ['id' => 'page-special', 'owner_target' => 'workspace.7.page.8', 'top' => [], 'left' => []],
            ['id' => 'shared', 'path_patterns' => ['/workspace/menu-cleanup/*'], 'top' => [], 'left' => []],
        ];
        $repository->contextFiles()->write($contexts);
        $before = $repository->contextFiles()->snapshot();
        $pageFile = $config->getAppRootDir() . '/data/workspaces/menu-cleanup--7/pages/page--' . $this->uuid()
        . '/menus/' . hash('sha256', 'page-special') . '.json';
        $workspaceFile = $config->getAppRootDir() . '/data/workspaces/menu-cleanup--7/menus/'
        . hash('sha256', 'workspace.7.top') . '.json';
        $this->assertCount(3, $before);
        $this->assertFileExists($pageFile);
        $this->assertFileExists($workspaceFile);
        $container = new class ($repository) implements ContainerInterface {
            public function __construct(private readonly MenuConfigRepository $repository)
            {
            }

            public function has(string $id): bool
            {
                return $id === MenuConfigRepository::class;
            }

            public function get(string $id): \AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository
            {
                if (!$this->has($id)) {
                    throw new \RuntimeException('No optional Menu GUI service in this fixture.');
                }

                return $this->repository;
            }
        };
        $composer = $this->createStub(ComposerBridge::class);
        $composer->method('isInstalled')->willReturn(true);
        $service = new WorkspaceMenuService(
            $container,
            $composer,
            new WorkspaceConfig($config, $this->module('simbioza-module-workspace')),
            new WorkspaceRepository($db),
            $this->createStub(UrlGenerator::class),
        );
        $this->assertFalse($service->isAvailable());
        $service->deleteForPages([['workspace_id' => 7, 'node_id' => 8]]);
        $db->table('workspace_nodes')->where('id', '=', 8)->delete();
        $this->assertFileDoesNotExist($pageFile);
        $this->assertFileExists($workspaceFile);
        $this->assertSame(['workspace.7.top', 'shared'], array_column($repository->contextFiles()->read(), 'id'));
        $service->deleteForWorkspace(['id' => 7, 'slug' => 'menu-cleanup']);
        $db->table('workspaces')->where('id', '=', 7)->delete();
        $this->assertFileDoesNotExist($workspaceFile);
        $this->assertSame([$contexts[2]], $repository->contextFiles()->read());
        $this->assertCount(1, $repository->contextFiles()->snapshot());
        $repository->contextFiles()->write([$contexts[2]]);
        $this->assertSame([$contexts[2]], $repository->contextFiles()->read());
    }

    /** @return iterable<string,array{string,string}> */
    public static function drivers(): iterable
    {
        foreach (['database', 'filesystem'] as $source) {
            foreach (['database', 'filesystem'] as $target) {
                yield $source . '-' . $target => [$source, $target];
            }
        }
    }

    /**
     * EN: Both source drivers restore to both target drivers, remap IDs and roll back existing target bytes.
     *
     * HR: Oba izvorna drivera obnavljaju se na oba ciljna drivera, remapiraju ID-eve i vraćaju ciljne bajtove.
     */
    #[DataProvider('drivers')]
    public function testSiteFilesRestoreToTargetOwnerAndDriver(
        string $sourceDriver,
        string $targetDriver,
    ): void {
        [$source, $sourceConfig, $sourceEditor, $backup] = $this->fixture('source', $sourceDriver, 7);
        [$target, $targetConfig, $targetEditor] = $this->fixture('target', $targetDriver, 41);
        $sourcePath = '@data/workspaces/source--7/pages/page--' . $this->uuid() . '/content/hr/versions/000001.html';
        $sourceAsset = '@data/workspaces/source--7/pages/page--' . $this->uuid() . '/attachments/a.txt';
        $source->table('workspaces')->where('id', '=', 7)->update(['storage_slug' => 'source']);
        $target->table('workspaces')->where('id', '=', 41)->update(['storage_slug' => 'target']);
        $this->bytes(
            $sourceEditor->documentPayloadPath($sourcePath),
            '<p>Portable content</p>',
        );
        $this->bytes($sourceEditor->attachmentPayloadPath($sourceAsset), 'portable bytes');
        $scope = new BackupScope(BackupScope::COMPONENT);
        $writer = new BackupArchiveWriter($backup, new BackupFilesystem(), $scope, [], 'Data layout fixture');
        $writer->beginProvider(new BackupProviderMetadata(
            'editor-html',
            ModuleEditorHtml::PACKAGE_NAME,
            2,
            ['en' => 'Fixture'],
        ));
        foreach (
            ['documents' => ModuleEditorHtml::TABLE_DOCUMENTS, 'versions' => ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS,
            'assets' => ModuleEditorHtml::TABLE_ASSETS] as $dataset => $table
        ) {
            foreach ($source->table($table)->get() as $row) {
                $writer->writeRecord('editor-html', $dataset, $row);
            }
        }

        $sourceProvider = new EditorFilesBackupProvider($source, $sourceEditor, new BackupFilesystem());
        $writer->beginProvider($sourceProvider->metadata());
        $sourceProvider->export(new BackupExportContext($scope, []), $writer);
        $reader = new BackupArchiveReader($writer->finish(), $backup, new BackupFilesystem());
        try {
            $provider = new EditorFilesBackupProvider(
                $target,
                $targetEditor,
                new BackupFilesystem(),
                $this->bridge($target, $targetConfig),
            );
            $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_MERGE, []);
            foreach (['editor.document', 'editor.version', 'editor.asset'] as $namespace) {
                $context->state->map($namespace, 1, 1);
            }

            $this->assertTrue($provider->preflight($context, $reader)->isAllowed());
            $path = '@data/workspaces/target--41/pages/page--' . $this->uuid() . '/attachments/a.txt';
            $this->bytes($targetEditor->attachmentPayloadPath($path), 'previous target');
            chmod($targetEditor->attachmentPayloadPath($path), 0640);
            $provider->prepareImport($context, $reader);
            $provider->import($context, $reader);
            $provider->finalizeImport($context, $reader);
            $asset = $target->table(ModuleEditorHtml::TABLE_ASSETS)->first();
            $this->assertSame($path, $asset['content_path']);
            $this->assertSame('portable bytes', file_get_contents($targetEditor->attachmentPayloadPath($path)));
            $this->assertSame(0640, fileperms($targetEditor->attachmentPayloadPath($path)) & 0777);
            $version = $target->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->first();
            $this->assertSame($targetDriver, $version['storage_driver']);
            $html = $targetDriver === 'database' ? $version['content_html']
            : (new EditorHtmlDocumentFormatter())->extractFragment(
                file_get_contents($targetEditor->documentPayloadPath($version['content_path'])),
            );
            $this->assertSame('<p>Portable content</p>', $html);
            if ($targetDriver === 'filesystem') {
                $this->assertStringStartsWith('@data/workspaces/target--41/', $version['content_path']);
            }

            $provider->abortImport($context);
            $this->assertSame('previous target', file_get_contents($targetEditor->attachmentPayloadPath($path)));
        } finally {
            $reader->close();
        }
    }

    /**
     * EN: Private theme files use remapped destination workspace IDs and preserve rollback bytes.
     *
     * HR: Privatne teme koriste remapirane ID-eve ciljnih područja i čuvaju rollback bajtove.
     */
    public function testPrivateThemesRestoreWithDestinationIdentityAndRollback(): void
    {
        [$source, $config, , $backup] = $this->fixture('source', 'database', 7);
        [$target, $targetConfig] = $this->fixture('target', 'database', 41);
        $source->table('workspaces')->where('id', '=', 7)->update(['storage_slug' => 'source']);
        $target->table('workspaces')->where('id', '=', 41)->update(['storage_slug' => 'target']);
        $sourceSettings = new WorkspaceConfig($config, $this->module('simbioza-module-workspace'), $source);
        $targetSettings = new WorkspaceConfig($targetConfig, $this->module('simbioza-module-workspace'), $target);
        $this->bytes($sourceSettings->workspaceThemePath(7) . '/css/a.css', '.portable{}');
        $targetPath = $targetSettings->workspaceThemePath(41) . '/css/a.css';
        $this->bytes($targetPath, '.previous{}');
        $scope = new BackupScope(BackupScope::COMPONENT);
        $sourceProvider = new WorkspaceFilesBackupProvider($source, $sourceSettings, new BackupFilesystem());
        $writer = new BackupArchiveWriter($backup, new BackupFilesystem(), $scope, [], 'Private theme fixture');
        $writer->beginProvider($sourceProvider->metadata());

        $sourceProvider->export(new BackupExportContext($scope, []), $writer);
        $reader = new BackupArchiveReader($writer->finish(), $backup, new BackupFilesystem());
        try {
            $provider = new WorkspaceFilesBackupProvider($target, $targetSettings, new BackupFilesystem());
            $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_MERGE, []);
            $context->state->map('workspace.workspace', 7, 41);
            $this->assertTrue($provider->preflight($context, $reader)->isAllowed());
            $provider->prepareImport($context, $reader);
            $provider->import($context, $reader);
            $provider->finalizeImport($context, $reader);
            $this->assertSame('.portable{}', file_get_contents($targetPath));
            $this->assertDirectoryDoesNotExist($targetConfig->getAppRootDir() . '/data/workspaces/source--7');
            $provider->abortImport($context);
            $this->assertSame('.previous{}', file_get_contents($targetPath));
        } finally {
            $reader->close();
        }
    }

    /**
     * EN: Builds isolated synthetic domain and Editor data, never the development database.
     *
     * HR: Gradi izolirane sintetičke domenske i Editor podatke, nikada razvojnu bazu.
     *
     * @return array{Database,Config,EditorHtmlConfig,BackupConfig}
     */
    private function fixture(string $name, string $driver, int $workspace): array
    {
        $root = $this->root . '/' . $name;
        mkdir($root . '/config', 0770, true);
        mkdir($root . '/data', 0770, true);
        $this->bytes($root . '/config/app.php', '<?php return ["name" => "Synthetic layout fixture"];');
        $helper = new Helper();
        $config = new class ($helper, $root) extends Config {
            public function __construct(Helper $helper, private readonly string $root)
            {
                parent::__construct($helper, ['database' => ['connections' => ['default' => [
                    'driver' => 'sqlite', 'database' => ':memory:',
                ]]],
                    'app' => ['modules' => ['enabled' => [
                        'aaieduhr/simbioza-module-workspace', 'aaieduhr/heartphrame-module-menu',
                    ]]],
                    'backup' => ['archive_dir' => $root . '/archives', 'staging_dir' => $root . '/staging']]);
            }

            public function getAppRootDir(): string
            {
                return $this->root;
            }
        };
        $db = new Database($config, $helper);
        $db->execute('CREATE TABLE auth_users (id INTEGER PRIMARY KEY, login_identifier TEXT)');
        $db->table('auth_users')->insert(['id' => 1, 'login_identifier' => 'fixture']);
        $db->execute('CREATE TABLE workspaces (id INTEGER PRIMARY KEY, slug TEXT, storage_slug TEXT)');
        $db->execute('CREATE TABLE workspace_nodes (
            id INTEGER PRIMARY KEY, workspace_id INTEGER, node_type TEXT, document_key TEXT, uuid TEXT)');
        $schema = require $this->module('heartphrame-module-editor-html')
        . '/resources/migrations/initial_editor_html_schema.php';
        $schema->up($db);
        $db->table('workspaces')->insert(['id' => $workspace, 'slug' => $name, 'storage_slug' => $name]);
        $db->table('workspace_nodes')->insert([
            'id' => 8, 'workspace_id' => $workspace, 'node_type' => 'document',
            'document_key' => 'page', 'uuid' => $this->uuid(),
        ]);
        $db->table(ModuleEditorHtml::TABLE_DOCUMENTS)->insert([
            'uuid' => $this->uuid(), 'document_key' => 'page', 'title' => 'Page',
            'default_language' => 'hr', 'owner_user_id' => 1, 'storage_driver' => $driver]);
        $prefix = '@data/workspaces/' . $name . '--' . $workspace . '/pages/page--' . $this->uuid();
        $db->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->insert([
            'document_id' => 1, 'language_code' => 'hr', 'version_number' => 1,
            'title' => 'Page', 'storage_driver' => $driver,
            'content_path' => $driver === 'filesystem' ? $prefix . '/content/hr/versions/000001.html' : null,
            'content_html' => $driver === 'database' ? '<p>Portable content</p>' : null, 'is_published' => 1]);
        $db->table(ModuleEditorHtml::TABLE_ASSETS)->insert([
            'uuid' => '22222222-2222-4222-8222-222222222222', 'document_id' => 1,
            'original_name' => 'a.txt', 'stored_name' => 'a.txt', 'mime_type' => 'text/plain', 'file_size' => 14,
            'storage_driver' => 'filesystem', 'content_path' => $prefix . '/attachments/a.txt']);
        $editor = new EditorHtmlConfig($config, $this->module('heartphrame-module-editor-html'));
        $editor->configurationStore()->mutate(static function (array $values) use ($driver): array {
            $values['storage']['driver'] = $driver;
            return $values;
        });
        $editor->reload();
        $backup = new BackupConfig($config, new ComposerBridge(), $this->module('heartphrame-module-backup'));
        return [$db, $config, $editor, $backup];
    }

    /**
     * EN: Exposes real fixture ownership through the optional integration contract.
     *
     * HR: Izlaže stvarno vlasništvo fixture kroz opcionalni integracijski ugovor.
     */
    private function bridge(Database $database, Config $config): EditorWorkspaceIntegration
    {
        $container = new class (new WorkspacePayloadOwner($database)) implements ContainerInterface {
            public function __construct(private readonly object $owner)
            {
            }

            public function has(string $id): bool
            {
                return true;
            }

            public function get(string $id): object
            {
                return $this->owner;
            }
        };
        return new EditorWorkspaceIntegration($container, $config);
    }

    /**
     * EN: Writes fixture bytes into a newly created test directory only.
     *
     * HR: Piše fixture bajtove samo u novi testni direktorij.
     */
    private function bytes(string $path, string $bytes): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }

        file_put_contents($path, $bytes);
    }

    /**
     * EN: Locates the integrated development module without touching its runtime data.
     *
     * HR: Pronalazi integrirani razvojni modul bez diranja njegovih podataka.
     */
    private function module(string $name): string
    {
        return dirname(__DIR__, 3) . '/vendor/aaieduhr/' . $name;
    }

    /**
     * EN: Returns a stable synthetic document identity.
     *
     * HR: Vraća stabilni sintetički identitet dokumenta.
     */
    private function uuid(): string
    {
        return '11111111-1111-4111-8111-111111111111';
    }
}
