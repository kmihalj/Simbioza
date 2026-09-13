<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;

$migration = require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-calendar/resources/migrations/add_meeting_scheduler.php';

if (!$migration instanceof ReversibleMigrationInterface) {
    throw new RuntimeException('Calendar meeting scheduler migration is invalid.');
}

return new class ($migration) implements ReversibleMigrationInterface {
    public function __construct(private readonly ReversibleMigrationInterface $migration)
    {
    }

    public function up(Database $db): void
    {
        $this->migration->up($db);
    }

    public function down(Database $db): void
    {
        $this->migration->down($db);
    }
};
