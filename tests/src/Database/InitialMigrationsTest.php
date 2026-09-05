<?php

declare(strict_types=1);

namespace Tests\Database;

use AaiEduHr\HeartPhrameModuleApi\ModuleApi;
use AaiEduHr\HeartPhrameModuleAudit\ModuleAudit;
use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleBackup\ModuleBackup;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleComment\ModuleComment;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEmail\ModuleEmail;
use AaiEduHr\HeartPhrameModuleNotification\ModuleNotification;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTask\ModuleTask;
use AaiEduHr\SimbiozaModuleConfluenceImport\ModuleSimbiozaConfluenceImport;
use AaiEduHr\SimbiozaModuleUser\ModuleSimbiozaUser;
use AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace;
use AaiEduHr\SimbiozaModuleWorkspaceSearch\ModuleWorkspaceSearch;
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
     * HR: Pokreće dvadeset i osam aktualnih aplikacijskih migracija te provjerava aktualne
     * Auth, Calendar, Editor, Workspace, Workspace Search, E-mail, Notification,
     * Task, Comment, API, Backup, Audit, Simbioza User i Confluence Import sheme, izvedeni backlink
     * indeks, izostanak unaprijed izrađenih korisnika i izostanak sadržaja.
     *
     * EN: Runs twenty-eight current application migrations and verifies the current Auth,
     * Calendar, Editor, Workspace, Workspace Search, E-mail, Notification, Task,
     * Comment, API, Backup, Audit, Simbioza User, and Confluence Import schemas, the derived backlink
     * index, the absence of pre-created users, and no content data.
     */
    public function testFreshInstallationCreatesCompleteSchemasWithoutSampleContent(): void
    {
        $migrationFiles = glob(dirname(__DIR__, 3) . '/database/migrations/*.php');
        $this->assertIsArray($migrationFiles);
        sort($migrationFiles);
        $this->assertCount(28, $migrationFiles, 'Every current application migration must be covered.');

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
        $this->assertTrue(
            $this->database->schema()->hasIndex(
                ModuleCalendar::TABLE_CALENDAR_EVENTS,
                'calendar_events_calendar_uid_unique',
                'unique',
            ),
            'Missing calendar-scoped event UID index.',
        );

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
        $this->assertColumns(ModuleEditorHtml::TABLE_DOCUMENT_INCLUDES, [
            'id',
            'uuid',
            'source_document_id',
            'target_document_id',
            'target_label',
            'external_provider',
            'external_space_key',
            'external_page_id',
            'external_page_title',
            'resolution_status',
            'created_at',
            'updated_at',
        ]);

        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACES, [
            'id',
            'uuid',
            'slug',
            'name',
            'description',
            'name_translations',
            'description_translations',
            'visibility',
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
            'title_translations',
            'document_key',
            'route_name',
            'target_url',
            'sort_order',
            'is_tree_hidden',
            'is_homepage',
            'is_enabled',
            'created_by_user_id',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODE_LABELS, [
            'id',
            'node_id',
            'label',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODE_PROPERTIES, [
            'id',
            'node_id',
            'property_key',
            'property_label',
            'property_type',
            'property_value',
            'sort_order',
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
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_NODE_DIRECT_PERMISSIONS, [
            'id',
            'node_id',
            'user_id',
            'can_view',
            'can_edit',
            'can_publish',
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
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_HOMEPAGE_SETTINGS, [
            'id',
            'public_node_id',
            'public_target_type',
            'public_workspace_id',
            'public_show_tree',
            'public_show_display_options',
            'authenticated_node_id',
            'authenticated_target_type',
            'authenticated_workspace_id',
            'authenticated_show_tree',
            'authenticated_show_display_options',
            'allow_user_selection',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_USER_HOMEPAGES, [
            'id',
            'user_id',
            'node_id',
            'target_type',
            'workspace_id',
            'show_tree',
            'show_display_options',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_THEMES, [
            'id',
            'workspace_id',
            'selection_type',
            'source_theme_id',
            'mode_policy',
            'theme_json',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_BACKLINKS, [
            'id',
            'source_workspace_id',
            'source_node_id',
            'source_language_code',
            'source_version_number',
            'source_title',
            'target_workspace_id',
            'target_node_id',
            'link_text',
            'indexed_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspace::TABLE_WORKSPACE_BACKLINK_INDEX_STATE, [
            'id',
            'rebuilt_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleWorkspaceSearch::TABLE_INDEX, [
            'id',
            'workspace_id',
            'node_id',
            'workspace_slug',
            'workspace_name',
            'node_slug',
            'document_key',
            'language_code',
            'title',
            'body_text',
            'normalized_text',
            'author_user_id',
            'author_name',
            'published_at',
            'version_number',
            'content_hash',
            'indexed_at',
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
        $this->assertColumns(ModuleNotification::TABLE_USER_PREFERENCES, [
            'id',
            'user_id',
            'email_enabled',
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
        $this->assertColumns(ModuleComment::TABLE_SETTINGS, [
            'id',
            'document_id',
            'language_code',
            'published_enabled',
            'draft_enabled',
            'has_draft_setting',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleComment::TABLE_COMMENTS, [
            'id',
            'uuid',
            'document_id',
            'language_code',
            'user_id',
            'author_display_name',
            'body',
            'is_deleted',
            'deleted_by_user_id',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleComment::TABLE_REACTIONS, [
            'id',
            'comment_id',
            'user_id',
            'reaction',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleComment::TABLE_REPORTS, [
            'id',
            'uuid',
            'comment_id',
            'reporter_user_id',
            'status',
            'reason',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleApi::TABLE_RATE_LIMITS, [
            'id',
            'api_key_id',
            'window_start',
            'request_count',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleApi::TABLE_IDEMPOTENCY_KEYS, [
            'id',
            'api_key_id',
            'idempotency_key',
            'request_fingerprint',
            'response_status',
            'response_headers_json',
            'response_body',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleApi::TABLE_KEY_REQUESTS, [
            'id',
            'uuid',
            'user_id',
            'name',
            'description',
            'scopes_json',
            'allowed_ips_json',
            'expires_at',
            'status',
            'decided_by_user_id',
            'decided_at',
            'decision_note',
            'api_key_id',
            'encrypted_token',
            'token_revealed_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleApi::TABLE_WEBHOOK_SUBSCRIPTIONS, [
            'id',
            'uuid',
            'owner_api_key_id',
            'owner_user_id',
            'name',
            'target_url',
            'events_json',
            'encrypted_secret',
            'is_active',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleApi::TABLE_WEBHOOK_DELIVERIES, [
            'id',
            'uuid',
            'subscription_id',
            'event_uuid',
            'event_name',
            'payload_json',
            'status',
            'attempts',
            'available_at',
            'locked_at',
            'delivered_at',
            'response_status',
            'response_body',
            'last_error',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleBackup::TABLE_JOBS, [
            'id',
            'uuid',
            'operation',
            'scope_type',
            'scope_identifier',
            'status',
            'conflict_mode',
            'selected_providers_json',
            'options_json',
            'archive_path',
            'archive_name',
            'archive_sha256',
            'archive_bytes',
            'processed_bytes',
            'error_summary',
            'result_json',
            'actor_user_id',
            'started_at',
            'completed_at',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleBackup::TABLE_UPLOADS, [
            'id',
            'uuid',
            'original_name',
            'total_size',
            'chunk_size',
            'next_offset',
            'temp_path',
            'expected_sha256',
            'actual_sha256',
            'status',
            'actor_user_id',
            'expires_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleAudit::TABLE_EVENTS, [
            'id',
            'uuid',
            'occurred_at',
            'module',
            'event_key',
            'action',
            'outcome',
            'actor_type',
            'actor_user_id',
            'actor_label',
            'impersonator_user_id',
            'auth_method',
            'channel',
            'workspace_id',
            'page_id',
            'target_type',
            'target_id',
            'target_label',
            'version_id',
            'language',
            'request_id',
            'correlation_id',
            'ip_address',
            'user_agent',
            'metadata_json',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_PREFERENCES, [
            'id',
            'user_id',
            'email_mode',
            'notify_own_changes',
            'theme_mode',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_FOLLOWS, [
            'id',
            'uuid',
            'user_id',
            'target_type',
            'target_id',
            'workspace_id',
            'page_id',
            'document_id',
            'label_snapshot',
            'email_mode_override',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_PENDING_DELIVERIES, [
            'id',
            'uuid',
            'user_id',
            'event_key',
            'target_type',
            'target_id',
            'workspace_id',
            'page_id',
            'document_id',
            'actor_user_id',
            'importance',
            'title',
            'message',
            'link_url',
            'payload_json',
            'dedup_key',
            'occurrence_count',
            'deliver_after',
            'delivered_at',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_FOLLOW_EXCLUSIONS, [
            'id',
            'user_id',
            'target_type',
            'target_id',
            'source',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_SETTINGS, [
            'id',
            'setting_key',
            'setting_value',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_PERSONAL_WORKSPACES, [
            'id',
            'user_id',
            'workspace_id',
            'created_automatically',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaUser::TABLE_PERSONAL_WORKSPACE_POLICIES, [
            'id',
            'user_id',
            'auto_create_enabled',
            'updated_by_user_id',
            'created_at',
            'updated_at',
        ]);
        $this->assertColumns(ModuleSimbiozaConfluenceImport::TABLE_JOBS, [
            'id',
            'uuid',
            'operation',
            'status',
            'stage',
            'archive_path',
            'workspace_id',
            'actor_user_id',
            'created_at',
            'updated_at',
        ]);
        foreach (
            [
                ModuleSimbiozaConfluenceImport::TABLE_SPACES,
                ModuleSimbiozaConfluenceImport::TABLE_CONTENT,
                ModuleSimbiozaConfluenceImport::TABLE_IDENTITIES,
                ModuleSimbiozaConfluenceImport::TABLE_GROUPS,
                ModuleSimbiozaConfluenceImport::TABLE_LINKS,
                ModuleSimbiozaConfluenceImport::TABLE_ATTACHMENTS,
            ] as $confluenceImportTable
        ) {
            $this->assertTrue(
                $this->database->schema()->hasTable($confluenceImportTable),
                'Missing table: ' . $confluenceImportTable,
            );
        }

        $this->assertTrue(
            $this->database->schema()->hasIndex(
                ModuleSimbiozaConfluenceImport::TABLE_ATTACHMENTS,
                'simbioza_confluence_attachment_job_source_uq',
                'unique',
            ),
            'Missing job-scoped Confluence attachment identity index.',
        );

        $this->assertSame([], $this->database->table(ModuleAuth::TABLE_AUTH_USERS)->get());

        $this->assertSame([], $this->database->table(ModuleCalendar::TABLE_CALENDARS)->get());
        $this->assertSame([], $this->database->table(ModuleCalendar::TABLE_CALENDAR_EVENTS)->get());
        $this->assertSame([], $this->database->table(ModuleEditorHtml::TABLE_DOCUMENTS)->get());
        $this->assertSame([], $this->database->table(ModuleEditorHtml::TABLE_ASSETS)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACES)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACE_THEMES)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspace::TABLE_WORKSPACE_BACKLINKS)->get());
        $this->assertSame([], $this->database->table(ModuleWorkspaceSearch::TABLE_INDEX)->get());
        $this->assertSame([], $this->database->table(ModuleEmail::TABLE_OUTBOX)->get());
        $this->assertSame([], $this->database->table(ModuleNotification::TABLE_NOTIFICATIONS)->get());
        $this->assertSame([], $this->database->table(ModuleNotification::TABLE_USER_PREFERENCES)->get());
        $this->assertSame([], $this->database->table(ModuleTask::TABLE_STATES)->get());
        $this->assertSame([], $this->database->table(ModuleTask::TABLE_EVENTS)->get());
        $this->assertSame([], $this->database->table(ModuleComment::TABLE_SETTINGS)->get());
        $this->assertSame([], $this->database->table(ModuleComment::TABLE_COMMENTS)->get());
        $this->assertSame([], $this->database->table(ModuleComment::TABLE_REACTIONS)->get());
        $this->assertSame([], $this->database->table(ModuleComment::TABLE_REPORTS)->get());
        $this->assertSame([], $this->database->table(ModuleApi::TABLE_RATE_LIMITS)->get());
        $this->assertSame([], $this->database->table(ModuleApi::TABLE_IDEMPOTENCY_KEYS)->get());
        $this->assertSame([], $this->database->table(ModuleApi::TABLE_KEY_REQUESTS)->get());
        $this->assertSame([], $this->database->table(ModuleApi::TABLE_WEBHOOK_SUBSCRIPTIONS)->get());
        $this->assertSame([], $this->database->table(ModuleApi::TABLE_WEBHOOK_DELIVERIES)->get());
        $this->assertSame([], $this->database->table(ModuleBackup::TABLE_JOBS)->get());
        $this->assertSame([], $this->database->table(ModuleBackup::TABLE_UPLOADS)->get());
        $this->assertSame([], $this->database->table(ModuleAudit::TABLE_EVENTS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_PREFERENCES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_FOLLOWS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_PENDING_DELIVERIES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_FOLLOW_EXCLUSIONS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_SETTINGS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_PERSONAL_WORKSPACES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaUser::TABLE_PERSONAL_WORKSPACE_POLICIES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_JOBS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_SPACES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_CONTENT)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_IDENTITIES)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_GROUPS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_LINKS)->get());
        $this->assertSame([], $this->database->table(ModuleSimbiozaConfluenceImport::TABLE_ATTACHMENTS)->get());
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
