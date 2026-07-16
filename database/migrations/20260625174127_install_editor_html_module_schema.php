<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleOrm\Database\Schema\Blueprint;

return new class implements ReversibleMigrationInterface {
    /**
     * HR: Kreira početnu editor shemu: dokumente, budući ACL, verzije i asset metapodatke.
     * Prava uređivanja su za sada vezana samo uz administratorski status korisnika.
     *
     * EN: Creates the initial editor schema: documents, future ACL, versions and asset metadata.
     * Editing rights are currently tied only to the user's administrator status.
     */
    public function up(Database $db): void
    {
        $schema = $db->schema();

        if (!$schema->hasTable(ModuleEditorHtml::TABLE_DOCUMENTS)) {
            $schema->create(ModuleEditorHtml::TABLE_DOCUMENTS, static function (Blueprint $table): void {
                $table->id();
                $table->string('uuid', 36)->unique();
                $table->string('document_key', 190)->unique();
                $table->string('title', 255)->index();
                $table->string('default_language', 16)->default('hr')->index();
                $table->bigInteger('owner_user_id')->unsigned()->index();
                $table->bigInteger('current_version_id')->unsigned()->nullable()->index();
                $table->string('storage_driver', 32)->default('filesystem')->index();
                $table->string('attachment_visibility', 32)->default('none')->index();
                $table->boolean('is_enabled')->default(true)->index();
                $table->boolean('is_deleted')->default(false)->index();
                $table->string('deleted_original_document_key', 190)->nullable()->index();
                $table->string('deleted_editor_url', 512)->nullable();
                $table->string('deleted_view_url', 512)->nullable();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->bigInteger('updated_by_user_id')->unsigned()->nullable()->index();
                $table->bigInteger('deleted_by_user_id')->unsigned()->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable(ModuleEditorHtml::TABLE_DOCUMENT_ACL)) {
            $schema->create(ModuleEditorHtml::TABLE_DOCUMENT_ACL, static function (Blueprint $table): void {
                $table->id();
                $table->bigInteger('document_id')->unsigned()->index();
                $table->string('subject_type', 16)->index();
                $table->bigInteger('subject_id')->unsigned()->nullable()->index();
                $table->string('subject_key', 190)->nullable()->index();
                $table->boolean('can_read')->default(true)->index();
                $table->boolean('can_write')->default(false)->index();
                $table->boolean('can_manage')->default(false)->index();
                $table->timestamps();

                $table->unique(
                    ['document_id', 'subject_type', 'subject_id', 'subject_key'],
                    'editor_html_acl_subject_unique',
                );
            });
        }

        if (!$schema->hasTable(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)) {
            $schema->create(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS, static function (Blueprint $table): void {
                $table->id();
                $table->bigInteger('document_id')->unsigned()->index();
                $table->string('language_code', 16)->default('hr')->index();
                $table->integer('version_number')->index();
                $table->string('title', 255);
                $table->string('storage_driver', 32)->default('filesystem')->index();
                $table->string('content_path', 512)->nullable();
                $table->longText('content_html')->nullable();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->timestamp('created_at')->nullable();

                $table->unique(['document_id', 'version_number'], 'editor_html_versions_unique');
                $table->index(
                    ['document_id', 'language_code', 'version_number'],
                    'editor_html_versions_language_idx',
                );
            });
        }

        if (!$schema->hasTable(ModuleEditorHtml::TABLE_ASSETS)) {
            $schema->create(ModuleEditorHtml::TABLE_ASSETS, static function (Blueprint $table): void {
                $table->id();
                $table->string('uuid', 36)->unique();
                $table->bigInteger('document_id')->unsigned()->nullable()->index();
                $table->string('original_name', 255);
                $table->string('display_name', 255)->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->string('caption', 255)->nullable();
                $table->text('description')->nullable();
                $table->string('stored_name', 255);
                $table->string('mime_type', 190)->index();
                $table->bigInteger('file_size')->unsigned()->default(0);
                $table->string('storage_driver', 32)->default('filesystem')->index();
                $table->string('content_path', 512)->nullable();
                $table->bigInteger('created_by_user_id')->unsigned()->nullable()->index();
                $table->boolean('is_deleted')->default(false)->index();
                $table->bigInteger('deleted_by_user_id')->unsigned()->nullable()->index();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * HR: Briše editor tablice obrnutim redoslijedom.
     *
     * EN: Drops editor tables in reverse order.
     */
    public function down(Database $db): void
    {
        $schema = $db->schema();

        foreach (
            [
                ModuleEditorHtml::TABLE_ASSETS,
                ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS,
                ModuleEditorHtml::TABLE_DOCUMENT_ACL,
                ModuleEditorHtml::TABLE_DOCUMENTS,
            ] as $table
        ) {
            $schema->dropIfExists($table);
        }
    }
};
