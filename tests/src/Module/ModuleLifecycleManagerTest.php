<?php

declare(strict_types=1);

namespace Tests\Module;

use AaiEduHr\HeartPhrameModuleEmail\ModuleEmail;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;
use App\Module\CommandResult;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\ModuleDataArchive;
use App\Module\ModuleLifecycleManager;
use App\Module\ModuleStateStore;
use App\Module\NativeProcessRunner;
use App\Module\ProcessRunnerInterface;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(ModuleLifecycleManager::class)]
#[CoversClass(ComposerPackageManager::class)]
#[CoversClass(CommandResult::class)]
#[CoversClass(ModuleDataArchive::class)]
#[CoversClass(ModuleStateStore::class)]
#[CoversClass(ModuleCatalog::class)]
final class ModuleLifecycleManagerTest extends TestCase
{
    private string $root;

    private Database $database;

    private ModuleStateStore $state;

    private ModuleLifecycleManager $manager;

    /**
     * HR: Priprema izoliranu SQLite instalaciju s uključenim E-mail modulom.
     * EN: Prepares an isolated SQLite installation with the E-mail module enabled.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-module-test-' . bin2hex(random_bytes(8));
        foreach (['config', 'data', 'database/migrations'] as $directory) {
            if (!mkdir($this->root . '/' . $directory, 0770, true) && !is_dir($this->root . '/' . $directory)) {
                throw new RuntimeException('Unable to create module lifecycle test directory.');
            }
        }

        $projectRoot = dirname(__DIR__, 3);
        foreach (
            [
                '20260605093456_install_auth_module_schema',
                '20260718090000_install_email_module_schema',
            ] as $migration
        ) {
            $source = $projectRoot . '/database/migrations/' . $migration . '.php';
            $target = $this->root . '/database/migrations/' . $migration . '.php';
            if (!symlink($source, $target)) {
                throw new RuntimeException('Unable to link a module lifecycle migration fixture.');
            }
        }

        if (!symlink($projectRoot . '/vendor', $this->root . '/vendor')) {
            throw new RuntimeException('Unable to link module lifecycle dependencies.');
        }

        $helper = new Helper();
        $config = new Config($helper, [
            'database' => [
                'connections' => [
                    'default' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]);
        $this->database = new Database($config, $helper);
        $migrations = [];
        foreach (
            [
                '20260605093456_install_auth_module_schema',
                '20260718090000_install_email_module_schema',
            ] as $migration
        ) {
            $migrations[$migration] = require $this->root . '/database/migrations/' . $migration . '.php';
        }

        $this->database->migrator()->migrate($migrations);

        $catalog = new ModuleCatalog();
        $this->state = new ModuleStateStore($this->root, $catalog);
        $this->state->initialize(['email']);
        $this->assertSame(0660, fileperms($this->root . '/data/config/modules.php') & 0777);

        $archives = new ModuleDataArchive($this->database, $this->root);
        $this->manager = new ModuleLifecycleManager(
            $this->database,
            $catalog,
            $this->state,
            $archives,
            $this->root,
        );
    }

    /** HR: Uklanja samo privremenu instalaciju izrađenu testom. EN: Removes only the temporary installation created by the test. */
    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }

            $path = $entry->getPathname();
            if ($entry->isLink() || $entry->isFile()) {
                unlink($path);
            } else {
                rmdir($path);
            }
        }

        rmdir($this->root);
    }

    /**
     * HR: Uklanjanje čuva podatke u NDJSON-u, briše shemu te ih nakon ponovnog
     *     dodavanja transakcijski vraća zajedno sa stanjem modula.
     * EN: Removal stores data in NDJSON, drops the schema, and a later add
     *     transactionally restores both data and module state.
     */
    public function testRemoveAndRestoreOptionalModuleData(): void
    {
        $this->database->table(ModuleEmail::TABLE_OUTBOX)->insert([
            'uuid' => '017f3a1f-9e04-7d4f-a577-42c5df1538aa',
            'dedup_key' => 'module-test',
            'user_id' => null,
            'recipient_email' => 'recipient@example.test',
            'recipient_name' => 'Recipient',
            'subject' => 'Module lifecycle',
            'body_text' => 'Preserve this row.',
            'body_html' => null,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => '2026-09-15 12:00:00',
            'locked_at' => null,
            'sent_at' => null,
            'last_error' => null,
            'created_at' => '2026-09-15 12:00:00',
            'updated_at' => '2026-09-15 12:00:00',
        ]);

        $archive = $this->manager->remove('email');

        $this->assertDirectoryExists($archive);
        $this->assertFileExists($archive . '/manifest.json');
        $this->assertSame(02770, fileperms($this->root . '/data/module-backups') & 07777);
        $this->assertSame(02770, fileperms(dirname($archive)) & 07777);
        $this->assertSame(0660, fileperms($archive . '/manifest.json') & 0777);
        $this->assertFalse($this->database->schema()->hasTable(ModuleEmail::TABLE_OUTBOX));
        $this->assertFalse($this->state->isEnabled('email'));
        $email = array_values(array_filter(
            $this->manager->status(),
            static fn(array $module): bool => $module['slug'] === 'email',
        ))[0];
        $this->assertSame('removed', $email['state']);
        $this->assertTrue($email['backup_available']);

        $this->assertSame($archive, $this->manager->add('email', true));
        $this->assertTrue($this->state->isEnabled('email'));
        $this->assertTrue($this->database->schema()->hasTable(ModuleEmail::TABLE_OUTBOX));
        $this->assertCount(1, $this->database->table(ModuleEmail::TABLE_OUTBOX)->get());
        $row = $this->database->table(ModuleEmail::TABLE_OUTBOX)->first();
        $this->assertIsArray($row);
        $this->assertSame('recipient@example.test', $row['recipient_email'] ?? null);
    }

    /** HR: Isključivanje ostavlja shemu i podatke, a uključivanje ih ponovno izlaže. EN: Disabling retains schema and data, and enabling exposes them again. */
    public function testDisableAndEnableKeepsSchema(): void
    {
        $this->manager->disable('email');

        $this->assertFalse($this->state->isEnabled('email'));
        $this->assertTrue($this->database->schema()->hasTable(ModuleEmail::TABLE_OUTBOX));
        $this->manager->enable('email');
        $this->assertTrue($this->state->isEnabled('email'));
    }

    /**
     * HR: Neuspjela Composer radnja ne smije prije vremena isključiti modul ni
     *     ukloniti njegovu shemu, a privatni cache mora biti počišćen.
     * EN: A failed Composer operation must not disable the module or drop its
     *     schema prematurely, and its private cache must be cleaned up.
     */
    public function testPackageFailureLeavesEnabledSchemaIntact(): void
    {
        $manifest = json_encode([
            'require' => ['aaieduhr/heartphrame-module-email' => '^0.1.2'],
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
        file_put_contents($this->root . '/composer.json', $manifest);
        $runner = new class implements ProcessRunnerInterface {
            public ?string $composerCache = null;

            /** HR: Bilježi privatni cache i simulira Composer kvar. EN: Records the private cache and simulates a Composer failure. */
            public function run(array $command, string $workingDirectory): CommandResult
            {
                $cache = getenv('COMPOSER_CACHE_DIR');
                $this->composerCache = is_string($cache) ? $cache : null;

                return new CommandResult(1, '', 'simulated package failure');
            }
        };
        $catalog = new ModuleCatalog();
        $manager = new ModuleLifecycleManager(
            $this->database,
            $catalog,
            $this->state,
            new ModuleDataArchive($this->database, $this->root),
            $this->root,
            new ComposerPackageManager($catalog, $runner, $this->root),
        );

        try {
            $manager->remove('email');
            self::fail('The simulated Composer failure was not propagated.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('simulated package failure', $runtimeException->getMessage());
        }

        $this->assertTrue($this->state->isEnabled('email'));
        $this->assertTrue($this->database->schema()->hasTable(ModuleEmail::TABLE_OUTBOX));
        $this->assertSame($manifest, file_get_contents($this->root . '/composer.json'));
        $this->assertIsString($runner->composerCache);
        $this->assertDirectoryDoesNotExist($runner->composerCache);
    }

    /**
     * HR: Globalna migracija preskače omotač opcionalnog paketa koji nije
     *     instaliran, ali i dalje primjenjuje aplikacijsku migraciju bez vlasnika.
     * EN: Global migration skips the wrapper of an absent optional package
     *     while still applying an unowned application migration.
     */
    public function testInstalledMigrationSetSkipsAbsentOptionalPackages(): void
    {
        $filteredRoot = $this->root . '/filtered';
        foreach (['config', 'data', 'database/migrations', 'vendor/composer'] as $directory) {
            $this->assertTrue(mkdir($filteredRoot . '/' . $directory, 0770, true));
        }

        file_put_contents(
            $filteredRoot . '/vendor/composer/installed.json',
            json_encode(['packages' => []], JSON_THROW_ON_ERROR),
        );
        file_put_contents(
            $filteredRoot . '/database/migrations/20260608000212_install_calendar_module_schema.php',
            "<?php\nthrow new RuntimeException('Absent Calendar migration was loaded.');\n",
        );
        file_put_contents(
            $filteredRoot . '/database/migrations/20990101000000_unowned_application_migration.php',
            <<<'PHP'
<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements MigrationInterface {
    /** HR: Stvara probnu tablicu. EN: Creates the test table. */
    public function up(Database $db): void
    {
        $db->schema()->create('module_migration_probe', static function (Blueprint $table): void {
            $table->increments('id');
        });
    }
};
PHP,
        );

        $helper = new Helper();
        $database = new Database(new Config($helper, [
            'database' => [
                'connections' => [
                    'default' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]), $helper);
        $catalog = new ModuleCatalog();
        $this->assertSame(
            'calendar',
            $catalog->slugForMigration('20990101000001_install_calendar_module_schema'),
        );
        $state = new ModuleStateStore($filteredRoot, $catalog);
        $state->initialize([]);

        $packages = new ComposerPackageManager(
            $catalog,
            new NativeProcessRunner(),
            $filteredRoot,
        );
        $manager = new ModuleLifecycleManager(
            $database,
            $catalog,
            $state,
            new ModuleDataArchive($database, $filteredRoot),
            $filteredRoot,
            $packages,
        );

        $this->assertSame([
            'ran' => [],
            'pending' => ['20990101000000_unowned_application_migration'],
        ], $manager->installedMigrationStatus());
        $this->assertSame(
            ['20990101000000_unowned_application_migration'],
            $manager->migrateInstalled(),
        );
        $this->assertTrue($database->schema()->hasTable('module_migration_probe'));
    }

    /**
     * HR: Uklanjanje Confluence modula staje dok povijest stranica još sadrži
     *     proxy koji bi bez tog modula postao neupotrebljiv.
     * EN: Confluence removal stops while page history still contains a proxy
     *     that would become unusable without that module.
     */
    public function testConfluenceRemovalRejectsUnresolvedRuntimeReferences(): void
    {
        $this->database->schema()->create(
            'editor_html_document_versions',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->longText('content_html')->nullable();
            },
        );
        $this->database->table('editor_html_document_versions')->insert([
            'content_html' => '<a href="/confluence-import/link/42">Legacy link</a>',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unresolved import references');
        $this->manager->remove('confluence-import');
    }
}
