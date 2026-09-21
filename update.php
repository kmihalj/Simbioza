#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Simbioza\Update;

use App\Module\NativeProcessRunner;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use JsonException;
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
        '/.git/',
        '/vendor/',
        '/data/',
        '/composer.lock',
        '/composer.local.json',
        '/composer.local.lock',
        '/config/database.php',
        '/config/env.php',
        '/config/installation.php',
        '/config/email.php',
        '/config/workspace.php',
        '/config/editor-html.php',
        '/resources/config/menu/',
        '/resources/config/theme/',
    ];

    /** @var list<string> */
    private const PRESERVED_WRITABLE_PATHS = [
        'config',
        'config/database.php',
        'config/env.php',
        'config/installation.php',
        'config/email.php',
        'config/workspace.php',
        'config/editor-html.php',
        'data',
        'resources/config',
        'resources/config/menu',
        'resources/config/theme',
    ];

    /** @var list<string> */
    private const RELEASE_MANAGED_CONFIG_FILES = [
        'config/app.php',
        'config/bootstrap.php',
        'config/commands.php',
        'config/listeners.php',
        'config/middleware.php',
        'config/routes.php',
        'config/services.php',
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
            'hr' => "Upotreba: php update.php [--check] [--tag=X.Y.Z] [--lang=hr|en]\n"
                . "  bez opcija    ažurira Simbiozu i module na zadnja kompatibilna izdanja\n"
                . "  --check      samo prikazuje trenutačni i zadnji dostupni tag\n"
                . "  --tag=TAG    ažurira na određeni stabilni Simbioza tag\n",
            'en' => "Usage: php update.php [--check] [--tag=X.Y.Z] [--lang=hr|en]\n"
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
            'hr' => 'Korijen aplikacije nije zapisiv. Pokrenite updater kao vlasnik instalacije ili ponovno dovršite namjenski FPM Setup; ne pokrećite ga kao root.',
            'en' => 'The application root is not writable. Run the updater as the installation owner or finalize the dedicated FPM Setup again; do not run it as root.',
        ],
        'delegating' => [
            'hr' => 'Namjenska FPM instalacija: nadogradnju predajem ograničenom deploy helperu...',
            'en' => 'Dedicated FPM installation: handing the update to the restricted deploy helper...',
        ],
        'delegate_timeout' => [
            'hr' => 'Isteklo je vrijeme čekanja na pozadinsku FPM nadogradnju. Provjerite privatni zapis data/logs/application-update.log.',
            'en' => 'Timed out while waiting for the background FPM update. Check the private data/logs/application-update.log.',
        ],
        'delegate_status' => [
            'hr' => 'Pozadinska FPM nadogradnja nije vratila valjan status.',
            'en' => 'The background FPM update did not return a valid status.',
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
        'theme_config' => [
            'hr' => 'Dopunjujem nedostajuće strukturne vrijednosti postojećih tema...',
            'en' => 'Adding missing structural values to existing themes...',
        ],
        'composer' => [
            'hr' => 'Ažuriram Composer module na zadnje kompatibilne tagove...',
            'en' => 'Updating Composer modules to their latest compatible tags...',
        ],
        'platform' => [
            'hr' => 'Provjeravam PHP i platformske preduvjete...',
            'en' => 'Checking PHP and platform requirements...',
        ],
        'preflight' => [
            'hr' => 'Provjeravam pokretanje aplikacije i pristup bazi prije migracija...',
            'en' => 'Checking application bootstrap and database access before migrations...',
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
        'metadata_restore_failure' => [
            'hr' => 'Nije moguće vratiti vlasništvo ili prava putanje: %s',
            'en' => 'Unable to restore ownership or permissions for path: %s',
        ],
    ];

    private readonly string $locale;

    /** @var resource|null */
    private $lockHandle;

    private bool $maintenanceEnabled = false;

    private bool $migrationStarted = false;

    private ?string $backupPath = null;

    private ?string $temporaryDirectory = null;

    private ?string $runtimeSettingsSnapshotDirectory = null;

    /** @var array<string, array{mode:int,uid:int,gid:int}> */
    private array $preservedPathMetadata = [];

    /** @var array<string,string> */
    private array $selectedOptionalRequirements = [];

    /** @var (\Closure(string,int,string):void)|null */
    private readonly ?\Closure $progressReporter;

    /**
     * HR: Prima korijen, CLI argumente i opcionalni izvjestitelj GUI napretka.
     * EN: Receives the root, CLI arguments, and an optional GUI progress reporter.
     *
     * @param list<string> $arguments
     * @param (callable(string,int,string):void)|null $progressReporter
     */
    public function __construct(
        private readonly string $appRoot,
        private readonly array $arguments,
        ?callable $progressReporter = null,
    ) {
        $this->locale = $this->requestedLocale() ?? $this->installedLocale();
        $this->progressReporter = $progressReporter === null ? null : \Closure::fromCallable($progressReporter);
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

            if ($this->shouldDelegateToSetupHelper()) {
                return $this->delegateToSetupHelper($targetTag);
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
            $this->progress('download', 10, $this->message('download'));
            $this->mustRun([
                $git,
                'init',
                '--quiet',
                $sourceDirectory,
            ]);
            $this->mustRun([
                $git,
                '-C',
                $sourceDirectory,
                'remote',
                'add',
                'origin',
                self::REPOSITORY,
            ]);
            $this->mustRun([
                $git,
                '-C',
                $sourceDirectory,
                'fetch',
                '--quiet',
                '--depth',
                '1',
                'origin',
                'refs/tags/' . $targetTag,
            ]);
            $this->mustRun([
                $git,
                '-C',
                $sourceDirectory,
                '-c',
                'advice.detachedHead=false',
                'checkout',
                '--quiet',
                'FETCH_HEAD',
            ]);
            $this->assertReleaseSource($sourceDirectory, $targetTag);
            $this->captureSelectedOptionalRequirements($sourceDirectory);

            $this->backupPath = $this->createBackup($tar, $currentTag, $targetTag);
            $this->progress('backup', 25, sprintf($this->message('backup'), $this->backupPath));
            $this->enableMaintenance($targetTag);
            $this->ensureModuleStateConfig();
            $this->capturePreservedPathMetadata();
            $this->captureRuntimeSettings();

            $this->progress('sync', 35, $this->message('sync'));
            $this->syncSource($rsync, $sourceDirectory);
            $this->restoreSelectedOptionalRequirements();
            $this->restoreRuntimeSettings();
            $this->appendMissingMenuSettings($sourceDirectory);
            $this->progress('configuration', 42, $this->message('theme_config'));
            $this->normalizeStoredThemeComponentHeights();
            $this->restorePreservedPathMetadata();
            $this->normalizeReleaseConfigFileMetadata();

            $this->progress('dependencies', 50, $this->message('composer'));
            $this->updateComposerDependencies($composer);

            $this->progress('platform', 70, $this->message('platform'));
            $this->mustRunComposer([$composer, 'check-platform-reqs'], $this->appRoot);
            $this->mustRunComposer([$composer, 'audit', '--locked'], $this->appRoot);

            // HR: Read-only status prisiljava potpuni bootstrap aplikacije prije
            //     nego označimo da su migracije započele. Pad tijekom bootstrapa
            //     tada još uvijek može sigurno vratiti kod i Composer pakete.
            // EN: The read-only status command forces a complete application
            //     bootstrap before migrations are marked as started. A bootstrap
            //     failure can therefore still safely restore code and packages.
            $this->progress('preflight', 78, $this->message('preflight'));
            $this->mustRun([
                $this->appRoot . '/vendor/bin/hph',
                'modules',
                'migrate-status',
            ], $this->appRoot);

            $this->migrationStarted = true;
            $this->progress('migrations', 85, $this->message('migrate'));
            $this->mustRun([
                $this->appRoot . '/vendor/bin/hph',
                'modules',
                'migrate-up',
            ], $this->appRoot);
            $this->progress('verification', 92, $this->message('status'));
            $this->mustRun([
                $this->appRoot . '/vendor/bin/hph',
                'modules',
                'migrate-status',
            ], $this->appRoot);

            // HR: Paketi sadržaja primjenjuju se tek uz migriranu shemu.
            // EN: Content packages are applied only after schema migrations.
            $this->mustRun([PHP_BINARY, $this->appRoot . '/scripts/update_bundled_assets.php'], $this->appRoot);
            $this->progress('cache', 96, $this->message('cache'));
            $this->clearCache($this->appRoot . '/data/cache');
            $this->restorePreservedPathMetadata();
            $this->disableMaintenance();
            $this->progress('complete', 100, sprintf($this->message('success'), $targetTag));
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
                    $this->reportProgress('rollback', 60, $this->message('rollback'));
                    $this->rollback();
                } catch (Throwable $rollbackError) {
                    $this->error(sprintf($this->message('failure'), $rollbackError->getMessage()));
                    return 1;
                }
            }

            $this->disableMaintenance();
            return 1;
        } finally {
            $this->restorePreservedPathMetadata(false);
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

    /**
     * HR: Prepoznaje namjensku FPM instalaciju samo kada root-owned helper
     *     izričito cilja worker upravo ove instalacije, a CLI ne radi kao
     *     vlasnik aplikacijskog koda.
     * EN: Recognises a dedicated FPM installation only when the root-owned
     *     helper explicitly targets this installation's worker and the CLI is
     *     not already running as the application-code owner.
     */
    private function shouldDelegateToSetupHelper(): bool
    {
        if (!function_exists('posix_geteuid')) {
            return false;
        }

        $owner = fileowner($this->appRoot);
        if (!is_int($owner) || posix_geteuid() === $owner) {
            return false;
        }

        $setupPath = $this->appRoot . '/config/setup.php';
        $setup = is_file($setupPath) ? require $setupPath : null;
        if (!is_array($setup) || !empty($setup['direct_local_testing'])) {
            return false;
        }

        $helper = is_string($setup['helper'] ?? null) ? $setup['helper'] : '';

        return self::requiresHelperDelegation(
            posix_geteuid(),
            $owner,
            self::helperTargetsInstallation($helper, $this->appRoot),
        );
    }

    /**
     * HR: Odvaja čistu odluku o delegiranju kako bi se provjerila bez stvarnih
     *     sistemskih korisnika: vlasnik koda radi izravno, ostali samo kroz
     *     helper potvrđen za istu instalaciju.
     * EN: Separates the pure delegation decision so it can be verified without
     *     real system accounts: the code owner runs directly, while other
     *     identities use only a helper confirmed for the same installation.
     */
    private static function requiresHelperDelegation(
        int $effectiveUid,
        int $applicationOwnerUid,
        bool $helperTargetsInstallation,
    ): bool {
        return $helperTargetsInstallation && $effectiveUid !== $applicationOwnerUid;
    }

    /**
     * HR: Provjerava da globalni helper nije konfiguriran za neku drugu
     *     Simbioza instalaciju na istom poslužitelju.
     * EN: Verifies that the global helper is not configured for another
     *     Simbioza installation on the same host.
     */
    private static function helperTargetsInstallation(string $helper, string $appRoot): bool
    {
        if ($helper === '' || !is_file($helper) || !is_executable($helper) || !is_readable($helper)) {
            return false;
        }

        $contents = file_get_contents($helper);

        return is_string($contents)
        && str_contains($contents, rtrim($appRoot, DIRECTORY_SEPARATOR) . '/scripts/setup_worker.php');
    }

    /**
     * HR: Iz običnog administratorskog CLI-ja pokreće istu strogo dopuštenu
     *     pozadinsku radnju kao GUI Setup i čeka njezin konačni status. Sam
     *     CLI nikada ne dobiva vlasništvo nad release kodom ni opću root ljusku.
     * EN: From an ordinary administrator CLI, starts the same strictly
     *     allowlisted background action as GUI Setup and waits for its final
     *     status. The CLI receives neither release-code ownership nor a general
     *     root shell.
     */
    private function delegateToSetupHelper(string $targetTag): int
    {
        $autoload = $this->appRoot . '/vendor/autoload.php';
        $setupPath = $this->appRoot . '/config/setup.php';
        if (!is_file($autoload) || !is_file($setupPath)) {
            throw new RuntimeException($this->message('write_access'));
        }
        require_once $autoload;
        $setup = require $setupPath;
        if (!is_array($setup)) {
            throw new RuntimeException($this->message('write_access'));
        }

        $helper = is_string($setup['helper'] ?? null) ? $setup['helper'] : '';
        $requestDirectory = is_string($setup['request_dir'] ?? null)
        ? $setup['request_dir']
        : $this->appRoot . '/data/setup-requests';
        $requests = new SetupRequestStore($requestDirectory);
        $gateway = new SetupGateway(
            $requests,
            new NativeProcessRunner(),
            $this->appRoot,
            $helper,
            !empty($setup['direct_local_testing']),
        );
        if (!$gateway->isAvailable()) {
            throw new RuntimeException($this->message('write_access'));
        }

        $logPath = $this->appRoot . '/data/logs/application-update.log';
        clearstatcache(true, $logPath);
        $logOffset = is_file($logPath) ? filesize($logPath) : 0;
        $logOffset = is_int($logOffset) ? $logOffset : 0;
        $this->write($this->message('delegating'));
        $gateway->execute('application-update-start', [
            'locale' => $this->locale,
            'tag' => $targetTag,
        ]);

        $statusPath = $this->appRoot . '/data/application-update-status.json';
        $deadline = time() + 3600;
        while (time() <= $deadline) {
            $this->drainDelegatedLog($logPath, $logOffset);
            clearstatcache(true, $statusPath);
            $status = is_file($statusPath)
            ? json_decode((string)file_get_contents($statusPath), true)
            : null;
            $state = is_array($status) && is_string($status['state'] ?? null)
            ? $status['state']
            : '';
            if ($state === 'success') {
                $this->drainDelegatedLog($logPath, $logOffset);
                return 0;
            }
            if ($state === 'failed') {
                $this->drainDelegatedLog($logPath, $logOffset);
                $message = is_array($status) && is_string($status['message'] ?? null)
                ? $status['message']
                : $this->message('delegate_status');
                throw new RuntimeException($message);
            }

            usleep(250_000);
        }

        throw new RuntimeException($this->message('delegate_timeout'));
    }

    /**
     * HR: Prikazuje samo novi dio privatnog updater loga dok ograničeni helper
     *     obavlja posao, bez ponavljanja zapisa ranijih nadogradnji.
     * EN: Displays only the new part of the private updater log while the
     *     restricted helper works, without replaying earlier update logs.
     */
    private function drainDelegatedLog(string $path, int &$offset): void
    {
        clearstatcache(true, $path);
        $size = is_file($path) ? filesize($path) : false;
        if (!is_int($size) || $size <= $offset) {
            if (is_int($size) && $size < $offset) {
                $offset = 0;
            }
            return;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return;
        }
        try {
            if (fseek($handle, $offset) !== 0) {
                return;
            }
            $chunk = stream_get_contents($handle);
            if (is_string($chunk) && $chunk !== '') {
                fwrite(STDOUT, $chunk);
                $offset += strlen($chunk);
            }
        } finally {
            fclose($handle);
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
        $command = [
            $rsync,
            '--archive',
            '--delete',
            '--no-owner',
            '--no-group',
            '--no-perms',
        ];
        foreach ($this->sourceSyncExcludes() as $exclude) {
            $command[] = '--exclude=' . $exclude;
        }
        $command[] = rtrim($sourceDirectory, '/') . '/';
        $command[] = rtrim($this->appRoot, '/') . '/';
        $this->mustRun($command);
    }

    /**
     * HR: Starije instalacije spremale su popis modula izravno u release datoteku
     *     app.php. Prije njezine zamjene izdvaja taj odabir u trajnu runtime
     *     konfiguraciju pod data/ kako bi je i GUI i CLI mogli atomski mijenjati.
     * EN: Legacy installations stored the module list directly in the release
     *     app.php file. Before replacing it, extracts that selection into the
     *     persistent runtime configuration under data/ so both GUI and CLI can
     *     update it atomically without allowing FPM to replace release config.
     */
    private function ensureModuleStateConfig(): void
    {
        $moduleStatePath = $this->appRoot . '/data/config/modules.php';
        if (is_file($moduleStatePath)) {
            return;
        }

        $legacyModuleStatePath = $this->appRoot . '/config/modules.php';
        $legacyModuleState = is_file($legacyModuleStatePath) ? require $legacyModuleStatePath : null;
        $legacyAppPath = $this->appRoot . '/config/app.php';
        $legacyApp = is_file($legacyAppPath) ? require $legacyAppPath : null;
        $legacyModules = is_array($legacyModuleState)
        && is_array($legacyModuleState['enabled'] ?? null)
        ? $legacyModuleState['enabled']
        : (is_array($legacyApp)
            && is_array($legacyApp['modules'] ?? null)
            && is_array($legacyApp['modules']['enabled'] ?? null)
            ? $legacyApp['modules']['enabled']
            : []);
        $enabled = array_values(array_unique(array_filter(
            $legacyModules,
            static fn(mixed $package): bool => is_string($package) && trim($package) !== '',
        )));
        $removed = is_array($legacyModuleState)
        && is_array($legacyModuleState['removed'] ?? null)
        ? array_values(array_unique(array_filter(
            $legacyModuleState['removed'],
            static fn(mixed $slug): bool => is_string($slug) && trim($slug) !== '',
        )))
        : [];
        if ($enabled === []) {
            throw new RuntimeException(
                'Legacy module selection could not be extracted before replacing config/app.php.',
            );
        }

        $moduleStateDirectory = dirname($moduleStatePath);
        if (!is_dir($moduleStateDirectory)) {
            if (!mkdir($moduleStateDirectory, 02770, true) && !is_dir($moduleStateDirectory)) {
                throw new RuntimeException('Unable to create the persistent module state directory.');
            }
            if (!chmod($moduleStateDirectory, 02770)) {
                throw new RuntimeException('Unable to secure the persistent module state directory.');
            }
        }

        $temporary = tempnam($moduleStateDirectory, '.simbioza-modules-update-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to prepare the persistent module configuration.');
        }

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export([
            'enabled' => $enabled,
            'removed' => $removed,
        ], true) . ";\n";
        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || !chmod($temporary, 0660)) {
                throw new RuntimeException('Unable to write the persistent module configuration.');
            }
            if (!rename($temporary, $moduleStatePath)) {
                throw new RuntimeException('Unable to activate the persistent module configuration.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * HR: Spaja stalna izuzeća izdanja sa svim zatečenim administratorskim
     *     postavkama. Trajne datoteke zato ostaju na mjestu, s istim inodeom,
     *     vlasnikom i pravima, čak i kada updater radi kao odvojeni deploy korisnik.
     * EN: Combines permanent release exclusions with every discovered
     *     administrator-managed setting. Persistent files therefore stay in
     *     place with the same inode, owner, and mode even under a separate deploy user.
     *
     * @return list<string>
     */
    private function sourceSyncExcludes(): array
    {
        $excludes = array_fill_keys(self::SOURCE_SYNC_EXCLUDES, true);
        foreach ($this->runtimeSettingsFiles() as $relativePath) {
            $excludes['/' . ltrim($relativePath, '/')] = true;
        }

        return array_keys($excludes);
    }

    /**
     * HR: Pamti opcionalne module koje je administrator stvarno dodao u
     *     aplikacijski Composer skup. Ograničenja verzija čita iz zasebnog
     *     kataloga stabilnih opcionalnih paketa u manifestu izdanja.
     * EN: Remembers optional modules that the administrator actually added to
     *     the application Composer set. Version constraints are read from the
     *     release manifest's separate stable optional-package catalog.
     */
    private function captureSelectedOptionalRequirements(string $sourceDirectory): void
    {
        $current = json_decode((string)file_get_contents($this->appRoot . '/composer.json'), true);
        $currentLockPath = $this->appRoot . '/composer.lock';
        $currentLock = is_file($currentLockPath)
        ? json_decode((string)file_get_contents($currentLockPath), true)
        : [];
        $moduleStatePath = $this->appRoot . '/data/config/modules.php';
        $moduleState = is_file($moduleStatePath) ? require $moduleStatePath : [];
        $release = json_decode((string)file_get_contents($sourceDirectory . '/composer.json'), true);
        if (!is_array($current) || !is_array($currentLock) || !is_array($moduleState) || !is_array($release)) {
            throw new RuntimeException('Composer manifests could not be compared before update.');
        }

        $this->selectedOptionalRequirements = self::selectedOptionalRequirements(
            $current,
            $currentLock,
            $moduleState,
            $release,
        );
    }

    /**
     * HR: Spaja tri pouzdana traga o instaliranom opcionalnom modulu: izričiti
     *     Composer zahtjev, zaključani paket i trajno uključeno stanje. Time
     *     nadogradnja čuva i isključeni instalirani modul te popravlja stariju
     *     instalaciju čiji je manifest već bio sveden na obveznu jezgru.
     * EN: Combines three reliable traces of an installed optional module: an
     *     explicit Composer requirement, a locked package, and persistent
     *     enabled state. This preserves disabled installed modules and repairs
     *     older installations whose manifest was already reduced to core.
     *
     * @param array<array-key,mixed> $current
     * @param array<array-key,mixed> $currentLock
     * @param array<array-key,mixed> $moduleState
     * @param array<array-key,mixed> $release
     * @return array<string,string>
     */
    public static function selectedOptionalRequirements(
        array $current,
        array $currentLock,
        array $moduleState,
        array $release,
    ): array {
        $currentRequire = is_array($current['require'] ?? null) ? $current['require'] : [];
        $releaseOptional = is_array($release['suggest'] ?? null) ? $release['suggest'] : [];
        $releaseExtra = is_array($release['extra'] ?? null) ? $release['extra'] : [];
        $releaseSimbioza = is_array($releaseExtra['simbioza'] ?? null) ? $releaseExtra['simbioza'] : [];
        $releaseConstraints = is_array($releaseSimbioza['optional-modules'] ?? null)
        ? $releaseSimbioza['optional-modules']
        : [];
        $selectedPackages = [];
        foreach (array_keys($currentRequire) as $package) {
            if (is_string($package)) {
                $selectedPackages[$package] = true;
            }
        }
        foreach (['packages', 'packages-dev'] as $section) {
            foreach (is_array($currentLock[$section] ?? null) ? $currentLock[$section] : [] as $package) {
                if (is_array($package) && is_string($package['name'] ?? null)) {
                    $selectedPackages[$package['name']] = true;
                }
            }
        }
        foreach (is_array($moduleState['enabled'] ?? null) ? $moduleState['enabled'] : [] as $package) {
            if (is_string($package)) {
                $selectedPackages[$package] = true;
            }
        }

        $selected = [];
        foreach ($releaseOptional as $package => $_description) {
            if (!is_string($package) || !isset($selectedPackages[$package])) {
                continue;
            }
            $constraint = $releaseConstraints[$package] ?? $currentRequire[$package];
            if (is_string($constraint) && trim($constraint) !== '') {
                $selected[$package] = $constraint;
            }
        }
        ksort($selected, SORT_STRING);

        return $selected;
    }

    /**
     * HR: Nakon sinkronizacije vraća odabrane opcionalne pakete u produkcijski
     *     dio manifesta prije Composer razrješenja novog izdanja.
     * EN: After source synchronization, restores selected optional packages to
     *     the production manifest before resolving the new release dependencies.
     */
    private function restoreSelectedOptionalRequirements(): void
    {
        if ($this->selectedOptionalRequirements === []) {
            return;
        }

        $path = $this->appRoot . '/composer.json';
        $manifest = json_decode((string)file_get_contents($path), true);
        if (!is_array($manifest)) {
            throw new RuntimeException('Updated Composer manifest is invalid.');
        }
        $require = is_array($manifest['require'] ?? null) ? $manifest['require'] : [];
        foreach ($this->selectedOptionalRequirements as $package => $constraint) {
            $require[$package] = $constraint;
        }
        ksort($require, SORT_STRING);
        $manifest['require'] = $require;
        $encoded = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";
        $temporary = $this->appRoot . '/.simbioza-update-composer-' . bin2hex(random_bytes(8));
        if (file_put_contents($temporary, $encoded, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new RuntimeException('Selected optional modules could not be restored to composer.json.');
        }
    }

    /**
     * HR: Sprema sve postojeće aplikacijske override postavke prije sinkronizacije izdanja.
     *     Datoteke koje grade bootstrap, rute i servisni spremnik ostaju pod upravljanjem izdanja.
     * EN: Snapshots every existing application override setting before release synchronization.
     *     Files that build the bootstrap, routes, and service container remain release-managed.
     */
    private function captureRuntimeSettings(): void
    {
        if ($this->temporaryDirectory === null) {
            throw new RuntimeException('The temporary update directory is unavailable.');
        }

        $snapshotDirectory = $this->temporaryDirectory . '/runtime-settings';
        if (!mkdir($snapshotDirectory, 0700, true) && !is_dir($snapshotDirectory)) {
            throw new RuntimeException('Unable to create the runtime-settings snapshot directory.');
        }

        foreach ($this->runtimeSettingsFiles() as $relativePath) {
            $source = $this->appRoot . '/' . $relativePath;
            $target = $snapshotDirectory . '/' . $relativePath;
            $targetDirectory = dirname($target);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0700, true) && !is_dir($targetDirectory)) {
                throw new RuntimeException('Unable to prepare a runtime-settings snapshot path.');
            }
            if (!copy($source, $target)) {
                throw new RuntimeException('Unable to snapshot runtime setting: ' . $relativePath);
            }
            chmod($target, 0600);
        }

        $this->runtimeSettingsSnapshotDirectory = $snapshotDirectory;
    }

    /**
     * HR: Nakon sinkronizacije vraća zatečene postavke preko novih release defaulta.
     *     Nova konfiguracijska datoteka koju prethodna instalacija nije imala ostaje iz izdanja.
     * EN: Restores captured settings over new release defaults after synchronization.
     *     A new configuration file absent from the previous installation remains from the release.
     */
    private function restoreRuntimeSettings(): void
    {
        $snapshotDirectory = $this->runtimeSettingsSnapshotDirectory;
        if ($snapshotDirectory === null || !is_dir($snapshotDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($snapshotDirectory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isFile() || $item->isLink()) {
                continue;
            }

            $source = $item->getPathname();
            $relativePath = substr($source, strlen($snapshotDirectory) + 1);
            if ($relativePath === '') {
                continue;
            }

            $target = $this->appRoot . '/' . $relativePath;
            if (is_file($target) && !is_link($target)) {
                $snapshotContents = file_get_contents($source);
                $currentContents = file_get_contents($target);
                if (is_string($snapshotContents) && hash_equals($snapshotContents, (string)$currentContents)) {
                    continue;
                }
                if (!is_string($snapshotContents) || file_put_contents($target, $snapshotContents, LOCK_EX) === false) {
                    throw new RuntimeException('Unable to restore runtime setting: ' . $relativePath);
                }
                continue;
            }

            $targetDirectory = dirname($target);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0770, true) && !is_dir($targetDirectory)) {
                throw new RuntimeException('Unable to recreate runtime-settings directory: ' . $relativePath);
            }

            $temporaryTarget = $targetDirectory . '/.simbioza-update-setting-' . bin2hex(random_bytes(8));
            if (!copy($source, $temporaryTarget) || !rename($temporaryTarget, $target)) {
                @unlink($temporaryTarget);
                throw new RuntimeException('Unable to restore runtime setting: ' . $relativePath);
            }
        }
    }

    /**
     * HR: U zatečeni administratorski izbornik postavki dodaje samo nove
     *     release stavke. Postojeće stavke, njihov redoslijed i postavke ostaju
     *     netaknuti, a svaka nova značajka ili modul dodaje se na kraj.
     * EN: Appends only new release entries to the administrator-managed
     *     settings menu. Existing entries, order, and options stay untouched,
     *     while every new feature or module is added at the end.
     */
    private function appendMissingMenuSettings(string $sourceDirectory): int
    {
        $relativePath = 'resources/config/menu/settings.json';
        $currentPath = $this->appRoot . '/' . $relativePath;
        $releasePath = rtrim($sourceDirectory, '/') . '/' . $relativePath;
        if (!is_file($currentPath) || !is_file($releasePath)) {
            return 0;
        }

        try {
            $current = json_decode((string)file_get_contents($currentPath), true, 512, JSON_THROW_ON_ERROR);
            $release = json_decode((string)file_get_contents($releasePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Menu settings could not be decoded during update.', 0, $exception);
        }
        if (!is_array($current) || !array_is_list($current) || !is_array($release) || !array_is_list($release)) {
            throw new RuntimeException('Menu settings must contain a JSON list during update.');
        }

        $existingIds = [];
        $lastOrder = 0;
        foreach ($current as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (is_string($entry['id'] ?? null) && trim($entry['id']) !== '') {
                $existingIds[$entry['id']] = true;
            }
            if (is_numeric($entry['order'] ?? null)) {
                $lastOrder = max($lastOrder, (int)$entry['order']);
            }
        }

        $added = 0;
        foreach ($release as $entry) {
            if (!is_array($entry) || !is_string($entry['id'] ?? null) || trim($entry['id']) === '') {
                continue;
            }
            $id = $entry['id'];
            if (isset($existingIds[$id])) {
                continue;
            }

            $lastOrder += 10;
            $entry['order'] = $lastOrder;
            $current[] = $entry;
            $existingIds[$id] = true;
            ++$added;
        }
        if ($added === 0) {
            return 0;
        }

        $encoded = json_encode(
            $current,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ) . "\n";
        $this->writeExistingFilePreservingMetadata(
            $currentPath,
            $encoded,
            'Updated menu settings could not be written.',
        );

        return $added;
    }

    /**
     * HR: U sačuvane instalacijske teme dodaje samo nove ključeve visine koji nedostaju.
     *     Vrijednosti odgovaraju izgledu prije uvođenja podesivih visina.
     * EN: Adds only missing height keys to preserved installation themes. Values
     *     match the appearance before configurable heights were introduced.
     */
    private function normalizeStoredThemeComponentHeights(): int
    {
        $path = $this->appRoot . '/resources/config/theme/themes.json';
        if (!is_file($path)) {
            return 0;
        }

        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new RuntimeException('Unable to read preserved theme configuration.');
        }

        try {
            $themes = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            throw new RuntimeException('Preserved theme configuration contains invalid JSON.', 0, $jsonException);
        }

        if (!is_array($themes)) {
            throw new RuntimeException('Preserved theme configuration must contain a JSON array.');
        }

        $changedThemes = 0;
        foreach ($themes as $index => $theme) {
            if (!is_array($theme)) {
                continue;
            }

            $components = is_array($theme['components'] ?? null) ? $theme['components'] : [];
            $header = is_array($components['header'] ?? null) ? $components['header'] : [];
            $navigation = is_array($components['navigation'] ?? null) ? $components['navigation'] : [];
            $changed = false;

            if (!array_key_exists('height_px', $header)) {
                $header['height_px'] = 72;
                $changed = true;
            }

            if (!array_key_exists('height_px', $navigation)) {
                $navigation['height_px'] = 56;
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $components['header'] = $header;
            $components['navigation'] = $navigation;
            $theme['components'] = $components;
            $themes[$index] = $theme;
            ++$changedThemes;
        }

        if ($changedThemes === 0) {
            return 0;
        }

        try {
            $encoded = json_encode(
                $themes,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) . "\n";
        } catch (\JsonException $jsonException) {
            throw new RuntimeException('Unable to encode upgraded theme configuration.', 0, $jsonException);
        }

        $this->writeExistingFilePreservingMetadata(
            $path,
            $encoded,
            'Unable to upgrade preserved theme configuration.',
        );

        return $changedThemes;
    }

    /**
     * HR: Zaključano prepisuje sadržaj postojeće trajne datoteke bez zamjene
     *     inodea. Time neprivilegirani deploy proces čuva FPM vlasnika, grupu,
     *     ACL i prava administratorski upravljane konfiguracije.
     * EN: Rewrites an existing persistent file under an exclusive lock without
     *     replacing its inode. This lets an unprivileged deploy process retain
     *     the FPM owner, group, ACL, and mode of administrator-managed config.
     */
    private function writeExistingFilePreservingMetadata(
        string $path,
        string $contents,
        string $failureMessage,
    ): void {
        $handle = @fopen($path, 'r+b');
        if (!is_resource($handle)) {
            throw new RuntimeException($failureMessage);
        }

        $locked = false;
        try {
            $locked = flock($handle, LOCK_EX);
            if (!$locked || !rewind($handle) || !ftruncate($handle, 0)) {
                throw new RuntimeException($failureMessage);
            }

            $length = strlen($contents);
            $written = 0;
            while ($written < $length) {
                $chunk = fwrite($handle, substr($contents, $written));
                if (!is_int($chunk) || $chunk <= 0) {
                    throw new RuntimeException($failureMessage);
                }
                $written += $chunk;
            }
            if (!fflush($handle)) {
                throw new RuntimeException($failureMessage);
            }
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }
    }

    /**
     * HR: Otkriva trajne PHP override datoteke i sve administratorski upravljane resource postavke.
     * EN: Discovers persistent PHP overrides and all administrator-managed resource settings.
     *
     * @return list<string>
     */
    private function runtimeSettingsFiles(): array
    {
        $files = [];
        $configDirectory = $this->appRoot . '/config';
        if (is_dir($configDirectory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($configDirectory, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo || !$item->isFile() || $item->isLink()) {
                    continue;
                }

                $path = $item->getPathname();
                $relativePath = 'config/' . substr($path, strlen($configDirectory) + 1);
                if (
                    !str_ends_with($relativePath, '.php')
                    || str_ends_with($relativePath, '.php.dist')
                    || in_array($relativePath, self::RELEASE_MANAGED_CONFIG_FILES, true)
                ) {
                    continue;
                }
                $files[$relativePath] = true;
            }
        }

        $resourceDirectory = $this->appRoot . '/resources/config';
        if (is_dir($resourceDirectory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($resourceDirectory, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo || !$item->isFile() || $item->isLink()) {
                    continue;
                }
                $path = $item->getPathname();
                $relativePath = 'resources/config/' . substr($path, strlen($resourceDirectory) + 1);
                $files[$relativePath] = true;
            }
        }

        $paths = array_keys($files);
        sort($paths, SORT_STRING);
        return $paths;
    }

    /** HR: Pamti Unix vlasništvo i prava zapisivih putanja. EN: Captures Unix ownership and modes of writable paths. */
    private function capturePreservedPathMetadata(): void
    {
        $this->preservedPathMetadata = [];
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $paths = array_fill_keys(self::PRESERVED_WRITABLE_PATHS, true);
        foreach ($this->runtimeSettingsFiles() as $relativePath) {
            $paths[$relativePath] = true;
            $directory = dirname($relativePath);
            while ($directory !== '.' && $directory !== '') {
                $paths[$directory] = true;
                $directory = dirname($directory);
            }
        }

        foreach (array_keys($paths) as $relativePath) {
            $path = $this->appRoot . '/' . $relativePath;
            $metadata = @stat($path);
            if (!is_array($metadata)) {
                continue;
            }

            $this->preservedPathMetadata[$relativePath] = [
                'mode' => ((int)($metadata['mode'] ?? 0)) & 07777,
                'uid' => (int)($metadata['uid'] ?? -1),
                'gid' => (int)($metadata['gid'] ?? -1),
            ];
        }
    }

    /**
     * HR: Nova konfiguracijska datoteka izdanja bez zapamćenih metapodataka ostaje
     *     u vlasništvu deploy procesa i postaje čitljiva FPM procesu. Updater zato
     *     ne treba privilegirani chown, dok trajne postavke zadržavaju svoja prava.
     * EN: A new release configuration file without captured metadata remains owned
     *     by the deploy process and is made readable by FPM. The updater therefore
     *     needs no privileged chown while persistent settings keep their metadata.
     */
    private function normalizeReleaseConfigFileMetadata(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        if (!isset($this->preservedPathMetadata['config'])) {
            return;
        }

        foreach (glob($this->appRoot . '/config/*.php') ?: [] as $path) {
            $relativePath = 'config/' . basename($path);
            if (!is_file($path) || isset($this->preservedPathMetadata[$relativePath])) {
                continue;
            }

            if (!@chmod($path, 0644)) {
                throw new RuntimeException(sprintf(
                    $this->message('metadata_restore_failure'),
                    $relativePath,
                ));
            }

            clearstatcache(true, $path);
            $this->preservedPathMetadata[$relativePath] = [
                'mode' => 0644,
                'uid' => (int)@fileowner($path),
                'gid' => (int)@filegroup($path),
            ];
        }
    }

    /**
     * HR: Vraća zatečeni Unix UID, GID i mode; ne pretpostavlja ime web-korisnika.
     * EN: Restores the captured Unix UID, GID, and mode without assuming a web-user name.
     */
    private function restorePreservedPathMetadata(bool $strict = true): void
    {
        if (PHP_OS_FAMILY === 'Windows' || $this->preservedPathMetadata === []) {
            return;
        }

        foreach ($this->preservedPathMetadata as $relativePath => $metadata) {
            $path = $this->appRoot . '/' . $relativePath;
            if (!file_exists($path)) {
                continue;
            }

            clearstatcache(true, $path);
            $restored = true;
            $currentOwner = @fileowner($path);
            if (!is_int($currentOwner)) {
                $restored = false;
            } elseif ($metadata['uid'] >= 0 && $currentOwner !== $metadata['uid']) {
                $restored = @chown($path, $metadata['uid']);
            }

            clearstatcache(true, $path);
            $currentGroup = @filegroup($path);
            if (!is_int($currentGroup)) {
                $restored = false;
            } elseif ($metadata['gid'] >= 0 && $currentGroup !== $metadata['gid']) {
                $restored = @chgrp($path, $metadata['gid']) && $restored;
            }

            clearstatcache(true, $path);
            $currentMode = @fileperms($path);
            if (!is_int($currentMode)) {
                $restored = false;
            } elseif (($currentMode & 07777) !== $metadata['mode']) {
                $restored = @chmod($path, $metadata['mode']) && $restored;
            }

            if (!$restored && $strict) {
                throw new RuntimeException(sprintf(
                    $this->message('metadata_restore_failure'),
                    $relativePath,
                ));
            }
        }
    }

    /**
     * HR: Izračunava novi lock bez izmjene postojećeg vendora pa pakete instalira u čisti vendor.
     *     Time release instalacija ne ovisi o `.git` direktorijima unutar postojećih VCS paketa.
     *
     * EN: Resolves the new lock without changing the existing vendor, then installs into a clean vendor.
     *     This keeps release updates independent of `.git` directories inside existing VCS packages.
     */
    private function updateComposerDependencies(string $composer): void
    {
        $this->mustRunComposer([
            $composer,
            'update',
            '--with-all-dependencies',
            '--no-install',
            '--no-interaction',
            '--no-progress',
            '--prefer-dist',
        ], $this->appRoot);

        $vendorDirectory = $this->appRoot . '/vendor';
        $vendorBackupDirectory = $this->appRoot . '/data/update-vendor-' . bin2hex(random_bytes(8));
        $vendorMoved = false;

        if (is_dir($vendorDirectory)) {
            if (!rename($vendorDirectory, $vendorBackupDirectory)) {
                throw new RuntimeException('Unable to move the existing Composer vendor directory.');
            }
            $vendorMoved = true;
        }

        try {
            $this->mustRunComposer([
                $composer,
                'install',
                '--no-interaction',
                '--no-progress',
                '--prefer-dist',
                '--optimize-autoloader',
            ], $this->appRoot);
            $this->normalizeComposerVendorMetadata();
        } catch (Throwable $throwable) {
            $this->removeDirectory($vendorDirectory);
            if ($vendorMoved && !rename($vendorBackupDirectory, $vendorDirectory)) {
                throw new RuntimeException(
                    'Composer installation failed and the previous vendor directory could not be restored: '
                    . $throwable->getMessage(),
                    0,
                    $throwable,
                );
            }
            throw $throwable;
        }

        if ($vendorMoved) {
            $this->removeDirectory($vendorBackupDirectory);
        }
    }

    /**
     * HR: Composerove datoteke čini čitljivima web procesu i prohodnima kroz
     *     direktorije bez promjene vlasnika, grupe ili prava pisanja. To je
     *     potrebno kada sigurni FPM helper stvara vendor uz umask 0007.
     * EN: Makes Composer files readable by the web process and directories
     *     traversable without changing ownership, group, or write permissions.
     *     This is required when the secure FPM helper creates vendor with umask 0007.
     */
    private function normalizeComposerVendorMetadata(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $vendorDirectory = $this->appRoot . '/vendor';
        if (!is_dir($vendorDirectory)) {
            throw new RuntimeException('Composer vendor directory is unavailable after installation.');
        }

        $paths = [new \SplFileInfo($vendorDirectory)];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($vendorDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                $paths[] = $item;
            }
        }

        foreach ($paths as $item) {
            if ($item->isLink()) {
                continue;
            }

            $path = $item->getPathname();
            $permissions = @fileperms($path);
            if (!is_int($permissions)) {
                throw new RuntimeException('Unable to read Composer path permissions: ' . $path);
            }
            $mode = $permissions & 07777;
            $required = $item->isDir() ? 0005 : 0004;
            if (($mode & $required) === $required) {
                continue;
            }
            if (!@chmod($path, $mode | $required)) {
                throw new RuntimeException('Unable to make Composer path web-readable: ' . $path);
            }
        }
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
        $this->mustRunComposer([
            $composer,
            'install',
            '--no-interaction',
            '--no-progress',
            '--prefer-dist',
            '--optimize-autoloader',
        ], $this->appRoot);
        $this->normalizeComposerVendorMetadata();

        $command = [
            $rsync,
            '--archive',
            '--delete',
            '--no-owner',
            '--no-group',
            '--no-perms',
        ];
        foreach ($this->sourceSyncExcludes() as $exclude) {
            $command[] = '--exclude=' . $exclude;
        }
        $command[] = rtrim($rollbackDirectory, '/') . '/';
        $command[] = rtrim($this->appRoot, '/') . '/';
        $this->mustRun($command);
        $this->restorePreservedPathMetadata();
        $this->disableMaintenance();
        $this->write($this->message('rollback_success'));
    }

    private function removeDirectory(string $directory): void
    {
        if (is_link($directory) || is_file($directory)) {
            unlink($directory);
            return;
        }
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            $path = $item->getPathname();
            if ($item->isLink() || $item->isFile()) {
                unlink($path);
                continue;
            }
            rmdir($path);
        }
        rmdir($directory);
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
                sprintf(
                    'Command failed (%d): %s%s',
                    $exitCode,
                    implode(' ', $command),
                    $detail !== '' ? "\n" . $detail : '',
                ),
            );
        }
    }

    /** @param list<string> $command */
    private function mustRunComposer(array $command, ?string $workingDirectory = null): void
    {
        $this->temporaryDirectory ??= $this->createTemporaryDirectory();
        $cacheDirectory = $this->temporaryDirectory . '/composer-cache';
        if (!is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
            throw new RuntimeException('Unable to create a private Composer update cache.');
        }

        $previousSuperuser = getenv('COMPOSER_ALLOW_SUPERUSER');
        $previousCache = getenv('COMPOSER_CACHE_DIR');
        putenv('COMPOSER_ALLOW_SUPERUSER=1');
        putenv('COMPOSER_CACHE_DIR=' . $cacheDirectory);
        try {
            $this->mustRun($command, $workingDirectory);
        } finally {
            if ($previousSuperuser === false) {
                putenv('COMPOSER_ALLOW_SUPERUSER');
            } else {
                putenv('COMPOSER_ALLOW_SUPERUSER=' . $previousSuperuser);
            }

            if ($previousCache === false) {
                putenv('COMPOSER_CACHE_DIR');
            } else {
                putenv('COMPOSER_CACHE_DIR=' . $previousCache);
            }
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

    /**
     * HR: Ispisuje CLI poruku i, kada postoji GUI omotač, atomski javlja fazu i postotak.
     * EN: Writes the CLI message and, when a GUI wrapper exists, atomically reports its stage and percentage.
     */
    private function progress(string $stage, int $percentage, string $message): void
    {
        $this->write($message);
        $this->reportProgress($stage, $percentage, $message);
    }

    /**
     * HR: Šalje ograničeni napredak bez uvjetovanja samostalnog CLI rada.
     * EN: Sends restricted progress without making standalone CLI execution depend on it.
     */
    private function reportProgress(string $stage, int $percentage, string $message): void
    {
        if ($this->progressReporter === null) {
            return;
        }

        ($this->progressReporter)($stage, max(0, min(100, $percentage)), $message);
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
