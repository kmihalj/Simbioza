<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements ReversibleMigrationInterface {
    /** HR: Ograničava jedinstvenost UID-a događaja na pripadajući kalendar. EN: Scopes event UID uniqueness to its calendar. */
    public function up(Database $db): void
    {
        $schema = $db->schema();
        $tableName = ModuleCalendar::TABLE_CALENDAR_EVENTS;
        if (!$schema->hasTable($tableName)) {
            return;
        }

        if ($schema->hasIndex($tableName, 'calendar_events_uid_unique', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->dropUnique(['uid'], 'calendar_events_uid_unique');
            });
        }

        if (!$schema->hasIndex($tableName, 'calendar_events_calendar_uid_unique', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->unique(['calendar_id', 'uid'], 'calendar_events_calendar_uid_unique');
            });
        }
    }

    /** HR: Vraća globalnu jedinstvenost UID-a samo pri povratu migracije. EN: Restores global UID uniqueness on rollback. */
    public function down(Database $db): void
    {
        $schema = $db->schema();
        $tableName = ModuleCalendar::TABLE_CALENDAR_EVENTS;
        if (!$schema->hasTable($tableName)) {
            return;
        }

        if ($schema->hasIndex($tableName, 'calendar_events_calendar_uid_unique', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->dropUnique(['calendar_id', 'uid'], 'calendar_events_calendar_uid_unique');
            });
        }

        if (!$schema->hasIndex($tableName, 'calendar_events_uid_unique', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->unique(['uid'], 'calendar_events_uid_unique');
            });
        }
    }
};
