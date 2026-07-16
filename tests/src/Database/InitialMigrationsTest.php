<?php

declare(strict_types=1);

namespace Tests\Database;

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class InitialMigrationsTest extends TestCase
{
    private Database $database;

    /**
     * HR: Prije svakog testa priprema potpuno praznu SQLite bazu u memoriji.
     * Time provjera ne može slučajno koristiti razvojne podatke aplikacije.
     *
     * EN: Prepares a completely empty in-memory SQLite database before each test.
     * This prevents the verification from accidentally using application development data.
     */
    protected function setUp(): void
    {
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
    }

    /**
     * HR: Pokreće samo tri objedinjene inicijalne migracije te provjerava aktualnu
     * auth, calendar i editor shemu, početnog administratora i izostanak sadržaja.
     *
     * EN: Runs only the three consolidated initial migrations and verifies the
     * current auth, calendar, and editor schemas, bootstrap administrator, and no content data.
     */
    public function testFreshInstallationCreatesCompleteSchemasWithoutSampleContent(): void
    {
        $migrationFiles = glob(dirname(__DIR__, 3) . '/database/migrations/*.php');
        $this->assertIsArray($migrationFiles);
        sort($migrationFiles);
        $this->assertCount(3, $migrationFiles, 'Only consolidated initial migrations are expected.');

        foreach ($migrationFiles as $migrationFile) {
            $migration = require $migrationFile;
            $this->assertTrue(method_exists($migration, 'up'));
            $migration->up($this->database);
        }

        $this->assertColumns(ModuleAuth::TABLE_AUTH_USERS, [
            'id',
            'login_identifier',
            'password_hash',
            'is_admin',
            'is_active',
            'auth_source',
            'last_login_at',
            'must_change_password',
            'force_local_password_reset_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_VALUES, [
            'user_id',
            'field_key',
            'value_text',
            'value_json',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleAuth::TABLE_AUTH_GROUPS, [
            'id',
            'group_key',
            'group_name',
            'is_system',
            'is_enabled',
            'sort_order',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleAuth::TABLE_AUTH_USER_GROUPS, [
            'id',
            'user_id',
            'group_id',
            'source',
            'source_field_key',
            'source_provider',
            'created_at',
            'updated_at',
        ]);

        foreach (
            [
                ModuleAuth::TABLE_AUTH_USER_ATTRIBUTE_FIELDS,
                ModuleAuth::TABLE_AUTH_PROVIDER_SETTINGS,
                ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES,
                ModuleAuth::TABLE_AUTH_SYSTEM_SETTINGS,
                ModuleAuth::TABLE_AUTH_USER_PROVIDER_ACCESS,
                ModuleAuth::TABLE_AUTH_USER_IDENTITIES,
                ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES,
                ModuleAuth::TABLE_AUTH_AUDIT_LOGS,
            ] as $authTable
        ) {
            $this->assertTrue($this->database->schema()->hasTable($authTable), 'Missing table: ' . $authTable);
        }

        $this->assertColumns(ModuleCalendar::TABLE_CALENDARS, [
            'id',
            'uuid',
            'slug',
            'name',
            'description',
            'calendar_type',
            'owner_user_id',
            'created_by_user_id',
            'color',
            'is_enabled',
            'is_public_read',
            'is_authenticated_read',
            'show_on_public_index',
            'show_public_link',
            'public_order',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleCalendar::TABLE_CALENDAR_EVENTS, [
            'id',
            'calendar_id',
            'event_type_id',
            'uid',
            'title',
            'description',
            'location',
            'starts_at',
            'ends_at',
            'is_all_day',
            'timezone',
            'recurrence_rule',
            'icalendar',
            'etag',
            'created_by_user_id',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);

        foreach (
            [
                ModuleCalendar::TABLE_CALENDAR_ACL,
                ModuleCalendar::TABLE_CALENDAR_FOLLOWERS,
                ModuleCalendar::TABLE_EVENT_TYPES,
                ModuleCalendar::TABLE_CALDAV_CREDENTIALS,
            ] as $calendarTable
        ) {
            $this->assertTrue($this->database->schema()->hasTable($calendarTable), 'Missing table: ' . $calendarTable);
        }

        $this->assertColumns(ModuleEditorHtml::TABLE_DOCUMENTS, [
            'id',
            'uuid',
            'document_key',
            'title',
            'default_language',
            'owner_user_id',
            'current_version_id',
            'storage_driver',
            'attachment_visibility',
            'is_enabled',
            'is_deleted',
            'deleted_original_document_key',
            'deleted_editor_url',
            'deleted_view_url',
            'created_by_user_id',
            'updated_by_user_id',
            'deleted_by_user_id',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS, [
            'id',
            'document_id',
            'language_code',
            'version_number',
            'title',
            'storage_driver',
            'content_path',
            'content_html',
            'created_by_user_id',
            'created_at',
        ]);
        $this->assertColumns(ModuleEditorHtml::TABLE_ASSETS, [
            'id',
            'uuid',
            'document_id',
            'original_name',
            'display_name',
            'alt_text',
            'caption',
            'description',
            'stored_name',
            'mime_type',
            'file_size',
            'storage_driver',
            'content_path',
            'created_by_user_id',
            'is_deleted',
            'deleted_by_user_id',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertTrue($this->database->schema()->hasTable(ModuleEditorHtml::TABLE_DOCUMENT_ACL));

        $users = $this->database->table(ModuleAuth::TABLE_AUTH_USERS)->get();
        $this->assertCount(1, $users);
        $administrator = $users[0];
        $this->assertSame('Administrator', $administrator['login_identifier']);
        $this->assertTrue(password_verify('Admin123!', (string)$administrator['password_hash']));
        $this->assertSame(1, (int)$administrator['is_admin']);
        $this->assertSame(1, (int)$administrator['must_change_password']);

        $this->assertSame([], $this->database->table(ModuleCalendar::TABLE_CALENDARS)->get());
        $this->assertSame([], $this->database->table(ModuleCalendar::TABLE_CALENDAR_EVENTS)->get());
        $this->assertSame([], $this->database->table(ModuleEditorHtml::TABLE_DOCUMENTS)->get());
        $this->assertSame([], $this->database->table(ModuleEditorHtml::TABLE_ASSETS)->get());
    }

    /**
     * HR: Provjerava da tablica postoji i da nijedno očekivano polje nije izostavljeno.
     * EN: Verifies that a table exists and that none of its expected columns were omitted.
     *
     * @param list<string> $columns
     */
    private function assertColumns(string $table, array $columns): void
    {
        $this->assertTrue($this->database->schema()->hasTable($table), 'Missing table: ' . $table);

        foreach ($columns as $column) {
            $hasColumn = $this->database->schema()->hasColumn($table, $column);
            $this->assertTrue($hasColumn, sprintf('Missing column %s.%s', $table, $column));
        }
    }
}
