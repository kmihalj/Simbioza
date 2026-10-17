<?php

declare(strict_types=1);

namespace App\Module;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

use function array_filter;
use function array_keys;
use function array_values;
use function in_array;
use function is_string;
use function strtolower;
use function trim;

/**
 * EN: Central catalog of bundled Simbioza modules, their dependencies,
 *     migrations, and tables exclusively owned by each optional module.
 *
 * HR: Središnji katalog ugrađenih Simbioza modula, njihovih ovisnosti,
 *     migracija i tablica kojima pojedini opcionalni modul isključivo upravlja.
 */
final readonly class ModuleCatalog
{
    /**
     * EN: Returns the canonical module order required for a valid bootstrap.
     *
     * HR: Vraća kanonski redoslijed modula potreban za ispravan bootstrap.
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
            // EN: API and Task are not part of the minimal installation. They
            //     can be added separately while the catalog checks hard dependencies.
            //
            // HR: API i Task nisu dio minimalne instalacije. Mogu se dodati
            //     zasebno, a katalog pritom provjerava njihove tvrde ovisnosti.
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
                        ],
            ),
            'workspace-search' => $this->definition(
                'aaieduhr/simbioza-module-workspace-search',
                'Pretraživanje područja',
                'Workspace search',
                dependencies: ['workspace'],
                migrations: [
                    '20260812130000_install_workspace_search_schema',
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
                    '20261016130000_widen_calendar_event_text',
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
            // EN: The optional personal-adjustments module does not create tables.
            //
            // HR: Opcionalni modul osobnih prilagodbi ne stvara tablice.
            'accessibility' => $this->definition(
                'aaieduhr/heartphrame-module-accessibility',
                'Pristupačnost',
                'Accessibility',
                optional: true,
            ),
        ];
    }

    /**
     * EN: Returns one validated definition by short name.
     *
     * HR: Vraća jednu provjerenu definiciju po kratkom nazivu.
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
     * EN: Returns optional module slugs.
     *
     * HR: Vraća kratke nazive opcionalnih modula.
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
     * EN: Returns optional modules recommended for a new installation.
     *
     * HR: Vraća opcionalne module preporučene za novu instalaciju.
     *
     * @return list<string>
     */
    public function recommendedSlugs(): array
    {
        return array_keys(array_filter(
            $this->definitions(),
            static fn(array $definition): bool => $definition['optional'] && $definition['recommended'],
        ));
    }

    /**
     * EN: Reads an optional package constraint from the release manifest so
     *     GUI and CLI use the same tag without a separately drifting list.
     *
     * HR: Čita ograničenje opcionalnog paketa iz manifesta izdanja; tako GUI i
     *     CLI koriste isti tag bez zasebnog popisa koji može zastarjeti.
     */
    public function constraintFor(string $slug): string
    {
        $definition = $this->definitionFor($slug);
        if (!$definition['optional']) {
            throw new InvalidArgumentException('The module is not an optional Composer package: ' . $slug);
        }

        try {
            $manifest = json_decode(
                (string)file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $jsonException) {
            throw new RuntimeException('The release Composer manifest is invalid.', 0, $jsonException);
        }

        if (!is_array($manifest)) {
            throw new RuntimeException('The release Composer manifest is invalid.');
        }

        $extra = $manifest['extra'] ?? null;
        $simbioza = is_array($extra) ? ($extra['simbioza'] ?? null) : null;
        $optionalModules = is_array($simbioza) ? ($simbioza['optional-modules'] ?? null) : null;
        $constraint = is_array($optionalModules) ? ($optionalModules[$definition['package']] ?? null) : null;
        if (!is_string($constraint) || trim($constraint) === '') {
            throw new RuntimeException('The release has no Composer constraint for optional module: ' . $slug);
        }

        return $constraint;
    }

    /**
     * EN: Returns required module slugs.
     *
     * HR: Vraća kratke nazive obaveznih modula.
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
     * EN: Converts installer selection into a complete, correctly ordered package list.
     *
     * HR: Pretvara instalacijski odabir u potpun, pravilno poredan popis paketa.
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

    /**
     * EN: Returns a package slug or NULL for an unknown package.
     *
     * HR: Vraća kratki naziv paketa ili NULL za nepoznati paket.
     */
    public function slugForPackage(string $package): ?string
    {
        foreach ($this->definitions() as $slug => $definition) {
            if ($definition['package'] === trim($package)) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * EN: Returns a migration owner or NULL.
     *
     * HR: Vraća vlasnika migracije ili NULL.
     */
    public function slugForMigration(string $migration): ?string
    {
        foreach ($this->definitions() as $slug => $definition) {
            foreach ($definition['migrations'] as $ownedMigration) {
                if ($this->migrationMatches($ownedMigration, $migration)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * EN: Compares migrations independently of an installer wrapper timestamp.
     *
     * HR: Uspoređuje migracije neovisno o vremenskoj oznaci instalacijskog omotača.
     */
    public function migrationMatches(string $catalogMigration, string $actualMigration): bool
    {
        return $this->migrationIdentity($catalogMigration) === $this->migrationIdentity($actualMigration);
    }

    /**
     * EN: Normalizes and validates a short name.
     *
     * HR: Normalizira i provjerava kratki naziv.
     */
    public function normalizeSlug(string $slug): string
    {
        return strtolower(trim($slug));
    }

    /**
     * EN: Returns the stable migration name without its leading timestamp.
     *
     * HR: Vraća stabilni dio naziva migracije bez početne vremenske oznake.
     */
    private function migrationIdentity(string $migration): string
    {
        return preg_replace('/\A\d{14}_/', '', trim($migration)) ?? trim($migration);
    }

    /**
     * EN: Builds a strictly typed internal module definition.
     *
     * HR: Gradi strogo tipiziranu internu definiciju modula.
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
