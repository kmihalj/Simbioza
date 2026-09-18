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
     * HR: Savjet o detached HEAD stanju isključen je samo za dohvat taga, bez skrivanja grešaka.
     * EN: Detached HEAD advice is disabled only for the tag clone without hiding errors.
     */
    public function testReleaseCloneDisablesOnlyDetachedHeadAdvice(): void
    {
        $updater = (string)file_get_contents(dirname(__DIR__, 3) . '/update.php');
        $this->assertMatchesRegularExpression(
            '/\$this->mustRun\(\[\s*\$git,\s*\'-c\',\s*\'advice\.detachedHead=false\',\s*\'clone\',/',
            $updater,
        );
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
        $this->assertStringContainsString("'/resources/config/theme/'", $updater);
        $this->assertStringContainsString('captureRuntimeSettings', $updater);
        $this->assertStringContainsString('restoreRuntimeSettings', $updater);
        $this->assertStringContainsString('appendMissingMenuSettings', $updater);
        $this->assertStringContainsString('normalizeStoredThemeComponentHeights', $updater);
        $this->assertFileExists($root . '/resources/installation/theme/simbioza.zip');
        $preflightPosition = strpos($updater, '$this->write($this->message(\'preflight\'));');
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
        $maintenancePosition = strpos($frontController, '$updateMaintenanceFile');
        $autoloadPosition = strpos($frontController, 'require_once $hphAppPath');
        $this->assertIsInt($maintenancePosition);
        $this->assertIsInt($autoloadPosition);
        $this->assertLessThan($autoloadPosition, $maintenancePosition);
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
            'config/app.php',
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

        $this->assertSame('new release routes', file_get_contents($root . '/config/routes.php'));
        $this->assertSame('new release services', file_get_contents($root . '/config/services.php'));
        $this->assertSame('new release database example', file_get_contents($root . '/config/database.php.dist'));
        $this->assertSame('new release policy', file_get_contents($root . '/config/new-policy.php'));
        $this->assertSame('install me', file_get_contents($root . '/new-release-file.php'));
        $this->assertFileDoesNotExist($root . '/obsolete.php');
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
        ];
        $release = [
            ['id' => 'menu', 'order' => 10, 'enabled' => true, 'custom' => 'replace'],
            ['id' => 'setup', 'order' => 5, 'enabled' => true],
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
        $this->assertSame(80, $stored[2]['order']);
        $this->assertSame('future-module', $stored[3]['id']);
        $this->assertSame(90, $stored[3]['order']);
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
