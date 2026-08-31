<?php

declare(strict_types=1);

namespace App\Installation;

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthGroupService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserAttributeService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use AaiEduHr\HeartPhrameModuleTheme\ModuleTheme;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use HeartPhrame\App;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use JsonException;
use ReflectionClass;
use RuntimeException;

/**
 * HR: Dovršava instalaciju jednim strogo uređenim slijedom i tek na kraju zapisuje lock.
 * EN: Completes installation in one strict sequence and writes the lock only at the end.
 */
final readonly class InstallationRunner
{
    /**
     * HR: Paket sadrži isključivo javne početne upute; ovo nije instalacijska tajna.
     * EN: The package contains only public starter guides; this is not an installation secret.
     */
    private const USER_GUIDES_ARCHIVE_PASSPHRASE = 'SimbiozaSeed2026!';

    private const USER_GUIDES_WORKSPACE_SLUG = 'korisnicke-upute';

    private const THEME_PLACEHOLDER_ID = 'installation-placeholder';

    /** HR: Inicijalizira sve instalacijske ovisnosti. EN: Initializes all installation dependencies. */
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
     * HR: Ponovno provjerava ulaz, bazu i preduvjete pa pokreće migracije,
     * uvozi temu, izrađuje administratora i zaključava instalaciju.
     *
     * EN: Revalidates input, database, and requirements, then runs migrations,
     * imports the theme and public guides, creates the administrator, and locks installation.
     *
     * @param array<array-key, mixed> $databaseInput
     * @param array<array-key, mixed> $applicationInput
     * @param array<array-key, mixed> $administratorInput
     * @return array{migration_count:int,theme_id:string,administrator_login:string,workspace_slug:string}
     */
    public function run(array $databaseInput, array $applicationInput, array $administratorInput): array
    {
        if ($this->paths->isInstalled()) {
            throw new RuntimeException('The application is already installed.');
        }

        $databaseInput = $this->validator->database($databaseInput);
        $application = $this->validator->application($applicationInput);
        if (!array_key_exists('password_confirmation', $administratorInput)) {
            // HR: Web-korak je potvrdu već provjerio i u session sprema samo jednu kopiju tajne.
            // EN: The web step already checked confirmation and keeps only one secret copy in the session.
            $administratorInput['password_confirmation'] = $administratorInput['password'] ?? '';
        }

        $administrator = $this->validator->administrator($administratorInput);
        $driver = $databaseInput['driver'];
        if (!$this->requirements->passes($driver)) {
            throw new RuntimeException('One or more installation requirements are not satisfied.');
        }

        $this->databaseTester->test($databaseInput);
        $this->configWriter->write($databaseInput, $application);
        [$config, $database] = $this->runtime();
        $appliedMigrations = $this->migrate($database);
        $this->prepareThemeStorage();
        $themeId = $this->importTheme($config);
        $administratorId = $this->createAdministrator(
            $database,
            $config,
            $administrator,
            $application['timezone'],
        );
        $workspaceSlug = $this->importUserGuides($administratorId);
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
     * HR: Gradi minimalni runtime potreban migracijama i servisima modula.
     * EN: Builds the minimal runtime required by migrations and module services.
     *
     * @return array{Config,Database}
     */
    private function runtime(): array
    {
        $helper = new Helper();
        $config = new Config($helper, [], $this->paths->appRoot());
        $configDirectory = $this->paths->configDirectory();
        if ($configDirectory === '') {
            throw new RuntimeException('The installation configuration directory is invalid.');
        }

        $config->loadLayeredDirectories([$configDirectory]);

        return [$config, new Database($config, $helper)];
    }

    /**
     * HR: Učitava i stvarno izvršava sve aplikacijske migracije preko ORM migratora.
     * EN: Loads and actually executes every application migration through the ORM migrator.
     *
     * @return list<string>
     */
    private function migrate(Database $database): array
    {
        $files = glob($this->paths->migrationsDirectory() . DIRECTORY_SEPARATOR . '*.php');
        if (!is_array($files) || $files === []) {
            throw new RuntimeException('No application migrations were found.');
        }

        sort($files, SORT_STRING);
        $migrations = [];
        foreach ($files as $file) {
            $migration = require $file;
            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException('An application migration has an invalid contract.');
            }

            $migrations[pathinfo($file, PATHINFO_FILENAME)] = $migration;
        }

        return array_values($database->migrator()->migrate($migrations));
    }

    /**
     * HR: Uvozi checksummed paket aktualne teme i aktivira automatski light/dark način.
     * EN: Imports the checksummed current-theme package and enables automatic light/dark mode.
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
     * HR: Prije importa uklanja konfiguracijske početne teme i ostavlja jednu
     * privremenu temu kako repozitorij ne bi aktivirao svoje fallback primjere.
     * EN: Before import, removes configured starter themes and leaves one temporary
     * theme so the repository does not activate its fallback examples.
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

    /** HR: Uklanja datoteke svih tema osim aktivne Simbioza teme. EN: Removes files for every theme except active Simbioza. */
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
     * HR: Uvozi javno područje s dvojezičnim uputama i sve izvorne autore
     * preslikava na upravo kreiranog administratora.
     * EN: Imports the public bilingual guide workspace and maps all source authors
     * to the newly created administrator.
     */
    private function importUserGuides(int $administratorId): string
    {
        try {
            $runtimeConfig = $this->prepareImportConfig();
            $application = new App(
                [$this->paths->configDirectory(), $runtimeConfig],
                $this->paths->appRoot(),
            );
            // HR: Backup i njegovi poslovni provideri odgođeni su moduli. U
            //     instalacijskom CLI kontekstu javni lifecycle ih učitava i
            //     registrira prije dohvaćanja BackupManagera.
            // EN: Backup and its business providers are deferred modules. In
            //     the installer CLI context, the public lifecycle loads and
            //     registers them before BackupManager is resolved.
            $application->loadCommands();
            $manager = $application->getContainer()->get(BackupManager::class);
            if (!$manager instanceof BackupManager) {
                throw new RuntimeException('The Backup manager is unavailable during installation.');
            }

            $context = new BackupImportContext(
                new BackupScope(BackupScope::WORKSPACE, self::USER_GUIDES_WORKSPACE_SLUG),
                BackupImportContext::CONFLICT_COPY,
                [],
                [],
                [
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

            return self::USER_GUIDES_WORKSPACE_SLUG;
        } finally {
            $this->removeImportConfig();
        }
    }

    /**
     * HR: Gradi uski config sloj bez web bootstrapa i s temp-root backup putanjama.
     * EN: Builds a narrow no-web-bootstrap config layer with temp-root backup paths.
     * @return non-empty-string
     */
    private function prepareImportConfig(): string
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
        $files = [
            'bootstrap.php' => "<?php\n\ndeclare(strict_types=1);\n\nreturn [];\n",
            'backup.php' => "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($backup, true) . ";\n",
        ];
        foreach ($files as $name => $contents) {
            if (file_put_contents($directory . '/' . $name, $contents, LOCK_EX) === false) {
                throw new RuntimeException('The installation import configuration could not be written.');
            }
        }

        return $directory;
    }

    /** HR: Uklanja isključivo privremeni config sloj ovog instalera. EN: Removes only this installer's temporary config layer. */
    private function removeImportConfig(): void
    {
        $directory = $this->paths->importConfigDirectory();
        foreach (['bootstrap.php', 'backup.php'] as $file) {
            $path = $directory . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($directory)) {
            rmdir($directory);
        }
    }

    /**
     * HR: Transakcijski izrađuje jedini prvi administratorski račun.
     * EN: Transactionally creates the sole initial administrator account.
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
     * HR: Atomski aktivira trajni lock nakon uklanjanja svake preostale kopije tokena.
     * EN: Atomically activates the permanent lock after removing any remaining token copy.
     *
     * @param array{name:string,primary_locale:string,supported_locales:list<string>,timezone:string} $application
     */
    private function writeLock(string $driver, array $application, string $themeId): void
    {
        try {
            $contents = json_encode([
                'installed_at' => gmdate(DATE_ATOM),
                'database_driver' => $driver,
                'primary_locale' => $application['primary_locale'],
                'supported_locales' => $application['supported_locales'],
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

            if (!chmod($temporaryPath, 0600)) {
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
