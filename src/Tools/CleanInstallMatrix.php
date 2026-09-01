<?php

/**
 * HR: Provjerava clean-room instalacije HeartPhrame aplikacije s minimalnim
 *     skupovima modula. Svaki slučaj koristi novu privremenu aplikaciju,
 *     udaljene VCS repozitorije i praznu bazu podataka.
 *
 * EN: Verifies clean-room HeartPhrame application installations with minimal
 *     module sets. Every case uses a new temporary application, remote VCS
 *     repositories, and an empty database.
 *
 * Usage / Uporaba:
 *   php scripts/verify_clean_install_matrix.php
 *   php scripts/verify_clean_install_matrix.php --case=theme
 *   php scripts/verify_clean_install_matrix.php --case=all --database=pgsql
 *   php scripts/verify_clean_install_matrix.php --case=all --local
 *   php scripts/verify_clean_install_matrix.php --case=theme-menu --keep
 */

declare(strict_types=1);

namespace HFClean\Tools;

use App\Tools\MatrixCommandResult;
use RuntimeException;
use Throwable;

const MATRIX_MODULE_ORDER = [
    'aaieduhr/heartphrame-module-orm',
    'aaieduhr/heartphrame-module-menu',
    'aaieduhr/heartphrame-module-theme',
    'aaieduhr/heartphrame-module-auth',
    'aaieduhr/heartphrame-module-audit',
    'aaieduhr/heartphrame-module-email',
    'aaieduhr/heartphrame-module-notification',
    'aaieduhr/heartphrame-module-editor-html',
    'aaieduhr/heartphrame-module-task',
    'aaieduhr/heartphrame-module-comment',
    'aaieduhr/simbioza-module-workspace',
    'aaieduhr/simbioza-module-workspace-search',
    'aaieduhr/heartphrame-module-calendar',
    'aaieduhr/heartphrame-module-api',
    'aaieduhr/simbioza-module-user',
    'aaieduhr/simbioza-module-confluence-import',
    'aaieduhr/heartphrame-module-backup',
];

const MATRIX_CASES = [
    'framework' => [],
    'theme' => ['aaieduhr/heartphrame-module-theme'],
    'menu' => ['aaieduhr/heartphrame-module-menu'],
    'theme-menu' => [
        'aaieduhr/heartphrame-module-theme',
        'aaieduhr/heartphrame-module-menu',
    ],
    'orm' => ['aaieduhr/heartphrame-module-orm'],
    'auth' => ['aaieduhr/heartphrame-module-auth'],
    'audit' => ['aaieduhr/heartphrame-module-audit'],
    'calendar' => ['aaieduhr/heartphrame-module-calendar'],
    'editor-html' => ['aaieduhr/heartphrame-module-editor-html'],
    'email' => ['aaieduhr/heartphrame-module-email'],
    'notification' => ['aaieduhr/heartphrame-module-notification'],
    'workspace' => ['aaieduhr/simbioza-module-workspace'],
    'workspace-search' => ['aaieduhr/simbioza-module-workspace-search'],
    'task' => ['aaieduhr/heartphrame-module-task'],
    'comment' => ['aaieduhr/heartphrame-module-comment'],
    'api' => ['aaieduhr/heartphrame-module-api'],
    'simbioza-user' => ['aaieduhr/simbioza-module-user'],
    'simbioza-confluence-import' => ['aaieduhr/simbioza-module-confluence-import'],
    'backup' => ['aaieduhr/heartphrame-module-backup'],
    'all' => MATRIX_MODULE_ORDER,
];

