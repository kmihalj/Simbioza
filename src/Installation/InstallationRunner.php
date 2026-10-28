<?php

declare(strict_types=1);

namespace App\Installation;

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthGroupService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserAttributeService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use AaiEduHr\HeartPhrameModuleTheme\ModuleTheme;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Module\ModuleCatalog;
use HeartPhrame\App;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use JsonException;
use ReflectionClass;
use RuntimeException;

/**
 * EN: Completes installation in one strict sequence and writes the lock only at the end.
 *
 * HR: Dovršava instalaciju jednim strogo uređenim slijedom i tek na kraju zapisuje lock.
 */
final readonly class InstallationRunner
{
    /**
     * EN: The package contains only public starter guides; this is not an installation secret.
     *
     * HR: Paket sadrži isključivo javne početne upute; ovo nije instalacijska tajna.
     */
    private const USER_GUIDES_ARCHIVE_PASSPHRASE = 'SimbiozaSeed2026!';

    private const USER_GUIDES_WORKSPACE_SLUG = 'korisnicke-upute';

    private const THEME_PLACEHOLDER_ID = 'installation-placeholder';

    /**
     * EN: Initializes all installation dependencies.
     *
     * HR: Inicijalizira sve instalacijske ovisnosti.
     */
    public function __construct(
        private InstallationPaths $paths,
        private InstallationAccessToken $accessToken,
        private InstallationConfigWriter $configWriter,
        private InstallationDatabaseTester $databaseTester,
        private InstallationInputValidator $validator,
        private InstallationRequirements $requirements,
        private InstallationLogger $logger,
    ) {
    }

    /**
     * EN: Revalidates input, database, and requirements, then runs migrations,
     * imports the theme and public guides, creates the administrator, and locks installation.
     *
     * HR: Ponovno provjerava ulaz, bazu i preduvjete pa pokreće migracije,
     * uvozi temu, izrađuje administratora i zaključava instalaciju.
     *
     * @param array<array-key, mixed> $databaseInput
     * @param array<array-key, mixed> $applicationInput
     * @param array<array-key, mixed> $administratorInput
     * @return array{migration_count:int,theme_id:string,administrator_login:string,workspace_slug:string}
     */
    public function run(
        array $databaseInput,
        array $applicationInput,
        array $administratorInput,
        string $basePath = '',
    ): array {
        if ($this->paths->isInstalled()) {
            throw new RuntimeException('The application is already installed.');
        }

        $databaseInput = $this->validator->database($databaseInput);
        $application = $this->validator->application($applicationInput);
        if (!array_key_exists('password_confirmation', $administratorInput)) {
            // EN: The web step already checked confirmation and keeps only one secret copy in the session.
            //
            // HR: Web-korak je potvrdu već provjerio i u session sprema samo jednu kopiju tajne.
            $administratorInput['password_confirmation'] = $administratorInput['password'] ?? '';
        }

        $administrator = $this->validator->administrator($administratorInput);
        $driver = $databaseInput['driver'];
        if (!$this->requirements->passes($driver)) {
            throw new RuntimeException('One or more installation requirements are not satisfied.');
        }

        $this->databaseTester->test($databaseInput);
        $this->prepareSelectedLanguages($application['supported_locales']);
        $application['base_path'] = rtrim($basePath, '/');
        $this->configWriter->write($databaseInput, $application);
        $catalog = new ModuleCatalog();
        $enabledPackages = $catalog->packagesForSelection($application['optional_modules']);
        [$config, $database] = $this->runtime();
        $themePackage = $catalog->definitionFor('theme')['package'];
        // EN: Check before migrations, or a failed theme import leaves a schema
        //     that the next installation attempt no longer accepts as empty.
        //
        // HR: Provjera mora prethoditi migracijama jer inače neuspjeli uvoz teme
        //     ostavlja shemu u bazi koju idući pokušaj više ne prihvaća kao praznu.
        if (in_array($themePackage, $enabledPackages, true)) {
            $this->assertThemeStorageWritable($config);
        }

        $appliedMigrations = $this->migrate($database, $enabledPackages);
        $themeId = '';
        if (in_array($themePackage, $enabledPackages, true)) {
            $this->prepareThemeStorage();
            $themeId = $this->importTheme($config);
        }

        $administratorId = $this->createAdministrator(
            $database,
            $config,
            $administrator,
            $application['timezone'],
        );
        $workspaceSlug = $this->importUserGuides(
            $database,
            $administratorId,
            $basePath,
            $enabledPackages,
            $application['supported_locales'],
        );
        $this->writeLock($driver, $application, $themeId);

        $this->logger->info(sprintf(
            'Installation completed with %d applied migrations and database driver %s.',
            count($appliedMigrations),
            $driver,
        ));

        return [
            'migration_count' => count($appliedMigrations),
            'theme_id' => $themeId,
            'administrator_login' => $administrator['login'],
            'workspace_slug' => $workspaceSlug,
        ];
    }

    /**
     * EN: CLI installation prepares released external locales unless the web step already did so.
     *
     * HR: CLI instalacija priprema objavljene vanjske jezike kada ih web-korak nije već pripremio.
     *
     * @param list<string> $selected
     */
    private function prepareSelectedLanguages(array $selected): void
    {
        $root = $this->paths->appRoot();
        $languages = new LanguagePackManager($root, new ModuleCatalog());
        $installed = array_column($languages->status(), null, 'locale');
        $repository = new RepositoryLanguageManager(new LanguageRepository($root), $languages, $root);
        foreach ($selected as $locale) {
            if (!in_array($locale, ['hr', 'en'], true) && !isset($installed[$locale])) {
                $repository->install($locale);
            }
        }
    }

    /**
     * EN: Builds the minimal runtime required by migrations and module services.
     *
     * HR: Gradi minimalni runtime potreban migracijama i servisima modula.
     *
     * @return array{Config,Database}
     */
    private function runtime(): array
    {
        $helper = new Helper();
        $config = new Config($helper, [], $this->paths->appRoot());
        $config->loadLayeredDirectories($this->paths->configuration()->directories());

        return [$config, new Database($config, $helper)];
    }

    /**
     * EN: Loads and actually executes every application migration through the ORM migrator.
     *
     * HR: Učitava i stvarno izvršava sve aplikacijske migracije preko ORM migratora.
     *
     * @param list<string> $enabledPackages
     * @return list<string>
     */
    private function migrate(Database $database, array $enabledPackages): array
    {
        $files = glob($this->paths->migrationsDirectory() . DIRECTORY_SEPARATOR . '*.php');
        if (!is_array($files) || $files === []) {
            throw new RuntimeException('No application migrations were found.');
        }

        sort($files, SORT_STRING);
        $migrations = [];
        $catalog = new ModuleCatalog();
        foreach ($files as $file) {
            $migrationName = pathinfo($file, PATHINFO_FILENAME);
            $owner = $catalog->slugForMigration($migrationName);
            if ($owner !== null) {
                $ownerPackage = $catalog->definitionFor($owner)['package'];
                if (!in_array($ownerPackage, $enabledPackages, true)) {
                    continue;
                }
            }

            $migration = require $file;
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException('An application migration has an invalid contract.');
            }

            $migrations[$migrationName] = $migration;
        }

        return array_values($database->migrator()->migrate($migrations));
    }

    /**
     * EN: Imports the checksummed current-theme package and enables automatic light/dark mode.
     *
     * HR: Uvozi checksummed paket aktualne teme i aktivira automatski light/dark način.
     */
    private function importTheme(Config $config): string
    {
        $moduleFile = (new ReflectionClass(ModuleTheme::class))->getFileName();
        if (!is_string($moduleFile)) {
            throw new RuntimeException('The Theme module path could not be resolved.');
        }

        $repository = new ThemeConfigRepository($config, dirname($moduleFile, 2));
        $archive = new ThemeArchiveService($repository, new ThemeAssetLibrary($repository));
        $themeId = $archive->importFile($this->paths->themePackage());
        $repository->saveSettings($themeId, 'auto', true);
        $repository->deleteTheme(self::THEME_PLACEHOLDER_ID);
        $this->removeUnusedThemeDirectories($repository, $themeId);
        $theme = $repository->themeById($themeId);
        $themeFiles = $repository->themeFiles($themeId);
        $light = $theme['light'] ?? null;
        $dark = $theme['dark'] ?? null;
        if (
            !is_array($light)
            || !is_array($light['colors'] ?? null)
            || !is_array($dark)
            || !is_array($dark['colors'] ?? null)
            || $themeFiles === []
        ) {
            throw new RuntimeException('The imported theme is incomplete.');
        }

        return $themeId;
    }

    /**
     * EN: Checks persistent theme files before irreversible database migrations.
     *
     * HR: Provjerava trajne datoteke teme prije nepovratnih migracija baze.
     */
    private function assertThemeStorageWritable(Config $config): void
    {
        $directory = $this->paths->themeConfigDirectory();
        // EN: An explicitly prepared writable override may not yet have Theme's JSON subdirectory.
        //
        // HR: Izričito pripremljeni zapisivi vanjski sloj možda još nema JSON poddirektorij teme.
        if (!is_dir($directory) && is_writable(dirname($directory)) && !mkdir($directory, 0770)) {
            throw new RuntimeException('The theme configuration directory cannot be created.');
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('The theme configuration directory is not writable.');
        }

        foreach (['themes.json', 'settings.json'] as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) && !is_writable($path)) {
                throw new RuntimeException('The theme configuration file is not writable: ' . $file);
            }
        }

        $moduleFile = (new ReflectionClass(ModuleTheme::class))->getFileName();
        if (!is_string($moduleFile)) {
            throw new RuntimeException('The Theme module path could not be resolved.');
        }

        $repository = new ThemeConfigRepository($config, dirname($moduleFile, 2));
        if (!is_writable($repository->themesDirectoryPath())) {
            throw new RuntimeException('The theme asset directory is not writable.');
        }
    }

    /**
     * EN: Before import, removes configured starter themes and leaves one temporary
     * theme so the repository does not activate its fallback examples.
     *
     * HR: Prije importa uklanja konfiguracijske početne teme i ostavlja jednu
     * privremenu temu kako repozitorij ne bi aktivirao svoje fallback primjere.
     */
    private function prepareThemeStorage(): void
    {
        $directory = $this->paths->themeConfigDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('The installation theme directory could not be created.');
        }

        $themes = [[
            'id' => self::THEME_PLACEHOLDER_ID,
            'label' => ['hr' => 'Instalacija', 'en' => 'Installation'],
            'system' => false,
            'light' => ['colors' => []],
            'dark' => ['colors' => []],
        ]];
        $encoded = json_encode(
            $themes,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        if (file_put_contents($directory . '/themes.json', $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The installation theme storage could not be prepared.');
        }
    }

    /**
     * EN: Removes files for every theme except active Simbioza.
     *
     * HR: Uklanja datoteke svih tema osim aktivne Simbioza teme.
     */
    private function removeUnusedThemeDirectories(ThemeConfigRepository $repository, string $themeId): void
    {
        $directories = glob($repository->themesDirectoryPath() . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach (is_array($directories) ? $directories : [] as $directory) {
            $candidate = basename($directory);
            if ($candidate !== $themeId && preg_match('/^[a-z0-9][a-z0-9._-]*$/D', $candidate) === 1) {
                $repository->deleteThemeFiles($candidate);
            }
        }
    }

    /**
     * EN: Imports the public bilingual guide workspace and maps all source authors
     * to the newly created administrator.
     *
     * HR: Uvozi javno područje s dvojezičnim uputama i sve izvorne autore
     * preslikava na upravo kreiranog administratora.
     *
     * @param list<string> $enabledPackages
     * @param list<string> $selectedLocales
     */
    private function importUserGuides(
        Database $database,
        int $administratorId,
        string $basePath,
        array $enabledPackages,
        array $selectedLocales,
    ): string {
        // EN: Native Wiki guides must not install Wiki into a standalone product.
        //
        // HR: Ugrađene Wiki upute ne smiju instalirati Wiki u samostalan proizvod.
        $workspacePackage = (new ModuleCatalog())->definitionFor('workspace')['package'];
        if (!in_array($workspacePackage, $enabledPackages, true)) {
            return '';
        }

        try {
            $runtimeConfig = $this->prepareImportConfig($enabledPackages);
            $application = new App(
                [...$this->paths->configuration()->directories(), $runtimeConfig],
                $this->paths->appRoot(),
            );
            // EN: Backup and its business providers are deferred modules. In
            //     the installer CLI context, the public lifecycle loads and
            //     registers them before BackupManager is resolved.
            //
            // HR: Backup i njegovi poslovni provideri odgođeni su moduli. U
            //     instalacijskom CLI kontekstu javni lifecycle ih učitava i
            //     registrira prije dohvaćanja BackupManagera.
            $application->loadCommands();
            $manager = $application->getContainer()->get(BackupManager::class);
            if (!$manager instanceof BackupManager) {
                throw new RuntimeException('The Backup manager is unavailable during installation.');
            }

            $skippedProviders = $this->unavailableGuideProviders($enabledPackages);
            $context = new BackupImportContext(
                new BackupScope(BackupScope::WORKSPACE, self::USER_GUIDES_WORKSPACE_SLUG),
                BackupImportContext::CONFLICT_COPY,
                [],
                $skippedProviders,
                [
                    'site_base_url' => \App\Update\BundledAssetsUpdater::guideSiteUrl($basePath),
                    'workspace-scope' => [
                        'target_slug' => self::USER_GUIDES_WORKSPACE_SLUG,
                        'preserve_name_on_copy' => true,
                    ],
                    'comment-workspace' => ['fallback_users_to_actor' => true],
                    'task-workspace' => ['fallback_users_to_actor' => true],
                ],
                $administratorId,
                self::USER_GUIDES_ARCHIVE_PASSPHRASE,
            );
            $preflight = $manager->preflight($this->paths->userGuidesPackage(), $context);
            if (!$preflight->isAllowed()) {
                throw new RuntimeException(
                    'The bundled user guides failed preflight: ' . implode(' | ', $preflight->errors),
                );
            }

            $manager->restore($this->paths->userGuidesPackage(), $context);
            $this->retainSelectedGuideLocales($database, $selectedLocales);
            $this->rewriteImportedUserGuidePaths($database, $basePath);

            return self::USER_GUIDES_WORKSPACE_SLUG;
        } finally {
            $this->removeImportConfig();
        }
    }

    /**
     * EN: On a fresh install, keeps only selected guides; third-party locales retain EN and HR as temporary fallback.
     *
     * HR: Na svježoj instalaciji zadržava samo odabrane upute; za treće jezike čuva EN i HR kao privremeni fallback.
     *
     * @param list<string> $selected
     */
    private function retainSelectedGuideLocales(Database $database, array $selected): void
    {
        $wanted = array_values(array_intersect(['en', 'hr'], $selected));
        if (array_diff($selected, ['en', 'hr']) !== []) {
            $wanted = ['en', 'hr'];
        }

        if ($wanted === ['en', 'hr'] || $wanted === ['hr', 'en']) {
            return;
        }

        $preferred = $wanted[0] ?? 'en';
        $workspace = $database->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACES)
            ->where('slug', '=', self::USER_GUIDES_WORKSPACE_SLUG)->first();
        if (!is_array($workspace)) {
            throw new RuntimeException('Imported guide workspace is missing.');
        }

        $workspaceId = (int)$workspace['id'];
        $database->transaction(function (Database $database) use ($workspace, $workspaceId, $wanted, $preferred): void {
            $workspaceUpdate = [];
            foreach (['name_translations', 'description_translations'] as $field) {
                $workspaceUpdate[$field] = $this->filteredGuideTranslations($workspace[$field] ?? null, $wanted);
            }

            $name = json_decode((string)$workspaceUpdate['name_translations'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($name)) {
                throw new RuntimeException('The imported guide workspace has invalid name translations.');
            }

            if (is_string($name[$preferred] ?? null)) {
                $workspaceUpdate['name'] = $name[$preferred];
            }

            $database->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACES)
                ->where('id', '=', $workspaceId)->update($workspaceUpdate);

            $nodes = $database->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACE_NODES)
                ->where('workspace_id', '=', $workspaceId)->get();
            foreach ($nodes as $node) {
                $nodeId = (int)$node['id'];
                $titles = $this->filteredGuideTranslations($node['title_translations'] ?? null, $wanted);
                $titleMap = json_decode($titles, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($titleMap)) {
                    throw new RuntimeException('An imported guide page has invalid title translations.');
                }

                $update = ['title_translations' => $titles];
                if (is_string($titleMap[$preferred] ?? null)) {
                    $update['title'] = $titleMap[$preferred];
                }

                $database->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACE_NODES)
                    ->where('id', '=', $nodeId)->update($update);

                $workflows = $database
                    ->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS)
                    ->where('node_id', '=', $nodeId)->get();
                foreach ($workflows as $workflow) {
                    if (!in_array($workflow['language_code'] ?? null, $wanted, true)) {
                        $database
                            ->table(\AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS)
                            ->where('id', '=', (int)$workflow['id'])->delete();
                    }
                }

                $key = $node['document_key'] ?? null;
                if (!is_string($key) || $key === '') {
                    continue;
                }

                $document = $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
                    ->where('document_key', '=', $key)->first();
                if (!is_array($document)) {
                    continue;
                }

                $versions = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                    ->where('document_id', '=', (int)$document['id'])->get();
                $kept = [];
                foreach ($versions as $version) {
                    if (in_array($version['language_code'] ?? null, $wanted, true)) {
                        $kept[] = $version;
                    } else {
                        $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                            ->where('id', '=', (int)$version['id'])->delete();
                    }
                }

                if ($kept !== []) {
                    $latest = $kept[array_key_last($kept)];
                    $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
                        ->where('id', '=', (int)$document['id'])->update([
                            'default_language' => $preferred,
                            'current_version_id' => (int)$latest['id'],
                            'title' => (string)$latest['title'],
                        ]);
                }
            }
        });
    }

    /**
     * EN: Narrows a guide translation JSON map to the permitted locales.
     *
     * HR: Smanjuje JSON mapu prijevoda samo na dopuštene jezike uputa.
     *
     * @param list<string> $wanted
     */
    private function filteredGuideTranslations(mixed $value, array $wanted): string
    {
        $decoded = is_string($value) && $value !== ''
        ? json_decode($value, true, 512, JSON_THROW_ON_ERROR)
        : [];
        $map = is_array($decoded) ? $decoded : [];
        return json_encode(
            array_intersect_key($map, array_flip($wanted)),
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * EN: Lists starter-package components owned by unselected optional modules
     *     so their empty or auxiliary data is explicitly skipped instead of
     *     being silently partially restored.
     *
     * HR: Navodi komponente početnog paketa koje pripadaju neodabranim
     *     opcionalnim modulima kako bi se njihov prazan ili pomoćni sadržaj
     *     izričito preskočio, bez tihog djelomičnog uvoza.
     *
     * @param list<string> $enabledPackages
     * @return list<string>
     */
    private function unavailableGuideProviders(array $enabledPackages): array
    {
        $catalog = new ModuleCatalog();
        $providers = [
            'calendar' => 'calendar-workspace',
            'comment' => 'comment-workspace',
            'confluence-import' => 'simbioza-confluence-import-workspace',
            'task' => 'task-workspace',
            'simbioza-user' => 'simbioza-user-workspace',
            'workspace-search' => 'workspace-search-workspace',
        ];
        $skipped = [];
        foreach ($providers as $module => $provider) {
            if (!in_array($catalog->definitionFor($module)['package'], $enabledPackages, true)) {
                $skipped[] = $provider;
            }
        }

        return $skipped;
    }

    /**
     * EN: The starter package stores internal links as canonical root-relative
     *     paths. A new installation may live at the domain root or in any
     *     subdirectory. The shared, idempotent normalizer only adds a missing
     *     base path: cross-site restore may already have resolved a link.
     *     External, relative, and fragment links remain unchanged.
     *
     * HR: Početni paket sprema interne poveznice kao kanonske root-relative
     *     putanje. Nova instalacija može biti u korijenu ili bilo kojem
     *     poddirektoriju. Zajednički, idempotentni normalizator dodaje samo
     *     nedostajuću osnovnu putanju: prijenos na drugi site možda ju je već dodao.
     *     Vanjske, relativne i sidrene poveznice ostaju nepromijenjene.
     */
    private function rewriteImportedUserGuidePaths(Database $database, string $basePath): void
    {
        $basePath = str_replace('\\', '/', trim($basePath));
        if ($basePath === '/' || $basePath === '.') {
            $basePath = '';
        }

        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && preg_match('#^/(?:[A-Za-z0-9._~-]+/?)*$#D', $basePath) !== 1) {
            throw new RuntimeException('The installation base path is invalid.');
        }

        if ($basePath === '') {
            return;
        }

        $rows = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get();
        $payloadConfig = new EditorHtmlConfig(
            new Config(new Helper(), [], $this->paths->appRoot()),
            $this->paths->appRoot() . '/vendor/aaieduhr/heartphrame-module-editor-html',
        );
        $database->transaction(static function (Database $database) use ($rows, $basePath, $payloadConfig): void {
            foreach ($rows as $row) {
                $id = is_numeric($row['id'] ?? null) ? (int)$row['id'] : 0;
                $html = is_string($row['content_html'] ?? null) ? $row['content_html'] : '';
                $file = null;
                if (($row['storage_driver'] ?? null) === 'filesystem') {
                    $file = $payloadConfig->documentPayloadPath(
                        is_string($row['content_path'] ?? null) ? $row['content_path'] : '',
                    );
                    $bytes = file_get_contents($file);
                    if (!is_string($bytes)) {
                        throw new RuntimeException('Imported user-guide HTML is unavailable.');
                    }

                    $html = $bytes;
                }

                if ($id <= 0 || $html === '') {
                    continue;
                }

                $rewritten = \App\Update\BundledAssetsUpdater::normalizeGuideHtmlPaths(
                    $html,
                    $basePath,
                );

                if ($rewritten !== $html) {
                    if ($file !== null) {
                        if (file_put_contents($file, $rewritten, LOCK_EX) !== strlen($rewritten)) {
                            throw new RuntimeException('Imported user-guide HTML could not be normalized.');
                        }
                    } else {
                        $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                            ->where('id', '=', $id)
                            ->update(['content_html' => $rewritten]);
                    }
                }
            }
        });
    }

    /**
     * EN: Builds a narrow no-web-bootstrap config layer with temp-root backup paths.
     *
     * HR: Gradi uski config sloj bez web bootstrapa i s temp-root backup putanjama.
     *
     * @param list<string> $enabledPackages
     * @return non-empty-string
     */
    private function prepareImportConfig(array $enabledPackages): string
    {
        $directory = $this->paths->importConfigDirectory();
        if ($directory === '') {
            throw new RuntimeException('The installation import configuration path is invalid.');
        }

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('The installation import configuration could not be created.');
        }

        $backup = [
            'archive_dir' => $this->paths->dataDirectory() . '/backups/archives',
            'staging_dir' => $this->paths->dataDirectory() . '/backups/staging',
            'upload_dir' => $this->paths->dataDirectory() . '/backups/uploads',
        ];
        $backupPackage = (new ModuleCatalog())->definitionFor('backup')['package'];
        if (!in_array($backupPackage, $enabledPackages, true)) {
            $enabledPackages[] = $backupPackage;
        }

        $files = [
            'bootstrap.php' => "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n",
            'backup/backup.php' => "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($backup, true) . ";\n",
            'app.php' => "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export([
                'modules' => ['enabled' => array_values($enabledPackages)],
            ], true) . ";\n",
        ];
        if (!is_dir($directory . '/backup') && !mkdir($directory . '/backup', 0700)) {
            throw new RuntimeException('The installation import Backup configuration could not be created.');
        }

        foreach ($files as $name => $contents) {
            if (file_put_contents($directory . '/' . $name, $contents, LOCK_EX) === false) {
                throw new RuntimeException('The installation import configuration could not be written.');
            }
        }

        return $directory;
    }

    /**
     * EN: Removes only this installer's temporary config layer.
     *
     * HR: Uklanja isključivo privremeni config sloj ovog instalera.
     */
    private function removeImportConfig(): void
    {
        $directory = $this->paths->importConfigDirectory();
        foreach (['bootstrap.php', 'backup/backup.php', 'app.php'] as $file) {
            $path = $directory . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($directory . '/backup')) {
            rmdir($directory . '/backup');
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }

    /**
     * EN: Transactionally creates the sole initial administrator account.
     *
     * HR: Transakcijski izrađuje jedini prvi administratorski račun.
     *
     * @param array{
     *     login:string,
     *     display_name:string,
     *     first_name:string,
     *     last_name:string,
     *     email:string,
     *     password:string
     * } $administrator
     */
    private function createAdministrator(
        Database $database,
        Config $config,
        array $administrator,
        string $timezone,
    ): int {
        date_default_timezone_set($timezone);
        $attributeService = new AuthUserAttributeService($database, $config);
        $groupService = new AuthGroupService($database);
        $userService = new AuthUserService($database, $attributeService, $groupService);

        $created = $database->transaction(static fn(): array => $userService->createInitialAdministrator(
            $administrator['login'],
            $administrator['display_name'],
            $administrator['first_name'],
            $administrator['last_name'],
            $administrator['email'],
            $administrator['password'],
        ));

        $administratorId = is_array($created) && is_numeric($created['id'] ?? null)
        ? (int)$created['id']
        : 0;
        if ($administratorId <= 0) {
            throw new RuntimeException('The initial administrator ID could not be resolved.');
        }

        return $administratorId;
    }

    /**
     * EN: Atomically activates the permanent lock after removing any remaining token copy.
     *
     * HR: Atomski aktivira trajni lock nakon uklanjanja svake preostale kopije tokena.
     *
     * @param array{
     *     name:string,
     *     primary_locale:string,
     *     supported_locales:list<string>,
     *     timezone:string,
     *     optional_modules?:list<string>
     * } $application
     */
    private function writeLock(string $driver, array $application, string $themeId): void
    {
        try {
            $contents = json_encode([
                'installed_at' => gmdate(DATE_ATOM),
                'database_driver' => $driver,
                'primary_locale' => $application['primary_locale'],
                'supported_locales' => $application['supported_locales'],
                'optional_modules' => $application['optional_modules'] ?? [],
                'theme_id' => $themeId,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $jsonException) {
            throw new RuntimeException('The installation lock metadata could not be encoded.', 0, $jsonException);
        }

        $temporaryPath = tempnam($this->paths->dataDirectory(), '.simbioza-lock-');
        if (!is_string($temporaryPath)) {
            throw new RuntimeException('The installation lock could not be prepared.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents . PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('The installation lock could not be written.');
            }

            // EN: The lock contains no secrets; Setup/CLI must be able to read
            //     it through the exclusive runtime group to clean temporary packages.
            //
            // HR: Lock ne sadrži tajne; Setup/CLI ga moraju moći pročitati
            //     kroz isključivu runtime grupu radi čišćenja privremenih paketa.
            if (!chmod($temporaryPath, 0640)) {
                throw new RuntimeException('The installation lock permissions could not be secured.');
            }

            $this->accessToken->remove();
            if (!rename($temporaryPath, $this->paths->lockFile())) {
                throw new RuntimeException('The installation lock could not be activated.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
