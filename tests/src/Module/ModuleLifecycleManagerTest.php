<?php

declare(strict_types=1);

namespace Tests\Module;

use AaiEduHr\HeartPhrameModuleEmail\ModuleEmail;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;
use App\Module\ModuleCatalog;
use App\Module\ModuleDataArchive;
use App\Module\ModuleLifecycleManager;
use App\Module\ModuleStateStore;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(ModuleLifecycleManager::class)]
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
        $this->assertSame(0660, fileperms($this->root . '/config/modules.php') & 0777);

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
