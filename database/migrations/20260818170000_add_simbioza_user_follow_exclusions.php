<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;
use AaiEduHr\SimbiozaModuleUser\ModuleSimbiozaUser;

return new class implements ReversibleMigrationInterface {
    /** HR: Sprema izričite iznimke od automatskog praćenja. EN: Stores explicit automatic-follow opt-outs. */
    public function up(Database $db): void
    {
        if ($db->schema()->hasTable(ModuleSimbiozaUser::TABLE_FOLLOW_EXCLUSIONS)) {
            return;
        }

        $db->schema()->create(
            ModuleSimbiozaUser::TABLE_FOLLOW_EXCLUSIONS,
            static function (Blueprint $table): void {
                $table->id();
                $table->bigInteger('user_id')->unsigned()->index();
                $table->string('target_type', 24)->index();
                $table->string('target_id', 190)->index();
                $table->string('source', 48)->default('automatic')->index();
                $table->timestamps();
                $table->unique(
                    ['user_id', 'target_type', 'target_id'],
                    'simbioza_user_follow_exclusion_target_uq',
                );
            },
        );
    }

    /** HR: Uklanja samo tablicu iznimki. EN: Drops only the exclusions table. */
    public function down(Database $db): void
    {
        $db->schema()->dropIfExists(ModuleSimbiozaUser::TABLE_FOLLOW_EXCLUSIONS);
    }
};
