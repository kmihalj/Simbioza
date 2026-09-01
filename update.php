#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Simbioza\Update;

use RuntimeException;
use Throwable;

/**
 * HR: Samostalni updater za release instalacije bez trajnog Git direktorija.
 *
 * EN: Standalone updater for release installations without a persistent Git directory.
 */
final class ApplicationUpdateCommand
{
    private const REPOSITORY = 'https://github.com/kmihalj/Simbioza.git';

    /** @var list<string> */
    private const SOURCE_SYNC_EXCLUDES = [
        '.git/',
        'vendor/',
        'data/',
        'composer.lock',
        'composer.local.json',
        'composer.local.lock',
        'config/database.php',
        'config/env.php',
        'config/installation.php',
        'config/email.php',
        'resources/config/menu/',
        'resources/config/theme/',
    ];

    /** @var list<string> */
    private const BACKUP_EXCLUDES = [
        './.git',
        './vendor',
        './data',
        './build',
        './node_modules',
        './output',
        './.playwright-cli',
        './playwright-report',
        './test-results',
    ];

    /** @var array<string, array{hr:string,en:string}> */
    private const MESSAGES = [
        'usage' => [
            'hr' => "Upotreba: php update.php [--check] [--tag=0.1.9] [--lang=hr|en]\n"
                . "  bez opcija    ažurira Simbiozu i module na zadnja kompatibilna izdanja\n"
                . "  --check      samo prikazuje trenutačni i zadnji dostupni tag\n"
                . "  --tag=TAG    ažurira na određeni stabilni Simbioza tag\n",
            'en' => "Usage: php update.php [--check] [--tag=0.1.9] [--lang=hr|en]\n"
                . "  no options   updates Simbioza and modules to latest compatible releases\n"
                . "  --check      only shows the current and latest available tag\n"
                . "  --tag=TAG    updates to a specific stable Simbioza tag\n",
        ],
        'cli_only' => [
            'hr' => 'Updater se smije pokrenuti samo iz naredbenog retka.',
            'en' => 'The updater may only be run from the command line.',
        ],
        'wrong_directory' => [
            'hr' => 'update.php mora se nalaziti i pokrenuti u korijenu Simbioza instalacije.',
            'en' => 'update.php must be located and run in the Simbioza installation root.',
        ],
        'already_running' => [
            'hr' => 'Drugo ažuriranje već je pokrenuto.',
            'en' => 'Another update is already running.',
        ],
        'write_access' => [
            'hr' => 'Korijen aplikacije nije zapisiv. Pokrenite: sudo php update.php',
            'en' => 'The application root is not writable. Run: sudo php update.php',
        ],
        'current_latest' => [
            'hr' => 'Trenutačni tag: %s; zadnji dostupni tag: %s.',
            'en' => 'Current tag: %s; latest available tag: %s.',
        ],
        'unknown' => [
            'hr' => 'nepoznat',
            'en' => 'unknown',
        ],
        'target' => [
            'hr' => 'Ciljno izdanje: %s.',
            'en' => 'Target release: %s.',
        ],
        'backup' => [
            'hr' => 'Sigurnosna kopija aplikacijskog koda: %s',
            'en' => 'Application code backup: %s',
        ],
        'download' => [
            'hr' => 'Dohvaćam označeno izdanje Simbioze...',
            'en' => 'Fetching the tagged Simbioza release...',
        ],
        'sync' => [
            'hr' => 'Ažuriram aplikacijske datoteke uz očuvanje privatne konfiguracije i podataka...',
            'en' => 'Updating application files while preserving private configuration and data...',
        ],
        'composer' => [
            'hr' => 'Ažuriram Composer module na zadnje kompatibilne tagove...',
            'en' => 'Updating Composer modules to their latest compatible tags...',
        ],
        'platform' => [
            'hr' => 'Provjeravam PHP i platformske preduvjete...',
            'en' => 'Checking PHP and platform requirements...',
        ],
        'migrate' => [
            'hr' => 'Primjenjujem migracije baze...',
            'en' => 'Applying database migrations...',
        ],
        'status' => [
            'hr' => 'Provjeravam da nema migracija na čekanju...',
            'en' => 'Checking that no migrations remain pending...',
        ],
        'cache' => [
            'hr' => 'Čistim aplikacijsku predmemoriju...',
            'en' => 'Clearing the application cache...',
        ],
        'success' => [
            'hr' => 'Ažuriranje je završeno. Simbioza koristi izdanje %s i zadnje kompatibilne module.',
            'en' => 'Update completed. Simbioza now uses release %s and the latest compatible modules.',
        ],
        'rollback' => [
            'hr' => 'Ažuriranje je prekinuto prije migracija; vraćam prethodni kod i Composer pakete...',
            'en' => 'The update stopped before migrations; restoring the previous code and Composer packages...',
        ],
        'rollback_success' => [
            'hr' => 'Prethodno izdanje je vraćeno.',
            'en' => 'The previous release has been restored.',
        ],
        'migration_failure' => [
            'hr' => 'Migracije su već pokrenute pa automatski rollback koda nije siguran. Održavanje ostaje uključeno; ispravite uzrok i ponovno pokrenite updater. Backup: %s',
            'en' => 'Migrations have already started, so an automatic code rollback is unsafe. Maintenance remains enabled; fix the cause and rerun the updater. Backup: %s',
        ],
        'failure' => [
            'hr' => 'Ažuriranje nije uspjelo: %s',
            'en' => 'Update failed: %s',
        ],
    ];