const MATRIX_MIGRATION_COMMANDS = [
    'aaieduhr/heartphrame-module-auth' => 'auth:install-migration',
    'aaieduhr/heartphrame-module-audit' => 'audit:install-migration',
    'aaieduhr/heartphrame-module-calendar' => 'calendar:install-migration',
    'aaieduhr/heartphrame-module-editor-html' => 'editor-html:install-migration',
    'aaieduhr/heartphrame-module-email' => 'email:install-migration',
    'aaieduhr/heartphrame-module-notification' => 'notification:install-migration',
    'aaieduhr/simbioza-module-workspace' => 'workspace:install-migration',
    'aaieduhr/simbioza-module-workspace-search' => 'workspace-search:install-migration',
    'aaieduhr/heartphrame-module-task' => 'task:install-migration',
    'aaieduhr/heartphrame-module-comment' => 'comment:install-migration',
    'aaieduhr/heartphrame-module-api' => 'api:install-migration',
    'aaieduhr/simbioza-module-user' => 'simbioza-user:install-migration',
    'aaieduhr/simbioza-module-confluence-import' => 'simbioza-confluence-import:install-migration',
    'aaieduhr/heartphrame-module-backup' => 'backup:install-migration',
];

const MATRIX_LOCAL_PACKAGE_DIRECTORIES = [
    'aaieduhr/heartphrame-module-api' => 'heartphrame-module-api',
    'aaieduhr/heartphrame-module-auth' => 'heartphrame-module-auth',
    'aaieduhr/heartphrame-module-audit' => 'heartphrame-module-audit',
    'aaieduhr/heartphrame-module-calendar' => 'heartphrame-module-calendar',
    'aaieduhr/heartphrame-module-comment' => 'heartphrame-module-comment',
    'aaieduhr/heartphrame-module-editor-html' => 'heartphrame-module-editor-html',
    'aaieduhr/heartphrame-module-email' => 'heartphrame-module-email',
    'aaieduhr/heartphrame-module-menu' => 'heartphrame-module-menu',
    'aaieduhr/heartphrame-module-notification' => 'heartphrame-module-notification',
    'aaieduhr/heartphrame-module-orm' => 'heartphrame-module-orm',
    'aaieduhr/heartphrame-module-task' => 'heartphrame-module-task',
    'aaieduhr/heartphrame-module-theme' => 'heartphrame-module-theme',
    'aaieduhr/simbioza-module-workspace' => 'simbioza-module-workspace',
    'aaieduhr/simbioza-module-workspace-search' => 'simbioza-module-workspace-search',
    'aaieduhr/heartphrame-module-backup' => 'heartphrame-module-backup',
    'aaieduhr/simbioza-module-user' => 'simbioza-module-user',
    'aaieduhr/simbioza-module-confluence-import' => 'simbioza-module-confluence-import',
];

/**
 * HR: Pokreće proces bez posredovanja ljuske i redovito prosljeđuje izlaz.
 * EN: Runs a process without a shell intermediary and streams output regularly.
 *
 * @param list<string> $command
 * @param array<string, string> $environment
 */
function runMatrixCommand(array $command, string $workingDirectory, array $environment = []): MatrixCommandResult
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $inheritedEnvironment = getenv();
    $processEnvironment = array_merge(
        is_array($inheritedEnvironment) ? array_filter($inheritedEnvironment, is_string(...)) : [],
        $environment,
    );
    $startedAt = microtime(true);
    $process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory, $processEnvironment);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start command: ' . implode(' ', $command));
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = '';
    while (true) {
        $status = proc_get_status($process);
        foreach ([1, 2] as $pipeIndex) {
            $chunk = stream_get_contents($pipes[$pipeIndex]);
            if (is_string($chunk) && $chunk !== '') {
                $output .= $chunk;
            }
        }

        if (!$status['running']) {
            break;
        }

        usleep(100_000);
    }

    foreach ([1, 2] as $pipeIndex) {
        $chunk = stream_get_contents($pipes[$pipeIndex]);
        if (is_string($chunk) && $chunk !== '') {
            $output .= $chunk;
        }

        fclose($pipes[$pipeIndex]);
    }

    $exitCode = proc_close($process);
    if ($exitCode === -1) {
        $exitCode = $status['exitcode'];
    }

    return new MatrixCommandResult($exitCode, $output, microtime(true) - $startedAt);
}

