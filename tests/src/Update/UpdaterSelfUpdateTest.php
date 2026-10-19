<?php

declare(strict_types=1);

namespace Tests\Update;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class UpdaterSelfUpdateTest extends TestCase
{
    private ?string $temporaryRoot = null;

    /** @var list<string> */
    private array $identityCommand = [];

    protected function tearDown(): void
    {
        if ($this->temporaryRoot === null) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->temporaryRoot);
    }

    /**
     * EN: Exercises the real CLI and GUI wrapper under ordinary and restrictive permissions.
     *
     * HR: Testira stvarni CLI i GUI omotač pod tipičnim i restriktivnim pravima.
     *
     * @return iterable<string,array{string,int,int}>
     */
    public static function installationModes(): iterable
    {
        yield 'mod_php CLI owner' => ['cli', 0755, 0022];
        yield 'FPM GUI deployment job' => ['gui', 0755, 0022];
        yield 'secure FPM GUI restrictive umask' => ['gui', 0750, 0007];
        yield 'secure FPM maintainer CLI' => ['cli', 0750, 0007];
        yield 'private demo CLI' => ['cli', 0700, 0077];
    }

    /**
     * EN: Freshly loaded code must run all subsequent phases while lock and GUI status remain valid.
     *
     * HR: Novo učitani kod mora izvršiti sve sljedeće faze, a lock i GUI status moraju ostati važeći.
     */
    #[DataProvider('installationModes')]
    public function testNewProcessCompletesUpdate(string $entry, int $mode, int $mask): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $this->release($repository, '0.3.1', $this->candidate($repository));
        chmod($root . '/update.php', $mode);
        $configuration = (string)file_get_contents($root . '/config/private.php');
        $metadata = stat($root . '/update.php');
        [$exit, $output] = $this->runUpdater($root, $tools, $entry, [], $mask);
        $this->assertSame(0, $exit, $output);
        $this->assertSame('0.3.1', trim((string)file_get_contents($root . '/VERSION')));
        $proof = json_decode((string)file_get_contents($root . '/data/fresh-updater.json'), true);
        $this->assertSame('1.0.1', $proof['version']);
        $this->assertTrue($proof['lock_held']);
        $this->assertNotSame($proof['parent_pid'], $proof['pid']);
        $this->assertSame($configuration, file_get_contents($root . '/config/private.php'));
        $this->assertSame('kept', file_get_contents($root . '/data/document.txt'));
        $database = new \PDO('sqlite:' . $root . '/data/database.sqlite');
        $this->assertSame(1, (int)$database->query('SELECT updates FROM fixture')->fetchColumn());
        $this->assertSame($mode, fileperms($root . '/update.php') & 0777);
        $this->assertSame($metadata['uid'], fileowner($root . '/update.php'));
        $this->assertSame($metadata['gid'], filegroup($root . '/update.php'));
        $this->assertFileDoesNotExist($root . '/data/update-maintenance.json');
        $this->assertSame([], glob($root . '/data/simbioza-update-*'));
        $backups = glob($root . '/data/backups/updater/*.php');
        $this->assertCount(1, $backups);
        $this->assertSame(0600, fileperms($backups[0]) & 0777);
        $phases = (string)file_get_contents($root . '/data/phases.log');
        $this->assertStringContainsString('fresh-updater', $phases);
        $this->assertLessThan(strpos($phases, 'composer-update'), strpos($phases, 'fresh-updater'));
        $this->assertLessThan(strpos($phases, 'hph-migrate-up'), strpos($phases, 'composer-update'));
        if ($entry === 'gui') {
            $status = json_decode((string)file_get_contents($root . '/data/application-update-status.json'), true);
            $this->assertSame('success', $status['state']);
            $this->assertSame(100, $status['progress']);
            $this->assertNull($status['pid']);
            $this->assertStringContainsString('fresh_updater', (string)file_get_contents($root . '/data/progress.log'));
        }
    }

    /**
     * EN: The latest updater takes priority even for an explicitly selected older application tag.
     *
     * HR: Zadnji updater ima prednost i uz izričito odabran raniji aplikacijski tag.
     */
    public function testLatestUpdaterIsNotDowngradedBySelectedApplicationTag(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $this->release($repository, '0.3.1', (string)file_get_contents($root . '/update.php'));
        $candidate = $this->candidate($repository);
        $this->release($repository, '0.3.2', $candidate);
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli', ['--tag=0.3.1']);
        $this->assertSame(0, $exit, $output);
        $this->assertSame('0.3.1', trim((string)file_get_contents($root . '/VERSION')));
        $this->assertSame($candidate, file_get_contents($root . '/update.php'));
    }

    /**
     * EN: Application rollback never restores the obsolete updater.
     *
     * HR: Povrat aplikacijskog koda ne vraća zastarjeli updater.
     */
    public function testRollbackRetainsFreshUpdaterAndGuiFailure(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $candidate = $this->candidate($repository);
        $this->release($repository, '0.3.1', $candidate);
        file_put_contents($root . '/data/fail-platform', '1');
        [$exit, $output] = $this->runUpdater($root, $tools, 'gui');
        $this->assertSame(1, $exit, $output);
        $this->assertSame('0.3.0', trim((string)file_get_contents($root . '/VERSION')));
        $this->assertSame($candidate, file_get_contents($root . '/update.php'));
        $database = new \PDO('sqlite:' . $root . '/data/database.sqlite');
        $this->assertSame(0, (int)$database->query('SELECT updates FROM fixture')->fetchColumn());
        $status = json_decode((string)file_get_contents($root . '/data/application-update-status.json'), true);
        $this->assertSame('failed', $status['state']);
        $this->assertFileDoesNotExist($root . '/data/update-maintenance.json');
    }

    /**
     * EN: A failure after migration start must not clear the maintenance guard.
     *
     * HR: Pad nakon početka migracija ne smije ukloniti zaštitu održavanja.
     */
    public function testMigrationFailureSurvivesParentHandoff(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $this->release($repository, '0.3.1', $this->candidate($repository));
        file_put_contents($root . '/data/fail-migration', '1');
        [$exit, $output] = $this->runUpdater($root, $tools, 'gui');
        $this->assertSame(1, $exit, $output);
        $this->assertFileExists($root . '/data/update-maintenance.json');
        $status = json_decode((string)file_get_contents($root . '/data/application-update-status.json'), true);
        $this->assertSame('failed', $status['state']);
    }

    /**
     * EN: An unchanged updater neither restarts nor creates a self-update backup.
     *
     * HR: Isti updater ne pokreće predaju ni stvara posebni backup.
     */
    public function testUnchangedUpdaterDoesNotRestart(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $this->release($repository, '0.3.1', (string)file_get_contents($root . '/update.php'));
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(0, $exit, $output);
        $this->assertFileDoesNotExist($root . '/data/fresh-updater.json');
        $this->assertDirectoryDoesNotExist($root . '/data/backups/updater');
    }

    /**
     * EN: An invalid updater stops the update before application changes.
     *
     * HR: Nevaljani updater zaustavlja nadogradnju prije promjene aplikacije.
     */
    public function testInvalidUpdaterStopsBeforeApplicationChanges(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $previous = (string)file_get_contents($root . '/update.php');
        $this->release($repository, '0.3.1', $this->candidate($repository) . "\nsyntax error");
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(1, $exit, $output);
        $this->assertSame($previous, file_get_contents($root . '/update.php'));
        $this->assertSame('0.3.0', trim((string)file_get_contents($root . '/VERSION')));
        $this->assertFileDoesNotExist($root . '/data/phases.log');
        $this->assertFileDoesNotExist($root . '/data/update-maintenance.json');
    }

    /**
     * EN: Read-only checks and rejecting a concurrent update must preserve existing maintenance.
     *
     * HR: Read-only provjera i odbijanje druge nadogradnje ne smiju brisati postojeće održavanje.
     */
    public function testCheckAndConcurrentUpdateDoNotMutateInstallation(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $this->release($repository, '0.3.1', $this->candidate($repository));
        $previous = (string)file_get_contents($root . '/update.php');
        file_put_contents($root . '/data/update-maintenance.json', '{"existing":true}');
        $lock = fopen($root . '/data/update.lock', 'c+');
        flock($lock, LOCK_EX);
        try {
            [$exit, $output] = $this->runUpdater($root, $tools, 'cli', ['--check']);
            $this->assertSame(0, $exit, $output);
            [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
            $this->assertSame(1, $exit, $output);
            $this->assertSame($previous, file_get_contents($root . '/update.php'));
            $this->assertSame('{"existing":true}', file_get_contents($root . '/data/update-maintenance.json'));
            $this->assertDirectoryDoesNotExist($root . '/data/backups');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * EN: A manually supplied internal flag without private descriptors is not a valid update.
     *
     * HR: Ručni interni argument bez privatnih deskriptora nije valjana nadogradnja.
     */
    public function testForgedHandoffIsRejected(): void
    {
        [$root, , $tools] = $this->fixture();
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli', ['--continue-update']);
        $this->assertSame(1, $exit, $output);
        $this->assertFileDoesNotExist($root . '/data/phases.log');
    }

    /**
     * EN: Different code with the same version or another protocol must not replace the updater.
     *
     * HR: Kod istog broja verzije ili drugog protokola ne smije zamijeniti updater.
     */
    public function testAmbiguousVersionAndIncompatibleProtocolAreRejected(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $previous = (string)file_get_contents($root . '/update.php');
        $this->release($repository, '0.3.1', $previous . "\n// different code\n");
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(1, $exit, $output);
        $this->assertSame($previous, file_get_contents($root . '/update.php'));
        $candidate = str_replace('HANDOFF_PROTOCOL = 1;', 'HANDOFF_PROTOCOL = 2;', $this->candidate($repository));
        $this->release($repository, '0.3.2', $candidate);
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(1, $exit, $output);
        $this->assertSame($previous, file_get_contents($root . '/update.php'));
        $this->assertFileDoesNotExist($root . '/data/phases.log');
    }

    /**
     * EN: An isolated Linux root test runs deployment as another UID and preserves FPM-owned settings.
     *
     * HR: Izolirani Linux root test pokreće deploy kao drugi UID i čuva FPM-ovo vlasništvo postavki.
     */
    public function testSeparateRuntimeOwnerSurvivesGuiSelfUpdate(): void
    {
        if (PHP_OS_FAMILY !== 'Linux' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Requires root in an isolated Linux test environment.');
        }

        [$root, $repository, $tools] = $this->fixture();
        mkdir($root . '/config/menu', 0770, true);
        mkdir($repository . '/config/menu', 0770, true);
        $menu = $root . '/config/menu/settings.json';
        file_put_contents($menu, '[]');
        file_put_contents($repository . '/config/menu/settings.json', '[{"id":"new-item","order":10}]');
        $this->release($repository, '0.3.1', $this->candidate($repository));
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        chown($this->temporaryRoot, 65534);
        chgrp($this->temporaryRoot, 65534);
        foreach ($iterator as $item) {
            chown($item->getPathname(), 65534);
            chgrp($item->getPathname(), 65534);
        }

        foreach ([$menu, $root . '/config/private.php'] as $runtimeFile) {
            chown($runtimeFile, 65533);
            chgrp($runtimeFile, 65534);
            chmod($runtimeFile, 0660);
        }

        $inode = fileinode($menu);
        $this->identityCommand = ['setpriv', '--reuid=65534', '--regid=65534', '--clear-groups', '--'];
        [$exit, $output] = $this->runUpdater($root, $tools, 'gui', [], 0007);
        $this->assertSame(0, $exit, $output);
        clearstatcache();
        $this->assertSame(65534, fileowner($root . '/update.php'));
        $this->assertSame(65533, fileowner($menu));
        $this->assertSame($inode, fileinode($menu));
        $this->assertSame(0660, fileperms($menu) & 0777);
        $this->assertSame(65533, fileowner($root . '/config/private.php'));
        $this->assertStringContainsString('new-item', (string)file_get_contents($menu));
        $proof = json_decode((string)file_get_contents($root . '/data/fresh-updater.json'), true);
        $this->assertSame(65534, $proof['uid']);
        $this->assertTrue($proof['lock_held']);
    }

    /**
     * EN: The real worker detaches the GUI job and returns control before update completion.
     *
     * HR: Stvarni worker odvaja GUI posao i vraća kontrolu prije završetka nadogradnje.
     */
    public function testWorkerStartsFreshUpdaterInBackground(): void
    {
        if (!function_exists('pcntl_fork') || !function_exists('pcntl_exec')) {
            $this->markTestSkipped('The GUI launcher requires CLI pcntl.');
        }

        [$root, $repository, $tools] = $this->fixture();
        $this->prepareWorker($root);
        $this->release($repository, '0.3.1', $this->candidate($repository));
        $id = bin2hex(random_bytes(24));
        file_put_contents($root . '/data/setup-requests/' . $id . '.json', json_encode([
            'id' => $id, 'action' => 'application-update-start', 'parameters' => ['locale' => 'en', 'tag' => '0.3.1'],
        ]));
        $path = getenv('PATH');
        putenv('PATH=' . $tools . PATH_SEPARATOR . $path);
        try {
            [$exit, $output] = $this->process([PHP_BINARY, $root . '/scripts/setup_worker.php', $id], $root);
            $this->assertSame(0, $exit, $output);
            $resultPath = $root . '/data/setup-requests/' . $id . '.result.json';
            $result = json_decode((string)file_get_contents($resultPath), true);
            $this->assertTrue($result['ok'], $result['message']);
            $this->waitForJob($root);
        } finally {
            putenv($path === false ? 'PATH' : 'PATH=' . $path);
        }

        $status = json_decode((string)file_get_contents($root . '/data/application-update-status.json'), true);
        $log = (string)file_get_contents($root . '/data/logs/application-update.log');
        $this->assertSame('success', $status['state'], $log);
        $this->assertFileExists($root . '/data/fresh-updater.json');
    }

    /**
     * EN: A fixed helper delegates the CLI job to another code owner without retaining privileges.
     *
     * HR: Fiksni helper predaje CLI posao drugom vlasniku koda, bez zadržavanja privilegija.
     */
    public function testMaintainerCliDelegatesToDeploymentWorker(): void
    {
        if (PHP_OS_FAMILY !== 'Linux' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Requires root in an isolated Linux test environment.');
        }

        [$root, $repository, $tools] = $this->fixture();
        $this->prepareWorker($root, true);
        $this->release($repository, '0.3.1', $this->candidate($repository));
        $helper = $this->temporaryRoot . '/fixed-helper';
        file_put_contents($helper, "#!/bin/sh\nPATH=" . escapeshellarg($tools . ':' . getenv('PATH'))
            . "\nexport PATH\nexec env -i PATH=\"\$PATH\" setpriv --reuid=65534 --regid=65534 --clear-groups -- "
            . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/scripts/setup_worker.php') . ' "$1"' . "\n");
        chmod($helper, 0755);
        mkdir($root . '/private-config', 0700);
        file_put_contents($root . '/framework.php', '<?php return ' . var_export([
            'HPH_CONFIG_PATH' => $root . '/config', 'HPH_CONFIG_OVERRIDE_PATHS' => $root . '/private-config',
        ], true) . ';');
        file_put_contents($root . '/private-config/setup.php', '<?php return ' . var_export([
            'helper' => $helper, 'request_dir' => $root . '/data/setup-requests', 'direct_local_testing' => false,
        ], true) . ';');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        chmod($this->temporaryRoot, 0755);
        foreach ($iterator as $item) {
            if (!$item->isLink() && $item->getPathname() !== $helper) {
                chown($item->getPathname(), 65534);
                chgrp($item->getPathname(), 65534);
            }
        }

        chmod($root . '/data/setup-requests', 02770);
        // EN: The initial root process reads its own fixture now owned by the deploy UID.
        // Trust applies only to this repository and test, not global Git configuration.
        //
        // HR: Početni root proces čita vlastitu fixture sada u vlasništvu deploy UID-a.
        // Povjerenje vrijedi samo za ovaj repozitorij i ovaj test, ne za globalni Git.
        $gitConfig = $this->temporaryRoot . '/git-safe.config';
        file_put_contents($gitConfig, '[safe]' . "\n\tdirectory = " . $repository . '/.git' . "\n");
        $previousGitConfig = getenv('GIT_CONFIG_GLOBAL');
        putenv('GIT_CONFIG_GLOBAL=' . $gitConfig);
        try {
            [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        } finally {
            putenv($previousGitConfig === false ? 'GIT_CONFIG_GLOBAL' : 'GIT_CONFIG_GLOBAL=' . $previousGitConfig);
        }

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('handing the update to the restricted deploy helper', $output);
        $proof = json_decode((string)file_get_contents($root . '/data/fresh-updater.json'), true);
        $this->assertSame(65534, $proof['uid']);
        $this->assertTrue($proof['lock_held']);
    }

    /**
     * EN: Prepares the real worker; another UID receives a private copy independent of access to the CI user's home.
     *
     * HR: Priprema pravi worker; drugi UID dobiva privatnu kopiju bez ovisnosti o pristupu CI korisnikovom homeu.
     */
    private function prepareWorker(string $root, bool $isolatedVendor = false): void
    {
        $sourceRoot = dirname(__DIR__, 3);
        if ($isolatedVendor) {
            // EN: The isolated copy's autoload also needs the application bootstrap, not only vendor/src.
            //
            // HR: Autoload privatne kopije treba i aplikacijski bootstrap, ne samo vendor/src.
            copy($sourceRoot . '/bootstrap.php', $root . '/bootstrap.php');
            foreach (['vendor', 'src'] as $directory) {
                [$exit, $output] = $this->process([
                    'rsync', '--archive', '--copy-links', '--chmod=D0755,F0644',
                    $sourceRoot . '/' . $directory . '/', $root . '/' . $directory . '/',
                ]);
                $this->assertSame(0, $exit, $output);
            }
        } else {
            // EN: The fixture already contains the minimal resolver; the worker needs real autoload.
            //
            // HR: Fixture već sadrži minimalni resolver; worker treba stvarni autoload.
            symlink($sourceRoot . '/vendor/autoload.php', $root . '/vendor/autoload.php');
        }

        copy(dirname(__DIR__, 3) . '/scripts/setup_worker.php', $root . '/scripts/setup_worker.php');
        mkdir($root . '/data/setup-requests', 0770, true);
        file_put_contents($root . '/config/setup.php', '<?php return ' . var_export([
            'request_dir' => $root . '/data/setup-requests', 'direct_local_testing' => true,
        ], true) . ';');
    }

    /**
     * EN: Waits for completion only of this fixture's own GUI job.
     *
     * HR: Čeka završetak samo vlastitog privremenog GUI posla.
     */
    private function waitForJob(string $root): void
    {
        $deadline = microtime(true) + 30;
        do {
            $status = json_decode((string)@file_get_contents($root . '/data/application-update-status.json'), true);
            if (is_array($status) && in_array($status['state'] ?? null, ['success', 'failed'], true)) {
                return;
            }

            usleep(100000);
        } while (microtime(true) < $deadline);

        $this->fail('The fixture background update did not finish within 30 seconds.');
    }

    /**
     * EN: Prepares an isolated Git repository, installation, and test Composer without network access.
     *
     * HR: Priprema izolirano Git spremište, instalaciju i testni Composer bez mreže.
     *
     * @return array{string,string,string}
     */
    private function fixture(): array
    {
        $this->temporaryRoot = sys_get_temp_dir() . '/simbioza-self-update-test-' . bin2hex(random_bytes(6));
        $root = $this->temporaryRoot . '/installation';
        $repository = $this->temporaryRoot . '/repository';
        $tools = $this->temporaryRoot . '/tools';
        $directories = [
            $root . '/config', $root . '/public', $root . '/data', $root . '/scripts',
            $repository . '/config', $repository . '/public', $repository . '/scripts', $tools,
        ];
        foreach ($directories as $directory) {
            mkdir($directory, 0700, true);
        }

        // EN: The standalone updater uses the real resolver without full vendor/application bootstrap.
        //
        // HR: Samostalni updater koristi stvarni resolver, bez punog vendora/aplikacije.
        $framework = $root . '/vendor/aaieduhr/heartphrame-framework/src';
        $frameworkFiles = ['CodeBook/EnvKeyEnum.php', 'Config/ConfigPathResolver.php', 'Config/ConfigArrayMerger.php'];
        foreach ($frameworkFiles as $file) {
            if (!is_dir(dirname($framework . '/' . $file))) {
                mkdir(dirname($framework . '/' . $file), 0700, true);
            }

            copy(dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-framework/src/' . $file, $framework . '/' . $file);
            if (!is_dir(dirname($tools . '/framework/' . $file))) {
                mkdir(dirname($tools . '/framework/' . $file), 0700, true);
            }

            copy($framework . '/' . $file, $tools . '/framework/' . $file);
        }

        $updater = $this->updaterCode($repository, '1.0.0');
        file_put_contents($root . '/update.php', $updater);
        file_put_contents($root . '/VERSION', '0.3.0');
        file_put_contents($root . '/composer.json', '{"name":"aaieduhr/simbioza","require":{}}');
        file_put_contents($root . '/config/app.php', '<?php return [];');
        file_put_contents($root . '/config/modules.php', '<?php return ["enabled" => [], "removed" => []];');
        file_put_contents($root . '/config/private.php', '<?php return ["keep" => true];');
        file_put_contents($root . '/public/index.php', '<?php echo "old";');
        file_put_contents($root . '/data/document.txt', 'kept');
        $database = new \PDO('sqlite:' . $root . '/data/database.sqlite');
        $database->exec('CREATE TABLE fixture (updates INTEGER); INSERT INTO fixture VALUES (0)');

        $job = (string)file_get_contents(dirname(__DIR__, 3) . '/scripts/application_update_job.php');
        $job = str_replace(
            'writeApplicationUpdateStatus($statusPath, [',
            'file_put_contents($root . "/data/progress.log",'
            . ' json_encode($stage ?? "preparing") . "\n", FILE_APPEND);'
            . ' writeApplicationUpdateStatus($statusPath, [',
            $job,
        );
        // EN: Capture root in the real callback for test progress recording.
        //
        // HR: Varijabla root je u stvarnom callbacku zatvorena kroz use.
        $job = str_replace('use ($statusPath)', 'use ($statusPath, $root)', $job);
        file_put_contents($root . '/scripts/application_update_job.php', $job);
        copy($root . '/scripts/application_update_job.php', $repository . '/scripts/application_update_job.php');
        file_put_contents($repository . '/composer.json', '{"name":"aaieduhr/simbioza","require":{}}');
        file_put_contents($repository . '/config/app.php', '<?php return [];');
        file_put_contents($repository . '/public/index.php', '<?php echo "new";');
        file_put_contents(
            $repository . '/scripts/update_bundled_assets.php',
            '<?php file_put_contents(dirname(__DIR__) . "/data/phases.log", "bundled-assets\n", FILE_APPEND);',
        );
        $composer = <<<'PHP'
#!/usr/bin/env php
<?php
$root = getcwd();
file_put_contents($root . '/data/phases.log', 'composer-' . $argv[1] . "\n", FILE_APPEND);
if ($argv[1] === 'update') {
    file_put_contents($root . '/composer.lock', '{"packages":[],"packages-dev":[]}');
}
if ($argv[1] === 'check-platform-reqs' && is_file($root . '/data/fail-platform')) {
    exit(2);
}
if ($argv[1] === 'install') {
    if (!is_dir($root . '/vendor/bin')) { mkdir($root . '/vendor/bin', 0700, true); }
    foreach (['CodeBook/EnvKeyEnum.php', 'Config/ConfigPathResolver.php', 'Config/ConfigArrayMerger.php'] as $file) {
        $target = $root . '/vendor/aaieduhr/heartphrame-framework/src/' . $file;
        if (!is_dir(dirname($target))) { mkdir(dirname($target), 0755, true); }
        copy(__DIR__ . '/framework/' . $file, $target);
    }
    file_put_contents($root . '/vendor/autoload.php', <<<'AUTOLOAD'
<?php
spl_autoload_register(static function ($class) {
    $prefixes = [
        'App\\' => dirname(__DIR__) . '/src/',
        'HeartPhrame\\' => __DIR__ . '/aaieduhr/heartphrame-framework/src/',
    ];
    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) { require $file; }
        }
    }
});
AUTOLOAD);
    $hph = <<<'HPH'
#!/usr/bin/env php
<?php
$root = getcwd();
file_put_contents($root . '/data/phases.log', 'hph-' . $argv[2] . "\n", FILE_APPEND);
if ($argv[2] === 'migrate-status' && is_file($root . '/data/fail-preflight')) {
    exit(4);
}
if ($argv[2] === 'migrate-up') {
    if (is_file($root . '/data/fail-migration')) { exit(3); }
    (new PDO('sqlite:' . $root . '/data/database.sqlite'))->exec('UPDATE fixture SET updates = updates + 1');
}
HPH;
    file_put_contents($root . '/vendor/bin/hph', $hph);
    chmod($root . '/vendor/bin/hph', 0700);
}
PHP;
        file_put_contents($tools . '/composer', $composer);
        chmod($tools . '/composer', 0700);
        $this->process(['git', 'init', '--quiet', $repository]);
        $this->process(['git', '-C', $repository, 'config', 'user.email', 'fixture@example.invalid']);
        $this->process(['git', '-C', $repository, 'config', 'user.name', 'Updater fixture']);
        return [$root, $repository, $tools];
    }

    /**
     * EN: Adds fresh-process evidence only to the new updater.
     *
     * HR: Dodaje dokaz svježeg PHP procesa samo u novi updater.
     */
    private function candidate(string $repository): string
    {
        $code = $this->updaterCode($repository, '1.0.1');

        $proof = <<<'PHP'
                $probe = fopen($this->appRoot . '/data/update.lock', 'c+');
                $blocked = !flock($probe, LOCK_EX | LOCK_NB);
                fclose($probe);
                file_put_contents($this->appRoot . '/data/fresh-updater.json', json_encode([
                    'version' => self::UPDATER_VERSION, 'pid' => getmypid(),
                    'parent_pid' => posix_getppid(), 'lock_held' => $blocked,
                    'uid' => posix_geteuid(),
                ]));
                file_put_contents($this->appRoot . '/data/phases.log', "fresh-updater\n", FILE_APPEND);
                $this->progress('fresh_updater', 9, 'Fresh updater is running.');
PHP;
        return str_replace('$this->receiveUpdaterHandoff();', '$this->receiveUpdaterHandoff();' . "\n" . $proof, $code);
    }

    /**
     * EN: Isolates the repository URL and independent fixture updater version.
     *
     * HR: Izolira URL i neovisnu testnu verziju updatera.
     */
    private function updaterCode(string $repository, string $version): string
    {
        $code = str_replace(
            'https://github.com/kmihalj/Simbioza.git',
            $repository,
            (string)file_get_contents(dirname(__DIR__, 3) . '/update.php'),
        );
        return (string)preg_replace(
            "/UPDATER_VERSION = '[0-9]+\\.[0-9]+\\.[0-9]+'/",
            "UPDATER_VERSION = '" . $version . "'",
            $code,
        );
    }

    /**
     * EN: Creates a local annotated release tag.
     *
     * HR: Stvara lokalni anotirani release tag.
     */
    private function release(string $repository, string $tag, string $updater): void
    {
        file_put_contents($repository . '/update.php', $updater);
        file_put_contents($repository . '/VERSION', $tag);
        $this->process(['git', '-C', $repository, 'add', '.']);
        $this->process(['git', '-C', $repository, 'commit', '--quiet', '-m', $tag]);
        $this->process(['git', '-C', $repository, 'tag', '-a', $tag, '-m', $tag]);
    }

    /**
     * EN: Runs the real entry point with a temporary PATH and umask.
     *
     * HR: Pokreće pravi entry point uz privremeni PATH i umask.
     *
     * @param list<string> $arguments
     * @return array{int,string}
     */
    private function runUpdater(
        string $root,
        string $tools,
        string $entry,
        array $arguments = [],
        int $mask = 0022,
    ): array {
        $previous = umask($mask);
        $path = getenv('PATH');
        putenv('PATH=' . $tools . PATH_SEPARATOR . $path);
        try {
            return $this->process([
                ...$this->identityCommand,
                PHP_BINARY,
                $root . ($entry === 'gui' ? '/scripts/application_update_job.php' : '/update.php'),
                '--lang=en', ...$arguments,
            ], $root);
        } finally {
            umask($previous);
            putenv($path === false ? 'PATH' : 'PATH=' . $path);
        }
    }

    /**
     * EN: Captures subprocess output so tests do not emit uncontrolled output.
     *
     * HR: Hvata izlaz subprocessa kako testovi ne proizvode nekontrolirani ispis.
     *
     * @param list<string> $command
     * @return array{int,string}
     */
    private function process(array $command, ?string $directory = null): array
    {
        // EN: An isolated installation must not inherit HFC paths from the autoloader.
        //
        // HR: Izolirana instalacija ne smije naslijediti HFC putanje iz autoloadera.
        $environment = getenv();
        if ($directory !== null && is_dir($directory . '/config')) {
            $environment['HPH_APP_PATH'] = $directory;
            $environment['HPH_CONFIG_PATH'] = $directory . '/config';
            $environment['HPH_CONFIG_OVERRIDE_PATHS'] = '';
            if (is_file($directory . '/framework.php')) {
                unset($environment['HPH_CONFIG_PATH'], $environment['HPH_CONFIG_OVERRIDE_PATHS']);
            }
        }

        $process = proc_open($command, [
            0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1],
        ], $pipes, $directory, $environment);
        $this->assertIsResource($process);
        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return [proc_close($process), $output];
    }

    /**
     * EN: External current-format configuration remains intact on both success and rollback.
     *
     * HR: Vanjska konfiguracija u aktualnom formatu ostaje netaknuta nakon uspjeha i povrata.
     */
    #[DataProvider('configurationModes')]
    public function testCurrentConfigurationIsPreserved(string $entry, bool $fail, bool $inside): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $external = $inside ? $root . '/private-config/layers/site' : $this->temporaryRoot . '/external';
        mkdir($external, 0700, true);
        rename($root . '/config/modules.php', $external . '/modules.php');
        $privateConfiguration = [
            'auth/auth.json' => '{"providers":{}}',
            'api/api.php' => '<?php return ["rate_limit_per_minute"=>37];',
            'backup/backup.php' => '<?php return ["archive_dir"=>"data/site-archives"];',
            'backup/backup-providers.php' => '<?php return ["site-provider"=>true];',
            'calendar/calendar.php' => '<?php return ["timezone"=>"Europe/Zagreb"];',
            'email/email.json' => '{"sender":{"name":"Site-specific sender"}}',
            'editor-html/editor-html.json' => '{"storage":{"driver":"database"}}',
            'editor-html/external-scripts.json' => '{"scripts":[]}',
            'workspace/workspace.json' => '{"homepage":{"type":"summaries"}}',
            'workspace-search/workspace-search.php' => '<?php return ["page_size"=>37];',
            'confluence-import/confluence-import.php' => '<?php return ["batch_size"=>37];',
            'menu/menu.php' => '<?php return ["site-specific"=>true];',
            'menu/top.json' => '[{"id":"site-home","label":"Site home"}]',
            'menu/settings.json' => '[{"id":"site-settings","label":"Site settings"}]',
            'theme/theme.php' => '<?php return ["site-specific"=>true];',
            'theme/themes.json' => '[{"id":"simbioza","light":{"colors":{"muted_text":"#D6D6D6"}}}]',
            'theme/settings.json' => '{"active_theme":"simbioza","mode_policy":"dark"}',
        ];
        foreach ($privateConfiguration as $path => $bytes) {
            if (!is_dir(dirname($external . '/' . $path))) {
                mkdir(dirname($external . '/' . $path), 0700, true);
            }

            file_put_contents($external . '/' . $path, $bytes);
            chmod($external . '/' . $path, 0640);
        }

        $before = [];
        foreach (['modules.php', ...array_keys($privateConfiguration)] as $path) {
            $before[$path] = [file_get_contents($external . '/' . $path), stat($external . '/' . $path)];
        }

        copy(dirname(__DIR__, 3) . '/bootstrap.php', $root . '/bootstrap.php');
        copy($root . '/bootstrap.php', $repository . '/bootstrap.php');
        file_put_contents($root . '/framework.php', '<?php return ' . var_export([
            'HPH_CONFIG_PATH' => $external, 'HPH_CONFIG_OVERRIDE_PATHS' => '',
        ], true) . ';');
        if ($fail) {
            file_put_contents($root . '/data/fail-preflight', 'yes');
        }

        $this->release($repository, '0.3.1', $this->candidate($repository));
        [$exit, $output] = $this->runUpdater($root, $tools, $entry);
        $this->assertSame($fail ? 1 : 0, $exit, $output);
        $this->assertStringNotContainsString('cannot delete', $output);
        $this->assertStringNotContainsString('not empty', $output);
        foreach ($before as $path => [$bytes, $metadata]) {
            clearstatcache(true, $external . '/' . $path);
            $this->assertSame($bytes, file_get_contents($external . '/' . $path));
            foreach (['ino', 'uid', 'gid', 'mode', 'mtime', 'ctime'] as $key) {
                $this->assertSame($metadata[$key], stat($external . '/' . $path)[$key]);
            }
        }

        $database = new \PDO('sqlite:' . $root . '/data/database.sqlite');
        $this->assertSame($fail ? 0 : 1, (int)$database->query('SELECT updates FROM fixture')->fetchColumn());
        $this->assertFileDoesNotExist($root . '/data/update-maintenance.json');
        if ($inside) {
            $archives = glob($root . '/data/backups/application-updates/*.tar.gz');
            [$tarExit, $entries] = $this->process(['tar', '-tzf', $archives[0]]);
            $this->assertSame(0, $tarExit);
            $this->assertStringNotContainsString('private-config/layers/site', $entries);
        }
    }

    /** @return iterable<string,array{string,bool,bool}> */
    public static function configurationModes(): iterable
    {
        yield 'external CLI' => ['cli', false, false];
        yield 'external GUI' => ['gui', false, false];
        yield 'inside CLI' => ['cli', false, true];
        yield 'external CLI rollback' => ['cli', true, false];
        yield 'inside GUI rollback' => ['gui', true, true];
    }

    /**
     * EN: Private module directories absent from a release survive sync and
     * rollback without deletion warnings; shared directories still receive examples.
     *
     * HR: Privatni modulski direktoriji kojih nema u izdanju prežive sinkronizaciju
     * i povrat bez upozorenja o brisanju; zajednički direktoriji i dalje dobivaju primjere.
     */
    #[DataProvider('privateDirectoryModes')]
    public function testPrivateConfigurationDirectoriesSurviveWithoutWarnings(
        string $entry,
        int $mode,
        int $mask,
        bool $fail,
    ): void {
        [$root, $repository, $tools] = $this->fixture();
        $private = ['demo/demo.json', 'site-module/profiles/nested/profile.json', 'mixed/settings.json'];
        $before = [];
        foreach ($private as $path) {
            $target = $root . '/config/' . $path;
            $this->assertTrue(mkdir(dirname($target), 0700, true));
            file_put_contents($target, '{"private":true}');
            chmod($target, 0600);
            $before[$path] = stat($target);
        }

        $this->assertTrue(mkdir($repository . '/config/mixed', 0700, true));
        file_put_contents($root . '/config/mixed/settings.json.dist', 'old example');
        file_put_contents($repository . '/config/mixed/settings.json.dist', 'new example');
        file_put_contents($repository . '/config/mixed/new.php.dist', 'new release example');
        file_put_contents($root . '/config/mixed/obsolete.php.dist', 'obsolete example');
        file_put_contents($root . '/obsolete.php', 'obsolete code');
        if ($fail) {
            file_put_contents($root . '/data/fail-preflight', 'yes');
        }

        chmod($root . '/update.php', $mode);
        $this->release($repository, '0.3.1', $this->candidate($repository));
        [$exit, $output] = $this->runUpdater($root, $tools, $entry, [], $mask);
        $this->assertSame($fail ? 1 : 0, $exit, $output);
        $this->assertStringNotContainsString('cannot delete', $output);
        $this->assertStringNotContainsString('not empty', $output);
        foreach ($before as $path => $metadata) {
            $target = $root . '/config/' . $path;
            clearstatcache(true, $target);
            $this->assertSame('{"private":true}', file_get_contents($target));
            foreach (['ino', 'uid', 'gid', 'mode'] as $key) {
                $this->assertSame($metadata[$key], stat($target)[$key], $path . ': ' . $key);
            }
        }

        $this->assertSame(
            $fail ? 'old example' : 'new example',
            file_get_contents($root . '/config/mixed/settings.json.dist'),
        );
        if ($fail) {
            $this->assertFileExists($root . '/config/mixed/obsolete.php.dist');
            $this->assertFileExists($root . '/obsolete.php');
            $this->assertFileDoesNotExist($root . '/config/mixed/new.php.dist');
        } else {
            $this->assertFileDoesNotExist($root . '/config/mixed/obsolete.php.dist');
            $this->assertFileDoesNotExist($root . '/obsolete.php');
            $this->assertSame('new release example', file_get_contents($root . '/config/mixed/new.php.dist'));
        }

        $this->assertFileDoesNotExist($root . '/data/update-maintenance.json');
    }

    /** @return iterable<string,array{string,int,int,bool}> */
    public static function privateDirectoryModes(): iterable
    {
        foreach (self::installationModes() as $name => [$entry, $mode, $mask]) {
            yield $name => [$entry, $mode, $mask, false];
            yield $name . ' rollback' => [$entry, $mode, $mask, true];
        }
    }

    /**
     * EN: Directory protection never suppresses a real rsync failure or its diagnostic.
     *
     * HR: Zaštita direktorija nikada ne skriva stvarnu rsync pogrešku ni njezinu poruku.
     */
    public function testDirectoryProtectionDoesNotHideRsyncFailures(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        file_put_contents($tools . '/rsync', "#!/bin/sh\nprintf '%s\\n' 'fixture-rsync-failure' >&2\nexit 23\n");
        chmod($tools . '/rsync', 0700);
        $this->release($repository, '0.3.1', $this->candidate($repository));

        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('fixture-rsync-failure', $output);
        $this->assertStringContainsString('Command failed (23)', $output);
    }

    public function testConfigurationDirectorySymlinkSurvivesUpdate(): void
    {
        [$root, $repository, $tools] = $this->fixture();
        $private = $this->temporaryRoot . '/private-linked-config';
        rename($root . '/config', $private);
        symlink($private, $root . '/config');
        $original = file_get_contents($private . '/private.php');
        $metadata = stat($private . '/private.php');
        $this->release($repository, '0.3.1', $this->candidate($repository));
        [$exit, $output] = $this->runUpdater($root, $tools, 'cli');
        $this->assertSame(0, $exit, $output);
        $this->assertTrue(is_link($root . '/config'));
        $this->assertSame($original, file_get_contents($private . '/private.php'));
        $this->assertSame($metadata['ino'], fileinode($private . '/private.php'));
        $this->assertSame($metadata['mode'], fileperms($private . '/private.php'));
    }
}