    private string $locale;

    /** @var resource|null */
    private $lockHandle = null;

    private bool $maintenanceEnabled = false;

    private bool $migrationStarted = false;

    private ?string $backupPath = null;

    private ?string $temporaryDirectory = null;

    /** @param list<string> $arguments */
    public function __construct(
        private readonly string $appRoot,
        private readonly array $arguments,
    ) {
        $this->locale = $this->requestedLocale() ?? $this->installedLocale();
    }

    public function run(): int
    {
        if (PHP_SAPI !== 'cli') {
            $this->error($this->message('cli_only'));
            return 1;
        }

        if ($this->hasOption('--help') || $this->hasOption('-h')) {
            $this->write($this->message('usage'));
            return 0;
        }

        try {
            $this->assertInstallationRoot();
            $git = $this->requireExecutable('git');
            $latestTag = $this->latestStableTag($git);
            $targetTag = $this->requestedTag() ?? $latestTag;
            $currentTag = $this->currentTag();

            $this->write(sprintf(
                $this->message('current_latest'),
                $currentTag ?? $this->message('unknown'),
                $latestTag,
            ));

            if ($this->hasOption('--check')) {
                if ($this->requestedTag() !== null) {
                    $this->write(sprintf($this->message('target'), $targetTag));
                }
                return 0;
            }

            if (!is_writable($this->appRoot)) {
                throw new RuntimeException($this->message('write_access'));
            }

            $this->acquireLock();
            $rsync = $this->requireExecutable('rsync');
            $tar = $this->requireExecutable('tar');
            $composer = $this->requireExecutable('composer');
            $this->write(sprintf($this->message('target'), $targetTag));

            $this->temporaryDirectory = $this->createTemporaryDirectory();
            $sourceDirectory = $this->temporaryDirectory . '/source';
            $this->write($this->message('download'));
            $this->mustRun([
                $git,
                'clone',
                '--quiet',
                '--depth',
                '1',
                '--branch',
                $targetTag,
                '--single-branch',
                self::REPOSITORY,
                $sourceDirectory,
            ]);
            $this->assertReleaseSource($sourceDirectory, $targetTag);

            $this->backupPath = $this->createBackup($tar, $currentTag, $targetTag);
            $this->write(sprintf($this->message('backup'), $this->backupPath));
            $this->enableMaintenance($targetTag);

            $this->write($this->message('sync'));
            $this->syncSource($rsync, $sourceDirectory);

            $this->write($this->message('composer'));
            $this->mustRun([
                $composer,
                'update',
                '--with-all-dependencies',
                '--no-dev',
                '--no-interaction',
                '--no-progress',
                '--prefer-dist',
                '--optimize-autoloader',
            ], $this->appRoot);

            $this->write($this->message('platform'));
            $this->mustRun([$composer, 'check-platform-reqs', '--no-dev'], $this->appRoot);
            $this->mustRun([$composer, 'audit', '--locked', '--no-dev'], $this->appRoot);

            $this->migrationStarted = true;
            $this->write($this->message('migrate'));
            $this->mustRun([
                $this->appRoot . '/vendor/bin/hph',
                'orm-migrate:up',
                '--connection=default',
                '--path=database/migrations',
            ], $this->appRoot);
            $this->write($this->message('status'));
            $this->mustRun([
                $this->appRoot . '/vendor/bin/hph',
                'orm-migrate:status',
                '--connection=default',
                '--path=database/migrations',
            ], $this->appRoot);

            $this->write($this->message('cache'));
            $this->clearCache($this->appRoot . '/data/cache');
            $this->disableMaintenance();
            $this->write(sprintf($this->message('success'), $targetTag));
            return 0;
        } catch (Throwable $throwable) {
            $this->error(sprintf($this->message('failure'), $throwable->getMessage()));

            if ($this->maintenanceEnabled && $this->migrationStarted) {
                $this->error(sprintf(
                    $this->message('migration_failure'),
                    $this->backupPath ?? $this->message('unknown'),
                ));
                return 1;
            }

            if ($this->maintenanceEnabled && $this->backupPath !== null) {
                try {
                    $this->rollback();
                } catch (Throwable $rollbackError) {
                    $this->error(sprintf($this->message('failure'), $rollbackError->getMessage()));
                    return 1;
                }
            }

            $this->disableMaintenance();
            return 1;
        } finally {
            $this->removeTemporaryDirectory();
            $this->releaseLock();
        }
    }