/**
 * HR: Rekurzivno kopira aplikacijske datoteke, ali nikada lokalne artefakte.
 * EN: Recursively copies application files while excluding local artifacts.
 */
function copyMatrixPath(string $source, string $destination): void
{
    if (is_link($source)) {
        throw new RuntimeException('Symbolic links are not accepted in clean-room source: ' . $source);
    }

    if (is_file($source)) {
        $parent = dirname($destination);
        if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
            throw new RuntimeException('Unable to create directory: ' . $parent);
        }

        if (!copy($source, $destination)) {
            throw new RuntimeException('Unable to copy file: ' . $source);
        }

        return;
    }

    if (!is_dir($source)) {
        throw new RuntimeException('Clean-room source does not exist: ' . $source);
    }

    if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
        throw new RuntimeException('Unable to create directory: ' . $destination);
    }

    $items = scandir($source);
    if (!is_array($items)) {
        throw new RuntimeException('Unable to read source directory: ' . $source);
    }

    foreach ($items as $item) {
        if ($item === '.') {
            continue;
        }

        if ($item === '..') {
            continue;
        }

        if ($item === '.DS_Store') {
            continue;
        }

        copyMatrixPath($source . '/' . $item, $destination . '/' . $item);
    }
}

/**
 * HR: Uklanja isključivo privremeni direktorij koji je izradio ovaj alat.
 * EN: Removes only a temporary directory created by this verifier.
 */
function removeMatrixDirectory(string $directory, string $temporaryRoot): void
{
    $realTemporaryRoot = realpath($temporaryRoot);
    $realDirectory = realpath($directory);
    if (
        !is_string($realTemporaryRoot)
        || !is_string($realDirectory)
        || $realDirectory === $realTemporaryRoot
        || !str_starts_with($realDirectory . '/', $realTemporaryRoot . '/')
    ) {
        throw new RuntimeException('Refusing to remove an unsafe matrix directory: ' . $directory);
    }

    $items = scandir($realDirectory);
    if (!is_array($items)) {
        throw new RuntimeException('Unable to read matrix directory: ' . $realDirectory);
    }

    foreach ($items as $item) {
        if ($item === '.') {
            continue;
        }

        if ($item === '..') {
            continue;
        }

        $path = $realDirectory . '/' . $item;
        if (is_dir($path) && !is_link($path)) {
            removeMatrixDirectory($path, $realTemporaryRoot);
            continue;
        }

        if (!unlink($path)) {
            throw new RuntimeException('Unable to remove file: ' . $path);
        }
    }

    if (!rmdir($realDirectory)) {
        throw new RuntimeException('Unable to remove matrix directory: ' . $realDirectory);
    }
}

/**
 * HR: Zapisuje PHP konfiguracijsku datoteku s tipiziranim zaglavljem.
 * EN: Writes a PHP configuration file with a typed header.
 *
 * @param array<string, mixed> $configuration
 */
function writeMatrixPhpConfig(string $path, array $configuration): void
{
    $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn "
    . var_export($configuration, true)
    . ";\n";
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Unable to write matrix configuration: ' . $path);
    }
}

/**
 * HR: Označava izravno konfiguriranu testnu aplikaciju instaliranom prije HTTP provjere.
 *     Matrica namjerno pokreće migracijske CLI naredbe umjesto web-čarobnjaka.
 * EN: Marks the directly configured test application as installed before its HTTP check.
 *     The matrix intentionally runs migration CLI commands instead of the web wizard.
 */
function writeMatrixInstallationLock(string $projectDirectory, string $database): void
{
    $dataDirectory = $projectDirectory . '/data';
    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0775, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException('Unable to create matrix data directory: ' . $dataDirectory);
    }

    $contents = json_encode([
        'installed_at' => gmdate(DATE_ATOM),
        'database_driver' => $database,
        'primary_locale' => 'hr',
        'supported_locales' => ['hr', 'en'],
        'theme_id' => '',
        'source' => 'clean-install-matrix',
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";
    $temporaryPath = tempnam($dataDirectory, '.matrix-installation-lock-');
    if (!is_string($temporaryPath)) {
        throw new RuntimeException('Unable to prepare matrix installation lock.');
    }

    try {
        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write matrix installation lock.');
        }

        if (!chmod($temporaryPath, 0600)) {
            throw new RuntimeException('Unable to secure matrix installation lock.');
        }

        if (!rename($temporaryPath, $dataDirectory . '/installation.lock')) {
            throw new RuntimeException('Unable to activate matrix installation lock.');
        }
    } finally {
        if (is_file($temporaryPath)) {
            unlink($temporaryPath);
        }
    }
}

