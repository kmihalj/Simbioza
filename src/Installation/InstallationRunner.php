<?php

declare(strict_types=1);

namespace App\Installation;

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthGroupService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserAttributeService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use AaiEduHr\HeartPhrameModuleTheme\ModuleTheme;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
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
     * imports the theme, creates the administrator, and locks installation.
     *
     * @param array<array-key, mixed> $databaseInput
     * @param array<array-key, mixed> $applicationInput
     * @param array<array-key, mixed> $administratorInput
     * @return array{migration_count:int,theme_id:string,administrator_login:string}
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
        $themeId = $this->importTheme($config);
        $this->createAdministrator($database, $config, $administrator, $application['timezone']);
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
    ): void {
        date_default_timezone_set($timezone);
        $attributeService = new AuthUserAttributeService($database, $config);
        $groupService = new AuthGroupService($database);
        $userService = new AuthUserService($database, $attributeService, $groupService);

        $database->transaction(static function () use ($userService, $administrator): void {
            $userService->createInitialAdministrator(
                $administrator['login'],
                $administrator['display_name'],
                $administrator['first_name'],
                $administrator['last_name'],
                $administrator['email'],
                $administrator['password'],
            );
        });
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