    /**
     * HR: Odabire najveći stabilni semantički tag iz izlaza `git ls-remote`.
     *
     * EN: Selects the greatest stable semantic tag from `git ls-remote` output.
     */
    public static function greatestStableTag(string $remoteOutput): ?string
    {
        $tags = [];
        foreach (preg_split('/\R/', trim($remoteOutput)) ?: [] as $line) {
            if (preg_match('#refs/tags/((?:v)?\d+\.\d+\.\d+)$#', trim($line), $matches) !== 1) {
                continue;
            }
            $tags[$matches[1]] = true;
        }

        $stableTags = array_keys($tags);
        usort(
            $stableTags,
            static fn (string $left, string $right): int => version_compare(
                ltrim($right, 'v'),
                ltrim($left, 'v'),
            ),
        );

        return $stableTags[0] ?? null;
    }

    private function latestStableTag(string $git): string
    {
        [$exitCode, $stdout, $stderr] = $this->runProcess([
            $git,
            'ls-remote',
            '--tags',
            '--refs',
            self::REPOSITORY,
        ], $this->appRoot, true);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'Unable to read release tags.');
        }

        $tag = self::greatestStableTag($stdout);
        if ($tag === null) {
            throw new RuntimeException('No stable Simbioza release tag was found.');
        }

        return $tag;
    }

    private function assertInstallationRoot(): void
    {
        foreach (['composer.json', 'config/app.php', 'public/index.php'] as $requiredFile) {
            if (!is_file($this->appRoot . '/' . $requiredFile)) {
                throw new RuntimeException($this->message('wrong_directory'));
            }
        }
    }

    private function assertReleaseSource(string $sourceDirectory, string $targetTag): void
    {
        $versionFile = $sourceDirectory . '/VERSION';
        $composerFile = $sourceDirectory . '/composer.json';
        if (!is_file($versionFile) || !is_file($composerFile)) {
            throw new RuntimeException('The downloaded release is incomplete.');
        }

        $releaseVersion = trim((string)file_get_contents($versionFile));
        if (ltrim($releaseVersion, 'v') !== ltrim($targetTag, 'v')) {
            throw new RuntimeException('The downloaded release version does not match its tag.');
        }

        $composer = json_decode((string)file_get_contents($composerFile), true);
        if (!is_array($composer) || ($composer['name'] ?? null) !== 'aaieduhr/simbioza') {
            throw new RuntimeException('The downloaded release does not identify as Simbioza.');
        }
    }

    private function acquireLock(): void
    {
        $lockPath = $this->appRoot . '/data/update.lock';
        $handle = fopen($lockPath, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException($this->message('already_running'));
        }
        $this->lockHandle = $handle;
    }

    private function releaseLock(): void
    {
        if (!is_resource($this->lockHandle)) {
            return;
        }
        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    private function createBackup(string $tar, ?string $currentTag, string $targetTag): string
    {
        $backupDirectory = $this->appRoot . '/data/backups/application-updates';
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
            throw new RuntimeException('Unable to create the application update backup directory.');
        }

        $from = preg_replace('/[^A-Za-z0-9._-]/', '-', $currentTag ?? 'unknown') ?: 'unknown';
        $to = preg_replace('/[^A-Za-z0-9._-]/', '-', $targetTag) ?: 'target';
        $backupPath = sprintf(
            '%s/simbioza-%s-before-%s-to-%s.tar.gz',
            $backupDirectory,
            gmdate('Ymd-His'),
            $from,
            $to,
        );

        $command = [$tar, '-czf', $backupPath];
        foreach (self::BACKUP_EXCLUDES as $exclude) {
            $command[] = '--exclude=' . $exclude;
        }
        $command[] = '.';
        $this->mustRun($command, $this->appRoot);
        chmod($backupPath, 0600);

        return $backupPath;
    }

    private function syncSource(string $rsync, string $sourceDirectory): void
    {
        $command = [$rsync, '--archive', '--delete'];
        foreach (self::SOURCE_SYNC_EXCLUDES as $exclude) {
            $command[] = '--exclude=' . $exclude;
        }
        $command[] = rtrim($sourceDirectory, '/') . '/';
        $command[] = rtrim($this->appRoot, '/') . '/';
        $this->mustRun($command);
    }

    private function rollback(): void
    {
        $backupPath = $this->backupPath;
        if ($backupPath === null || !is_file($backupPath)) {
            throw new RuntimeException('The rollback backup is unavailable.');
        }

        $this->write($this->message('rollback'));
        $tar = $this->requireExecutable('tar');
        $rsync = $this->requireExecutable('rsync');
        $composer = $this->requireExecutable('composer');
        $rollbackDirectory = ($this->temporaryDirectory ?? $this->createTemporaryDirectory()) . '/rollback';
        if (!mkdir($rollbackDirectory, 0700, true) && !is_dir($rollbackDirectory)) {
            throw new RuntimeException('Unable to prepare the rollback directory.');
        }
        $this->mustRun([$tar, '-xzf', $backupPath, '-C', $rollbackDirectory]);

        foreach (['composer.json', 'composer.lock'] as $composerFile) {
            $source = $rollbackDirectory . '/' . $composerFile;
            if (is_file($source) && !copy($source, $this->appRoot . '/' . $composerFile)) {
                throw new RuntimeException('Unable to restore ' . $composerFile . '.');
            }
        }
        $this->mustRun([
            $composer,
            'install',
            '--no-dev',
            '--no-interaction',
            '--no-progress',
            '--prefer-dist',
            '--optimize-autoloader',
        ], $this->appRoot);

        $command = [$rsync, '--archive', '--delete', '--exclude=.git/', '--exclude=vendor/', '--exclude=data/'];
        $command[] = rtrim($rollbackDirectory, '/') . '/';
        $command[] = rtrim($this->appRoot, '/') . '/';
        $this->mustRun($command);
        $this->disableMaintenance();
        $this->write($this->message('rollback_success'));
    }

    private function enableMaintenance(string $targetTag): void
    {
        $path = $this->maintenancePath();
        $contents = json_encode([
            'started_at' => gmdate(DATE_ATOM),
            'target_tag' => $targetTag,
            'pid' => getmypid(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $contents . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to enable update maintenance mode.');
        }
        chmod($path, 0640);
        $this->maintenanceEnabled = true;
    }

    private function disableMaintenance(): void
    {
        $path = $this->maintenancePath();
        if (is_file($path)) {
            unlink($path);
        }
        $this->maintenanceEnabled = false;
    }

    private function maintenancePath(): string
    {
        return $this->appRoot . '/data/update-maintenance.json';
    }

    private function clearCache(string $cacheDirectory): void
    {
        if (!is_dir($cacheDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $path = $item->getPathname();
            if ($item->getFilename() === '.gitignore') {
                continue;
            }
            if ($item->isDir()) {
                rmdir($path);
                continue;
            }
            unlink($path);
        }
    }

    private function createTemporaryDirectory(): string
    {
        $path = rtrim(sys_get_temp_dir(), '/') . '/simbioza-update-' . bin2hex(random_bytes(8));
        if (!mkdir($path, 0700, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create a temporary update directory.');
        }
        return $path;
    }

    private function removeTemporaryDirectory(): void
    {
        if ($this->temporaryDirectory === null || !is_dir($this->temporaryDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->temporaryDirectory);
        $this->temporaryDirectory = null;
    }

    private function currentTag(): ?string
    {
        $versionPath = $this->appRoot . '/VERSION';
        if (!is_file($versionPath)) {
            return null;
        }
        $version = trim((string)file_get_contents($versionPath));
        return preg_match('/^(?:v)?\d+\.\d+\.\d+$/', $version) === 1 ? $version : null;
    }

    private function requestedTag(): ?string
    {
        foreach ($this->arguments as $argument) {
            if (!str_starts_with($argument, '--tag=')) {
                continue;
            }
            $tag = trim(substr($argument, 6));
            if (preg_match('/^(?:v)?\d+\.\d+\.\d+$/', $tag) !== 1) {
                throw new RuntimeException('Invalid stable release tag: ' . $tag);
            }
            return $tag;
        }
        return null;
    }

    private function requestedLocale(): ?string
    {
        foreach ($this->arguments as $argument) {
            if (!str_starts_with($argument, '--lang=')) {
                continue;
            }
            $locale = strtolower(trim(substr($argument, 7)));
            return in_array($locale, ['hr', 'en'], true) ? $locale : null;
        }
        return null;
    }

    private function installedLocale(): string
    {
        $configurationPath = $this->appRoot . '/config/installation.php';
        if (!is_file($configurationPath)) {
            return 'hr';
        }
        $configuration = require $configurationPath;
        $locale = is_array($configuration) ? ($configuration['primary_locale'] ?? null) : null;
        return is_string($locale) && in_array($locale, ['hr', 'en'], true) ? $locale : 'hr';
    }

    private function hasOption(string $option): bool
    {
        return in_array($option, $this->arguments, true);
    }

    private function requireExecutable(string $name): string
    {
        $path = getenv('PATH');
        foreach (explode(PATH_SEPARATOR, is_string($path) ? $path : '') as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        throw new RuntimeException('Required executable is unavailable: ' . $name);
    }

    /** @param list<string> $command */
    private function mustRun(array $command, ?string $workingDirectory = null): void
    {
        [$exitCode, , $stderr] = $this->runProcess($command, $workingDirectory, false);
        if ($exitCode !== 0) {
            $detail = trim($stderr);
            throw new RuntimeException(
                sprintf('Command failed (%d): %s%s', $exitCode, implode(' ', $command), $detail !== '' ? "\n" . $detail : ''),
            );
        }
    }

    /**
     * @param list<string> $command
     * @return array{0:int,1:string,2:string}
     */
    private function runProcess(array $command, ?string $workingDirectory, bool $capture): array
    {
        $descriptors = $capture
            ? [
                0 => ['file', '/dev/null', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ]
            : [
                0 => ['file', '/dev/null', 'r'],
                1 => STDOUT,
                2 => STDERR,
            ];
        $process = proc_open($command, $descriptors, $pipes, $workingDirectory ?? $this->appRoot, null, [
            'bypass_shell' => true,
        ]);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start command: ' . implode(' ', $command));
        }

        $stdout = '';
        $stderr = '';
        if ($capture) {
            $capturedStdout = stream_get_contents($pipes[1]);
            $capturedStderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $stdout = is_string($capturedStdout) ? $capturedStdout : '';
            $stderr = is_string($capturedStderr) ? $capturedStderr : '';
        }
        $exitCode = proc_close($process);

        return [$exitCode, $stdout, $stderr];
    }

    private function message(string $key): string
    {
        return self::MESSAGES[$key][$this->locale] ?? self::MESSAGES[$key]['en'] ?? $key;
    }

    private function write(string $message): void
    {
        fwrite(STDOUT, rtrim($message) . PHP_EOL);
    }

    private function error(string $message): void
    {
        fwrite(STDERR, rtrim($message) . PHP_EOL);
    }
}

if (is_string($_SERVER['SCRIPT_FILENAME'] ?? null) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $rawArguments = is_array($_SERVER['argv'] ?? null) ? array_slice($_SERVER['argv'], 1) : [];
    $arguments = [];
    foreach ($rawArguments as $argument) {
        if (is_string($argument)) {
            $arguments[] = $argument;
        }
    }
    exit((new ApplicationUpdateCommand(__DIR__, $arguments))->run());
}