/**
 * HR: Provjerava da konfiguracijsko polje ima isključivo tekstualne ključeve.
 * EN: Verifies that a configuration array contains string keys only.
 *
 * @param array<mixed, mixed> $value
 * @return array<string, mixed>
 */
function matrixStringKeyedArray(array $value, string $label): array
{
    $normalized = [];
    foreach ($value as $key => $item) {
        if (!is_string($key)) {
            throw new RuntimeException($label . ' contains a non-string key.');
        }

        $normalized[$key] = $item;
    }

    return $normalized;
}

/**
 * HR: Izrađuje konfiguraciju potpuno prazne baze odabrane za ovaj test.
 *     Pristupni podaci za mrežne baze čitaju se samo iz okoliša i nikada
 *     se ne zapisuju u izvještaj matrice.
 *
 * EN: Builds the empty database configuration selected for this test. Network
 *     database credentials are read only from the environment and are never
 *     written to the matrix report.
 *
 * Required environment / Obavezni okoliš (mysql, mariadb, pgsql):
 *   HPH_MATRIX_DB_NAME, HPH_MATRIX_DB_USER
 * Optional environment / Neobavezni okoliš:
 *   HPH_MATRIX_DB_HOST, HPH_MATRIX_DB_PORT, HPH_MATRIX_DB_PASSWORD
 *
 * @return array<string, mixed>
 */
function matrixDatabaseConfiguration(
    string $database,
    string $projectDirectory,
): array {
    if ($database === 'sqlite') {
        return [
            'connections' => [
                'default' => [
                    'driver' => 'sqlite',
                    'database' => $projectDirectory . '/data/matrix.sqlite',
                ],
            ],
        ];
    }

    $name = trim((string)getenv('HPH_MATRIX_DB_NAME'));
    $username = trim((string)getenv('HPH_MATRIX_DB_USER'));
    if ($name === '' || $username === '') {
        throw new RuntimeException(
            'HPH_MATRIX_DB_NAME and HPH_MATRIX_DB_USER are required for ' . $database . '.',
        );
    }

    $defaultPort = $database === 'pgsql' ? 5432 : 3306;
    $configuredPort = trim((string)getenv('HPH_MATRIX_DB_PORT'));
    $port = $configuredPort !== '' ? (int)$configuredPort : $defaultPort;
    if ($port < 1 || $port > 65535) {
        throw new RuntimeException('HPH_MATRIX_DB_PORT must be between 1 and 65535.');
    }

    return [
        'connections' => [
            'default' => [
                'driver' => $database,
                'host' => trim((string)getenv('HPH_MATRIX_DB_HOST')) ?: '127.0.0.1',
                'port' => $port,
                'database' => $name,
                'username' => $username,
                'password' => (string)getenv('HPH_MATRIX_DB_PASSWORD'),
                'charset' => $database === 'pgsql' ? 'UTF8' : 'utf8mb4',
                'options' => [],
            ],
        ],
    ];
}

/**
 * HR: U lokalnom načinu dopuštene module učitava isključivo iz lokalnih path
 *     repozitorija. Time Composer ne može tiho posegnuti za zastarjelim ili još
 *     neobjavljenim VCS repozitorijem. Framework ostaje udaljeni izvor, a demo
 *     namjerno nije na popisu lokalnih izvora.
 *
 * EN: In local mode, loads allowed modules exclusively from local path
 *     repositories. Composer therefore cannot silently fall back to a stale or
 *     not-yet-published VCS repository. Framework remains remote, while demo is
 *     intentionally absent from the local source list.
 *
 * @param array<string, mixed> $rootComposer
 * @return array<string, mixed>
 */
