<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;

return new class implements MigrationInterface {
    /**
     * HR: Osigurava sistemsku grupu Kalendari i na postojećim instalacijama
     *     čija je početna Calendar migracija izvršena prije dodavanja grupe.
     * EN: Ensures the Calendars system group on existing installations whose
     *     initial Calendar migration ran before the group was introduced.
     */
    public function up(Database $db): void
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
};
