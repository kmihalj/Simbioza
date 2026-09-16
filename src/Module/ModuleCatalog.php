<?php

declare(strict_types=1);

namespace App\Module;

use InvalidArgumentException;

use function array_filter;
use function array_keys;
use function array_values;
use function in_array;
use function is_string;
use function strtolower;
use function trim;

/**
 * HR: Središnji katalog ugrađenih Simbioza modula, njihovih ovisnosti,
 *     migracija i tablica kojima pojedini opcionalni modul isključivo upravlja.
 * EN: Central catalog of bundled Simbioza modules, their dependencies,
 *     migrations, and tables exclusively owned by each optional module.
 */
final readonly class ModuleCatalog
{
    /**
     * HR: Vraća kanonski redoslijed modula potreban za ispravan bootstrap.
     * EN: Returns the canonical module order required for a valid bootstrap.
     *
     * @return array<string,array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}>
     */
    public function definitions(): array
    {
        return [
            'orm' => $this->definition('aaieduhr/heartphrame-module-orm', 'ORM i baza', 'ORM and database'),
            'menu' => $this->definition('aaieduhr/heartphrame-module-menu', 'Izbornik', 'Menu', dependencies: ['orm']),
            'theme' => $this->definition(
                'aaieduhr/heartphrame-module-theme',
                'Tema',
                'Theme',
                true,
                ['menu'],
                recommended: true,
            ),
            'auth' => $this->definition(
                'aaieduhr/heartphrame-module-auth',
                'Autentikacija i ovlasti',
                'Authentication and authorization',
                dependencies: ['orm', 'menu'],
                migrations: ['20260605093456_install_auth_module_schema'],
            ),
            'audit' => $this->definition(
                'aaieduhr/heartphrame-module-audit',
                'Dnevnici aktivnosti',
                'Audit log',
                true,
                ['auth'],
                ['20260814110000_install_audit_module_schema'],
                ['audit_events'],
            ),
            // HR: API i Task nisu dio minimalne instalacije. Mogu se dodati
            //     zasebno, a katalog pritom provjerava njihove tvrde ovisnosti.
            // EN: API and Task are not part of the minimal installation. They
            //     can be added separately while the catalog checks hard dependencies.
            'api' => $this->definition(
                'aaieduhr/heartphrame-module-api',
                'Aplikacijski API',
                'Application API',
                optional: true,
                dependencies: ['auth'],
                migrations: ['20260729211500_install_api_module_schema'],
            ),
            'email' => $this->definition(
                'aaieduhr/heartphrame-module-email',
                'E-pošta',
                'E-mail',
                true,
                ['auth'],
                ['20260718090000_install_email_module_schema'],
                ['email_outbox'],
            ),
            'notification' => $this->definition(
                'aaieduhr/heartphrame-module-notification',
                'Obavijesti',
                'Notifications',
                dependencies: ['auth'],
                migrations: ['20260718090100_install_notification_module_schema'],
            ),
            'editor-html' => $this->definition(
                'aaieduhr/heartphrame-module-editor-html',
                'HTML editor',
                'HTML editor',
                dependencies: ['auth'],
                migrations: [
                    '20260625174127_install_editor_html_module_schema',
                    '20260821160000_add_editor_document_includes',
                    '20260915170000_add_editor_asset_versions',
                ],
            ),
            'task' => $this->definition(
                'aaieduhr/heartphrame-module-task',
                'Zadaci',
                'Tasks',
                optional: true,
                dependencies: ['auth', 'notification', 'editor-html'],
                migrations: ['20260728093000_install_task_module_schema'],
            ),
            'comment' => $this->definition(
                'aaieduhr/heartphrame-module-comment',
                'Komentari',
                'Comments',
                true,
                ['auth', 'notification', 'editor-html'],
                ['20260730090000_install_comment_module_schema'],
                [
                    'document_comment_settings',
                    'document_comments',
                    'document_comment_reactions',
                    'document_comment_reports',
                ],
            ),
            'workspace' => $this->definition(
                'aaieduhr/simbioza-module-workspace',
                'Područja',
                'Workspaces',
                dependencies: ['auth', 'editor-html'],
                migrations: [
                    '20260717120804_install_workspace_module_schema',
                    '20260815110000_add_workspace_backlinks',
                    '20260821102216_add_workspace_homepage_view_options',
                    '20260821163942_add_workspace_node_labels',
                    '20260821220000_add_workspace_node_properties',
                    '20260826161934_add_workspace_node_direct_permissions',
                    '20260826190000_remove_workspace_owner',
                    '20260828100000_add_workspace_metadata_translations',
                    '20260905223000_add_workspace_tree_hiding',
                    // HR: Migracija prilagođava zajedničke JSON postavke, ali ne
                    //     stvara shemu Theme modula i mora ostati evidentirana i
                    //     kada se opcionalni Theme modul ukloni.
                    // EN: This migration adjusts shared JSON settings but does not
                    //     create Theme schema, so it must remain recorded when the
                    //     optional Theme module is removed.
                    '20260911160000_add_theme_component_heights',
                ],
            ),
            'workspace-search' => $this->definition(
                'aaieduhr/simbioza-module-workspace-search',
                'Pretraživanje područja',
                'Workspace search',
                dependencies: ['workspace'],
                migrations: [
                    '20260812130000_install_workspace_search_schema',
                    '20260909120000_add_workspace_search_modification_metadata',
                ],
            ),
            'calendar' => $this->definition(
                'aaieduhr/heartphrame-module-calendar',
                'Kalendari i sastanci',
                'Calendars and meetings',
                true,
                ['auth', 'notification'],
                [
                    '20260608000212_install_calendar_module_schema',
                    '20260905213000_scope_calendar_event_uid_to_calendar',
                    '20260911123000_ensure_calendar_manager_group',
                    '20260911193000_add_calendar_meeting_scheduler',
                ],
                [
                    'calendar_calendars',
                    'calendar_calendar_acl',
                    'calendar_calendar_followers',
                    'calendar_event_types',
                    'calendar_events',
                    'calendar_attendee_scopes',
                    'calendar_event_attendee_scopes',
                    'calendar_event_responses',
                    'calendar_event_resources',
                    'calendar_resource_approvers',
                    'calendar_caldav_credentials',
                ],
            ),
            'simbioza-user' => $this->definition(
                'aaieduhr/simbioza-module-user',
                'Simbioza korisnik',
                'Simbioza user',
                dependencies: ['auth', 'workspace'],
                migrations: [
                    '20260818120000_install_simbioza_user_schema',
                    '20260818170000_add_simbioza_user_follow_exclusions',
                    '20260820150000_add_simbioza_user_personal_workspaces',
                    '20260901223000_add_simbioza_user_theme_mode',
                ],
            ),
            'confluence-import' => $this->definition(
                'aaieduhr/simbioza-module-confluence-import',
                'Confluence uvoz',
                'Confluence import',
                true,
                ['auth', 'editor-html', 'workspace', 'simbioza-user'],
                [
                    '20260820170000_install_simbioza_confluence_import_schema',
                    '20260824120000_scope_confluence_attachment_identity_to_import_job',
                    '20260915170100_add_confluence_attachment_audit',
                ],
                [
                    'simbioza_confluence_import_jobs',
                    'simbioza_confluence_import_spaces',
                    'simbioza_confluence_import_content',
                    'simbioza_confluence_import_identities',
                    'simbioza_confluence_import_groups',
                    'simbioza_confluence_import_links',
                    'simbioza_confluence_import_attachments',
                ],
            ),
            'backup' => $this->definition(
                'aaieduhr/heartphrame-module-backup',
                'Sigurnosne kopije',
                'Backup',
                true,
                ['auth', 'workspace'],
                ['20260813100000_install_backup_schema'],
                ['backup_jobs', 'backup_uploads'],
            ),
        ];
    }

    /**
     * HR: Vraća jednu provjerenu definiciju po kratkom nazivu.
     * EN: Returns one validated definition by short name.
     *
     * @return array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}
     */
    public function definitionFor(string $slug): array
    {
        $slug = $this->normalizeSlug($slug);
        $definition = $this->definitions()[$slug] ?? null;
        if (!is_array($definition)) {
            throw new InvalidArgumentException('Unknown Simbioza module: ' . $slug);
        }

        return $definition;
    }

    /**
     * HR: Vraća kratke nazive opcionalnih modula.
     * EN: Returns optional module slugs.
     *
     * @return list<string>
     */
    public function optionalSlugs(): array
    {
        return array_keys(array_filter(
            $this->definitions(),
            static fn(array $definition): bool => $definition['optional'],
        ));
    }

    /**
     * HR: Vraća opcionalne module preporučene za novu instalaciju.
     * EN: Returns optional modules recommended for a new installation.
     * @return list<string>
     */
    public function recommendedSlugs(): array
    {
        return array_keys(array_filter(
            $this->definitions(),
            static fn(array $definition): bool => $definition['optional'] && $definition['recommended'],
        ));
    }

    /** HR: Vraća zaključano Composer ograničenje paketa iz kataloga. EN: Returns a catalog-pinned Composer package constraint. */
    public function constraintFor(string $slug): string
    {
        return match ($this->normalizeSlug($slug)) {
            'theme' => '^0.1.14',
            'audit' => '^0.1.0',
            'api' => '^0.1.3',
            'email' => '^0.1.2',
            'task' => '^0.1.4',
            'comment' => '^0.1.2',
            'calendar' => '^0.1.18',
            'confluence-import' => '^0.1.32',
            'backup' => '^0.1.5',
            default => throw new InvalidArgumentException('The module is not an optional Composer package: ' . $slug),
        };
    }

    /**
     * HR: Vraća kratke nazive obaveznih modula.
     * EN: Returns required module slugs.
     *
     * @return list<string>
     */
    public function requiredSlugs(): array
    {
        return array_keys(array_filter(
            $this->definitions(),
            static fn(array $definition): bool => !$definition['optional'],
        ));
    }

    /**
     * HR: Pretvara instalacijski odabir u potpun, pravilno poredan popis paketa.
     * EN: Converts installer selection into a complete, correctly ordered package list.
     *
     * @param list<string> $optionalSlugs
     * @return list<string>
     */
    public function packagesForSelection(array $optionalSlugs): array
    {
        $selected = [];
        foreach ($optionalSlugs as $slug) {
            if (is_string($slug)) {
                $normalized = $this->normalizeSlug($slug);
                if (in_array($normalized, $this->optionalSlugs(), true)) {
                    $selected[$normalized] = true;
                }
            }
        }

        $packages = [];
        foreach ($this->definitions() as $slug => $definition) {
            if (!$definition['optional'] || isset($selected[$slug])) {
                $packages[] = $definition['package'];
            }
        }

        return $packages;
    }

    /** HR: Vraća kratki naziv paketa ili NULL za nepoznati paket. EN: Returns a package slug or NULL for an unknown package. */
    public function slugForPackage(string $package): ?string
    {
        foreach ($this->definitions() as $slug => $definition) {
            if ($definition['package'] === trim($package)) {
                return $slug;
            }
        }

        return null;
    }

    /** HR: Vraća vlasnika migracije ili NULL. EN: Returns a migration owner or NULL. */
    public function slugForMigration(string $migration): ?string
    {
        foreach ($this->definitions() as $slug => $definition) {
            if (in_array(trim($migration), $definition['migrations'], true)) {
                return $slug;
            }
        }

        return null;
    }

    /** HR: Normalizira i provjerava kratki naziv. EN: Normalizes and validates a short name. */
    public function normalizeSlug(string $slug): string
    {
        return strtolower(trim($slug));
    }

    /**
     * HR: Gradi strogo tipiziranu internu definiciju modula.
     * EN: Builds a strictly typed internal module definition.
     *
     * @param list<string> $dependencies
     * @param list<string> $migrations
     * @param list<string> $tables
     * @return array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}
     */
    private function definition(
        string $package,
        string $labelHr,
        string $labelEn,
        bool $optional = false,
        array $dependencies = [],
        array $migrations = [],
        array $tables = [],
        bool $recommended = false,
    ): array {
        return [
            'package' => $package,
            'label_hr' => $labelHr,
            'label_en' => $labelEn,
            'optional' => $optional,
            'recommended' => $recommended,
            'dependencies' => array_values($dependencies),
            'migrations' => array_values($migrations),
            'tables' => array_values($tables),
        ];
    }
}