function withLocalMatrixRepositories(array $rootComposer, string $workspaceRoot): array
{
    $localRepositories = [];
    foreach (MATRIX_LOCAL_PACKAGE_DIRECTORIES as $package => $directory) {
        $path = $workspaceRoot . '/' . $directory;
        if (!is_file($path . '/composer.json')) {
            throw new RuntimeException('Missing local matrix package: ' . $package);
        }

        $localRepositories[] = [
            'type' => 'path',
            'url' => $path,
            'canonical' => true,
            'options' => [
                'symlink' => true,
            ],
        ];
    }

    $configuredRepositories = $rootComposer['repositories'] ?? [];
    if (!is_array($configuredRepositories)) {
        throw new RuntimeException('Root Composer repositories must be an array.');
    }

    $remoteNonModuleRepositories = array_values(array_filter(
        $configuredRepositories,
        static function (mixed $repository): bool {
            if (!is_array($repository)) {
                return true;
            }

            $type = is_string($repository['type'] ?? null) ? $repository['type'] : '';
            $url = is_string($repository['url'] ?? null) ? $repository['url'] : '';
            return !in_array($type, ['vcs', 'path'], true)
                || !str_contains(strtolower($url), 'heartphrame-module-');
        },
    ));
    $rootComposer['repositories'] = array_merge($localRepositories, $remoteNonModuleRepositories);

    return $rootComposer;
}

/**
 * HR: Vraća module koje je Composer stvarno instalirao u privremenom projektu.
 * EN: Returns modules that Composer actually installed in the temporary project.
 *
 * @return list<string>
 */
function installedMatrixModules(string $projectDirectory): array
{
    $installedPath = $projectDirectory . '/vendor/composer/installed.php';
    $installed = require $installedPath;
    if (!is_array($installed)) {
        throw new RuntimeException('Composer installed metadata is not an array.');
    }

    $versionsValue = $installed['versions'] ?? null;
    $versions = is_array($versionsValue) ? $versionsValue : [];
    $modules = [];
    foreach (MATRIX_MODULE_ORDER as $package) {
        if (array_key_exists($package, $versions)) {
            $modules[] = $package;
        }
    }

    return $modules;
}

/**
 * HR: Priprema potpuno novu aplikaciju i vraća podatke o jednoj provjeri.
 * EN: Prepares a brand-new application and returns one verification record.
 *
 * @param list<string> $requestedModules
 * @param array<string, mixed> $rootComposer
 * @return array<string, mixed>
 */
