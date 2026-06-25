<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements ReversibleMigrationInterface {
    /**
     * HR: Kreira početnu calendar shemu: kalendare, ACL, praćenje,
     * tipove događaja, događaje, CalDAV podatke i sistemsku calendar grupu.
     *
     * EN: Creates the initial calendar schema: calendars, ACL, subscriptions,
     * event types, events, CalDAV data, and the system calendar manager group.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();

        if (!$schema->hasTable(ModuleCalendar::TABLE_CALENDARS)) {
            $schema->create(ModuleCalendar::TABLE_CALENDARS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->string('uuid', 36)->unique();
                $table->string('slug', 128)->unique();
                $table->string('name', 190)->index();
                $table->text('description')->nullable();
                $table->string('calendar_type', 32)->default('team')->index();
                $table->bigInteger('owner_user_id')->unsigned()->nullable()->index();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->string('color', 16)->default('#0d6efd');
                $table->boolean('is_enabled')->default(1)->index();
                $table->boolean('is_public_read')->default(0)->index();
                $table->boolean('is_authenticated_read')->default(0)->index();
                $table->boolean('show_on_public_index')->default(1)->index();
                $table->boolean('show_public_link')->default(1)->index();
                $table->integer('public_order')->default(100)->index();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable(ModuleCalendar::TABLE_CALENDAR_ACL)) {
            $schema->create(ModuleCalendar::TABLE_CALENDAR_ACL, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('calendar_id')->unsigned()->index();
                $table->string('subject_type', 16)->index();
                $table->bigInteger('subject_id')->unsigned()->index();
                $table->boolean('can_read')->default(1)->index();
                $table->boolean('can_write')->default(0)->index();
                $table->timestamps();

                $table->unique(['calendar_id', 'subject_type', 'subject_id'], 'calendar_acl_subject_unique');
            });
        }

        if (!$schema->hasTable(ModuleCalendar::TABLE_CALENDAR_FOLLOWERS)) {
            $schema->create(ModuleCalendar::TABLE_CALENDAR_FOLLOWERS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('calendar_id')->unsigned()->index();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->boolean('is_subscribed')->default(1)->index();
                $table->boolean('is_visible')->default(1)->index();
                $table->string('color_override', 16)->nullable();
                $table->timestamps();

                $table->unique(['calendar_id', 'user_id'], 'calendar_followers_unique');
            });
        }

        if (!$schema->hasTable(ModuleCalendar::TABLE_EVENT_TYPES)) {
            $schema->create(ModuleCalendar::TABLE_EVENT_TYPES, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('user_id')->unsigned()->default(0)->index();
                $table->string('type_key', 128)->index();
                $table->string('label', 190);
                $table->string('icon', 64)->default('calendar-event');
                $table->boolean('is_system')->default(0)->index();
                $table->boolean('is_enabled')->default(1)->index();
                $table->timestamps();

                $table->unique(['user_id', 'type_key'], 'calendar_event_types_unique');
            });
        }

        if (!$schema->hasTable(ModuleCalendar::TABLE_CALENDAR_EVENTS)) {
            $schema->create(ModuleCalendar::TABLE_CALENDAR_EVENTS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->id();
                $table->bigInteger('calendar_id')->unsigned()->index();
                $table->bigInteger('event_type_id')->unsigned()->nullable()->index();
                $table->string('uid', 190)->unique();
                $table->string('title', 255)->index();
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->timestamp('starts_at')->index();
                $table->timestamp('ends_at')->index();
                $table->boolean('is_all_day')->default(0)->index();
                $table->string('timezone', 64)->default('UTC');
                $table->text('recurrence_rule')->nullable();
                $table->text('icalendar')->nullable();
                $table->string('etag', 64)->index();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->bigInteger('updated_by_user_id')->unsigned()->nullable()->index();
                $table->timestamps();

                $table->index(['calendar_id', 'starts_at', 'ends_at'], 'calendar_events_range_idx');
            });
        }

        if (!$schema->hasTable(ModuleCalendar::TABLE_CALDAV_CREDENTIALS)) {
            $schema->create(ModuleCalendar::TABLE_CALDAV_CREDENTIALS, static function (Blueprint $table): void {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_unicode_ci');

                $table->bigInteger('user_id')->unsigned();
                $table->string('password_hash', 255);
                $table->boolean('is_enabled')->default(1)->index();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->primary('user_id');
            });
        }

        $this->seedCalendarManagerGroup($db);
    }

    /**
     * HR: Briše calendar tablice obrnutim redoslijedom.
     *
     * EN: Drops calendar tables in reverse order.
     */
    public function down(Database $db): void
    {
        $schema = $db->schema();

        foreach (
            [
                ModuleCalendar::TABLE_CALDAV_CREDENTIALS,
                ModuleCalendar::TABLE_CALENDAR_EVENTS,
                ModuleCalendar::TABLE_EVENT_TYPES,
                ModuleCalendar::TABLE_CALENDAR_FOLLOWERS,
                ModuleCalendar::TABLE_CALENDAR_ACL,
                ModuleCalendar::TABLE_CALENDARS,
            ] as $table
        ) {
            $schema->dropIfExists($table);
        }

        $this->removeCalendarManagerGroup($db);
    }

    /**
     * HR: Osigurava sistemsku auth grupu koja smije administrirati kalendare.
     *
     * EN: Ensures the system auth group allowed to administer calendars.
     */
    private function seedCalendarManagerGroup(Database $db): void
    {
        if (!$db->schema()->hasTable(ModuleAuth::TABLE_AUTH_GROUPS)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $group = $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('group_key', '=', ModuleCalendar::GROUP_KEY_CALENDAR_MANAGERS)
            ->first();

        $values = [
            'group_name' => 'Kalendari',
            'is_system' => 1,
            'is_enabled' => 1,
            'sort_order' => 20,
            'updated_at' => $now,
        ];

        if (is_array($group)) {
            $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
                ->where('group_key', '=', ModuleCalendar::GROUP_KEY_CALENDAR_MANAGERS)
                ->update($values);

            return;
        }

        $db->table(ModuleAuth::TABLE_AUTH_GROUPS)->insert([
            'group_key' => ModuleCalendar::GROUP_KEY_CALENDAR_MANAGERS,
            'created_at' => $now,
            ...$values,
        ]);
    }

    /**
     * HR: Uklanja sistemsku calendar grupu koju je modul kreirao.
     *
     * EN: Removes the system calendar group created by this module.
     */
    private function removeCalendarManagerGroup(Database $db): void
    {
        $schema = $db->schema();
        if (!$schema->hasTable(ModuleAuth::TABLE_AUTH_GROUPS)) {
            return;
        }

        $group = $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('group_key', '=', ModuleCalendar::GROUP_KEY_CALENDAR_MANAGERS)
            ->first();
        if (!is_array($group) || !is_numeric($group['id'] ?? null)) {
            return;
        }

        $groupId = (int)$group['id'];
        if ($schema->hasTable(ModuleAuth::TABLE_AUTH_USER_GROUPS)) {
            $db->table(ModuleAuth::TABLE_AUTH_USER_GROUPS)
                ->where('group_id', '=', $groupId)
                ->delete();
        }
        if ($schema->hasTable(ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES)) {
            $db->table(ModuleAuth::TABLE_AUTH_GROUP_MAPPING_RULES)
                ->where('group_id', '=', $groupId)
                ->delete();
        }

        $db->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('id', '=', $groupId)
            ->delete();
    }
};
