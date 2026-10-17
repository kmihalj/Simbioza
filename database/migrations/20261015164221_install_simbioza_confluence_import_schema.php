<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;
use AaiEduHr\SimbiozaModuleConfluenceImport\ModuleSimbiozaConfluenceImport;

return new class implements ReversibleMigrationInterface {
    /**
     * EN: Adds only calendar mappings when upgrading the existing 0.3.x importer schema.
     * Fresh installs already obtain this table from the current initial module schema.
     *
     * HR: Nadogradnji postojeće sheme importera 0.3.x dodaje samo mapiranja kalendara.
     * Nove instalacije tablicu već dobivaju iz aktualne početne modulne sheme.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();

        if (!$schema->hasTable(ModuleSimbiozaConfluenceImport::TABLE_CALENDARS)) {
            $schema->create(ModuleSimbiozaConfluenceImport::TABLE_CALENDARS, static function (Blueprint $table): void {
                $table->id();
                $table->string('source_instance', 64)->index();
                $table->string('source_calendar_id', 190);
                $table->bigInteger('target_calendar_id')->unsigned()->index();
                $table->bigInteger('job_id')->unsigned()->index();
                $table->timestamps();
                $table->unique(['source_instance', 'source_calendar_id'], 'simbioza_confluence_calendar_source_uq');
            });
        }

    }

    /**
     * EN: Rolling back this addition never removes existing jobs, identities or content mappings.
     *
     * HR: Povrat ove dopune ne uklanja postojeće poslove, identitete ili mapiranja sadržaja.
     */
    public function down(Database $db): void
    {
        $schema = $db->schema();
        $schema->dropIfExists(ModuleSimbiozaConfluenceImport::TABLE_CALENDARS);
    }
};