function verifyMatrixCase(
    string $caseName,
    array $requestedModules,
    array $rootComposer,
    string $sourceRoot,
    string $temporaryRoot,
    string $database,
    bool $keep,
    bool $local,
): array {
    $projectDirectory = $temporaryRoot . '/' . $caseName . '-' . bin2hex(random_bytes(4));
    if (!mkdir($projectDirectory, 0775, true) && !is_dir($projectDirectory)) {
        throw new RuntimeException('Unable to create matrix project: ' . $projectDirectory);
    }

    $startedAt = microtime(true);
    $steps = [];
    $succeeded = false;

    try {
        foreach (['config', 'database', 'lang', 'public', 'resources', 'src', 'views'] as $path) {
            copyMatrixPath($sourceRoot . '/' . $path, $projectDirectory . '/' . $path);
        }

        // HR: Zadane biblioteke tema dio su aplikacije, a cache i logovi nisu.
        // EN: Preset theme libraries belong to the application; caches and logs do not.
        copyMatrixPath($sourceRoot . '/data/themes', $projectDirectory . '/data/themes');

        foreach (['LICENSE'] as $file) {
            copyMatrixPath($sourceRoot . '/' . $file, $projectDirectory . '/' . $file);
        }

        foreach (['database.php', 'env.php'] as $localConfig) {
            $path = $projectDirectory . '/config/' . $localConfig;
            if (is_file($path) && !unlink($path)) {
                throw new RuntimeException('Unable to replace local configuration: ' . $path);
            }
        }

        $migrationDirectory = $projectDirectory . '/database/migrations';
        foreach (glob($migrationDirectory . '/*.php') ?: [] as $migrationFile) {
            if (!unlink($migrationFile)) {
                throw new RuntimeException('Unable to reset migration: ' . $migrationFile);
            }
        }

        $composer = $rootComposer;
        $composer['name'] = 'aaieduhr/heartphrame-clean-matrix-' . $caseName;
        $composer['description'] = 'Ephemeral clean-room HeartPhrame matrix case.';
        $composer['require'] = [
            'php' => '>=8.2',
            'aaieduhr/heartphrame-framework' => '^0.0.24',
        ];
        foreach ($requestedModules as $package) {
            $composer['require'][$package] = $local ? 'dev-main' : '^0.1.0';
        }

        unset($composer['require-dev'], $composer['autoload-dev'], $composer['scripts']);
        $composerConfig = $composer['config'] ?? [];
        if (!is_array($composerConfig)) {
            $composerConfig = [];
        }

        $composerConfig['cache-dir'] = $sourceRoot . '/build/clean-install-composer-cache';
        $composer['config'] = $composerConfig;
        $composerJson = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";
        if (file_put_contents($projectDirectory . '/composer.json', $composerJson) === false) {
            throw new RuntimeException('Unable to write clean-room composer.json.');
        }

        $composerResult = runMatrixCommand(
            [
                'composer',
                'update',
                '--with-all-dependencies',
                '--no-dev',
                '--no-interaction',
                '--no-progress',
                '--prefer-dist',
                '--optimize-autoloader',
            ],
            $projectDirectory,
        );
        $steps['composer_update'] = [
            'exit_code' => $composerResult->exitCode,
            'duration_seconds' => round($composerResult->durationSeconds, 3),
        ];
        if ($composerResult->exitCode !== 0) {
            throw new RuntimeException("Composer update failed:\n" . $composerResult->output);
        }

        $installedModules = installedMatrixModules($projectDirectory);
        $appConfigValue = require $projectDirectory . '/config/app.php';
        if (!is_array($appConfigValue)) {
            throw new RuntimeException('Application configuration is not an array.');
        }

        $appConfig = matrixStringKeyedArray($appConfigValue, 'Application configuration');
        $appConfig['name'] = 'Simbioza matrix: ' . $caseName;
        $appConfig['cache_dir'] = $projectDirectory . '/data/cache';
        $logsConfig = $appConfig['logs'] ?? [];
        $logsConfig = is_array($logsConfig) ? $logsConfig : [];
        $logsConfig['dir'] = $projectDirectory . '/data/logs';
        $appConfig['logs'] = $logsConfig;
        $modulesConfig = $appConfig['modules'] ?? [];
        $modulesConfig = is_array($modulesConfig) ? $modulesConfig : [];
        $modulesConfig['enabled'] = $installedModules;
        $appConfig['modules'] = $modulesConfig;
        writeMatrixPhpConfig($projectDirectory . '/config/app.php', $appConfig);
        writeMatrixPhpConfig(
            $projectDirectory . '/config/database.php',
            matrixDatabaseConfiguration($database, $projectDirectory),
        );
        writeMatrixPhpConfig($projectDirectory . '/config/env.php', [
            'salt' => 'matrix-salt-' . $caseName,
            'log_level' => 'info',
            'environment' => 'development',
            'debug' => true,
            'encryption_key' => base64_encode(hash('sha256', 'matrix-key-' . $caseName, true)),
            'trusted_proxies' => ['127.0.0.1'],
        ]);
        foreach (['data/cache', 'data/logs'] as $directory) {
            $path = $projectDirectory . '/' . $directory;
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                throw new RuntimeException('Unable to create runtime directory: ' . $path);
            }
        }

        $cliResult = runMatrixCommand(['vendor/bin/hph'], $projectDirectory);
        $steps['cli_boot'] = [
            'exit_code' => $cliResult->exitCode,
            'duration_seconds' => round($cliResult->durationSeconds, 3),
        ];
        if ($cliResult->exitCode !== 0 || !str_contains($cliResult->output, 'Available commands:')) {
            throw new RuntimeException("CLI boot failed:\n" . $cliResult->output);
        }

        $migrationCommands = [];
        foreach ($installedModules as $package) {
            $commandName = MATRIX_MIGRATION_COMMANDS[$package] ?? null;
            if (!is_string($commandName)) {
                continue;
            }

            $migrationResult = runMatrixCommand(['vendor/bin/hph', $commandName], $projectDirectory);
            $migrationCommands[$commandName] = [
                'exit_code' => $migrationResult->exitCode,
                'duration_seconds' => round($migrationResult->durationSeconds, 3),
            ];
            if ($migrationResult->exitCode !== 0) {
                throw new RuntimeException($commandName . " failed:\n" . $migrationResult->output);
            }
        }

        $steps['install_migrations'] = $migrationCommands;

        if (in_array('aaieduhr/heartphrame-module-orm', $installedModules, true)) {
            $migrateResult = runMatrixCommand(['vendor/bin/hph', 'orm-migrate:up'], $projectDirectory);
            $steps['migrate_up'] = [
                'exit_code' => $migrateResult->exitCode,
                'duration_seconds' => round($migrateResult->durationSeconds, 3),
            ];
            if ($migrateResult->exitCode !== 0) {
                throw new RuntimeException("Migration failed:\n" . $migrateResult->output);
            }
        }

        writeMatrixInstallationLock($projectDirectory, $database);

        $requestEnvironment = [
            'HPH_APP_PATH' => $projectDirectory,
            'HPH_CONFIG_PATH' => $projectDirectory . '/config',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '/index.php',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'HTTP_HOST' => 'localhost',
        ];
        $httpResult = runMatrixCommand(['php', 'public/index.php'], $projectDirectory, $requestEnvironment);
        $steps['http_home'] = [
            'exit_code' => $httpResult->exitCode,
            'duration_seconds' => round($httpResult->durationSeconds, 3),
        ];
        if (
            $httpResult->exitCode !== 0
            || !str_contains($httpResult->output, '<html lang="hr">')
            || !str_contains(
                $httpResult->output,
                'Zajednički prostor za znanje, suradnju i sadržaj koji raste s vašom zajednicom.',
            )
        ) {
            throw new RuntimeException("HTTP homepage failed:\n" . $httpResult->output);
        }

        $succeeded = true;
        return [
            'case' => $caseName,
            'database' => $database,
            'status' => 'success',
            'requested_modules' => $requestedModules,
            'installed_modules' => $installedModules,
            'migration_count' => count(glob($migrationDirectory . '/*.php') ?: []),
            'steps' => $steps,
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
            'project_directory' => $keep ? $projectDirectory : null,
        ];
    } catch (Throwable $throwable) {
        return [
            'case' => $caseName,
            'database' => $database,
            'status' => 'failure',
            'requested_modules' => $requestedModules,
            'steps' => $steps,
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
            'project_directory' => $projectDirectory,
            'error' => $throwable->getMessage(),
        ];
    } finally {
        if (!$keep && $succeeded) {
            removeMatrixDirectory($projectDirectory, $temporaryRoot);
        }
    }
}

