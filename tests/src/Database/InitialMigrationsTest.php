<?php

declare(strict_types=1);

namespace Tests\Database;

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEmail\ModuleEmail;
use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTask\ModuleTask;
use AaiEduHr\HeartPhrameModuleWorkspace\ModuleWorkspace;
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
     * HR: Pokreće sedam objedinjenih inicijalnih migracija te provjerava aktualne
     * Auth, Calendar, Editor, Workspace, E-mail, Notification i Task sheme,
     * početnog administratora i izostanak sadržaja.
     *
     * EN: Runs seven consolidated initial migrations and verifies the current
     * Auth, Calendar, Editor, Workspace, E-mail, Notification, and Task schemas,
     * the bootstrap administrator, and the absence of content data.
     */
    public function testFreshInstallationCreatesCompleteSchemasWithoutSampleContent(): void
    {
        $migrationFiles = glob(dirname(__DIR__, 3) . '/database/migrations/*.php');
        $this->assertIsArray($migrationFiles);
        sort($migrationFiles);
        $this->assertCount(7, $migrationFiles, 'Only consolidated initial migrations are expected.');

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

        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACES, [
            'id',
            'uuid',
            'slug',
            'name',
            'description',
            'visibility',
            'owner_user_id',
            'is_archived',
            'is_deleted',
            'created_by_user_id',
            'updated_by_user_id',
            'deleted_by_user_id',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_ACL, [
            'id',
            'workspace_id',
            'subject_type',
            'subject_id',
            'can_view',
            'can_add',
            'can_edit',
            'can_publish',
            'can_delete',
            'can_manage',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODES, [
            'id',
            'uuid',
            'workspace_id',
            'parent_id',
            'node_type',
            'slug',
            'title',
            'document_key',
            'route_name',
            'target_url',
            'sort_order',
            'is_homepage',
            'is_enabled',
            'created_by_user_id',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL, [
            'id',
            'node_id',
            'subject_type',
            'subject_id',
            'can_view',
            'can_add',
            'can_edit',
            'can_publish',
            'can_delete',
            'can_manage',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS, [
            'id',
            'node_id',
            'language_code',
            'status',
            'current_version_number',
            'published_version_number',
            'submitted_by_user_id',
            'submitted_at',
            'published_by_user_id',
            'published_at',
            'archived_by_user_id',
            'archived_at',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleEmail::TABLE_OUTBOX, [
            'id',
            'uuid',
            'dedup_key',
            'user_id',
            'recipient_email',
            'recipient_name',
            'subject',
            'body_text',
            'body_html',
            'status',
            'attempts',
            'available_at',
            'locked_at',
            'sent_at',
            'last_error',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleNotification::TABLE_NOTIFICATIONS, [
            'id',
            'uuid',
            'user_id',
            'notification_key',
            'title',
            'message',
            'link_url',
            'source_module',
            'source_reference',
            'dedup_key',
            'data_json',
            'read_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleTask::TABLE_STATES, [
            'id',
            'task_uuid',
            'task_list_uuid',
            'document_id',
            'is_completed',
            'updated_by_user_id',
            'updated_by_display_name',
            'state_version',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleTask::TABLE_EVENTS, [
            'id',
            'uuid',
            'task_uuid',
            'task_list_uuid',
            'document_id',
            'is_completed',
            'changed_by_user_id',
            'changed_by_display_name',
            'created_at',
        ]);

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
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACES)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)->get());
        $this->assertSame([], $this->database->table(ModuleEmail::TABLE_OUTBOX)->get());
        $this->assertSame([], $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)->get());
        $this->assertSame([], $this->database->table(ModuleTask::TABLE_STATES)->get());
        $this->assertSame([], $this->database->table(ModuleTask::TABLE_EVENTS)->get());
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
