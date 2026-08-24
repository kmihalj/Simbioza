<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;
use AaiEduHr\SimbiozaModuleConfluenceImport\ModuleSimbiozaConfluenceImport;

return new class implements ReversibleMigrationInterface {
    /**
     * HR: Izolira izvorni identitet privitka unutar jednoga Confluence import posla.
     * EN: Scopes source attachment identity to one Confluence import job.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();
        $tableName = ModuleSimbiozaConfluenceImport::TABLE_ATTACHMENTS;
        if (!$schema->hasTable($tableName)) {
            return;
        }

        if ($schema->hasIndex($tableName, 'simbioza_confluence_attachment_source_uq', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->dropUnique(
                    ['source_attachment_id', 'source_version'],
                    'simbioza_confluence_attachment_source_uq',
                );
            });
        }

        if (!$schema->hasIndex($tableName, 'simbioza_confluence_attachment_job_source_uq', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->unique(
                    ['job_id', 'source_attachment_id', 'source_version'],
                    'simbioza_confluence_attachment_job_source_uq',
                );
            });
        }
    }

    /**
     * HR: Vraća globalni identitet privitka samo za povrat migracije.
     * EN: Restores global attachment identity only when rolling back the migration.
     */
    public function down(Database $db): void
    {
        $schema = $db->schema();
        $tableName = ModuleSimbiozaConfluenceImport::TABLE_ATTACHMENTS;
        if (!$schema->hasTable($tableName)) {
            return;
        }

        if ($schema->hasIndex($tableName, 'simbioza_confluence_attachment_job_source_uq', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->dropUnique(
                    ['job_id', 'source_attachment_id', 'source_version'],
                    'simbioza_confluence_attachment_job_source_uq',
                );
            });
        }

        if (!$schema->hasIndex($tableName, 'simbioza_confluence_attachment_source_uq', 'unique')) {
            $schema->table($tableName, static function (Blueprint $table): void {
                $table->unique(
                    ['source_attachment_id', 'source_version'],
                    'simbioza_confluence_attachment_source_uq',
                );
            });
        }
    }
};