/**
 * HR: Čita jednostavne CLI opcije alata.
 * EN: Reads the verifier's simple CLI options.
 *
 * @return array{case: ?string, database: string, keep: bool, local: bool}
 */
function matrixOptions(): array
{
    $options = getopt('', ['case:', 'database:', 'keep', 'local']);
    $case = is_string($options['case'] ?? null) ? trim($options['case']) : null;
    if ($case === '') {
        $case = null;
    }

    $database = is_string($options['database'] ?? null)
    ? strtolower(trim($options['database']))
    : 'sqlite';
    if (!in_array($database, ['sqlite', 'mysql', 'mariadb', 'pgsql'], true)) {
        throw new RuntimeException('Unsupported matrix database: ' . $database);
    }

    return [
        'case' => $case,
        'database' => $database,
        'keep' => array_key_exists('keep', $options),
        'local' => array_key_exists('local', $options),
    ];
}

/**
 * HR: Izvršava odabrane slučajeve, zapisuje JSON izvještaj i vraća CLI kod.
 * EN: Executes selected cases, writes a JSON report, and returns the CLI code.
 */
function runCleanInstallMatrix(): int
{
    $sourceRoot = dirname(__DIR__, 2);
    $temporaryRoot = sys_get_temp_dir() . '/heartphrame-clean-matrix';
    if (!is_dir($temporaryRoot) && !mkdir($temporaryRoot, 0775, true) && !is_dir($temporaryRoot)) {
        throw new RuntimeException('Unable to create temporary matrix root: ' . $temporaryRoot);
    }

    $options = matrixOptions();
    $cases = MATRIX_CASES;
    if ($options['case'] !== null) {
        if (!array_key_exists($options['case'], $cases)) {
            fwrite(STDERR, 'Unknown matrix case: ' . $options['case'] . PHP_EOL);
            fwrite(STDERR, 'Available cases: ' . implode(', ', array_keys($cases)) . PHP_EOL);
            return 2;
        }

        $cases = [$options['case'] => $cases[$options['case']]];
    }

    $rootComposerValue = json_decode(
        (string)file_get_contents($sourceRoot . '/composer.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    if (!is_array($rootComposerValue)) {
        throw new RuntimeException('Root Composer metadata is not an array.');
    }

    $rootComposer = matrixStringKeyedArray($rootComposerValue, 'Root Composer metadata');
    if ($options['local']) {
        $rootComposer = withLocalMatrixRepositories($rootComposer, dirname($sourceRoot));
    }

    $results = [];
    foreach ($cases as $caseName => $requestedModules) {
        fwrite(STDOUT, sprintf('[START] %s%s', $caseName, PHP_EOL));
        $result = verifyMatrixCase(
            $caseName,
            $requestedModules,
            $rootComposer,
            $sourceRoot,
            $temporaryRoot,
            $options['database'],
            $options['keep'],
            $options['local'],
        );
        $results[] = $result;
        $status = $result['status'] ?? null;
        $duration = $result['duration_seconds'] ?? null;
        if (!is_string($status) || (!is_int($duration) && !is_float($duration))) {
            throw new RuntimeException('Matrix result has an invalid shape.');
        }

        fwrite(
            STDOUT,
            sprintf('[%s] %s (%.3fs)%s', strtoupper($status), $caseName, $duration, PHP_EOL),
        );
        if ($status !== 'success') {
            $error = $result['error'] ?? 'Unknown failure';
            fwrite(STDERR, (is_string($error) ? $error : 'Unknown failure') . PHP_EOL);
        }
    }

    $report = [
        'generated_at' => gmdate(DATE_ATOM),
        'php_version' => PHP_VERSION,
        'database' => $options['database'],
        'source' => $options['local'] ? 'local-candidate' : 'remote-release',
        'results' => $results,
    ];
    $reportDirectory = $sourceRoot . '/build';
    if (!is_dir($reportDirectory) && !mkdir($reportDirectory, 0775, true) && !is_dir($reportDirectory)) {
        throw new RuntimeException('Unable to create report directory: ' . $reportDirectory);
    }

    $reportPath = $reportDirectory . '/clean-install-matrix-' . $options['database'] . '.json';
    file_put_contents(
        $reportPath,
        json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
    );
    $failures = array_filter(
        $results,
        static fn(array $result): bool => ($result['status'] ?? null) !== 'success',
    );
    fwrite(STDOUT, 'Report / Izvještaj: ' . $reportPath . PHP_EOL);
    return $failures === [] ? 0 : 1;
}
