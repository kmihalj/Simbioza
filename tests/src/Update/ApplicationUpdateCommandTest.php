<?php

declare(strict_types=1);

namespace Tests\Update;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Simbioza\Update\ApplicationUpdateCommand;

use function dirname;
use function file_get_contents;
use function strpos;
use function trim;

#[CoversNothing]
final class ApplicationUpdateCommandTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    /** HR: Učitava samostalni updater bez njegova pokretanja. EN: Loads the standalone updater without running it. */
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 3) . '/update.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }

                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($directory);
        }

        $this->temporaryDirectories = [];
    }

    /**
     * HR: Updater bira najveći stabilni semantički tag i zanemaruje prerelease i dereferencirane tagove.
     *
     * EN: The updater chooses the greatest stable semantic tag and ignores prerelease and dereferenced tags.
     */
    public function testGreatestStableTagIsSelected(): void
    {
        $remoteOutput = <<<'TAGS'
111 refs/tags/0.1.9
222 refs/tags/0.1.10
333 refs/tags/0.2.0-rc1
444 refs/tags/0.1.10^{}
555 refs/tags/v0.2.0
TAGS;

        $this->assertSame('v0.2.0', ApplicationUpdateCommand::greatestStableTag($remoteOutput));
    }

    /**
     * HR: Updater jasno prepoznaje izlaz u kojem nema stabilnog izdanja.
     *
     * EN: The updater clearly recognises output that contains no stable release.
     */
    public function testMissingStableTagReturnsNull(): void
    {
        $this->assertNull(ApplicationUpdateCommand::greatestStableTag("111 refs/tags/0.2.0-rc1\n"));
    }

    /**
     * HR: Lokalni demo-paket ostaje izvan javnog kataloga i ne smije zamijeniti ovisnost izdanja.
     * EN: A local demo package stays outside the public catalog and cannot replace a release dependency.
     */
    public function testLocalComposerRequirementsAreMergedWithoutOverridingRelease(): void
    {
        $release = [
            'require' => ['aaieduhr/heartphrame-framework' => '^0.0.25'],
            'repositories' => [['type' => 'vcs', 'url' => 'https://example.invalid/framework.git']],
        ];
        $local = [
            'repositories' => [[
                'type' => 'path', 'url' => sys_get_temp_dir(), 'options' => ['symlink' => false],
            ]],
            'require' => ['local/simbioza-module-demo' => '0.1.0'],
        ];
        $merged = ApplicationUpdateCommand::mergeLocalComposerRequirements($release, $local);

        $this->assertSame('^0.0.25', $merged['require']['aaieduhr/heartphrame-framework']);
        $this->assertSame('0.1.0', $merged['require']['local/simbioza-module-demo']);
        $this->assertSame('path', $merged['repositories'][0]['type']);
        $this->assertSame('vcs', $merged['repositories'][1]['type']);
        $this->assertArrayNotHasKey('local/simbioza-module-demo', $release['require']);

        $local['require'] = ['aaieduhr/heartphrame-framework' => '0.0.1'];
        $this->expectException(\RuntimeException::class);
        ApplicationUpdateCommand::mergeLocalComposerRequirements($release, $local);
    }

    /** HR: Udaljeni privatni kod ne smije se prikriti kao lokalni path paket. EN: Remote private code cannot masquerade as a local path package. */
    public function testLocalComposerOverrideRejectsRemoteRepository(): void
    {
        $this->expectException(\RuntimeException::class);
        ApplicationUpdateCommand::mergeLocalComposerRequirements(['require' => []], [
            'repositories' => [[
                'type' => 'vcs', 'url' => 'https://example.invalid/demo.git',
            ]],
            'require' => ['local/simbioza-module-demo' => '0.1.0'],
        ]);
    }

    /** HR: Stvarni korak updatera vraća privatni paket i čitljive dozvole manifesta. EN: The updater step restores the private package and a web-readable manifest. */
    public function testLocalComposerRestorePreservesPrivatePackageOnDisk(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-private-package-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root, 0770, true));
        file_put_contents($root . '/composer.json', json_encode([
            'require' => ['aaieduhr/heartphrame-framework' => '^0.0.25'],
        ], JSON_THROW_ON_ERROR));
        file_put_contents($root . '/composer.local.json', json_encode([
            'repositories' => [[
                'type' => 'path', 'url' => $root, 'options' => ['symlink' => false],
            ]],
            'require' => ['local/simbioza-module-demo' => '0.1.0'],
        ], JSON_THROW_ON_ERROR));

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $restore = new \ReflectionMethod($command, 'restoreLocalComposerRequirements');
        $previousUmask = umask(0007);
        try {
            $restore->invoke($command);
        } finally {
            umask($previousUmask);
        }

        $manifest = json_decode((string)file_get_contents($root . '/composer.json'), true);
        $this->assertSame('0.1.0', $manifest['require']['local/simbioza-module-demo'] ?? null);
        $this->assertSame('^0.0.25', $manifest['require']['aaieduhr/heartphrame-framework'] ?? null);
        $this->assertSame(0664, fileperms($root . '/composer.json') & 07777);
    }

    /**
     * HR: Nadogradnja čuva opcionalne module iz manifesta, locka i trajnog stanja.
     * EN: An update preserves optional modules from the manifest, lock, and persistent state.
     */
    public function testSelectedOptionalModulesSurviveLegacyAndDisabledStates(): void
    {
        $release = [
            'suggest' => ['vendor/api' => 'API', 'vendor/audit' => 'Audit', 'vendor/theme' => 'Theme'],
            'extra' => ['simbioza' => ['optional-modules' => [
                'vendor/api' => '^2.0', 'vendor/audit' => '^3.0', 'vendor/theme' => '^4.0',
            ]]],
        ];
        $selected = ApplicationUpdateCommand::selectedOptionalRequirements(
            ['require' => ['vendor/api' => '^1.0']],
            ['packages' => [['name' => 'vendor/audit']]],
            ['enabled' => ['vendor/theme']],
            $release,
        );

        $this->assertSame([
            'vendor/api' => '^2.0',
            'vendor/audit' => '^3.0',
            'vendor/theme' => '^4.0',
        ], $selected);
    }

    /**
     * HR: Release sinkronizacija i rollback čuvaju instalirani jezik, ali ažuriraju ugrađene jezike.
     * EN: Release sync and rollback preserve an installed locale while updating bundled locales.
     */
    public function testSourceSyncPreservesInstalledLanguageFilesAndRegistry(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-language-' . bin2hex(random_bytes(8));
        mkdir($root . '/config', 0770, true);
        $this->temporaryDirectories[] = $root;
        file_put_contents($root . '/config/languages.php', <<<'PHP'
<?php
return ['en' => [], 'hr' => [], 'de' => [], 'sr-cyrl' => []];
PHP);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $method = new \ReflectionMethod(ApplicationUpdateCommand::class, 'sourceSyncExcludes');
        $excludes = $method->invoke($command);
        $this->assertContains('/config/languages.php', $excludes);
        $this->assertContains('/lang/de.php', $excludes);
        $this->assertContains('/lang/sr-cyrl.php', $excludes);
        $this->assertNotContains('/lang/en.php', $excludes);
        $this->assertNotContains('/lang/hr.php', $excludes);
    }

    /**
     * HR: Vraćanje opcionalnih modula ostavlja manifest čitljivim odvojenom FPM korisniku.
     * EN: Restoring optional modules leaves the manifest readable by a separate FPM user.
     */
    public function testRestoredOptionalModulesLeaveComposerManifestWebReadable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-composer-manifest-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root, 0770, true));
        file_put_contents($root . '/composer.json', json_encode([
            'require' => ['vendor/core' => '^1.0'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $selected = new \ReflectionProperty($command, 'selectedOptionalRequirements');
        $selected->setValue($command, ['vendor/theme' => '^2.0']);

        $restore = new \ReflectionMethod($command, 'restoreSelectedOptionalRequirements');

        $previousUmask = umask(0007);
        try {
            $restore->invoke($command);
        } finally {
            umask($previousUmask);
        }

        $manifest = json_decode((string)file_get_contents($root . '/composer.json'), true);
        $this->assertIsArray($manifest);
        $this->assertSame('^2.0', $manifest['require']['vendor/theme'] ?? null);
        $this->assertSame(0664, fileperms($root . '/composer.json') & 07777);
    }

    /**
     * HR: FPM CLI delegira samo kada helper cilja istu instalaciju i pozivatelj
     *     nije vlasnik koda; vlasnik i ne-FPM instalacija ostaju izravni.
     * EN: FPM CLI delegates only when the helper targets the same installation
     *     and the caller is not the code owner; owner and non-FPM runs remain direct.
     */
    public function testFpmUpdateDelegationIsBoundToOwnerAndInstallation(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-helper-' . bin2hex(random_bytes(6));
        $other = $root . '-other';
        $this->temporaryDirectories[] = $root;
        $this->temporaryDirectories[] = $other;
        $this->assertTrue(mkdir($root . '/scripts', 0770, true));
        $this->assertTrue(mkdir($other . '/scripts', 0770, true));
        file_put_contents($root . '/scripts/setup_worker.php', "<?php\n");
        file_put_contents($other . '/scripts/setup_worker.php', "<?php\n");
        $helper = $root . '/simbioza-setup';
        file_put_contents(
            $helper,
            "#!/bin/sh\nexec /usr/bin/php " . escapeshellarg($root . '/scripts/setup_worker.php') . " \"\$1\"\n",
        );
        chmod($helper, 0755);

        $targets = new \ReflectionMethod(ApplicationUpdateCommand::class, 'helperTargetsInstallation');
        $requires = new \ReflectionMethod(ApplicationUpdateCommand::class, 'requiresHelperDelegation');
        $this->assertTrue($targets->invoke(null, $helper, $root));
        $this->assertFalse($targets->invoke(null, $helper, $other));
        $this->assertTrue($requires->invoke(null, 501, 502, true));
        $this->assertFalse($requires->invoke(null, 501, 501, true));
        $this->assertFalse($requires->invoke(null, 501, 502, false));
    }

    /**
     * HR: FPM konfigurator daje istom validirajućem helperu ograničeno pravo
     *     i web korisniku i grupi održavatelja te ga osvježava pri finalizaciji.
     * EN: The FPM configurator grants the same validating helper narrowly to
     *     both the web user and maintainer group and refreshes it on finalize.
     */
    public function testFpmConfiguratorAuthorizesGuiAndMaintainerCli(): void
    {
        $source = (string)file_get_contents(dirname(__DIR__, 3) . '/scripts/configure_fpm_setup.php');
        $this->assertStringContainsString(
            'fpm-simbioza ALL=(root) NOPASSWD: /usr/local/sbin/simbioza-setup *',
            $source,
        );
        $this->assertStringContainsString(
            '%deploy-simbioza ALL=(root) NOPASSWD: /usr/local/sbin/simbioza-setup *',
            $source,
        );
        $this->assertStringContainsString("'/usr/local/sbin/simbioza-setup',\n        'invalid',", $source);
        $this->assertStringContainsString("return \$probe['code'] === 64;", $source);
        $this->assertStringContainsString("'data/config'", $source);
        $this->assertMatchesRegularExpression(
            '/else \{\s*installIdentities\([^;]+;\s*installHelper\(/s',
            $source,
        );
        $this->assertStringContainsString('--quiet --collect --unit="$unit"', $source);
        $this->assertStringContainsString('--property=ExitType=cgroup', $source);
        $this->assertStringNotContainsString('--wait --pipe --collect', $source);
    }

    /**
     * HR: Dohvat taga odvaja fetch i checkout pa ni anotirani tag ne proizvodi
     *     clone upozorenje; detached HEAD savjet se gasi samo pri checkoutu.
     * EN: Tag retrieval separates fetch and checkout so an annotated tag emits
     *     no clone warning; detached-HEAD advice is disabled only for checkout.
     */
    public function testReleaseFetchHandlesAnnotatedTagsWithoutDetachedHeadAdvice(): void
    {
        $updater = (string)file_get_contents(dirname(__DIR__, 3) . '/update.php');
        $this->assertStringContainsString("'refs/tags/' . \$targetTag", $updater);
        $this->assertStringContainsString(
            "'checkout',\n                '--quiet',\n                'FETCH_HEAD'",
            $updater,
        );
        $this->assertStringContainsString("'advice.detachedHead=false'", $updater);
        $this->assertStringNotContainsString('advice.detachedHead=true', $updater);
        $this->assertStringContainsString('2 => STDERR', $updater);
    }

    /**
     * HR: Composer koristi privatni cache procesa, ne cache instalacije ili drugog Unix korisnika.
     * EN: Composer uses a private process cache, not the installation or another Unix user's cache.
     */
    public function testComposerCacheIsPrivateAndEnvironmentIsRestored(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-composer-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/private', 0700, true));
        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $temporary = new \ReflectionProperty($command, 'temporaryDirectory');
        $temporary->setValue($command, $root . '/private');

        $run = new \ReflectionMethod($command, 'mustRunComposer');
        $previousCache = getenv('COMPOSER_CACHE_DIR');
        $previousSuperuser = getenv('COMPOSER_ALLOW_SUPERUSER');
        try {
            putenv('COMPOSER_CACHE_DIR=' . $root . '/existing-cache');
            putenv('COMPOSER_ALLOW_SUPERUSER=0');
            $capture = 'file_put_contents($argv[1], json_encode(['
            . '"cache" => getenv("COMPOSER_CACHE_DIR"),'
            . '"superuser" => getenv("COMPOSER_ALLOW_SUPERUSER")]));';
            foreach (['one', 'two'] as $name) {
                $run->invoke($command, [PHP_BINARY, '-r', $capture, $root . '/' . $name . '.json'], $root);
                $this->assertSame($root . '/existing-cache', getenv('COMPOSER_CACHE_DIR'));
                $this->assertSame('0', getenv('COMPOSER_ALLOW_SUPERUSER'));
            }

            $expected = ['cache' => $root . '/private/composer-cache', 'superuser' => '1'];
            $this->assertSame($expected, json_decode((string)file_get_contents($root . '/one.json'), true));
            $this->assertSame($expected, json_decode((string)file_get_contents($root . '/two.json'), true));
            $this->assertSame(0700, fileperms($expected['cache']) & 0777);
            $this->assertDirectoryDoesNotExist($root . '/existing-cache');
            try {
                $run->invoke($command, [PHP_BINARY, '-r', 'exit(9);'], $root);
                $this->fail('A failed Composer process was accepted.');
            } catch (\RuntimeException $runtimeException) {
                $this->assertStringContainsString('Command failed (9)', $runtimeException->getMessage());
            }

            $this->assertSame($root . '/existing-cache', getenv('COMPOSER_CACHE_DIR'));
            $this->assertSame('0', getenv('COMPOSER_ALLOW_SUPERUSER'));
        } finally {
            putenv($previousCache === false ? 'COMPOSER_CACHE_DIR' : 'COMPOSER_CACHE_DIR=' . $previousCache);
            putenv($previousSuperuser === false
                ? 'COMPOSER_ALLOW_SUPERUSER' : 'COMPOSER_ALLOW_SUPERUSER=' . $previousSuperuser);
        }
    }

    /**
     * HR: Verzija izvornog koda i zaštita održavanja sastavni su dio release paketa.
     *
     * EN: The source version and maintenance guard are part of the release package.
     */
    public function testReleaseMetadataAndMaintenanceGuardArePresent(): void
    {
        $root = dirname(__DIR__, 3);
        $version = trim((string)file_get_contents($root . '/VERSION'));
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);

        $updater = file_get_contents($root . '/update.php');
        $this->assertIsString($updater);
        foreach (['SIMBIOZA_GITHUB_TOKEN', 'GITHUB_TOKEN', 'COMPOSER_AUTH', 'Authorization: Bearer'] as $secret) {
            $this->assertStringNotContainsString($secret, $updater);
        }

        $this->assertStringContainsString("'--no-install'", $updater);
        $this->assertStringContainsString("'COMPOSER_ALLOW_SUPERUSER=1'", $updater);
        $this->assertStringContainsString("'/data/update-vendor-'", $updater);
        $this->assertStringContainsString("'/config/workspace.php'", $updater);
        $this->assertStringContainsString("'--no-owner'", $updater);
        $this->assertStringContainsString("'--no-group'", $updater);
        $this->assertStringContainsString("'--no-perms'", $updater);
        // HR: Sinkronizacija i povratak ne smiju mijenjati mtime tuđih FPM direktorija.
        // EN: Sync and rollback must not set mtimes on FPM-owned directories.
        $this->assertSame(2, substr_count($updater, "'--omit-dir-times'"));
        $this->assertStringContainsString("'/resources/config/theme/'", $updater);
        $this->assertStringContainsString('captureRuntimeSettings', $updater);
        $this->assertStringContainsString('restoreRuntimeSettings', $updater);
        $this->assertStringContainsString('appendMissingMenuSettings', $updater);
        $this->assertStringContainsString('normalizeStoredThemeComponentHeights', $updater);
        $this->assertFileExists($root . '/resources/installation/theme/simbioza.zip');
        $preflightPosition = strpos($updater, '$this->progress(\'preflight\', 78, $this->message(\'preflight\'));');
        $themeUpgradePosition = strpos($updater, '$this->normalizeStoredThemeComponentHeights();');
        $migrationPosition = strpos($updater, '$this->migrationStarted = true;');
        $this->assertIsInt($preflightPosition);
        $this->assertIsInt($themeUpgradePosition);
        $this->assertIsInt($migrationPosition);
        $this->assertLessThan($migrationPosition, $themeUpgradePosition);
        $this->assertLessThan($migrationPosition, $preflightPosition);

        $frontController = file_get_contents($root . '/public/index.php');
        $this->assertIsString($frontController);
        $this->assertStringContainsString('/data/update-maintenance.json', $frontController);
        $this->assertStringContainsString('/settings/setup/application-update-status', $frontController);
        $this->assertStringContainsString('role="progressbar"', $frontController);
        $this->assertStringContainsString('window.setTimeout(poll, 750)', $frontController);
        $maintenancePosition = strpos($frontController, '$updateMaintenanceFile');
        $configuredRootPosition = strpos($frontController, '$configuredAppPath = getenv(\'HPH_APP_PATH\');');
        $autoloadPosition = strpos($frontController, 'require_once $hphAppPath');
        $this->assertIsInt($maintenancePosition);
        $this->assertIsInt($configuredRootPosition);
        $this->assertIsInt($autoloadPosition);
        $this->assertLessThan($maintenancePosition, $configuredRootPosition);
        $this->assertLessThan($autoloadPosition, $maintenancePosition);
    }

    /**
     * HR: GUI izvjestitelj prima ograničenu fazu, postotak i poruku bez promjene CLI izlaza.
     * EN: The GUI reporter receives a restricted stage, percentage, and message without changing CLI output.
     */
    public function testProgressReporterReceivesNormalizedUpdateProgress(): void
    {
        $events = [];
        $command = new ApplicationUpdateCommand(
            dirname(__DIR__, 3),
            ['--lang=en'],
            static function (string $stage, int $progress, string $message) use (&$events): void {
                $events[] = [$stage, $progress, $message];
            },
        );
        $report = new \ReflectionMethod($command, 'reportProgress');
        $report->invoke($command, 'dependencies', 140, 'Updating dependencies.');

        $this->assertSame([['dependencies', 100, 'Updating dependencies.']], $events);
    }

    /**
     * HR: Updater vraća zatečena Unix prava zapisivih putanja umjesto nametanja web-korisnika.
     * EN: The updater restores captured Unix writable-path modes instead of imposing a web user.
     */
    public function testWritablePathModesAreRestoredWithoutAPlatformSpecificUser(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-metadata-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/config', 0770, true));
        $this->assertTrue(mkdir($root . '/data', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/menu', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/theme', 0770, true));
        file_put_contents($root . '/config/workspace.php', "<?php return [];\n");
        file_put_contents($root . '/config/editor-html.php', "<?php return [];\n");
        chmod($root . '/config', 0710);
        chmod($root . '/config/workspace.php', 0640);
        chmod($root . '/config/editor-html.php', 0660);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $capture = new \ReflectionMethod($command, 'capturePreservedPathMetadata');
        $restore = new \ReflectionMethod($command, 'restorePreservedPathMetadata');
        $capture->invoke($command);

        chmod($root . '/config', 0755);
        chmod($root . '/config/workspace.php', 0600);
        chmod($root . '/config/editor-html.php', 0600);
        $restore->invoke($command);

        $this->assertSame(0710, fileperms($root . '/config') & 07777);
        $this->assertSame(0640, fileperms($root . '/config/workspace.php') & 07777);
        $this->assertSame(0660, fileperms($root . '/config/editor-html.php') & 07777);
    }

    /**
     * HR: Restriktivni FPM umask ne smije ostaviti novi Composer vendor nečitljivim web procesu.
     * EN: A restrictive FPM umask must not leave a new Composer vendor unreadable by the web process.
     */
    public function testComposerVendorBecomesWebReadableWithoutChangingWritePermissions(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-vendor-metadata-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/vendor/package/bin', 0770, true));
        file_put_contents($root . '/vendor/autoload.php', "<?php\n");
        file_put_contents($root . '/vendor/package/bin/tool', "#!/bin/sh\n");
        chmod($root . '/vendor', 02770);
        chmod($root . '/vendor/package', 0770);
        chmod($root . '/vendor/package/bin', 0770);
        chmod($root . '/vendor/autoload.php', 0660);
        chmod($root . '/vendor/package/bin/tool', 0770);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $normalize = new \ReflectionMethod($command, 'normalizeComposerVendorMetadata');
        $normalize->invoke($command);

        $this->assertSame(02775, fileperms($root . '/vendor') & 07777);
        $this->assertSame(0775, fileperms($root . '/vendor/package') & 07777);
        $this->assertSame(0664, fileperms($root . '/vendor/autoload.php') & 07777);
        $this->assertSame(0774, fileperms($root . '/vendor/package/bin/tool') & 07777);
    }

    /**
     * HR: Restriktivni FPM umask ne smije nove datoteke izdanja ostaviti
     *     nečitljivima web procesu niti proširiti prava privatnih postavki.
     * EN: A restrictive FPM umask must not leave new release files unreadable
     *     by the web process or widen private-setting permissions.
     */
    public function testReleaseTreeBecomesWebReadableWithoutChangingPrivateSettings(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-release-metadata-' . bin2hex(random_bytes(6));
        $source = $root . '-source';
        $this->temporaryDirectories[] = $root;
        $this->temporaryDirectories[] = $source;
        foreach ([$root, $source] as $directory) {
            $this->assertTrue(mkdir($directory . '/src/Localization', 0770, true));
            $this->assertTrue(mkdir($directory . '/config', 0770, true));
        }

        file_put_contents($source . '/src/Localization/NewLanguageService.php', "<?php\n");
        chmod($source . '/src/Localization/NewLanguageService.php', 0660);
        chmod($source . '/src', 0770);
        chmod($source . '/src/Localization', 0770);
        file_put_contents($source . '/config/workspace.php', "<?php return [];\n");
        file_put_contents($root . '/config/workspace.php', "<?php return ['private' => true];\n");
        chmod($root . '/config/workspace.php', 0600);

        $rsync = '/usr/bin/rsync';
        if (!is_executable($rsync)) {
            $this->markTestSkipped('rsync is not available at /usr/bin/rsync.');
        }

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $sync = new \ReflectionMethod($command, 'syncSource');
        $normalize = new \ReflectionMethod($command, 'normalizeReleaseTreeMetadata');
        $previousUmask = umask(0007);
        try {
            $sync->invoke($command, $rsync, $source);
            clearstatcache(true, $root . '/src/Localization/NewLanguageService.php');
            $this->assertSame(0660, fileperms($root . '/src/Localization/NewLanguageService.php') & 07777);
            clearstatcache(true, $root . '/src/Localization');
            $directoryModeBeforeNormalization = fileperms($root . '/src/Localization') & 07777;
            $normalize->invoke($command, $source);
        } finally {
            umask($previousUmask);
        }

        clearstatcache(true, $root . '/src/Localization/NewLanguageService.php');
        clearstatcache(true, $root . '/src/Localization');
        clearstatcache(true, $root . '/config/workspace.php');
        $this->assertSame(0664, fileperms($root . '/src/Localization/NewLanguageService.php') & 07777);
        $this->assertSame(
            $directoryModeBeforeNormalization | 0005,
            fileperms($root . '/src/Localization') & 07777,
        );
        $this->assertSame(0600, fileperms($root . '/config/workspace.php') & 07777);
        $this->assertSame("<?php return ['private' => true];\n", file_get_contents($root . '/config/workspace.php'));
    }

    /**
     * HR: Sinkronizacija izdanja čuva sve postavke koje administratori mogu mijenjati kroz aplikaciju.
     * EN: Release synchronization preserves every setting administrators can change through the application.
     */
    public function testSourceSyncPreservesRuntimeSettingsAndUpdatesReleaseFiles(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The standalone updater requires rsync on Unix-like release hosts.');
        }

        $rsync = '/usr/bin/rsync';
        if (!is_executable($rsync)) {
            $this->markTestSkipped('rsync is not available at /usr/bin/rsync.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-settings-' . bin2hex(random_bytes(6));
        $source = sys_get_temp_dir() . '/simbioza-update-source-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->temporaryDirectories[] = $source;

        foreach ([$root, $source] as $directory) {
            $this->assertTrue(mkdir($directory . '/config', 0770, true));
            $this->assertTrue(mkdir($directory . '/resources/config/menu', 0770, true));
            $this->assertTrue(mkdir($directory . '/resources/config/theme', 0770, true));
            $this->assertTrue(mkdir($directory . '/resources/config/extensions', 0770, true));
        }

        $preservedFiles = [
            'config/api.php',
            'config/backup.php',
            'config/calendar.php',
            'config/database.php',
            'config/env.php',
            'config/installation.php',
            'config/email.php',
            'config/workspace.php',
            'config/workspace-search.php',
            'config/editor-html.php',
            'config/site-only.php',
            'resources/config/menu/top.json',
            'resources/config/menu/settings.json',
            'resources/config/menu/contexts.json',
            'resources/config/theme/settings.json',
            'resources/config/theme/themes.json',
            'resources/config/extensions/site.json',
        ];
        foreach ($preservedFiles as $relativePath) {
            file_put_contents($root . '/' . $relativePath, 'site:' . $relativePath);
            if (!str_contains($relativePath, 'site-only')) {
                file_put_contents($source . '/' . $relativePath, 'release:' . $relativePath);
            }
        }

        file_put_contents($root . '/config/app.php', 'old static application config');
        file_put_contents($source . '/config/app.php', 'new dynamic release config');

        $preservedInodes = [];
        foreach ($preservedFiles as $relativePath) {
            $inode = fileinode($root . '/' . $relativePath);
            $this->assertIsInt($inode);
            $preservedInodes[$relativePath] = $inode;
        }

        file_put_contents($root . '/config/routes.php', 'old routes');
        file_put_contents($source . '/config/routes.php', 'new release routes');
        file_put_contents($root . '/config/services.php', 'old services');
        file_put_contents($source . '/config/services.php', 'new release services');
        file_put_contents($root . '/config/database.php.dist', 'old database example');
        file_put_contents($source . '/config/database.php.dist', 'new release database example');
        file_put_contents($source . '/config/new-policy.php', 'new release policy');
        file_put_contents($root . '/obsolete.php', 'remove me');
        file_put_contents($source . '/new-release-file.php', 'install me');

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $createTemporaryDirectory = new \ReflectionMethod($command, 'createTemporaryDirectory');
        $temporaryDirectory = $createTemporaryDirectory->invoke($command);
        $this->assertIsString($temporaryDirectory);
        $this->temporaryDirectories[] = $temporaryDirectory;
        $temporaryDirectoryProperty = new \ReflectionProperty($command, 'temporaryDirectory');
        $temporaryDirectoryProperty->setValue($command, $temporaryDirectory);

        $capture = new \ReflectionMethod($command, 'captureRuntimeSettings');
        $sync = new \ReflectionMethod($command, 'syncSource');
        $restore = new \ReflectionMethod($command, 'restoreRuntimeSettings');
        $capture->invoke($command);
        $sync->invoke($command, $rsync, $source);
        $restore->invoke($command);

        foreach ($preservedFiles as $relativePath) {
            $this->assertSame('site:' . $relativePath, file_get_contents($root . '/' . $relativePath));
            $this->assertSame($preservedInodes[$relativePath], fileinode($root . '/' . $relativePath));
        }

        $this->assertSame('new dynamic release config', file_get_contents($root . '/config/app.php'));
        $this->assertSame('new release routes', file_get_contents($root . '/config/routes.php'));
        $this->assertSame('new release services', file_get_contents($root . '/config/services.php'));
        $this->assertSame('new release database example', file_get_contents($root . '/config/database.php.dist'));
        $this->assertSame('new release policy', file_get_contents($root . '/config/new-policy.php'));
        $this->assertSame('install me', file_get_contents($root . '/new-release-file.php'));
        $this->assertFileDoesNotExist($root . '/obsolete.php');
    }

    /**
     * HR: Stari statički app.php prije zamjene postaje trajni modules.php,
     *     a postojeće stanje modula nikada se ne prepisuje.
     * EN: A legacy static app.php becomes persistent modules.php before it is
     *     replaced, while existing module state is never overwritten.
     */
    public function testLegacyModuleSelectionIsExtractedOnlyOnce(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-modules-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/config', 0770, true));
        file_put_contents($root . '/config/app.php', <<<'PHP'
<?php

return [
    'modules' => [
        'enabled' => [
            'aaieduhr/heartphrame-module-orm',
            'aaieduhr/heartphrame-module-theme',
        ],
    ],
];
PHP);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $extract = new \ReflectionMethod($command, 'ensureModuleStateConfig');
        $extract->invoke($command);

        $statePath = $root . '/data/config/modules.php';
        $state = require $statePath;
        $this->assertSame([
            'aaieduhr/heartphrame-module-orm',
            'aaieduhr/heartphrame-module-theme',
        ], $state['enabled']);
        $this->assertSame([], $state['removed']);
        $this->assertSame(0660, fileperms($statePath) & 0777);

        file_put_contents($statePath, "<?php return ['enabled' => ['kept'], 'removed' => ['theme']];\n");
        $extract->invoke($command);
        $this->assertSame(['enabled' => ['kept'], 'removed' => ['theme']], require $statePath);
    }

    /**
     * HR: Nadogradnja tema dodaje samo nedostajuće visine i čuva svaku postojeću postavku.
     * EN: Theme upgrading adds only missing heights and preserves every existing setting.
     */
    public function testThemeHeightUpgradePreservesExistingConfiguration(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-theme-heights-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/resources/config/theme', 0770, true));
        $path = $root . '/resources/config/theme/themes.json';
        $themes = [
            [
                'id' => 'legacy',
                'components' => [
                    'header' => ['sticky' => true],
                    'navigation' => ['height_px' => 61],
                    'content' => ['surface' => 'title-card'],
                ],
                'light' => ['colors' => ['primary' => '#123456']],
            ],
            [
                'id' => 'configured',
                'components' => [
                    'header' => ['height_px' => 94],
                    'navigation' => ['height_px' => 65],
                ],
            ],
        ];
        file_put_contents($path, json_encode($themes, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        chmod($path, 0660);
        clearstatcache(true, $path);
        $originalInode = fileinode($path);
        $originalOwner = fileowner($path);
        $originalGroup = filegroup($path);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $normalize = new \ReflectionMethod($command, 'normalizeStoredThemeComponentHeights');
        $this->assertSame(1, $normalize->invoke($command));

        $stored = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($stored);
        $this->assertSame(72, $stored[0]['components']['header']['height_px']);
        $this->assertSame(61, $stored[0]['components']['navigation']['height_px']);
        $this->assertTrue($stored[0]['components']['header']['sticky']);
        $this->assertSame('title-card', $stored[0]['components']['content']['surface']);
        $this->assertSame('#123456', $stored[0]['light']['colors']['primary']);
        $this->assertSame(94, $stored[1]['components']['header']['height_px']);
        $this->assertSame(65, $stored[1]['components']['navigation']['height_px']);
        $this->assertSame(0, $normalize->invoke($command));
        clearstatcache(true, $path);
        $this->assertSame($originalInode, fileinode($path));
        $this->assertSame($originalOwner, fileowner($path));
        $this->assertSame($originalGroup, filegroup($path));
        $this->assertSame(0660, fileperms($path) & 0777);
    }

    /**
     * HR: Nadogradnja dodaje nove postavke modula na kraj bez promjene ijedne
     *     zatečene stavke ili administratorskog redoslijeda.
     * EN: An update appends new module settings without changing any existing
     *     entry or administrator-defined order.
     */
    public function testMenuSettingsUpgradeAppendsOnlyMissingReleaseEntries(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-menu-settings-' . bin2hex(random_bytes(6));
        $source = $root . '-source';
        $this->temporaryDirectories[] = $root;
        $this->temporaryDirectories[] = $source;
        foreach ([$root, $source] as $directory) {
            $this->assertTrue(mkdir($directory . '/resources/config/menu', 0770, true));
        }

        $current = [
            ['id' => 'menu', 'order' => 25, 'enabled' => false, 'custom' => 'keep'],
            ['id' => 'auth', 'order' => 70, 'enabled' => true],
            [
                'id' => 'setup',
                'order' => 75,
                'enabled' => false,
                'children' => [['id' => 'setup.index', 'order' => 30, 'label' => 'My modules']],
            ],
        ];
        $release = [
            ['id' => 'menu', 'order' => 10, 'enabled' => true, 'custom' => 'replace'],
            [
                'id' => 'setup',
                'order' => 5,
                'enabled' => true,
                'children' => [
                    ['id' => 'setup.index', 'order' => 10, 'label' => 'Modules'],
                    ['id' => 'accessibility.settings', 'order' => 20, 'label' => 'Accessibility'],
                ],
            ],
            ['id' => 'future-module', 'order' => 15, 'enabled' => true],
        ];
        file_put_contents(
            $root . '/resources/config/menu/settings.json',
            json_encode($current, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        chmod($root . '/resources/config/menu/settings.json', 0660);
        clearstatcache(true, $root . '/resources/config/menu/settings.json');
        $originalInode = fileinode($root . '/resources/config/menu/settings.json');
        $originalOwner = fileowner($root . '/resources/config/menu/settings.json');
        $originalGroup = filegroup($root . '/resources/config/menu/settings.json');
        file_put_contents(
            $source . '/resources/config/menu/settings.json',
            json_encode($release, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $append = new \ReflectionMethod($command, 'appendMissingMenuSettings');
        $this->assertSame(2, $append->invoke($command, $source));
        $this->assertSame(0, $append->invoke($command, $source));

        $stored = json_decode(
            (string)file_get_contents($root . '/resources/config/menu/settings.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame($current[0], $stored[0]);
        $this->assertSame($current[1], $stored[1]);
        $this->assertSame('setup', $stored[2]['id']);
        $this->assertSame(75, $stored[2]['order']);
        $this->assertFalse($stored[2]['enabled']);
        $this->assertSame($current[2]['children'][0], $stored[2]['children'][0]);
        $this->assertSame('accessibility.settings', $stored[2]['children'][1]['id']);
        $this->assertSame(40, $stored[2]['children'][1]['order']);
        $this->assertSame('future-module', $stored[3]['id']);
        $this->assertSame(85, $stored[3]['order']);
        clearstatcache(true, $root . '/resources/config/menu/settings.json');
        $this->assertSame($originalInode, fileinode($root . '/resources/config/menu/settings.json'));
        $this->assertSame($originalOwner, fileowner($root . '/resources/config/menu/settings.json'));
        $this->assertSame($originalGroup, filegroup($root . '/resources/config/menu/settings.json'));
        $this->assertSame(0660, fileperms($root . '/resources/config/menu/settings.json') & 0777);
    }

    /**
     * HR: Nova release config datoteka zadržava vlasnika deploy procesa i ostaje čitljiva web-procesu.
     * EN: A new release-managed config file keeps the deploy-process owner and remains web-readable.
     */
    public function testReleaseConfigurationFileInheritsSafeMetadata(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-new-config-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/config', 0710, true));
        $this->assertTrue(mkdir($root . '/data', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/menu', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/theme', 0770, true));
        file_put_contents($root . '/config/workspace.php', "<?php return [];\n");

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $capture = new \ReflectionMethod($command, 'capturePreservedPathMetadata');
        $normalize = new \ReflectionMethod($command, 'normalizeReleaseConfigFileMetadata');
        $capture->invoke($command);

        file_put_contents($root . '/config/editor-html.php', "<?php return [];\n");
        chmod($root . '/config/editor-html.php', 0600);
        $owner = fileowner($root . '/config/editor-html.php');
        $group = filegroup($root . '/config/editor-html.php');
        $normalize->invoke($command);

        $this->assertSame(0644, fileperms($root . '/config/editor-html.php') & 07777);
        $this->assertSame($owner, fileowner($root . '/config/editor-html.php'));
        $this->assertSame($group, filegroup($root . '/config/editor-html.php'));
    }

    /**
     * HR: Updater zadržava prava postojećih trajnih konfiguracija i normalizira samo nove release datoteke.
     * EN: The updater keeps existing persistent-config modes and normalizes only new release files.
     */
    public function testExistingRuntimeConfigurationMetadataIsPreserved(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $root = sys_get_temp_dir() . '/simbioza-update-existing-config-' . bin2hex(random_bytes(6));
        $this->temporaryDirectories[] = $root;
        $this->assertTrue(mkdir($root . '/config', 0710, true));
        $this->assertTrue(mkdir($root . '/data', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/menu', 0770, true));
        $this->assertTrue(mkdir($root . '/resources/config/theme', 0770, true));
        file_put_contents($root . '/config/workspace.php', "<?php return [];\n");
        file_put_contents($root . '/config/editor-html.php', "<?php return [];\n");
        file_put_contents($root . '/config/api.php', "<?php return [];\n");
        chmod($root . '/config/workspace.php', 0600);
        chmod($root . '/config/editor-html.php', 0600);
        chmod($root . '/config/api.php', 0600);

        $command = new ApplicationUpdateCommand($root, ['--lang=en']);
        $capture = new \ReflectionMethod($command, 'capturePreservedPathMetadata');
        $normalize = new \ReflectionMethod($command, 'normalizeReleaseConfigFileMetadata');
        $capture->invoke($command);
        $normalize->invoke($command);

        $this->assertSame(0600, fileperms($root . '/config/api.php') & 07777);
        $this->assertSame(0600, fileperms($root . '/config/workspace.php') & 07777);
        $this->assertSame(0600, fileperms($root . '/config/editor-html.php') & 07777);
    }
}
